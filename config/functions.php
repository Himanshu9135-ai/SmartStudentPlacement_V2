<?php
/**
 * Global Utility Functions & Business Logic Engine
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/database.php';

/**
 * Sanitize string output for safe HTML rendering (XSS Prevention)
 */
function e($string) {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if current user is admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if current user is student
 */
function isStudent() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

/**
 * Check if current user is company recruiter
 */
function isCompany() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'company';
}

/**
 * Guard: Require user to be logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please login to access this page.');
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
}

/**
 * Guard: Require admin role
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setFlash('danger', 'Access denied. Administrator privileges required.');
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
}

/**
 * Guard: Require student role
 */
function requireStudent() {
    requireLogin();
    if (!isStudent()) {
        setFlash('danger', 'Access denied. Student portal area only.');
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
}

/**
 * Guard: Require company recruiter role
 */
function requireCompany() {
    requireLogin();
    if (!isCompany()) {
        setFlash('danger', 'Access denied. Recruiter portal area only.');
        header('Location: ' . BASE_URL . 'auth/login.php?role=company');
        exit;
    }
}

/**
 * Get current logged in student record with academic details
 */
function getCurrentStudent($pdo) {
    if (!isStudent()) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT s.*, u.email, u.status as account_status,
               a.tenth_percentage, a.twelfth_percentage, a.cgpa, 
               a.active_backlogs, a.attendance_percentage
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN academic_records a ON s.id = a.student_id
        WHERE s.user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Get student's acquired skill IDs
 */
function getStudentSkillIds($pdo, $student_id) {
    $stmt = $pdo->prepare("SELECT skill_id FROM student_skills WHERE student_id = :student_id");
    $stmt->execute([':student_id' => $student_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Get drive's required skill IDs and names
 */
function getDriveRequiredSkills($pdo, $drive_id) {
    $stmt = $pdo->prepare("
        SELECT s.id, s.skill_name, s.category 
        FROM drive_skills ds
        JOIN skills s ON ds.skill_id = s.id
        WHERE ds.drive_id = :drive_id
    ");
    $stmt->execute([':drive_id' => $drive_id]);
    return $stmt->fetchAll();
}

/**
 * Check if a student has already applied for a drive
 */
function hasStudentApplied($pdo, $student_id, $drive_id) {
    $stmt = $pdo->prepare("SELECT id, status FROM applications WHERE student_id = :student_id AND drive_id = :drive_id LIMIT 1");
    $stmt->execute([':student_id' => $student_id, ':drive_id' => $drive_id]);
    return $stmt->fetch();
}

/**
 * Placement Eligibility Engine
 * Verifies:
 * 1. CGPA >= Min CGPA
 * 2. Attendance % >= Min Attendance %
 * 3. Active Backlogs <= Max Backlogs
 * 4. Branch eligibility
 * 5. Drive deadline not expired
 * 6. Required Skills match
 * 7. Resume uploaded
 * 
 * @return array ['is_eligible' => bool, 'reasons' => array, 'missing_skills' => array]
 */
function checkStudentEligibility($pdo, $student_id, $drive_id) {
    // 1. Fetch Student Details & Academics
    $stmtStudent = $pdo->prepare("
        SELECT s.*, a.cgpa, a.attendance_percentage, a.active_backlogs
        FROM students s
        LEFT JOIN academic_records a ON s.id = a.student_id
        WHERE s.id = :student_id
        LIMIT 1
    ");
    $stmtStudent->execute([':student_id' => $student_id]);
    $student = $stmtStudent->fetch();

    // 2. Fetch Drive Details
    $stmtDrive = $pdo->prepare("SELECT * FROM placement_drives WHERE id = :drive_id LIMIT 1");
    $stmtDrive->execute([':drive_id' => $drive_id]);
    $drive = $stmtDrive->fetch();

    if (!$student || !$drive) {
        return [
            'is_eligible' => false,
            'reasons' => ['Invalid student or drive record.'],
            'missing_skills' => []
        ];
    }

    $reasons = [];

    // Drive status check
    if ($drive['status'] !== 'Active') {
        $reasons[] = "Drive is currently " . $drive['status'] . " (Applications closed).";
    }

    // Deadline check
    $today = date('Y-m-d');
    if ($drive['deadline'] < $today) {
        $reasons[] = "Application deadline has passed (" . date('d M Y', strtotime($drive['deadline'])) . ").";
    }

    // CGPA check
    $studentCgpa = (float)($student['cgpa'] ?? 0);
    $minCgpa = (float)$drive['min_cgpa'];
    if ($studentCgpa < $minCgpa) {
        $reasons[] = "CGPA Requirement: Min {$minCgpa} required (Your CGPA: {$studentCgpa})";
    }

    // Attendance check
    $studentAtt = (float)($student['attendance_percentage'] ?? 0);
    $minAtt = (float)$drive['min_attendance'];
    if ($studentAtt < $minAtt) {
        $reasons[] = "Attendance Requirement: Min {$minAtt}% required (Your Attendance: {$studentAtt}%)";
    }

    // Backlog check
    $studentBacklogs = (int)($student['active_backlogs'] ?? 0);
    $maxBacklogs = (int)$drive['max_backlogs'];
    if ($studentBacklogs > $maxBacklogs) {
        $reasons[] = "Backlog Limit: Max {$maxBacklogs} allowed (You have {$studentBacklogs} standing backlog(s))";
    }

    // Branch check
    if ($drive['allowed_branches'] !== 'All') {
        $allowed = array_map('trim', explode(',', strtolower($drive['allowed_branches'])));
        $studentDept = strtolower(trim($student['department'] ?? ''));
        if (!in_array($studentDept, $allowed) && !in_array('all', $allowed)) {
            $reasons[] = "Branch Eligibility: Allowed for (" . $drive['allowed_branches'] . "), your branch is {$student['department']}";
        }
    }

    // Skills check
    $studentSkillIds = getStudentSkillIds($pdo, $student_id);
    $requiredSkills = getDriveRequiredSkills($pdo, $drive_id);
    $missingSkills = [];

    foreach ($requiredSkills as $req) {
        if (!in_array($req['id'], $studentSkillIds)) {
            $missingSkills[] = $req['skill_name'];
        }
    }

    if (!empty($missingSkills)) {
        $reasons[] = "Missing Required Skills: " . implode(', ', $missingSkills);
    }

    // Resume check
    if (empty($student['resume_path'])) {
        $reasons[] = "Resume Required: Please upload your PDF resume in your profile before applying.";
    }

    return [
        'is_eligible' => empty($reasons),
        'reasons' => $reasons,
        'missing_skills' => $missingSkills
    ];
}

/**
 * Get CSS Badge Class for Application Status
 */
function getStatusBadge($status) {
    switch ($status) {
        case 'Applied':
            return '<span class="badge bg-primary text-white"><i class="bi bi-send-fill me-1"></i> Applied</span>';
        case 'Shortlisted':
            return '<span class="badge bg-info text-dark"><i class="bi bi-list-check me-1"></i> Shortlisted</span>';
        case 'Interview':
            return '<span class="badge bg-warning text-dark"><i class="bi bi-calendar-event me-1"></i> Interview</span>';
        case 'Selected':
            return '<span class="badge bg-success text-white"><i class="bi bi-trophy-fill me-1"></i> Selected</span>';
        case 'Rejected':
            return '<span class="badge bg-danger text-white"><i class="bi bi-x-circle me-1"></i> Rejected</span>';
        default:
            return '<span class="badge bg-secondary">' . e($status) . '</span>';
    }
}

/**
 * Format standard readable dates
 */
function formatPortalDate($dateStr) {
    if (empty($dateStr)) return 'N/A';
    return date('d M Y', strtotime($dateStr));
}

/**
 * Check if a student has saved / bookmarked a drive
 */
function hasStudentSavedDrive($pdo, $studentId, $driveId) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM saved_jobs WHERE student_id = ? AND drive_id = ? LIMIT 1");
        $stmt->execute([$studentId, $driveId]);
        return (bool)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get count of saved drives for a student
 */
function getStudentSavedJobsCount($pdo, $studentId) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM saved_jobs WHERE student_id = ?");
        $stmt->execute([$studentId]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

