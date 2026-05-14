<?php
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

$post_id = $data['post_id'] ?? 0;
$reaction_type = $data['reaction_type'] ?? '';
$user_identifier = $_SERVER['REMOTE_ADDR']; // Using IP as identifier

if (!$post_id || !in_array($reaction_type, ['helpful', 'not_accurate'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data.']);
    exit;
}

try {
    // Check if reaction already exists
    $stmt = $pdo->prepare("SELECT reaction_type FROM reactions WHERE post_id = ? AND user_identifier = ?");
    $stmt->execute([$post_id, $user_identifier]);
    $existing = $stmt->fetch();

    if ($existing) {
        http_response_code(403);
        echo json_encode(['error' => 'You have already reacted to this post.']);
        exit;
    }

    // Insert reaction
    $stmt = $pdo->prepare("INSERT INTO reactions (post_id, reaction_type, user_identifier) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $reaction_type, $user_identifier]);

    // Update post upvotes if helpful, decrement if not_accurate (or just track separately)
    // The requirement says "Sort by most upvoted", so I'll increment 'upvotes' for 'helpful' 
    // and maybe decrement for 'not_accurate' to keep it simple as a ranking system.
    if ($reaction_type === 'helpful') {
        $stmt = $pdo->prepare("UPDATE posts SET upvotes = upvotes + 1 WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE posts SET upvotes = upvotes - 1 WHERE id = ?");
    }
    $stmt->execute([$post_id]);

    // Get new count
    $stmt = $pdo->prepare("SELECT upvotes FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $new_count = $stmt->fetchColumn();

    echo json_encode(['success' => true, 'new_upvotes' => $new_count]);

} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry
        http_response_code(403);
        echo json_encode(['error' => 'You have already reacted to this post.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error.']);
    }
}
?>
