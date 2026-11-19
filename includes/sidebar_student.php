<?php
/**
 * Student Portal Sidebar Component
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$studentInfo = getCurrentStudent($pdo);
$displayName = $studentInfo ? ($studentInfo['first_name'] . ' ' . $studentInfo['last_name']) : 'Student';
$initials = $studentInfo ? strtoupper(substr($studentInfo['first_name'], 0, 1) . substr($studentInfo['last_name'], 0, 1)) : 'ST';
?>
<aside class="portal-sidebar">
    <div class="sidebar-profile">
        <div class="avatar-circle">
            <?= e($initials) ?>
        </div>
        <h6 class="mb-0 fw-bold"><?= e($displayName) ?></h6>
        <small class="text-muted"><?= e($studentInfo['enrollment_no'] ?? 'Student Portal') ?></small>
    </div>

    <ul class="nav flex-column portal-nav">
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>student/dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'profile.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>student/profile.php">
                <i class="bi bi-person-fill"></i> My Profile
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'academics.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>student/academics.php">
                <i class="bi bi-award-fill"></i> Academic & Attendance
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'skills.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>student/skills.php">
                <i class="bi bi-code-slash"></i> My Skills
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'resume_builder.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>student/resume_builder.php">
                <i class="bi bi-file-earmark-person-fill text-success"></i> Build Resume (PDF)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'resume.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>student/resume.php">
                <i class="bi bi-file-earmark-pdf-fill"></i> Upload Resume
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= in_array($currentPage, ['drives.php', 'drive_details.php']) ? 'active' : '' ?>" href="<?= BASE_URL ?>student/drives.php">
                <i class="bi bi-briefcase-fill"></i> Placement Drives
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'saved_jobs.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>student/saved_jobs.php">
                <i class="bi bi-bookmark-heart-fill text-danger"></i> Saved Jobs
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'my_applications.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>student/my_applications.php">
                <i class="bi bi-send-check-fill"></i> My Applications
            </a>
        </li>
        <li class="nav-item mt-4 pt-3 border-top">
            <a class="nav-link text-danger" href="<?= BASE_URL ?>auth/logout.php?csrf=<?= getCsrfToken() ?>">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </li>
    </ul>
</aside>
