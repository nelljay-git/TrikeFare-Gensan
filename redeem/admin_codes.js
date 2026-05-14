// ── Admin Codes JS ─────────────────────────────────────────────────────
let currentFilter = 'all';
let currentSearch = '';
let currentPage   = 1;

function showToast(msg, type = 'info') {
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => t.remove(), 3200);
}

// ── Create Codes ──────────────────────────────────────────────────────
async function createCodes() {
    const mode  = document.getElementById('createMode').value;
    const code  = document.getElementById('manualCode').value.trim();
    const count = parseInt(document.getElementById('autoCount').value) || 1;

    const body = mode === 'manual' ? { mode, code } : { mode, count };

    try {
        const res = await fetch('api/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (data.success) {
            showToast(`Created ${data.count} code(s)!`, 'success');
            showGeneratedCodes(data.codes);
            document.getElementById('manualCode').value = '';
            loadCodes();
        } else {
            showToast(data.error, 'error');
        }
    } catch { showToast('Failed to create codes', 'error'); }
}

function showGeneratedCodes(codes) {
    const wrap = document.getElementById('generatedCodes');
    const list = document.getElementById('genList');
    list.innerHTML = '';
    codes.forEach(c => {
        const el = document.createElement('span');
        el.className = 'gen-code';
        el.textContent = c;
        el.title = 'Click to copy';
        el.onclick = () => { navigator.clipboard.writeText(c); showToast('Copied!', 'info'); };
        list.appendChild(el);
    });
    wrap.classList.add('show');
}

function onModeChange() {
    const mode = document.getElementById('createMode').value;
    document.getElementById('manualGroup').style.display = mode === 'manual' ? 'flex' : 'none';
    document.getElementById('autoGroup').style.display   = mode === 'auto'   ? 'flex' : 'none';
}

// ── Load Codes ────────────────────────────────────────────────────────
async function loadCodes() {
    const params = new URLSearchParams({
        filter: currentFilter,
        search: currentSearch,
        page:   currentPage
    });

    try {
        const res  = await fetch('api/list.php?' + params);
        const data = await res.json();
        if (!data.success) { showToast(data.error, 'error'); return; }

        renderStats(data.stats);
        renderTable(data.codes);
        renderPagination(data.page, data.pages);
    } catch { showToast('Failed to load codes', 'error'); }
}

function renderStats(s) {
    document.getElementById('statTotal').textContent       = s.total || 0;
    document.getElementById('statActive').textContent      = s.active_count || 0;
    document.getElementById('statRedeemed').textContent    = s.redeemed_count || 0;
    document.getElementById('statDeactivated').textContent = s.deactivated_count || 0;
}

function renderTable(codes) {
    const tbody = document.getElementById('codesBody');
    if (!codes.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty-row">No codes found</td></tr>';
        return;
    }
    tbody.innerHTML = codes.map(c => {
        const statusBadge = c.status === 'active'
            ? '<span class="badge active">Active</span>'
            : '<span class="badge deactivated">Deactivated</span>';
        const redeemBadge = c.is_redeemed == 1
            ? '<span class="badge yes">Yes</span>'
            : '<span class="badge no">No</span>';
        const user = c.redeemed_by_name || '—';
        const date = c.redeemed_at ? new Date(c.redeemed_at).toLocaleString() : '—';
        const toggleLabel = c.status === 'active' ? 'Deactivate' : 'Activate';
        const toggleStatus = c.status === 'active' ? 'deactivated' : 'active';
        const delDisabled = c.is_redeemed == 1 ? 'disabled title="Cannot delete redeemed code"' : '';

        return `<tr>
            <td class="code-cell">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span>${esc(c.code)}</span>
                    <button class="btn-copy-sm" onclick="copyToClipboard('${c.code}', this)" title="Copy Code">
                        <i class="fa-regular fa-copy"></i>
                    </button>
                </div>
            </td>
            <td>${statusBadge}</td>
            <td>${redeemBadge}</td>
            <td>${esc(user)}</td>
            <td style="font-size:0.78rem;color:var(--text-dim)">${date}</td>
            <td><div class="action-btns">
                <button class="btn-sm" onclick="toggleCode(${c.id},'${toggleStatus}')">${toggleLabel}</button>
                <button class="btn-sm danger" onclick="deleteCode(${c.id})" ${delDisabled}>Delete</button>
            </div></td>
        </tr>`;
    }).join('');
}

function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function renderPagination(page, pages) {
    const wrap = document.getElementById('pagination');
    if (pages <= 1) { wrap.innerHTML = ''; return; }
    let html = `<button class="page-btn" onclick="goPage(${page-1})" ${page<=1?'disabled':''}>‹</button>`;
    for (let i = 1; i <= pages; i++) {
        html += `<button class="page-btn${i===page?' active':''}" onclick="goPage(${i})">${i}</button>`;
    }
    html += `<button class="page-btn" onclick="goPage(${page+1})" ${page>=pages?'disabled':''}>›</button>`;
    wrap.innerHTML = html;
}

function goPage(p) { currentPage = p; loadCodes(); }

// ── Filter / Search ───────────────────────────────────────────────────
function setFilter(f) {
    currentFilter = f;
    currentPage   = 1;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.toggle('active', b.dataset.filter === f));
    loadCodes();
}

let searchTimer;
function onSearch(val) {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { currentSearch = val; currentPage = 1; loadCodes(); }, 300);
}

// ── Toggle / Delete ───────────────────────────────────────────────────
async function toggleCode(id, status) {
    try {
        const res = await fetch('api/toggle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, status })
        });
        const data = await res.json();
        if (data.success) { showToast(data.message, 'success'); loadCodes(); }
        else showToast(data.error, 'error');
    } catch { showToast('Toggle failed', 'error'); }
}

async function deleteCode(id) {
    if (!confirm('Delete this code? This cannot be undone.')) return;
    try {
        const res = await fetch('api/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (data.success) { showToast(data.message, 'success'); loadCodes(); }
        else showToast(data.error, 'error');
    } catch { showToast('Delete failed', 'error'); }
}

// ── Copy to Clipboard ──────────────────────────────────────────────────
function copyToClipboard(text, btn) {
    navigator.clipboard.writeText(text);
    const icon = btn.querySelector('i');
    if (icon) {
        icon.className = 'fa-solid fa-check';
        icon.style.color = 'var(--primary)';
        setTimeout(() => {
            icon.className = 'fa-regular fa-copy';
            icon.style.color = '';
        }, 1500);
    }
    showToast('Copied!', 'info');
}

// ── Init ──────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    onModeChange();
    loadCodes();
});
