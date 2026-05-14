<?php
require 'db.php';
require_once 'middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || !isset($data['action']) || !isset($data['username'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields (id, action, username)']);
    exit;
}

$id = intval($data['id']);
$action = $data['action']; // 'upvote' or 'downvote'
$username = trim($data['username']);

if (empty($username)) {
    http_response_code(400);
    echo json_encode(['error' => 'Username is required']);
    exit;
}

if (!in_array($action, ['upvote', 'downvote'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

$increment = ($action === 'upvote') ? 1 : -1;

try {
    // Check if this user already voted on this fare
    $checkStmt = $pdo->prepare("SELECT id, vote_type FROM fare_votes WHERE fare_id = ? AND username = ?");
    $checkStmt->execute([$id, $username]);
    $existingVote = $checkStmt->fetch();

    if ($existingVote) {
        if ($existingVote['vote_type'] === $action) {
            // User already voted with the same action
            echo json_encode([
                'success' => false,
                'error' => 'already_voted',
                'message' => 'You have already voted on this fare entry.',
                'existing_vote' => $existingVote['vote_type']
            ]);
            exit;
        } else {
            // User is changing their vote
            $updateVoteStmt = $pdo->prepare("UPDATE fare_votes SET vote_type = ? WHERE id = ?");
            $updateVoteStmt->execute([$action, $existingVote['id']]);

            // If changing from downvote(-1) to upvote(+1) => rating increases by 2
            // If changing from upvote(+1) to downvote(-1) => rating decreases by 2
            $ratingChange = ($action === 'upvote') ? 2 : -2;

            $updateRatingStmt = $pdo->prepare("UPDATE community_fares SET rating = rating + ? WHERE id = ?");
            $updateRatingStmt->execute([$ratingChange, $id]);
        }
    } else {
        // Record new vote
        $insertStmt = $pdo->prepare("INSERT INTO fare_votes (fare_id, username, vote_type) VALUES (?, ?, ?)");
        $insertStmt->execute([$id, $username, $action]);

        // Update the fare rating normally
        $updateStmt = $pdo->prepare("UPDATE community_fares SET rating = rating + ? WHERE id = ?");
        $updateStmt->execute([$increment, $id]);
    }

    // Get the updated rating and individual counts
    $countsStmt = $pdo->prepare("
        SELECT rating,
               (SELECT COUNT(*) FROM fare_votes WHERE fare_id = ? AND vote_type = 'upvote') as upvotes,
               (SELECT COUNT(*) FROM fare_votes WHERE fare_id = ? AND vote_type = 'downvote') as downvotes
        FROM community_fares WHERE id = ?
    ");
    $countsStmt->execute([$id, $id, $id]);
    $result = $countsStmt->fetch();
    
    echo json_encode([
        'success' => true,
        'new_rating' => intval($result['rating']),
        'new_upvotes' => intval($result['upvotes']),
        'new_downvotes' => intval($result['downvotes'])
    ]);
} catch (PDOException $e) {
    // Handle unique constraint violation (race condition safety)
    if ($e->getCode() == 23000) {
        echo json_encode([
            'success' => false,
            'error' => 'already_voted',
            'message' => 'You have already voted on this fare entry.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update rating']);
    }
}
?>
