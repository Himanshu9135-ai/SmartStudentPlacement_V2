<?php
/**
 * Student Saved Jobs & Bookmarked Drives View
 * PlacementIndia Style Bookmarking
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireStudent();

$student = getCurrentStudent($pdo);
$studentId = $student['id'];

// Ensure saved_jobs table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `saved_jobs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `student_id` INT NOT NULL,
        `drive_id` INT NOT NULL,
        `saved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uk_student_drive_saved` (`student_id`, `drive_id`),
        CONSTRAINT `fk_saved_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_saved_drive` FOREIGN KEY (`drive_id`) REFERENCES `placement_drives` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB;
");

// Fetch saved drives
$stmt = $pdo->prepare("
    SELECT d.*, c.name as company_name, c.location as company_location, c.website as company_website, s.saved_at
    FROM saved_jobs s
    JOIN placement_drives d ON s.drive_id = d.id
    JOIN companies c ON d.company_id = c.id
    WHERE s.student_id = ?
    ORDER BY s.saved_at DESC
");
$stmt->execute([$studentId]);
$savedDrives = $stmt->fetchAll();

// Process eligibility for each saved drive
$processedDrives = [];
foreach ($savedDrives as $dr) {
    $eligibility = checkStudentEligibility($pdo, $studentId, $dr['id']);
    $application = hasStudentApplied($pdo, $studentId, $dr['id']);

    $dr['is_eligible'] = $eligibility['is_eligible'];
    $dr['reasons'] = $eligibility['reasons'];
    $dr['missing_skills'] = $eligibility['missing_skills'];
    $dr['application'] = $application;
    $processedDrives[] = $dr;
}

$pageTitle = 'Saved Jobs & Bookmarks';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_student.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-bookmark-heart-fill text-danger me-2"></i>Saved Placement Drives</h3>
                    <p class="text-muted mb-0">Track your bookmarked jobs and apply before the application deadlines expire.</p>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>student/drives.php" class="btn btn-outline-primary btn-sm px-3">
                        <i class="bi bi-search me-1"></i> Explore More Drives
                    </a>
                </div>
            </div>

            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

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
                                        <!-- Remove Bookmark button -->
                                        <form method="POST" action="<?= BASE_URL ?>student/save_job_toggle.php" class="d-inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="drive_id" value="<?= $dr['id'] ?>">
                                            <input type="hidden" name="redirect" value="<?= BASE_URL ?>student/saved_jobs.php">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove from saved">
                                                <i class="bi bi-trash"></i>
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

                                    <!-- Status Section -->
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
                                                    <div class="small fw-normal">All criteria met. Submit your application below.</div>
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
                                    <small class="text-muted d-block"><i class="bi bi-clock me-1"></i> Saved on <?= date('d M Y, h:i A', strtotime($dr['saved_at'])) ?></small>
                                </div>
                                <div class="card-footer bg-white border-0 pt-0 pb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="<?= BASE_URL ?>student/drive_details.php?id=<?= $dr['id'] ?>" class="btn btn-outline-primary btn-sm px-3 fw-bold">
                                            <i class="bi bi-info-circle me-1"></i> View Details
                                        </a>

                                        <?php if ($dr['application']): ?>
                                            <a href="<?= BASE_URL ?>student/my_applications.php" class="btn btn-sm btn-info text-white">
                                                <i class="bi bi-search me-1"></i> Track Status
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
                            <i class="bi bi-bookmark-heart text-muted display-3 mb-3"></i>
                            <h4 class="fw-bold text-dark">No Saved Jobs Yet</h4>
                            <p class="text-muted">You haven't bookmarked any placement drives. Browse campus drives and save the ones you are interested in.</p>
                            <a href="<?= BASE_URL ?>student/drives.php" class="btn btn-primary btn-sm mx-auto px-4">
                                <i class="bi bi-briefcase me-1"></i> Browse Drives
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
