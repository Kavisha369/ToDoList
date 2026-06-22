<?php
// ============================================================
//  dashboard.php  —  User task/folder management
// ============================================================
require_once 'config.php';
requireLogin();

$user   = currentUser();
$userId = (int) $user['id'];
$db     = getDB();

// ── PHP helper: render a task row ─────────────────────────────
function renderTaskRow(array $task): string {
    $id       = (int) $task['id'];
    $title    = htmlspecialchars($task['title']);
    $status   = $task['status'];
    $priority = $task['priority'];
    $due      = $task['due_date'] ? htmlspecialchars($task['due_date']) : '';
    $pBadge   = match($priority) {
        'high'  => '<span class="badge badge-high">High</span>',
        'low'   => '<span class="badge badge-low">Low</span>',
        default => '<span class="badge badge-medium">Medium</span>',
    };
    $checked  = $status === 'completed' ? 'checked' : '';
    $rowClass = $status === 'completed' ? 'task-row completed-task' : 'task-row';
    $dueHtml  = $due ? "<span class='due-date'>&#128197; {$due}</span>" : '';

    $selPending   = $status === 'pending'     ? 'selected' : '';
    $selInProg    = $status === 'in_progress' ? 'selected' : '';
    $selCompleted = $status === 'completed'   ? 'selected' : '';

    return <<<HTML
<div class="{$rowClass}" id="task-row-{$id}" data-status="{$status}" data-title="{$title}">
    <label class="task-check-wrap">
        <input type="checkbox" class="task-cb" id="cb-{$id}" {$checked}
               onchange="cycleStatus({$id}, this)">
        <span class="custom-cb"></span>
    </label>
    <div class="task-body">
        <span class="task-title" id="ttitle-{$id}">{$title}</span>
        <div class="task-meta">
            {$pBadge}
            <span class="status-badge status-{$status}" id="sbadge-{$id}">{$status}</span>
            {$dueHtml}
        </div>
    </div>
    <div class="task-actions">
        <select class="status-select" onchange="changeStatus({$id}, this.value)" title="Set status">
            <option value="pending" {$selPending}>Pending</option>
            <option value="in_progress" {$selInProg}>In Progress</option>
            <option value="completed" {$selCompleted}>Completed</option>
        </select>
        <button class="icon-btn" onclick="renameItem({$id}, 'ttitle-{$id}')" title="Rename task">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
        </button>
        <button class="icon-btn icon-btn-danger" onclick="deleteItem({$id}, 'task')" title="Delete task">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
            </svg>
        </button>
    </div>
</div>
HTML;
}

// ── AJAX / POST handler ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!verifyCsrf($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        echo json_encode(['ok' => false, 'msg' => 'CSRF mismatch']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        // ── Create folder ──────────────────────────────────────
        case 'create_folder':
            $title = trim($_POST['title'] ?? '');
            if ($title === '') { echo json_encode(['ok' => false, 'msg' => 'Title required']); exit; }
            $stmt = $db->prepare('INSERT INTO tasks (user_id, title, is_folder) VALUES (?, ?, 1)');
            $stmt->execute([$userId, $title]);
            echo json_encode(['ok' => true, 'id' => (int)$db->lastInsertId(), 'title' => $title]);
            break;

        // ── Create task ────────────────────────────────────────
        case 'create_task':
            $title    = trim($_POST['title'] ?? '');
            $folderId = (int)($_POST['folder_id'] ?? 0);
            $desc     = trim($_POST['description'] ?? '');
            $priority = $_POST['priority'] ?? 'medium';
            $due      = trim($_POST['due_date'] ?? '');
            if (!in_array($priority, ['low', 'medium', 'high'])) $priority = 'medium';
            if ($title === '') { echo json_encode(['ok' => false, 'msg' => 'Title required']); exit; }
            if ($folderId > 0) {
                $chk = $db->prepare('SELECT id FROM tasks WHERE id=? AND user_id=? AND is_folder=1');
                $chk->execute([$folderId, $userId]);
                if (!$chk->fetch()) { echo json_encode(['ok' => false, 'msg' => 'Folder not found']); exit; }
            }
            $stmt = $db->prepare(
                'INSERT INTO tasks (user_id, title, description, priority, due_date, parent_folder_id, is_folder)
                 VALUES (?, ?, ?, ?, ?, ?, 0)'
            );
            $stmt->execute([$userId, $title, $desc, $priority, $due !== '' ? $due : null, $folderId > 0 ? $folderId : null]);
            echo json_encode(['ok' => true, 'id' => (int)$db->lastInsertId()]);
            break;

        // ── Update task status ─────────────────────────────────
        case 'update_status':
            $taskId = (int)($_POST['task_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            if (!in_array($status, ['pending', 'in_progress', 'completed'])) {
                echo json_encode(['ok' => false, 'msg' => 'Invalid status']); exit;
            }
            $stmt = $db->prepare('UPDATE tasks SET status=? WHERE id=? AND user_id=? AND is_folder=0');
            $stmt->execute([$status, $taskId, $userId]);
            echo json_encode(['ok' => true]);
            break;

        // ── Delete item ────────────────────────────────────────
        case 'delete_item':
            $itemId = (int)($_POST['item_id'] ?? 0);
            $stmt   = $db->prepare('DELETE FROM tasks WHERE id=? AND user_id=?');
            $stmt->execute([$itemId, $userId]);
            echo json_encode(['ok' => true]);
            break;

        // ── Rename item ────────────────────────────────────────
        case 'rename_item':
            $itemId = (int)($_POST['item_id'] ?? 0);
            $title  = trim($_POST['title'] ?? '');
            if ($title === '') { echo json_encode(['ok' => false, 'msg' => 'Title required']); exit; }
            $stmt = $db->prepare('UPDATE tasks SET title=? WHERE id=? AND user_id=?');
            $stmt->execute([$title, $itemId, $userId]);
            echo json_encode(['ok' => true]);
            break;

        default:
            echo json_encode(['ok' => false, 'msg' => 'Unknown action']);
    }
    exit;
}

