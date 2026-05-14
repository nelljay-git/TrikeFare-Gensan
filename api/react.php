<?php
require_once 'db.php';
validateAuth();
checkRateLimit('react', 60, 60); // 1 reaction per second avg

$data = json_decode(file_get_contents('php://input'), true);

$target_type = $data['target_type'] ?? ''; // 'post', 'comment', 'reply'
$target_id = (int)($data['target_id'] ?? 0);
$reaction_type = $data['reaction_type'] ?? ''; // 'like', 'dislike'
$user_identifier = $_SERVER['REMOTE_ADDR']; // Using IP as identifier

$allowed_targets = ['post', 'comment', 'reply'];
$allowed_reactions = ['like', 'dislike'];

if (!in_array($target_type, $allowed_targets) || !$target_id || !in_array($reaction_type, $allowed_reactions)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data.']);
    exit;
}

try {
    // 1. Check if user already reacted to this specific item
    $stmt = $pdo->prepare("SELECT reaction_type FROM reactions WHERE target_type = ? AND target_id = ? AND user_identifier = ?");
    $stmt->execute([$target_type, $target_id, $user_identifier]);
    $existing = $stmt->fetch();

    $table = $target_type . 's'; // posts, comments, replies
    if ($target_type === 'reply') $table = 'replies'; // Special case if plural is different, but here it's replies

    if ($existing) {
        $old_reaction = $existing['reaction_type'];

        if ($old_reaction === $reaction_type) {
            // Toggle OFF: User clicked the same reaction again
            $pdo->prepare("DELETE FROM reactions WHERE target_type = ? AND target_id = ? AND user_identifier = ?")->execute([$target_type, $target_id, $user_identifier]);
            $pdo->prepare("UPDATE $table SET " . $reaction_type . "s = GREATEST(0, " . $reaction_type . "s - 1) WHERE id = ?")->execute([$target_id]);
            $action = 'removed';
        } else {
            // Switch reaction: e.g., Like -> Dislike
            $pdo->prepare("UPDATE reactions SET reaction_type = ? WHERE target_type = ? AND target_id = ? AND user_identifier = ?")->execute([$reaction_type, $target_type, $target_id, $user_identifier]);
            $pdo->prepare("UPDATE $table SET " . $old_reaction . "s = GREATEST(0, " . $old_reaction . "s - 1), " . $reaction_type . "s = " . $reaction_type . "s + 1 WHERE id = ?")->execute([$target_id]);
            $action = 'switched';
        }
    } else {
        // New reaction
        $pdo->prepare("INSERT INTO reactions (target_type, target_id, reaction_type, user_identifier) VALUES (?, ?, ?, ?)")->execute([$target_type, $target_id, $reaction_type, $user_identifier]);
        $pdo->prepare("UPDATE $table SET " . $reaction_type . "s = " . $reaction_type . "s + 1 WHERE id = ?")->execute([$target_id]);
        $action = 'added';
    }

    // Get new counts for the UI
    $stmt = $pdo->prepare("SELECT likes, dislikes FROM $table WHERE id = ?");
    $stmt->execute([$target_id]);
    $counts = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'action' => $action,
        'likes' => $counts['likes'],
        'dislikes' => $counts['dislikes'],
        'user_reaction' => ($action === 'removed' ? null : $reaction_type)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
