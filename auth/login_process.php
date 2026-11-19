<?php
/**
 * Authentication Processing Handler
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

// CSRF Protection Check
if (!validateCsrfToken()) {
    setFlash('danger', 'Security validation failed (Invalid CSRF token). Please try logging in again.');
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

// Rate Limiting (Max 5 failed attempts in 15 minutes)
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_failed_login'] = time();
}

if ($_SESSION['login_attempts'] >= 5) {
    $lockoutTime = 900; // 15 minutes
    $timeSinceLast = time() - ($_SESSION['last_failed_login'] ?? 0);
    if ($timeSinceLast < $lockoutTime) {
        $minutesLeft = ceil(($lockoutTime - $timeSinceLast) / 60);
        setFlash('danger', "Too many failed login attempts. Please try again after {$minutesLeft} minute(s).");
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    } else {
        // Reset after lockout expires
        $_SESSION['login_attempts'] = 0;
    }
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    setFlash('danger', 'Please provide both email address and password.');
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

try {
    // Prepared statement lookup
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] !== 'active') {
            setFlash('danger', 'Your account has been deactivated. Please contact the administrator.');
            header('Location: ' . BASE_URL . 'auth/login.php');
            exit;
        }

        // Reset failed login counter on success
        $_SESSION['login_attempts'] = 0;
        unset($_SESSION['last_failed_login']);

        // Prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();

        // If student, attach student profile ID
        if ($user['role'] === 'student') {
            $stmtStudent = $pdo->prepare("SELECT id, first_name, last_name, enrollment_no FROM students WHERE user_id = :uid LIMIT 1");
            $stmtStudent->execute([':uid' => $user['id']]);
            $student = $stmtStudent->fetch();

            if ($student) {
                $_SESSION['student_id'] = $student['id'];
                $_SESSION['student_name'] = $student['first_name'] . ' ' . $student['last_name'];
                $_SESSION['enrollment_no'] = $student['enrollment_no'];
            }

            setFlash('success', 'Welcome back, ' . e($_SESSION['student_name'] ?? 'Student') . '!');
            header('Location: ' . BASE_URL . 'student/dashboard.php');
            exit;
        } elseif ($user['role'] === 'company') {
            $_SESSION['company_id'] = $user['company_id'];
            $compName = 'Company Recruiter';
            if (!empty($user['company_id'])) {
                $stmtC = $pdo->prepare("SELECT name FROM companies WHERE id = :cid LIMIT 1");
                $stmtC->execute([':cid' => $user['company_id']]);
                $cRow = $stmtC->fetch();
                if ($cRow) $compName = $cRow['name'];
            }
            $_SESSION['company_name'] = $compName;
            setFlash('success', 'Welcome to Recruiter Portal, ' . e($compName) . '!');
            header('Location: ' . BASE_URL . 'company/dashboard.php');
            exit;
        } else {
            setFlash('success', 'Welcome back to Placement Admin Console!');
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
            exit;
        }
    } else {
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['last_failed_login'] = time();
        $remaining = max(0, 5 - $_SESSION['login_attempts']);

        if ($remaining === 0) {
            setFlash('danger', 'Too many failed login attempts. Your account has been temporarily locked for 15 minutes.');
        } else {
            setFlash('danger', "Invalid email or password. ({$remaining} attempts remaining before temporary lockout)");
        }
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Login PDO error: " . $e->getMessage());
    setFlash('danger', 'System error during login. Please try again later.');
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}
