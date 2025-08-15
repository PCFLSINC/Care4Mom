<?php
/**
 * Care4Mom - Header Template
 * Common header for all Care4Mom pages
 * Author: Care4Mom Development Team
 * Version: 1.0
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/errorlog.php';

// Get current user info
$current_user = getCurrentUser();
$is_logged_in = isLoggedIn();
$page_title = $page_title ?? 'Care4Mom';
$body_class = $body_class ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Care4Mom</title>
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Accessibility CSS -->
    <link rel="stylesheet" href="assets/css/accessibility.css">
    
    <!-- Icons and Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Meta tags for mobile -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="theme-color" content="#6366f1">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/icons/favicon.ico">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo $is_logged_in ? generateCSRFToken() : ''; ?>">
</head>
<body class="<?php echo htmlspecialchars($body_class); ?>">
    
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
        <button onclick="toggleAccessibilityPanel()" class="accessibility-btn" title="Hide Accessibility Panel">
            <span class="icon">✨</span>
            <span class="text">Hide Panel</span>
        </button>
    </div>

    <?php if ($is_logged_in && $current_user): ?>
    <!-- Main Navigation (for logged-in users) -->
    <nav class="main-nav" id="mainNav">
        <div class="nav-container">
            <!-- Logo/Brand -->
            <div class="nav-brand">
                <a href="dashboard.php" class="brand-link">
                    <span class="brand-icon">💝</span>
                    <span class="brand-text">Care4Mom</span>
                </a>
            </div>
            
            <!-- Navigation Items -->
            <div class="nav-items">
                <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-text">Dashboard</span>
                </a>
                
                <a href="modules/symptom.php" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Symptoms</span>
                </a>
                
                <a href="modules/med.php" class="nav-item">
                    <span class="nav-icon">💊</span>
                    <span class="nav-text">Medications</span>
                </a>
                
                <a href="modules/vitals.php" class="nav-item">
                    <span class="nav-icon">❤️</span>
                    <span class="nav-text">Vitals</span>
                </a>
                
                <a href="modules/mood.php" class="nav-item">
                    <span class="nav-icon">😊</span>
                    <span class="nav-text">Mood</span>
                </a>
                
                <a href="modules/report.php" class="nav-item">
                    <span class="nav-icon">📋</span>
                    <span class="nav-text">Reports</span>
                </a>
                
                <?php if ($current_user['role'] === 'caregiver' || $current_user['role'] === 'doctor'): ?>
                <a href="modules/caregiver.php" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Care Team</span>
                </a>
                <?php endif; ?>
            </div>
            
            <!-- User Menu -->
            <div class="nav-user">
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($current_user['first_name']); ?></span>
                    <span class="user-role"><?php echo ucfirst($current_user['role']); ?></span>
                </div>
                <div class="user-actions">
                    <button onclick="showUserMenu()" class="user-menu-btn">
                        <span class="user-avatar">👤</span>
                    </button>
                    <div class="user-menu" id="userMenu" style="display: none;">
                        <a href="profile.php" class="menu-item">
                            <span class="menu-icon">⚙️</span>
                            <span class="menu-text">Settings</span>
                        </a>
                        <a href="help.php" class="menu-item">
                            <span class="menu-icon">❓</span>
                            <span class="menu-text">Help</span>
                        </a>
                        <a href="logout.php" class="menu-item">
                            <span class="menu-icon">🚪</span>
                            <span class="menu-text">Logout</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <span class="hamburger"></span>
                <span class="hamburger"></span>
                <span class="hamburger"></span>
            </button>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Mobile Menu Overlay (for logged-in users) -->
    <?php if ($is_logged_in): ?>
    <div class="mobile-menu-overlay" id="mobileMenuOverlay" onclick="closeMobileMenu()"></div>
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-header">
            <span class="mobile-brand">💝 Care4Mom</span>
            <button onclick="closeMobileMenu()" class="mobile-close">✕</button>
        </div>
        <div class="mobile-menu-items">
            <a href="dashboard.php" class="mobile-menu-item">
                <span class="icon">🏠</span>
                <span class="text">Dashboard</span>
            </a>
            <a href="modules/symptom.php" class="mobile-menu-item">
                <span class="icon">📊</span>
                <span class="text">Symptoms</span>
            </a>
            <a href="modules/med.php" class="mobile-menu-item">
                <span class="icon">💊</span>
                <span class="text">Medications</span>
            </a>
            <a href="modules/vitals.php" class="mobile-menu-item">
                <span class="icon">❤️</span>
                <span class="text">Vitals</span>
            </a>
            <a href="modules/mood.php" class="mobile-menu-item">
                <span class="icon">😊</span>
                <span class="text">Mood</span>
            </a>
            <a href="modules/report.php" class="mobile-menu-item">
                <span class="icon">📋</span>
                <span class="text">Reports</span>
            </a>
            <a href="profile.php" class="mobile-menu-item">
                <span class="icon">⚙️</span>
                <span class="text">Settings</span>
            </a>
            <a href="logout.php" class="mobile-menu-item">
                <span class="icon">🚪</span>
                <span class="text">Logout</span>
            </a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main Content Container -->
    <main class="main-content" id="mainContent"><?php