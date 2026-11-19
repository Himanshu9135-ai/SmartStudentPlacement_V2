<?php
/**
 * Corporate Recruiter & Partner Company Registration
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
    } elseif (isCompany()) {
        header('Location: ' . BASE_URL . 'company/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . 'student/dashboard.php');
    }
    exit;
}

$errors = [];
$formData = [
    'company_name'   => '',
    'website'        => '',
    'location'       => '',
    'hr_name'        => '',
    'hr_email'       => '',
    'hr_phone'       => '',
    'industry'       => 'Information Technology & Software',
    'description'    => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = 'Security validation failed (Invalid CSRF token). Please try again.';
    }

    $formData['company_name'] = trim($_POST['company_name'] ?? '');
    $formData['website']      = trim($_POST['website'] ?? '');
    $formData['location']     = trim($_POST['location'] ?? '');
    $formData['hr_name']      = trim($_POST['hr_name'] ?? '');
    $formData['hr_email']     = trim($_POST['hr_email'] ?? '');
    $formData['hr_phone']     = trim($_POST['hr_phone'] ?? '');
    $formData['industry']     = trim($_POST['industry'] ?? 'Information Technology & Software');
    $formData['description']  = trim($_POST['description'] ?? '');
    $password                 = $_POST['password'] ?? '';
    $confirm_password         = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($formData['company_name'])) $errors[] = 'Company name is required.';
    if (empty($formData['location']))     $errors[] = 'Company location/headquarters is required.';
    if (empty($formData['hr_name']))      $errors[] = 'HR Contact person name is required.';
    if (empty($formData['hr_email']) || !filter_var($formData['hr_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid corporate HR email address is required.';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must be at least 8 characters long and contain at least one uppercase letter and one digit.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Password confirmation does not match.';
    }

    // Check if email already registered
    if (empty($errors)) {
        $stmtChk = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmtChk->execute([':email' => $formData['hr_email']]);
        if ($stmtChk->fetch()) {
            $errors[] = 'This corporate email address is already registered. Please sign in or reset your password.';
        }
    }

    // Insert company and user account in transaction
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // 1. Insert into companies table
            $stmtComp = $pdo->prepare("
                INSERT INTO companies (name, website, contact_email, contact_phone, location, description)
                VALUES (:name, :web, :email, :phone, :loc, :descr)
            ");
            $stmtComp->execute([
                ':name'  => $formData['company_name'],
                ':web'   => $formData['website'],
                ':email' => $formData['hr_email'],
                ':phone' => $formData['hr_phone'],
                ':loc'   => $formData['location'],
                ':descr' => $formData['description']
            ]);
            $companyId = $pdo->lastInsertId();

            // 2. Insert into users table as 'company' role
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmtUser = $pdo->prepare("
                INSERT INTO users (email, password, role, company_id, status)
                VALUES (:email, :pass, 'company', :cid, 'active')
            ");
            $stmtUser->execute([
                ':email' => $formData['hr_email'],
                ':pass'  => $hashedPassword,
                ':cid'   => $companyId
            ]);
            $userId = $pdo->lastInsertId();

            $pdo->commit();

            // Auto-login newly registered company
            session_regenerate_id(true);
            $_SESSION['user_id']      = $userId;
            $_SESSION['email']        = $formData['hr_email'];
            $_SESSION['role']         = 'company';
            $_SESSION['company_id']   = $companyId;
            $_SESSION['company_name'] = $formData['company_name'];
            $_SESSION['last_activity'] = time();

            setFlash('success', 'Corporate registration completed successfully! Welcome to ' . e($formData['company_name']) . ' Hiring Hub.');
            header('Location: ' . BASE_URL . 'company/dashboard.php');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Company registration error: " . $e->getMessage());
            $errors[] = 'Registration failed due to a system error. Please try again.';
        }
    }
}

$pageTitle = 'Partner Company Registration';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger shadow-sm mb-4">
                    <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please resolve the following:</h6>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card card-portal shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-success text-white text-center py-4">
                    <div class="display-6 mb-2"><i class="bi bi-building-add text-warning"></i></div>
                    <h3 class="mb-1 fw-bold">Recruiter & Company Registration</h3>
                    <p class="text-white-50 mb-0">Join as an official hiring partner to access talented polytechnic engineering candidates</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <form action="<?= BASE_URL ?>auth/register_company.php" method="POST" class="needs-validation">
                        <?= csrfField() ?>

                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                            <i class="bi bi-building me-2 text-success"></i>1. Corporate Organization Details
                        </h5>

                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label for="company_name" class="form-label fw-semibold">Company / Organization Name *</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" value="<?= e($formData['company_name']) ?>" placeholder="e.g. Infosys, Wipro, Tata Elxsi" required>
                            </div>
                            <div class="col-md-5">
                                <label for="location" class="form-label fw-semibold">Corporate Location / City *</label>
                                <input type="text" class="form-control" id="location" name="location" value="<?= e($formData['location']) ?>" placeholder="e.g. Pune, Bangalore, Delhi" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="website" class="form-label fw-semibold">Company Website URL</label>
                                <input type="url" class="form-control" id="website" name="website" value="<?= e($formData['website']) ?>" placeholder="https://company.com">
                            </div>
                            <div class="col-md-6">
                                <label for="industry" class="form-label fw-semibold">Industry Sector</label>
                                <select class="form-select" id="industry" name="industry">
                                    <option value="Information Technology & Software">Information Technology & Software</option>
                                    <option value="Manufacturing & Automobile">Manufacturing & Automobile</option>
                                    <option value="Civil & Infrastructure">Civil & Infrastructure</option>
                                    <option value="Electronics & Telecommunication">Electronics & Telecommunication</option>
                                    <option value="Energy & Power">Energy & Electrical Power</option>
                                    <option value="Consulting & Services">Consulting & Services</option>
                                    <option value="Other">Other Industry</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-semibold">Brief Company Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2" placeholder="Brief about your company services, core tech stack, and hiring domains..."><?= e($formData['description']) ?></textarea>
                        </div>

                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3 mt-4">
                            <i class="bi bi-person-badge-fill me-2 text-success"></i>2. HR & Recruiter Account Credentials
                        </h5>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="hr_name" class="form-label fw-semibold">HR Contact Person Name *</label>
                                <input type="text" class="form-control" id="hr_name" name="hr_name" value="<?= e($formData['hr_name']) ?>" placeholder="e.g. Priya Sharma (Campus Talent HR)" required>
                            </div>
                            <div class="col-md-6">
                                <label for="hr_phone" class="form-label fw-semibold">Official Contact Phone</label>
                                <input type="tel" class="form-control" id="hr_phone" name="hr_phone" value="<?= e($formData['hr_phone']) ?>" placeholder="e.g. +91 9876543210">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="hr_email" class="form-label fw-semibold">Official HR / Corporate Email *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="hr_email" name="hr_email" value="<?= e($formData['hr_email']) ?>" placeholder="recruiter@company.com" required>
                            </div>
                            <small class="text-muted">You will use this corporate email to sign in to your Recruiter Dashboard.</small>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="password" class="form-label fw-semibold">Portal Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Min. 8 chars, 1 uppercase, 1 digit" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label fw-semibold">Confirm Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-check2-circle"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-success btn-lg fw-bold shadow-sm py-3">
                                <i class="bi bi-building-check me-2"></i> Complete Company Registration
                            </button>
                        </div>

                        <div class="text-center pt-2">
                            <p class="text-muted small mb-0">Already registered as a hiring partner? 
                                <a href="<?= BASE_URL ?>auth/login.php?role=company" class="fw-bold text-success">Sign in here</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
