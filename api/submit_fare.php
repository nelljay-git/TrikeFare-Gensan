<?php
require 'db.php';
require_once 'middleware.php';
require_once 'utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['origin']) || !isset($data['destination']) || !isset($data['fare']) || !isset($data['transport_type']) || !isset($data['time_tag'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$origin = trim($data['origin']);
$destination = trim($data['destination']);
$fare = floatval($data['fare']);
$transport_type = trim($data['transport_type']);
$time_tag = trim($data['time_tag']);
$username = isset($data['username']) ? trim($data['username']) : null;

// New optional fields for distance-based filtering
$distance_km = isset($data['distance_km']) ? floatval($data['distance_km']) : null;
$origin_lat = isset($data['origin_lat']) ? floatval($data['origin_lat']) : null;
$origin_lng = isset($data['origin_lng']) ? floatval($data['origin_lng']) : null;
$dest_lat = isset($data['dest_lat']) ? floatval($data['dest_lat']) : null;
$dest_lng = isset($data['dest_lng']) ? floatval($data['dest_lng']) : null;

// Optional note (max 100 chars)
$note = null;
if (isset($data['note']) && $data['note'] !== null) {
    $note = mb_substr(trim($data['note']), 0, 100);
    if ($note === '')
        $note = null;
}

if ($fare <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Fare must be greater than zero']);
    exit;
}

try {
    /* =========================
    STEP 1: Smart Deduplication Check
    ========================= */
    // Only attempt deduplication if we have a username to associate the vote with
    if ($username) {
        $time_condition = "";
        if ($time_tag === 'night') {
            $time_condition = " AND time_tag = 'night'";
        } else {
            $time_condition = " AND time_tag IN ('day', 'rush_hour')";
        }

        // Fetch potential matches: same transport type, same time tag (broadly), and exact fare
        $sql = "SELECT id, origin, destination, fare, rating, distance_km, origin_lat, origin_lng, dest_lat, dest_lng 
                    FROM community_fares 
                    WHERE transport_type = ? AND ABS(fare - ?) < 0.01 $time_condition";

        $params = [$transport_type, $fare];

        // If distance is provided, filter by ±4km range (as per requirement ±1-4km)
        if ($distance_km !== null) {
            $sql .= " AND distance_km BETWEEN ? AND ?";
            $params[] = $distance_km - 4.0;
            $params[] = $distance_km + 4.0;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $candidates = $stmt->fetchAll();

        $bestMatch = null;
        $maxScore = 0;

        foreach ($candidates as $cand) {
            $score = isRouteMatch(
                $origin,
                $origin_lat,
                $origin_lng,
                $cand['origin'],
                $cand['origin_lat'],
                $cand['origin_lng'],
                $destination,
                $dest_lat,
                $dest_lng,
                $cand['destination'],
                $cand['dest_lat'],
                $cand['dest_lng']
            );

            if ($score >= 50) {
                // Tie-break: highest matching score, then highest rating, then closest distance
                if ($score > $maxScore) {
                    $maxScore = $score;
                    $bestMatch = $cand;
                } else if (abs($score - $maxScore) < 0.01) {
                    if ($bestMatch === null || $cand['rating'] > $bestMatch['rating']) {
                        $bestMatch = $cand;
                    } else if ($cand['rating'] == $bestMatch['rating'] && $distance_km !== null) {
                        $d1 = abs($cand['distance_km'] - $distance_km);
                        $d2 = abs($bestMatch['distance_km'] - $distance_km);
                        if ($d1 < $d2)
                            $bestMatch = $cand;
                    }
                }
            }
        }

        if ($bestMatch) {
            /* =========================
            STEP 2: Register as Upvote
            ========================= */
            $fareId = $bestMatch['id'];

            // Check if already voted
            $checkStmt = $pdo->prepare("SELECT id, vote_type FROM fare_votes WHERE fare_id = ? AND username = ?");
            $checkStmt->execute([$fareId, $username]);
            $existingVote = $checkStmt->fetch();

            if ($existingVote) {
                if ($existingVote['vote_type'] === 'upvote') {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Matching fare found! You already upvoted this entry.',
                        'id' => $fareId,
                        'is_vote' => true
                    ]);
                    exit;
                } else {
                    // Change downvote to upvote
                    $updateVoteStmt = $pdo->prepare("UPDATE fare_votes SET vote_type = 'upvote' WHERE id = ?");
                    $updateVoteStmt->execute([$existingVote['id']]);

                    $updateRatingStmt = $pdo->prepare("UPDATE community_fares SET rating = rating + 2 WHERE id = ?");
                    $updateRatingStmt->execute([$fareId]);
                }
            } else {
                // New upvote
                $insertVoteStmt = $pdo->prepare("INSERT INTO fare_votes (fare_id, username, vote_type) VALUES (?, ?, 'upvote')");
                $insertVoteStmt->execute([$fareId, $username]);

                $updateRatingStmt = $pdo->prepare("UPDATE community_fares SET rating = rating + 1 WHERE id = ?");
                $updateRatingStmt->execute([$fareId]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Matching fare found! Registered your submission as an upvote.',
                'id' => $fareId,
                'is_vote' => true
            ]);
            exit;
        }
    }

    /* =========================
    STEP 3: Normal Insertion
    ========================= */
    $stmt = $pdo->prepare("INSERT INTO community_fares (origin, destination, fare, transport_type, time_tag, distance_km, origin_lat, origin_lng, dest_lat, dest_lng, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$origin, $destination, $fare, $transport_type, $time_tag, $distance_km, $origin_lat, $origin_lng, $dest_lat, $dest_lng, $note]);

    $newId = $pdo->lastInsertId();

    // Automatically upvote own submission if username is provided
    if ($username) {
        $pdo->prepare("INSERT INTO fare_votes (fare_id, username, vote_type) VALUES (?, ?, 'upvote')")->execute([$newId, $username]);
        $pdo->prepare("UPDATE community_fares SET rating = rating + 1 WHERE id = ?")->execute([$newId]);
    }

    echo json_encode(['success' => true, 'id' => $newId, 'is_vote' => false]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save fare']);
}
?>