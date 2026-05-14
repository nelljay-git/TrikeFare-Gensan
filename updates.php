<?php
session_start();
if (empty($_SESSION['app_token'])) {
    $_SESSION['app_token'] = bin2hex(random_bytes(32));
}
$appToken = $_SESSION['app_token'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <link rel="manifest" href="manifest.json" crossorigin="use-credentials">
    <link rel="icon" type="image/png" sizes="192x192" href="icon-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="icon-512x512.png">
    <meta name="og:image" content="icon-512x512.png">
    <link rel="apple-touch-icon" href="icon-192x192.png">
    <title>What's New - TrikeFare Gensan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f8fafc;
            --surface: #ffffff;
            --card: #ffffff;
            --border: #e2e8f0;
            --primary: #00b894;
            --accent: #6c5ce7;
            --text: #1e293b;
            --text-dim: #64748b;
            --text-muted: #94a3b8;
            --radius: 16px;
        }

        [data-theme="dark"] {
            --bg: #0a0e1a;
            --surface: #131829;
            --card: #1a2035;
            --border: #2a3050;
            --primary: #00d4aa;
            --text: #e8ecf4;
            --text-dim: #8892a8;
            --text-muted: #5a6478;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
            padding-bottom: 40px;
            transition: background-color 0.3s;
        }

        .header {
            position: sticky;
            top: 0;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 100;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .back-btn {
            color: var(--text);
            text-decoration: none;
            font-size: 1.2rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.2s;
        }

        .back-btn:hover {
            background: var(--border);
        }

        .header-title {
            font-weight: 800;
            font-size: 1.1rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .welcome-section {
            margin-bottom: 30px;
            text-align: center;
        }

        .welcome-section h1 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .welcome-section p {
            color: var(--text-dim);
            font-size: 0.95rem;
        }

        /* TIMELINE STYLES */
        .timeline {
            position: relative;
            padding-left: 20px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 10px;
            bottom: 10px;
            width: 2px;
            background: var(--border);
        }

        .update-entry {
            position: relative;
            margin-bottom: 40px;
        }

        .update-entry::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 10px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--primary);
            border: 2px solid var(--bg);
            box-shadow: 0 0 0 4px var(--border);
            z-index: 1;
        }

        .update-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            overflow: hidden;
        }

        .update-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        .update-entry {
            position: relative;
            margin-bottom: 40px;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInSlide 0.5s forwards;
        }

        @keyframes fadeInSlide {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .update-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .update-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text);
            flex: 1;
            min-width: 200px;
        }

        .update-date {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .tag {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .tag-feature {
            background: rgba(0, 184, 148, 0.15);
            color: #00b894;
        }

        .tag-bug {
            background: rgba(255, 107, 107, 0.15);
            color: #ff6b6b;
        }

        .tag-ui {
            background: rgba(108, 92, 231, 0.15);
            color: #6c5ce7;
        }

        .tag-improvement {
            background: rgba(240, 165, 0, 0.15);
            color: #f0a500;
        }

        .update-description {
            font-size: 0.95rem;
            color: var(--text-dim);
            white-space: pre-line;
        }

        .skeleton {
            background: var(--border);
            border-radius: 4px;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 0.6;
            }

            50% {
                opacity: 0.3;
            }

            100% {
                opacity: 0.6;
            }
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* DARK MODE TOGGLE */
        .theme-toggle {
            background: none;
            border: none;
            color: var(--text);
            font-size: 1.2rem;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .theme-toggle:hover {
            background: var(--border);
        }
    </style>
</head>

<body>
    <header class="header">
        <a href="index.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i></a>
        <div class="header-title">TrikeFare Updates</div>
        <button class="theme-toggle" onclick="toggleTheme()" id="themeBtn">
            <i class="fa-solid fa-moon"></i>
        </button>
    </header>

    <div class="container">
        <section class="welcome-section">
            <h1>What's New</h1>
            <p>Track the latest improvements and features in TrikeFare Gensan.</p>
        </section>

        <div class="timeline" id="updatesContainer">
            <!-- SKELETON LOADER -->
            <div class="update-entry">
                <div class="update-card">
                    <div style="height: 20px; width: 60%; margin-bottom: 10px;" class="skeleton"></div>
                    <div style="height: 14px; width: 30%; margin-bottom: 15px;" class="skeleton"></div>
                    <div style="height: 60px; width: 100%;" class="skeleton"></div>
                </div>
            </div>
            <div class="update-entry">
                <div class="update-card">
                    <div style="height: 20px; width: 50%; margin-bottom: 10px;" class="skeleton"></div>
                    <div style="height: 14px; width: 25%; margin-bottom: 15px;" class="skeleton"></div>
                    <div style="height: 40px; width: 100%;" class="skeleton"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const APP_SESSION_TOKEN = '<?php echo $appToken; ?>';

        // THEME LOGIC
        let currentTheme = localStorage.getItem('trikefareTheme') || 'light';
        if (currentTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.getElementById('themeBtn').innerHTML = '<i class="fa-solid fa-sun"></i>';
        }

        function toggleTheme() {
            if (document.documentElement.getAttribute('data-theme') === 'dark') {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('trikefareTheme', 'light');
                document.getElementById('themeBtn').innerHTML = '<i class="fa-solid fa-moon"></i>';
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('trikefareTheme', 'dark');
                document.getElementById('themeBtn').innerHTML = '<i class="fa-solid fa-sun"></i>';
            }
        }

        // FETCH UPDATES
        async function loadUpdates() {
            const container = document.getElementById('updatesContainer');
            try {
                const response = await fetch('api/fetch_updates.php', {
                    headers: {
                        'X-Session-Token': APP_SESSION_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!response.ok) throw new Error('Failed to fetch');
                const updates = await response.json();

                if (updates.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fa-solid fa-rocket"></i>
                            <p>No updates yet. Stay tuned!</p>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = '';
                updates.forEach(update => {
                    const entry = document.createElement('div');
                    entry.className = 'update-entry';

                    const tagClass = getTagClass(update.update_type);
                    const formattedDate = formatDate(update.release_date);

                    entry.innerHTML = `
                        <div class="update-card">
                            <span class="tag ${tagClass}">${update.update_type}</span>
                            <div class="update-header">
                                <h3 class="update-title">${update.title}</h3>
                                <span class="update-date">${formattedDate}</span>
                            </div>
                            <div class="update-description">${update.description}</div>
                        </div>
                    `;
                    container.appendChild(entry);
                });

            } catch (err) {
                console.error(err);
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <p>Oops! Failed to load updates. Please try again later.</p>
                    </div>
                `;
            }
        }

        function getTagClass(type) {
            const t = type.toLowerCase();
            if (t.includes('feature')) return 'tag-feature';
            if (t.includes('bug')) return 'tag-bug';
            if (t.includes('ui')) return 'tag-ui';
            return 'tag-improvement';
        }

        function formatDate(dateStr) {
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            return new Date(dateStr).toLocaleDateString(undefined, options);
        }

        loadUpdates();
    </script>
</body>

</html>