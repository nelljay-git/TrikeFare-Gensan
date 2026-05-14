<?php
// redeem/api/admin_check.php
// Shared helper — require_admin() checks session + DB role

require_once __DIR__ . '/../../api/db.php';

function require_admin() {
    global $pdo;
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'You must be logged in.']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if (!$user || $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Admin access required.']);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Authorization check failed.']);
        exit;
    }
}

function is_admin() {
    global $pdo;
    if (!isset($_SESSION['user_id'])) return false;
    try {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        return $user && $user['role'] === 'admin';
    } catch (PDOException $e) {
        return false;
    }
}
?>
