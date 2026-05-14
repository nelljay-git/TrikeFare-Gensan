<?php
// redeem/api/toggle.php — POST: activate/deactivate a code (admin only)
header('Content-Type: application/json');
require_once __DIR__ . '/admin_check.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$data   = json_decode(file_get_contents('php://input'), true);
$id     = (int)($data['id'] ?? 0);
$status = $data['status'] ?? '';

if (!$id || !in_array($status, ['active', 'deactivated'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE codes SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'Code not found.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => "Code " . ($status === 'active' ? 'activated' : 'deactivated') . " successfully."]);
} catch (PDOException $e) {
    error_log("Toggle code error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
?>
