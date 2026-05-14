<?php
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $rideUuid = $data['ride_uuid'] ?? null;

    if ($rideUuid) {
        // Delete specific entry by its unique UUID
        $stmt = $pdo->prepare("DELETE FROM user_rides WHERE user_id = ? AND ride_uuid = ?");
        $stmt->execute([$userId, $rideUuid]);
        $message = 'Ride entry deleted from cloud.';
    } elseif (isset($data['clear_all']) && $data['clear_all'] === true) {
        // Explicitly clear all history
        $stmt = $pdo->prepare("DELETE FROM user_rides WHERE user_id = ?");
        $stmt->execute([$userId]);
        $message = 'Ride history cleared from cloud.';
    } else {
        $message = 'No deletion action performed.';
    }

    echo json_encode(['success' => true, 'message' => $message]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Deletion failed: ' . $e->getMessage()]);
}
