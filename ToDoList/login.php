<?php
// ============================================================
//  login.php  —  Single login gate for users & admin
// ============================================================
require_once 'config.php';

// Already logged in → send to the right place
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin_panel.php' : 'dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Please fill in all fields.';
        } else {
            $db   = getDB();
            $stmt = $db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent fixation
                session_regenerate_id(true);

                $_SESSION[SESSION_KEY] = [
                    'id'       => $user['id'],
                    'username' => $user['username'],
                    'email'    => $user['email'],
                    'role'     => $user['role'],
                ];

                if ($user['role'] === 'admin') {
                    header('Location: admin_panel.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit;
            } else {
                $error = 'Invalid username or password.';
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
    <title>Login — TaskFlow</title>
    <meta name="description" content="Sign in to TaskFlow – your personal productivity hub.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">

    <!-- Animated background blobs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="auth-container">
        <div class="auth-card glass-card">

            <!-- Logo / Brand -->
            <div class="brand-header">
                <div class="brand-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24">
                        <path fill="url(#g1)" d="M9 11.5l2 2 4-4"/>
                        <rect x="3" y="3" width="18" height="18" rx="4" stroke="url(#g1)" stroke-width="2" fill="none"/>
                        <defs>
                            <linearGradient id="g1" x1="3" y1="3" x2="21" y2="21" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#7c3aed"/>
                                <stop offset="1" stop-color="#06b6d4"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <h1 class="brand-name">TaskFlow</h1>
                <p class="brand-tagline">Organize. Focus. Achieve.</p>
            </div>

            <!-- Tab switcher -->
            <div class="auth-tabs">
                <button class="tab-btn active" id="tab-login" onclick="switchTab('login')">Sign In</button>
                <button class="tab-btn" id="tab-register" onclick="window.location='register.php'">Register</button>
            </div>

            <!-- Flash / Error -->
            <?php if ($error): ?>
            <div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                </svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <?= htmlspecialchars($flash['msg']) ?>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="login.php" class="auth-form" id="login-form">
                <input type="hidden" name="csrf_token" value="<?= $token ?>">

                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                        <input type="text" id="username" name="username"
                               class="form-input" placeholder="Enter your username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               autocomplete="username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <input type="password" id="password" name="password"
                               class="form-input" placeholder="Enter your password"
                               autocomplete="current-password" required>
                        <button type="button" class="toggle-pw" onclick="togglePassword('password', this)" title="Show/hide password">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-full" id="login-btn">
                    <span class="btn-text">Sign In</span>
                    <span class="btn-spinner hidden" id="login-spinner"></span>
                </button>
            </form>

            <p class="auth-footer-link">
                Don't have an account? <a href="register.php">Create one free</a>
            </p>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        document.getElementById('login-form').addEventListener('submit', function() {
            document.getElementById('login-btn').disabled = true;
            document.querySelector('.btn-text').textContent = 'Signing in…';
        });
    </script>
</body>
</html>
