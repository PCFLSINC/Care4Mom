-- Care4Mom Database Schema
-- Database for Stage 4 Lung Cancer Care Tracking Application
-- Created: 2024
-- Usage: Import into phpMyAdmin (root user, blank password)

CREATE DATABASE IF NOT EXISTS care4mom;
USE care4mom;

-- Users table for authentication and roles
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('patient', 'caregiver', 'doctor') DEFAULT 'patient',
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    emergency_contact VARCHAR(100),
    emergency_phone VARCHAR(20),
    doctor_name VARCHAR(100),
    doctor_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    active BOOLEAN DEFAULT TRUE
);

-- Symptoms tracking table
CREATE TABLE symptoms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    symptom_name VARCHAR(100) NOT NULL,
    severity INT NOT NULL CHECK (severity >= 1 AND severity <= 10),
    notes TEXT,
    voice_note_path VARCHAR(255),
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, logged_at),
    INDEX idx_symptom_severity (symptom_name, severity)
);

-- Medication tracking table
CREATE TABLE medications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    medication_name VARCHAR(100) NOT NULL,
    dosage VARCHAR(50),
    frequency VARCHAR(50),
    taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    scheduled_time TIME,
    taken BOOLEAN DEFAULT FALSE,
    photo_path VARCHAR(255),
    side_effects TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, taken_at),
    INDEX idx_medication_taken (medication_name, taken)
);

-- Vitals and wearable data table
CREATE TABLE vitals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    heart_rate INT,
    blood_pressure_systolic INT,
    blood_pressure_diastolic INT,
    temperature DECIMAL(4,1),
    oxygen_saturation INT,
    steps INT,
    sleep_hours DECIMAL(3,1),
    weight DECIMAL(5,1),
    fitbit_sync BOOLEAN DEFAULT FALSE,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, recorded_at)
);

-- Mood and mental health tracking
CREATE TABLE mood_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mood_score INT NOT NULL CHECK (mood_score >= 1 AND mood_score <= 10),
    energy_level INT CHECK (energy_level >= 1 AND energy_level <= 10),
    anxiety_level INT CHECK (anxiety_level >= 1 AND anxiety_level <= 10),
    notes TEXT,
    mindfulness_activity VARCHAR(100),
    activity_completed BOOLEAN DEFAULT FALSE,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, logged_at)
);

-- AI alerts and recommendations
CREATE TABLE ai_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    alert_type ENUM('warning', 'advice', 'reminder', 'emergency') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    recommendation TEXT,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    acknowledged BOOLEAN DEFAULT FALSE,
    auto_generated BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    acknowledged_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_severity (user_id, severity),
    INDEX idx_acknowledged (acknowledged, created_at)
);

-- Error logs for system monitoring
CREATE TABLE error_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    error_type VARCHAR(50) NOT NULL,
    error_message TEXT NOT NULL,
    file_path VARCHAR(255),
    line_number INT,
    user_agent TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_error_type (error_type, created_at),
    INDEX idx_user_date (user_id, created_at)
);

-- Caregiver communications and tasks
CREATE TABLE caregiver_communications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    caregiver_id INT NOT NULL,
    message_type ENUM('chat', 'task', 'alert', 'note') DEFAULT 'chat',
    subject VARCHAR(200),
    message TEXT NOT NULL,
    task_assigned BOOLEAN DEFAULT FALSE,
    task_completed BOOLEAN DEFAULT FALSE,
    task_due_date DATETIME NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    read_status BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (caregiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_patient_caregiver (patient_id, caregiver_id),
    INDEX idx_task_status (task_assigned, task_completed)
);

-- Images and resources storage
CREATE TABLE user_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image_type ENUM('medication_photo', 'gallery', 'profile', 'documentation') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255),
    file_size INT,
    description TEXT,
    related_entry_id INT NULL, -- Links to medication, symptom, etc.
    related_table VARCHAR(50) NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_type (user_id, image_type),
    INDEX idx_related (related_table, related_entry_id)
);

-- Medication schedule and reminders
CREATE TABLE medication_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    medication_name VARCHAR(100) NOT NULL,
    dosage VARCHAR(50),
    frequency_times_per_day INT DEFAULT 1,
    time_1 TIME,
    time_2 TIME,
    time_3 TIME,
    time_4 TIME,
    start_date DATE NOT NULL,
    end_date DATE,
    active BOOLEAN DEFAULT TRUE,
    reminder_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_active (user_id, active)
);

-- Pre-configured symptoms for quick logging
INSERT INTO symptoms (user_id, symptom_name, severity, notes, logged_at) VALUES
(1, 'Dizziness', 1, 'Sample entry - delete after first user registration', NOW()),
(1, 'Hot hands/feet', 1, 'Sample entry - delete after first user registration', NOW()),
(1, 'Stomach pain', 1, 'Sample entry - delete after first user registration', NOW()),
(1, 'Fatigue', 1, 'Sample entry - delete after first user registration', NOW()),
(1, 'Nausea', 1, 'Sample entry - delete after first user registration', NOW()),
(1, 'Headache', 1, 'Sample entry - delete after first user registration', NOW()),
(1, 'Breathing issues', 1, 'Sample entry - delete after first user registration', NOW()),
(1, 'General pain', 1, 'Sample entry - delete after first user registration', NOW());

-- Create default admin user (password: admin123)
INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, emergency_contact, emergency_phone) VALUES
('admin', 'admin@care4mom.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 'Admin', 'User', '555-0000', 'Emergency Contact', '555-0001');

-- Create sample patient user (password: patient123)
INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, emergency_contact, emergency_phone, doctor_name, doctor_phone) VALUES
('patient1', 'patient@care4mom.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', 'Mom', 'Patient', '555-0100', 'Family Member', '555-0101', 'Dr. Smith', '555-0200');

COMMIT;