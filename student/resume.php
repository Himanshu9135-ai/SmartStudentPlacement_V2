<?php
/**
 * Student Resume Upload & Management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireStudent();

$student = getCurrentStudent($pdo);
$studentId = $student['id'];
$existingResume = $student['resume_path'];

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        setFlash('danger', 'Security validation failed (Invalid CSRF token). Please try again.');
        header('Location: ' . BASE_URL . 'student/resume.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'upload_resume') {
        if (!isset($_FILES['resume_file']) || $_FILES['resume_file']['error'] !== UPLOAD_ERR_OK) {
            setFlash('danger', 'Please select a valid PDF file to upload.');
        } else {
            $file = $_FILES['resume_file'];
            $fileSize = $file['size'];
            $fileTmp  = $file['tmp_name'];
            $fileName = $file['name'];
            $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Max 2MB (2 * 1024 * 1024)
            $maxBytes = 2 * 1024 * 1024;

            // 1. Check Extension
            if ($fileExt !== 'pdf') {
                setFlash('danger', 'Invalid file type. Only PDF documents (.pdf) are allowed.');
            }
            // 2. Check Size
            elseif ($fileSize > $maxBytes) {
                setFlash('danger', 'File size exceeds 2MB limit. Please compress your PDF.');
            }
            // 3. Check Real MIME type
            else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $fileTmp);
                finfo_close($finfo);

                if ($mimeType !== 'application/pdf') {
                    setFlash('danger', 'Invalid file content. Uploaded file is not a legitimate PDF.');
                } else {
                    // Unique safe filename
                    $newFileName = 'resume_' . $student['enrollment_no'] . '_' . uniqid() . '.pdf';
                    $uploadDir   = __DIR__ . '/../uploads/resumes/';
                    $destPath    = $uploadDir . $newFileName;
                    $dbRelPath   = 'uploads/resumes/' . $newFileName;

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    if (move_uploaded_file($fileTmp, $destPath)) {
                        // Delete old file if exists
                        if (!empty($existingResume)) {
                            $oldFile = __DIR__ . '/../' . $existingResume;
                            if (file_exists($oldFile)) {
                                @unlink($oldFile);
                            }
                        }

                        // Update DB
                        $stmtUp = $pdo->prepare("UPDATE students SET resume_path = :path WHERE id = :sid");
                        $stmtUp->execute([':path' => $dbRelPath, ':sid' => $studentId]);

                        setFlash('success', 'Resume uploaded and verified successfully!');
                        header('Location: ' . BASE_URL . 'student/resume.php');
                        exit;
                    } else {
                        setFlash('danger', 'Failed to save uploaded file. Check server permissions.');
                    }
                }
            }
        }
        header('Location: ' . BASE_URL . 'student/resume.php');
        exit;
    } elseif ($action === 'delete_resume') {
        if (!empty($existingResume)) {
            $oldFile = __DIR__ . '/../' . $existingResume;
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
            $stmtDel = $pdo->prepare("UPDATE students SET resume_path = NULL WHERE id = :sid");
            $stmtDel->execute([':sid' => $studentId]);
            setFlash('info', 'Your uploaded resume has been removed.');
        }
        header('Location: ' . BASE_URL . 'student/resume.php');
        exit;
    }
}

// Refresh student record
$student = getCurrentStudent($pdo);
$hasResume = !empty($student['resume_path']) && file_exists(__DIR__ . '/../' . $student['resume_path']);

$pageTitle = 'Upload Resume';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_student.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-file-earmark-pdf-fill text-danger me-2"></i>My Resume (PDF)</h3>
                    <p class="text-muted mb-0">Upload your verified curriculum vitae for recruiter assessment</p>
                </div>
            </div>

            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="row g-4">
                <!-- Upload Box -->
                <div class="col-lg-5">
                    <div class="card card-portal">
                        <div class="card-header">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-cloud-arrow-up me-2 text-primary"></i><?= $hasResume ? 'Replace Resume' : 'Upload New Resume' ?></h5>
                        </div>
                        <div class="card-body p-4">
                            <form action="<?= BASE_URL ?>student/resume.php" method="POST" enctype="multipart/form-data" class="needs-validation">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="upload_resume">

                                <div class="mb-3">
                                    <label for="resume_file" class="form-label fw-semibold">Select PDF Document *</label>
                                    <input class="form-control" type="file" id="resume_file" name="resume_file" accept="application/pdf" required>
                                    <div class="invalid-feedback">Please choose a valid PDF file.</div>
                                    <small class="text-muted d-block mt-1">Maximum file size: <strong>2 MB</strong>. Only <strong>.pdf</strong> allowed.</small>
                                </div>

                                <div class="alert alert-light border p-3 small mb-4">
                                    <h6 class="fw-bold mb-1"><i class="bi bi-shield-check text-success me-1"></i> Resume Guidelines:</h6>
                                    <ul class="mb-0 ps-3 text-muted">
                                        <li>Include technical skills, diploma projects, and internships.</li>
                                        <li>Maintain clear contact details and GitHub / LinkedIn links.</li>
                                        <li>Ensure the document is not password-protected.</li>
                                    </ul>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 fw-bold">
                                    <i class="bi bi-upload me-1"></i> <?= $hasResume ? 'Upload & Replace Resume' : 'Upload Resume' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Preview / Status Box -->
                <div class="col-lg-7">
                    <div class="card card-portal h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-file-earmark-check me-2 text-primary"></i>Uploaded Document Status</h5>
                            <?php if ($hasResume): ?>
                                <div>
                                    <a href="<?= e(BASE_URL . $student['resume_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> Open in New Tab
                                    </a>
                                    <form action="<?= BASE_URL ?>student/resume.php" method="POST" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete_resume">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Are you sure you want to delete your current resume?">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                            <?php if ($hasResume): ?>
                                <div class="my-auto py-4">
                                    <i class="bi bi-file-earmark-pdf-fill text-danger display-1 mb-3"></i>
                                    <h5 class="fw-bold text-dark mb-1">Active Resume Attached</h5>
                                    <p class="text-muted small mb-4">
                                        Stored as: <code><?= basename($student['resume_path']) ?></code>
                                    </p>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="<?= e(BASE_URL . $student['resume_path']) ?>" download class="btn btn-success px-4 fw-bold">
                                            <i class="bi bi-download me-1"></i> Download PDF
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="my-auto py-5 text-muted">
                                    <i class="bi bi-file-earmark-x fs-1 d-block mb-3 text-secondary"></i>
                                    <h5 class="fw-bold text-dark mb-1">No Resume Uploaded Yet</h5>
                                    <p class="small text-muted mb-0">You cannot apply to placement drives until you upload your verified PDF resume.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
