-- ==========================================================
-- Database Schema: Smart Student Management & Placement Portal
-- Target: MySQL 5.7+ / 8.0+ (InnoDB Engine)
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `smart_placement_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `smart_placement_db`;

-- 1. Users Table (Authentication)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Students Table (Profile Information)
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `enrollment_no` VARCHAR(30) NOT NULL UNIQUE,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `phone` VARCHAR(15) NULL,
    `department` VARCHAR(50) NOT NULL DEFAULT 'Computer Engineering',
    `semester` INT NOT NULL DEFAULT 5,
    `gender` ENUM('Male', 'Female', 'Other') NULL,
    `dob` DATE NULL,
    `address` TEXT NULL,
    `resume_path` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 3. Academic Records Table
CREATE TABLE IF NOT EXISTS `academic_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL UNIQUE,
    `tenth_percentage` DECIMAL(5, 2) NULL DEFAULT 0.00,
    `twelfth_percentage` DECIMAL(5, 2) NULL DEFAULT 0.00,
    `cgpa` DECIMAL(4, 2) NOT NULL DEFAULT 0.00,
    `active_backlogs` INT NOT NULL DEFAULT 0,
    `attendance_percentage` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_academic_student` FOREIGN KEY (`student_id`) 
        REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 4. Skills Master Table
CREATE TABLE IF NOT EXISTS `skills` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `skill_name` VARCHAR(50) NOT NULL UNIQUE,
    `category` VARCHAR(50) NULL DEFAULT 'General'
) ENGINE=InnoDB;

-- 5. Student Skills Mapping Table
CREATE TABLE IF NOT EXISTS `student_skills` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `skill_id` INT NOT NULL,
    `proficiency` ENUM('Beginner', 'Intermediate', 'Advanced') NOT NULL DEFAULT 'Intermediate',
    UNIQUE KEY `uk_student_skill` (`student_id`, `skill_id`),
    CONSTRAINT `fk_studentskills_student` FOREIGN KEY (`student_id`) 
        REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_studentskills_skill` FOREIGN KEY (`skill_id`) 
        REFERENCES `skills` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 6. Companies Table
CREATE TABLE IF NOT EXISTS `companies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `website` VARCHAR(150) NULL,
    `contact_email` VARCHAR(100) NULL,
    `contact_phone` VARCHAR(20) NULL,
    `location` VARCHAR(100) NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 7. Placement Drives Table
CREATE TABLE IF NOT EXISTS `placement_drives` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `job_title` VARCHAR(100) NOT NULL,
    `job_description` TEXT NOT NULL,
    `package_lpa` DECIMAL(6, 2) NULL DEFAULT 0.00,
    `job_location` VARCHAR(100) NOT NULL DEFAULT 'On-Site',
    `min_cgpa` DECIMAL(4, 2) NOT NULL DEFAULT 6.00,
    `min_attendance` DECIMAL(5, 2) NOT NULL DEFAULT 75.00,
    `max_backlogs` INT NOT NULL DEFAULT 0,
    `allowed_branches` VARCHAR(255) NOT NULL DEFAULT 'All',
    `drive_date` DATE NOT NULL,
    `deadline` DATE NOT NULL,
    `status` ENUM('Upcoming', 'Active', 'Closed') NOT NULL DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_drives_company` FOREIGN KEY (`company_id`) 
        REFERENCES `companies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 8. Drive Skills Mapping Table
CREATE TABLE IF NOT EXISTS `drive_skills` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `drive_id` INT NOT NULL,
    `skill_id` INT NOT NULL,
    UNIQUE KEY `uk_drive_skill` (`drive_id`, `skill_id`),
    CONSTRAINT `fk_driveskills_drive` FOREIGN KEY (`drive_id`) 
        REFERENCES `placement_drives` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_driveskills_skill` FOREIGN KEY (`skill_id`) 
        REFERENCES `skills` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 9. Applications Table
CREATE TABLE IF NOT EXISTS `applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `drive_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('Applied', 'Shortlisted', 'Interview', 'Selected', 'Rejected') NOT NULL DEFAULT 'Applied',
    `admin_notes` TEXT NULL,
    UNIQUE KEY `uk_drive_student_application` (`drive_id`, `student_id`),
    CONSTRAINT `fk_applications_drive` FOREIGN KEY (`drive_id`) 
        REFERENCES `placement_drives` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_applications_student` FOREIGN KEY (`student_id`) 
        REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
