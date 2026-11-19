<?php
/**
 * Student Self-Registration Page
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . (isAdmin() ? 'admin/dashboard.php' : 'student/dashboard.php'));
    exit;
}

$errors = [];
$formData = [
    'enrollment_no' => '',
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'department' => 'Computer Engineering',
    'semester' => 5,
    'gender' => 'Male'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = 'Security validation failed (Invalid CSRF token). Please reload and try again.';
    }

    $formData['enrollment_no'] = trim($_POST['enrollment_no'] ?? '');
    $formData['first_name']    = trim($_POST['first_name'] ?? '');
    $formData['last_name']     = trim($_POST['last_name'] ?? '');
    $formData['email']         = trim($_POST['email'] ?? '');
    $formData['phone']         = trim($_POST['phone'] ?? '');
    
    // Whitelist check
    $allowedDepts = ['Computer Engineering', 'Information Technology', 'Electronics & Communication', 'Mechanical Engineering', 'Civil Engineering', 'Electrical Engineering'];
    $postedDept   = trim($_POST['department'] ?? 'Computer Engineering');
    $formData['department'] = in_array($postedDept, $allowedDepts, true) ? $postedDept : 'Computer Engineering';
    
    $formData['semester']      = max(1, min(8, (int)($_POST['semester'] ?? 5)));
    $postedGender              = trim($_POST['gender'] ?? 'Male');
    $formData['gender']        = in_array($postedGender, ['Male', 'Female', 'Other'], true) ? $postedGender : 'Male';
    $password                  = $_POST['password'] ?? '';
    $confirm_password          = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($formData['enrollment_no'])) $errors[] = 'Enrollment number is required.';
    if (empty($formData['first_name']))    $errors[] = 'First name is required.';
    if (empty($formData['last_name']))     $errors[] = 'Last name is required.';
    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must be at least 8 characters long and include at least one uppercase letter and one digit.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Password confirmation does not match.';
    }

    // Duplicate Check
    if (empty($errors)) {
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmtCheck->execute([':email' => $formData['email']]);
        if ($stmtCheck->fetch()) {
            $errors[] = 'This email address is already registered.';
        }

        $stmtCheckRoll = $pdo->prepare("SELECT id FROM students WHERE enrollment_no = :roll LIMIT 1");
        $stmtCheckRoll->execute([':roll' => $formData['enrollment_no']]);
        if ($stmtCheckRoll->fetch()) {
            $errors[] = 'This enrollment number is already registered.';
        }
    }

    // Execute Registration
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // 1. Insert User Credentials
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmtUser = $pdo->prepare("INSERT INTO users (email, password, role, status) VALUES (:email, :pass, 'student', 'active')");
            $stmtUser->execute([
                ':email' => $formData['email'],
                ':pass'  => $hashedPassword
            ]);
            $userId = $pdo->lastInsertId();

            // 2. Insert Student Profile
            $stmtStudent = $pdo->prepare("
                INSERT INTO students (user_id, enrollment_no, first_name, last_name, phone, department, semester, gender)
                VALUES (:uid, :roll, :fname, :lname, :phone, :dept, :sem, :gender)
            ");
            $stmtStudent->execute([
                ':uid'    => $userId,
                ':roll'   => $formData['enrollment_no'],
                ':fname'  => $formData['first_name'],
                ':lname'  => $formData['last_name'],
                ':phone'  => $formData['phone'],
                ':dept'   => $formData['department'],
                ':sem'    => $formData['semester'],
                ':gender' => $formData['gender']
            ]);
            $studentId = $pdo->lastInsertId();

            // 3. Insert Initial Academic Record
            $stmtAcad = $pdo->prepare("INSERT INTO academic_records (student_id, cgpa, attendance_percentage) VALUES (:sid, 0.00, 0.00)");
            $stmtAcad->execute([':sid' => $studentId]);

            $pdo->commit();

            setFlash('success', 'Registration successful! You can now log in with your credentials.');
            header('Location: ' . BASE_URL . 'auth/login.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Registration error: " . $e->getMessage());
            $errors[] = 'A system error occurred during registration. Please try again later.';
        }
    }
}

$pageTitle = 'Student Registration';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            
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

            <div class="card card-portal shadow-lg">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Student Registration</h4>
                    <small class="text-white-50">Create your campus placement portal profile</small>
                </div>
                <div class="card-body p-4">
                    <form action="<?= BASE_URL ?>auth/register.php" method="POST" class="needs-validation">
                        <?= csrfField() ?>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="enrollment_no" class="form-label fw-semibold">Enrollment / Roll No *</label>
                                <input type="text" class="form-control" id="enrollment_no" name="enrollment_no" value="<?= e($formData['enrollment_no']) ?>" placeholder="e.g. CSE2024045" required>
                            </div>
                            <div class="col-md-6">
                                <label for="department" class="form-label fw-semibold">Department / Branch *</label>
                                <select class="form-select" id="department" name="department" required>
                                    <option value="Computer Engineering" <?= $formData['department'] === 'Computer Engineering' ? 'selected' : '' ?>>Computer Engineering</option>
                                    <option value="Information Technology" <?= $formData['department'] === 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                                    <option value="Electronics & Telecommunication" <?= $formData['department'] === 'Electronics & Telecommunication' ? 'selected' : '' ?>>Electronics & Telecommunication</option>
                                    <option value="Mechanical Engineering" <?= $formData['department'] === 'Mechanical Engineering' ? 'selected' : '' ?>>Mechanical Engineering</option>
                                    <option value="Civil Engineering" <?= $formData['department'] === 'Civil Engineering' ? 'selected' : '' ?>>Civil Engineering</option>
                                    <option value="Electrical Engineering" <?= $formData['department'] === 'Electrical Engineering' ? 'selected' : '' ?>>Electrical Engineering</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label fw-semibold">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?= e($formData['first_name']) ?>" placeholder="First name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label fw-semibold">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?= e($formData['last_name']) ?>" placeholder="Last name" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold">College / Personal Email *</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= e($formData['email']) ?>" placeholder="student@college.edu" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label fw-semibold">Mobile Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($formData['phone']) ?>" placeholder="10-digit mobile number">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="semester" class="form-label fw-semibold">Current Semester *</label>
                                <select class="form-select" id="semester" name="semester" required>
                                    <option value="1" <?= $formData['semester'] == 1 ? 'selected' : '' ?>>Semester 1</option>
                                    <option value="2" <?= $formData['semester'] == 2 ? 'selected' : '' ?>>Semester 2</option>
                                    <option value="3" <?= $formData['semester'] == 3 ? 'selected' : '' ?>>Semester 3</option>
                                    <option value="4" <?= $formData['semester'] == 4 ? 'selected' : '' ?>>Semester 4</option>
                                    <option value="5" <?= $formData['semester'] == 5 ? 'selected' : '' ?>>Semester 5 (Final Year)</option>
                                    <option value="6" <?= $formData['semester'] == 6 ? 'selected' : '' ?>>Semester 6 (Final Year)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="gender" class="form-label fw-semibold">Gender</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="Male" <?= $formData['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $formData['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= $formData['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="password" class="form-label fw-semibold">Password * (Min 6 chars)</label>
                                <input type="password" class="form-control" id="password" name="password" minlength="6" placeholder="••••••••" required>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label fw-semibold">Confirm Password *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" placeholder="••••••••" required>
                            </div>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold">
                                <i class="bi bi-check2-circle me-1"></i> Register Student Account
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-3 pt-3 border-top">
                        <p class="text-muted mb-0 small">Already have an account? 
                            <a href="<?= BASE_URL ?>auth/login.php" class="fw-bold text-primary">Login here</a>
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
