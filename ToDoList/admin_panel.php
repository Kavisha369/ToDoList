<?php
// ============================================================
//  admin_panel.php  —  Admin user management
// ============================================================
require_once 'config.php';
requireAdmin();          // redirects if not logged-in or not admin

$db = getDB();

// ── AJAX / POST ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        echo json_encode(['ok' => false, 'msg' => 'CSRF mismatch']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        // Delete a user (and cascade their tasks)
        case 'delete_user':
            $uid = (int) ($_POST['user_id'] ?? 0);
            if ($uid <= 1) { echo json_encode(['ok'=>false,'msg'=>"Cannot delete the admin account."]); exit; }
            $stmt = $db->prepare('DELETE FROM users WHERE id=? AND role != "admin"');
            $stmt->execute([$uid]);
            echo json_encode(['ok' => true]);
            break;

        // Change a user's role
        case 'toggle_role':
            $uid  = (int) ($_POST['user_id'] ?? 0);
            $role = $_POST['role'] ?? 'user';
            if (!in_array($role, ['user','admin'])) { echo json_encode(['ok'=>false,'msg'=>'Bad role']); exit; }
            if ($uid <= 1) { echo json_encode(['ok'=>false,'msg'=>'Cannot change super-admin role']); exit; }
            $stmt = $db->prepare('UPDATE users SET role=? WHERE id=?');
            $stmt->execute([$role, $uid]);
            echo json_encode(['ok' => true]);
            break;

        // Reset a user's password (admin sets a temporary one)
        case 'reset_password':
            $uid  = (int) ($_POST['user_id'] ?? 0);
            $newpw = trim($_POST['new_password'] ?? '');
            if (strlen($newpw) < 6) { echo json_encode(['ok'=>false,'msg'=>'Password too short (min 6)']); exit; }
            $hash = password_hash($newpw, PASSWORD_BCRYPT, ['cost'=>12]);
            $stmt = $db->prepare('UPDATE users SET password=? WHERE id=?');
            $stmt->execute([$hash, $uid]);
            echo json_encode(['ok' => true]);
            break;

        default:
            echo json_encode(['ok' => false, 'msg' => 'Unknown action']);
    }
    exit;
}

// ── Fetch stats ────────────────────────────────────────────────
$totalUsers    = (int) $db->query('SELECT COUNT(*) FROM users WHERE role="user"')->fetchColumn();
$totalTasks    = (int) $db->query('SELECT COUNT(*) FROM tasks WHERE is_folder=0')->fetchColumn();
$completedTask = (int) $db->query("SELECT COUNT(*) FROM tasks WHERE is_folder=0 AND status='completed'")->fetchColumn();
$newToday      = (int) $db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at)=CURDATE()")->fetchColumn();

// ── Fetch users with task counts ──────────────────────────────
$users = $db->query(
    "SELECT u.*, 
        COUNT(t.id) AS task_count,
        SUM(t.status='completed') AS done_count
     FROM users u
     LEFT JOIN tasks t ON t.user_id = u.id AND t.is_folder = 0
     GROUP BY u.id
     ORDER BY u.created_at DESC"
)->fetchAll();

$token = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — TaskFlow</title>
    <meta name="description" content="TaskFlow administrator panel – manage users and monitor activity.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<!-- ══ Sidebar ═══════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24">
                <path fill="url(#ga)" d="M9 11.5l2 2 4-4"/>
                <rect x="3" y="3" width="18" height="18" rx="4" stroke="url(#ga)" stroke-width="2" fill="none"/>
                <defs><linearGradient id="ga" x1="3" y1="3" x2="21" y2="21" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#7c3aed"/><stop offset="1" stop-color="#06b6d4"/>
                </linearGradient></defs>
            </svg>
        </div>
        <span class="brand-text">TaskFlow <span class="admin-badge">Admin</span></span>
    </div>

    <nav class="sidebar-nav">
        <a href="admin_panel.php" class="nav-item active" id="nav-overview">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
            </svg>
            Overview
        </a>
        <a href="#users-section" class="nav-item" id="nav-users">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Users
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-pill">
            <div class="user-avatar admin-avatar">A</div>
            <div class="user-info">
                <span class="user-name">admin</span>
                <span class="user-role" style="color: var(--accent-purple)">Super Admin</span>
            </div>
        </div>
        <a href="logout.php" class="btn-logout" title="Logout">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </a>
    </div>
