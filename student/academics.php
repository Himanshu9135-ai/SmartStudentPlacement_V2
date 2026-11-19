<?php
/**
 * Student Academic Records & Attendance View
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireStudent();

$student = getCurrentStudent($pdo);
$cgpa = (float)($student['cgpa'] ?? 0.00);
$attendance = (float)($student['attendance_percentage'] ?? 0.00);
$backlogs = (int)($student['active_backlogs'] ?? 0);

$pageTitle = 'Academic & Attendance Records';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_student.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-award-fill text-primary me-2"></i>Academic & Attendance Records</h3>
                    <p class="text-muted mb-0">Official verified scores used for placement drive eligibility checking</p>
                </div>
            </div>

            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Metrics Overview Cards -->
            <div class="row g-4 mb-4">
                <!-- CGPA Card -->
                <div class="col-md-4">
                    <div class="card card-portal h-100 border-start border-4 <?= $cgpa >= 7.5 ? 'border-success' : ($cgpa >= 6.0 ? 'border-primary' : 'border-danger') ?>">
                        <div class="card-body p-4">
                            <span class="text-muted text-uppercase fw-bold small">Overall Diploma CGPA</span>
                            <div class="d-flex align-items-baseline mt-2 mb-2">
                                <h2 class="fw-bold mb-0 me-2"><?= number_format($cgpa, 2) ?></h2>
                                <span class="text-muted">/ 10.00</span>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar <?= $cgpa >= 7.5 ? 'bg-success' : ($cgpa >= 6.0 ? 'bg-primary' : 'bg-danger') ?>" style="width: <?= min(100, $cgpa * 10) ?>%;"></div>
                            </div>
                            <small class="text-muted">
                                <?= $cgpa >= 7.5 ? '<i class="bi bi-check-circle-fill text-success me-1"></i> Eligible for First Class / Top Tier Drives' : '<i class="bi bi-info-circle text-primary me-1"></i> Standard Eligibility Range' ?>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Attendance Card -->
                <div class="col-md-4">
                    <div class="card card-portal h-100 border-start border-4 <?= $attendance >= 75.0 ? 'border-success' : 'border-danger' ?>">
                        <div class="card-body p-4">
                            <span class="text-muted text-uppercase fw-bold small">Attendance Percentage</span>
                            <div class="d-flex align-items-baseline mt-2 mb-2">
                                <h2 class="fw-bold mb-0 me-2"><?= number_format($attendance, 1) ?>%</h2>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar <?= $attendance >= 75.0 ? 'bg-success' : 'bg-danger' ?>" style="width: <?= min(100, $attendance) ?>%;"></div>
                            </div>
                            <small class="text-muted">
                                <?= $attendance >= 75.0 ? '<i class="bi bi-check-circle-fill text-success me-1"></i> Meets minimum campus placement quota (75%)' : '<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> Attendance below standard placement cutoff (75%)' ?>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Backlog Status Card -->
                <div class="col-md-4">
                    <div class="card card-portal h-100 border-start border-4 <?= $backlogs === 0 ? 'border-success' : 'border-warning' ?>">
                        <div class="card-body p-4">
                            <span class="text-muted text-uppercase fw-bold small">Active Backlogs</span>
                            <div class="d-flex align-items-baseline mt-2 mb-2">
                                <h2 class="fw-bold mb-0 me-2 <?= $backlogs === 0 ? 'text-success' : 'text-danger' ?>"><?= $backlogs ?></h2>
                                <span class="text-muted">Standing Subject(s)</span>
                            </div>
                            <div class="mt-3">
                                <?php if ($backlogs === 0): ?>
                                    <span class="badge bg-success text-white px-3 py-2"><i class="bi bi-check-all me-1"></i> Clean Academic Record (0 Backlogs)</span>
                                <?php else: ?>
                                    <span class="badge bg-danger text-white px-3 py-2"><i class="bi bi-exclamation-circle me-1"></i> <?= $backlogs ?> Active Backlog(s)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Breakdown Card -->
            <div class="card card-portal">
                <div class="card-header">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-table me-2 text-primary"></i>Academic History</h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-bordered table-portal align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Metric / Record</th>
                                    <th>Score / Value</th>
                                    <th>Status / Verification</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>10th / Secondary Percentage</strong></td>
                                    <td><?= number_format((float)($student['tenth_percentage'] ?? 0), 2) ?>%</td>
                                    <td><span class="badge bg-light text-dark"><i class="bi bi-check2 text-success me-1"></i> Recorded</span></td>
                                </tr>
                                <tr>
                                    <td><strong>12th / ITI Percentage</strong></td>
                                    <td><?= !empty($student['twelfth_percentage']) ? number_format((float)$student['twelfth_percentage'], 2) . '%' : 'N/A' ?></td>
                                    <td><span class="badge bg-light text-dark"><i class="bi bi-check2 text-success me-1"></i> Recorded</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Diploma Cumulative CGPA</strong></td>
                                    <td><strong class="text-primary fs-5"><?= number_format($cgpa, 2) ?></strong> / 10.00</td>
                                    <td><span class="badge bg-success text-white"><i class="bi bi-shield-check me-1"></i> Verified by TPO</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Official Attendance Tracking</strong></td>
                                    <td><strong class="<?= $attendance >= 75 ? 'text-success' : 'text-danger' ?> fs-5"><?= number_format($attendance, 1) ?>%</strong></td>
                                    <td><span class="badge bg-success text-white"><i class="bi bi-shield-check me-1"></i> Verified by Department</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-info d-flex align-items-center mb-0 mt-3">
                        <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                        <div>
                            <strong>Note for Students:</strong> Academic scores and attendance percentages are maintained and updated directly by the College Academic & Training & Placement Cell. If you notice any discrepancy, please contact your department placement coordinator.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
