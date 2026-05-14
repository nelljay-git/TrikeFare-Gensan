<?php
require 'db.php';
require_once 'middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$origin = isset($_GET['origin']) ? trim($_GET['origin']) : '';
$destination = isset($_GET['destination']) ? trim($_GET['destination']) : '';
$transport_type = isset($_GET['transport_type']) ? trim($_GET['transport_type']) : 'tricycle';
$target_distance = isset($_GET['distance_km']) ? floatval($_GET['distance_km']) : null;
$time_period = isset($_GET['time_period']) ? trim($_GET['time_period']) : 'auto';

$req_origin_lat = isset($_GET['origin_lat']) && $_GET['origin_lat'] !== '' ? floatval($_GET['origin_lat']) : null;
$req_origin_lng = isset($_GET['origin_lng']) && $_GET['origin_lng'] !== '' ? floatval($_GET['origin_lng']) : null;
$req_dest_lat = isset($_GET['dest_lat']) && $_GET['dest_lat'] !== '' ? floatval($_GET['dest_lat']) : null;
$req_dest_lng = isset($_GET['dest_lng']) && $_GET['dest_lng'] !== '' ? floatval($_GET['dest_lng']) : null;

if (empty($origin) && empty($destination) && $req_dest_lat === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Origin/destination or coordinates are required']);
    exit;
}

require_once 'utils.php';

