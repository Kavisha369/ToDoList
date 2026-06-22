/* ============================================================
   dashboard.js  —  Dashboard-specific logic
   ============================================================ */

'use strict';

/* ── Folder collapse/expand ──────────────────────────────── */
function toggleFolder(folderId) {
  const body  = document.getElementById('fbody-' + folderId);
  const chev  = document.getElementById('chev-' + folderId);
  if (!body) return;
  body.classList.toggle('collapsed');
  if (chev) chev.classList.toggle('open');
}

/* ── Pre-fill task modal with a specific folder ──────────── */
function addTaskToFolder(folderId, folderName) {
  document.getElementById('task-folder').value = folderId;
  openModal('task-modal');
}

/* ── Create Folder ───────────────────────────────────────── */
async function submitFolder(e) {
  e.preventDefault();
  const titleEl = document.getElementById('folder-title');
  const title   = titleEl.value.trim();
  if (!title) return;

  const btn = document.getElementById('folder-submit-btn');
  btn.disabled = true; btn.textContent = 'Creating…';

  const res = await apiPost({ action: 'create_folder', title });
  btn.disabled = false; btn.textContent = 'Create Folder';

  if (res.ok) {
    closeModal('folder-modal');
    titleEl.value = '';
    injectFolder(res.id, res.title);
    showToast(`Folder "${res.title}" created!`, 'success');
    updateEmptyState();
  } else {
    showToast(res.msg || 'Could not create folder', 'error');
  }
}

function injectFolder(id, title) {
  const container = document.getElementById('folders-container');
  const html = `
  <div class="folder-card" id="folder-${id}" data-id="${id}">
    <div class="folder-header" onclick="toggleFolder(${id})">
      <div class="folder-left">
        <span class="folder-chevron" id="chev-${id}">▶</span>
        <div class="folder-icon-wrap">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
          </svg>
        </div>
        <span class="folder-name" id="fname-${id}">${escHtml(title)}</span>
        <span class="folder-count">0/0 done</span>
      </div>
      <div class="folder-right" onclick="event.stopPropagation()">
        <div class="folder-mini-progress"><div class="folder-mini-fill" style="width:0%"></div></div>
        <button class="icon-btn" onclick="addTaskToFolder(${id}, '${escHtml(title)}')" title="Add task">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
        </button>
        <button class="icon-btn" onclick="renameItem(${id}, 'fname-${id}')" title="Rename">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
        </button>
        <button class="icon-btn icon-btn-danger" onclick="deleteItem(${id}, 'folder')" title="Delete folder">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
            <path d="M10 11v6"/><path d="M14 11v6"/>
          </svg>
        </button>
      </div>
    </div>
    <div class="folder-body collapsed" id="fbody-${id}">
      <div class="task-list" id="tlist-${id}">
        <p class="no-tasks-msg" id="no-tasks-${id}">No tasks yet. Click + to add one.</p>
      </div>
    </div>
  </div>`;

  // Also add to the task-modal folder select
  const sel = document.getElementById('task-folder');
  if (sel) {
    const opt = new Option(title, id);
    sel.appendChild(opt);
    sel.value = id; // auto-select the new folder
  }

  // Hide the "no folders yet" hint
  const hint = document.getElementById('no-folder-hint');
  if (hint) hint.classList.add('hidden');

  container.insertAdjacentHTML('afterbegin', html);
}

/* ── Create Task ─────────────────────────────────────────── */
async function submitTask(e) {
  e.preventDefault();
  const title    = document.getElementById('task-title').value.trim();
  const desc     = document.getElementById('task-desc').value.trim();
  const folderId = document.getElementById('task-folder').value;
  const priority = document.getElementById('task-priority').value;
  const dueDate  = document.getElementById('task-due').value;
  if (!title) return;

  const btn = document.getElementById('task-submit-btn');
  btn.disabled = true; btn.textContent = 'Adding…';

  const res = await apiPost({
    action: 'create_task', title, description: desc,
    folder_id: folderId, priority, due_date: dueDate
  });

  btn.disabled = false; btn.textContent = 'Add Task';

  if (res.ok) {
    closeModal('task-modal');
    // Reset form
    document.getElementById('task-title').value = '';
    document.getElementById('task-desc').value  = '';
    document.getElementById('task-due').value   = '';

    injectTask(res.id, title, desc, folderId, priority, dueDate);
    updateStats(1, 1, 0, 0); // total+1, pending+1
    showToast('Task added!', 'success');
  } else {
    showToast(res.msg || 'Could not create task', 'error');
  }
}

