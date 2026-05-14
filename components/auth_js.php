let currentUser = null;

async function checkAuthStatus() {
// 1. Try to load from localStorage first for instant UI response (especially offline)
const cachedUser = localStorage.getItem('trikefare_user');
if (cachedUser) {
try {
currentUser = JSON.parse(cachedUser);
if (typeof updateAuthUI === 'function') updateAuthUI();
} catch (e) {
console.warn('Failed to parse cached user:', e);
}
}

try {
const res = await fetch('api/auth/status.php');
const data = await res.json();

if (data.isLoggedIn) {
currentUser = data;
// 2. Persist fresh data from server
localStorage.setItem('trikefare_user', JSON.stringify(data));
} else {
currentUser = null;
// 3. Clear cache if server says we're logged out
localStorage.removeItem('trikefare_user');
}

if (typeof updateAuthUI === 'function') updateAuthUI();

if (currentUser) {
// First pull history from server
await fetchHistoryFromServer();
// Then sync any local rides that aren't on the server yet
if (typeof syncHistoryToServer === 'function') {
syncHistoryToServer();
}
}
} catch (e) {
console.warn('Auth status check failed (likely offline):', e);
// If offline, we continue using the currentUser from localStorage if it exists
// No need to clear currentUser here
}
}

async function fetchHistoryFromServer() {
try {
const res = await fetch('api/fetch_history.php');
const data = await res.json();

if (data.success && data.rides) {
// Requirement: Replace existing ride history in localStorage with latest data from database
const cloudRides = data.rides;

// Limit to 100 recent rides
const finalHistory = cloudRides.slice(0, 100);
localStorage.setItem('trikefareHistory', JSON.stringify(finalHistory));

// Re-render history UI if available
if (typeof renderHistory === 'function') renderHistory();

if (cloudRides.length > 0) {
console.log(`Synced ${cloudRides.length} rides from cloud.`);
}
}
} catch (e) {
console.warn('Failed to fetch cloud history:', e);
}
}

function openAuthModal() {
const modal = document.getElementById('authModalOverlay');
if (modal) {
modal.classList.add('show');
toggleAuthView('login');
}
}

function closeAuthModal() {
const modal = document.getElementById('authModalOverlay');
if (modal) modal.classList.remove('show');
}

function toggleAuthView(view) {
const isLogin = view === 'login';
const loginView = document.getElementById('loginView');
const signupView = document.getElementById('signupView');
const title = document.getElementById('authModalTitle');
const subtext = document.getElementById('authModalSubtext');

if (loginView) loginView.style.display = isLogin ? 'block' : 'none';
if (signupView) signupView.style.display = isLogin ? 'none' : 'block';
if (title) title.textContent = isLogin ? 'Welcome Back' : 'Join Community';
if (subtext) subtext.textContent = isLogin ? 'Login to sync your rides' : 'Create an account to start syncing';
}

async function handleLogin() {
const identifier = document.getElementById('loginIdentifier').value;
const password = document.getElementById('loginPassword').value;
const rememberMe = document.getElementById('loginRememberMe')?.checked || false;

if (!identifier || !password) return showToast('Please fill all fields', 'error');

try {
const res = await fetch('api/auth/login.php', {
method: 'POST',
headers: { 'Content-Type': 'application/json' },
body: JSON.stringify({ identifier, password, rememberMe })
});
const data = await res.json();
if (data.success) {
showToast(data.message, 'success');
closeAuthModal();
checkAuthStatus();
} else {
showToast(data.error, 'error');
}
} catch (e) {
showToast('Login failed. Try again.', 'error');
}
}

async function handleSignup() {
const username = document.getElementById('signupUsername').value;
const email = document.getElementById('signupEmail').value;
const password = document.getElementById('signupPassword').value;

if (!username || !email || !password) return showToast('Please fill all fields', 'error');

try {
const res = await fetch('api/auth/register.php', {
method: 'POST',
headers: { 'Content-Type': 'application/json' },
body: JSON.stringify({ username, email, password })
});
const data = await res.json();
if (data.success) {
showToast(data.message, 'success');
closeAuthModal();
checkAuthStatus();
} else {
showToast(data.error, 'error');
}
} catch (e) {
showToast('Signup failed. Try again.', 'error');
}
}

async function handleLogout() {
if (!confirm('Are you sure you want to logout?')) return;
try {
await fetch('api/auth/logout.php');
currentUser = null;
// 4. Clear cache on logout
localStorage.removeItem('trikefare_user');

if (typeof updateAuthUI === 'function') updateAuthUI();
if (typeof closeSettingsModal === 'function') closeSettingsModal();
showToast('Logged out successfully', 'info');
} catch (e) {
showToast('Logout failed', 'error');
}
}

// Re-map toast if not defined (for community.php)
if (typeof showToast !== 'function') {
window.showToast = (msg, type) => alert(msg);
}