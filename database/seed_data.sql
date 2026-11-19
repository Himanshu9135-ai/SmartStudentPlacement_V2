-- ==========================================================
-- Seed Data: Smart Student Management & Placement Portal
-- ==========================================================

USE `smart_placement_db`;

-- 1. Insert Master Skills
INSERT INTO `skills` (`id`, `skill_name`, `category`) VALUES
(1, 'PHP', 'Programming Language'),
(2, 'MySQL', 'Database'),
(3, 'JavaScript', 'Web Development'),
(4, 'HTML5 / CSS3', 'Web Development'),
(5, 'Python', 'Programming Language'),
(6, 'Java', 'Programming Language'),
(7, 'Bootstrap', 'Frontend Framework'),
(8, 'Git & GitHub', 'Dev Tools'),
(9, 'React.js', 'Frontend Framework'),
(10, 'C / C++', 'Core Programming')
ON DUPLICATE KEY UPDATE `skill_name`=VALUES(`skill_name`);

-- 2. Insert Companies
INSERT INTO `companies` (`id`, `name`, `website`, `contact_email`, `contact_phone`, `location`, `description`) VALUES
(1, 'TechCorp Solutions', 'https://techcorp-example.com', 'hr@techcorp-example.com', '+91 9876543210', 'Bangalore, India', 'Leading software consulting and enterprise solutions provider.'),
(2, 'Innovate Labs', 'https://innovatelabs-example.com', 'careers@innovatelabs-example.com', '+91 9876543211', 'Pune, India', 'Fast-growing cloud, AI, and full-stack product development company.'),
(3, 'Nexus Infotech', 'https://nexusinfo-example.com', 'placement@nexusinfo-example.com', '+91 9876543212', 'Hyderabad, India', 'Global IT services and digital transformation partner.')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 3. Insert Placement Drives
INSERT INTO `placement_drives` (`id`, `company_id`, `job_title`, `job_description`, `package_lpa`, `job_location`, `min_cgpa`, `min_attendance`, `max_backlogs`, `allowed_branches`, `drive_date`, `deadline`, `status`) VALUES
(1, 1, 'Junior Web Developer', 'Looking for enthusiastic diploma graduates with solid fundamentals in PHP, MySQL, HTML/CSS and JavaScript to build web applications.', 3.60, 'Bangalore / Hybrid', 6.50, 75.00, 0, 'Computer Engineering,Information Technology', DATE_ADD(CURDATE(), INTERVAL 14 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'Active'),
(2, 2, 'Associate Software Engineer', 'Great opportunity for freshers to work with Java, Python, and SQL databases on modern cloud platforms.', 4.20, 'Pune', 7.50, 80.00, 0, 'Computer Engineering', DATE_ADD(CURDATE(), INTERVAL 21 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'Active'),
(3, 3, 'Frontend Trainee Engineer', 'Develop and maintain responsive user interfaces using HTML5, CSS3, JavaScript and Bootstrap.', 3.00, 'Hyderabad', 6.00, 70.00, 1, 'All', DATE_ADD(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'Active')
ON DUPLICATE KEY UPDATE `job_title`=VALUES(`job_title`);

-- 4. Insert Drive Skills Requirements
INSERT INTO `drive_skills` (`drive_id`, `skill_id`) VALUES
(1, 1), -- Junior Web Dev -> PHP
(1, 2), -- Junior Web Dev -> MySQL
(1, 3), -- Junior Web Dev -> JavaScript
(2, 5), -- Assoc SWE -> Python
(2, 6), -- Assoc SWE -> Java
(2, 2), -- Assoc SWE -> MySQL
(3, 4), -- Frontend Trainee -> HTML5/CSS3
(3, 3), -- Frontend Trainee -> JavaScript
(3, 7)  -- Frontend Trainee -> Bootstrap
ON DUPLICATE KEY UPDATE `drive_id`=VALUES(`drive_id`);
