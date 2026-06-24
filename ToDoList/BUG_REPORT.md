# Bug Reports — TaskFlow To-Do List Application

**Project:** TaskFlow To-Do List Web Application  
**Tester:** Kavisha Goonewardena  
**Testing Period:** June 2026  
**Environment:** XAMPP | PHP 8.x | MySQL 8.x | Windows 11 | Google Chrome 125

---

## Severity Levels
| Level | Description |
|-------|-------------|
| 🔴 Critical | App crashes, data loss, security breach, login broken |
| 🟠 High | Core feature broken, major functionality unusable |
| 🟡 Medium | Feature partially works, workaround exists |
| 🟢 Low | Cosmetic issue, minor UX inconvenience |

## Status
| Status | Meaning |
|--------|---------|
| 🐛 Open | Bug confirmed, not yet fixed |
| ✅ Fixed | Bug resolved and verified |
| ❌ Won't Fix | Acknowledged but not in scope |

---

## BUG-001 — Admin Login Fails Despite Correct Credentials
| Field | Details |
|-------|---------|
| **Bug ID** | BUG-001 |
| **Severity** | 🔴 Critical |
| **Status** | ✅ Fixed |
| **Module** | Authentication |
| **Reported Date** | June 22, 2026 |
| **Fixed Date** | June 24, 2026 |

**Description:**  
Admin cannot log in with the documented credentials (`admin` / `admin123`) even though the user account exists in the database.

**Steps to Reproduce:**
1. Import `database/schema.sql`
2. Go to `http://localhost/ToDoList/login.php`
3. Enter Username: `admin`, Password: `admin123`
4. Click Sign In

**Expected Result:**  
Redirect to `admin_panel.php`

**Actual Result:**  
Error message: "Invalid username or password." Login fails.

**Root Cause:**  
The `schema.sql` seed file contained a placeholder bcrypt hash (`$2y$12$92I...`) that was the hash of the word `"password"` — not `"admin123"`. The hash and the documented password were mismatched.

**Fix Applied:**  
Ran a repair script (`fix_admin.php`) to regenerate the correct bcrypt hash for `admin123` and update the `users` table. Script was deleted after use.

**Evidence:**  
phpMyAdmin `users` table showing the admin row with an incorrect pre-seeded hash.

---

## BUG-002 — Dashboard Buttons Non-Functional (onclick Handlers Undefined)
| Field | Details |
|-------|---------|
| **Bug ID** | BUG-002 |
| **Severity** | 🔴 Critical |
| **Status** | ✅ Fixed |
| **Module** | Dashboard |
| **Reported Date** | June 22, 2026 |
| **Fixed Date** | June 22, 2026 |

**Description:**  
All interactive buttons on the dashboard (Add Task, Create Folder, Delete, Rename, Status change) were completely non-functional. Clicking them produced no response.

**Steps to Reproduce:**
1. Log in as any user
2. Click "Add Task" button in the topbar
3. Click the "+" icon on any folder
4. Click any delete or rename icon

**Expected Result:**  
Modals open, AJAX actions execute, toasts appear.

**Actual Result:**  
Nothing happens. Browser console shows: `Uncaught ReferenceError: openModal is not defined`

**Root Cause:**  
`app.js` (which defines `openModal`, `closeModal`, `showToast`, `apiPost`) was not included in `dashboard.php`. The `dashboard.js` file depended on these shared utilities but they were never loaded.

**Fix Applied:**  
Added `<script src="assets/js/app.js"></script>` before `<script src="assets/js/dashboard.js"></script>` in `dashboard.php`.

---

## BUG-003 — Dropdown Option Text Invisible When Open
| Field | Details |
|-------|---------|
| **Bug ID** | BUG-003 |
| **Severity** | 🟡 Medium |
| **Status** | ✅ Fixed |
| **Module** | Dashboard / UI |
| **Reported Date** | June 22, 2026 |
| **Fixed Date** | June 22, 2026 |

**Description:**  
When clicking the Priority or Status dropdown in the New Task modal or on task rows, the dropdown opens but the option text (Medium, High, etc.) is almost invisible — very faint text on a near-white background.

**Steps to Reproduce:**
1. Log in and open the "New Task" modal
2. Click the **Priority** dropdown
3. Observe the open dropdown list

**Expected Result:**  
Options clearly visible with dark background and white text matching the app's dark theme.

