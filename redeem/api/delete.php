<?php
// redeem/api/delete.php — POST: delete a code (admin only, unredeemed only)
header('Content-Type: application/json');
require_once __DIR__ . '/admin_check.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id   = (int)($data['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Invalid code ID.']);
    exit;
}

try {
    // Only allow deleting unredeemed codes
    $check = $pdo->prepare("SELECT id, is_redeemed FROM codes WHERE id = ?");
    $check->execute([$id]);
    $row = $check->fetch();

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Code not found.']);
        exit;
    }

    if ($row['is_redeemed']) {
        echo json_encode(['success' => false, 'error' => 'Cannot delete a redeemed code. The redemption log must be preserved.']);
        exit;
    }

    $del = $pdo->prepare("DELETE FROM codes WHERE id = ?");
    $del->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Code deleted successfully.']);
} catch (PDOException $e) {
    error_log("Delete code error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
?>
