<?php
/**
 * Admin Portal Sidebar Component
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="portal-sidebar">
    <div class="sidebar-profile">
        <div class="avatar-circle">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h6 class="mb-0 fw-bold">Admin Console</h6>
        <small class="text-muted">Training & Placement Cell</small>
    </div>

    <ul class="nav flex-column portal-nav">
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= in_array($currentPage, ['students.php', 'student_view.php', 'student_edit.php']) ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/students.php">
                <i class="bi bi-people-fill"></i> Manage Students
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'student_add.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/student_add.php">
                <i class="bi bi-person-plus-fill"></i> Add Student
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'academics.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/academics.php">
                <i class="bi bi-journal-bookmark-fill"></i> Academic & Attendance
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'skills_master.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/skills_master.php">
                <i class="bi bi-stars"></i> Skills Master
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'companies.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/companies.php">
                <i class="bi bi-building"></i> Companies
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= in_array($currentPage, ['drives.php', 'drive_view.php', 'drive_edit.php']) ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/drives.php">
                <i class="bi bi-briefcase-fill"></i> Placement Drives
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'drive_create.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/drive_create.php">
                <i class="bi bi-plus-circle-fill"></i> Create Drive
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/reports.php">
                <i class="bi bi-bar-chart-line-fill"></i> Reports & Statistics
            </a>
        </li>
        <li class="nav-item mt-4 pt-3 border-top">
            <a class="nav-link text-danger" href="<?= BASE_URL ?>auth/logout.php?csrf=<?= getCsrfToken() ?>">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </li>
    </ul>
</aside>
