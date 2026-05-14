<?php
header('Content-Type: application/json');
require_once '../db.php';

$data = json_decode(file_get_contents('php://input'), true);

$identifier = trim($data['identifier'] ?? ''); // can be email or username
$password = $data['password'] ?? '';

if (empty($identifier) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        $rememberMe = $data['rememberMe'] ?? false;
        if ($rememberMe) {
            // Set session cookie to last for 30 days
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                session_id(), 
                time() + 2592000, 
                $params["path"], 
                $params["domain"], 
                $params["secure"], 
                $params["httponly"]
            );
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Logged in successfully!', 
            'username' => $user['username']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid credentials.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