// ── Fetch all folders + their tasks ───────────────────────────
$stmtF = $db->prepare('SELECT * FROM tasks WHERE user_id=? AND is_folder=1 ORDER BY created_at DESC');
$stmtF->execute([$userId]);
$folders = $stmtF->fetchAll();

$stmtT = $db->prepare('SELECT * FROM tasks WHERE user_id=? AND is_folder=0 ORDER BY created_at DESC');
$stmtT->execute([$userId]);
$allTasks = $stmtT->fetchAll();

// Group tasks by folder
$tasksByFolder = [];
$unfiledTasks  = [];
foreach ($allTasks as $t) {
    if ($t['parent_folder_id']) {
        $tasksByFolder[$t['parent_folder_id']][] = $t;
    } else {
        $unfiledTasks[] = $t;
    }
}

// Stats
$totalTasks     = count($allTasks);
$completedTasks = count(array_filter($allTasks, fn($t) => $t['status'] === 'completed'));
$pendingTasks   = count(array_filter($allTasks, fn($t) => $t['status'] === 'pending'));
$inProgTasks    = count(array_filter($allTasks, fn($t) => $t['status'] === 'in_progress'));
$pct            = $totalTasks > 0 ? round($completedTasks / $totalTasks * 100) : 0;

$token = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — TaskFlow</title>
    <meta name="description" content="Manage your tasks and folders in TaskFlow.">
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
                <path fill="url(#gs)" d="M9 11.5l2 2 4-4"/>
                <rect x="3" y="3" width="18" height="18" rx="4" stroke="url(#gs)" stroke-width="2" fill="none"/>
                <defs><linearGradient id="gs" x1="3" y1="3" x2="21" y2="21" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#7c3aed"/><stop offset="1" stop-color="#06b6d4"/>
                </linearGradient></defs>
            </svg>
        </div>
        <span class="brand-text">TaskFlow</span>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item active" id="nav-dashboard">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
            </svg>
            Dashboard
        </a>
        <a href="#" class="nav-item" onclick="openModal('folder-modal'); return false;" id="nav-folders">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
            </svg>
            New Folder
        </a>
        <a href="#" class="nav-item" onclick="openModal('task-modal'); return false;" id="nav-tasks">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            New Task
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-pill">
            <div class="user-avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
            <div class="user-info">
                <span class="user-name"><?= htmlspecialchars($user['username']) ?></span>
                <span class="user-role">User</span>
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

