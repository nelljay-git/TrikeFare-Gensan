<?php
session_start();
if (!isset($_SESSION['api_token'])) {
    $_SESSION['api_token'] = bin2hex(random_bytes(32));
}
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
    <title>Community Feed - TrikeFare Gensan</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --community-accent: #298648ff;
            --community-bg: #f8f9fd;
            --community-card: #ffffff;
            --community-text: #2d3436;
            --community-text-dim: #636e72;
            --community-border: rgba(0, 0, 0, 0.05);
            --title-text: black;
        }

        [data-theme="dark"] {
            --community-bg: #0a0e1a;
            --community-card: #161b2d;
            --community-text: #f1f2f6;
            --community-text-dim: #a4b0be;
            --community-border: rgba(255, 255, 255, 0.05);
            --title-text: white;
        }

        html,
        body {
            height: auto;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        body {
            background-color: var(--community-bg);
            color: var(--community-text);
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            transition: background 0.3s ease;
        }

        .no-scroll {
            overflow: hidden !important;
        }

        .comm-header {
            background: var(--community-card);
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-bottom: 1px solid var(--community-border);
            position: fixed;
            width: 100%;
        }

        .comm-logo {
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--title-text);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .comm-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            margin-top: 64px;
        }

        .stats-section {
            background: linear-gradient(135deg, var(--community-accent), #002238ff);
            color: white;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 24px;
            box-shadow: 0 10px 20px rgba(108, 92, 231, 0.2);
            display: none;
        }

        .stats-title {
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
            opacity: 0.9;
            color: white;
        }

        .stats-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .stats-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stats-item:last-child {
            border-bottom: none;
        }

        .filter-bar {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 4px 0 20px 0;
            scrollbar-width: none;
        }

        .filter-bar::-webkit-scrollbar {
            display: none;
        }

        .filter-btn {
            background: var(--community-card);
            border: 1px solid var(--community-border);
            color: var(--community-text-dim);
            padding: 8px 16px;
            border-radius: 20px;
            white-space: nowrap;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-btn.active {
            background: var(--community-accent);
            color: white;
            border-color: var(--community-accent);
        }

        .post-card {
            background: var(--community-card);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid var(--community-border);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
            position: relative;
            animation: slideUp 0.4s ease-out;
        }

        .pinned-label {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--community-accent);
            background: rgba(108, 92, 231, 0.1);
            padding: 4px 8px;
            border-radius: 6px;
            margin-bottom: 10px;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .post-type {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-block;
            margin-bottom: 10px;
        }

        .type-traffic {
            background: rgba(255, 71, 87, 0.1);
            color: #ff4757;
        }

        .type-fare {
            background: rgba(46, 213, 115, 0.1);
            color: #2ed573;
        }

        .type-tip {
            background: rgba(30, 144, 255, 0.1);
            color: #1e90ff;
        }

        .type-feedback {
            background: rgba(255, 165, 2, 0.1);
            color: #ffa502;
        }

        .type-official {
            background: rgba(31, 128, 255, 0.1);
            color: #468fc4ff;
        }

        .post-title {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 6px;
        }

        .post-content {
            font-size: 0.95rem;
            white-space: break-spaces;
            line-height: 1.5;
            color: var(--community-text);
            margin-bottom: 12px;
        }

        .post-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.8rem;
            color: var(--community-text-dim);
            margin-bottom: 16px;
        }

        .post-meta i {
            font-size: 0.85rem;
        }

        .post-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            border-top: 1px solid var(--community-border);
            padding-top: 12px;
        }

        .reaction-group {
            display: flex;
            gap: 6px;
            background: var(--community-bg);
            padding: 4px 8px;
            border-radius: 20px;
        }

        .action-btn {
            background: transparent;
            border: none;
            padding: 4px 6px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--community-text-dim);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s ease;
        }

        .action-btn i {
            font-size: 0.95rem;
        }

        .action-btn:hover {
            color: var(--community-text);
        }

        .action-btn.active-like {
            color: #2ed573;
        }

        .action-btn.active-dislike {
            color: #ff4757;
        }

        .action-btn.comment-btn {
            margin-left: auto;
            background: var(--community-bg);
            padding: 6px 12px;
        }

        .fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--community-accent);
            color: white;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 6px 20px rgba(108, 92, 231, 0.4);
            cursor: pointer;
            z-index: 1000;
            border: none;
        }

        /* Modal Styles */
        .comm-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-content-box {
            background: var(--community-card);
            width: 100%;
            max-width: 450px;
            border-radius: 20px;
            padding: 24px;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--community-text-dim);
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--community-text-dim);
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid var(--community-border);
            background: var(--community-bg);
            color: var(--community-text);
            font-family: inherit;
            box-sizing: border-box;
        }

        .form-textarea {
            resize: none;
            height: 100px;
        }

        .submit-btn {
            background: var(--community-accent);
            color: white;
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            margin-top: 10px;
        }

        .loading-spinner {
            display: flex;
            justify-content: center;
            padding: 40px;
            color: var(--community-accent);
        }

        /* Comments & Replies CSS */
        .comments-container {
            margin-top: 12px;
            display: none;
            border-top: 1px solid var(--community-border);
            padding-top: 12px;
        }

        .comment-item {
            margin-bottom: 12px;
        }

        .comment-bubble {
            background: var(--community-bg);
            padding: 8px 12px;
            border-radius: 12px;
            position: relative;
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2px;
        }

        .comment-user {
            font-weight: 700;
            font-size: 0.8rem;
            color: #31ad5e;
        }

        .comment-time {
            font-size: 0.65rem;
            color: var(--community-text-dim);
        }

        .comment-content {
            font-size: 0.85rem;
            line-height: 1.4;
            word-wrap: break-word;
        }

        .see-more {
            color: var(--community-accent);
            font-weight: 700;
            cursor: pointer;
            font-size: 0.75rem;
            margin-left: 4px;
        }

        .comment-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 4px;
            padding-left: 5px;
        }

        .comment-action-btn {
            background: none;
            border: none;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--community-text-dim);
            cursor: pointer;
            padding: 4px 0;
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .comment-action-btn:hover {
            color: var(--community-text);
        }

        .comment-action-btn.active-like {
            color: #2ed573;
        }

        .comment-action-btn.active-dislike {
            color: #ff4757;
        }

        .replies-toggle-btn {
            background: none;
            border: none;
            color: var(--community-accent);
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            padding: 4px 0;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .replies-list {
            margin-left: 20px;
            margin-top: 6px;
            border-left: 2px solid var(--community-border);
            padding-left: 10px;
            display: none;
        }

        .nested-replies {
            margin-left: 25px;
            margin-top: 4px;
            border-left: 1px dashed var(--community-border);
            padding-left: 8px;
        }

        .reply-item {
            margin-bottom: 8px;
        }

        .reply-bubble {
            background: var(--community-bg);
            padding: 6px 10px;
            border-radius: 10px;
            opacity: 0.95;
        }

        .comment-input-area {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            align-items: center;
        }

        .comment-input {
            flex: 1;
            background: var(--community-bg);
            border: 1px solid var(--community-border);
            border-radius: 20px;
            padding: 8px 16px;
            font-size: 0.85rem;
            color: var(--community-text);
            outline: none;
        }

        .comment-input:focus {
            border-color: var(--community-accent);
        }

        .comment-submit-btn {
            background: var(--community-accent);
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .comment-submit-btn:hover {
            transform: scale(1.1);
        }

        .reply-input-wrapper {
            margin-left: 30px;
            margin-top: 8px;
            display: none;
        }

        .nickname-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 3000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .nickname-box {
            background: var(--community-card);
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            width: 100%;
            max-width: 350px;
        }

        @media (max-width: 480px) {
            .comm-container {
                padding: 15px;
            }

            .post-card {
                padding: 16px;
            }

            .replies-list {
                margin-left: 20px;
            }
        }
    </style>
</head>

<body>

    <header class="comm-header">
        <a href="index.php" class="comm-logo">
            <i class="fa-solid fa-angle-left"></i> TrikeFare Community
        </a>
        <button class="filter-btn" onclick="toggleTheme()" id="theme-toggle">
            <i class="fa-solid fa-moon"></i>
        </button>
    </header>

    <div class="comm-container">
        <section class="stats-section" id="stats-section">
            <div class="stats-title"><i class="fa-solid fa-fire"></i> Most Reported Areas Today</div>
            <div id="stats-loader" class="loading-spinner" style="color: white; padding: 10px;"><i
                    class="fa-solid fa-circle-notch fa-spin"></i></div>
            <div class="stats-list" id="stats-list"></div>
        </section>

        <div class="filter-bar">
            <button class="filter-btn active" onclick="filterPosts('all', this)">All</button>
            <button class="filter-btn" onclick="filterPosts('official', this)">Official Announcement</button>
            <button class="filter-btn" onclick="filterPosts('traffic', this)">Traffic</button>
            <button class="filter-btn" onclick="filterPosts('fare', this)">Fare</button>
            <button class="filter-btn" onclick="filterPosts('tip', this)">Tips</button>
            <button class="filter-btn" onclick="filterPosts('feedback', this)">Feedback</button>

        </div>

        <div id="posts-container">
            <!-- Posts will be loaded here -->
        </div>

        <div id="posts-loader" class="loading-spinner"><i class="fa-solid fa-circle-notch fa-spin fa-2x"></i></div>
    </div>

    <button class="fab" onclick="openPostModal()">
        <i class="fa-solid fa-plus"></i>
    </button>

    <!-- Post Modal -->
    <div class="comm-modal" id="postModal">
        <div class="modal-content-box">
            <span class="modal-close" onclick="closePostModal()">&times;</span>
            <h2 style="margin-top: 0; font-size: 1.4rem;">Create a Thread</h2>
            <form id="postForm">
                <div class="form-group">
                    <label style="margin-top: 15px;">Thread Category</label>
                    <select class="form-select" id="postType" required>
                        <option value="" disabled selected>Select type...</option>
                        <option value="traffic">Traffic Report</option>
                        <option value="fare">Fare Issue</option>
                        <option value="tip">Route Tip</option>
                        <option value="feedback">Driver Feedback</option>
                    </select>
                </div>
                <div class="form-group">
                    <label style="margin-top: 15px;">Title (Optional)</label>
                    <input type="text" class="form-input" id="postTitle" placeholder="Short summary..."
                        value="Post Thread">
                </div>
                <div class="form-group">
                    <label style="margin-top: 15px;">Location</label>
                    <input type="text" class="form-input" id="postLocation" placeholder="Where is this happening?"
                        required>
                </div>
                <div class="form-group">
                    <label style="margin-top: 15px;">Description</label>
                    <textarea class="form-textarea" id="postContent" maxlength="1000"
                        placeholder="Details... (max 1000 chars)" required></textarea>
                    <div style="text-align: right; font-size: 0.75rem; color: var(--community-text-dim); margin-top: 4px;"
                        id="charCount">0/1000</div>
                </div>
                <button type="submit" class="submit-btn" id="submitBtn">Post to Community</button>
            </form>
        </div>
    </div>

    <?php include 'components/auth_modal.php'; ?>

    <script>
        <?php include 'components/auth_js.php'; ?>
        const API_TOKEN = '<?php echo $_SESSION['api_token']; ?>';
        let currentFilter = 'all';

        document.addEventListener('DOMContentLoaded', () => {
            checkAuthStatus(); // Check login status
            ensureUsername(); // Auto-generate on load if not logged in
            loadPosts();
            loadStats();

            // Character count listener
            document.getElementById('postContent').addEventListener('input', function () {
                document.getElementById('charCount').textContent = this.value.length + '/1000';
            });

            // Form submission
            document.getElementById('postForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                if (!currentUser) {
                    showToast('Please login to create a thread.', 'error');
                    openAuthModal();
                    return;
                }
                if (!await checkNickname()) return;
                const submitBtn = document.getElementById('submitBtn');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Posting...';

                const data = {
                    type: document.getElementById('postType').value,
                    title: document.getElementById('postTitle').value,
                    location: document.getElementById('postLocation').value,
                    content: document.getElementById('postContent').value
                };

                try {
                    const response = await fetch('api/community_submit.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-API-TOKEN': API_TOKEN
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();
                    if (result.success) {
                        alert('Post shared with community!');
                        closePostModal();
                        document.getElementById('postForm').reset();
                        loadPosts();
                        loadStats();
                    } else {
                        alert(result.error || 'Something went wrong.');
                    }
                } catch (err) {
                    alert('Network error. Please try again.');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Post to Community';
                }
            });
        });

        async function loadPosts() {
            const container = document.getElementById('posts-container');
            const loader = document.getElementById('posts-loader');

            loader.style.display = 'flex';

            try {
                const response = await fetch(`api/community_fetch.php?type=${currentFilter}`, {
                    headers: { 'X-API-TOKEN': API_TOKEN }
                });
                const posts = await response.json();

                container.innerHTML = '';

                if (posts.length === 0) {
                    container.innerHTML = `<div style="text-align:center; padding:40px; color:var(--community-text-dim);">No posts yet in this category.</div>`;
                } else {
                    posts.forEach(post => {
                        const card = document.createElement('div');
                        card.className = 'post-card';
                        card.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div class="post-type type-${post.type}">${post.type}</div>
                            ${post.is_pinned ? `<div class="pinned-label"><i class="fa-solid fa-thumbtack"></i> Pinned</div>` : ''}
                        </div>
                        ${post.title ? `<div class="post-title">${escapeHtml(post.title)}</div>` : ''}
                        <div class="post-content">${escapeHtml(post.content)}</div>
                        <div class="post-meta">
                            <span><i class="fa-solid fa-location-dot"></i> ${escapeHtml(post.location)}</span>
                            <span><i class="fa-solid fa-clock"></i> <span class="time-ago" data-time="${post.created_at}">${formatTimeAgo(post.created_at)}</span></span>
                        </div>
                        <div class="post-actions">
                            <div class="reaction-group">
                                <button class="action-btn ${post.user_reaction === 'like' ? 'active-like' : ''}" 
                                        onclick="handleReaction('post', ${post.id}, 'like', this)">
                                    <i class="fa-solid fa-thumbs-up"></i> <span class="count">${post.likes}</span>
                                </button>
                                <button class="action-btn ${post.user_reaction === 'dislike' ? 'active-dislike' : ''}" 
                                        onclick="handleReaction('post', ${post.id}, 'dislike', this)">
                                    <i class="fa-solid fa-thumbs-down"></i> <span class="count">${post.dislikes}</span>
                                </button>
                            </div>
                            <button class="action-btn comment-btn" onclick="toggleComments(${post.id})">
                                <i class="fa-solid fa-comment"></i> Comments
                            </button>
                        </div>
                        <div class="comments-container" id="comments-${post.id}">
                            <div class="comments-list" id="comments-list-${post.id}">
                                <!-- Comments loaded here -->
                            </div>
                            <div class="comment-input-area">
                                <input type="text" class="comment-input" id="input-${post.id}" placeholder="Write a comment..." maxlength="150">
                                <button class="comment-submit-btn" onclick="postComment(${post.id})">
                                    <i class="fa-solid fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    `;
                        container.appendChild(card);
                    });
                }
            } catch (err) {
                container.innerHTML = `<div style="text-align:center; padding:40px; color:#ff4757;">Failed to load posts.</div>`;
            } finally {
                loader.style.display = 'none';
            }
        }

        async function loadStats() {
            const list = document.getElementById('stats-list');
            const loader = document.getElementById('stats-loader');

            try {
                const response = await fetch('api/community_stats.php', {
                    headers: { 'X-API-TOKEN': API_TOKEN }
                });
                const stats = await response.json();

                list.innerHTML = '';
                if (stats.length === 0) {
                    list.innerHTML = '<div style="font-size:0.85rem; opacity:0.8;">No reports yet today.</div>';
                } else {
                    stats.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'stats-item';
                        div.innerHTML = `
                        <span style="font-weight:600;">${escapeHtml(item.location)}</span>
                        <span style="background:rgba(255,255,255,0.2); padding:2px 8px; border-radius:10px; font-size:0.75rem;">${item.report_count} reports</span>
                    `;
                        list.appendChild(div);
                    });
                }
            } catch (err) {
                console.error('Stats error:', err);
            } finally {
                loader.style.display = 'none';
            }
        }

        async function handleReaction(targetType, targetId, reactionType, btn) {
            if (!currentUser) {
                showToast('Please login to react to posts.', 'error');
                openAuthModal();
                return;
            }

            // Optimistic UI Update
            const countEl = btn.querySelector('.count');
            let currentCount = parseInt(countEl.textContent);
            const isActive = btn.classList.contains('active-' + reactionType);
            const group = btn.parentElement;
            const otherBtn = Array.from(group.children).find(el => el !== btn);
            const otherReactionType = reactionType === 'like' ? 'dislike' : 'like';
            const wasOtherActive = otherBtn && otherBtn.classList.contains('active-' + otherReactionType);

            if (isActive) {
                // Toggle OFF
                countEl.textContent = Math.max(0, currentCount - 1);
                btn.classList.remove('active-' + reactionType);
            } else {
                // Toggle ON
                countEl.textContent = currentCount + 1;
                btn.classList.add('active-' + reactionType);

                // If other was active, decrement it
                if (wasOtherActive) {
                    const otherCountEl = otherBtn.querySelector('.count');
                    otherCountEl.textContent = Math.max(0, parseInt(otherCountEl.textContent) - 1);
                    otherBtn.classList.remove('active-' + otherReactionType);
                }
            }

            try {
                const response = await fetch('api/react.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-TOKEN': API_TOKEN
                    },
                    body: JSON.stringify({
                        target_type: targetType,
                        target_id: targetId,
                        reaction_type: reactionType
                    })
                });

                const result = await response.json();
                if (result.success) {
                    // Sync with server result just in case
                    countEl.textContent = result[reactionType + 's'];
                    const otherCountEl = otherBtn.querySelector('.count');
                    if (otherCountEl) {
                        otherCountEl.textContent = result[otherReactionType + 's'];
                    }
                } else {
                    // Revert on error
                    loadPosts(); // Or just reload the specific item
                }
            } catch (err) {
                console.error('Reaction error:', err);
                // Revert could be handled here
            }
        }

        function filterPosts(type, btn) {
            currentFilter = type;
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            loadPosts();
        }

        async function openPostModal() {
            if (!currentUser) {
                showToast('Please login to share a post.', 'error');
                openAuthModal();
                return;
            }
            document.getElementById('postModal').style.display = 'flex';
            document.body.classList.add('no-scroll');
        }

        function closePostModal() {
            document.getElementById('postModal').style.display = 'none';
            document.body.classList.remove('no-scroll');
        }

        function toggleTheme() {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            if (isDark) {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('trikefareTheme', 'light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('trikefareTheme', 'dark');
            }
        }

        // Initialize theme
        if (localStorage.getItem('trikefareTheme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatTimeAgo(dateString) {
            if (!dateString) return '';

            // Handle MySQL format (YYYY-MM-DD HH:MM:SS)
            // Replace space with T and append Z to force UTC parsing
            const utcDate = new Date(dateString.replace(' ', 'T') + 'Z');
            const now = new Date();
            const diffInSeconds = Math.floor((now - utcDate) / 1000);

            if (diffInSeconds < 5) return 'Just now';
            if (diffInSeconds < 60) return diffInSeconds + 's ago';

            const diffInMinutes = Math.floor(diffInSeconds / 60);
            if (diffInMinutes < 60) return diffInMinutes + 'm ago';

            const diffInHours = Math.floor(diffInMinutes / 60);
            if (diffInHours < 24) return diffInHours + 'h ago';

            const diffInDays = Math.floor(diffInHours / 24);
            if (diffInDays < 7) return diffInDays + 'd ago';

            return utcDate.toLocaleDateString(undefined, {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            });
        }

        // Auto-refresh relative times every minute
        setInterval(() => {
            document.querySelectorAll('.time-ago').forEach(el => {
                const timestamp = el.getAttribute('data-time');
                if (timestamp) {
                    el.textContent = formatTimeAgo(timestamp);
                }
            });
        }, 60000);

        async function ensureUsername() {
            if (currentUser) return currentUser.username;
            let username = localStorage.getItem('trikefareVoteUsername');
            if (username) return username;

            try {
                const res = await fetch('api/generate_username.php');
                const data = await res.json();
                if (data.success) {
                    localStorage.setItem('trikefareVoteUsername', data.username);
                    console.log('Assigned username:', data.username);
                    return data.username;
                }
            } catch (e) {
                console.error('Failed to auto-generate username:', e);
            }
            return null;
        }

        // Comment & Reply Logic
        async function checkNickname() {
            const username = await ensureUsername();
            if (!username) {
                alert('Unable to assign a username. Please check your connection.');
                return false;
            }
            return true;
        }


        async function toggleComments(postId) {
            const container = document.getElementById(`comments-${postId}`);
            if (container.style.display === 'block') {
                container.style.display = 'none';
            } else {
                container.style.display = 'block';
                await loadComments(postId);
            }
        }

        async function loadComments(postId) {
            const list = document.getElementById(`comments-list-${postId}`);
            list.innerHTML = '<div style="padding:10px; text-align:center;"><i class="fa-solid fa-circle-notch fa-spin"></i></div>';

            try {
                const response = await fetch(`api/comments_fetch.php?post_id=${postId}`, {
                    headers: { 'X-API-TOKEN': API_TOKEN }
                });
                const comments = await response.json();

                list.innerHTML = '';
                if (comments.length === 0) {
                    list.innerHTML = '<div style="padding:10px; font-size:0.8rem; color:var(--community-text-dim); text-align:center;">No comments yet.</div>';
                } else {
                    const initialComments = comments.slice(0, 3);
                    const remainingComments = comments.slice(3);

                    renderCommentList(initialComments, list, postId);

                    if (remainingComments.length > 0) {
                        const moreBtn = document.createElement('button');
                        moreBtn.className = 'replies-toggle-btn';
                        moreBtn.style.margin = '10px auto';
                        moreBtn.innerHTML = `View ${remainingComments.length} more comments`;
                        moreBtn.onclick = () => {
                            renderCommentList(remainingComments, list, postId);
                            moreBtn.remove();
                        };
                        list.appendChild(moreBtn);
                    }
                }
            } catch (err) {
                list.innerHTML = '<div style="padding:10px; color:#ff4757; font-size:0.8rem;">Error loading comments.</div>';
            }
        }

        function renderCommentList(comments, container, postId) {
            comments.forEach(comment => {
                const item = document.createElement('div');
                item.className = 'comment-item';

                const replies = comment.replies || [];
                const hasReplies = replies.length > 0;

                // Group Level 3 replies by parent_reply_id
                const level2Replies = replies.filter(r => !r.parent_reply_id);
                const level3Replies = replies.filter(r => r.parent_reply_id);

                let repliesHtml = '';
                level2Replies.forEach(reply => {
                    // Find children (Level 3) for this Level 2 reply
                    const children = level3Replies.filter(c => c.parent_reply_id == reply.id);
                    let childrenHtml = '';

                    children.forEach(child => {
                        childrenHtml += `
                        <div class="reply-item" style="margin-bottom: 5px;">
                            <div class="reply-bubble" style="opacity: 0.85;">
                                <div class="comment-header">
                                    <span class="comment-user">@${escapeHtml(child.username)}</span>
                                    <span class="comment-time time-ago" data-time="${child.created_at}">${formatTimeAgo(child.created_at)}</span>
                                </div>
                                <div class="comment-content" style="font-size: 0.8rem; white-space: break-spaces;">${formatCommentContent(child.content)}</div>
                            </div>
                            <div class="comment-actions" style="margin-bottom: 4px;">
                                <div class="reaction-group" style="background:transparent; padding:0;">
                                    <button class="comment-action-btn ${child.user_reaction === 'like' ? 'active-like' : ''}" 
                                            onclick="handleReaction('reply', ${child.id}, 'like', this)">
                                        <i class="fa-solid fa-thumbs-up"></i> <span class="count">${child.likes}</span>
                                    </button>
                                    <button class="comment-action-btn ${child.user_reaction === 'dislike' ? 'active-dislike' : ''}" 
                                            onclick="handleReaction('reply', ${child.id}, 'dislike', this)">
                                        <i class="fa-solid fa-thumbs-down"></i> <span class="count">${child.dislikes}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        `;
                    });

                    repliesHtml += `
                    <div class="reply-item">
                        <div class="reply-bubble">
                            <div class="comment-header">
                                <span class="comment-user">@${escapeHtml(reply.username)}</span>
                                <span class="comment-time time-ago" data-time="${reply.created_at}">${formatTimeAgo(reply.created_at)}</span>
                            </div>
                            <div class="comment-content">${formatCommentContent(reply.content)}</div>
                        </div>
                        <div class="comment-actions" style="margin-bottom: 4px;">
                            <div class="reaction-group" style="background:transparent; padding:0;">
                                <button class="comment-action-btn ${reply.user_reaction === 'like' ? 'active-like' : ''}" 
                                        onclick="handleReaction('reply', ${reply.id}, 'like', this)">
                                    <i class="fa-solid fa-thumbs-up"></i> <span class="count">${reply.likes}</span>
                                </button>
                                <button class="comment-action-btn ${reply.user_reaction === 'dislike' ? 'active-dislike' : ''}" 
                                        onclick="handleReaction('reply', ${reply.id}, 'dislike', this)">
                                    <i class="fa-solid fa-thumbs-down"></i> <span class="count">${reply.dislikes}</span>
                                </button>
                            </div>
                            <button class="comment-action-btn" onclick="toggleReplyInput(${comment.id}, ${reply.id}, '${escapeHtml(reply.username)}')">
                                <i class="fa-solid fa-reply"></i> Reply
                            </button>
                        </div>
                        ${childrenHtml ? `<div class="nested-replies">${childrenHtml}</div>` : ''}
                    </div>
                `;
                });

                item.innerHTML = `
                <div class="comment-bubble">
                    <div class="comment-header">
                        <span class="comment-user">@${escapeHtml(comment.username)}</span>
                        <span class="comment-time time-ago" data-time="${comment.created_at}">${formatTimeAgo(comment.created_at)}</span>
                    </div>
                    <div class="comment-content">${formatCommentContent(comment.content)}</div>
                </div>
                <div class="comment-actions">
                    <div class="reaction-group" style="background:transparent; padding:0;">
                        <button class="comment-action-btn ${comment.user_reaction === 'like' ? 'active-like' : ''}" 
                                onclick="handleReaction('comment', ${comment.id}, 'like', this)">
                            <i class="fa-solid fa-thumbs-up"></i> <span class="count">${comment.likes}</span>
                        </button>
                        <button class="comment-action-btn ${comment.user_reaction === 'dislike' ? 'active-dislike' : ''}" 
                                onclick="handleReaction('comment', ${comment.id}, 'dislike', this)">
                            <i class="fa-solid fa-thumbs-down"></i> <span class="count">${comment.dislikes}</span>
                        </button>
                    </div>
                    <button class="comment-action-btn" onclick="toggleReplyInput(${comment.id})">
                        <i class="fa-solid fa-reply"></i> Reply
                    </button>
                    ${hasReplies ? `<button class="replies-toggle-btn" onclick="toggleReplies(${comment.id}, this)">
                        <i class="fa-solid fa-chevron-down"></i> ${comment.replies.length}
                    </button>` : ''}
                </div>
                <div class="replies-list" id="replies-${comment.id}">${repliesHtml}</div>
                <div class="reply-input-wrapper" id="reply-input-area-${comment.id}">
                    <div class="comment-input-area" style="margin-top:5px;">
                        <input type="text" class="comment-input" id="reply-field-${comment.id}" placeholder="Write a reply..." maxlength="150">
                        <button class="comment-submit-btn" onclick="postReply(${comment.id}, ${postId})" style="width:28px; height:28px; font-size:0.7rem;">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            `;
                container.appendChild(item);
            });
        }

        function formatCommentContent(content) {
            const limit = 100;
            if (content.length <= limit) return escapeHtml(content);

            const truncated = escapeHtml(content.substring(0, limit));
            const full = escapeHtml(content);

            return `
                <span class="text-short">${truncated}...</span>
                <span class="text-full" style="display:none; white-space: break-spaces;">${full}</span>
                <span class="see-more" onclick="toggleSeeMore(this)">See more</span>
            `;
        }

        function toggleSeeMore(btn) {
            const parent = btn.parentElement;
            const short = parent.querySelector('.text-short');
            const full = parent.querySelector('.text-full');

            if (full.style.display === 'none') {
                full.style.display = 'inline';
                short.style.display = 'none';
                btn.textContent = 'Show less';
            } else {
                full.style.display = 'none';
                short.style.display = 'inline';
                btn.textContent = 'See more';
            }
        }

        function toggleReplies(commentId, btn) {
            const list = document.getElementById(`replies-${commentId}`);
            const isHidden = window.getComputedStyle(list).display === 'none';

            if (isHidden) {
                list.style.display = 'block';
                btn.innerHTML = `<i class="fa-solid fa-chevron-up"></i> Hide replies`;
            } else {
                list.style.display = 'none';
                btn.innerHTML = `<i class="fa-solid fa-chevron-down"></i> View ${list.children.length} replies`;
            }
        }

        async function postComment(postId) {
            if (!currentUser) {
                showToast('Please login to comment.', 'error');
                openAuthModal();
                return;
            }

            const input = document.getElementById(`input-${postId}`);
            const content = input.value.trim();

            if (!content) return;
            if (content.length > 150) {
                alert('Comment too long (max 150 chars)');
                return;
            }

            try {
                const response = await fetch('api/comment_submit.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-TOKEN': API_TOKEN
                    },
                    body: JSON.stringify({
                        post_id: postId,
                        username: localStorage.getItem('trikefareVoteUsername'),
                        content: content
                    })
                });

                const result = await response.json();
                if (result.success) {
                    input.value = '';
                    await loadComments(postId);
                } else {
                    alert(result.error || 'Failed to post comment.');
                }
            } catch (err) {
                alert('Network error. Try again.');
            }
        }

        function toggleReplyInput(commentId, parentReplyId = null, replyToUser = null) {
            const area = document.getElementById(`reply-input-area-${commentId}`);
            const input = document.getElementById(`reply-field-${commentId}`);

            // If already open and user clicks same button, close it
            // BUT if it's open for a different parent_reply, keep it open but update content
            const isVisible = area.style.display === 'block';
            const currentParentId = input.getAttribute('data-parent-reply');

            if (isVisible && currentParentId == parentReplyId) {
                area.style.display = 'none';
                return;
            }

            area.style.display = 'block';
            input.setAttribute('data-parent-reply', parentReplyId || '');

            if (replyToUser) {
                input.value = `@${replyToUser} `;
            } else {
                input.value = '';
            }
            input.focus();
        }

        async function postReply(commentId, postId) {
            if (!currentUser) {
                showToast('Please login to reply.', 'error');
                openAuthModal();
                return;
            }

            const input = document.getElementById(`reply-field-${commentId}`);
            const content = input.value.trim();
            const parentReplyId = input.getAttribute('data-parent-reply') || null;

            if (!content) return;
            if (content.length > 150) {
                alert('Reply too long (max 150 chars)');
                return;
            }

            try {
                const response = await fetch('api/reply_submit.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-TOKEN': API_TOKEN
                    },
                    body: JSON.stringify({
                        comment_id: commentId,
                        parent_reply_id: parentReplyId,
                        username: localStorage.getItem('trikefareVoteUsername'),
                        content: content
                    })
                });

                const result = await response.json();
                if (result.success) {
                    input.value = '';
                    document.getElementById(`reply-input-area-${commentId}`).style.display = 'none';
                    await loadComments(postId);
                } else {
                    alert(result.error || 'Failed to post reply.');
                }
            } catch (err) {
                alert('Network error. Try again.');
            }
        }
    </script>


</body>

</html>