function injectTask(id, title, desc, folderId, priority, dueDate) {
  const listId    = folderId ? `tlist-${folderId}` : 'tlist-unfiled';
  const noTasksId = folderId ? `no-tasks-${folderId}` : null;

  // Remove "no tasks" placeholder
  if (noTasksId) {
    const placeholder = document.getElementById(noTasksId);
    if (placeholder) placeholder.remove();
  }

  // If unfiled section doesn't exist yet, create it
  let list = document.getElementById(listId);
  if (!list && !folderId) {
    const container = document.getElementById('folders-container');
    container.insertAdjacentHTML('beforeend', `
      <div class="folder-card" id="folder-unfiled">
        <div class="folder-header" onclick="toggleFolder('unfiled')">
          <div class="folder-left">
            <span class="folder-chevron" id="chev-unfiled">▶</span>
            <div class="folder-icon-wrap" style="color:var(--text-muted)">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
              </svg>
            </div>
            <span class="folder-name">Unfiled Tasks</span>
          </div>
        </div>
        <div class="folder-body collapsed" id="fbody-unfiled">
          <div class="task-list" id="tlist-unfiled"></div>
        </div>
      </div>`);
    list = document.getElementById('tlist-unfiled');
  }

  if (!list) return;

  const pBadge = { high: '<span class="badge badge-high">High</span>', low: '<span class="badge badge-low">Low</span>' }[priority] || '<span class="badge badge-medium">Medium</span>';
  const dueHtml = dueDate ? `<span class="due-date">📅 ${dueDate}</span>` : '';

  list.insertAdjacentHTML('beforeend', `
    <div class="task-row" id="task-row-${id}" data-status="pending" data-title="${escHtml(title)}">
      <label class="task-check-wrap">
        <input type="checkbox" class="task-cb" id="cb-${id}" onchange="cycleStatus(${id}, this)">
        <span class="custom-cb"></span>
      </label>
      <div class="task-body">
        <span class="task-title" id="ttitle-${id}">${escHtml(title)}</span>
        <div class="task-meta">
          ${pBadge}
          <span class="status-badge status-pending" id="sbadge-${id}">pending</span>
          ${dueHtml}
        </div>
      </div>
      <div class="task-actions">
        <select class="status-select" onchange="changeStatus(${id}, this.value)" title="Set status">
          <option value="pending" selected>Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
        </select>
        <button class="icon-btn" onclick="renameItem(${id}, 'ttitle-${id}')" title="Rename task">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
        </button>
        <button class="icon-btn icon-btn-danger" onclick="deleteItem(${id}, 'task')" title="Delete task">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <polyline points="3 6 5 6 21 6"/>
            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
          </svg>
        </button>
      </div>
    </div>`);

  updateEmptyState();
}

/* ── Status helpers ──────────────────────────────────────── */
async function cycleStatus(taskId, checkbox) {
  const newStatus = checkbox.checked ? 'completed' : 'pending';
  await changeStatus(taskId, newStatus);
}

async function changeStatus(taskId, newStatus) {
  const row = document.getElementById('task-row-' + taskId);
  const res = await apiPost({ action: 'update_status', task_id: taskId, status: newStatus });

  if (res.ok && row) {
    const oldStatus = row.dataset.status;
    row.dataset.status = newStatus;

    // Toggle line-through class
    row.classList.toggle('completed-task', newStatus === 'completed');

    // Sync checkbox
    const cb = document.getElementById('cb-' + taskId);
    if (cb) cb.checked = newStatus === 'completed';

    // Sync status badge
    const badge = document.getElementById('sbadge-' + taskId);
    if (badge) {
      badge.className = `status-badge status-${newStatus}`;
      badge.textContent = newStatus.replace('_', ' ');
    }

    // Update stats delta
    const doneΔ    = (newStatus === 'completed' ? 1 : 0) - (oldStatus === 'completed' ? 1 : 0);
    const pendingΔ = (newStatus === 'pending'   ? 1 : 0) - (oldStatus === 'pending'   ? 1 : 0);
    const inprogΔ  = (newStatus === 'in_progress'? 1:0)  - (oldStatus === 'in_progress'? 1:0);
    updateStats(0, pendingΔ, inprogΔ, doneΔ);

  } else if (!res.ok) {
    showToast(res.msg || 'Status update failed', 'error');
  }
}

