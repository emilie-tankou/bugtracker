<?php
/**
 * Database configuration file
 * Update these values according to your hosting environment
 */

// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_NAME', 'bugtracker');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application configuration
define('BASE_URL', 'http://localhost/bugtracker');
define('SITE_NAME', 'BugTracker');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
session_start();

/**
 * Get database connection using PDO
 * @return PDO Database connection object
 * @throws PDOException If connection fails
 */
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        die("Connection failed. Please contact administrator.");
    }
}

/**
 * Check if user is authenticated
 * @return bool True if user is logged in
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to login page if not authenticated
 */
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Redirect to dashboard if already authenticated
 */
function requireGuest() {
    if (isAuthenticated()) {
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Get current user data
 * @return array|null User data or null if not authenticated
 */
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT id, name, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Sanitize output to prevent XSS
 * @param string $text Text to sanitize
 * @return string Sanitized text
 */
function escape($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}