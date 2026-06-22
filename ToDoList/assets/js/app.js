/* ============================================================
   app.js  —  Shared utilities used on ALL pages
   ============================================================ */

'use strict';

// ── Toggle password visibility ─────────────────────────────
function togglePassword(inputId, btn) {
  const input = document.getElementById(inputId);
  if (!input) return;
  const isText = input.type === 'text';
  input.type = isText ? 'password' : 'text';
  btn.style.opacity = isText ? '0.5' : '1';
}

// ── Modal helpers ──────────────────────────────────────────
function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('open');
  document.addEventListener('keydown', handleEscKey);
}

function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('open');
}

function handleEscKey(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
  }
}

// Close modal when clicking the overlay backdrop
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function (e) {
      if (e.target === this) this.classList.remove('open');
    });
  });
});

// ── Toast notifications ────────────────────────────────────
function showToast(msg, type = 'info', duration = 3000) {
  const container = document.getElementById('toast-container');
  if (!container) return;

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;

  const icon = { success: '✅', error: '❌', info: 'ℹ️' }[type] || 'ℹ️';
  toast.innerHTML = `<span>${icon}</span><span>${msg}</span>`;
  container.appendChild(toast);

  setTimeout(() => {
    toast.classList.add('removing');
    toast.addEventListener('animationend', () => toast.remove());
  }, duration);
}

// ── Sidebar toggle ─────────────────────────────────────────
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const main    = document.getElementById('main-content');

  if (window.innerWidth <= 768) {
    sidebar.classList.toggle('mobile-open');
  } else {
    sidebar.classList.toggle('collapsed');
    main && main.classList.toggle('expanded');
  }
}

// ── Generic POST helper (returns JSON) ────────────────────
async function apiPost(data) {
  const form = new FormData();
  data.csrf_token = typeof CSRF !== 'undefined' ? CSRF : '';
  for (const [k, v] of Object.entries(data)) {
    form.append(k, v ?? '');
  }
  const res = await fetch(window.location.pathname, { method: 'POST', body: form });
  return res.json();
}

// ── Inline rename ─────────────────────────────────────────
async function renameItem(id, spanId) {
  const span = document.getElementById(spanId);
  if (!span) return;

  const oldTitle = span.textContent.trim();
  const newTitle = prompt('New name:', oldTitle);
  if (!newTitle || newTitle.trim() === oldTitle) return;

  const res = await apiPost({ action: 'rename_item', item_id: id, title: newTitle.trim() });
  if (res.ok) {
    span.textContent = newTitle.trim();
    showToast('Renamed successfully', 'success');
  } else {
    showToast(res.msg || 'Rename failed', 'error');
  }
}
