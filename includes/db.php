<?php
/**
 * Care4Mom - Database Connection
 * Database connection configuration for Care4Mom app
 * Author: Care4Mom Development Team
 * Version: 1.0
 */

// Database configuration - Updated with provided credentials
// Note: For local development, using SQLite. For production, use the credentials below:
// DB_USER: outsrglr_Momcare, DB_PASS: ethanJ#2015, DB_NAME: outsrglr_Momcare

// Check if we're in development mode (no remote database access)
$is_development = !isset($_SERVER['SERVER_NAME']) || $_SERVER['SERVER_NAME'] === 'localhost' || php_sapi_name() === 'cli';

if ($is_development) {
    // Local SQLite development database
    define('DB_TYPE', 'sqlite');
    define('DB_PATH', __DIR__ . '/../sql/care4mom_dev.db');
} else {
    // Production MySQL database with provided credentials
    define('DB_TYPE', 'mysql');
    define('DB_HOST', 'localhost');
    define('DB_USER', 'outsrglr_Momcare');
    define('DB_PASS', 'ethanJ#2015');
    define('DB_NAME', 'outsrglr_Momcare');
}

// Create database connection
function getDBConnection() {
    try {
        if (DB_TYPE === 'sqlite') {
            // SQLite connection for development
            $pdo = new PDO(
                "sqlite:" . DB_PATH,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            
            // Initialize SQLite database if it doesn't exist
            initializeSQLiteDatabase($pdo);
        } else {
            // MySQL connection for production
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        }
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Connection failed. Please try again later.");
    }
}

// Global database connection
$pdo = getDBConnection();

/**
 * Initialize SQLite database with schema
 */
function initializeSQLiteDatabase($pdo) {
    // Check if tables exist
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    if ($stmt->fetchColumn()) {
        return; // Database already initialized
    }
    
    // Create tables with SQLite-compatible schema
    $schema = "
    -- Users table for authentication and roles
    CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role TEXT CHECK(role IN ('patient', 'caregiver', 'doctor')) DEFAULT 'patient',
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        phone VARCHAR(20),
        emergency_contact VARCHAR(100),
        emergency_phone VARCHAR(20),
        doctor_name VARCHAR(100),
        doctor_phone VARCHAR(20),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        active BOOLEAN DEFAULT 1
    );

    -- Symptoms tracking table
    CREATE TABLE symptoms (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        symptom_name VARCHAR(100) NOT NULL,
        severity INTEGER NOT NULL CHECK (severity >= 1 AND severity <= 10),
        notes TEXT,
        voice_note_path VARCHAR(255),
        logged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    -- Medication tracking table
    CREATE TABLE medications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        medication_name VARCHAR(100) NOT NULL,
        dosage VARCHAR(50),
        frequency VARCHAR(50),
        taken_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        scheduled_time TIME,
        taken BOOLEAN DEFAULT 0,
        photo_path VARCHAR(255),
        side_effects TEXT,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    -- Vitals and wearable data table
    CREATE TABLE vitals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        heart_rate INTEGER,
        blood_pressure_systolic INTEGER,
        blood_pressure_diastolic INTEGER,
        temperature REAL,
        oxygen_saturation INTEGER,
        steps INTEGER,
        sleep_hours REAL,
        weight REAL,
        fitbit_sync BOOLEAN DEFAULT 0,
        recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    -- Mood and mental health tracking
    CREATE TABLE mood_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        mood_score INTEGER NOT NULL CHECK (mood_score >= 1 AND mood_score <= 10),
        energy_level INTEGER CHECK (energy_level >= 1 AND energy_level <= 10),
        anxiety_level INTEGER CHECK (anxiety_level >= 1 AND anxiety_level <= 10),
        notes TEXT,
        mindfulness_activity VARCHAR(100),
        activity_completed BOOLEAN DEFAULT 0,
        logged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    -- AI alerts and recommendations
    CREATE TABLE ai_alerts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        alert_type TEXT CHECK(alert_type IN ('warning', 'advice', 'reminder', 'emergency')) NOT NULL,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        recommendation TEXT,
        severity TEXT CHECK(severity IN ('low', 'medium', 'high', 'critical')) DEFAULT 'medium',
        acknowledged BOOLEAN DEFAULT 0,
        auto_generated BOOLEAN DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        acknowledged_at DATETIME NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    -- Error logs for system monitoring
    CREATE TABLE error_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NULL,
        error_type VARCHAR(50) NOT NULL,
        error_message TEXT NOT NULL,
        file_path VARCHAR(255),
        line_number INTEGER,
        user_agent TEXT,
        ip_address VARCHAR(45),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    );

    -- Caregiver communications and tasks
    CREATE TABLE caregiver_communications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        patient_id INTEGER NOT NULL,
        caregiver_id INTEGER NOT NULL,
        message_type TEXT CHECK(message_type IN ('chat', 'task', 'alert', 'note')) DEFAULT 'chat',
        subject VARCHAR(200),
        message TEXT NOT NULL,
        task_assigned BOOLEAN DEFAULT 0,
        task_completed BOOLEAN DEFAULT 0,
        task_due_date DATETIME NULL,
        priority TEXT CHECK(priority IN ('low', 'medium', 'high')) DEFAULT 'medium',
        read_status BOOLEAN DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (caregiver_id) REFERENCES users(id) ON DELETE CASCADE
    );

    -- Images and resources storage
    CREATE TABLE user_images (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        image_type TEXT CHECK(image_type IN ('medication_photo', 'gallery', 'profile', 'documentation')) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        original_filename VARCHAR(255),
        file_size INTEGER,
        description TEXT,
        related_entry_id INTEGER NULL,
        related_table VARCHAR(50) NULL,
        uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    -- Medication schedule and reminders
    CREATE TABLE medication_schedule (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        medication_name VARCHAR(100) NOT NULL,
        dosage VARCHAR(50),
        frequency_times_per_day INTEGER DEFAULT 1,
        time_1 TIME,
        time_2 TIME,
        time_3 TIME,
        time_4 TIME,
        start_date DATE NOT NULL,
        end_date DATE,
        active BOOLEAN DEFAULT 1,
        reminder_enabled BOOLEAN DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    ";
    
    // Execute schema
    $pdo->exec($schema);
    
    // Insert default users (password: admin123 and patient123)
    $pdo->exec("
    INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, emergency_contact, emergency_phone) VALUES
    ('admin', 'admin@care4mom.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 'Admin', 'User', '555-0000', 'Emergency Contact', '555-0001');
    
    INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, emergency_contact, emergency_phone, doctor_name, doctor_phone) VALUES
    ('patient1', 'patient@care4mom.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', 'Mom', 'Patient', '555-0100', 'Family Member', '555-0101', 'Dr. Smith', '555-0200');
    
    INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, emergency_contact, emergency_phone) VALUES
    ('caregiver1', 'caregiver@care4mom.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'caregiver', 'Care', 'Giver', '555-0300', 'Backup Contact', '555-0301');
    ");
}

/**
 * Include error logging functions
 */
require_once __DIR__ . '/errorlog.php';

// Global database connection
$pdo = getDBConnection();

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate secure password hash
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND active = 1");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        logError("database", "Failed to get current user: " . $e->getMessage(), __FILE__, __LINE__);
        return null;
    }
}

/**
 * Redirect to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Redirect based on user role
 */
function redirectByRole($role = null) {
    if (!$role && isLoggedIn()) {
        $user = getCurrentUser();
        $role = $user['role'] ?? 'patient';
    }
    
    // Determine correct path based on current location
    $dashboard_path = 'dashboard.php';
    if (strpos($_SERVER['REQUEST_URI'], '/public/') !== false) {
        $dashboard_path = '../dashboard.php';
    }
    
    switch ($role) {
        case 'doctor':
        case 'caregiver':
        case 'patient':
            header("Location: $dashboard_path");
            break;
        default:
            header("Location: login.php");
    }
    exit();
}
?>