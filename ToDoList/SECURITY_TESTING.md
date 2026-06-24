# Security Test Cases — TaskFlow

**Project:** TaskFlow To-Do List Web Application  
**Framework:** OWASP Top 10 (2021) aligned  
**Tester:** Kavisha Goonewardena  
**Environment:** XAMPP | PHP 8.x | MySQL 8.x | Chrome DevTools  
**Last Updated:** June 2026

---

## Why Security Testing Matters
Security testing verifies that the application protects user data and resists malicious inputs. These tests simulate real-world attack vectors to ensure the app is safe before deployment.

---

## SEC-001 — SQL Injection via Login Form
| Field | Details |
|-------|---------|
| **Test ID** | SEC-001 |
| **OWASP Category** | A03: Injection |
| **Severity** | 🔴 Critical |
| **Status** | ✅ Protected |

**Objective:** Attempt to bypass authentication using SQL injection payloads.

**Test Inputs:**
| Payload (Username) | Password | Expected | Result |
|--------------------|----------|----------|--------|
| `admin'--` | anything | Login blocked | ✅ Blocked |
| `' OR '1'='1` | anything | Login blocked | ✅ Blocked |
| `'; DROP TABLE users;--` | anything | Login blocked | ✅ Blocked |
| `admin' OR 1=1--` | anything | Login blocked | ✅ Blocked |

**Why It's Protected:**  
All database queries use **PDO prepared statements** with parameterised inputs. User-supplied data is never concatenated into SQL strings.

```php
// Safe: parameterised query
$stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
$stmt->execute([$username]);
```

---

## SEC-002 — SQL Injection via Task/Folder Creation
| Field | Details |
|-------|---------|
| **Test ID** | SEC-002 |
| **OWASP Category** | A03: Injection |
| **Severity** | 🔴 Critical |
| **Status** | ✅ Protected |

**Test Inputs:**
| Field | Payload | Expected |
|-------|---------|----------|
| Task Title | `'; DROP TABLE tasks;--` | Saved as literal text, no DB damage |
| Folder Name | `" OR ""="` | Saved as literal text |
| Task Title | `<script>alert(1)</script>` | Stored safely, escaped on output |

**Verification:** Check phpMyAdmin after submission — the literal string is stored, tables intact.

---

## SEC-003 — Cross-Site Scripting (XSS) via Task Title
| Field | Details |
|-------|---------|
| **Test ID** | SEC-003 |
| **OWASP Category** | A03: Injection (XSS) |
| **Severity** | 🟠 High |
| **Status** | ✅ Protected |

**Objective:** Inject JavaScript that executes in another user's browser.

**Test Inputs:**
| Payload | Expected | Result |
|---------|----------|--------|
| `<script>alert('XSS')</script>` | Script not executed; rendered as text | ✅ Escaped |
| `<img src=x onerror=alert(1)>` | Image tag rendered as text | ✅ Escaped |
| `"><svg onload=alert(1)>` | Rendered as escaped HTML | ✅ Escaped |
| `javascript:alert(1)` | Rendered as text | ✅ Escaped |

**Why It's Protected:**  
All output uses `htmlspecialchars()` before rendering user-supplied content in HTML.

```php
echo htmlspecialchars($task['title']); // Converts < to &lt; etc.
```

---

## SEC-004 — Cross-Site Request Forgery (CSRF)
| Field | Details |
|-------|---------|
| **Test ID** | SEC-004 |
| **OWASP Category** | A01: Broken Access Control |
| **Severity** | 🟠 High |
| **Status** | ✅ Protected |

**Objective:** Submit a forged POST request without a valid CSRF token.

**Steps to Test:**
1. Open browser DevTools → Network tab
2. Perform any action (create task, delete folder)
3. Capture the POST request
4. Replay the request with a modified or missing `csrf_token`

**Expected Result:** Server returns `{"ok": false, "msg": "CSRF mismatch"}` and action is NOT performed.

**How to Reproduce the Attack:**
```bash
curl -X POST http://localhost/ToDoList/dashboard.php \
  -d "action=delete_item&item_id=1&csrf_token=faktoken123"
```

**Result:** ✅ `{"ok":false,"msg":"CSRF mismatch"}` — attack blocked.

---

## SEC-005 — Insecure Direct Object Reference (IDOR)
| Field | Details |
|-------|---------|
| **Test ID** | SEC-005 |
| **OWASP Category** | A01: Broken Access Control |
| **Severity** | 🔴 Critical |
| **Status** | ✅ Protected |

**Objective:** Attempt to delete or modify another user's tasks by guessing task IDs.

**Steps to Test:**
1. Log in as User A; create a task (note its ID via DevTools)
2. Log in as User B in a different browser
3. As User B, submit a DELETE request targeting User A's task ID:

