<?php
/**
 * Professional Executive Resume Builder & Live PDF Generator
 * Exactly Matching Two-Column Modern Design with Photo / Avatar
 * Smart Student Management & Placement Portal (V2)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireStudent();

$student = getCurrentStudent($pdo);
$studentId = $student['id'];

// Fetch Academic Record
$stmtAcad = $pdo->prepare("SELECT * FROM academic_records WHERE student_id = ? LIMIT 1");
$stmtAcad->execute([$studentId]);
$academics = $stmtAcad->fetch() ?: [];

// Fetch Skills
$stmtSkills = $pdo->prepare("
    SELECT sk.skill_name, ss.proficiency
    FROM student_skills ss
    JOIN skills sk ON ss.skill_id = sk.id
    WHERE ss.student_id = ?
    ORDER BY sk.skill_name ASC
");
$stmtSkills->execute([$studentId]);
$skills = $stmtSkills->fetchAll();

// Pre-fill fields from profile or custom POST
$fullName    = trim($student['first_name'] . ' ' . $student['last_name']);
$email       = trim($student['email']);
$phone       = trim($student['phone'] ?? '+91 9876543210');
$address     = trim($student['address'] ?? 'New Delhi, India');
$branch      = trim($student['department']);
$enrollment  = trim($student['enrollment_no']);
$cgpa        = number_format((float)($academics['cgpa'] ?? 8.20), 2);
$tenth       = number_format((float)($academics['tenth_percentage'] ?? 85.00), 1);
$twelfth     = number_format((float)($academics['twelfth_percentage'] ?? 82.00), 1);

// Custom preview variables
$jobTitle        = trim($_POST['job_title'] ?? 'SOFTWARE ENGINEER / FULL STACK DEVELOPER');
$careerObjective = trim($_POST['career_objective'] ?? 'Detail-oriented and passionate engineering graduate equipped with strong fundamentals in full-stack web technologies, database design, and algorithmic problem-solving. Seeking an entry-level position at a forward-thinking organization to build scalable web applications and create impactful user experiences.');
$project1_title  = trim($_POST['project1_title'] ?? ($student['project_title'] ?: 'Smart Student Placement & Management Portal'));
$project1_role   = trim($_POST['project1_role'] ?? 'Lead Developer & Architect (2025 - PRESENT)');
$project1_desc   = trim($_POST['project1_desc'] ?? "• Architected a role-based multi-tier placement platform for Students, TPO Admins, and Corporate Recruiters.\n• Implemented automated eligibility criteria engine matching CGPA, attendance %, and backlogs in real-time.\n• Built secure password hashing, CSRF defenses, and 1-click professional PDF resume generation.");

$project2_title  = trim($_POST['project2_title'] ?? 'E-Commerce Cloud Application');
$project2_role   = trim($_POST['project2_role'] ?? 'Full Stack Developer (2024 - 2025)');
$project2_desc   = trim($_POST['project2_desc'] ?? "• Designed responsive product catalog with real-time stock inventory, shopping cart, and search filtering.\n• Implemented session-based cart management, relational database indexing, and order tracking APIs.");

$internship      = trim($_POST['internship'] ?? "Software Development Intern — Tech Solutions Lab (Summer 2025)\n• Collaborated with engineering team to optimize backend SQL queries and increase API response speed by 30%.\n• Designed and integrated responsive UI components using modern Bootstrap & JavaScript frameworks.");

$languages       = trim($_POST['languages'] ?? "English (Fluent / Professional)\nHindi (Native / Fluent)");
$references      = trim($_POST['references'] ?? "Head of Department (Training & Placement)\nCollege of Engineering & Technology\nEmail: tpo@college.edu | Contact: +91 11 2345 6789");

$isPrintMode = isset($_GET['print']) && $_GET['print'] == '1';

if ($isPrintMode): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resume - <?= e($fullName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #fff;
            color: #334155;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .resume-sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #ffffff;
            display: flex;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        /* Left Column (35%) */
        .left-col {
            width: 34%;
            background-color: #e2e8f0;
            padding: 0;
            display: flex;
            flex-direction: column;
        }
        .left-top-banner {
            background-color: #1e293b;
            padding: 40px 20px 30px;
            text-align: center;
        }
        .profile-photo {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #ffffff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.25);
            margin: 0 auto;
            display: block;
        }
        .left-content {
            padding: 30px 24px;
        }
        .left-heading {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            border-bottom: 2px solid #94a3b8;
            padding-bottom: 6px;
            margin-top: 24px;
            margin-bottom: 14px;
        }
        .left-heading:first-child {
            margin-top: 0;
        }
        .contact-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 10px;
            font-size: 12px;
            color: #1e293b;
            word-break: break-word;
        }
        .contact-item i {
            margin-right: 10px;
            color: #2563eb;
            font-size: 14px;
            margin-top: 2px;
        }
        .left-list {
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
        }
        .left-list li {
            position: relative;
            padding-left: 14px;
            margin-bottom: 7px;
            font-size: 12.5px;
            color: #1e293b;
            font-weight: 500;
        }
        .left-list li::before {
            content: "•";
            position: absolute;
            left: 0;
            color: #2563eb;
            font-size: 16px;
            top: -2px;
        }

        /* Right Column (65%) */
        .right-col {
            width: 66%;
            padding: 0;
            background: #ffffff;
        }
        .right-top-banner {
            background-color: #1e293b;
            color: #ffffff;
            padding: 40px 35px 30px;
        }
        .candidate-name {
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .candidate-title {
            font-size: 13px;
            color: #94a3b8;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 600;
        }
        .right-content {
            padding: 25px 35px;
        }
        .right-section-title {
            display: flex;
            align-items: center;
            font-size: 13.5px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-top: 20px;
            margin-bottom: 12px;
        }
        .right-section-title i {
            background: #1e293b;
            color: #ffffff;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            margin-right: 10px;
        }
        .right-section-title:first-child {
            margin-top: 0;
        }
        .timeline-item {
            position: relative;
            padding-left: 20px;
            border-left: 2px solid #cbd5e1;
            margin-left: 12px;
            margin-bottom: 16px;
        }
        .timeline-item::before {
            content: "";
            position: absolute;
            left: -6px;
            top: 4px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #2563eb;
            border: 2px solid #ffffff;
        }
        .timeline-title {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1px;
        }
        .timeline-sub {
            font-size: 11.5px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .timeline-desc {
            font-size: 12px;
            color: #334155;
            white-space: pre-line;
            margin-bottom: 0;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0 !important; background: #fff !important; }
            .resume-sheet { box-shadow: none !important; width: 100% !important; min-height: 100vh !important; }
        }
    </style>
</head>
<body onload="window.print()">

    <!-- Print Control Bar for Screen Preview -->
    <div class="no-print bg-dark text-white p-3 mb-4 d-flex justify-content-between align-items-center rounded shadow mx-auto" style="max-width: 210mm;">
        <div>
            <i class="bi bi-patch-check-fill me-2 text-warning"></i>
            <strong>Executive 2-Column Format:</strong> Designed for High-Impact Corporate Screening.
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-primary btn-sm me-2 fw-bold"><i class="bi bi-printer me-1"></i> Print / Save PDF</button>
            <a href="<?= BASE_URL ?>student/resume_builder.php" class="btn btn-outline-light btn-sm"><i class="bi bi-pencil me-1"></i> Edit Details</a>
        </div>
    </div>

    <!-- Main Two-Column Resume Canvas -->
    <div class="resume-sheet">
        
        <!-- LEFT COLUMN -->
        <div class="left-col">
            <div class="left-top-banner">
                <?php if (!empty($student['photo_path']) && file_exists(__DIR__ . '/../' . $student['photo_path'])): ?>
                    <img src="<?= BASE_URL . e($student['photo_path']) ?>" alt="Photo" class="profile-photo">
                <?php else: ?>
                    <div class="profile-photo d-flex align-items-center justify-content-center text-white fw-bold bg-primary" style="font-size: 42px;">
                        <?= strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="left-content">
                <!-- CONTACT -->
                <div class="left-heading">CONTACT</div>
                <div class="contact-item">
                    <i class="bi bi-telephone-fill"></i>
                    <span><?= e($phone) ?></span>
                </div>
                <div class="contact-item">
                    <i class="bi bi-envelope-fill"></i>
                    <span><?= e($email) ?></span>
                </div>
                <div class="contact-item">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span><?= e($address) ?></span>
                </div>
                <div class="contact-item">
                    <i class="bi bi-card-heading"></i>
                    <span>Roll: <?= e($enrollment) ?></span>
                </div>

                <!-- KEY SKILLS -->
                <div class="left-heading">CORE SKILLS</div>
                <ul class="left-list">
                    <?php if (!empty($skills)): ?>
                        <?php foreach ($skills as $sk): ?>
                            <li><?= e($sk['skill_name']) ?> <small class="text-muted">(<?= e($sk['proficiency']) ?>)</small></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>Full Stack Web Development</li>
                        <li>PHP & OOP Programming</li>
                        <li>MySQL Database & Indexing</li>
                        <li>Bootstrap 5 Responsive UI</li>
                        <li>JavaScript & REST APIs</li>
                        <li>Git & GitHub Collaboration</li>
                    <?php endif; ?>
                </ul>

                <!-- LANGUAGES -->
                <div class="left-heading">LANGUAGES</div>
                <ul class="left-list">
                    <?php foreach (explode("\n", $languages) as $lang): ?>
                        <?php if (trim($lang)): ?>
                            <li><?= e(trim($lang)) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>

                <!-- REFERENCE -->
                <?php if (!empty($references)): ?>
                <div class="left-heading">REFERENCE</div>
                <div class="small text-secondary" style="font-size: 11.5px; white-space: pre-line;">
                    <?= e($references) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="right-col">
            <div class="right-top-banner">
                <div class="candidate-name"><?= e($fullName) ?></div>
                <div class="candidate-title"><?= e($jobTitle) ?></div>
            </div>

            <div class="right-content">
                <!-- PROFILE / CAREER OBJECTIVE -->
                <div class="right-section-title">
                    <i class="bi bi-person-fill"></i> PROFILE SUMMARY
                </div>
                <p class="text-secondary small mb-3" style="text-align: justify; line-height: 1.6;">
                    <?= nl2br(e($careerObjective)) ?>
                </p>

                <!-- WORK EXPERIENCE / PROJECTS -->
                <div class="right-section-title">
                    <i class="bi bi-briefcase-fill"></i> PROJECTS & EXPERIENCE
                </div>

                <div class="timeline-item">
                    <div class="timeline-title"><?= e($project1_title) ?></div>
                    <div class="timeline-sub"><?= e($project1_role) ?></div>
                    <p class="timeline-desc"><?= e($project1_desc) ?></p>
                </div>

                <div class="timeline-item">
                    <div class="timeline-title"><?= e($project2_title) ?></div>
                    <div class="timeline-sub"><?= e($project2_role) ?></div>
                    <p class="timeline-desc"><?= e($project2_desc) ?></p>
                </div>

                <?php if (!empty($internship)): ?>
                <div class="timeline-item">
                    <div class="timeline-title">Practical Internship Experience</div>
                    <p class="timeline-desc"><?= e($internship) ?></p>
                </div>
                <?php endif; ?>

                <!-- EDUCATION -->
                <div class="right-section-title">
                    <i class="bi bi-mortarboard-fill"></i> EDUCATION
                </div>

                <div class="timeline-item">
                    <div class="timeline-title">Diploma in Computer Science &amp; Engineering</div>
                    <div class="timeline-sub"><?= e($branch) ?> &bull; Sem <?= (int)($student['semester'] ?? 5) ?></div>
                    <div class="small fw-bold text-primary">Score: <?= e($cgpa) ?> CGPA</div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-title">Senior Secondary (Class XII - Science)</div>
                    <div class="timeline-sub">State / Central Board &bull; Score: <?= e($twelfth) ?>%</div>
                </div>

                <div class="timeline-item mb-0">
                    <div class="timeline-title">Secondary School (Class X)</div>
                    <div class="timeline-sub">State / Central Board &bull; Score: <?= e($tenth) ?>%</div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
<?php exit; endif; ?>

<?php
$pageTitle = 'Executive Resume Builder';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_student.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-file-earmark-person-fill text-primary me-2"></i>Executive Resume Builder</h3>
                    <p class="text-muted mb-0">Two-column professional resume designed to match modern hiring standards.</p>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>student/profile.php" class="btn btn-outline-primary btn-sm px-3">
                        <i class="bi bi-camera me-1"></i> Update Profile Photo
                    </a>
                </div>
            </div>

            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="row g-4">
                
                <!-- Resume Customizer Form -->
                <div class="col-lg-7">
                    <div class="card card-portal">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-sliders text-primary me-2"></i>Customize Your Resume Sections</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="<?= BASE_URL ?>student/resume_builder.php?print=1" target="_blank">
                                <?= csrfField() ?>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">TARGET PROFESSIONAL TITLE</label>
                                    <input type="text" name="job_title" class="form-control fw-bold text-primary" value="<?= e($jobTitle) ?>" placeholder="e.g. SOFTWARE ENGINEER / WEB DEVELOPER">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">PROFILE / CAREER OBJECTIVE</label>
                                    <textarea name="career_objective" class="form-control" rows="3"><?= e($careerObjective) ?></textarea>
                                </div>

                                <h6 class="fw-bold text-dark border-bottom pb-2 mt-4 mb-3">Key Projects (Shows in Experience Section)</h6>

                                <div class="row g-2 mb-3">
                                    <div class="col-md-7">
                                        <label class="form-label small fw-semibold">Project 1 Title</label>
                                        <input type="text" name="project1_title" class="form-control" value="<?= e($project1_title) ?>">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small fw-semibold">Role & Duration</label>
                                        <input type="text" name="project1_role" class="form-control" value="<?= e($project1_role) ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Bullet Points / Tech Stack</label>
                                        <textarea name="project1_desc" class="form-control" rows="2"><?= e($project1_desc) ?></textarea>
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-md-7">
                                        <label class="form-label small fw-semibold">Project 2 Title</label>
                                        <input type="text" name="project2_title" class="form-control" value="<?= e($project2_title) ?>">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small fw-semibold">Role & Duration</label>
                                        <input type="text" name="project2_role" class="form-control" value="<?= e($project2_role) ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Bullet Points / Tech Stack</label>
                                        <textarea name="project2_desc" class="form-control" rows="2"><?= e($project2_desc) ?></textarea>
                                    </div>
                                </div>

                                <h6 class="fw-bold text-dark border-bottom pb-2 mt-4 mb-3">Internship, Languages & References</h6>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Internship Experience</label>
                                    <textarea name="internship" class="form-control" rows="2"><?= e($internship) ?></textarea>
                                </div>

                                <div class="row g-2 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Languages (One per line)</label>
                                        <textarea name="languages" class="form-control" rows="2"><?= e($languages) ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Reference Details</label>
                                        <textarea name="references" class="form-control" rows="2"><?= e($references) ?></textarea>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">
                                    <i class="bi bi-printer-fill me-2"></i> Generate & Print Two-Column Resume
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Template Preview Card -->
                <div class="col-lg-5">
                    <div class="card card-portal h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-layout-split text-primary me-2"></i>Executive Template</h5>
                            <span class="badge bg-success">Canva Style</span>
                        </div>
                        <div class="card-body p-4 text-center">
                            <div class="p-3 bg-light rounded-4 border mb-3">
                                <!-- Miniature Mockup of the 2-column layout -->
                                <div class="rounded shadow-sm overflow-hidden border mx-auto" style="max-width: 260px; height: 350px; display: flex; font-size: 8px;">
                                    <div style="width: 35%; background: #e2e8f0; padding: 10px 5px; text-align: left;">
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #1e293b; margin: 0 auto 10px; border: 2px solid #fff;"></div>
                                        <div style="font-weight: bold; font-size: 7px; margin-bottom: 4px;">CONTACT</div>
                                        <div style="background: #94a3b8; height: 3px; width: 80%; margin-bottom: 3px;"></div>
                                        <div style="background: #94a3b8; height: 3px; width: 60%; margin-bottom: 8px;"></div>
                                        <div style="font-weight: bold; font-size: 7px; margin-bottom: 4px;">SKILLS</div>
                                        <div style="background: #94a3b8; height: 3px; width: 70%; margin-bottom: 3px;"></div>
                                        <div style="background: #94a3b8; height: 3px; width: 65%; margin-bottom: 3px;"></div>
                                    </div>
                                    <div style="width: 65%; background: #fff; text-align: left;">
                                        <div style="background: #1e293b; color: #fff; padding: 12px 10px;">
                                            <div style="font-weight: bold; font-size: 10px;"><?= strtoupper(e($fullName)) ?></div>
                                            <div style="font-size: 6px; color: #94a3b8;"><?= e($jobTitle) ?></div>
                                        </div>
                                        <div style="padding: 10px;">
                                            <div style="font-weight: bold; font-size: 7px; margin-bottom: 3px;">PROFILE SUMMARY</div>
                                            <div style="background: #cbd5e1; height: 2px; width: 90%; margin-bottom: 6px;"></div>
                                            <div style="font-weight: bold; font-size: 7px; margin-bottom: 3px;">PROJECTS</div>
                                            <div style="background: #cbd5e1; height: 2px; width: 90%; margin-bottom: 6px;"></div>
                                            <div style="font-weight: bold; font-size: 7px; margin-bottom: 3px;">EDUCATION</div>
                                            <div style="background: #cbd5e1; height: 2px; width: 90%;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Executive 2-Column Template</h6>
                            <p class="text-muted small mb-0">Pre-styled with your college marks, verified skills, and photo preview. Ready for high-impact campus interview submissions.</p>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
