<?php
// ============================================================
//  index.php  —  Entry-point redirect
// ============================================================
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin_panel.php' : 'dashboard.php'));
} else {
    header('Location: login.php');
}
exit;
