<?php
require_once 'db.php';
validateAuth();
checkRateLimit('community_stats', 30, 60);

try {
    // Get top 5 reported locations in the last 24 hours
    $sql = "SELECT location, COUNT(*) as report_count 
            FROM posts 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY location 
            ORDER BY report_count DESC 
            LIMIT 5";
    
    $stmt = $pdo->query($sql);
    $stats = $stmt->fetchAll();
    
    echo json_encode($stats);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
}
?>