```bash
curl -X POST http://localhost/ToDoList/dashboard.php \
  -d "action=delete_item&item_id=<user_a_task_id>&csrf_token=<user_b_token>"
```

**Expected Result:** Request fails silently — task not deleted (server checks `user_id = session user_id`).

**Why It's Protected:**  
Every query includes a `user_id` check from the session:
```php
$stmt = $db->prepare('DELETE FROM tasks WHERE id=? AND user_id=?');
$stmt->execute([$itemId, $userId]);  // $userId from session
```

---

## SEC-006 — Brute Force Login (No Rate Limiting)
| Field | Details |
|-------|---------|
| **Test ID** | SEC-006 |
| **OWASP Category** | A07: Identification and Authentication Failures |
| **Severity** | 🟡 Medium |
| **Status** | ⚠️ Not Protected (Known Limitation) |

**Objective:** Submit repeated login attempts with different passwords.

**Test:** Attempt 20 consecutive logins with wrong passwords.

**Expected Result (ideal):** Account locked or CAPTCHA shown after N attempts.

**Actual Result:** Login continues to accept attempts indefinitely — no rate limiting implemented.

**Recommendation:**  
For production, implement:
- Login attempt counter in database
- Account lockout after 5 failed attempts
- CAPTCHA (e.g., reCAPTCHA) after 3 failures
- IP-based throttling

> ⚠️ This is a **known limitation** of this version — acceptable for a local/educational project but must be addressed before public deployment.

---

## SEC-007 — Password Storage Verification
| Field | Details |
|-------|---------|
| **Test ID** | SEC-007 |
| **OWASP Category** | A02: Cryptographic Failures |
| **Severity** | 🔴 Critical |
| **Status** | ✅ Protected |

**Objective:** Verify passwords are not stored in plaintext.

**Steps to Test:**
1. Register a new user with password `mypassword123`
2. Open phpMyAdmin → `todolist_db` → `users` table
3. Find the newly created user row; inspect the `password` column

**Expected Result:** Column shows a bcrypt hash (starts with `$2y$12$...`), NOT the plaintext password.

**Actual Result:** ✅ Stored as: `$2y$12$[60-character hash]` — bcrypt, cost 12.

---

## SEC-008 — Session Fixation
| Field | Details |
|-------|---------|
| **Test ID** | SEC-008 |
| **OWASP Category** | A07: Identification and Authentication Failures |
| **Severity** | 🟠 High |
| **Status** | ✅ Protected |

**Objective:** Verify session ID changes after login (prevents session fixation attacks).

**Steps to Test:**
1. Open DevTools → Application → Cookies
2. Note the `PHPSESSID` value before login
3. Submit login credentials
4. Note the `PHPSESSID` value after login

**Expected Result:** Session ID is different after login.

**Actual Result:** ✅ Session ID regenerated — `session_regenerate_id(true)` called on successful login.

---

## SEC-009 — Privilege Escalation via URL Manipulation
| Field | Details |
|-------|---------|
| **Test ID** | SEC-009 |
| **OWASP Category** | A01: Broken Access Control |
| **Severity** | 🔴 Critical |
| **Status** | ✅ Protected |

**Objective:** Access admin-only pages as a regular user.

**Test Cases:**
| Attempt | Expected | Result |
|---------|----------|--------|
| Regular user visits `/admin_panel.php` | Redirect to dashboard | ✅ Blocked |
| Not-logged-in user visits `/dashboard.php` | Redirect to login | ✅ Blocked |
| Not-logged-in user visits `/admin_panel.php` | Redirect to login | ✅ Blocked |

**Why It's Protected:**  
Server-side guard functions called at the top of every protected page:
```php
requireLogin();   // on dashboard.php
requireAdmin();   // on admin_panel.php
```

---

## Security Summary

| Test | OWASP Category | Status |
|------|---------------|--------|
| SEC-001: SQL Injection (Login) | A03 Injection | ✅ Protected |
| SEC-002: SQL Injection (CRUD) | A03 Injection | ✅ Protected |
| SEC-003: XSS | A03 Injection | ✅ Protected |
| SEC-004: CSRF | A01 Broken Access Control | ✅ Protected |
| SEC-005: IDOR | A01 Broken Access Control | ✅ Protected |
| SEC-006: Brute Force | A07 Auth Failures | ⚠️ Known Gap |
| SEC-007: Password Storage | A02 Crypto Failures | ✅ Protected |
| SEC-008: Session Fixation | A07 Auth Failures | ✅ Protected |
| SEC-009: Privilege Escalation | A01 Broken Access Control | ✅ Protected |

**8/9 vectors protected — 1 known limitation documented.**
