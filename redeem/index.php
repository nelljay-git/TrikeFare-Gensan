<?php
require_once __DIR__ . '/../api/db.php';
header('Content-Type: text/html; charset=UTF-8'); // Override db.php's application/json
$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? '';
$initialCode = isset($_GET['redeemcode']) ? trim($_GET['redeemcode']) : '';
$safeInitialCode = htmlspecialchars(strtoupper($initialCode), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redeem Code — TrikeFare</title>
    <meta name="description" content="Redeem your exclusive TrikeFare code. Login required.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg: #0a0e1a;
            --surface: #131829;
            --card: #1a2035;
            --border: #2a3050;
            --primary: #00d4aa;
            --primary-glow: #00d4aa33;
            --accent: #6c5ce7;
            --accent-glow: #6c5ce733;
            --danger: #ff6b6b;
            --danger-glow: #ff6b6b22;
            --warning: #f0a500;
            --warning-glow: #f0a50022;
            --success-glow: #00d4aa22;
            --text: #e8ecf4;
            --text-dim: #8892a8;
            --text-muted: #5a6478;
            --radius: 20px;
            --radius-sm: 12px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            z-index: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 10%, #00d4aa0d 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 80% 80%, #6c5ce70d 0%, transparent 60%);
            pointer-events: none;
        }

        .page-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 480px;
        }

        /* NAV BAR */
        .top-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
        }

        .brand {
            font-size: 1.1rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .nav-user {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.82rem;
            color: var(--text-dim);
        }

        .nav-user .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        .btn-nav-login {
            padding: 7px 16px;
            border-radius: 20px;
            background: var(--card);
            border: 1px solid var(--border);
            color: var(--text);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
        }

        .btn-nav-login:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* HERO */
        .hero {
            text-align: center;
            margin-bottom: 36px;
        }

        .hero-icon {
            width: 72px;
            height: 72px;
            border-radius: 22px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px var(--primary-glow);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-6px);
            }
        }

        .hero h1 {
            font-size: 2rem;
            font-weight: 900;
            letter-spacing: -0.5px;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #fff 40%, var(--text-dim));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 0.95rem;
            color: var(--text-dim);
            line-height: 1.6;
        }

        /* CARD */
        .redeem-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }

        /* AUTH GATE */
        .auth-gate {
            text-align: center;
            padding: 10px 0 4px;
        }

        .auth-gate .lock-icon {
            font-size: 2.5rem;
            margin-bottom: 14px;
            color: var(--text-muted);
        }

        .auth-gate h2 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .auth-gate p {
            font-size: 0.875rem;
            color: var(--text-dim);
            margin-bottom: 22px;
        }

        .btn-login-gate {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 32px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--primary), #00a885);
            color: #000;
            font-weight: 800;
            font-size: 0.95rem;
            cursor: pointer;
            border: none;
            font-family: inherit;
            box-shadow: 0 6px 24px var(--primary-glow);
            transition: all 0.2s;
        }

        .btn-login-gate:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 32px var(--primary-glow);
        }

        .btn-login-gate:active {
            transform: translateY(0);
        }

        /* FORM */
        .redeem-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .field-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-dim);
            margin-bottom: 8px;
            display: block;
        }

        .code-input-wrap {
            position: relative;
        }

        .code-input {
            width: 100%;
            padding: 16px 50px 16px 18px;
            background: var(--card);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text);
            font-size: 1.05rem;
            font-family: 'Inter', monospace;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .code-input::placeholder {
            letter-spacing: 1px;
            font-weight: 400;
            color: var(--text-muted);
            text-transform: none;
        }

        .code-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }

        .clear-btn {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 0.9rem;
            display: none;
            transition: color 0.2s;
        }

        .clear-btn:hover {
            color: var(--text);
        }

        .btn-redeem {
            width: 100%;
            padding: 16px;
            border-radius: var(--radius-sm);
            background: linear-gradient(135deg, var(--primary), #00a885);
            color: #000;
            font-weight: 800;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            font-family: inherit;
            box-shadow: 0 6px 24px var(--primary-glow);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-redeem:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 32px var(--primary-glow);
        }

        .btn-redeem:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-redeem:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-redeem .spinner {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid rgba(0, 0, 0, 0.3);
            border-top-color: #000;
            animation: spin 0.7s linear infinite;
            display: none;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* RESULT BANNER */
        .result-banner {
            border-radius: var(--radius-sm);
            padding: 20px;
            display: none;
            animation: slideUp 0.4s ease;
            border: 1px solid transparent;
        }

        .result-banner.show {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .result-banner .res-icon {
            font-size: 1.6rem;
            flex-shrink: 0;
            line-height: 1;
        }

        .result-banner .res-title {
            font-size: 1rem;
            font-weight: 800;
            margin-bottom: 3px;
        }

        .result-banner .res-msg {
            font-size: 0.85rem;
            opacity: 0.85;
        }

        .result-banner.success {
            background: #00d4aa12;
            border-color: #00d4aa44;
        }

        .result-banner.success .res-title {
            color: var(--primary);
        }

        .result-banner.invalid {
            background: #ff6b6b10;
            border-color: #ff6b6b44;
        }

        .result-banner.invalid .res-title {
            color: var(--danger);
        }

        .result-banner.already_redeemed {
            background: #f0a50010;
            border-color: #f0a50044;
        }

        .result-banner.already_redeemed .res-title {
            color: var(--warning);
        }

        .result-banner.inactive {
            background: #5a647820;
            border-color: #5a647844;
        }

        .result-banner.inactive .res-title {
            color: var(--text-dim);
        }

        /* FOOTER */
        .page-footer {
            text-align: center;
            margin-top: 28px;
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        .page-footer a {
            color: var(--primary);
            text-decoration: none;
        }

        .page-footer a:hover {
            text-decoration: underline;
        }

        /* AUTH MODAL (reusing existing) */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.show {
            display: flex;
        }

        .fare-modal {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            width: 100%;
            max-width: 400px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.6);
            animation: slideUp 0.3s ease;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px 24px 0;
        }

        .modal-title {
            font-size: 1.2rem;
            font-weight: 800;
        }

        .modal-subtext {
            font-size: 0.82rem;
            color: var(--text-dim);
            margin-top: 4px;
        }

        .modal-content {
            padding: 20px 24px 24px;
        }

        .surcharge-input {
            width: 100%;
            padding: 12px 14px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-family: inherit;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .surcharge-input:focus {
            border-color: var(--primary);
        }

        .btn-close {
            background: none;
            border: none;
            color: var(--text);
            font-size: 1.4rem;
            cursor: pointer;
            line-height: 1;
            padding: 4px 8px;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .btn-close:hover {
            background: var(--card);
        }

        .btn-start {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), #00a885);
            color: #000;
            font-weight: 800;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
            width: 100%;
        }

        .btn-start:hover {
            transform: translateY(-1px);
        }

        /* TOAST */
        #toastContainer {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
            pointer-events: none;
        }

        .toast {
            padding: 12px 22px;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #fff;
            pointer-events: auto;
            animation: toastIn 0.3s ease;
            white-space: nowrap;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }

        .toast.success {
            background: #00a885;
        }

        .toast.error {
            background: #e53e3e;
        }

        .toast.info {
            background: var(--accent);
        }

        @keyframes toastIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>

    <div class="page-wrap">

        <!-- TOP NAV -->
        <nav class="top-nav">
            <a href="../index.php" class="brand">⚡ TrikeFare</a>
            <div class="nav-user">
                <?php if ($isLoggedIn): ?>
                    <div class="avatar"><?= strtoupper(substr($username, 0, 1)) ?></div>
                    <span><?= htmlspecialchars($username) ?></span>
                <?php else: ?>
                    <button class="btn-nav-login" onclick="openAuthModal()">
                        <i class="fa-solid fa-right-to-bracket"></i> Login
                    </button>
                <?php endif; ?>
            </div>
        </nav>

        <!-- HERO -->
        <div class="hero">
            <div class="hero-icon">🎟️</div>
            <h1>Redeem Your Code</h1>
            <p>Enter your exclusive code below to unlock rewards.</p>
        </div>

        <!-- MAIN CARD -->
        <div class="redeem-card">

            <?php if (!$isLoggedIn): ?>
                <!-- AUTH GATE (shown server-side if not logged in) -->
                <div class="auth-gate" id="authGate">
                    <div class="lock-icon"><i class="fa-solid fa-lock"></i></div>
                    <h2>Login Required</h2>
                    <?php if (!empty($safeInitialCode)): ?>
                        <p>Login to redeem code <strong><?= $safeInitialCode ?></strong>.<br>Your redemption will be saved to your account.</p>
                    <?php else: ?>
                        <p>You need to be logged in to redeem a code.<br>Your redemption will be saved to your account.</p>
                    <?php endif; ?>
                    <button class="btn-login-gate" onclick="openAuthModal()">
                        <i class="fa-solid fa-right-to-bracket"></i> Login / Register
                    </button>
                </div>
            <?php else: ?>
                <!-- REDEEM FORM -->
                <div class="redeem-form" id="redeemForm">
                    <div>
                        <label class="field-label" for="codeInput">Your Redemption Code</label>
                        <div class="code-input-wrap">
                            <input type="text" id="codeInput" class="code-input" placeholder="e.g. ABCD1234-EFGH"
                                value="<?= $safeInitialCode ?>"
                                maxlength="64" autocomplete="off" spellcheck="false" oninput="onCodeInput(this)"
                                onkeydown="if(event.key==='Enter') submitCode()">
                            <button class="clear-btn" id="clearBtn" onclick="clearCode()" title="Clear" <?= !empty($safeInitialCode) ? 'style="display:block;"' : '' ?>>
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <!-- RESULT BANNER -->
                    <div class="result-banner" id="resultBanner">
                        <div class="res-icon" id="resIcon"></div>
                        <div>
                            <div class="res-title" id="resTitle"></div>
                            <div class="res-msg" id="resMsg"></div>
                        </div>
                    </div>

                    <button class="btn-redeem" id="redeemBtn" onclick="submitCode()">
                        <div class="spinner" id="spinner"></div>
                        <i class="fa-solid fa-gift" id="redeemIcon"></i>
                        <span id="redeemBtnText">Redeem Code</span>
                    </button>
                </div>
            <?php endif; ?>

        </div>

        <!-- FOOTER -->
        <div class="page-footer">
            <?php if ($isLoggedIn): ?>
                <p>Logged in as <strong><?= htmlspecialchars($username) ?></strong> &nbsp;·&nbsp;
                    <a href="../api/auth/logout.php" onclick="handleLogout();return false;">Logout</a>
                </p>
            <?php else: ?>
                <p>Don't have an account? <a href="#" onclick="openAuthModal();return false;">Register free</a></p>
            <?php endif; ?>
        </div>

    </div>

    <!-- AUTH MODAL -->
    <?php include __DIR__ . '/../components/auth_modal.php'; ?>

    <!-- TOAST CONTAINER -->
    <div id="toastContainer"></div>

    <script>
        // ── Toast ──────────────────────────────────────────────────────────────
        function showToast(msg, type = 'info') {
            const c = document.getElementById('toastContainer');
            const t = document.createElement('div');
            t.className = `toast ${type}`;
            t.textContent = msg;
            c.appendChild(t);
            setTimeout(() => t.remove(), 3200);
        }

        // ── Auth helpers (minimal subset) ─────────────────────────────────────
        function openAuthModal() {
            const m = document.getElementById('authModalOverlay');
            if (m) { m.classList.add('show'); toggleAuthView('login'); }
        }
        function closeAuthModal() {
            const m = document.getElementById('authModalOverlay');
            if (m) m.classList.remove('show');
        }
        function toggleAuthView(v) {
            const isLogin = v === 'login';
            const lv = document.getElementById('loginView');
            const sv = document.getElementById('signupView');
            const t = document.getElementById('authModalTitle');
            const s = document.getElementById('authModalSubtext');
            if (lv) lv.style.display = isLogin ? 'block' : 'none';
            if (sv) sv.style.display = isLogin ? 'none' : 'block';
            if (t) t.textContent = isLogin ? 'Welcome Back' : 'Join TrikeFare';
            if (s) s.textContent = isLogin ? 'Login to redeem your code' : 'Create an account to redeem codes';
        }
        async function handleLogin() {
            const id = document.getElementById('loginIdentifier').value;
            const pw = document.getElementById('loginPassword').value;
            const rem = document.getElementById('loginRememberMe')?.checked || false;
            if (!id || !pw) return showToast('Please fill all fields', 'error');
            try {
                const r = await fetch('../api/auth/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ identifier: id, password: pw, rememberMe: rem })
                });
                const d = await r.json();
                if (d.success) { showToast(d.message, 'success'); setTimeout(() => location.reload(), 800); }
                else showToast(d.error, 'error');
            } catch { showToast('Login failed. Try again.', 'error'); }
        }
        async function handleSignup() {
            const u = document.getElementById('signupUsername').value;
            const e = document.getElementById('signupEmail').value;
            const pw = document.getElementById('signupPassword').value;
            if (!u || !e || !pw) return showToast('Please fill all fields', 'error');
            try {
                const r = await fetch('../api/auth/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username: u, email: e, password: pw })
                });
                const d = await r.json();
                if (d.success) { showToast(d.message, 'success'); setTimeout(() => location.reload(), 800); }
                else showToast(d.error, 'error');
            } catch { showToast('Signup failed. Try again.', 'error'); }
        }
        async function handleLogout() {
            await fetch('../api/auth/logout.php');
            location.reload();
        }

        // ── Code Input ─────────────────────────────────────────────────────────
        function onCodeInput(el) {
            el.value = el.value.toUpperCase();
            document.getElementById('clearBtn').style.display = el.value ? 'block' : 'none';
            // Hide result when user starts typing again
            document.getElementById('resultBanner').className = 'result-banner';
        }
        function clearCode() {
            const inp = document.getElementById('codeInput');
            inp.value = '';
            document.getElementById('clearBtn').style.display = 'none';
            document.getElementById('resultBanner').className = 'result-banner';
            inp.focus();
        }

        // ── Submit ─────────────────────────────────────────────────────────────
        async function submitCode() {
            const inp = document.getElementById('codeInput');
            const code = inp.value.trim();
            if (!code) { inp.focus(); showToast('Please enter a code', 'error'); return; }

            setLoading(true);

            try {
                const res = await fetch('api/redeem.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code })
                });
                const data = await res.json();
                showResult(data);
            } catch {
                showResult({ success: false, status: 'error', message: 'Network error. Please try again.' });
            } finally {
                setLoading(false);
            }
        }

        function setLoading(on) {
            const btn = document.getElementById('redeemBtn');
            const spin = document.getElementById('spinner');
            const ico = document.getElementById('redeemIcon');
            const txt = document.getElementById('redeemBtnText');
            btn.disabled = on;
            spin.style.display = on ? 'block' : 'none';
            ico.style.display = on ? 'none' : 'inline';
            txt.textContent = on ? 'Redeeming…' : 'Redeem Code';
        }

        const RESULT_MAP = {
            success: { icon: '✅', title: 'Code Redeemed!', cls: 'success' },
            invalid: { icon: '❌', title: 'Invalid Code', cls: 'invalid' },
            already_redeemed: { icon: '⚠️', title: 'Already Redeemed', cls: 'already_redeemed' },
            inactive: { icon: '🚫', title: 'Code Inactive', cls: 'inactive' },
            unauthenticated: { icon: '🔒', title: 'Login Required', cls: 'invalid' },
            error: { icon: '⚙️', title: 'Something went wrong', cls: 'invalid' },
        };

        function showResult(data) {
            const status = data.status || (data.success ? 'success' : 'error');
            const map = RESULT_MAP[status] || RESULT_MAP.error;
            const banner = document.getElementById('resultBanner');
            document.getElementById('resIcon').textContent = map.icon;
            document.getElementById('resTitle').textContent = map.title;
            document.getElementById('resMsg').textContent = data.message || data.error || '';
            banner.className = `result-banner show ${map.cls}`;
            if (data.success) {
                document.getElementById('codeInput').value = '';
                document.getElementById('clearBtn').style.display = 'none';
            }
        }

    </script>
</body>

</html>