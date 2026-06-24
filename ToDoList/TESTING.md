# TaskFlow — Manual Test Cases

**Project:** TaskFlow To-Do List Web Application  
**Tester:** Kavisha Goonewardena  
**Environment:** XAMPP | PHP 8.x | MySQL 8.x | Google Chrome  
**Base URL:** `http://localhost/ToDoList/`  
**Last Updated:** June 2026

---

## Test Status Legend
| Symbol | Meaning |
|--------|---------|
| ✅ PASS | Feature works as expected |
| ❌ FAIL | Feature does not work as expected |
| ⚠️ PARTIAL | Feature works with minor issues |
| 🔲 NOT RUN | Test not yet executed |

---

## MODULE 1 — User Authentication

### TC-001 — Valid Admin Login
| Field | Details |
|-------|---------|
| **Test ID** | TC-001 |
| **Module** | Authentication |
| **Priority** | High |
| **Precondition** | Database imported, XAMPP running |
| **Steps** | 1. Go to `http://localhost/ToDoList/login.php` <br> 2. Enter Username: `admin` <br> 3. Enter Password: `admin123` <br> 4. Click **Sign In** |
| **Expected Result** | Redirected to `admin_panel.php` with admin dashboard visible |
| **Actual Result** | ✅ PASS — Redirected to admin panel correctly |

---

### TC-002 — Valid User Login
| Field | Details |
|-------|---------|
| **Test ID** | TC-002 |
| **Module** | Authentication |
| **Priority** | High |
| **Preconditions** | A registered user account exists |
| **Steps** | 1. Go to `login.php` <br> 2. Enter valid username and password <br> 3. Click **Sign In** |
| **Expected Result** | Redirected to `dashboard.php` |
| **Actual Result** | ✅ PASS |

---

### TC-003 — Login with Wrong Password
| Field | Details |
|-------|---------|
| **Test ID** | TC-003 |
| **Module** | Authentication |
| **Priority** | High |
| **Steps** | 1. Go to `login.php` <br> 2. Enter valid username, wrong password <br> 3. Click **Sign In** |
| **Expected Result** | Error message: "Invalid username or password." Page does not redirect |
| **Actual Result** | ✅ PASS |

---

### TC-004 — Login with Empty Fields
| Field | Details |
|-------|---------|
| **Test ID** | TC-004 |
| **Module** | Authentication |
| **Priority** | Medium |
| **Steps** | 1. Go to `login.php` <br> 2. Leave both fields blank <br> 3. Click **Sign In** |
| **Expected Result** | Browser validation prevents submission OR server returns error |
| **Actual Result** | ✅ PASS — HTML5 `required` attribute blocks submission |

---

### TC-005 — Login with Non-Existent Username
| Field | Details |
|-------|---------|
| **Test ID** | TC-005 |
| **Module** | Authentication |
| **Priority** | Medium |
| **Steps** | 1. Go to `login.php` <br> 2. Enter username: `ghost_user`, any password <br> 3. Click **Sign In** |
| **Expected Result** | Error: "Invalid username or password." |
| **Actual Result** | ✅ PASS |

---

### TC-006 — Direct Access to Dashboard Without Login
| Field | Details |
|-------|---------|
| **Test ID** | TC-006 |
| **Module** | Authentication / Access Control |
| **Priority** | Critical |
| **Steps** | 1. Open a new incognito window <br> 2. Navigate directly to `http://localhost/ToDoList/dashboard.php` |
| **Expected Result** | Redirected to `login.php` — access denied without session |
| **Actual Result** | ✅ PASS |

---

### TC-007 — Direct Access to Admin Panel as Regular User
| Field | Details |
|-------|---------|
| **Test ID** | TC-007 |
| **Module** | Access Control |
| **Priority** | Critical |
| **Steps** | 1. Log in as a regular user <br> 2. Navigate directly to `http://localhost/ToDoList/admin_panel.php` |
| **Expected Result** | Redirected to `dashboard.php` — admin page blocked |
| **Actual Result** | ✅ PASS |

---

