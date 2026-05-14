<?php
require_once __DIR__ . '/../api/db.php';
header('Content-Type: text/html; charset=UTF-8'); // Override db.php's application/json
require_once __DIR__ . '/api/admin_check.php';

// Redirect non-admins
if (!isset($_SESSION['user_id']) || !is_admin()) {
    header('Location: index.php');
    exit;
}
$username = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Code Management</title>
    <meta name="description" content="TrikeFare admin panel for code management.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin_codes.css">
</head>
<body>
<div class="wrap">

    <!-- NAV -->
    <nav class="top-nav">
        <div>
            <a href="../index.php" class="brand">⚡ TrikeFare</a>
            <span class="brand-sub">Admin Panel</span>
        </div>
        <div class="nav-right">
            <span style="font-size:0.82rem;color:var(--text-dim)">
                <i class="fa-solid fa-shield-halved" style="color:var(--primary)"></i>
                <?= htmlspecialchars($username) ?>
            </span>
            <a href="index.php" class="nav-link"><i class="fa-solid fa-ticket"></i> Redeem Page</a>
        </div>
    </nav>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Codes</div>
            <div class="stat-value" id="statTotal">0</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Active</div>
            <div class="stat-value green" id="statActive">0</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Redeemed</div>
            <div class="stat-value purple" id="statRedeemed">0</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Deactivated</div>
            <div class="stat-value red" id="statDeactivated">0</div>
        </div>
    </div>

    <!-- CREATE CODES -->
    <div class="section-card">
        <div class="section-title"><i class="fa-solid fa-plus-circle"></i> Create Codes</div>
        <div class="create-row">
            <div class="form-group" style="max-width:160px">
                <label class="form-label">Mode</label>
                <select class="form-select" id="createMode" onchange="onModeChange()">
                    <option value="auto">Auto Generate</option>
                    <option value="manual">Manual Input</option>
                </select>
            </div>
            <div class="form-group" id="autoGroup">
                <label class="form-label">Count (1–50)</label>
                <input type="number" class="form-input" id="autoCount" min="1" max="50" value="5" style="max-width:100px">
            </div>
            <div class="form-group" id="manualGroup" style="display:none">
                <label class="form-label">Code</label>
                <input type="text" class="form-input" id="manualCode" placeholder="e.g. PROMO-2026" maxlength="64" style="text-transform:uppercase">
            </div>
            <button class="btn-create" onclick="createCodes()">
                <i class="fa-solid fa-bolt"></i> Generate
            </button>
        </div>
        <div class="generated-codes" id="generatedCodes">
            <div class="gen-title">✨ Generated Codes (click to copy)</div>
            <div class="gen-list" id="genList"></div>
        </div>
    </div>

    <!-- CODES TABLE -->
    <div class="section-card">
        <div class="section-title"><i class="fa-solid fa-table-list"></i> All Codes</div>

        <div class="toolbar">
            <input type="text" class="search-input" placeholder="Search codes or users..."
                   oninput="onSearch(this.value)">
            <button class="filter-btn active" data-filter="all" onclick="setFilter('all')">All</button>
            <button class="filter-btn" data-filter="active" onclick="setFilter('active')">Active</button>
            <button class="filter-btn" data-filter="redeemed" onclick="setFilter('redeemed')">Redeemed</button>
            <button class="filter-btn" data-filter="deactivated" onclick="setFilter('deactivated')">Deactivated</button>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Status</th>
                        <th>Redeemed</th>
                        <th>By User</th>
                        <th>Timestamp</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="codesBody">
                    <tr><td colspan="6" class="empty-row">Loading...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="pagination" id="pagination"></div>
    </div>

</div>

<div id="toastContainer"></div>
<script src="admin_codes.js"></script>
</body>
</html>
