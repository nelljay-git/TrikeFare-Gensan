<?php
require_once 'db.php';
// middleware.php usually handles session_start and provides security context

try {
    // Safety check: ensure table exists (in case migration wasn't run)
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $username = '';
    $found = false;
    $attempts = 0;
    
    while (!$found && $attempts < 15) {
        $id = mt_rand(1000, 9999);
        $candidate = "user" . $id;
        
        // Check uniqueness across all potential sources
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$candidate]);
        if ($stmt->fetchColumn() == 0) {
            // Try to insert
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username) VALUES (?)");
                $stmt->execute([$candidate]);
                $username = $candidate;
                $found = true;
            } catch (PDOException $e) {
                // Collision happened between check and insert, loop will retry
                if ($e->getCode() != 23000) throw $e; 
            }
        }
        $attempts++;
    }
    
    if ($found) {
        echo json_encode(['success' => true, 'username' => $username]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Could not generate unique username after multiple attempts']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