### TC-008 — Logout Functionality
| Field | Details |
|-------|---------|
| **Test ID** | TC-008 |
| **Module** | Authentication |
| **Priority** | High |
| **Steps** | 1. Log in as any user <br> 2. Click the logout icon (→) in the sidebar footer <br> 3. After logout, press browser Back button |
| **Expected Result** | Redirected to `login.php`; back button does not restore session |
| **Actual Result** | ✅ PASS |

---

## MODULE 2 — User Registration

### TC-009 — Successful Registration
| Field | Details |
|-------|---------|
| **Test ID** | TC-009 |
| **Module** | Registration |
| **Priority** | High |
| **Steps** | 1. Go to `register.php` <br> 2. Enter unique username, valid email, password (min 6 chars) <br> 3. Confirm password matches <br> 4. Click **Create Account** |
| **Expected Result** | Success flash message; redirected to `login.php` |
| **Actual Result** | ✅ PASS |

---

### TC-010 — Registration with Duplicate Username
| Field | Details |
|-------|---------|
| **Test ID** | TC-010 |
| **Module** | Registration |
| **Priority** | High |
| **Steps** | 1. Go to `register.php` <br> 2. Enter a username that already exists (e.g., `admin`) <br> 3. Click **Create Account** |
| **Expected Result** | Error: "That username or email is already taken." |
| **Actual Result** | ✅ PASS |

---

### TC-011 — Registration with Mismatched Passwords
| Field | Details |
|-------|---------|
| **Test ID** | TC-011 |
| **Module** | Registration |
| **Priority** | Medium |
| **Steps** | 1. Enter valid username and email <br> 2. Enter Password: `test123`, Confirm: `test456` <br> 3. Click **Create Account** |
| **Expected Result** | Error: "Passwords do not match." |
| **Actual Result** | ✅ PASS |

---

### TC-012 — Registration with Short Password
| Field | Details |
|-------|---------|
| **Test ID** | TC-012 |
| **Module** | Registration |
| **Priority** | Medium |
| **Steps** | 1. Enter all valid fields <br> 2. Set password to `abc` (3 characters) <br> 3. Submit |
| **Expected Result** | Error: "Password must be at least 6 characters." |
| **Actual Result** | ✅ PASS |

---

### TC-013 — Registration with Invalid Email Format
| Field | Details |
|-------|---------|
| **Test ID** | TC-013 |
| **Module** | Registration |
| **Priority** | Medium |
| **Steps** | 1. Enter email: `notanemail` <br> 2. Submit the form |
| **Expected Result** | Error: "Please enter a valid email address." |
| **Actual Result** | ✅ PASS |

---

### TC-014 — Password Strength Meter
| Field | Details |
|-------|---------|
| **Test ID** | TC-014 |
| **Module** | Registration / UI |
| **Priority** | Low |
| **Steps** | 1. Go to `register.php` <br> 2. Type progressively complex passwords in the password field |
| **Expected Result** | Strength bar changes through: Weak → Fair → Good → Strong → Very Strong |
| **Actual Result** | ✅ PASS |

---

## MODULE 3 — Dashboard / Task Management

### TC-015 — Create a New Folder
| Field | Details |
|-------|---------|
| **Test ID** | TC-015 |
| **Module** | Dashboard |
| **Priority** | High |
| **Steps** | 1. Log in as user <br> 2. Click **New Folder** in sidebar <br> 3. Enter folder name: `Work` <br> 4. Click **Create Folder** |
| **Expected Result** | Modal closes; folder card `Work` appears on dashboard without page reload |
| **Actual Result** | ✅ PASS |

---

### TC-016 — Create a Task Inside a Folder
| Field | Details |
|-------|---------|
| **Test ID** | TC-016 |
| **Module** | Dashboard |
| **Priority** | High |
| **Steps** | 1. Create a folder first <br> 2. Click **Add Task** or the `+` button on the folder <br> 3. Enter title: `Write report`, select the folder, set priority: High <br> 4. Click **Add Task** |
| **Expected Result** | Task appears inside the folder; total task counter increments |
| **Actual Result** | ✅ PASS |

