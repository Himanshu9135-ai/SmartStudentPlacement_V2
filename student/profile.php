<?php
/**
 * Student Profile Management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireStudent();

$student = getCurrentStudent($pdo);
$studentId = $student['id'];
$userId = $student['user_id'];

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        setFlash('danger', 'Security validation failed (Invalid CSRF token).');
        header('Location: ' . BASE_URL . 'student/profile.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $first_name          = trim($_POST['first_name'] ?? '');
        $last_name           = trim($_POST['last_name'] ?? '');
        $phone               = trim($_POST['phone'] ?? '');
        $gender              = trim($_POST['gender'] ?? 'Male');
        $dob                 = !empty($_POST['dob']) ? $_POST['dob'] : null;
        $address             = trim($_POST['address'] ?? '');
        $preferred_location  = trim($_POST['preferred_location'] ?? '');
        $project_title       = trim($_POST['project_title'] ?? '');

        if (empty($first_name) || empty($last_name)) {
            setFlash('danger', 'First name and last name are required.');
        } else {
            try {
                // Ensure helper columns exist in students table
                $cols = $pdo->query("SHOW COLUMNS FROM students LIKE 'preferred_location'")->fetchAll();
                if (empty($cols)) {
                    $pdo->exec("ALTER TABLE students ADD COLUMN preferred_location VARCHAR(100) NULL AFTER address");
                    $pdo->exec("ALTER TABLE students ADD COLUMN project_title VARCHAR(255) NULL AFTER preferred_location");
                }
                $photoCol = $pdo->query("SHOW COLUMNS FROM students LIKE 'photo_path'")->fetchAll();
                if (empty($photoCol)) {
                    $pdo->exec("ALTER TABLE students ADD COLUMN photo_path VARCHAR(255) NULL AFTER project_title");
                }

                // Handle Profile Photo Upload if provided
                $uploadedPhoto = $student['photo_path'] ?? null;
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $pExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
                    if (in_array($pExt, $allowedExts, true) && $_FILES['photo']['size'] <= 2 * 1024 * 1024) {
                        $pNewName = 'student_' . $student['enrollment_no'] . '_' . uniqid() . '.' . $pExt;
                        $pDest = __DIR__ . '/../uploads/photos/' . $pNewName;
                        if (move_uploaded_file($_FILES['photo']['tmp_name'], $pDest)) {
                            // remove old photo if exists
                            if (!empty($student['photo_path']) && file_exists(__DIR__ . '/../' . $student['photo_path'])) {
                                @unlink(__DIR__ . '/../' . $student['photo_path']);
                            }
                            $uploadedPhoto = 'uploads/photos/' . $pNewName;
                        }
                    }
                }

                $stmtUpdate = $pdo->prepare("
                    UPDATE students 
                    SET first_name = :fname, last_name = :lname, phone = :phone, 
                        gender = :gender, dob = :dob, address = :addr,
                        preferred_location = :loc, project_title = :proj,
                        photo_path = :photo
                    WHERE id = :sid
                ");
                $stmtUpdate->execute([
                    ':fname'  => $first_name,
                    ':lname'  => $last_name,
                    ':phone'  => $phone,
                    ':gender' => $gender,
                    ':dob'    => $dob,
                    ':addr'   => $address,
                    ':loc'    => $preferred_location,
                    ':proj'   => $project_title,
                    ':photo'  => $uploadedPhoto,
                    ':sid'    => $studentId
                ]);
                setFlash('success', 'Profile updated successfully!');
            } catch (PDOException $e) {
                error_log("Student profile update error: " . $e->getMessage());
                setFlash('danger', 'Failed to update profile due to a system error.');
            }
            header('Location: ' . BASE_URL . 'student/profile.php');
            exit;
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $stmtUser = $pdo->prepare("SELECT password FROM users WHERE id = :uid");
        $stmtUser->execute([':uid' => $userId]);
        $userRow = $stmtUser->fetch();

        if (!password_verify($current_password, $userRow['password'])) {
            setFlash('danger', 'Current password is incorrect.');
        } elseif (strlen($new_password) < 8 || !preg_match('/[A-Z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
            setFlash('danger', 'New password must be at least 8 characters long and include an uppercase letter and a number.');
        } elseif ($new_password !== $confirm_password) {
            setFlash('danger', 'New password confirmation does not match.');
        } else {
            try {
                $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                $stmtPass = $pdo->prepare("UPDATE users SET password = :pass WHERE id = :uid");
                $stmtPass->execute([':pass' => $hashed, ':uid' => $userId]);
                setFlash('success', 'Password changed successfully!');
            } catch (PDOException $e) {
                error_log("Password update error: " . $e->getMessage());
                setFlash('danger', 'Failed to change password due to a system error.');
            }
            header('Location: ' . BASE_URL . 'student/profile.php');
            exit;
        }
    }
}

// Refresh student data
$student = getCurrentStudent($pdo);

$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_student.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-person-fill text-primary me-2"></i>My Profile</h3>
                    <p class="text-muted mb-0">Manage your personal information and account security</p>
                </div>
            </div>

            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="row g-4">
                <!-- Personal Info Form -->
                <div class="col-lg-8">
                    <div class="card card-portal">
                        <div class="card-header">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-person-badge me-2 text-primary"></i>Personal Details</h5>
                        </div>
                        <div class="card-body p-4">
                            <form action="<?= BASE_URL ?>student/profile.php" method="POST" enctype="multipart/form-data" class="needs-validation">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="update_profile">

                                <!-- Profile Picture Upload / Preview -->
                                <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 mb-4 border">
                                    <?php if (!empty($student['photo_path']) && file_exists(__DIR__ . '/../' . $student['photo_path'])): ?>
                                        <img src="<?= BASE_URL . e($student['photo_path']) ?>" alt="Profile Photo" class="rounded-circle shadow-sm border border-2 border-primary" style="width: 75px; height: 75px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 75px; height: 75px; background: linear-gradient(135deg, #1e3c72, #2a5298); font-size: 24px;">
                                            <?= strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1">
                                        <label for="photo" class="form-label fw-bold mb-1"><i class="bi bi-camera me-1"></i> Profile Photo (Optional)</label>
                                        <input type="file" name="photo" id="photo" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp">
                                        <small class="text-muted">Upload a formal passport-size photo (JPG, PNG, max 2MB). Used for Resume & ID Card.</small>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-bold">Enrollment / Roll Number</label>
                                        <input type="text" class="form-control bg-light" value="<?= e($student['enrollment_no']) ?>" readonly>
                                        <small class="text-muted">Official enrollment number is verified and read-only.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-bold">Department / Branch</label>
                                        <input type="text" class="form-control bg-light" value="<?= e($student['department']) ?>" readonly>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label fw-semibold">First Name *</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?= e($student['first_name']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label fw-semibold">Last Name *</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?= e($student['last_name']) ?>" required>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-bold">Email Address</label>
                                        <input type="email" class="form-control bg-light" value="<?= e($student['email']) ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label fw-semibold">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($student['phone']) ?>" placeholder="10-digit mobile number">
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="gender" class="form-label fw-semibold">Gender</label>
                                        <select class="form-select" id="gender" name="gender">
                                            <option value="Male" <?= $student['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                            <option value="Female" <?= $student['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                            <option value="Other" <?= $student['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="dob" class="form-label fw-semibold">Date of Birth</label>
                                        <input type="date" class="form-control" id="dob" name="dob" value="<?= e($student['dob']) ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="address" class="form-label fw-semibold">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="2" placeholder="Permanent Address"><?= e($student['address']) ?></textarea>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="preferred_location" class="form-label fw-semibold">Preferred Job Location</label>
                                        <input type="text" class="form-control" id="preferred_location" name="preferred_location" value="<?= e($student['preferred_location'] ?? '') ?>" placeholder="e.g. Pune, Bangalore, Delhi NCR, Remote">
                                        <small class="text-muted">Cities you are willing to relocate to.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="project_title" class="form-label fw-semibold">Major Academic Project</label>
                                        <input type="text" class="form-control" id="project_title" name="project_title" value="<?= e($student['project_title'] ?? '') ?>" placeholder="e.g. AI-Based Attendance System">
                                        <small class="text-muted">Final year / Capstone project name.</small>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary px-4 fw-bold">
                                    <i class="bi bi-save me-1"></i> Save Profile Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Security / Change Password -->
                <div class="col-lg-4">
                    <div class="card card-portal">
                        <div class="card-header">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-shield-lock me-2 text-primary"></i>Change Password</h5>
                        </div>
                        <div class="card-body p-4">
                            <form action="<?= BASE_URL ?>student/profile.php" method="POST" class="needs-validation">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="change_password">

                                <div class="mb-3">
                                    <label for="current_password" class="form-label fw-semibold">Current Password *</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label fw-semibold">New Password *</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required>
                                    <small class="text-muted">Minimum 6 characters.</small>
                                </div>

                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label fw-semibold">Confirm New Password *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                                </div>

                                <button type="submit" class="btn btn-outline-primary w-100 fw-bold">
                                    <i class="bi bi-key me-1"></i> Update Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
