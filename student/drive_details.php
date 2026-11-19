<?php
/**
 * Student Drive Details & 1-Click Application Handler
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireStudent();

$student = getCurrentStudent($pdo);
$studentId = $student['id'];
$driveId = (int)($_GET['id'] ?? 0);

if ($driveId <= 0) {
    setFlash('danger', 'Invalid placement drive specified.');
    header('Location: ' . BASE_URL . 'student/drives.php');
    exit;
}

// Fetch Drive Details
$stmtDrive = $pdo->prepare("
    SELECT d.*, c.name as company_name, c.website, c.contact_email, c.location as company_location, c.description as company_description
    FROM placement_drives d
    JOIN companies c ON d.company_id = c.id
    WHERE d.id = :id
    LIMIT 1
");
$stmtDrive->execute([':id' => $driveId]);
$drive = $stmtDrive->fetch();

if (!$drive) {
    setFlash('danger', 'Placement drive not found.');
    header('Location: ' . BASE_URL . 'student/drives.php');
    exit;
}

// Required Skills
$requiredSkills = getDriveRequiredSkills($pdo, $driveId);
$studentSkillIds = getStudentSkillIds($pdo, $studentId);

// Check Eligibility
$eligibility = checkStudentEligibility($pdo, $studentId, $driveId);
$existingApplication = hasStudentApplied($pdo, $studentId, $driveId);

// Handle Application Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_now'])) {
    if (!validateCsrfToken()) {
        setFlash('danger', 'Security validation failed (Invalid CSRF token). Please try again.');
        header('Location: ' . BASE_URL . 'student/drive_details.php?id=' . $driveId);
        exit;
    }

    if ($existingApplication) {
        setFlash('warning', 'You have already applied for this placement drive.');
    } elseif (!$eligibility['is_eligible']) {
        setFlash('danger', 'Cannot submit application: You do not meet the drive eligibility criteria.');
    } else {
        try {
            $stmtApply = $pdo->prepare("
                INSERT INTO applications (drive_id, student_id, status)
                VALUES (:did, :sid, 'Applied')
            ");
            $stmtApply->execute([':did' => $driveId, ':sid' => $studentId]);

            setFlash('success', 'Application submitted successfully! Track your status under My Applications.');
            header('Location: ' . BASE_URL . 'student/my_applications.php');
            exit;
        } catch (PDOException $e) {
            error_log("Application submission error: " . $e->getMessage());
            setFlash('danger', 'Failed to submit application due to an internal system error.');
        }
    }
}

$pageTitle = e($drive['job_title']) . ' - Drive Details';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_student.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>student/drives.php">Placement Drives</a></li>
                    <li class="breadcrumb-item active"><?= e($drive['job_title']) ?></li>
                </ol>
            </nav>

            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="row g-4">
                <!-- Main Drive Details -->
                <div class="col-lg-8">
                    <div class="card card-portal mb-4">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-primary text-white mb-1"><?= e($drive['company_name']) ?></span>
                                    <h3 class="fw-bold text-dark mb-0"><?= e($drive['job_title']) ?></h3>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success fs-5 px-3 py-2">₹ <?= number_format((float)$drive['package_lpa'], 2) ?> LPA</span>
                                    <?php $isSaved = hasStudentSavedDrive($pdo, $studentId, $driveId); ?>
                                    <form method="POST" action="<?= BASE_URL ?>student/save_job_toggle.php" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="drive_id" value="<?= $driveId ?>">
                                        <input type="hidden" name="redirect" value="<?= BASE_URL ?>student/drive_details.php?id=<?= $driveId ?>">
                                        <button type="submit" class="btn <?= $isSaved ? 'btn-danger' : 'btn-outline-danger' ?> btn-sm py-2 px-3 fw-bold" title="<?= $isSaved ? 'Remove Bookmark' : 'Bookmark this Drive' ?>">
                                            <i class="bi <?= $isSaved ? 'bi-bookmark-heart-fill' : 'bi-bookmark-heart' ?> me-1"></i>
                                            <?= $isSaved ? 'Saved' : 'Save Job' ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-card-text text-primary me-2"></i>Job Description & Roles</h5>
                            <p class="text-secondary" style="white-space: pre-line;"><?= e($drive['job_description']) ?></p>

                            <hr class="my-4">

                            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-check2-square text-primary me-2"></i>Required Technical Skills</h5>
                            <div class="d-flex flex-wrap gap-2 mb-4">
                                <?php if (!empty($requiredSkills)): ?>
                                    <?php foreach ($requiredSkills as $rSkill): ?>
                                        <?php $hasSkill = in_array($rSkill['id'], $studentSkillIds); ?>
                                        <span class="badge <?= $hasSkill ? 'bg-success' : 'bg-danger' ?> px-3 py-2 fs-6">
                                            <i class="bi <?= $hasSkill ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?> me-1"></i>
                                            <?= e($rSkill['skill_name']) ?> <?= $hasSkill ? '(Matched)' : '(Missing)' ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted small">No specific technical skill prerequisites.</span>
                                <?php endif; ?>
                            </div>

                            <hr class="my-4">

                            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-building text-primary me-2"></i>About <?= e($drive['company_name']) ?></h5>
                            <p class="text-muted small"><?= e($drive['company_description'] ?? 'Recruiting partner organization.') ?></p>
                            <div class="row g-2 small text-muted">
                                <div class="col-md-6"><i class="bi bi-geo-alt me-1"></i> <strong>Location:</strong> <?= e($drive['company_location'] ?? 'N/A') ?></div>
                                <div class="col-md-6"><i class="bi bi-globe me-1"></i> <strong>Website:</strong> <a href="<?= e($drive['website']) ?>" target="_blank"><?= e($drive['website'] ?? 'N/A') ?></a></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Eligibility Card & Apply Action -->
                <div class="col-lg-4">
                    <div class="card card-portal mb-4" id="apply">
                        <div class="card-header">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-speedometer text-primary me-2"></i>Eligibility Check</h5>
                        </div>
                        <div class="card-body p-4">
                            
                            <ul class="list-group list-group-flush mb-4 small">
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>Min CGPA Requirement (<?= e($drive['min_cgpa']) ?>)</span>
                                    <?php if (($student['cgpa'] ?? 0) >= $drive['min_cgpa']): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-lg"></i> <?= e($student['cgpa']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-lg"></i> <?= e($student['cgpa'] ?? 0) ?></span>
                                    <?php endif; ?>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>Min Attendance (<?= e($drive['min_attendance']) ?>%)</span>
                                    <?php if (($student['attendance_percentage'] ?? 0) >= $drive['min_attendance']): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-lg"></i> <?= e($student['attendance_percentage']) ?>%</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-lg"></i> <?= e($student['attendance_percentage'] ?? 0) ?>%</span>
                                    <?php endif; ?>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>Max Allowed Backlogs (<?= e($drive['max_backlogs']) ?>)</span>
                                    <?php if (($student['active_backlogs'] ?? 0) <= $drive['max_backlogs']): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-lg"></i> <?= e($student['active_backlogs'] ?? 0) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-lg"></i> <?= e($student['active_backlogs']) ?></span>
                                    <?php endif; ?>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>Branch Allowed (<?= e($drive['allowed_branches']) ?>)</span>
                                    <span class="badge bg-info text-dark"><?= e($student['department']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>Resume Uploaded</span>
                                    <?php if (!empty($student['resume_path'])): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-lg"></i> Yes</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-lg"></i> Missing</span>
                                    <?php endif; ?>
                                </li>
                            </ul>

                            <!-- Action Box -->
                            <?php if ($existingApplication): ?>
                                <div class="alert alert-primary text-center mb-0">
                                    <i class="bi bi-check-circle-fill fs-3 d-block mb-2"></i>
                                    <h6 class="fw-bold mb-1">Application Submitted</h6>
                                    <p class="small mb-2">Status: <?= getStatusBadge($existingApplication['status']) ?></p>
                                    <a href="<?= BASE_URL ?>student/my_applications.php" class="btn btn-sm btn-outline-primary">View Status Timeline</a>
                                </div>
                            <?php elseif ($eligibility['is_eligible']): ?>
                                <div class="alert alert-success text-center mb-3">
                                    <i class="bi bi-award-fill fs-3 d-block mb-1"></i>
                                    <strong>You Meet All Criteria!</strong>
                                </div>
                                <form action="<?= BASE_URL ?>student/drive_details.php?id=<?= $drive['id'] ?>" method="POST">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="apply_now" value="1">
                                    <button type="submit" class="btn btn-success btn-lg w-100 fw-bold shadow" data-confirm="Confirm submitting your application for <?= e($drive['job_title']) ?>?">
                                        <i class="bi bi-send-fill me-1"></i> Submit Application
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-danger mb-3">
                                    <h6 class="fw-bold mb-2"><i class="bi bi-x-octagon-fill me-1"></i> Not Eligible:</h6>
                                    <ul class="mb-0 ps-3 small">
                                        <?php foreach ($eligibility['reasons'] as $rsn): ?>
                                            <li><?= e($rsn) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <button class="btn btn-secondary w-100" disabled>
                                    <i class="bi bi-lock-fill me-1"></i> Applications Locked
                                </button>
                                <?php if (empty($student['resume_path'])): ?>
                                    <a href="<?= BASE_URL ?>student/resume.php" class="btn btn-outline-danger btn-sm w-100 mt-2">
                                        <i class="bi bi-cloud-arrow-up me-1"></i> Upload Resume Now
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>

                        </div>
                    </div>

                    <!-- Key Dates Card -->
                    <div class="card bg-light border-0 shadow-sm p-3">
                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-calendar-event text-primary me-2"></i>Important Schedule</h6>
                        <div class="small text-muted mb-2">
                            <strong>Drive Date:</strong> <?= formatPortalDate($drive['drive_date']) ?>
                        </div>
                        <div class="small text-muted">
                            <strong>Application Deadline:</strong> <span class="text-danger fw-bold"><?= formatPortalDate($drive['deadline']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
