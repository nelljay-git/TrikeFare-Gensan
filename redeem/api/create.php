<?php
// redeem/api/create.php — POST: create codes (admin only)
header('Content-Type: application/json');
require_once __DIR__ . '/admin_check.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$data  = json_decode(file_get_contents('php://input'), true);
$mode  = $data['mode'] ?? 'auto';   // 'auto' or 'manual'
$count = max(1, min(50, (int)($data['count'] ?? 1)));
$manualCode = strtoupper(trim($data['code'] ?? ''));

$generated = [];

try {
    if ($mode === 'manual') {
        if (empty($manualCode)) {
            echo json_encode(['success' => false, 'error' => 'Code cannot be empty.']);
            exit;
        }
        if (!preg_match('/^[A-Za-z0-9\-_]{3,64}$/', $manualCode)) {
            echo json_encode(['success' => false, 'error' => 'Code must be 3–64 alphanumeric characters (dashes/underscores allowed).']);
            exit;
        }
        // Check duplicate
        $check = $pdo->prepare("SELECT id FROM codes WHERE code = ?");
        $check->execute([$manualCode]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Code already exists.']);
            exit;
        }
        $ins = $pdo->prepare("INSERT INTO codes (code) VALUES (?)");
        $ins->execute([$manualCode]);
        $generated[] = $manualCode;

    } else {
        // Auto-generate
        $ins = $pdo->prepare("INSERT IGNORE INTO codes (code) VALUES (?)");
        for ($i = 0; $i < $count; $i++) {
            $attempts = 0;
            do {
                $code = strtoupper(substr(bin2hex(random_bytes(5)), 0, 8))
                      . '-'
                      . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
                $check = $pdo->prepare("SELECT id FROM codes WHERE code = ?");
                $check->execute([$code]);
                $exists = $check->fetch();
                $attempts++;
            } while ($exists && $attempts < 10);

            if (!$exists) {
                $ins->execute([$code]);
                $generated[] = $code;
            }
        }
    }

    echo json_encode(['success' => true, 'codes' => $generated, 'count' => count($generated)]);

} catch (PDOException $e) {
    error_log("Create code error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
?>
