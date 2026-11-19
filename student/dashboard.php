<?php
/**
 * Student Dashboard
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireStudent();

$student = getCurrentStudent($pdo);
if (!$student) {
    setFlash('danger', 'Student record not found.');
    header('Location: ' . BASE_URL . 'auth/logout.php');
    exit;
}

$studentId = $student['id'];

// 1. Fetch Key Metrics
// Total Active Drives
$activeDrivesCount = $pdo->query("SELECT COUNT(*) FROM placement_drives WHERE status = 'Active' AND deadline >= CURDATE()")->fetchColumn() ?: 0;

// Total Applied Drives
$stmtAppCount = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE student_id = :sid");
$stmtAppCount->execute([':sid' => $studentId]);
$appliedCount = $stmtAppCount->fetchColumn() ?: 0;

// Total Selected Offers
$stmtSelCount = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE student_id = :sid AND status = 'Selected'");
$stmtSelCount->execute([':sid' => $studentId]);
$selectedCount = $stmtSelCount->fetchColumn() ?: 0;

// Total Skills Count
$stmtSkillCount = $pdo->prepare("SELECT COUNT(*) FROM student_skills WHERE student_id = :sid");
$stmtSkillCount->execute([':sid' => $studentId]);
$skillCount = $stmtSkillCount->fetchColumn() ?: 0;

// Calculate Profile Completion %
$completionScore = 0;
if (!empty($student['first_name']) && !empty($student['last_name'])) $completionScore += 20;
if (!empty($student['phone'])) $completionScore += 15;
if (!empty($student['resume_path'])) $completionScore += 25;
if ($skillCount > 0) $completionScore += 20;
if (($student['cgpa'] ?? 0) > 0) $completionScore += 20;

// Recent Applications
$stmtRecentApps = $pdo->prepare("
    SELECT a.*, d.job_title, d.package_lpa, c.name as company_name
    FROM applications a
    JOIN placement_drives d ON a.drive_id = d.id
    JOIN companies c ON d.company_id = c.id
    WHERE a.student_id = :sid
    ORDER BY a.applied_at DESC
    LIMIT 5
");
$stmtRecentApps->execute([':sid' => $studentId]);
$recentApplications = $stmtRecentApps->fetchAll();

// Eligible Drives Count calculation
$allActiveDrives = $pdo->query("SELECT id FROM placement_drives WHERE status = 'Active' AND deadline >= CURDATE()")->fetchAll();
$eligibleDrivesCount = 0;
foreach ($allActiveDrives as $dr) {
    $res = checkStudentEligibility($pdo, $studentId, $dr['id']);
    if ($res['is_eligible']) {
        $eligibleDrivesCount++;
    }
}

$pageTitle = 'Student Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_student.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Welcome Banner -->
            <div class="card card-portal bg-primary text-white mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="fw-bold mb-1">Welcome back, <?= e($student['first_name']) ?>!</h3>
                            <p class="mb-2 text-white-50">
                                <?= e($student['department']) ?> | Sem <?= e($student['semester']) ?> | Roll No: <strong><?= e($student['enrollment_no']) ?></strong>
                            </p>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <span class="badge bg-light text-dark px-3 py-2">
                                    <i class="bi bi-mortarboard-fill text-primary me-1"></i> CGPA: <strong><?= number_format((float)($student['cgpa'] ?? 0), 2) ?></strong>
                                </span>
                                <span class="badge bg-light text-dark px-3 py-2">
                                    <i class="bi bi-clock-history text-primary me-1"></i> Attendance: <strong><?= number_format((float)($student['attendance_percentage'] ?? 0), 1) ?>%</strong>
                                </span>
                                <span class="badge <?= empty($student['resume_path']) ? 'bg-danger text-white' : 'bg-success text-white' ?> px-3 py-2">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> <?= empty($student['resume_path']) ? 'Resume Missing' : 'Resume Uploaded' ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <div class="bg-white bg-opacity-10 p-3 rounded text-center">
                                <span class="d-block small text-white-50 mb-1">Profile Completion</span>
                                <h4 class="fw-bold mb-2"><?= $completionScore ?>%</h4>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $completionScore ?>%;" aria-valuenow="<?= $completionScore ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metric Cards -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card bg-white p-3">
                        <i class="bi bi-briefcase text-primary stat-icon"></i>
                        <span class="stat-label text-muted">Active Drives</span>
                        <div class="stat-value text-primary"><?= $activeDrivesCount ?></div>
                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Open for campus</small>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card bg-white p-3">
                        <i class="bi bi-check2-circle text-success stat-icon"></i>
                        <span class="stat-label text-muted">Eligible Drives</span>
                        <div class="stat-value text-success"><?= $eligibleDrivesCount ?></div>
                        <small class="text-success"><i class="bi bi-check-all me-1"></i>Matched criteria</small>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card bg-white p-3">
                        <i class="bi bi-send text-info stat-icon"></i>
                        <span class="stat-label text-muted">Applied Drives</span>
                        <div class="stat-value text-info"><?= $appliedCount ?></div>
                        <small class="text-muted"><i class="bi bi-clock me-1"></i>Submissions</small>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card bg-white p-3">
                        <i class="bi bi-trophy text-warning stat-icon"></i>
                        <span class="stat-label text-muted">Selected Offers</span>
                        <div class="stat-value text-warning"><?= $selectedCount ?></div>
                        <small class="text-muted"><i class="bi bi-star-fill text-warning me-1"></i>Final selections</small>
                    </div>
                </div>
            </div>

            <!-- Main Dashboard Body (Recent Applications & Quick Actions) -->
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card card-portal">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>My Recent Applications</h5>
                            <a href="<?= BASE_URL ?>student/my_applications.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($recentApplications)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-portal mb-0 align-middle">
                                        <thead>
                                            <tr>
                                                <th>Company & Role</th>
                                                <th>CTC</th>
                                                <th>Applied Date</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentApplications as $app): ?>
                                                <tr>
                                                    <td>
                                                        <strong class="d-block text-dark"><?= e($app['job_title']) ?></strong>
                                                        <small class="text-muted"><?= e($app['company_name']) ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark">₹ <?= number_format((float)$app['package_lpa'], 2) ?> LPA</span>
                                                    </td>
                                                    <td><?= formatPortalDate($app['applied_at']) ?></td>
                                                    <td><?= getStatusBadge($app['status']) ?></td>
                                                    <td>
                                                        <a href="<?= BASE_URL ?>student/drive_details.php?id=<?= $app['drive_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                            <i class="bi bi-info-circle"></i> Details
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                    <p class="mb-2">You haven't applied to any placement drives yet.</p>
                                    <a href="<?= BASE_URL ?>student/drives.php" class="btn btn-primary btn-sm">
                                        <i class="bi bi-briefcase me-1"></i> Browse Placement Drives
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Quick Actions Card -->
                    <div class="card card-portal mb-4">
                        <div class="card-header">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-lightning-charge me-2 text-warning"></i>Quick Shortcuts</h5>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-grid gap-2">
                                <a href="<?= BASE_URL ?>student/drives.php" class="btn btn-outline-primary text-start">
                                    <i class="bi bi-search me-2"></i> Browse Eligible Placement Drives
                                </a>
                                <a href="<?= BASE_URL ?>student/skills.php" class="btn btn-outline-secondary text-start">
                                    <i class="bi bi-plus-circle me-2"></i> Update My Technical Skills (<?= $skillCount ?> added)
                                </a>
                                <a href="<?= BASE_URL ?>student/resume.php" class="btn btn-outline-secondary text-start">
                                    <i class="bi bi-cloud-arrow-up me-2"></i> Upload / Replace Resume
                                </a>
                                <a href="<?= BASE_URL ?>student/profile.php" class="btn btn-outline-secondary text-start">
                                    <i class="bi bi-person-gear me-2"></i> Edit Profile & Contact Details
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Placement Tips Card -->
                    <div class="card bg-light border-0 shadow-sm">
                        <div class="card-body p-3">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-lightbulb-fill text-warning me-1"></i> Placement Tips</h6>
                            <ul class="small text-muted mb-0 ps-3">
                                <li class="mb-1">Keep your attendance above <strong>75%</strong> to remain eligible for top drives.</li>
                                <li class="mb-1">Ensure your uploaded resume is in <strong>standard PDF</strong> format.</li>
                                <li>Add all relevant technical skills to qualify for software roles.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