<!-- ══ Main Content ══════════════════════════════════════════ -->
<main class="main-content" id="main-content">

    <!-- Top bar -->
    <header class="topbar">
        <button class="sidebar-toggle" id="sidebar-toggle" onclick="toggleSidebar()" title="Toggle sidebar">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
        <h2 class="topbar-title">My Dashboard</h2>
        <div class="topbar-actions">
            <button class="btn btn-primary btn-sm" onclick="openModal('task-modal')" id="quick-add-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Add Task
            </button>
        </div>
    </header>

    <!-- Stats row -->
    <section class="stats-row">
        <div class="stat-card" id="stat-total">
            <div class="stat-icon stat-icon-blue">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="s-total"><?= $totalTasks ?></span>
                <span class="stat-label">Total Tasks</span>
            </div>
        </div>
        <div class="stat-card" id="stat-pending">
            <div class="stat-icon stat-icon-yellow">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="s-pending"><?= $pendingTasks ?></span>
                <span class="stat-label">Pending</span>
            </div>
        </div>
        <div class="stat-card" id="stat-inprog">
            <div class="stat-icon stat-icon-purple">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="s-inprog"><?= $inProgTasks ?></span>
                <span class="stat-label">In Progress</span>
            </div>
        </div>
        <div class="stat-card" id="stat-done">
            <div class="stat-icon stat-icon-green">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="s-done"><?= $completedTasks ?></span>
                <span class="stat-label">Completed</span>
            </div>
        </div>
    </section>

    <!-- Progress bar -->
    <div class="progress-section">
        <div class="progress-header">
            <span>Overall Progress</span>
            <span id="progress-pct"><?= $pct ?>%</span>
        </div>
        <div class="progress-track">
            <div class="progress-fill" id="progress-fill" style="width: <?= $pct ?>%"></div>
        </div>
    </div>

    <!-- Filter bar -->
    <div class="filter-bar">
        <div class="filter-tabs" role="tablist">
            <button class="ftab active" data-filter="all"         onclick="filterTasks(this,'all')"         role="tab">All</button>
            <button class="ftab"        data-filter="pending"     onclick="filterTasks(this,'pending')"     role="tab">Pending</button>
            <button class="ftab"        data-filter="in_progress" onclick="filterTasks(this,'in_progress')" role="tab">In Progress</button>
            <button class="ftab"        data-filter="completed"   onclick="filterTasks(this,'completed')"   role="tab">Completed</button>
        </div>
        <div class="search-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" id="search-input" class="search-input" placeholder="Search tasks…" oninput="searchTasks(this.value)">
        </div>
    </div>

    <!-- Folders + Tasks list -->
    <div class="folders-container" id="folders-container">

        <?php if (empty($folders) && empty($unfiledTasks)): ?>
        <div class="empty-state" id="empty-state">
            <div class="empty-icon">📋</div>
            <h3>No tasks yet!</h3>
            <p>Create a folder to organise your tasks, or add a quick task directly.</p>
            <button class="btn btn-primary" onclick="openModal('folder-modal')" id="create-first-folder">Create Folder</button>
        </div>
        <?php endif; ?>

        <?php foreach ($folders as $folder):
            $fTasks = $tasksByFolder[$folder['id']] ?? [];
            $fTotal = count($fTasks);
            $fDone  = count(array_filter($fTasks, fn($t) => $t['status'] === 'completed'));
            $fPct   = $fTotal > 0 ? round($fDone / $fTotal * 100) : 0;
        ?>
        <div class="folder-card" id="folder-<?= $folder['id'] ?>" data-id="<?= $folder['id'] ?>">
            <div class="folder-header" onclick="toggleFolder(<?= $folder['id'] ?>)">
                <div class="folder-left">
                    <span class="folder-chevron" id="chev-<?= $folder['id'] ?>">&#9654;</span>
                    <div class="folder-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                        </svg>
                    </div>
                    <span class="folder-name" id="fname-<?= $folder['id'] ?>"><?= htmlspecialchars($folder['title']) ?></span>
                    <span class="folder-count"><?= $fDone ?>/<?= $fTotal ?> done</span>
                </div>
                <div class="folder-right" onclick="event.stopPropagation()">
                    <div class="folder-mini-progress">
                        <div class="folder-mini-fill" style="width: <?= $fPct ?>%"></div>
                    </div>
                    <button class="icon-btn" onclick="addTaskToFolder(<?= $folder['id'] ?>, '<?= htmlspecialchars(addslashes($folder['title'])) ?>')" title="Add task">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                    </button>
                    <button class="icon-btn" onclick="renameItem(<?= $folder['id'] ?>, 'fname-<?= $folder['id'] ?>')" title="Rename folder">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                    <button class="icon-btn icon-btn-danger" onclick="deleteItem(<?= $folder['id'] ?>, 'folder')" title="Delete folder">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                            <path d="M10 11v6"/><path d="M14 11v6"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Tasks inside this folder -->
            <div class="folder-body collapsed" id="fbody-<?= $folder['id'] ?>">
                <div class="task-list" id="tlist-<?= $folder['id'] ?>">
                    <?php if (empty($fTasks)): ?>
                    <p class="no-tasks-msg" id="no-tasks-<?= $folder['id'] ?>">No tasks yet. Click + to add one.</p>
                    <?php else: ?>
                        <?php foreach ($fTasks as $task): ?>
                            <?= renderTaskRow($task) ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Unfiled tasks -->
        <?php if (!empty($unfiledTasks)): ?>
        <div class="folder-card" id="folder-unfiled">
            <div class="folder-header" onclick="toggleFolder('unfiled')">
                <div class="folder-left">
                    <span class="folder-chevron" id="chev-unfiled">&#9654;</span>
                    <div class="folder-icon-wrap" style="color: var(--text-muted)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/>
                            <line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/>
                            <line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                        </svg>
                    </div>
                    <span class="folder-name">Unfiled Tasks</span>
                    <span class="folder-count"><?= count($unfiledTasks) ?> tasks</span>
                </div>
            </div>
            <div class="folder-body collapsed" id="fbody-unfiled">
                <div class="task-list" id="tlist-unfiled">
                    <?php foreach ($unfiledTasks as $task): ?>
                        <?= renderTaskRow($task) ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /folders-container -->

