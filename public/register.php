<?php
/**
 * Care4Mom - Registration Page
 * User registration with role selection
 * Author: Care4Mom Development Team
 * Version: 1.0
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/errorlog.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectByRole();
}

$page_title = 'Create Account';
$errors = [];
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token. Please try again.');
        }
        
        // Validate input
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = sanitizeInput($_POST['role'] ?? 'patient');
        $first_name = sanitizeInput($_POST['first_name'] ?? '');
        $last_name = sanitizeInput($_POST['last_name'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $emergency_contact = sanitizeInput($_POST['emergency_contact'] ?? '');
        $emergency_phone = sanitizeInput($_POST['emergency_phone'] ?? '');
        $doctor_name = sanitizeInput($_POST['doctor_name'] ?? '');
        $doctor_phone = sanitizeInput($_POST['doctor_phone'] ?? '');
        
        // Validation
        if (empty($username)) $errors[] = 'Username is required';
        if (strlen($username) < 3) $errors[] = 'Username must be at least 3 characters';
        if (empty($email)) $errors[] = 'Email is required';
        if (!validateEmail($email)) $errors[] = 'Please enter a valid email address';
        if (empty($password)) $errors[] = 'Password is required';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
        if ($password !== $confirm_password) $errors[] = 'Passwords do not match';
        if (empty($first_name)) $errors[] = 'First name is required';
        if (empty($last_name)) $errors[] = 'Last name is required';
        if (!in_array($role, ['patient', 'caregiver', 'doctor'])) $errors[] = 'Invalid role selected';
        
        // Check if username/email already exists
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = 'Username or email already exists';
            }
        }
        
        // Create account if no errors
        if (empty($errors)) {
            $password_hash = hashPassword($password);
            
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, 
                                 emergency_contact, emergency_phone, doctor_name, doctor_phone) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $username, $email, $password_hash, $role, $first_name, $last_name, $phone,
                $emergency_contact, $emergency_phone, $doctor_name, $doctor_phone
            ]);
            
            if ($result) {
                $user_id = $pdo->lastInsertId();
                logActivity('account_created', "New $role account created", $user_id);
                
                // Auto-login the user
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                
                // Redirect to dashboard
                header('Location: ../dashboard.php');
                exit();
            } else {
                $errors[] = 'Account creation failed. Please try again.';
            }
        }
        
    } catch (Exception $e) {
        logError('registration', $e->getMessage(), __FILE__, __LINE__);
        $errors[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Care4Mom</title>
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Meta tags -->
    <meta name="theme-color" content="#6366f1">
    <link rel="icon" type="image/x-icon" href="../assets/images/icons/favicon.ico">
</head>
<body class="auth-page">
    
    <!-- Accessibility Controls -->
    <div class="accessibility-controls" id="accessibilityControls">
        <button onclick="toggleLargeText()" class="accessibility-btn" title="Toggle Large Text">
            <span class="icon">🔍</span>
            <span class="text">Large Text</span>
        </button>
        <button onclick="toggleHighContrast()" class="accessibility-btn" title="Toggle High Contrast">
            <span class="icon">🌓</span>
            <span class="text">High Contrast</span>
        </button>
    </div>
    
    <div class="auth-container">
        <div class="auth-background">
            <div class="gradient-overlay"></div>
        </div>
        
        <div class="auth-content">
            <!-- Header -->
            <div class="auth-header">
                <a href="../index.php" class="auth-brand">
                    <span class="brand-icon">💝</span>
                    <span class="brand-text">Care4Mom</span>
                </a>
                <h1 class="auth-title">Create Your Account</h1>
                <p class="auth-subtitle">Join our caring community and start tracking your health journey</p>
            </div>
            
            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <?php foreach ($errors as $error): ?>
                        <div class="error-message">
                            <span class="error-icon">❌</span>
                            <span class="error-text"><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Registration Form -->
            <form method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <!-- Role Selection (Most Important) -->
                <div class="form-group role-selection">
                    <label class="form-label">👤 I am a:</label>
                    <div class="role-options">
                        <div class="role-option">
                            <input type="radio" id="role_patient" name="role" value="patient" 
                                   <?php echo (($_POST['role'] ?? 'patient') === 'patient') ? 'checked' : ''; ?>>
                            <label for="role_patient" class="role-label">
                                <span class="role-icon">🏥</span>
                                <span class="role-title">Patient</span>
                                <span class="role-description">I am managing my own health condition</span>
                            </label>
                        </div>
                        
                        <div class="role-option">
                            <input type="radio" id="role_caregiver" name="role" value="caregiver"
                                   <?php echo (($_POST['role'] ?? '') === 'caregiver') ? 'checked' : ''; ?>>
                            <label for="role_caregiver" class="role-label">
                                <span class="role-icon">❤️</span>
                                <span class="role-title">Caregiver</span>
                                <span class="role-description">I help care for a family member or loved one</span>
                            </label>
                        </div>
                        
                        <div class="role-option">
                            <input type="radio" id="role_doctor" name="role" value="doctor"
                                   <?php echo (($_POST['role'] ?? '') === 'doctor') ? 'checked' : ''; ?>>
                            <label for="role_doctor" class="role-label">
                                <span class="role-icon">👨‍⚕️</span>
                                <span class="role-title">Healthcare Provider</span>
                                <span class="role-description">I provide medical care to patients</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Basic Information -->
                <div class="form-section">
                    <h3 class="section-title">Basic Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" id="first_name" name="first_name" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-input" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                        <small class="form-hint">Choose a username you'll remember easily</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-input" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Password Section -->
                <div class="form-section">
                    <h3 class="section-title">Password</h3>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-input" required>
                        <small class="form-hint">At least 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                    </div>
                </div>
                
                <!-- Emergency Contacts -->
                <div class="form-section">
                    <h3 class="section-title">Emergency Contacts (Optional but Recommended)</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="emergency_contact" class="form-label">Emergency Contact Name</label>
                            <input type="text" id="emergency_contact" name="emergency_contact" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['emergency_contact'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="emergency_phone" class="form-label">Emergency Contact Phone</label>
                            <input type="tel" id="emergency_phone" name="emergency_phone" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['emergency_phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="doctor_name" class="form-label">Doctor/Oncologist Name</label>
                            <input type="text" id="doctor_name" name="doctor_name" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['doctor_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="doctor_phone" class="form-label">Doctor Phone</label>
                            <input type="tel" id="doctor_phone" name="doctor_phone" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['doctor_phone'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary btn-large btn-submit">
                    <span class="btn-icon">✨</span>
                    <span class="btn-text">Create My Account</span>
                </button>
            </form>
            
            <!-- Footer Links -->
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php" class="auth-link">Sign in here</a></p>
                <p><a href="../index.php" class="auth-link">← Back to Home</a></p>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script>
        // Accessibility functions
        function toggleLargeText() {
            document.body.classList.toggle('large-text');
            localStorage.setItem('largeText', document.body.classList.contains('large-text'));
        }
        
        function toggleHighContrast() {
            document.body.classList.toggle('high-contrast');
            localStorage.setItem('highContrast', document.body.classList.contains('high-contrast'));
        }
        
        // Initialize accessibility settings
        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('largeText') === 'true') {
                document.body.classList.add('large-text');
            }
            if (localStorage.getItem('highContrast') === 'true') {
                document.body.classList.add('high-contrast');
            }
            
            // Form validation
            initializeFormValidation();
        });
        
        // Form validation
        function initializeFormValidation() {
            const form = document.querySelector('.auth-form');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            // Real-time password matching
            function checkPasswordMatch() {
                if (confirmPassword.value && password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
            
            password.addEventListener('input', checkPasswordMatch);
            confirmPassword.addEventListener('input', checkPasswordMatch);
            
            // Form submission
            form.addEventListener('submit', function(e) {
                checkPasswordMatch();
                
                if (!form.checkValidity()) {
                    e.preventDefault();
                    showValidationErrors();
                }
            });
        }
        
        function showValidationErrors() {
            const inputs = document.querySelectorAll('.form-input:invalid');
            inputs.forEach(input => {
                input.classList.add('error');
                input.addEventListener('input', function() {
                    this.classList.remove('error');
                }, { once: true });
            });
        }
    </script>
</body>
</html>