<?php
/**
 * Session Initialization & Security Hardening
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    // Secure cookie parameters
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if ($isHttps) {
        ini_set('session.cookie_secure', '1');
    }

    session_start();
} elseif (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session Idle Timeout (30 minutes)
if (isset($_SESSION['user_id'])) {
    $timeout = 1800; // 30 mins
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        session_start();
        setFlash('warning', 'Your session expired due to inactivity. Please log in again.');
        header('Location: ' . (defined('BASE_URL') ? BASE_URL : '/') . 'auth/login.php');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Generate CSRF Token
 */
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render CSRF Hidden Form Field
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate CSRF Token
 */
function validateCsrfToken($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($_SESSION['csrf_token']) || empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

/**
 * Guard state-changing requests with CSRF validation
 */
function requireCsrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCsrfToken()) {
            http_response_code(403);
            die("Error 403: Invalid or missing CSRF token. Please refresh the page and try again.");
        }
    }
}

/**
 * Emit modern security HTTP headers
 */
function sendSecurityHeaders() {
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}
sendSecurityHeaders();

/**
 * Set a one-time flash message
 * @param string $type success | danger | warning | info
 * @param string $message
 */
function setFlash($type, $message) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}

/**
 * Get and clear flash message
 * @return array|null
 */
function getFlash() {
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}