---

### TC-017 — Create a Task Without a Folder (Unfiled)
| Field | Details |
|-------|---------|
| **Test ID** | TC-017 |
| **Module** | Dashboard |
| **Priority** | Medium |
| **Steps** | 1. Click **Add Task** <br> 2. Enter title, leave Folder as "None (Unfiled)" <br> 3. Submit |
| **Expected Result** | Task appears in "Unfiled Tasks" section |
| **Actual Result** | ✅ PASS |

---

### TC-018 — Change Task Status via Dropdown
| Field | Details |
|-------|---------|
| **Test ID** | TC-018 |
| **Module** | Dashboard |
| **Priority** | High |
| **Steps** | 1. Create a task <br> 2. Use the status dropdown on the task row <br> 3. Change from `Pending` → `In Progress` |
| **Expected Result** | Status badge updates; In Progress counter increments; Pending counter decrements |
| **Actual Result** | ✅ PASS |

---

### TC-019 — Mark Task as Completed via Checkbox
| Field | Details |
|-------|---------|
| **Test ID** | TC-019 |
| **Module** | Dashboard |
| **Priority** | High |
| **Steps** | 1. Create a task <br> 2. Click the checkbox on the left of the task |
| **Expected Result** | Task title gets strikethrough; Completed counter increments; progress bar updates |
| **Actual Result** | ✅ PASS |

---

### TC-020 — Delete a Task
| Field | Details |
|-------|---------|
| **Test ID** | TC-020 |
| **Module** | Dashboard |
| **Priority** | High |
| **Steps** | 1. Click the 🗑 delete icon on a task <br> 2. Confirm deletion in the dialog |
| **Expected Result** | Task removed from UI; total counter decrements; no page reload |
| **Actual Result** | ✅ PASS |

---

### TC-021 — Delete a Folder (Cascade)
| Field | Details |
|-------|---------|
| **Test ID** | TC-021 |
| **Module** | Dashboard |
| **Priority** | High |
| **Steps** | 1. Create a folder with 2 tasks inside <br> 2. Delete the folder <br> 3. Confirm in dialog |
| **Expected Result** | Folder and all its tasks removed; counters update correctly |
| **Actual Result** | ✅ PASS |

---

### TC-022 — Rename a Folder
| Field | Details |
|-------|---------|
| **Test ID** | TC-022 |
| **Module** | Dashboard |
| **Priority** | Medium |
| **Steps** | 1. Click the ✏️ rename icon on a folder <br> 2. Enter new name in prompt <br> 3. Confirm |
| **Expected Result** | Folder name updates inline without page reload |
| **Actual Result** | ✅ PASS |

---

### TC-023 — Filter Tasks by Status
| Field | Details |
|-------|---------|
| **Test ID** | TC-023 |
| **Module** | Dashboard |
| **Priority** | Medium |
| **Steps** | 1. Create tasks with different statuses <br> 2. Click **Pending** filter tab |
| **Expected Result** | Only pending tasks visible; completed and in-progress hidden |
| **Actual Result** | ✅ PASS |

---

### TC-024 — Search Tasks
| Field | Details |
|-------|---------|
| **Test ID** | TC-024 |
| **Module** | Dashboard |
| **Priority** | Medium |
| **Steps** | 1. Create multiple tasks with different names <br> 2. Type a keyword in the search box |
| **Expected Result** | Only matching tasks shown; others hidden in real time |
| **Actual Result** | ✅ PASS |

---

### TC-025 — Progress Bar Accuracy
| Field | Details |
|-------|---------|
| **Test ID** | TC-025 |
| **Module** | Dashboard / UI |
| **Priority** | Low |
| **Steps** | 1. Create 4 tasks <br> 2. Mark 2 as completed |
| **Expected Result** | Progress bar shows 50%; percentage label shows "50%" |
| **Actual Result** | ✅ PASS |

---

