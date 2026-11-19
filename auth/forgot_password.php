<?php
/**
 * Forgot Password Request Page with Live Email Notification Support
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$resetSuccess = false;
$resetLink = null;
$error = null;
$mailSent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $error = 'Security validation failed (Invalid CSRF token). Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid registered email address.';
        } else {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, email, role FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate secure random token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour validity

                // Store token in password_resets table
                $stmtToken = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires)");
                $stmtToken->execute([
                    ':email'   => $email,
                    ':token'   => $token,
                    ':expires' => $expires
                ]);

                $resetSuccess = true;
                $resetLink = BASE_URL . 'auth/reset_password.php?token=' . urlencode($token);

                // ==========================================
                // REAL EMAIL DISPATCH ENGINE (HTML Email)
                // ==========================================
                $subject = "Password Reset Request - Campus Placement Portal";
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $headers .= "From: Campus Placement Cell <no-reply@college.edu>\r\n";
                $headers .= "Reply-To: placement@college.edu\r\n";

                $htmlBody = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;'>
                    <div style='text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 15px;'>
                        <h2 style='color: #1e3a8a; margin: 0;'>Training & Placement Portal</h2>
                        <small style='color: #64748b;'>Campus Recruitment Management</small>
                    </div>
                    <div style='padding: 20px 0;'>
                        <p>Hello,</p>
                        <p>We received a request to reset your password for your account associated with <strong>{$email}</strong>.</p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='{$resetLink}' style='background-color: #2563eb; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>Reset Your Password</a>
                        </div>
                        <p style='color: #64748b; font-size: 13px;'>This link will expire in 60 minutes for security purposes. If you did not request this, please ignore this email.</p>
                    </div>
                    <div style='border-top: 1px solid #e2e8f0; padding-top: 15px; font-size: 12px; color: #94a3b8; text-align: center;'>
                        Government Polytechnic College - T&P Department &copy; " . date('Y') . "
                    </div>
                </div>";

                // Attempt sending email via PHP mail server
                @$mailSent = mail($email, $subject, $htmlBody, $headers);
            } else {
                // Show generic success for privacy / user enumeration prevention
                $resetSuccess = true;
            }
        }
    }
}

$pageTitle = 'Forgot Password';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger shadow-sm mb-4">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
                </div>
            <?php endif; ?>

            <div class="card card-portal shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white text-center py-4">
                    <div class="display-6 mb-2"><i class="bi bi-key-fill text-warning"></i></div>
                    <h4 class="mb-1 fw-bold">Reset Your Password</h4>
                    <small class="text-white-50">We will help you regain secure access to your account</small>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php if ($resetSuccess): ?>
                        <div class="text-center p-2">
                            <i class="bi bi-envelope-check-fill text-success display-4 d-block mb-3"></i>
                            <h5 class="fw-bold text-dark">Password Reset Email Dispatched!</h5>
                            <p class="text-muted small mb-4">
                                If an account exists with the provided email, a secure password recovery link has been sent to your inbox.
                            </p>

                            <?php if ($resetLink): ?>
                                <!-- Offline / Local Development Fallback Helper -->
                                <div class="p-3 bg-light rounded-3 text-start mb-4 border">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <small class="fw-bold text-dark"><i class="bi bi-laptop me-1 text-primary"></i> Localhost Offline Link:</small>
                                        <span class="badge bg-secondary" style="font-size: 10px;">Offline Dev</span>
                                    </div>
                                    <small class="text-muted d-block mb-2" style="font-size: 11px;">
                                        (Since XAMPP doesn't have an online SMTP server configured, you can click below directly):
                                    </small>
                                    <a href="<?= $resetLink ?>" class="btn btn-sm btn-primary w-100 fw-bold shadow-sm">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> Open Password Reset Page
                                    </a>
                                </div>
                            <?php endif; ?>

                            <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-outline-secondary btn-sm px-4">
                                <i class="bi bi-arrow-left me-1"></i> Return to Login
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-4">
                            Enter the email address associated with your account (Student, Admin, or Recruiter) and we will send you a password recovery link.
                        </p>

                        <form action="<?= BASE_URL ?>auth/forgot_password.php" method="POST">
                            <?= csrfField() ?>

                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold">Registered Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="name@college.edu" required autofocus>
                                </div>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm">
                                    <i class="bi bi-send-fill me-1"></i> Send Recovery Email
                                </button>
                            </div>

                            <div class="text-center pt-2">
                                <a href="<?= BASE_URL ?>auth/login.php" class="text-decoration-none small text-muted">
                                    <i class="bi bi-arrow-left me-1"></i> Back to Login
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
