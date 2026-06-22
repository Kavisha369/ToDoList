/* ============================================================
   admin.js  —  Admin panel interactions
   ============================================================ */

'use strict';

/* ── Delete user ─────────────────────────────────────────── */
function deleteUser(uid) {
  if (!confirm('⚠️ Permanently delete this user and all their tasks? This cannot be undone.')) return;
  apiPost({ action: 'delete_user', user_id: uid }).then(res => {
    if (res.ok) {
      document.getElementById('urow-' + uid)?.remove();
      showToast('User deleted successfully', 'success');
    } else {
      showToast(res.msg || 'Could not delete user', 'error');
    }
  });
}

/* ── Promote user to admin ───────────────────────────────── */
function promoteUser(uid, role) {
  if (!confirm(`Make this user an Admin? They will gain full admin privileges.`)) return;
  apiPost({ action: 'toggle_role', user_id: uid, role }).then(res => {
    if (res.ok) {
      const badge = document.getElementById('role-badge-' + uid);
      if (badge) {
        badge.className = `role-badge role-${role}`;
        badge.textContent = role.charAt(0).toUpperCase() + role.slice(1);
      }
      // Hide promote button since they are now admin
      document.getElementById('promo-btn-' + uid)?.remove();
      showToast('Role updated to Admin', 'success');
    } else {
      showToast(res.msg || 'Could not update role', 'error');
    }
  });
}

/* ── Reset password modal ────────────────────────────────── */
let _resetUid = null;

function openResetPw(uid, username) {
  _resetUid = uid;
  document.getElementById('reset-username-label').textContent = username;
  document.getElementById('new-pw-input').value = '';
  openModal('reset-modal');
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('reset-confirm-btn')?.addEventListener('click', async () => {
    const pw = document.getElementById('new-pw-input').value.trim();
    if (!pw) { showToast('Please enter a password', 'error'); return; }
    if (pw.length < 6) { showToast('Password too short (min 6 chars)', 'error'); return; }

    const res = await apiPost({ action: 'reset_password', user_id: _resetUid, new_password: pw });
    if (res.ok) {
      closeModal('reset-modal');
      showToast('Password reset successfully', 'success');
    } else {
      showToast(res.msg || 'Reset failed', 'error');
    }
  });
});

/* ── Filter users (live search) ──────────────────────────── */
function filterUsers(query) {
  const q = query.toLowerCase().trim();
  document.querySelectorAll('#users-tbody tr').forEach(row => {
    const uname = row.dataset.username || '';
    const email = row.dataset.email    || '';
    const match = q === '' || uname.includes(q) || email.includes(q);
    row.classList.toggle('hidden-row', !match);
  });
}
