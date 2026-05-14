<?php
require_once 'db.php';
validateAuth();
checkRateLimit('comment_submit', 10, 60); // 10 comments per minute

// Rate Limiting: 1 comment per 10 seconds per IP
$ip = $_SERVER['REMOTE_ADDR'];
$stmt = $pdo->prepare("SELECT created_at FROM comments WHERE ip_address = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$ip]);
$last_comment = $stmt->fetch();

if ($last_comment) {
    $last_time = strtotime($last_comment['created_at']);
    if (time() - $last_time < 10) {
        http_response_code(429);
        echo json_encode(['error' => 'Please wait 10 seconds between comments.']);
        exit;
    }
}

// Get JSON Input
$data = json_decode(file_get_contents('php://input'), true);

$post_id = (int)($data['post_id'] ?? 0);
$username = trim($data['username'] ?? '');
$content = trim($data['content'] ?? '');

// Validation
if (!$post_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid post ID.']);
    exit;
}

if (empty($username)) {
    http_response_code(400);
    echo json_encode(['error' => 'Username is required.']);
    exit;
}

if (empty($content) || strlen($content) > 150) {
    http_response_code(400);
    echo json_encode(['error' => 'Comment must be between 1 and 150 characters.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, username, content, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$post_id, $username, $content, $ip]);
    
    echo json_encode(['success' => true, 'message' => 'Comment added!']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
