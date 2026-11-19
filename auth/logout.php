<?php
/**
 * Logout Handler with CSRF Protection
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

// Verify CSRF token from POST or GET
$submittedToken = $_POST['csrf_token'] ?? $_GET['csrf'] ?? '';
if (!validateCsrfToken($submittedToken)) {
    setFlash('warning', 'Invalid logout request token.');
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// Start a fresh session just to hold the goodbye flash
session_start();
setFlash('info', 'You have been successfully logged out.');
header('Location: ' . BASE_URL . 'auth/login.php');
exit;
