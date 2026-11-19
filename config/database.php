<?php
/**
 * Database Configuration & Connection (PDO)
 * Smart Student Management & Placement Portal
 */

// Database credentials for XAMPP
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'smart_placement_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Base URL definition (adjust if folder name changes)
define('APP_NAME', 'Smart Placement Portal');
define('BASE_URL', '/SmartStudentPlacement_V2/');

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // If running in development, show error; otherwise generic message
    die("Database Connection Failed: " . htmlspecialchars($e->getMessage()) . "<br><br>Please ensure MySQL is running in XAMPP and run <code>setup_db.php</code> first.");
}
