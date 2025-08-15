<?php
/**
 * Care4Mom - Login Page
 * User authentication
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

$page_title = 'Sign In';
$errors = [];
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token. Please try again.');
        }
        
        $username_or_email = sanitizeInput($_POST['username_or_email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);
        
        // Validation
        if (empty($username_or_email)) {
            $errors[] = 'Username or email is required';
        }
        if (empty($password)) {
            $errors[] = 'Password is required';
        }
        
        // Authenticate user
        if (empty($errors)) {
            $stmt = $pdo->prepare("
                SELECT id, username, password_hash, role, first_name, last_name, active 
                FROM users 
                WHERE (username = ? OR email = ?) AND active = 1
            ");
            $stmt->execute([$username_or_email, $username_or_email]);
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['password_hash'])) {
                // Successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Handle remember me
                if ($remember_me) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true); // 30 days
                    // In a production app, you'd store this token in the database
                }
                
                logActivity('login', "User logged in successfully", $user['id']);
                
                // Redirect based on role
                redirectByRole($user['role']);
                
            } else {
                $errors[] = 'Invalid username/email or password';
                logError('login', "Failed login attempt for: $username_or_email", __FILE__, __LINE__);
            }
        }
        
    } catch (Exception $e) {
        logError('login', $e->getMessage(), __FILE__, __LINE__);
        $errors[] = 'Login failed. Please try again.';
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
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Sign in to continue your care journey</p>
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
            
            <!-- Success Message -->
            <?php if (!empty($success_message)): ?>
                <div class="success-messages">
                    <div class="success-message">
                        <span class="success-icon">✅</span>
                        <span class="success-text"><?php echo htmlspecialchars($success_message); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" class="auth-form login-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="username_or_email" class="form-label">Username or Email</label>
                    <input type="text" id="username_or_email" name="username_or_email" class="form-input" 
                           value="<?php echo htmlspecialchars($_POST['username_or_email'] ?? ''); ?>" 
                           placeholder="Enter your username or email" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="Enter your password" required>
                    <div class="forgot-password">
                        <a href="#forgot-password" onclick="showForgotPasswordModal()">Forgot password?</a>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="remember-me">
                        <input type="checkbox" id="remember_me" name="remember_me" 
                               <?php echo isset($_POST['remember_me']) ? 'checked' : ''; ?>>
                        <label for="remember_me" class="form-label">Remember me for 30 days</label>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary btn-large btn-submit">
                    <span class="btn-icon">🏠</span>
                    <span class="btn-text">Sign In</span>
                </button>
            </form>
            
            <!-- Quick Access for Demo -->
            <div class="demo-accounts" style="background: #f0f9ff; border: 2px solid #bfdbfe; border-radius: var(--radius-lg); padding: var(--spacing-md); margin-bottom: var(--spacing-lg);">
                <h4 style="margin-bottom: var(--spacing-sm); color: var(--primary-color);">🧪 Demo Accounts (For Testing)</h4>
                <div class="demo-buttons" style="display: flex; gap: var(--spacing-sm); flex-wrap: wrap;">
                    <button onclick="fillDemo('patient1', 'patient123')" class="btn btn-outline" style="flex: 1; min-width: 120px;">
                        <span style="font-size: 0.9rem;">👤 Patient Demo</span>
                    </button>
                    <button onclick="fillDemo('admin', 'admin123')" class="btn btn-outline" style="flex: 1; min-width: 120px;">
                        <span style="font-size: 0.9rem;">👨‍⚕️ Doctor Demo</span>
                    </button>
                </div>
                <p style="font-size: 0.85rem; color: var(--gray-600); margin-top: var(--spacing-xs); margin-bottom: 0;">
                    Click to auto-fill login credentials for testing
                </p>
            </div>
            
            <!-- Footer Links -->
            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php" class="auth-link">Create one here</a></p>
                <p><a href="../index.php" class="auth-link">← Back to Home</a></p>
            </div>
        </div>
    </div>
    
    <!-- Forgot Password Modal -->
    <div id="forgotPasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🔑 Password Reset</h2>
                <button onclick="closeForgotPasswordModal()" class="close-btn">✕</button>
            </div>
            <div class="modal-body">
                <p>Password reset functionality is not yet implemented in this demo version.</p>
                <p><strong>For demo purposes, you can use these test accounts:</strong></p>
                <ul style="margin: var(--spacing-md) 0;">
                    <li><strong>Patient:</strong> username: <code>patient1</code>, password: <code>patient123</code></li>
                    <li><strong>Doctor:</strong> username: <code>admin</code>, password: <code>admin123</code></li>
                </ul>
                <p>In a production environment, this would send a secure password reset link to your email.</p>
                <div style="text-align: center; margin-top: var(--spacing-lg);">
                    <button onclick="closeForgotPasswordModal()" class="btn btn-primary">OK</button>
                </div>
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
            
            // Focus on username field
            document.getElementById('username_or_email').focus();
        });
        
        // Demo account helper
        function fillDemo(username, password) {
            document.getElementById('username_or_email').value = username;
            document.getElementById('password').value = password;
            
            // Optional: auto-submit after a brief delay
            setTimeout(() => {
                if (confirm('Auto-login with demo account?')) {
                    document.querySelector('.login-form').submit();
                }
            }, 100);
        }
        
        // Forgot password modal
        function showForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').style.display = 'flex';
        }
        
        function closeForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').style.display = 'none';
        }
        
        // Form submission with loading state
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            const submitBtn = document.querySelector('.btn-submit');
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('forgotPasswordModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };
    </script>
</body>
</html>