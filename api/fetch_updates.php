<?php
require_once 'middleware.php';

// Extra safeguard: Only allow AJAX requests
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !isset($normalizedHeaders['x-requested-with'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Direct access forbidden']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT id, title, release_date, update_type, description FROM app_updates ORDER BY release_date DESC, id DESC");
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($updates);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch updates.']);
}
?>
