<?php
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    $stmt = $pdo->prepare("SELECT streak_count, last_commute_date FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'User not found.']);
        exit;
    }

    $currentStreak = (int)$user['streak_count'];
    $lastDate = $user['last_commute_date'];
    $newStreak = $currentStreak;

    if ($lastDate === $today) {
        // Already commuted today, no change to streak
    } elseif ($lastDate === $yesterday) {
        // Commuted yesterday, increment streak
        $newStreak++;
    } else {
        // Missed a day or first time, reset streak to 1
        $newStreak = 1;
    }

    $updateStmt = $pdo->prepare("UPDATE users SET streak_count = ?, last_commute_date = ? WHERE id = ?");
    $updateStmt->execute([$newStreak, $today, $userId]);

    echo json_encode(['success' => true, 'streak' => $newStreak, 'message' => 'Streak updated!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Streak update failed.']);
}