</main>

<!-- ══ Modal: Create Folder ══════════════════════════════════ -->
<div class="modal-overlay" id="folder-modal">
    <div class="modal glass-card">
        <div class="modal-header">
            <h3>&#128193; New Folder</h3>
            <button class="modal-close" onclick="closeModal('folder-modal')">&times;</button>
        </div>
        <form onsubmit="submitFolder(event)">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Folder Name</label>
                    <input type="text" id="folder-title" class="form-input modal-input" placeholder="e.g. Work, Personal…" required autocomplete="off">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('folder-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="folder-submit-btn">Create Folder</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ Modal: Create Task ════════════════════════════════════ -->
<div class="modal-overlay" id="task-modal">
    <div class="modal glass-card">
        <div class="modal-header">
            <h3>&#9989; New Task</h3>
            <button class="modal-close" onclick="closeModal('task-modal')">&times;</button>
        </div>
        <form onsubmit="submitTask(event)">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Task Title <span class="req">*</span></label>
                    <input type="text" id="task-title" class="form-input modal-input" placeholder="What needs to be done?" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea id="task-desc" class="form-input modal-input" rows="2" placeholder="Optional details…"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group flex-1">
                        <label class="form-label">Folder</label>
                        <select id="task-folder" class="form-input modal-input">
                            <option value="">— None (Unfiled) —</option>
                            <?php foreach ($folders as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($folders)): ?>
                        <span class="field-hint" id="no-folder-hint">
                            You can save unfiled, or <a href="#" onclick="closeModal('task-modal'); openModal('folder-modal'); return false;">create a folder first</a>
                        </span>
                        <?php else: ?>
                        <span class="field-hint hidden" id="no-folder-hint">
                            You can save unfiled, or <a href="#" onclick="closeModal('task-modal'); openModal('folder-modal'); return false;">create a folder first</a>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group flex-1">
                        <label class="form-label">Priority</label>
                        <select id="task-priority" class="form-input modal-input">
                            <option value="low">&#9679; Low</option>
                            <option value="medium" selected>&#9679; Medium</option>
                            <option value="high">&#9679; High</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Due Date</label>
                    <input type="date" id="task-due" class="form-input modal-input">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('task-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="task-submit-btn">Add Task</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ Confirm Delete Dialog ════════════════════════════════ -->
<div class="modal-overlay" id="confirm-modal">
    <div class="modal modal-sm glass-card">
        <div class="modal-header">
            <h3>&#9888;&#65039; Confirm Delete</h3>
            <button class="modal-close" onclick="closeModal('confirm-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <p id="confirm-msg">Are you sure? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('confirm-modal')">Cancel</button>
            <button class="btn btn-danger" id="confirm-yes-btn">Delete</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast-container" id="toast-container"></div>

<!-- CSRF token available to JS -->
<script>
    const CSRF = <?= json_encode($token) ?>;
</script>

<!-- Load shared utilities FIRST, then page-specific logic -->
<script src="assets/js/app.js"></script>
<script src="assets/js/dashboard.js"></script>
</body>
</html>
