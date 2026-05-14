<?php
// redeem/api/list.php — GET: list all codes (admin only)
header('Content-Type: application/json');
require_once __DIR__ . '/admin_check.php';
require_admin();

$search  = trim($_GET['search'] ?? '');
$filter  = $_GET['filter'] ?? 'all'; // all | active | deactivated | redeemed | unredeemed
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

try {
    $where  = [];
    $params = [];

    if (!empty($search)) {
        $where[]  = "(c.code LIKE ? OR u.username LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    switch ($filter) {
        case 'active':       $where[] = "c.status = 'active'";       break;
        case 'deactivated':  $where[] = "c.status = 'deactivated'";  break;
        case 'redeemed':     $where[] = "c.is_redeemed = 1";         break;
        case 'unredeemed':   $where[] = "c.is_redeemed = 0";         break;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Total count
    $countStmt   = $pdo->prepare(
        "SELECT COUNT(*) FROM codes c LEFT JOIN users u ON c.redeemed_by = u.id $whereSql"
    );
    $countIndex = 1;
    foreach ($params as $param) {
        $countStmt->bindValue($countIndex++, $param, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    // Stats
    $stats = $pdo->query(
        "SELECT
            COUNT(*) AS total,
            SUM(status = 'active') AS active_count,
            SUM(status = 'deactivated') AS deactivated_count,
            SUM(is_redeemed = 1) AS redeemed_count,
            SUM(is_redeemed = 0) AS unredeemed_count
         FROM codes"
    )->fetch();

    // Paginated rows
    $rowStmt     = $pdo->prepare(
        "SELECT c.id, c.code, c.status, c.is_redeemed,
                u.username AS redeemed_by_name,
                c.redeemed_at, c.created_at
         FROM codes c
         LEFT JOIN users u ON c.redeemed_by = u.id
         $whereSql
         ORDER BY c.created_at DESC
         LIMIT ? OFFSET ?"
    );
    
    $paramIndex = 1;
    foreach ($params as $param) {
        $rowStmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
    }
    $rowStmt->bindValue($paramIndex++, $perPage, PDO::PARAM_INT);
    $rowStmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
    
    $rowStmt->execute();
    $codes = $rowStmt->fetchAll();

    echo json_encode([
        'success'   => true,
        'codes'     => $codes,
        'stats'     => $stats,
        'total'     => $total,
        'page'      => $page,
        'per_page'  => $perPage,
        'pages'     => (int)ceil($total / $perPage)
    ]);

} catch (PDOException $e) {
    error_log("List codes error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
?>