try {

    /* =========================
       STEP 1: Fetch candidate fares
    ========================= */
    $time_condition = "";
    if ($time_period === 'night') {
        $time_condition = " AND time_tag = 'night'";
    } else if ($time_period === 'day') {
        $time_condition = " AND time_tag IN ('day', 'rush_hour')";
    }

    // Fetch all valid fares for the transport type to allow hybrid matching
    $sql = "SELECT id, origin, destination, fare, transport_type, time_tag, rating,
                   (SELECT COUNT(*) FROM fare_votes WHERE fare_id = community_fares.id AND vote_type = 'upvote') as upvote,
                   (SELECT COUNT(*) FROM fare_votes WHERE fare_id = community_fares.id AND vote_type = 'downvote') as downvote,
                   distance_km, origin_lat, origin_lng, dest_lat, dest_lng,
                   COALESCE(note, '') AS note, created_at
            FROM community_fares
            WHERE transport_type = ?
            AND rating > -5
            $time_condition";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$transport_type]);
    $allFares = $stmt->fetchAll();

    if (count($allFares) === 0) {
        echo json_encode(['count' => 0]);
        exit;
    }

    /* =========================
       STEP 2: Hybrid Matching (Name + Coordinate)
    ========================= */
    $matchedFares = [];
    $RADIUS_THRESHOLD_KM = 0.5;

    foreach ($allFares as $row) {
        $conf = isRouteMatch(
            $origin, $req_origin_lat, $req_origin_lng,
            $row['origin'], $row['origin_lat'], $row['origin_lng'],
            $destination, $req_dest_lat, $req_dest_lng,
            $row['destination'], $row['dest_lat'], $row['dest_lng'],
            $RADIUS_THRESHOLD_KM
        );

        if ($conf > 0) {
            $row['overall_confidence'] = $conf;
            $matchedFares[] = $row;
        }
    }

    if (count($matchedFares) === 0) {
        echo json_encode([
            'count' => 0,
            'message' => 'No matching fares found'
        ]);
        exit;
    }

    /* =========================
       STEP 3: Distance Filter & Deduplication
    ========================= */
    $filteredFares = $matchedFares;

    if ($target_distance !== null && $target_distance > 0) {
        $tolerance = 4.0;
        $minDist = $target_distance - $tolerance;
        $maxDist = $target_distance + $tolerance;

        $filteredFares = array_filter($matchedFares, function ($f) use ($minDist, $maxDist) {
            $d = floatval($f['distance_km']);
            return ($d >= $minDist && $d <= $maxDist);
        });
        $filteredFares = array_values($filteredFares);
    }

    if (count($filteredFares) === 0) {
        echo json_encode([
            'count' => 0,
            'message' => 'No fares within estimated distance range'
        ]);
        exit;
    }

    // Deduplication Layer: Keep one representative per fare + distance range (bucket)
    $deduped = [];
    foreach ($filteredFares as $f) {
        $dist = $f['distance_km'] !== null ? floatval($f['distance_km']) : null;
        $bucket = $dist !== null ? round($dist * 2) / 2 : 'nodist'; // 0.5km buckets
        $fare = number_format(floatval($f['fare']), 2);
        $key = "{$bucket}_{$fare}";

        if (!isset($deduped[$key])) {
            $deduped[$key] = $f;
        } else {
            // Keep the one with higher confidence, or higher rating
            $existing = $deduped[$key];
            if (
                $f['overall_confidence'] > $existing['overall_confidence'] ||
                ($f['overall_confidence'] == $existing['overall_confidence'] && $f['rating'] > $existing['rating'])
            ) {
                $deduped[$key] = $f;
            }
        }
    }
    $filteredFares = array_values($deduped);

    /* =========================
       STEP 4: Sort fares by Ranking
    ========================= */
    usort($filteredFares, function ($a, $b) use ($target_distance) {
        // Primary: Rating DESC
        if (($b['rating'] ?? 0) !== ($a['rating'] ?? 0)) {
            return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
        }
        // Secondary: Overall Confidence DESC
        if (abs($a['overall_confidence'] - $b['overall_confidence']) > 0.01) {
            return $b['overall_confidence'] <=> $a['overall_confidence'];
        }
        // Tertiary: Proximity to target distance ASC
        if ($target_distance !== null && $a['distance_km'] !== null && $b['distance_km'] !== null) {
            $aDiff = abs(floatval($a['distance_km']) - $target_distance);
            $bDiff = abs(floatval($b['distance_km']) - $target_distance);
            if (abs($aDiff - $bDiff) > 0.01) {
                return $aDiff <=> $bDiff;
            }
        }
        // Quaternary: Fare ASC
        return $a['fare'] <=> $b['fare'];
    });



    $fareValues = array_map('floatval', array_column($filteredFares, 'fare'));
    sort($fareValues);

    /* =========================
       STEP 5: IQR OUTLIER REMOVAL
    ========================= */
    $count = count($fareValues);

    if ($count >= 4) {
        $q1 = getPercentile($fareValues, 25);
        $q3 = getPercentile($fareValues, 75);
        $iqr = $q3 - $q1;

        $lower = $q1 - (1.5 * $iqr);
        $upper = $q3 + (1.5 * $iqr);

        $cleanFares = [];

        foreach ($filteredFares as $f) {
            $fare = floatval($f['fare']);
            if ($fare >= $lower && $fare <= $upper) {
                $cleanFares[] = $f;
            }
        }

        if (count($cleanFares) > 0) {
            $filteredFares = $cleanFares;
            $fareValues = array_map('floatval', array_column($filteredFares, 'fare'));
        }
    }

    /* =========================
       STEP 6: Weighted Average
    ========================= */
    $now = time();
    $thirtyDaysAgo = $now - (30 * 86400);

    $weightedSum = 0;
    $totalWeight = 0;

    foreach ($filteredFares as $f) {

        $fare = floatval($f['fare']);
        $rating = intval($f['rating']);
        $created = strtotime($f['created_at']);

        $weight = 1.0;

        // recency boost
        if ($created >= $thirtyDaysAgo) {
            $weight += 0.3;
        }

        // rating boost
        if ($rating > 0) {
            $weight += min($rating * 0.1, 0.5);
        }

        $weightedSum += $fare * $weight;
        $totalWeight += $weight;
    }

    $weightedAverage = $totalWeight > 0 ? $weightedSum / $totalWeight : 0;

    /* =========================
       STEP 7: Stats
    ========================= */
    $validCount = count($fareValues);

    $average = array_sum($fareValues) / max($validCount, 1);
    $median = getPercentile($fareValues, 50);
    $min = min($fareValues);
    $max = max($fareValues);

    /* =========================
       STEP 8: Last updated
    ========================= */
    $lastUpdated = $filteredFares[0]['created_at'];

    foreach ($filteredFares as $f) {
        if ($f['created_at'] > $lastUpdated) {
            $lastUpdated = $f['created_at'];
        }
    }

    /* =========================
       FINAL RESPONSE
    ========================= */
    echo json_encode([
        'count' => $validCount,
        'average' => round($average, 2),
        'weighted_average' => round($weightedAverage, 2),
        'median' => round($median, 2),
        'min' => round($min, 2),
        'max' => round($max, 2),
        'last_updated' => $lastUpdated,
        'raw_submissions' => $filteredFares
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>