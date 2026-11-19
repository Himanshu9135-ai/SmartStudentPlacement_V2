<?php
/**
 * Global Modern Corporate Footer & Developer Spotlight Include
 * Smart Student Management & Placement Portal (V2)
 * Designed & Developed by Himanshu Kumar
 */
?>
    <!-- Professional College & Developer Footer -->
    <footer class="portal-footer mt-auto text-white" style="background: linear-gradient(135deg, #0b1329 0%, #111d3d 100%); border-top: 3px solid #2563eb;">
        <div class="container py-5">
            <div class="row g-4">
                
                <!-- Col 1: Portal Overview -->
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-3 bg-primary text-white d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; font-size: 20px;">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-white" style="letter-spacing: 0.5px;"><?= APP_NAME ?></h5>
                            <small class="text-white-50" style="font-size: 11px;">CAMPUS TRAINING & PLACEMENT CELL</small>
                        </div>
                    </div>
                    <p class="text-white-50 small mb-3" style="line-height: 1.7;">
                        An automated, enterprise-grade placement management platform streamlining student eligibility tracking, corporate recruitment drives, and professional resume building.
                    </p>
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-10 text-white-50 small border border-white border-opacity-10">
                        <i class="bi bi-award-fill text-warning"></i>
                        <span>Academic Session 2025 - 2026</span>
                    </div>
                </div>

                <!-- Col 2: Quick Links -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold text-white text-uppercase mb-3" style="font-size: 13px; letter-spacing: 1px;">Portals & Tools</h6>
                    <ul class="list-unstyled small mb-0 d-flex flex-column gap-2 text-white-50">
                        <li><a href="<?= BASE_URL ?>index.php" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-house-door me-1"></i> Home Portal</a></li>
                        <li><a href="<?= BASE_URL ?>student/drives.php" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-briefcase me-1"></i> Placement Drives</a></li>
                        <li><a href="<?= BASE_URL ?>student/resume_builder.php" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-file-earmark-person me-1"></i> Resume Builder</a></li>
                        <li><a href="<?= BASE_URL ?>auth/register_company.php" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-building me-1"></i> Recruiter Registration</a></li>
                        <li><a href="<?= BASE_URL ?>auth/login.php" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-box-arrow-in-right me-1"></i> Portal Login</a></li>
                    </ul>
                </div>

                <!-- Col 3: Tech Stack Badges (Examiner Highlight) -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="fw-bold text-white text-uppercase mb-3" style="font-size: 13px; letter-spacing: 1px;">Architecture & Stack</h6>
                    <div class="d-flex flex-wrap gap-1 mb-3">
                        <span class="badge bg-primary bg-opacity-25 text-info border border-info border-opacity-25">PHP 8.x OOP</span>
                        <span class="badge bg-primary bg-opacity-25 text-info border border-info border-opacity-25">MySQL / MariaDB</span>
                        <span class="badge bg-primary bg-opacity-25 text-info border border-info border-opacity-25">PDO Prepared Stmts</span>
                        <span class="badge bg-primary bg-opacity-25 text-info border border-info border-opacity-25">Bootstrap 5</span>
                        <span class="badge bg-primary bg-opacity-25 text-info border border-info border-opacity-25">CSRF Guard</span>
                        <span class="badge bg-primary bg-opacity-25 text-info border border-info border-opacity-25">BCRYPT Hashing</span>
                        <span class="badge bg-primary bg-opacity-25 text-info border border-info border-opacity-25">REST & AJAX</span>
                    </div>
                    <small class="text-white-50 d-block" style="font-size: 11.5px; line-height: 1.5;">
                        <i class="bi bi-shield-check text-success me-1"></i> Engineered following industry security standards and MVC design principles.
                    </small>
                </div>

                <!-- Col 4: Lead Developer Spotlight Card -->
                <div class="col-lg-3 col-md-6">
                    <div class="p-3 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-10 h-100">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-warning text-dark fw-bold px-2 py-1" style="font-size: 10px;">
                                <i class="bi bi-code-slash me-1"></i> LEAD DEVELOPER
                            </span>
                        </div>
                        <h6 class="fw-bold text-white mb-1" style="font-size: 16px;">Himanshu Kumar</h6>
                        <p class="text-white-50 mb-2 small" style="font-size: 12px; line-height: 1.4;">
                            Full Stack Web Developer &amp; System Architect<br>
                            <span class="text-info">Diploma in Computer Science &amp; Engineering</span>
                        </p>
                        <div class="small mb-3">
                            <a href="mailto:mrhimanshukumar343@gmail.com" class="text-white-50 text-decoration-none d-flex align-items-center gap-2" style="font-size: 12px;">
                                <i class="bi bi-envelope-fill text-warning"></i>
                                <span class="text-truncate">mrhimanshukumar343@gmail.com</span>
                            </a>
                        </div>
                        <button type="button" class="btn btn-outline-light btn-sm w-100 fw-semibold" style="font-size: 12px;" data-bs-toggle="modal" data-bs-target="#developerModal">
                            <i class="bi bi-info-circle me-1"></i> View Project Credits
                        </button>
                    </div>
                </div>

            </div>

            <!-- Bottom Sub-Footer Bar -->
            <hr class="my-4 border-white border-opacity-10">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 small text-white-50">
                <div>
                    &copy; <?= date('Y') ?> <strong><?= APP_NAME ?></strong>. Designed &amp; Developed with <i class="bi bi-heart-fill text-danger"></i> by <strong>Himanshu Kumar</strong>.
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span><i class="bi bi-patch-check-fill text-success me-1"></i> College Major Capstone Project</span>
                    <span>&bull;</span>
                    <span>Version 2.0 (Production Release)</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Developer & Project Credits Modal -->
    <div class="modal fade" id="developerModal" tabindex="-1" aria-labelledby="developerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header text-white p-4" style="background: linear-gradient(135deg, #0b1329 0%, #1e3c72 100%);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold bg-warning text-dark shadow-sm" style="width: 50px; height: 50px; font-size: 20px;">
                            HK
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="developerModalLabel">Himanshu Kumar</h5>
                            <small class="text-white-50">Lead Architect &amp; Full Stack Developer</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-briefcase-fill text-primary me-2"></i>About the Project</h6>
                    <p class="text-muted small mb-3" style="line-height: 1.6;">
                        <strong>Smart Student Placement Portal (V2)</strong> was conceptualized and developed as a comprehensive campus recruitment platform. It automates training and placement cell operations, implements a multi-parameter eligibility algorithm (CGPA, Attendance, Backlogs, Skills), and provides an industry-standard 2-column executive resume builder.
                    </p>

                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-tools text-primary me-2"></i>Key Engineering Highlights</h6>
                    <ul class="small text-muted ps-3 mb-3">
                        <li><strong>Multi-Portal Architecture:</strong> Separate, hardened portals for Students, TPO Admins, and Recruiters.</li>
                        <li><strong>Automated Eligibility Engine:</strong> Real-time automated criteria checking prevents ineligible submissions.</li>
                        <li><strong>ATS-Compliant Resume Builder:</strong> Dynamic two-column executive resume generation directly from verified database records.</li>
                        <li><strong>Enterprise Security:</strong> SQL Injection protection via PDO prepared statements, CSRF tokens, BCRYPT password hashing, and session fixation guards.</li>
                    </ul>

                    <div class="p-3 bg-light rounded-3 border">
                        <small class="text-muted d-block mb-1">Developer Contact &amp; Correspondence:</small>
                        <div class="fw-bold text-dark"><i class="bi bi-envelope-at-fill text-primary me-2"></i>mrhimanshukumar343@gmail.com</div>
                        <div class="small text-muted mt-1"><i class="bi bi-mortarboard text-secondary me-2"></i>Diploma in Computer Science &amp; Engineering &bull; Session 2025-26</div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 border-0">
                    <button type="button" class="btn btn-primary btn-sm px-4 fw-bold" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <style>
    .portal-footer a.hover-white:hover {
        color: #ffffff !important;
        text-decoration: underline !important;
    }
    </style>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Portal JS -->
    <script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
