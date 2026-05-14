<?php
// redeem/api/redeem.php — POST: redeem a code (user must be logged in)
header('Content-Type: application/json');
require_once __DIR__ . '/../../api/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'status' => 'unauthenticated', 'error' => 'You must be logged in to redeem a code.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$code = trim($data['code'] ?? '');

if (empty($code)) {
    echo json_encode(['success' => false, 'status' => 'invalid', 'error' => 'Please enter a code.']);
    exit;
}

// Sanitize: alphanumeric + dash/underscore only
if (!preg_match('/^[A-Za-z0-9\-_]{3,64}$/', $code)) {
    echo json_encode(['success' => false, 'status' => 'invalid', 'error' => 'Invalid code format.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, status, is_redeemed, redeemed_by FROM codes WHERE code = ?");
    $stmt->execute([$code]);
    $row = $stmt->fetch();

    if (!$row) {
        echo json_encode(['success' => false, 'status' => 'invalid', 'message' => 'Code not found. Please check and try again.']);
        exit;
    }

    if ($row['status'] === 'deactivated') {
        echo json_encode(['success' => false, 'status' => 'inactive', 'message' => 'This code has been disabled and cannot be redeemed.']);
        exit;
    }

    if ($row['is_redeemed']) {
        echo json_encode(['success' => false, 'status' => 'already_redeemed', 'message' => 'This code has already been redeemed.']);
        exit;
    }

    // Mark as redeemed
    $userId   = $_SESSION['user_id'];
    $username = $_SESSION['username'] ?? '';

    $upd = $pdo->prepare(
        "UPDATE codes SET is_redeemed = 1, redeemed_by = ?, redeemed_at = NOW() WHERE id = ? AND is_redeemed = 0"
    );
    $upd->execute([$userId, $row['id']]);

    if ($upd->rowCount() === 0) {
        // Race condition — someone else just redeemed it
        echo json_encode(['success' => false, 'status' => 'already_redeemed', 'message' => 'This code has already been redeemed.']);
        exit;
    }

    echo json_encode([
        'success'  => true,
        'status'   => 'success',
        'message'  => 'Code redeemed successfully! 🎉',
        'code'     => $code,
        'username' => $username
    ]);

} catch (PDOException $e) {
    error_log("Redeem error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'status' => 'error', 'error' => 'Server error. Please try again.']);
}
?>
