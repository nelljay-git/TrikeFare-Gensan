<?php
header('Content-Type: application/json');
require_once '../db.php';

if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT username, email, streak_count FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo json_encode([
                'isLoggedIn' => true,
                'username' => $user['username'],
                'email' => $user['email'],
                'streak' => (int)$user['streak_count']
            ]);
            exit;
        }
    } catch (PDOException $e) {}
}

echo json_encode(['isLoggedIn' => false]);
