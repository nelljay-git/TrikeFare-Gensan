<?php
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$rides = $data['rides'] ?? [];

try {
    $userId = $_SESSION['user_id'];
    $syncedCount = 0;

    // We no longer delete all history. Instead, we use UPSERT based on ride_uuid.
    // This preserves the original ride_timestamp and only updates metadata if needed.
    $stmt = $pdo->prepare("INSERT INTO user_rides 
        (user_id, ride_uuid, origin, destination, fare, distance_km, duration, path_json, ride_timestamp) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            origin = VALUES(origin),
            destination = VALUES(destination),
            fare = VALUES(fare),
            distance_km = VALUES(distance_km),
            duration = VALUES(duration),
            path_json = VALUES(path_json)
            /* ride_timestamp is NOT updated here to ensure it remains immutable */
    ");

    foreach ($rides as $ride) {
        $timestamp = isset($ride['timestamp']) ? date('Y-m-d H:i:s', strtotime($ride['timestamp'])) : null;
        if (!$timestamp) continue;

        // Use ride_uuid if available, otherwise fallback to a stable hash or skip upsert logic
        $rideUuid = $ride['ride_uuid'] ?? null;
        
        // If no UUID (old client), we'll still insert it, but it might duplicate if synced twice 
        // until the client updates their local storage with server IDs.
        $stmt->execute([
            $userId,
            $rideUuid,
            $ride['origin'] ?? 'Unknown Area',
            $ride['destination'] ?? 'Unknown Area',
            $ride['fare'] ?? 0,
            $ride['distance'] ?? 0,
            $ride['duration'] ?? 0,
            isset($ride['path']) ? json_encode($ride['path']) : null,
            $timestamp
        ]);
        $syncedCount++;
    }

    echo json_encode(['success' => true, 'synced_count' => $syncedCount, 'message' => 'History refreshed successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Sync failed: ' . $e->getMessage()]);
}