**Actual Result:**  
Native OS dropdown popup uses a white/light background, but the CSS-defined text colour (`var(--text-secondary)` — a light lavender) makes the options near-invisible.

**Root Cause:**  
CSS set `color: var(--text-secondary)` on `select` elements but did not set a `background-color` or `color` on `option` elements. When the native OS dropdown renders, it uses a white system background which clashes with the light text colour.

**Fix Applied:**  
Added explicit CSS styling for `option` elements:
```css
select.form-input option {
  background-color: #1e1e35;
  color: #f0f0ff;
}
```

---

## BUG-004 — Auth Page Input Fields Losing Icon Padding
| Field | Details |
|-------|---------|
| **Bug ID** | BUG-004 |
| **Severity** | 🟡 Medium |
| **Status** | ✅ Fixed |
| **Module** | Authentication / UI |
| **Reported Date** | June 22, 2026 |
| **Fixed Date** | June 22, 2026 |

**Description:**  
The username and password input fields on the login and registration pages had icons visually overlapping the input text. Text typed into the field would appear behind the icon.

**Steps to Reproduce:**
1. Go to `login.php` or `register.php`
2. Click into the Username or Password field
3. Type any text

**Expected Result:**  
Text appears to the right of the icon with proper padding.

**Actual Result:**  
Text starts from the far left, hidden behind the icon SVG.

**Root Cause:**  
A global CSS rule `.form-group .form-input { padding-left: 14px }` inside the modal section was overriding the icon-offset padding (`padding-left: 42px`) defined on `.form-input` for auth page inputs. The more-specific selector won, stripping the icon padding everywhere.

**Fix Applied:**  
Replaced the global override with a scoped class `.modal-input { padding-left: 14px !important }` applied only to modal form inputs.

---

## BUG-005 — "No Folders Yet" Hint Persists After Creating Folder
| Field | Details |
|-------|---------|
| **Bug ID** | BUG-005 |
| **Severity** | 🟡 Medium |
| **Status** | ✅ Fixed |
| **Module** | Dashboard |
| **Reported Date** | June 22, 2026 |
| **Fixed Date** | June 22, 2026 |

**Description:**  
After creating a folder via the sidebar, the Folder dropdown inside the "New Task" modal continued to show the hint: *"No folders yet — create one first"*, even though the newly created folder was now selectable in the dropdown.

**Steps to Reproduce:**
1. Log in (no folders exist yet)
2. Open "New Task" modal — note hint is shown
3. Close modal, click "New Folder", create a folder
4. Re-open "New Task" modal

**Expected Result:**  
Hint disappears; the new folder appears as a selected option.

**Actual Result:**  
Hint still shows. User thinks they cannot assign a folder.

**Root Cause:**  
The `injectFolder()` JavaScript function correctly appended a new `<option>` to the select, but did not hide the static PHP-rendered hint element. The hint had no `id` attribute, so JavaScript could not locate it.

**Fix Applied:**  
1. Added `id="no-folder-hint"` to the hint `<span>` in PHP
2. In `injectFolder()`, added: `document.getElementById('no-folder-hint')?.classList.add('hidden')`
3. In `deleteItem()`, added logic to re-show the hint if all folders are deleted

---

## BUG-006 — Task Status Stats Not Updating Correctly on Task Creation
| Field | Details |
|-------|---------|
| **Bug ID** | BUG-006 |
| **Severity** | 🟢 Low |
| **Status** | ✅ Fixed |
| **Module** | Dashboard / Stats |
| **Reported Date** | June 22, 2026 |
| **Fixed Date** | June 22, 2026 |

**Description:**  
When a new task is created, the "Total Tasks" counter increments correctly, but the "Pending" counter does not — even though all newly created tasks start with `pending` status.

**Steps to Reproduce:**
1. Note current Pending count (e.g., 2)
2. Create a new task with default status
3. Observe the stats row

**Expected Result:**  
Both Total and Pending counters increment by 1.

**Actual Result:**  
Total increments by 1; Pending stays the same.

**Root Cause:**  
In `dashboard.js`, the `submitTask()` function called `updateStats(1, 0, 0, 0)` — incrementing total by 1 but all other deltas were 0, ignoring the fact that new tasks default to `pending` status.

**Fix Applied:**  
Changed to `updateStats(1, 1, 0, 0)` — total +1, pending +1.

---

*Total Bugs Reported: 6 | Fixed: 6 | Open: 0*
