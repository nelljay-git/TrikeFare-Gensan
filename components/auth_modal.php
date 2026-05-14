<!-- AUTH MODAL COMPONENT -->
<div class="modal-overlay" id="authModalOverlay" onclick="if(event.target===this) closeAuthModal()">
    <div class="fare-modal" style="max-width: 400px; background: var(--card, var(--community-card, #1a1a2e));">
        <div class="modal-header">
            <div>
                <div class="modal-title" id="authModalTitle">Welcome Back</div>
                <div class="modal-subtext" id="authModalSubtext">Login to sync your rides and earn streaks</div>
            </div>
            <button class="btn-close" onclick="closeAuthModal()" style="background:none; border:none; color:var(--text, var(--community-text)); font-size:1.5rem; cursor:pointer;">×</button>
        </div>
        <div class="modal-content" style="padding-top: 10px;">
            <!-- Login View -->
            <div id="loginView">
                <div style="margin-bottom: 16px;">
                    <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--text-dim, var(--community-text-dim)); margin-bottom:6px; text-transform:uppercase;">Email or Username</label>
                    <input type="text" id="loginIdentifier" class="surcharge-input" style="width:100%; box-sizing:border-box; height:45px; font-size:1rem; padding: 10px; border-radius:8px; border:1px solid var(--border, var(--community-border)); background:var(--bg, var(--community-bg)); color:var(--text, var(--community-text));" placeholder="Enter email or username">
                </div>
                <div style="margin-bottom: 24px;">
                    <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--text-dim, var(--community-text-dim)); margin-bottom:6px; text-transform:uppercase;">Password</label>
                    <input type="password" id="loginPassword" class="surcharge-input" style="width:100%; box-sizing:border-box; height:45px; font-size:1rem; padding: 10px; border-radius:8px; border:1px solid var(--border, var(--community-border)); background:var(--bg, var(--community-bg)); color:var(--text, var(--community-text));" placeholder="••••••••">
                </div>
                <div style="margin-bottom: 24px; display: flex; align-items: center; gap: 8px; cursor: pointer;" onclick="document.getElementById('loginRememberMe').click()">
                    <input type="checkbox" id="loginRememberMe" style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary, var(--community-accent));">
                    <label for="loginRememberMe" style="font-size: 0.9rem; color: var(--text, var(--community-text)); cursor: pointer;">Stay logged in</label>
                </div>
                <button class="btn-start" onclick="handleLogin()" style="width:100%; height:50px; border-radius:12px; margin-bottom:16px; background:var(--primary, var(--community-accent)); color:white; border:none; font-weight:700; cursor:pointer;">
                    <i class="fa-solid fa-right-to-bracket"></i> Login
                </button>
                <div style="text-align:center; font-size:0.85rem; color:var(--text-dim, var(--community-text-dim));">
                    Don't have an account? <span onclick="toggleAuthView('signup')" style="color:var(--accent, #6c5ce7); font-weight:700; cursor:pointer;">Sign Up</span>
                </div>
            </div>

            <!-- Signup View -->
            <div id="signupView" style="display:none;">
                <div style="margin-bottom: 16px;">
                    <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--text-dim, var(--community-text-dim)); margin-bottom:6px; text-transform:uppercase;">Username</label>
                    <input type="text" id="signupUsername" class="surcharge-input" style="width:100%; box-sizing:border-box; height:45px; font-size:1rem; padding: 10px; border-radius:8px; border:1px solid var(--border, var(--community-border)); background:var(--bg, var(--community-bg)); color:var(--text, var(--community-text));" placeholder="e.g. JuanDelaCruz">
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--text-dim, var(--community-text-dim)); margin-bottom:6px; text-transform:uppercase;">Email Address</label>
                    <input type="email" id="signupEmail" class="surcharge-input" style="width:100%; box-sizing:border-box; height:45px; font-size:1rem; padding: 10px; border-radius:8px; border:1px solid var(--border, var(--community-border)); background:var(--bg, var(--community-bg)); color:var(--text, var(--community-text));" placeholder="name@example.com">
                </div>
                <div style="margin-bottom: 24px;">
                    <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--text-dim, var(--community-text-dim)); margin-bottom:6px; text-transform:uppercase;">Password</label>
                    <input type="password" id="signupPassword" class="surcharge-input" style="width:100%; box-sizing:border-box; height:45px; font-size:1rem; padding: 10px; border-radius:8px; border:1px solid var(--border, var(--community-border)); background:var(--bg, var(--community-bg)); color:var(--text, var(--community-text));" placeholder="At least 6 characters">
                </div>
                <button class="btn-start" onclick="handleSignup()" style="width:100%; height:50px; border-radius:12px; margin-bottom:16px; background:var(--primary, var(--community-accent)); color:white; border:none; font-weight:700; cursor:pointer;">
                    <i class="fa-solid fa-user-plus"></i> Create Account
                </button>
                <div style="text-align:center; font-size:0.85rem; color:var(--text-dim, var(--community-text-dim));">
                    Already have an account? <span onclick="toggleAuthView('login')" style="color:var(--accent, #6c5ce7); font-weight:700; cursor:pointer;">Login</span>
                </div>
            </div>
        </div>
    </div>
</div>
