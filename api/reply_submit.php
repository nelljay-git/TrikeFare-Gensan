<?php
require_once 'db.php';
validateAuth();
checkRateLimit('reply_submit', 10, 60); // 10 replies per minute

// Rate Limiting: 1 reply per 10 seconds per IP
$ip = $_SERVER['REMOTE_ADDR'];
$stmt = $pdo->prepare("SELECT created_at FROM replies WHERE ip_address = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$ip]);
$last_reply = $stmt->fetch();

if ($last_reply) {
    $last_time = strtotime($last_reply['created_at']);
    if (time() - $last_time < 10) {
        http_response_code(429);
        echo json_encode(['error' => 'Please wait 10 seconds between replies.']);
        exit;
    }
}

// Get JSON Input
$data = json_decode(file_get_contents('php://input'), true);

$comment_id = (int)($data['comment_id'] ?? 0);
$parent_reply_id = isset($data['parent_reply_id']) ? (int)$data['parent_reply_id'] : null;
$username = trim($data['username'] ?? '');
$content = trim($data['content'] ?? '');

// Validation
if (!$comment_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid comment ID.']);
    exit;
}

if (empty($username)) {
    http_response_code(400);
    echo json_encode(['error' => 'Username is required.']);
    exit;
}

if (empty($content) || strlen($content) > 150) {
    http_response_code(400);
    echo json_encode(['error' => 'Reply must be between 1 and 150 characters.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO replies (comment_id, parent_reply_id, username, content, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$comment_id, $parent_reply_id, $username, $content, $ip]);
    
    echo json_encode(['success' => true, 'message' => 'Reply added!']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