/* ── Delete item ─────────────────────────────────────────── */
function deleteItem(id, type) {
  const msg = type === 'folder'
    ? 'Delete this folder and ALL tasks inside it? This cannot be undone.'
    : 'Delete this task? This cannot be undone.';

  document.getElementById('confirm-msg').textContent = msg;
  const btn = document.getElementById('confirm-yes-btn');
  btn.onclick = async () => {
    closeModal('confirm-modal');
    const res = await apiPost({ action: 'delete_item', item_id: id });
    if (res.ok) {
      if (type === 'folder') {
        document.getElementById('folder-' + id)?.remove();
        // Remove folder from select
        const opt = document.querySelector(`#task-folder option[value="${id}"]`);
        if (opt) opt.remove();
        // Re-show hint if no real folders remain
        const sel = document.getElementById('task-folder');
        const hint = document.getElementById('no-folder-hint');
        if (hint && sel && sel.options.length <= 1) hint.classList.remove('hidden');
      } else {
        const row = document.getElementById('task-row-' + id);
        if (row) {
          const wasDone    = row.dataset.status === 'completed';
          const wasPending = row.dataset.status === 'pending';
          const wasInprog  = row.dataset.status === 'in_progress';
          row.remove();
          updateStats(-1, wasPending ? -1 : 0, wasInprog ? -1 : 0, wasDone ? -1 : 0);
        }
      }
      updateEmptyState();
      showToast(type === 'folder' ? 'Folder deleted' : 'Task deleted', 'success');
    } else {
      showToast(res.msg || 'Delete failed', 'error');
    }
  };
  openModal('confirm-modal');
}

/* ── Stats counter ───────────────────────────────────────── */
function updateStats(totalΔ, pendingΔ, inprogΔ, doneΔ) {
  const inc = (id, delta) => {
    const el = document.getElementById(id);
    if (el) el.textContent = Math.max(0, parseInt(el.textContent || '0') + delta);
  };
  inc('s-total',   totalΔ);
  inc('s-pending', pendingΔ);
  inc('s-inprog',  inprogΔ);
  inc('s-done',    doneΔ);

  // Update progress bar
  const total = parseInt(document.getElementById('s-total')?.textContent || '0');
  const done  = parseInt(document.getElementById('s-done')?.textContent  || '0');
  const pct   = total > 0 ? Math.round(done / total * 100) : 0;
  const fill  = document.getElementById('progress-fill');
  const label = document.getElementById('progress-pct');
  if (fill)  fill.style.width = pct + '%';
  if (label) label.textContent = pct + '%';
}

/* ── Empty state ─────────────────────────────────────────── */
function updateEmptyState() {
  const empty = document.getElementById('empty-state');
  if (!empty) return;
  const hasTasks   = parseInt(document.getElementById('s-total')?.textContent || '0') > 0;
  const hasFolders = document.querySelectorAll('#folders-container .folder-card').length > 0;
  if (hasTasks || hasFolders) {
    empty.classList.add('hidden');
  } else {
    empty.classList.remove('hidden');
  }
}

/* ── Filter tasks ────────────────────────────────────────── */
function filterTasks(btn, filter) {
  document.querySelectorAll('.ftab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  document.querySelectorAll('.task-row').forEach(row => {
    const status = row.dataset.status || '';
    const visible = filter === 'all' || status === filter;
    row.classList.toggle('hidden', !visible);
  });
}

/* ── Search tasks ────────────────────────────────────────── */
function searchTasks(query) {
  const q = query.toLowerCase().trim();
  document.querySelectorAll('.task-row').forEach(row => {
    const title = (row.dataset.title || '').toLowerCase();
    row.classList.toggle('hidden', q !== '' && !title.includes(q));
  });
}

/* ── HTML escape ─────────────────────────────────────────── */
function escHtml(str) {
  return String(str)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
