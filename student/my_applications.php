<?php
/**
 * Student Applications Tracker
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireStudent();

$student = getCurrentStudent($pdo);
$studentId = $student['id'];

// Fetch all applications for current student
$stmt = $pdo->prepare("
    SELECT a.*, d.job_title, d.package_lpa, d.drive_date, d.job_location, 
           c.name as company_name, c.website as company_website
    FROM applications a
    JOIN placement_drives d ON a.drive_id = d.id
    JOIN companies c ON d.company_id = c.id
    WHERE a.student_id = :sid
    ORDER BY a.applied_at DESC
");
$stmt->execute([':sid' => $studentId]);
$applications = $stmt->fetchAll();

$pageTitle = 'My Placement Applications';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_student.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-send-check-fill text-primary me-2"></i>My Job Applications</h3>
                    <p class="text-muted mb-0">Track real-time progress and remarks across your submitted recruitment drives</p>
                </div>
                <a href="<?= BASE_URL ?>student/drives.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Browse More Drives
                </a>
            </div>

            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="card card-portal">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-list-check text-primary me-2"></i>Application History (<?= count($applications) ?>)</h5>
                    <div class="w-25">
                        <input type="text" class="form-control form-control-sm" placeholder="Search applications..." data-table-search="myAppsTable">
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($applications)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-portal align-middle mb-0" id="myAppsTable">
                                <thead>
                                    <tr>
                                        <th>Application ID</th>
                                        <th>Company & Designation</th>
                                        <th>Offered Package</th>
                                        <th>Applied On</th>
                                        <th>Current Stage</th>
                                        <th>TPO Remarks / Updates</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($applications as $app): ?>
                                        <tr>
                                            <td><span class="text-muted small fw-bold">#APP-<?= str_pad($app['id'], 4, '0', STR_PAD_LEFT) ?></span></td>
                                            <td>
                                                <strong class="d-block text-dark"><?= e($app['job_title']) ?></strong>
                                                <span class="badge bg-light text-dark"><i class="bi bi-building me-1"></i><?= e($app['company_name']) ?></span>
                                            </td>
                                            <td>
                                                <strong class="text-success">₹ <?= number_format((float)$app['package_lpa'], 2) ?> LPA</strong>
                                            </td>
                                            <td><?= formatPortalDate($app['applied_at']) ?></td>
                                            <td><?= getStatusBadge($app['status']) ?></td>
                                            <td>
                                                <?php if (!empty($app['admin_notes'])): ?>
                                                    <div class="p-2 bg-light rounded small border">
                                                        <i class="bi bi-chat-left-dots text-primary me-1"></i>
                                                        <?= e($app['admin_notes']) ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small italic">Under TPO review</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= BASE_URL ?>student/drive_details.php?id=<?= $app['drive_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> View Drive
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox display-4 d-block mb-3 text-secondary"></i>
                            <h5 class="fw-bold text-dark">No Applications Found</h5>
                            <p class="small text-muted mb-3">You have not submitted applications to any campus placement drives yet.</p>
                            <a href="<?= BASE_URL ?>student/drives.php" class="btn btn-primary btn-sm">
                                <i class="bi bi-briefcase me-1"></i> Explore Available Drives
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
