<?php
require_once 'db.php';
validateAuth();
checkRateLimit('community_submit', 5, 60); // 5 posts per minute

// Rate Limiting: 1 post per 30 seconds per IP
$ip = $_SERVER['REMOTE_ADDR'];
$stmt = $pdo->prepare("SELECT created_at FROM posts WHERE ip_address = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$ip]);
$last_post = $stmt->fetch();

if ($last_post) {
    $last_post_time = strtotime($last_post['created_at']);
    if (time() - $last_post_time < 30) {
        http_response_code(429);
        echo json_encode(['error' => 'Please wait 30 seconds between posts.']);
        exit;
    }
}

// Get JSON Input
$data = json_decode(file_get_contents('php://input'), true);

$type = $data['type'] ?? '';
$title = trim($data['title'] ?? '');
$content = trim($data['content'] ?? '');
$location = trim($data['location'] ?? '');

// Validation
$allowed_types = ['traffic', 'fare', 'tip', 'feedback'];
if (!in_array($type, $allowed_types)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid post type.']);
    exit;
}

if (empty($content) || strlen($content) > 1000) {
    http_response_code(400);
    echo json_encode(['error' => 'Content is required and must be under 1000 characters.']);
    exit;
}

if (empty($location)) {
    http_response_code(400);
    echo json_encode(['error' => 'Location is required.']);
    exit;
}

// Title is optional but limited
if (strlen($title) > 100) {
    $title = substr($title, 0, 100);
}

try {
    $stmt = $pdo->prepare("INSERT INTO posts (type, title, content, location, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$type, $title, $content, $location, $ip]);

    echo json_encode(['success' => true, 'message' => 'Post submitted successfully!']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>