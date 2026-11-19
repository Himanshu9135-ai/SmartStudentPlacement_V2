<?php
/**
 * Top Navigation Bar Component
 */
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>">
            <i class="bi bi-mortarboard-fill fs-4 me-2 text-warning"></i>
            <span>Smart Placement Portal</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white me-2" href="<?= BASE_URL ?>admin/dashboard.php">
                                <i class="bi bi-speedometer2 me-1"></i> Admin Panel
                            </a>
                        </li>
                    <?php elseif (isStudent()): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white me-2" href="<?= BASE_URL ?>student/dashboard.php">
                                <i class="bi bi-person-badge me-1"></i> Student Portal
                            </a>
                        </li>
                    <?php elseif (isCompany()): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white me-2" href="<?= BASE_URL ?>company/dashboard.php">
                                <i class="bi bi-building-check me-1"></i> Recruiter Hub
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <span class="badge bg-light text-dark me-2 text-uppercase"><?= e($_SESSION['role']) ?></span>
                            <span><?= e($_SESSION['email'] ?? 'User') ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <?php if (isStudent()): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>student/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>student/resume.php"><i class="bi bi-file-earmark-pdf me-2"></i>My Resume</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>auth/logout.php?csrf=<?= getCsrfToken() ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?= BASE_URL ?>index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?= BASE_URL ?>auth/register.php">Student Register</a>
                    </li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a class="btn btn-warning btn-sm px-3 fw-bold" href="<?= BASE_URL ?>auth/login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Login
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
