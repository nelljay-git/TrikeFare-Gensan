<?php
require_once 'api/db.php';
header('Content-Type: text/html; charset=UTF-8');

try {
    // Fetch users who successfully redeemed codes, ordered by redemption date descending
    $stmt = $pdo->prepare("
        SELECT u.username, c.redeemed_at 
        FROM codes c 
        JOIN users u ON c.redeemed_by = u.id 
        WHERE c.is_redeemed = 1 
        ORDER BY c.redeemed_at DESC 
        LIMIT 100
    ");
    $stmt->execute();
    $leaders = $stmt->fetchAll();
} catch (PDOException $e) {
    $leaders = [];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboards — TrikeFare</title>
    <meta name="description" content="See who successfully redeemed TrikeFare codes!">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        
        :root {
            --bg: #0a0e1a;
            --surface: #131829;
            --card: #1a2035;
            --border: #2a3050;
            --primary: #00d4aa;
            --primary-glow: #00d4aa33;
            --accent: #6c5ce7;
            --text: #e8ecf4;
            --text-dim: #8892a8;
            --radius: 24px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            overflow-x: hidden;
        }

        /* Subtle animated background */
        body::before {
            content: '';
            position: fixed; inset: 0; z-index: 0;
            background: 
                radial-gradient(ellipse 60% 60% at 20% -10%, rgba(108, 92, 231, 0.1) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 80% 110%, rgba(0, 212, 170, 0.08) 0%, transparent 60%);
            pointer-events: none;
        }

        .container {
            width: 100%;
            max-width: 640px;
            margin-top: 30px;
            position: relative;
            z-index: 1;
        }

        .header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 32px;
        }

        .back-btn {
            background: var(--card);
            border: 1px solid var(--border);
            color: var(--text);
            width: 44px; height: 44px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 1.1rem;
        }

        .back-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateX(-3px);
            box-shadow: 0 4px 15px var(--primary-glow);
        }

        .title {
            font-size: 1.8rem;
            font-weight: 900;
            background: linear-gradient(135deg, #fff, var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        }

        .list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
        }

        .item:hover {
            transform: translateY(-2px);
            border-color: rgba(0, 212, 170, 0.4);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }

        /* Top 3 Highlighting */
        .item.rank-1 { background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), var(--card)); border-color: rgba(255, 215, 0, 0.3); }
        .item.rank-2 { background: linear-gradient(135deg, rgba(192, 192, 192, 0.08), var(--card)); border-color: rgba(192, 192, 192, 0.2); }
        .item.rank-3 { background: linear-gradient(135deg, rgba(205, 127, 50, 0.08), var(--card)); border-color: rgba(205, 127, 50, 0.2); }

        .user-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .avatar {
            width: 46px; height: 46px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 1.2rem;
            color: #fff;
            box-shadow: 0 6px 16px var(--primary-glow);
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            flex-shrink: 0;
        }

        .username {
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 3px;
        }

        .date {
            font-size: 0.82rem;
            color: var(--text-dim);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .medal-icon { font-size: 1.8rem; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3)); }
        .medal-1 { color: #ffd700; }
        .medal-2 { color: #c0c0c0; }
        .medal-3 { color: #cd7f32; }

        .empty {
            text-align: center;
            color: var(--text-dim);
            padding: 50px 20px;
        }

        @media (max-width: 480px) {
            .card { padding: 20px; }
            .item { padding: 14px 16px; }
            .avatar { width: 40px; height: 40px; font-size: 1rem; }
            .username { font-size: 1rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="index.php" class="back-btn" title="Back to Home"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="title"><i class="fa-solid fa-trophy" style="color: #ffd700;"></i> Redemption Wall</h1>
        </div>

        <div class="card">
            <?php if (empty($leaders)): ?>
                <div class="empty">
                    <i class="fa-solid fa-ticket-simple" style="font-size:3.5rem; margin-bottom:20px; opacity:0.3;"></i>
                    <h3 style="color:#fff; margin-bottom:8px;">No Redemptions Yet</h3>
                    <p>Be the first one to redeem a code and claim your spot on the wall!</p>
                </div>
            <?php else: ?>
                <div class="list">
                    <?php foreach ($leaders as $index => $row): ?>
                        <?php 
                            $rankClass = ($index < 3) ? 'rank-' . ($index + 1) : ''; 
                        ?>
                        <div class="item <?= $rankClass ?>">
                            <div class="user-info">
                                <div class="avatar"><?= strtoupper(substr($row['username'], 0, 1)) ?></div>
                                <div>
                                    <div class="username"><?= htmlspecialchars($row['username']) ?></div>
                                    <div class="date">
                                        <i class="fa-regular fa-clock"></i> 
                                        <?= date('M j, Y • g:i A', strtotime($row['redeemed_at'])) ?>
                                    </div>
                                </div>
                            </div>
                            <?php if ($index === 0): ?>
                                <i class="fa-solid fa-medal medal-icon medal-1" title="1st Place"></i>
                            <?php elseif ($index === 1): ?>
                                <i class="fa-solid fa-medal medal-icon medal-2" title="2nd Place"></i>
                            <?php elseif ($index === 2): ?>
                                <i class="fa-solid fa-medal medal-icon medal-3" title="3rd Place"></i>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
