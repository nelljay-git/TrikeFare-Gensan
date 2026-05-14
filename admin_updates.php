<?php
// admin_updates.php
session_start();

// Simple admin authentication
$admin_password = "admin123"; // Change this!

if (isset($_POST['password'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['is_admin'] = true;
        if (empty($_SESSION['app_token'])) {
            $_SESSION['app_token'] = bin2hex(random_bytes(32));
        }
    } else {
        $error = "Invalid password";
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['is_admin']);
    header("Location: admin_updates.php");
    exit;
}

$is_logged_in = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
$appToken = isset($_SESSION['app_token']) ? $_SESSION['app_token'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Updates</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f8fafc;
            --surface: #ffffff;
            --border: #e2e8f0;
            --primary: #00b894;
            --accent: #6c5ce7;
            --text: #1e293b;
            --text-dim: #64748b;
            --danger: #ff4757;
            --radius: 12px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .login-box {
            background: var(--surface);
            padding: 40px;
            border-radius: var(--radius);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            max-width: 400px;
            margin: 100px auto;
            text-align: center;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid var(--border);
            border-radius: 8px;
            box-sizing: border-box;
            font-family: inherit;
        }

        button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            transition: opacity 0.2s;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-accent { background: var(--accent); color: white; }
        .btn-danger { background: var(--danger); color: white; }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .update-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .update-info h3 { margin: 0 0 5px 0; }
        .update-info p { margin: 0; color: var(--text-dim); font-size: 0.9rem; }
        .update-meta { font-size: 0.8rem; color: var(--text-dim); margin-top: 5px; }

        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: var(--surface);
            padding: 30px;
            border-radius: var(--radius);
            width: 90%;
            max-width: 500px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$is_logged_in): ?>
            <div class="login-box">
                <h2>Admin Login</h2>
                <?php if (isset($error)) echo "<p style='color:red'>$error</p>"; ?>
                <form method="POST">
                    <input type="password" name="password" placeholder="Admin Password" required autofocus>
                    <button type="submit" class="btn-primary" style="width:100%">Login</button>
                </form>
                <p style="margin-top:20px; font-size:0.8rem; color:var(--text-dim)">Default: admin123</p>
            </div>
        <?php else: ?>
            <div class="header">
                <h1>Manage Updates</h1>
                <div>
                    <button class="btn-accent" onclick="showAddModal()"><i class="fa-solid fa-plus"></i> New Update</button>
                    <a href="?logout=1" style="margin-left:15px; color:var(--danger)">Logout</a>
                </div>
            </div>

            <div id="updatesList">
                <p>Loading updates...</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ADD/EDIT MODAL -->
    <div class="modal" id="updateModal">
        <div class="modal-content">
            <h2 id="modalTitle">New Update</h2>
            <form id="updateForm">
                <input type="hidden" id="updateId">
                <label>Title</label>
                <input type="text" id="title" required>
                
                <label>Date</label>
                <input type="date" id="release_date" required>
                
                <label>Type</label>
                <select id="update_type">
                    <option value="Feature">Feature</option>
                    <option value="Bug Fix">Bug Fix</option>
                    <option value="UI Improvement">UI Improvement</option>
                    <option value="Maintenance">Maintenance</option>
                </select>
                
                <label>Description</label>
                <textarea id="description" rows="4" required></textarea>
                
                <div style="display:flex; gap:10px; margin-top:10px;">
                    <button type="submit" class="btn-primary" style="flex:1">Save</button>
                    <button type="button" class="btn-danger" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const APP_TOKEN = '<?php echo $appToken; ?>';
        let updates = [];

        async function loadUpdates() {
            try {
                const res = await fetch('api/admin_updates_api.php', {
                    headers: { 'X-Session-Token': APP_TOKEN }
                });
                if (!res.ok) throw new Error('Failed to load');
                updates = await res.json();
                renderUpdates();
            } catch (err) {
                document.getElementById('updatesList').innerHTML = '<p style="color:red">Error loading updates</p>';
            }
        }

        function renderUpdates() {
            const container = document.getElementById('updatesList');
            if (updates.length === 0) {
                container.innerHTML = '<p>No updates found.</p>';
                return;
            }

            container.innerHTML = updates.map(u => `
                <div class="update-card">
                    <div class="update-info">
                        <h3>${u.title}</h3>
                        <p>${u.description}</p>
                        <div class="update-meta">
                            <span><i class="fa-solid fa-tag"></i> ${u.update_type}</span> | 
                            <span><i class="fa-solid fa-calendar"></i> ${u.release_date}</span>
                        </div>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button onclick="showEditModal(${u.id})" class="btn-accent" style="padding:8px 12px;"><i class="fa-solid fa-pen"></i></button>
                        <button onclick="deleteUpdate(${u.id})" class="btn-danger" style="padding:8px 12px;"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
            `).join('');
        }

        function showAddModal() {
            document.getElementById('modalTitle').innerText = 'New Update';
            document.getElementById('updateId').value = '';
            document.getElementById('updateForm').reset();
            document.getElementById('release_date').value = new Date().toISOString().split('T')[0];
            document.getElementById('updateModal').style.display = 'flex';
        }

        function showEditModal(id) {
            const u = updates.find(x => x.id == id);
            if (!u) return;
            document.getElementById('modalTitle').innerText = 'Edit Update';
            document.getElementById('updateId').value = u.id;
            document.getElementById('title').value = u.title;
            document.getElementById('release_date').value = u.release_date;
            document.getElementById('update_type').value = u.update_type;
            document.getElementById('description').value = u.description;
            document.getElementById('updateModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('updateModal').style.display = 'none';
        }

        document.getElementById('updateForm').onsubmit = async (e) => {
            e.preventDefault();
            const id = document.getElementById('updateId').value;
            const data = {
                id: id,
                title: document.getElementById('title').value,
                release_date: document.getElementById('release_date').value,
                update_type: document.getElementById('update_type').value,
                description: document.getElementById('description').value
            };

            const action = id ? 'edit' : 'create';
            const res = await fetch(`api/admin_updates_api.php?action=${action}`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-Session-Token': APP_TOKEN
                },
                body: JSON.stringify(data)
            });

            if (res.ok) {
                closeModal();
                loadUpdates();
            } else {
                alert('Failed to save update');
            }
        };

        async function deleteUpdate(id) {
            if (!confirm('Are you sure you want to delete this update?')) return;
            const res = await fetch('api/admin_updates_api.php?action=delete', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-Session-Token': APP_TOKEN
                },
                body: JSON.stringify({ id })
            });

            if (res.ok) {
                loadUpdates();
            } else {
                alert('Failed to delete update');
            }
        }

        if (document.getElementById('updatesList')) {
            loadUpdates();
        }
    </script>
</body>
</html>