</aside>

<!-- ══ Main ══════════════════════════════════════════════════ -->
<main class="main-content" id="main-content">

    <header class="topbar">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
        <h2 class="topbar-title">Admin Panel</h2>
        <div class="topbar-actions">
            <span class="topbar-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                Admin Mode
            </span>
        </div>
    </header>

    <!-- Stats -->
    <section class="stats-row">
        <div class="stat-card">
            <div class="stat-icon stat-icon-purple">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalUsers ?></span>
                <span class="stat-label">Total Users</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-blue">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalTasks ?></span>
                <span class="stat-label">Total Tasks</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-green">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?= $completedTask ?></span>
                <span class="stat-label">Tasks Completed</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-yellow">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?= $newToday ?></span>
                <span class="stat-label">New Today</span>
            </div>
        </div>
    </section>

    <!-- Users Section -->
    <section class="section-card" id="users-section">
        <div class="section-header">
            <h3 class="section-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                </svg>
                Registered Users
            </h3>
            <div class="search-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="user-search" class="search-input" placeholder="Search users…" oninput="filterUsers(this.value)">
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table" id="users-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Tasks</th>
                        <th>Completed</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="users-tbody">
                <?php foreach ($users as $i => $u): ?>
                <tr id="urow-<?= $u['id'] ?>" data-username="<?= strtolower($u['username']) ?>" data-email="<?= strtolower($u['email']) ?>">
                    <td class="td-num"><?= $i + 1 ?></td>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar-sm <?= $u['role']==='admin' ? 'admin-avatar' : '' ?>">
                                <?= strtoupper(substr($u['username'],0,1)) ?>
                            </div>
                            <?= htmlspecialchars($u['username']) ?>
                        </div>
                    </td>
                    <td class="td-muted"><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <span class="role-badge role-<?= $u['role'] ?>" id="role-badge-<?= $u['id'] ?>">
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>
                    <td class="td-center"><?= (int)$u['task_count'] ?></td>
                    <td class="td-center"><?= (int)$u['done_count'] ?></td>
                    <td class="td-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <div class="row-actions">
                            <?php if ($u['role'] !== 'admin'): ?>
                            <button class="btn btn-sm btn-ghost"
                                    onclick="openResetPw(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')"
                                    title="Reset password" id="reset-btn-<?= $u['id'] ?>">
                                🔑
                            </button>
                            <button class="btn btn-sm btn-ghost"
                                    onclick="promoteUser(<?= $u['id'] ?>, 'admin')"
                                    title="Make Admin" id="promo-btn-<?= $u['id'] ?>">
                                ⬆ Admin
                            </button>
                            <button class="btn btn-sm btn-danger"
                                    onclick="deleteUser(<?= $u['id'] ?>)"
                                    title="Delete user" id="del-user-btn-<?= $u['id'] ?>">
                                🗑 Delete
                            </button>
                            <?php else: ?>
                            <span class="td-muted" style="font-size:0.8rem;">Super Admin</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

<!-- ══ Reset Password Modal ══════════════════════════════════ -->
<div class="modal-overlay" id="reset-modal">
    <div class="modal modal-sm glass-card">
        <div class="modal-header">
            <h3>&#128273; Reset Password</h3>
            <button class="modal-close" onclick="closeModal('reset-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom:12px">Set new password for <strong id="reset-username-label"></strong></p>
            <div class="form-group">
                <label class="form-label">New Password</label>
                <div class="input-wrapper">
                    <input type="password" id="new-pw-input" class="form-input modal-input" placeholder="Min. 6 characters">
                    <button type="button" class="toggle-pw" onclick="togglePassword('new-pw-input', this)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('reset-modal')">Cancel</button>
            <button class="btn btn-primary" id="reset-confirm-btn">Save Password</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast-container" id="toast-container"></div>

<script>
const CSRF = <?= json_encode($token) ?>;
</script>
<script src="assets/js/app.js"></script>
<script src="assets/js/admin.js"></script>
</body>
</html>
