<?php
/**
 * Student Skills Management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireStudent();

$student = getCurrentStudent($pdo);
$studentId = $student['id'];

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        setFlash('danger', 'Security validation failed (Invalid CSRF token). Please try again.');
        header('Location: ' . BASE_URL . 'student/skills.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add_skill') {
        $skill_id    = (int)($_POST['skill_id'] ?? 0);
        $rawProf     = trim($_POST['proficiency'] ?? 'Intermediate');
        $proficiency = in_array($rawProf, ['Beginner', 'Intermediate', 'Advanced'], true) ? $rawProf : 'Intermediate';
        $custom_skill = trim(strip_tags($_POST['custom_skill'] ?? ''));

        // If student typed a custom skill name
        if ($skill_id === 0 && !empty($custom_skill)) {
            if (strlen($custom_skill) > 50) {
                setFlash('danger', 'Skill name cannot exceed 50 characters.');
                header('Location: ' . BASE_URL . 'student/skills.php');
                exit;
            }
            // Find or insert into skills table
            $stmtFind = $pdo->prepare("SELECT id FROM skills WHERE LOWER(skill_name) = LOWER(:sname) LIMIT 1");
            $stmtFind->execute([':sname' => $custom_skill]);
            $found = $stmtFind->fetch();

            if ($found) {
                $skill_id = $found['id'];
            } else {
                $stmtNewSkill = $pdo->prepare("INSERT INTO skills (skill_name, category) VALUES (:sname, 'Technical')");
                $stmtNewSkill->execute([':sname' => $custom_skill]);
                $skill_id = $pdo->lastInsertId();
            }
        }

        if ($skill_id > 0) {
            try {
                $stmtAdd = $pdo->prepare("
                    INSERT INTO student_skills (student_id, skill_id, proficiency) 
                    VALUES (:sid, :skid, :prof)
                    ON DUPLICATE KEY UPDATE proficiency = VALUES(proficiency)
                ");
                $stmtAdd->execute([
                    ':sid'  => $studentId,
                    ':skid' => $skill_id,
                    ':prof' => $proficiency
                ]);
                setFlash('success', 'Skill added / updated successfully!');
            } catch (PDOException $e) {
                error_log("Add skill error: " . $e->getMessage());
                setFlash('danger', 'Error adding skill due to a system problem.');
            }
        } else {
            setFlash('danger', 'Please select a valid skill or enter a new skill name.');
        }

        header('Location: ' . BASE_URL . 'student/skills.php');
        exit;
    } elseif ($action === 'delete_skill') {
        $map_id = (int)($_POST['map_id'] ?? 0);
        $stmtDel = $pdo->prepare("DELETE FROM student_skills WHERE id = :id AND student_id = :sid");
        $stmtDel->execute([':id' => $map_id, ':sid' => $studentId]);
        setFlash('info', 'Skill removed from your profile.');
        header('Location: ' . BASE_URL . 'student/skills.php');
        exit;
    }
}

// Fetch Master Skills for Dropdown
$allMasterSkills = $pdo->query("SELECT * FROM skills ORDER BY skill_name ASC")->fetchAll();

// Fetch Current Student Skills
$stmtStudentSkills = $pdo->prepare("
    SELECT ss.id as map_id, ss.proficiency, s.id as skill_id, s.skill_name, s.category
    FROM student_skills ss
    JOIN skills s ON ss.skill_id = s.id
    WHERE ss.student_id = :sid
    ORDER BY s.skill_name ASC
");
$stmtStudentSkills->execute([':sid' => $studentId]);
$mySkills = $stmtStudentSkills->fetchAll();
$mySkillIds = array_column($mySkills, 'skill_id');

$pageTitle = 'Manage My Skills';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_student.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-code-slash text-primary me-2"></i>My Technical Skills</h3>
                    <p class="text-muted mb-0">Highlight your technical proficiencies to match with company requirements</p>
                </div>
            </div>

            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="row g-4">
                <!-- Add Skill Form Card -->
                <div class="col-lg-5">
                    <div class="card card-portal">
                        <div class="card-header">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Add Technical Skill</h5>
                        </div>
                        <div class="card-body p-4">
                            <form action="<?= BASE_URL ?>student/skills.php" method="POST">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="add_skill">

                                <div class="mb-3">
                                    <label for="skill_id" class="form-label fw-semibold">Choose From Skill Catalog</label>
                                    <select class="form-select" id="skill_id" name="skill_id">
                                        <option value="0">-- Select Existing Skill or Type Below --</option>
                                        <?php foreach ($allMasterSkills as $sk): ?>
                                            <option value="<?= $sk['id'] ?>" <?= in_array($sk['id'], $mySkillIds) ? 'disabled' : '' ?>>
                                                <?= e($sk['skill_name']) ?> (<?= e($sk['category']) ?>) <?= in_array($sk['id'], $mySkillIds) ? ' - [Already Added]' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="custom_skill" class="form-label fw-semibold">Or Add New Skill</label>
                                    <input type="text" class="form-control" id="custom_skill" name="custom_skill" placeholder="e.g. Flutter, Django, Docker">
                                    <small class="text-muted">If your skill is not listed in dropdown above.</small>
                                </div>

                                <div class="mb-4">
                                    <label for="proficiency" class="form-label fw-semibold">Proficiency Level</label>
                                    <select class="form-select" id="proficiency" name="proficiency">
                                        <option value="Beginner">Beginner (Basic understanding)</option>
                                        <option value="Intermediate" selected>Intermediate (Hands-on project experience)</option>
                                        <option value="Advanced">Advanced (Strong domain mastery)</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 fw-bold">
                                    <i class="bi bi-check-lg me-1"></i> Add to My Profile
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- My Skills List Card -->
                <div class="col-lg-7">
                    <div class="card card-portal">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-list-stars me-2 text-primary"></i>Added Skills (<?= count($mySkills) ?>)</h5>
                        </div>
                        <div class="card-body p-4">
                            <?php if (!empty($mySkills)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-portal align-middle">
                                        <thead>
                                            <tr>
                                                <th>Skill Name</th>
                                                <th>Category</th>
                                                <th>Proficiency</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($mySkills as $ms): ?>
                                                <tr>
                                                    <td><strong class="text-dark"><?= e($ms['skill_name']) ?></strong></td>
                                                    <td><span class="badge bg-light text-secondary"><?= e($ms['category'] ?? 'Technical') ?></span></td>
                                                    <td>
                                                        <?php if ($ms['proficiency'] === 'Advanced'): ?>
                                                            <span class="badge bg-success text-white">Advanced</span>
                                                        <?php elseif ($ms['proficiency'] === 'Intermediate'): ?>
                                                            <span class="badge bg-primary text-white">Intermediate</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary text-white">Beginner</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <form action="<?= BASE_URL ?>student/skills.php" method="POST" class="d-inline">
                                                            <?= csrfField() ?>
                                                            <input type="hidden" name="action" value="delete_skill">
                                                            <input type="hidden" name="map_id" value="<?= $ms['map_id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Remove <?= e($ms['skill_name']) ?> from your skills?">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-stars fs-1 d-block mb-2 text-secondary"></i>
                                    <p class="mb-0">No technical skills added yet.</p>
                                    <small>Add your programming languages and frameworks to become eligible for placement drives.</small>
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
