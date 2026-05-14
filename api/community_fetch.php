<?php
require_once 'db.php';
validateAuth();
checkRateLimit('community_fetch', 100, 60); // 100 per minute for fetching

$type_filter = $_GET['type'] ?? 'all';
$sort = $_GET['sort'] ?? 'likes';
$user_identifier = $_SERVER['REMOTE_ADDR'];

$sql = "SELECT p.id, p.type, p.is_pinned, p.title, p.content, p.location, p.likes, p.dislikes, p.created_at,
        (SELECT r.reaction_type FROM reactions r WHERE r.target_type = 'post' AND r.target_id = p.id AND r.user_identifier = ?) as user_reaction
        FROM posts p";
$params = [$user_identifier];

if ($type_filter !== 'all') {
    $sql .= " WHERE p.type = ?";
    $params[] = $type_filter;
}

if ($sort === 'latest') {
    $sql .= " ORDER BY p.is_pinned DESC, p.created_at DESC";
} else {
    // Ranking algorithm: pinned first, then likes - dislikes
    $sql .= " ORDER BY p.is_pinned DESC, (p.likes - p.dislikes) DESC, p.created_at DESC";
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
    
    echo json_encode($posts);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
