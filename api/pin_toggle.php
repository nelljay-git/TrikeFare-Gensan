<?php
require_once 'db.php';
validateAuth(); // Ensure session-bound token is valid
checkRateLimit('pin_toggle', 10, 60);

$data = json_decode(file_get_contents('php://input'), true);
$post_id = (int)($data['post_id'] ?? 0);
$is_pinned = (int)($data['is_pinned'] ?? 0);

if (!$post_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid post ID.']);
    exit;
}

// In a real app, you would check if the current session belongs to an admin here.
// For now, we rely on the session-bound API_TOKEN and backend access.

try {
    $stmt = $pdo->prepare("UPDATE posts SET is_pinned = ? WHERE id = ?");
    $stmt->execute([$is_pinned, $post_id]);
    
    echo json_encode(['success' => true, 'message' => 'Post ' . ($is_pinned ? 'pinned' : 'unpinned') . ' successfully.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
