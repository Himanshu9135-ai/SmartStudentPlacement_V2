<?php
/**
 * Company Recruiter Self-Service Portal & Dashboard
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireCompany();

$companyId = (int)($_SESSION['company_id'] ?? 0);
$compName = $_SESSION['company_name'] ?? 'Company Recruiter';

// Handle Recruiter Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!validateCsrfToken()) {
        setFlash('danger', 'Security validation failed (Invalid CSRF token).');
    } else {
        $appId = (int)($_POST['application_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'Shortlisted');
        $notes = trim($_POST['recruiter_notes'] ?? '');

        $allowed = ['Applied', 'Shortlisted', 'Interview', 'Selected', 'Rejected'];
        if (in_array($status, $allowed, true) && $appId > 0) {
            $stmtUp = $pdo->prepare("
                UPDATE applications a
                JOIN placement_drives d ON a.drive_id = d.id
                SET a.status = :status, a.admin_notes = :notes
                WHERE a.id = :aid AND d.company_id = :cid
            ");
            $stmtUp->execute([
                ':status' => $status,
                ':notes'  => $notes,
                ':aid'    => $appId,
                ':cid'    => $companyId
            ]);
            setFlash('success', 'Candidate status updated to "' . e($status) . '" successfully!');
        }
    }
    header('Location: ' . BASE_URL . 'company/dashboard.php');
    exit;
}

// Handle Company Posting a New Placement Drive
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_company_drive'])) {
    if (!validateCsrfToken()) {
        setFlash('danger', 'Security validation failed (Invalid CSRF token).');
    } else {
        $job_title        = trim($_POST['job_title'] ?? '');
        $job_description  = trim($_POST['job_description'] ?? '');
        $package_lpa      = (float)($_POST['package_lpa'] ?? 0.0);
        $job_location     = trim($_POST['job_location'] ?? 'On-Site');
        $min_cgpa         = (float)($_POST['min_cgpa'] ?? 6.0);
        $min_attendance   = (float)($_POST['min_attendance'] ?? 75.0);
        $max_backlogs     = (int)($_POST['max_backlogs'] ?? 0);
        $allowed_branches = trim($_POST['allowed_branches'] ?? 'All');
        $drive_date       = !empty($_POST['drive_date']) ? $_POST['drive_date'] : null;
        $deadline         = $_POST['deadline'] ?? '';
        $skills           = is_array($_POST['skills'] ?? null) ? $_POST['skills'] : [];

        if (empty($job_title) || empty($job_description) || empty($deadline)) {
            setFlash('danger', 'Job designation, description, and application deadline are required.');
        } else {
            try {
                $pdo->beginTransaction();

                $stmtDrive = $pdo->prepare("
                    INSERT INTO placement_drives (
                        company_id, job_title, job_description, package_lpa, job_location,
                        min_cgpa, min_attendance, max_backlogs, allowed_branches,
                        drive_date, deadline, status
                    ) VALUES (
                        :cid, :title, :descr, :lpa, :loc,
                        :cgpa, :att, :backlogs, :branches,
                        :ddate, :dline, 'Active'
                    )
                ");
                $stmtDrive->execute([
                    ':cid'      => $companyId,
                    ':title'    => $job_title,
                    ':descr'    => $job_description,
                    ':lpa'      => $package_lpa,
                    ':loc'      => $job_location,
                    ':cgpa'     => $min_cgpa,
                    ':att'      => $min_attendance,
                    ':backlogs' => $max_backlogs,
                    ':branches' => $allowed_branches,
                    ':ddate'    => $drive_date,
                    ':dline'    => $deadline
                ]);
                $newDriveId = $pdo->lastInsertId();

                // Attach required skills
                if (!empty($skills)) {
                    $stmtSkill = $pdo->prepare("INSERT INTO drive_skills (drive_id, skill_id) VALUES (:did, :skid)");
                    foreach ($skills as $skid) {
                        $stmtSkill->execute([':did' => $newDriveId, ':skid' => (int)$skid]);
                    }
                }

                $pdo->commit();
                setFlash('success', 'Recruitment Drive for "' . e($job_title) . '" posted successfully! Students can now see and apply.');
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Recruiter drive create error: " . $e->getMessage());
                setFlash('danger', 'Failed to post drive due to a system error.');
            }
        }
    }
    header('Location: ' . BASE_URL . 'company/dashboard.php');
    exit;
}

// Fetch Company Details
$stmtC = $pdo->prepare("SELECT * FROM companies WHERE id = :cid LIMIT 1");
$stmtC->execute([':cid' => $companyId]);
$company = $stmtC->fetch() ?: ['name' => $compName, 'location' => 'N/A', 'website' => ''];

// Fetch All Master Skills grouped by category
$allSkills = $pdo->query("SELECT * FROM skills ORDER BY category ASC, skill_name ASC")->fetchAll();

// Group skills by engineering discipline
$skillsByBranch = [
    'cs' => [],
    'mech' => [],
    'civil' => [],
    'ece' => [],
    'elec' => [],
    'other' => []
];

foreach ($allSkills as $sk) {
    $cat = strtolower($sk['category']);
    $name = strtolower($sk['skill_name']);

    if (str_contains($cat, 'mechanical')) {
        $skillsByBranch['mech'][] = $sk;
    } elseif (str_contains($cat, 'civil')) {
        $skillsByBranch['civil'][] = $sk;
    } elseif (str_contains($cat, 'electronics') || str_contains($cat, 'communication')) {
        $skillsByBranch['ece'][] = $sk;
    } elseif (str_contains($cat, 'electrical')) {
        $skillsByBranch['elec'][] = $sk;
    } elseif (
        str_contains($cat, 'programming') || 
        str_contains($cat, 'web') || 
        str_contains($cat, 'framework') || 
        str_contains($cat, 'database') || 
        str_contains($cat, 'cloud') ||
        str_contains($name, 'java') ||
        str_contains($name, 'python') ||
        str_contains($name, 'c++')
    ) {
        $skillsByBranch['cs'][] = $sk;
    } else {
        $skillsByBranch['other'][] = $sk;
    }
}

// Fetch Drives by this company
$stmtDrives = $pdo->prepare("
    SELECT d.*, 
           (SELECT COUNT(*) FROM applications WHERE drive_id = d.id) as total_applicants,
           (SELECT COUNT(*) FROM applications WHERE drive_id = d.id AND status = 'Shortlisted') as shortlisted_count,
           (SELECT COUNT(*) FROM applications WHERE drive_id = d.id AND status = 'Selected') as selected_count
    FROM placement_drives d
    WHERE d.company_id = :cid
    ORDER BY d.created_at DESC
");
$stmtDrives->execute([':cid' => $companyId]);
$drives = $stmtDrives->fetchAll();

// Fetch Applicants for this company's drives
$stmtApps = $pdo->prepare("
    SELECT a.*, s.enrollment_no, s.first_name, s.last_name, s.department, s.phone, s.resume_path,
           u.email as student_email,
           ar.cgpa, ar.attendance_percentage,
           d.job_title, d.package_lpa
    FROM applications a
    JOIN placement_drives d ON a.drive_id = d.id
    JOIN students s ON a.student_id = s.id
    JOIN users u ON s.user_id = u.id
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    WHERE d.company_id = :cid
    ORDER BY a.applied_at DESC
");
$stmtApps->execute([':cid' => $companyId]);
$applicants = $stmtApps->fetchAll();

$pageTitle = 'Recruiter Console - ' . e($company['name']);
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

    <!-- Recruiter Hero Banner - HIGH-CONTRAST STYLING -->
    <div class="rounded-4 mb-4 p-4 shadow-sm text-white" style="background: #0f172a !important; border: 1px solid #1e293b;">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-warning text-dark px-3 py-2 fw-bold">
                        <i class="bi bi-patch-check-fill me-1"></i> Partner Corporate Recruiter
                    </span>
                    <span class="badge bg-secondary px-2 py-1">
                        <i class="bi bi-geo-alt me-1"></i><?= e($company['location'] ?? 'Headquarters') ?>
                    </span>
                </div>
                <h2 class="fw-bold mb-2 text-white" style="letter-spacing: -0.5px;">
                    <?= e($company['name']) ?> Hiring Hub
                </h2>
                <p class="mb-0 text-white-50" style="font-size: 15px; line-height: 1.6;">
                    Self-Service Campus Recruitment Hub. Post job drives, customize branch eligibility and required technical skills, review applicants, and confirm interview hires.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="<?= BASE_URL ?>company/search_candidates.php" class="btn btn-outline-light btn-lg fw-bold px-4 py-2 shadow-sm mb-2 w-100 w-lg-auto me-lg-2">
                    <i class="bi bi-people-fill me-1"></i> Search Talent
                </a>
                <button class="btn btn-warning btn-lg fw-bold px-4 py-2 shadow-sm mb-2 w-100 w-lg-auto" data-bs-toggle="modal" data-bs-target="#createDriveModal">
                    <i class="bi bi-plus-circle-fill me-1"></i> Post New Drive
                </button>
                <div class="small">
                    <a href="<?= BASE_URL ?>auth/logout.php?csrf=<?= getCsrfToken() ?>" class="text-white-50 text-decoration-none">
                        <i class="bi bi-box-arrow-right me-1"></i> Sign Out
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-primary">
                <small class="text-muted fw-bold text-uppercase" style="font-size: 12px;">Active Job Drives</small>
                <h3 class="fw-bold text-primary mb-0 mt-1"><?= count($drives) ?></h3>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-dark">
                <small class="text-muted fw-bold text-uppercase" style="font-size: 12px;">Total Applicants</small>
                <h3 class="fw-bold text-dark mb-0 mt-1"><?= count($applicants) ?></h3>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-info">
                <small class="text-muted fw-bold text-uppercase" style="font-size: 12px;">Shortlisted / Interview</small>
                <h3 class="fw-bold text-info mb-0 mt-1">
                    <?= count(array_filter($applicants, fn($a) => in_array($a['status'], ['Shortlisted', 'Interview'], true))) ?>
                </h3>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-success">
                <small class="text-muted fw-bold text-uppercase" style="font-size: 12px;">Selected Hires</small>
                <h3 class="fw-bold text-success mb-0 mt-1">
                    <?= count(array_filter($applicants, fn($a) => $a['status'] === 'Selected')) ?>
                </h3>
            </div>
        </div>
    </div>

    <!-- Section 1: Company's Posted Placement Drives Directory -->
    <div class="card card-portal shadow-sm border-0 rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-briefcase-fill text-primary me-2"></i>My Posted Placement Drives (<?= count($drives) ?>)
                </h5>
                <small class="text-muted">Recruitment requirements posted by <?= e($company['name']) ?> visible to eligible students</small>
            </div>
            <button class="btn btn-sm btn-primary fw-bold mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#createDriveModal">
                <i class="bi bi-plus-lg me-1"></i> Post Another Requirement
            </button>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($drives)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Designation / Role</th>
                                <th>Compensation (CTC)</th>
                                <th>Eligibility Criteria</th>
                                <th>Drive Date</th>
                                <th>Deadline</th>
                                <th>Applicant Funnel</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($drives as $dr): ?>
                                <tr>
                                    <td>
                                        <strong class="text-dark d-block"><?= e($dr['job_title']) ?></strong>
                                        <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($dr['job_location']) ?></small>
                                    </td>
                                    <td>
                                        <strong class="text-success fs-6">₹ <?= number_format((float)$dr['package_lpa'], 2) ?> LPA</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">Min CGPA: <?= e($dr['min_cgpa']) ?></span>
                                        <span class="badge bg-light text-dark border">Att: <?= e($dr['min_attendance']) ?>%</span>
                                        <small class="d-block text-muted mt-1">Branches: <?= e($dr['allowed_branches']) ?></small>
                                    </td>
                                    <td><?= formatPortalDate($dr['drive_date']) ?></td>
                                    <td>
                                        <?php if ($dr['deadline'] < date('Y-m-d')): ?>
                                            <span class="text-danger fw-bold"><i class="bi bi-x-circle me-1"></i> Closed (<?= formatPortalDate($dr['deadline']) ?>)</span>
                                        <?php else: ?>
                                            <span class="text-success fw-bold"><i class="bi bi-clock-history me-1"></i> <?= formatPortalDate($dr['deadline']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary rounded-pill"><?= $dr['total_applicants'] ?> Applied</span>
                                        <span class="badge bg-success rounded-pill"><?= $dr['selected_count'] ?> Hired</span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $dr['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= e($dr['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-briefcase display-4 text-secondary mb-3 d-block"></i>
                    <h5 class="fw-bold text-dark">No Recruitment Drives Posted Yet</h5>
                    <p class="small text-muted mb-3">Post your first campus hiring requirement with cutoffs and skills to start receiving student applications.</p>
                    <button class="btn btn-primary btn-sm fw-bold px-4 py-2" data-bs-toggle="modal" data-bs-target="#createDriveModal">
                        <i class="bi bi-plus-circle me-1"></i> Post Drive Now
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Section 2: Candidate Applications Directory -->
    <div class="card card-portal shadow-sm border-0 rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-people-fill text-primary me-2"></i>Candidate Applications (<?= count($applicants) ?>)</h5>
                <small class="text-muted">Review submitted student credentials, view PDF resumes, and progress candidate stages</small>
            </div>
            <div class="d-flex gap-2 mt-2 mt-md-0">
                <input type="text" class="form-control form-control-sm" placeholder="Live search applicant..." data-table-search="recruiterTable">
                <a href="<?= BASE_URL ?>admin/export_excel.php?type=company_applicants" class="btn btn-sm btn-outline-success text-nowrap">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Excel
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($applicants)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="recruiterTable">
                        <thead class="table-light">
                            <tr>
                                <th>Candidate</th>
                                <th>Branch & Scores</th>
                                <th>Role Applied</th>
                                <th>PDF Resume</th>
                                <th>Current Stage</th>
                                <th>Interview Remarks</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applicants as $app): ?>
                                <tr>
                                    <td>
                                        <strong class="d-block text-dark"><?= e($app['first_name'] . ' ' . $app['last_name']) ?></strong>
                                        <small class="text-muted"><?= e($app['enrollment_no']) ?> | <?= e($app['student_email']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= e($app['department']) ?></span>
                                        <div class="small mt-1">
                                            CGPA: <strong><?= number_format((float)($app['cgpa'] ?? 0), 2) ?></strong> | 
                                            Att: <strong><?= number_format((float)($app['attendance_percentage'] ?? 0), 1) ?>%</strong>
                                        </div>
                                    </td>
                                    <td>
                                        <strong class="text-dark"><?= e($app['job_title']) ?></strong>
                                        <small class="d-block text-success">₹ <?= number_format((float)$app['package_lpa'], 2) ?> LPA</small>
                                    </td>
                                    <td>
                                        <?php if (!empty($app['resume_path']) && file_exists(__DIR__ . '/../' . $app['resume_path'])): ?>
                                            <a href="<?= e(BASE_URL . $app['resume_path']) ?>" target="_blank" class="btn btn-sm btn-outline-danger py-0 px-2 fw-semibold" title="View PDF Resume">
                                                <i class="bi bi-file-earmark-pdf-fill me-1"></i> View Resume
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted">No File</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= getStatusBadge($app['status']) ?></td>
                                    <td><small class="text-muted"><?= e($app['admin_notes'] ?? 'None') ?></small></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#stageModal<?= $app['id'] ?>">
                                            <i class="bi bi-sliders me-1"></i> Update Stage
                                        </button>
                                    </td>
                                </tr>

                                <!-- Move Stage Modal -->
                                <div class="modal fade" id="stageModal<?= $app['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="<?= BASE_URL ?>company/dashboard.php" method="POST">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="update_status" value="1">
                                                <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title fw-bold">Update Candidate Stage: <?= e($app['first_name'] . ' ' . $app['last_name']) ?></h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Recruitment Stage *</label>
                                                        <select name="status" class="form-select form-select-lg">
                                                            <option value="Applied" <?= $app['status'] === 'Applied' ? 'selected' : '' ?>>Applied (Under Review)</option>
                                                            <option value="Shortlisted" <?= $app['status'] === 'Shortlisted' ? 'selected' : '' ?>>Shortlisted (Profile Cleared)</option>
                                                            <option value="Interview" <?= $app['status'] === 'Interview' ? 'selected' : '' ?>>Interview Scheduled</option>
                                                            <option value="Selected" <?= $app['status'] === 'Selected' ? 'selected' : '' ?>>Selected (Hire Confirmed)</option>
                                                            <option value="Rejected" <?= $app['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Interview Schedule & Recruiter Remarks</label>
                                                        <textarea name="recruiter_notes" class="form-control" rows="3" placeholder="e.g. Technical Round on 28th Oct at 11:00 AM via Google Meet / Venue Hall A"><?= e($app['admin_notes'] ?? '') ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary fw-bold">Save Candidate Stage</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox display-4 text-secondary mb-3 d-block"></i>
                    <h5 class="fw-bold text-dark">No Candidate Applications Yet</h5>
                    <p class="small text-muted mb-0">Eligible students will show up here as soon as they apply to your placement drives.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: POST NEW RECRUITMENT DRIVE (FIXED SCROLL & STICKY FOOTER) -->
<!-- ============================================================== -->
<div class="modal fade" id="createDriveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="max-height: 90vh;">
            
            <div class="modal-header bg-dark text-white py-3 px-4 flex-shrink-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-briefcase-fill text-warning me-2"></i>Post New Recruitment Drive
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Form wraps body and sticky footer -->
            <form action="<?= BASE_URL ?>company/dashboard.php" method="POST" class="d-flex flex-column flex-grow-1 overflow-hidden needs-validation">
                <?= csrfField() ?>
                <input type="hidden" name="create_company_drive" value="1">

                <!-- Scrollable Body Container -->
                <div class="modal-body p-4 overflow-y-auto" style="flex: 1 1 auto;">
                    <div class="alert alert-info border-0 shadow-sm small mb-4 py-2 px-3">
                        <i class="bi bi-info-circle-fill me-1"></i> Specify job details and eligibility thresholds. Eligible students from the selected branches will be notified.
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Job Title / Designation *</label>
                            <input type="text" name="job_title" class="form-control form-control-lg" placeholder="e.g. Graduate Engineer Trainee, Software Dev" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Annual Package (LPA) *</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light">₹</span>
                                <input type="number" step="0.1" name="package_lpa" class="form-control" placeholder="e.g. 5.5" required>
                                <span class="input-group-text bg-light">LPA</span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Job Work Location *</label>
                            <input type="text" name="job_location" class="form-control" placeholder="e.g. Pune, Bangalore, Delhi, Hybrid" value="On-Site / Hybrid" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-primary">Eligible Engineering Branches *</label>
                            <select name="allowed_branches" id="branchSelector" class="form-select border-primary" onchange="filterSkillsByBranch(this.value)">
                                <option value="All">All Engineering Branches (Open to All)</option>
                                <option value="Computer Engineering">Computer Engineering</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Computer Engineering, Information Technology">Computer + IT Combined</option>
                                <option value="Electronics & Communication">Electronics & Communication (ECE)</option>
                                <option value="Mechanical Engineering">Mechanical Engineering</option>
                                <option value="Civil Engineering">Civil Engineering</option>
                                <option value="Electrical Engineering">Electrical Engineering</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Job Description & Hiring Process Overview *</label>
                        <textarea name="job_description" class="form-control" rows="3" placeholder="Outline job responsibilities, skills desired, training period, and interview stages..." required></textarea>
                    </div>

                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-shield-lock-fill text-primary me-1"></i> Candidate Academic Cutoffs
                    </h6>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Minimum CGPA (0 - 10)</label>
                            <input type="number" step="0.1" min="0" max="10" name="min_cgpa" class="form-control" value="6.0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Min Official Attendance (%)</label>
                            <input type="number" step="1" min="0" max="100" name="min_attendance" class="form-control" value="75" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Max Allowed Standing Backlogs</label>
                            <input type="number" min="0" name="max_backlogs" class="form-control" value="0" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-danger">Application Deadline Date *</label>
                            <input type="date" name="deadline" class="form-control" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-success">Interview / Drive Date</label>
                            <input type="date" name="drive_date" class="form-control" min="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <!-- DYNAMIC BRANCH-WISE SKILL SELECTION -->
                    <div class="border rounded-3 p-3 bg-light mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0 text-primary">
                                <i class="bi bi-tags-fill me-1"></i> Technical Skills Relevant to Selected Branch
                            </h6>
                            <span class="badge bg-primary" id="selectedBranchBadge">All Branches Skills</span>
                        </div>
                        <small class="text-muted d-block mb-3">Select the core technical skills that candidates should have for this position:</small>

                        <!-- CS / IT Skills Section -->
                        <div class="skill-group-box mb-3" id="skillGroup_cs">
                            <div class="fw-bold small text-dark mb-1"><i class="bi bi-laptop me-1 text-primary"></i> Computer / IT & Software Development:</div>
                            <div class="row g-2">
                                <?php foreach ($skillsByBranch['cs'] as $sk): ?>
                                    <div class="col-sm-6 col-md-4">
                                        <div class="form-check bg-white p-2 rounded border">
                                            <input class="form-check-input ms-0 me-2" type="checkbox" name="skills[]" value="<?= $sk['id'] ?>" id="csk_<?= $sk['id'] ?>">
                                            <label class="form-check-label small fw-semibold" for="csk_<?= $sk['id'] ?>">
                                                <?= e($sk['skill_name']) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Mechanical Skills Section -->
                        <div class="skill-group-box mb-3" id="skillGroup_mech">
                            <div class="fw-bold small text-dark mb-1"><i class="bi bi-gear-wide-connected me-1 text-danger"></i> Mechanical Engineering Skills:</div>
                            <div class="row g-2">
                                <?php foreach ($skillsByBranch['mech'] as $sk): ?>
                                    <div class="col-sm-6 col-md-4">
                                        <div class="form-check bg-white p-2 rounded border">
                                            <input class="form-check-input ms-0 me-2" type="checkbox" name="skills[]" value="<?= $sk['id'] ?>" id="csk_<?= $sk['id'] ?>">
                                            <label class="form-check-label small fw-semibold" for="csk_<?= $sk['id'] ?>">
                                                <?= e($sk['skill_name']) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Civil Skills Section -->
                        <div class="skill-group-box mb-3" id="skillGroup_civil">
                            <div class="fw-bold small text-dark mb-1"><i class="bi bi-building me-1 text-success"></i> Civil Engineering Skills:</div>
                            <div class="row g-2">
                                <?php foreach ($skillsByBranch['civil'] as $sk): ?>
                                    <div class="col-sm-6 col-md-4">
                                        <div class="form-check bg-white p-2 rounded border">
                                            <input class="form-check-input ms-0 me-2" type="checkbox" name="skills[]" value="<?= $sk['id'] ?>" id="csk_<?= $sk['id'] ?>">
                                            <label class="form-check-label small fw-semibold" for="csk_<?= $sk['id'] ?>">
                                                <?= e($sk['skill_name']) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Electronics & Communication Section -->
                        <div class="skill-group-box mb-3" id="skillGroup_ece">
                            <div class="fw-bold small text-dark mb-1"><i class="bi bi-cpu me-1 text-warning"></i> Electronics & Hardware Design:</div>
                            <div class="row g-2">
                                <?php foreach ($skillsByBranch['ece'] as $sk): ?>
                                    <div class="col-sm-6 col-md-4">
                                        <div class="form-check bg-white p-2 rounded border">
                                            <input class="form-check-input ms-0 me-2" type="checkbox" name="skills[]" value="<?= $sk['id'] ?>" id="csk_<?= $sk['id'] ?>">
                                            <label class="form-check-label small fw-semibold" for="csk_<?= $sk['id'] ?>">
                                                <?= e($sk['skill_name']) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Electrical Section -->
                        <?php if (!empty($skillsByBranch['elec'])): ?>
                            <div class="skill-group-box mb-3" id="skillGroup_elec">
                                <div class="fw-bold small text-dark mb-1"><i class="bi bi-lightning-charge me-1 text-warning"></i> Electrical Automation & Power:</div>
                                <div class="row g-2">
                                    <?php foreach ($skillsByBranch['elec'] as $sk): ?>
                                        <div class="col-sm-6 col-md-4">
                                            <div class="form-check bg-white p-2 rounded border">
                                                <input class="form-check-input ms-0 me-2" type="checkbox" name="skills[]" value="<?= $sk['id'] ?>" id="csk_<?= $sk['id'] ?>">
                                                <label class="form-check-label small fw-semibold" for="csk_<?= $sk['id'] ?>">
                                                    <?= e($sk['skill_name']) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- General & Other Technical Skills -->
                        <?php if (!empty($skillsByBranch['other'])): ?>
                            <div class="skill-group-box" id="skillGroup_other">
                                <div class="fw-bold small text-dark mb-1"><i class="bi bi-tools me-1 text-secondary"></i> Core Engineering & Tools:</div>
                                <div class="row g-2">
                                    <?php foreach ($skillsByBranch['other'] as $sk): ?>
                                        <div class="col-sm-6 col-md-4">
                                            <div class="form-check bg-white p-2 rounded border">
                                                <input class="form-check-input ms-0 me-2" type="checkbox" name="skills[]" value="<?= $sk['id'] ?>" id="csk_<?= $sk['id'] ?>">
                                                <label class="form-check-label small fw-semibold" for="csk_<?= $sk['id'] ?>">
                                                    <?= e($sk['skill_name']) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- STICKY MODAL FOOTER (ALWAYS VISIBLE WITH EASY CLICK) -->
                <div class="modal-footer bg-white border-top py-3 px-4 flex-shrink-0 d-flex justify-content-between align-items-center" style="position: sticky; bottom: 0; z-index: 1055; box-shadow: 0 -4px 12px rgba(0,0,0,0.06);">
                    <div class="small text-muted">
                        <i class="bi bi-check-circle-fill text-success me-1"></i> Form ready to submit
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-lg fw-bold px-4 py-2 shadow-sm">
                            <i class="bi bi-send-check-fill me-1"></i> Publish Placement Drive
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
function filterSkillsByBranch(branch) {
    const badge = document.getElementById('selectedBranchBadge');
    const groupCs = document.getElementById('skillGroup_cs');
    const groupMech = document.getElementById('skillGroup_mech');
    const groupCivil = document.getElementById('skillGroup_civil');
    const groupEce = document.getElementById('skillGroup_ece');
    const groupElec = document.getElementById('skillGroup_elec');
    const groupOther = document.getElementById('skillGroup_other');

    if (badge) badge.textContent = branch;

    // Reset visibility
    const allGroups = [groupCs, groupMech, groupCivil, groupEce, groupElec, groupOther].filter(Boolean);

    if (branch === 'All') {
        allGroups.forEach(g => g.style.display = 'block');
    } else if (branch.includes('Computer') || branch.includes('Information')) {
        allGroups.forEach(g => g.style.display = 'none');
        if (groupCs) groupCs.style.display = 'block';
        if (groupOther) groupOther.style.display = 'block';
    } else if (branch.includes('Mechanical')) {
        allGroups.forEach(g => g.style.display = 'none');
        if (groupMech) groupMech.style.display = 'block';
        if (groupOther) groupOther.style.display = 'block';
    } else if (branch.includes('Civil')) {
        allGroups.forEach(g => g.style.display = 'none');
        if (groupCivil) groupCivil.style.display = 'block';
        if (groupOther) groupOther.style.display = 'block';
    } else if (branch.includes('Electronics') || branch.includes('Communication')) {
        allGroups.forEach(g => g.style.display = 'none');
        if (groupEce) groupEce.style.display = 'block';
        if (groupOther) groupOther.style.display = 'block';
    } else if (branch.includes('Electrical')) {
        allGroups.forEach(g => g.style.display = 'none');
        if (groupElec) groupElec.style.display = 'block';
        if (groupOther) groupOther.style.display = 'block';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
