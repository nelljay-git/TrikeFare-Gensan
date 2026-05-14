<?php
require_once 'db.php';
validateAuth();
checkRateLimit('comments_fetch', 100, 60);

$post_id = $_GET['post_id'] ?? 0;

if (!$post_id) {
    echo json_encode([]);
    exit;
}

$user_identifier = $_SERVER['REMOTE_ADDR'];

try {
    // Fetch comments
    $stmt = $pdo->prepare("SELECT c.id, c.username, c.content, c.likes, c.dislikes, c.created_at,
                           (SELECT r.reaction_type FROM reactions r WHERE r.target_type = 'comment' AND r.target_id = c.id AND r.user_identifier = ?) as user_reaction
                           FROM comments c WHERE c.post_id = ? ORDER BY c.created_at ASC");
    $stmt->execute([$user_identifier, $post_id]);
    $comments = $stmt->fetchAll();

    foreach ($comments as &$comment) {
        // Fetch all replies for each comment (including nested ones)
        $stmt_replies = $pdo->prepare("SELECT rp.id, rp.parent_reply_id, rp.username, rp.content, rp.likes, rp.dislikes, rp.created_at,
                                       (SELECT r.reaction_type FROM reactions r WHERE r.target_type = 'reply' AND r.target_id = rp.id AND r.user_identifier = ?) as user_reaction
                                       FROM replies rp WHERE rp.comment_id = ? ORDER BY rp.created_at ASC");
        $stmt_replies->execute([$user_identifier, $comment['id']]);
        $replies = $stmt_replies->fetchAll();
        $comment['replies'] = $replies;
    }

    echo json_encode($comments);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server Error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>