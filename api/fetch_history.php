<?php
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    // Limit to 100 recent rides for performance
    $stmt = $pdo->prepare("SELECT * FROM user_rides WHERE user_id = ? ORDER BY ride_timestamp DESC LIMIT 100");
    $stmt->execute([$userId]);
    $rides = $stmt->fetchAll();

    $formattedRides = [];
    foreach ($rides as $r) {
        $dateObj = new DateTime($r['ride_timestamp']);
        
        $duration = (int)$r['duration'];
        $startDateObj = clone $dateObj;
        if ($duration > 0) {
            $startDateObj->modify("-{$duration} seconds");
        }

        // Try to reverse engineer breakdown if needed
        $base = 15;
        $extra = max(0, $r['fare'] - $base);
        
        $formattedRides[] = [
            'id' => (int)$r['id'],
            'distance' => (float)$r['distance_km'],
            'fare' => (float)$r['fare'],
            'fixedFare' => null,
            'duration' => $duration,
            'date' => $dateObj->format('M j, Y'),
            'startTime' => $startDateObj->format('h:i A'),
            'endTime' => $dateObj->format('h:i A'),
            'origin' => $r['origin'] ?: 'Unknown Area',
            'destination' => $r['destination'] ?: 'Unknown Area',
            'routeSummary' => 'Cloud Backup Route',
            'breakdown' => [
                'base' => $base,
                'extra' => $extra,
                'night' => 0,
                'isFixed' => false
            ],
            'paymentStatus' => 'Paid (Cash)',
            'path' => $r['path_json'] ? json_decode($r['path_json'], true) : null,
            'timestamp' => $dateObj->format('Y-m-d\TH:i:s.vP'), // ISO format with offset
            'ride_uuid' => $r['ride_uuid']
        ];
    }

    echo json_encode(['success' => true, 'rides' => $formattedRides]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Fetch failed: ' . $e->getMessage()]);
}
