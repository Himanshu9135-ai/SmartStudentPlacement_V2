<?php
/**
 * PlacementIndia Style Talent & Candidate Search Engine for Corporate Recruiters
 * Smart Student Management & Placement Portal (V2)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireCompany();

$companyId = (int)($_SESSION['company_id'] ?? 0);
$compName = $_SESSION['company_name'] ?? 'Company Recruiter';

// Fetch company information
$stmtComp = $pdo->prepare("SELECT * FROM companies WHERE id = :id LIMIT 1");
$stmtComp->execute([':id' => $companyId]);
$company = $stmtComp->fetch();

// Fetch all available skills for multi-select filter
$allSkills = $pdo->query("SELECT id, skill_name, category FROM skills ORDER BY skill_name ASC")->fetchAll();

// Fetch distinct departments / engineering branches
$branches = $pdo->query("SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department != '' ORDER BY department ASC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($branches)) {
    $branches = [
        'Computer Engineering',
        'Information Technology',
        'Mechanical Engineering',
        'Civil Engineering',
        'Electronics & Communication',
        'Electrical Engineering'
    ];
}

// Search & Filter parameters
$search_q       = trim($_GET['q'] ?? '');
$branch_filter  = trim($_GET['branch'] ?? '');
$min_cgpa       = isset($_GET['min_cgpa']) && is_numeric($_GET['min_cgpa']) ? (float)$_GET['min_cgpa'] : 0.0;
$max_backlogs   = isset($_GET['max_backlogs']) && $_GET['max_backlogs'] !== '' ? (int)$_GET['max_backlogs'] : '';
$selected_skills = isset($_GET['skills']) && is_array($_GET['skills']) ? array_map('intval', $_GET['skills']) : [];
$has_resume_only = isset($_GET['has_resume']) && $_GET['has_resume'] == '1';

// Build Query
$sql = "
    SELECT 
        s.id as student_id,
        s.enrollment_no,
        s.first_name,
        s.last_name,
        s.phone,
        s.department,
        s.semester,
        s.gender,
        s.resume_path,
        u.email,
        ar.cgpa,
        ar.attendance_percentage,
        ar.active_backlogs,
        ar.tenth_percentage,
        ar.twelfth_percentage
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    WHERE u.status = 'active'
";

$params = [];

if (!empty($search_q)) {
    $sql .= " AND (s.first_name LIKE :sq OR s.last_name LIKE :sq OR s.enrollment_no LIKE :sq OR u.email LIKE :sq)";
    $params[':sq'] = "%{$search_q}%";
}

if (!empty($branch_filter) && $branch_filter !== 'All') {
    $sql .= " AND s.department = :dept";
    $params[':dept'] = $branch_filter;
}

if ($min_cgpa > 0) {
    $sql .= " AND COALESCE(ar.cgpa, 0) >= :min_cgpa";
    $params[':min_cgpa'] = $min_cgpa;
}

if ($max_backlogs !== '') {
    $sql .= " AND COALESCE(ar.active_backlogs, 0) <= :max_backlogs";
    $params[':max_backlogs'] = (int)$max_backlogs;
}

if ($has_resume_only) {
    $sql .= " AND s.resume_path IS NOT NULL AND s.resume_path != ''";
}

// Order by highest CGPA first
$sql .= " ORDER BY COALESCE(ar.cgpa, 0) DESC, s.first_name ASC";

$stmtCandidates = $pdo->prepare($sql);
$stmtCandidates->execute($params);
$rawCandidates = $stmtCandidates->fetchAll();

// Fetch skills for all filtered candidates
$candidateSkillsMap = [];
if (!empty($rawCandidates)) {
    $studentIds = array_column($rawCandidates, 'student_id');
    $inPlaceholders = implode(',', array_fill(0, count($studentIds), '?'));
    $stmtSk = $pdo->prepare("
        SELECT ss.student_id, sk.id as skill_id, sk.skill_name, ss.proficiency
        FROM student_skills ss
        JOIN skills sk ON ss.skill_id = sk.id
        WHERE ss.student_id IN ($inPlaceholders)
        ORDER BY sk.skill_name ASC
    ");
    $stmtSk->execute($studentIds);
    $allMappedSkills = $stmtSk->fetchAll();
    foreach ($allMappedSkills as $ms) {
        $candidateSkillsMap[$ms['student_id']][] = $ms;
    }
}

// Filter candidates if specific skills were requested
$filteredCandidates = [];
foreach ($rawCandidates as $c) {
    $cSkills = $candidateSkillsMap[$c['student_id']] ?? [];
    $cSkillIds = array_column($cSkills, 'skill_id');
    $c['skills'] = $cSkills;

    if (!empty($selected_skills)) {
        // Must possess ALL selected skills (or at least one depending on requirement - let's check if candidate matches any or all)
        // PlacementIndia matches candidates who possess the required skills
        $matchedSkills = array_intersect($selected_skills, $cSkillIds);
        if (count($matchedSkills) < count($selected_skills)) {
            continue; // Doesn't match all requested skills
        }
    }
    $filteredCandidates[] = $c;
}

// Also get active drives for this company for direct invitation modal
$stmtMyDrives = $pdo->prepare("SELECT id, job_title, package_lpa FROM placement_drives WHERE company_id = ? AND status = 'Active' ORDER BY id DESC");
$stmtMyDrives->execute([$companyId]);
$companyActiveDrives = $stmtMyDrives->fetchAll();

$pageTitle = 'PlacementIndia Talent Search - ' . e($compName);
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

    <!-- Recruiter Header Banner -->
    <div class="rounded-4 mb-4 p-4 shadow-sm text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border: 1px solid #334155;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-warning text-dark px-3 py-1 fw-bold">
                        <i class="bi bi-person-bounding-box me-1"></i> Talent Discovery
                    </span>
                    <span class="badge bg-light text-dark px-2 py-1">
                        PlacementIndia Hub
                    </span>
                </div>
                <h2 class="fw-bold mb-1 text-white">Find & Shortlist Pre-Vetted Candidates</h2>
                <p class="mb-0 text-white-50" style="font-size: 14px;">
                    Search student resumes, evaluate academic performance, verify skills, and discover top campus talent for <strong><?= e($company['name']) ?></strong>.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>company/dashboard.php" class="btn btn-outline-light btn-sm px-3 py-2">
                    <i class="bi bi-arrow-left me-1"></i> Recruiter Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="<?= BASE_URL ?>company/search_candidates.php" class="row g-3 align-items-end">
                
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="bi bi-search me-1"></i> Keyword / Student Name / Email</label>
                    <input type="text" name="q" class="form-control" placeholder="e.g. Rahul, Enrollment No, Email..." value="<?= e($search_q) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="bi bi-mortarboard me-1"></i> Branch / Specialization</label>
                    <select name="branch" class="form-select">
                        <option value="All">All Engineering Branches</option>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?= e($b) ?>" <?= $branch_filter === $b ? 'selected' : '' ?>><?= e($b) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="bi bi-award me-1"></i> Minimum CGPA</label>
                    <select name="min_cgpa" class="form-select">
                        <option value="0" <?= $min_cgpa == 0 ? 'selected' : '' ?>>Any CGPA</option>
                        <option value="6.0" <?= $min_cgpa == 6.0 ? 'selected' : '' ?>>6.0+ CGPA</option>
                        <option value="7.0" <?= $min_cgpa == 7.0 ? 'selected' : '' ?>>7.0+ CGPA</option>
                        <option value="7.5" <?= $min_cgpa == 7.5 ? 'selected' : '' ?>>7.5+ CGPA</option>
                        <option value="8.0" <?= $min_cgpa == 8.0 ? 'selected' : '' ?>>8.0+ CGPA</option>
                        <option value="8.5" <?= $min_cgpa == 8.5 ? 'selected' : '' ?>>8.5+ CGPA</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="bi bi-shield-check me-1"></i> Standing Backlogs</label>
                    <select name="max_backlogs" class="form-select">
                        <option value="" <?= $max_backlogs === '' ? 'selected' : '' ?>>Any Backlog History</option>
                        <option value="0" <?= $max_backlogs === 0 ? 'selected' : '' ?>>Zero Backlogs (Strict)</option>
                        <option value="1" <?= $max_backlogs === 1 ? 'selected' : '' ?>>Max 1 Backlog</option>
                        <option value="2" <?= $max_backlogs === 2 ? 'selected' : '' ?>>Max 2 Backlogs</option>
                    </select>
                </div>

                <!-- Skill Filter Accordion / Multi-Select -->
                <div class="col-12 mt-2">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="bi bi-code-slash me-1"></i> Filter by Required Skills (Must Match All Checked)</label>
                    <div class="d-flex flex-wrap gap-2 p-2 bg-light rounded-3 border">
                        <?php foreach ($allSkills as $sk): ?>
                            <?php $checked = in_array((int)$sk['id'], $selected_skills, true); ?>
                            <div class="form-check form-check-inline m-0">
                                <input class="form-check-input" type="checkbox" name="skills[]" value="<?= $sk['id'] ?>" id="sk_<?= $sk['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="sk_<?= $sk['id'] ?>"><?= e($sk['skill_name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-md-8 d-flex align-items-center gap-3">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="has_resume" value="1" id="has_resume" <?= $has_resume_only ? 'checked' : '' ?>>
                        <label class="form-check-label small fw-bold text-dark" for="has_resume">Show only profiles with uploaded PDF resume</label>
                    </div>
                </div>

                <div class="col-md-4 text-end">
                    <a href="<?= BASE_URL ?>company/search_candidates.php" class="btn btn-outline-secondary btn-sm px-3 me-2">Reset</a>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">
                        <i class="bi bi-funnel-fill me-1"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Candidate Count & Results Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-dark mb-0">
            Available Candidates (<?= count($filteredCandidates) ?> matches found)
        </h5>
        <small class="text-muted">Ranked by CGPA & Academic Excellence</small>
    </div>

    <!-- Candidate Cards Grid -->
    <?php if (!empty($filteredCandidates)): ?>
        <div class="row g-4">
            <?php foreach ($filteredCandidates as $cand): ?>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 50px; height: 50px; background: linear-gradient(135deg, #2563eb, #1e40af); font-size: 18px;">
                                    <?= strtoupper(substr($cand['first_name'], 0, 1) . substr($cand['last_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark"><?= e($cand['first_name'] . ' ' . $cand['last_name']) ?></h5>
                                    <small class="text-muted"><i class="bi bi-card-heading me-1"></i><?= e($cand['enrollment_no']) ?> &bull; <?= e($cand['department']) ?></small>
                                </div>
                            </div>
                            <span class="badge bg-primary text-white fs-6 px-3 py-1">
                                <?= number_format((float)($cand['cgpa'] ?? 0), 2) ?> CGPA
                            </span>
                        </div>

                        <!-- Academic Details Pills -->
                        <div class="bg-light p-2 rounded-3 my-2 small">
                            <div class="row g-1 text-center">
                                <div class="col-4 border-end">
                                    <span class="text-muted d-block" style="font-size: 11px;">ATTENDANCE</span>
                                    <strong class="<?= ($cand['attendance_percentage'] ?? 0) >= 75 ? 'text-success' : 'text-danger' ?>">
                                        <?= number_format((float)($cand['attendance_percentage'] ?? 0), 1) ?>%
                                    </strong>
                                </div>
                                <div class="col-4 border-end">
                                    <span class="text-muted d-block" style="font-size: 11px;">BACKLOGS</span>
                                    <strong class="<?= ($cand['active_backlogs'] ?? 0) == 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= (int)($cand['active_backlogs'] ?? 0) ?> Active
                                    </strong>
                                </div>
                                <div class="col-4">
                                    <span class="text-muted d-block" style="font-size: 11px;">SEMESTER</span>
                                    <strong class="text-dark">Sem <?= (int)($cand['semester'] ?? 1) ?></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Candidate Skills Tags -->
                        <div class="my-2">
                            <span class="text-muted small fw-bold d-block mb-1">Key Skills & Proficiencies:</span>
                            <div class="d-flex flex-wrap gap-1">
                                <?php if (!empty($cand['skills'])): ?>
                                    <?php foreach ($cand['skills'] as $sk): ?>
                                        <span class="badge bg-light text-dark border px-2 py-1">
                                            <?= e($sk['skill_name']) ?> <small class="text-primary">(<?= e($sk['proficiency']) ?>)</small>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted small">No verified skills listed.</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Contact & Resume Actions -->
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                            <div class="small text-muted">
                                <i class="bi bi-envelope me-1"></i><?= e($cand['email']) ?>
                            </div>
                            <div class="d-flex gap-2">
                                <?php if (!empty($cand['resume_path'])): ?>
                                    <a href="<?= BASE_URL ?>uploads/resumes/<?= e($cand['resume_path']) ?>" target="_blank" class="btn btn-outline-danger btn-sm px-3 fw-bold">
                                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> View Resume
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm px-3" disabled title="Resume not uploaded yet">
                                        <i class="bi bi-file-earmark-x me-1"></i> No Resume
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
            <i class="bi bi-person-x text-muted display-3 mb-3"></i>
            <h4 class="fw-bold text-dark">No Candidates Found</h4>
            <p class="text-muted">No student profiles match your specific CGPA, branch, or skill criteria. Try broadening your filter parameters.</p>
            <a href="<?= BASE_URL ?>company/search_candidates.php" class="btn btn-primary btn-sm mx-auto px-4">
                Clear Filters
            </a>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
