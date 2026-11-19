<?php
/**
 * Toggle Save / Bookmark Placement Drive
 * Smart Student Management & Placement Portal (V2)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireStudent();

$student = getCurrentStudent($pdo);
$studentId = $student['id'];

// CSRF validation
if (!validateCsrfToken()) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'CSRF token mismatch']);
        exit;
    }
    setFlash('danger', 'Security validation failed (Invalid CSRF token).');
    header('Location: ' . BASE_URL . 'student/drives.php');
    exit;
}

$driveId = (int)($_POST['drive_id'] ?? 0);
$redirect = $_POST['redirect'] ?? (BASE_URL . 'student/drives.php');

if ($driveId <= 0) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid Drive ID']);
        exit;
    }
    setFlash('danger', 'Invalid placement drive specified.');
    header('Location: ' . $redirect);
    exit;
}

try {
    // Check if table exists, create if not present yet
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

    $check = $pdo->prepare("SELECT id FROM saved_jobs WHERE student_id = ? AND drive_id = ?");
    $check->execute([$studentId, $driveId]);
    $savedRecord = $check->fetch();

    if ($savedRecord) {
        // Remove from saved jobs
        $delete = $pdo->prepare("DELETE FROM saved_jobs WHERE student_id = ? AND drive_id = ?");
        $delete->execute([$studentId, $driveId]);
        $isSaved = false;
        $msg = 'Job removed from your saved list.';
    } else {
        // Add to saved jobs
        $insert = $pdo->prepare("INSERT INTO saved_jobs (student_id, drive_id) VALUES (?, ?)");
        $insert->execute([$studentId, $driveId]);
        $isSaved = true;
        $msg = 'Job saved to your bookmarks successfully!';
    }

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'is_saved' => $isSaved, 'message' => $msg]);
        exit;
    }

    setFlash('success', $msg);
    header('Location: ' . $redirect);
    exit;

} catch (PDOException $e) {
    error_log("Save job error: " . $e->getMessage());
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        exit;
    }
    setFlash('danger', 'Unable to bookmark drive at this moment.');
    header('Location: ' . $redirect);
    exit;
}
