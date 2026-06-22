<?php
// ============================================================
//  register.php  —  New user sign-up
// ============================================================
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin_panel.php' : 'dashboard.php'));
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request token. Please try again.';
    } else {
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        // Validation
        if ($username === '' || $email === '' || $password === '') {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $error = 'Username must be 3–50 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $error = 'Username may only contain letters, numbers, and underscores.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $password2) {
            $error = 'Passwords do not match.';
        } else {
            $db = getDB();

            // Check uniqueness
            $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'That username or email is already taken.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $ins  = $db->prepare(
                    'INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, "user")'
                );
                $ins->execute([$username, $email, $hash]);

                setFlash('success', 'Account created! Please sign in.');
                header('Location: login.php');
                exit;
            }
        }
    }
}

$token = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — TaskFlow</title>
    <meta name="description" content="Create your free TaskFlow account and start organizing tasks today.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="auth-container">
        <div class="auth-card glass-card">

            <div class="brand-header">
                <div class="brand-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24">
                        <path fill="url(#g2)" d="M9 11.5l2 2 4-4"/>
                        <rect x="3" y="3" width="18" height="18" rx="4" stroke="url(#g2)" stroke-width="2" fill="none"/>
                        <defs>
                            <linearGradient id="g2" x1="3" y1="3" x2="21" y2="21" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#7c3aed"/><stop offset="1" stop-color="#06b6d4"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <h1 class="brand-name">TaskFlow</h1>
                <p class="brand-tagline">Create your free account</p>
            </div>

            <div class="auth-tabs">
                <button class="tab-btn" onclick="window.location='login.php'">Sign In</button>
                <button class="tab-btn active" id="tab-register">Register</button>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                </svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="register.php" class="auth-form" id="reg-form">
                <input type="hidden" name="csrf_token" value="<?= $token ?>">

                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                        <input type="text" id="username" name="username" class="form-input"
                               placeholder="e.g. johndoe" autocomplete="username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </span>
                        <input type="email" id="email" name="email" class="form-input"
                               placeholder="you@example.com" autocomplete="email"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password <span class="hint">(min. 6 chars)</span></label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <input type="password" id="password" name="password" class="form-input"
                               placeholder="Create a password" autocomplete="new-password" required>
                        <button type="button" class="toggle-pw" onclick="togglePassword('password', this)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <!-- Strength bar -->
                    <div class="strength-bar-wrap">
                        <div class="strength-bar" id="strength-bar"></div>
                    </div>
                    <span class="strength-label" id="strength-label"></span>
                </div>

                <div class="form-group">
                    <label for="password2" class="form-label">Confirm Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <input type="password" id="password2" name="password2" class="form-input"
                               placeholder="Repeat your password" autocomplete="new-password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-full" id="reg-btn">
                    <span class="btn-text">Create Account</span>
                </button>
            </form>

            <p class="auth-footer-link">
                Already have an account? <a href="login.php">Sign in</a>
            </p>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        // Password strength meter
        document.getElementById('password').addEventListener('input', function () {
            const pw = this.value;
            const bar = document.getElementById('strength-bar');
            const lbl = document.getElementById('strength-label');
            let score = 0;
            if (pw.length >= 6)  score++;
            if (pw.length >= 10) score++;
            if (/[A-Z]/.test(pw)) score++;
            if (/[0-9]/.test(pw)) score++;
            if (/[^A-Za-z0-9]/.test(pw)) score++;
            const levels = ['', 'weak', 'fair', 'good', 'strong', 'very-strong'];
            const texts  = ['', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
            bar.className = 'strength-bar ' + (levels[score] || '');
            lbl.textContent = pw.length ? texts[score] : '';
        });
    </script>
</body>
</html>
