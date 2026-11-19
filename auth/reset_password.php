<?php
/**
 * Reset Password Handler Page
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$errors = [];
$success = false;
$validToken = false;
$resetRow = null;

if (empty($token)) {
    setFlash('danger', 'Invalid or missing password reset token.');
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

// Check token validity in database
$stmtCheck = $pdo->prepare("SELECT * FROM password_resets WHERE token = :token AND expires_at >= NOW() ORDER BY id DESC LIMIT 1");
$stmtCheck->execute([':token' => $token]);
$resetRow = $stmtCheck->fetch();

if (!$resetRow) {
    $errors[] = 'This password reset link is invalid or has expired. Please request a new one.';
} else {
    $validToken = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    if (!validateCsrfToken()) {
        $errors[] = 'Security validation failed (Invalid CSRF token).';
    } else {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (strlen($new_password) < 8 || !preg_match('/[A-Z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
            $errors[] = 'Password must be at least 8 characters long and contain at least one uppercase letter and one digit.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New password confirmation does not match.';
        } else {
            try {
                $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                
                // Update user password
                $stmtUp = $pdo->prepare("UPDATE users SET password = :pass WHERE email = :email");
                $stmtUp->execute([':pass' => $hashed, ':email' => $resetRow['email']]);

                // Delete used tokens for this email
                $stmtDel = $pdo->prepare("DELETE FROM password_resets WHERE email = :email");
                $stmtDel->execute([':email' => $resetRow['email']]);

                $success = true;
            } catch (PDOException $e) {
                error_log("Password reset error: " . $e->getMessage());
                $errors[] = 'Failed to reset password due to a system error. Please try again.';
            }
        }
    }
}

$pageTitle = 'Set New Password';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger shadow-sm mb-4">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card card-portal shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-success text-white text-center py-4">
                    <div class="display-6 mb-2"><i class="bi bi-shield-check"></i></div>
                    <h4 class="mb-1 fw-bold">Set New Password</h4>
                    <small class="text-white-50">Create a strong, new password for your account</small>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php if ($success): ?>
                        <div class="text-center p-3">
                            <i class="bi bi-check-circle-fill text-success display-4 d-block mb-3"></i>
                            <h5 class="fw-bold">Password Successfully Updated!</h5>
                            <p class="text-muted small mb-4">Your account credentials have been updated securely. You can now login with your new password.</p>
                            <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-primary btn-lg fw-bold w-100 shadow-sm">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Proceed to Login
                            </a>
                        </div>
                    <?php elseif ($validToken): ?>
                        <p class="text-muted small mb-3">
                            Resetting password for: <strong class="text-dark"><?= e($resetRow['email']) ?></strong>
                        </p>

                        <form action="<?= BASE_URL ?>auth/reset_password.php?token=<?= urlencode($token) ?>" method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="token" value="<?= e($token) ?>">

                            <div class="mb-3">
                                <label for="new_password" class="form-label fw-semibold">New Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Min. 8 chars, 1 uppercase, 1 digit" required>
                                </div>
                                <small class="text-muted">Must contain uppercase letter and number.</small>
                            </div>

                            <div class="mb-4">
                                <label for="confirm_password" class="form-label fw-semibold">Confirm New Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-check2-circle"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat new password" required>
                                </div>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-success btn-lg fw-bold shadow-sm">
                                    <i class="bi bi-check-lg me-1"></i> Save New Password
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center">
                            <a href="<?= BASE_URL ?>auth/forgot_password.php" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-clockwise me-1"></i> Request a New Reset Link
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
