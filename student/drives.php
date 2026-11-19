<?php
/**
 * Student Placement Drives Directory with Eligibility Engine
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireStudent();

$student = getCurrentStudent($pdo);
$studentId = $student['id'];

$filter = $_GET['filter'] ?? 'all'; // all | eligible | applied

// Fetch all drives
$drives = $pdo->query("
    SELECT d.*, c.name as company_name, c.location as company_location, c.website as company_website
    FROM placement_drives d
    JOIN companies c ON d.company_id = c.id
    ORDER BY d.deadline ASC
")->fetchAll();

// Process eligibility and applications for each drive
$processedDrives = [];
foreach ($drives as $drive) {
    $eligibility = checkStudentEligibility($pdo, $studentId, $drive['id']);
    $application = hasStudentApplied($pdo, $studentId, $drive['id']);

    $driveData = $drive;
    $driveData['is_eligible'] = $eligibility['is_eligible'];
    $driveData['reasons'] = $eligibility['reasons'];
    $driveData['missing_skills'] = $eligibility['missing_skills'];
    $driveData['application'] = $application;

    // Filter check
    if ($filter === 'eligible' && (!$driveData['is_eligible'] || $driveData['application'])) {
        continue;
    }
    if ($filter === 'applied' && !$driveData['application']) {
        continue;
    }

    $processedDrives[] = $driveData;
}

$pageTitle = 'Available Placement Drives';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_student.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-briefcase-fill text-primary me-2"></i>Campus Placement Drives</h3>
                    <p class="text-muted mb-0">Browse drives and check your automated system eligibility</p>
                </div>

                <!-- Filter Pills -->
                <div class="btn-group shadow-sm" role="group">
                    <a href="<?= BASE_URL ?>student/drives.php?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">
                        All Drives (<?= count($drives) ?>)
                    </a>
                    <a href="<?= BASE_URL ?>student/drives.php?filter=eligible" class="btn btn-sm <?= $filter === 'eligible' ? 'btn-primary' : 'btn-outline-primary' ?>">
                        Eligible Only
                    </a>
                    <a href="<?= BASE_URL ?>student/drives.php?filter=applied" class="btn btn-sm <?= $filter === 'applied' ? 'btn-primary' : 'btn-outline-primary' ?>">
                        My Applied Drives
                    </a>
                </div>
            </div>

            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Drives Grid -->
            <div class="row g-4">
                <?php if (!empty($processedDrives)): ?>
                    <?php foreach ($processedDrives as $dr): ?>
                        <div class="col-lg-6">
                            <div class="card card-portal h-100 border <?= $dr['application'] ? 'border-primary' : ($dr['is_eligible'] ? 'border-success' : 'border-light') ?>">
                                <div class="card-header bg-transparent d-flex justify-content-between align-items-start pt-3">
                                    <div>
                                        <span class="badge bg-secondary text-white mb-1"><?= e($dr['company_name']) ?></span>
                                        <h5 class="fw-bold text-dark mb-0"><?= e($dr['job_title']) ?></h5>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-success text-white fs-6">
                                            ₹ <?= number_format((float)$dr['package_lpa'], 2) ?> LPA
                                        </span>
                                        <?php $isSaved = hasStudentSavedDrive($pdo, $studentId, $dr['id']); ?>
                                        <form method="POST" action="<?= BASE_URL ?>student/save_job_toggle.php" class="d-inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="drive_id" value="<?= $dr['id'] ?>">
                                            <input type="hidden" name="redirect" value="<?= BASE_URL ?>student/drives.php?filter=<?= e($filter) ?>">
                                            <button type="submit" class="btn btn-sm <?= $isSaved ? 'btn-danger' : 'btn-outline-danger' ?>" title="<?= $isSaved ? 'Remove Bookmark' : 'Save / Bookmark Job' ?>">
                                                <i class="bi <?= $isSaved ? 'bi-bookmark-heart-fill' : 'bi-bookmark-heart' ?>"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small mb-3">
                                        <?= e(substr($dr['job_description'], 0, 140)) ?>...
                                    </p>

                                    <!-- Criteria Badges -->
                                    <div class="bg-light p-3 rounded mb-3 small">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <i class="bi bi-mortarboard me-1 text-primary"></i> <strong>Min CGPA:</strong> <?= e($dr['min_cgpa']) ?>
                                            </div>
                                            <div class="col-6">
                                                <i class="bi bi-clock-history me-1 text-primary"></i> <strong>Min Attendance:</strong> <?= e($dr['min_attendance']) ?>%
                                            </div>
                                            <div class="col-6">
                                                <i class="bi bi-geo-alt me-1 text-secondary"></i> <strong>Location:</strong> <?= e($dr['job_location']) ?>
                                            </div>
                                            <div class="col-6">
                                                <i class="bi bi-calendar-check me-1 text-danger"></i> <strong>Deadline:</strong> <?= formatPortalDate($dr['deadline']) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Eligibility Status Section -->
                                    <div class="mb-3">
                                        <?php if ($dr['application']): ?>
                                            <div class="alert alert-primary py-2 px-3 mb-0 d-flex justify-content-between align-items-center">
                                                <span><i class="bi bi-check-circle-fill me-1"></i> Already Applied</span>
                                                <?= getStatusBadge($dr['application']['status']) ?>
                                            </div>
                                        <?php elseif ($dr['is_eligible']): ?>
                                            <div class="badge-eligible d-flex align-items-center">
                                                <i class="bi bi-patch-check-fill text-success fs-5 me-2"></i>
                                                <div>
                                                    <strong>You are Eligible to Apply!</strong>
                                                    <div class="small fw-normal">All CGPA, attendance, and skill criteria met.</div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="badge-ineligible">
                                                <div class="d-flex align-items-center mb-1">
                                                    <i class="bi bi-exclamation-octagon-fill text-danger fs-5 me-2"></i>
                                                    <strong>Not Eligible Currently</strong>
                                                </div>
                                                <ul class="mb-0 ps-3 small fw-normal">
                                                    <?php foreach ($dr['reasons'] as $r): ?>
                                                        <li><?= e($r) ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-0 pt-0 pb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="<?= BASE_URL ?>student/drive_details.php?id=<?= $dr['id'] ?>" class="btn btn-outline-primary btn-sm px-3 fw-bold">
                                            <i class="bi bi-info-circle me-1"></i> View Full Details
                                        </a>

                                        <?php if ($dr['application']): ?>
                                            <a href="<?= BASE_URL ?>student/my_applications.php" class="btn btn-sm btn-info text-white">
                                                <i class="bi bi-search me-1"></i> Track Application
                                            </a>
                                        <?php elseif ($dr['is_eligible']): ?>
                                            <a href="<?= BASE_URL ?>student/drive_details.php?id=<?= $dr['id'] ?>#apply" class="btn btn-success btn-sm px-3 fw-bold">
                                                <i class="bi bi-send-fill me-1"></i> Apply Now
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm px-3" disabled>
                                                <i class="bi bi-lock-fill me-1"></i> Ineligible
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <div class="card card-portal p-5">
                            <i class="bi bi-briefcase text-secondary display-3 mb-3"></i>
                            <h4 class="fw-bold text-dark">No Placement Drives Found</h4>
                            <p class="text-muted">No drives match your selected filter criteria.</p>
                            <a href="<?= BASE_URL ?>student/drives.php" class="btn btn-primary btn-sm mx-auto">
                                View All Drives
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
