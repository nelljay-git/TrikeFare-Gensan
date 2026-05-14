<?php
// api/admin_updates_api.php
require_once 'middleware.php';

// Check if the user is authenticated as admin
// For simplicity, we'll check a session variable. 
// In a real app, this would be more robust.
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($method) {
    case 'GET':
        fetchUpdates($pdo);
        break;
    case 'POST':
        if ($action === 'delete') {
            deleteUpdate($pdo);
        } elseif ($action === 'edit') {
            editUpdate($pdo);
        } else {
            createUpdate($pdo);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function fetchUpdates($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM app_updates ORDER BY release_date DESC, id DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function createUpdate($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['title']) || empty($data['release_date']) || empty($data['update_type']) || empty($data['description'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO app_updates (title, release_date, update_type, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $data['title'],
            $data['release_date'],
            $data['update_type'],
            $data['description']
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create update: ' . $e->getMessage()]);
    }
}

function editUpdate($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['id']) || empty($data['title']) || empty($data['release_date']) || empty($data['update_type']) || empty($data['description'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    try {
        $stmt = $pdo->prepare("UPDATE app_updates SET title = ?, release_date = ?, update_type = ?, description = ? WHERE id = ?");
        $stmt->execute([
            $data['title'],
            $data['release_date'],
            $data['update_type'],
            $data['description'],
            $data['id']
        ]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update: ' . $e->getMessage()]);
    }
}

function deleteUpdate($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing ID']);
        return;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM app_updates WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete: ' . $e->getMessage()]);
    }
}
