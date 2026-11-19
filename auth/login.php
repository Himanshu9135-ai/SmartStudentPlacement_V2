<?php
/**
 * Modern Unified Role-Based Login Portal
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

// Active role tab from query (?role=admin | student | company)
$activeRole = strtolower($_GET['role'] ?? 'student');
if (!in_array($activeRole, ['student', 'admin', 'company'], true)) {
    $activeRole = 'student';
}

$pageTitle = ucfirst($activeRole) . ' Login Portal';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Role Selector Nav Tabs -->
            <div class="card border-0 shadow-sm mb-3 rounded-4 overflow-hidden">
                <div class="btn-group w-100 p-1 bg-light rounded-4" role="group">
                    <a href="<?= BASE_URL ?>auth/login.php?role=student" class="btn btn-sm rounded-3 py-2 fw-bold <?= $activeRole === 'student' ? 'btn-primary shadow-sm' : 'btn-light text-muted' ?>">
                        <i class="bi bi-mortarboard-fill me-1"></i> Student
                    </a>
                    <a href="<?= BASE_URL ?>auth/login.php?role=admin" class="btn btn-sm rounded-3 py-2 fw-bold <?= $activeRole === 'admin' ? 'btn-dark shadow-sm' : 'btn-light text-muted' ?>">
                        <i class="bi bi-shield-lock-fill me-1"></i> Admin / TPO
                    </a>
                    <a href="<?= BASE_URL ?>auth/login.php?role=company" class="btn btn-sm rounded-3 py-2 fw-bold <?= $activeRole === 'company' ? 'btn-success shadow-sm' : 'btn-light text-muted' ?>">
                        <i class="bi bi-building-fill me-1"></i> Recruiter
                    </a>
                </div>
            </div>

            <!-- Login Box Card -->
            <div class="card card-portal shadow-lg border-0 rounded-4 overflow-hidden">
                <?php if ($activeRole === 'student'): ?>
                    <div class="card-header bg-primary text-white text-center py-4">
                        <div class="display-6 mb-2"><i class="bi bi-mortarboard"></i></div>
                        <h4 class="mb-1 fw-bold">Student Placement Portal</h4>
                        <small class="text-white-50">Access your drives, applications, and verified scorecards</small>
                    </div>
                <?php elseif ($activeRole === 'admin'): ?>
                    <div class="card-header bg-dark text-white text-center py-4">
                        <div class="display-6 mb-2"><i class="bi bi-shield-check text-warning"></i></div>
                        <h4 class="mb-1 fw-bold">TPO Admin Console</h4>
                        <small class="text-white-50">Manage candidates, recruitment drives, and analytics</small>
                    </div>
                <?php else: ?>
                    <div class="card-header bg-success text-white text-center py-4">
                        <div class="display-6 mb-2"><i class="bi bi-building-check"></i></div>
                        <h4 class="mb-1 fw-bold">Recruiter & Company Hub</h4>
                        <small class="text-white-50">Review applicants, shortlist candidates, and update stages</small>
                    </div>
                <?php endif; ?>

                <div class="card-body p-4 p-md-5">
                    <form action="<?= BASE_URL ?>auth/login_process.php" method="POST" class="needs-validation">
                        <?= csrfField() ?>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">
                                <?= $activeRole === 'student' ? 'Student College Email' : ($activeRole === 'company' ? 'Corporate Recruiter Email' : 'Admin Email Address') ?>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                <input type="email" class="form-control border-start-0 ps-0" id="email" name="email" 
                                    placeholder="<?= $activeRole === 'student' ? 'rahul.sharma@college.edu' : ($activeRole === 'company' ? 'recruiter@company.edu' : 'admin@college.edu') ?>" 
                                    required autofocus>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="password" class="form-label fw-semibold mb-0">Password</label>
                                <a href="<?= BASE_URL ?>auth/forgot_password.php" class="small text-decoration-none text-primary">Forgot Password?</a>
                            </div>
                            <div class="input-group mt-1">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                                <input type="password" class="form-control border-start-0 border-end-0 ps-0" id="password" name="password" placeholder="••••••••" required>
                                <button type="button" class="input-group-text bg-light border-start-0 toggle-password" onclick="togglePasswordVisibility()">
                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid mt-4 mb-3">
                            <button type="submit" class="btn btn-lg fw-bold shadow-sm <?= $activeRole === 'student' ? 'btn-primary' : ($activeRole === 'company' ? 'btn-success' : 'btn-dark') ?>">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to <?= ucfirst($activeRole) ?> Portal
                            </button>
                        </div>
                    </form>

                    <?php if ($activeRole === 'student'): ?>
                        <div class="text-center mt-4 pt-3 border-top">
                            <p class="text-muted mb-0 small">Are you a new student? 
                                <a href="<?= BASE_URL ?>auth/register.php" class="fw-bold text-primary">Register here</a>
                            </p>
                        </div>
                    <?php elseif ($activeRole === 'company'): ?>
                        <div class="text-center mt-4 pt-3 border-top">
                            <p class="text-muted mb-0 small">New Corporate Hiring Partner? 
                                <a href="<?= BASE_URL ?>auth/register_company.php" class="fw-bold text-success">Register your Company here</a>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function togglePasswordVisibility() {
    const input = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