### TC-026 — Expand / Collapse Folder
| Field | Details |
|-------|---------|
| **Test ID** | TC-026 |
| **Module** | Dashboard / UI |
| **Priority** | Low |
| **Steps** | 1. Click a folder header to expand <br> 2. Click again to collapse |
| **Expected Result** | Folder body smoothly animates open/closed; chevron rotates |
| **Actual Result** | ✅ PASS |

---

## MODULE 4 — Admin Panel

### TC-027 — Admin Views All Users
| Field | Details |
|-------|---------|
| **Test ID** | TC-027 |
| **Module** | Admin Panel |
| **Priority** | High |
| **Steps** | 1. Log in as admin <br> 2. View the users table |
| **Expected Result** | All registered users listed with username, email, role, task count, join date |
| **Actual Result** | ✅ PASS |

---

### TC-028 — Admin Deletes a User
| Field | Details |
|-------|---------|
| **Test ID** | TC-028 |
| **Module** | Admin Panel |
| **Priority** | High |
| **Steps** | 1. Log in as admin <br> 2. Click 🗑 Delete next to a user <br> 3. Confirm in browser dialog |
| **Expected Result** | User row removed from table; user's tasks deleted from database |
| **Actual Result** | ✅ PASS |

---

### TC-029 — Admin Cannot Delete Own Account
| Field | Details |
|-------|---------|
| **Test ID** | TC-029 |
| **Module** | Admin Panel |
| **Priority** | Critical |
| **Steps** | 1. Log in as admin <br> 2. Observe the admin row in the users table |
| **Expected Result** | No delete button shown for the admin row; "Super Admin" label shown instead |
| **Actual Result** | ✅ PASS |

---

### TC-030 — Admin Resets a User Password
| Field | Details |
|-------|---------|
| **Test ID** | TC-030 |
| **Module** | Admin Panel |
| **Priority** | High |
| **Steps** | 1. Click 🔑 reset icon next to a user <br> 2. Enter new password (min 6 chars) <br> 3. Click **Save Password** |
| **Expected Result** | Success toast shown; user can now log in with new password |
| **Actual Result** | ✅ PASS |

---

### TC-031 — Admin Promotes User to Admin Role
| Field | Details |
|-------|---------|
| **Test ID** | TC-031 |
| **Module** | Admin Panel |
| **Priority** | Medium |
| **Steps** | 1. Click **⬆ Admin** button next to a user <br> 2. Confirm browser dialog |
| **Expected Result** | Role badge updates to "Admin"; promote button disappears for that row |
| **Actual Result** | ✅ PASS |

---

### TC-032 — Admin Panel User Search
| Field | Details |
|-------|---------|
| **Test ID** | TC-032 |
| **Module** | Admin Panel |
| **Priority** | Medium |
| **Steps** | 1. Type a username in the search bar on admin panel |
| **Expected Result** | Table filters live to show only matching users |
| **Actual Result** | ✅ PASS |

---

## MODULE 5 — UI / Responsiveness

### TC-033 — Sidebar Toggle
| Field | Details |
|-------|---------|
| **Test ID** | TC-033 |
| **Module** | UI |
| **Priority** | Low |
| **Steps** | 1. Click the ☰ hamburger icon in the topbar |
| **Expected Result** | Sidebar slides in/out; main content area expands/contracts |
| **Actual Result** | ✅ PASS |

---

### TC-034 — Modal Close on Backdrop Click
| Field | Details |
|-------|---------|
| **Test ID** | TC-034 |
| **Module** | UI |
| **Priority** | Low |
| **Steps** | 1. Open any modal (e.g., New Task) <br> 2. Click outside the modal box on the dark overlay |
| **Expected Result** | Modal closes without submitting the form |
| **Actual Result** | ✅ PASS |

---

### TC-035 — Modal Close on ESC Key
| Field | Details |
|-------|---------|
| **Test ID** | TC-035 |
| **Module** | UI |
| **Priority** | Low |
| **Steps** | 1. Open any modal <br> 2. Press the `Esc` key |
| **Expected Result** | Modal closes |
| **Actual Result** | ✅ PASS |

---

*Total Test Cases: 35 | Passed: 35 | Failed: 0 | Not Run: 0*
