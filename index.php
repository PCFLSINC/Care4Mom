<?php
/**
 * Care4Mom - Landing Page
 * Welcome page with mission statement and account access
 * Author: Care4Mom Development Team
 * Version: 1.0
 */

// Start session
session_start();

// Redirect if already logged in
require_once 'includes/db.php';
if (isLoggedIn()) {
    redirectByRole();
}

$page_title = 'Welcome to Care4Mom';
$body_class = 'landing-page';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/landing.css">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Meta tags -->
    <meta name="description" content="Care4Mom - A comprehensive care tracking app for Stage 4 lung cancer patients and their families">
    <meta name="theme-color" content="#6366f1">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/icons/favicon.ico">
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
    </div>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-background">
            <div class="gradient-overlay"></div>
            <div class="floating-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
                <div class="shape shape-4"></div>
            </div>
        </div>
        
        <div class="hero-content">
            <div class="hero-brand">
                <div class="brand-icon">💝</div>
                <h1 class="brand-title">Care4Mom</h1>
            </div>
            
            <div class="hero-mission">
                <h2 class="mission-title">Comprehensive Care Tracking for Terminal Cancer Patients</h2>
                <p class="mission-description">
                    A compassionate, easy-to-use app designed specifically for Stage 4 lung cancer patients and their families. 
                    Track symptoms, manage medications, monitor vitals, and maintain clear communication with your care team.
                </p>
            </div>
            
            <div class="hero-features">
                <div class="feature-highlight">
                    <span class="feature-icon">📊</span>
                    <span class="feature-text">Simple Symptom Tracking</span>
                </div>
                <div class="feature-highlight">
                    <span class="feature-icon">💊</span>
                    <span class="feature-text">Medication Management</span>
                </div>
                <div class="feature-highlight">
                    <span class="feature-icon">❤️</span>
                    <span class="feature-text">Vitals Monitoring</span>
                </div>
                <div class="feature-highlight">
                    <span class="feature-icon">🤖</span>
                    <span class="feature-text">AI Health Insights</span>
                </div>
            </div>
            
            <div class="hero-actions">
                <a href="public/register.php" class="btn btn-primary btn-hero">
                    <span class="btn-icon">✨</span>
                    <span class="btn-text">Create Account</span>
                </a>
                <a href="public/login.php" class="btn btn-outline btn-hero">
                    <span class="btn-icon">🏠</span>
                    <span class="btn-text">Sign In</span>
                </a>
            </div>
        </div>
    </section>
    
    <!-- Benefits Section -->
    <section class="benefits">
        <div class="container">
            <h2 class="section-title">Designed with Love and Understanding</h2>
            <p class="section-subtitle">
                Every feature is crafted with accessibility and ease-of-use in mind, specifically for those facing terminal illness and their caregivers.
            </p>
            
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">🌟</div>
                    <h3 class="benefit-title">Senior-Friendly Interface</h3>
                    <p class="benefit-description">
                        Large buttons, clear text, high contrast options, and intuitive navigation designed for easy use.
                    </p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">⚡</div>
                    <h3 class="benefit-title">One-Tap Logging</h3>
                    <p class="benefit-description">
                        Quick symptom entry when energy is low. Just tap, rate severity, and add optional notes.
                    </p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">👨‍⚕️</div>
                    <h3 class="benefit-title">Doctor-Ready Reports</h3>
                    <p class="benefit-description">
                        Generate comprehensive reports that oncologists need to adjust treatment plans effectively.
                    </p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">👥</div>
                    <h3 class="benefit-title">Family Connection</h3>
                    <p class="benefit-description">
                        Multiple users can collaborate on care, share updates, and coordinate support efforts.
                    </p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">🔒</div>
                    <h3 class="benefit-title">Private & Secure</h3>
                    <p class="benefit-description">
                        Your health data stays private and secure, with options for selective sharing with care team.
                    </p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">📱</div>
                    <h3 class="benefit-title">Works Anywhere</h3>
                    <p class="benefit-description">
                        Responsive design works on phones, tablets, and computers. No app store downloads required.
                    </p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- How It Helps Section -->
    <section class="how-it-helps">
        <div class="container">
            <h2 class="section-title">How Care4Mom Helps</h2>
            
            <div class="help-scenarios">
                <div class="scenario">
                    <div class="scenario-icon">😵‍💫</div>
                    <h3 class="scenario-title">When Experiencing Dizziness</h3>
                    <p class="scenario-description">
                        Quickly log the episode with severity level, note any triggers, and track patterns over time. 
                        The AI advisor provides comfort tips and alerts your care team if needed.
                    </p>
                </div>
                
                <div class="scenario">
                    <div class="scenario-icon">🔥</div>
                    <h3 class="scenario-title">Hot Hands & Feet Episodes</h3>
                    <p class="scenario-description">
                        Track these specific symptoms with timing and intensity. Correlate with medication schedules 
                        to help doctors adjust treatment for better comfort.
                    </p>
                </div>
                
                <div class="scenario">
                    <div class="scenario-icon">🤢</div>
                    <h3 class="scenario-title">Managing Stomach Pain</h3>
                    <p class="scenario-description">
                        Log pain levels, note food intake, and medication timing. Get AI suggestions for diet 
                        modifications and comfort measures between doctor visits.
                    </p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Emergency Support -->
    <section class="emergency-support">
        <div class="container">
            <div class="emergency-card">
                <div class="emergency-content">
                    <h2 class="emergency-title">🚨 Always Here When You Need Help</h2>
                    <p class="emergency-description">
                        Care4Mom includes quick access to emergency contacts, your doctor's information, 
                        and crisis support resources. Help is always just one tap away.
                    </p>
                    <div class="emergency-features">
                        <span class="emergency-feature">📞 One-tap emergency calling</span>
                        <span class="emergency-feature">🏥 Doctor contact integration</span>
                        <span class="emergency-feature">💬 Crisis support hotlines</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Call to Action -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">Start Your Care Journey Today</h2>
                <p class="cta-description">
                    Join families who are already using Care4Mom to better manage terminal cancer care 
                    and maintain clear communication with their medical teams.
                </p>
                <div class="cta-actions">
                    <a href="public/register.php" class="btn btn-primary btn-large">
                        <span class="btn-icon">🌟</span>
                        <span class="btn-text">Get Started Now</span>
                    </a>
                    <a href="#learn-more" class="btn btn-outline btn-large">
                        <span class="btn-icon">📖</span>
                        <span class="btn-text">Learn More</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <span class="logo-icon">💝</span>
                        <span class="logo-text">Care4Mom</span>
                    </div>
                    <p class="footer-tagline">Compassionate care tracking for terminal cancer patients</p>
                </div>
                
                <div class="footer-links">
                    <div class="link-group">
                        <h4 class="link-title">Support</h4>
                        <a href="tel:555-2273" class="footer-link">📞 555-CARE (2273)</a>
                        <a href="mailto:support@care4mom.com" class="footer-link">✉️ support@care4mom.com</a>
                    </div>
                    <div class="link-group">
                        <h4 class="link-title">Resources</h4>
                        <a href="#help" class="footer-link">Help Center</a>
                        <a href="#privacy" class="footer-link">Privacy Policy</a>
                        <a href="#accessibility" class="footer-link">Accessibility</a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p class="copyright">© 2024 Care4Mom. Built with love and understanding.</p>
                <p class="disclaimer">
                    This app is designed to supplement, not replace, professional medical care. 
                    Always consult your healthcare team for medical decisions.
                </p>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script>
        // Accessibility functions
        function toggleLargeText() {
            document.body.classList.toggle('large-text');
            localStorage.setItem('largeText', document.body.classList.contains('large-text'));
            showAccessibilityFeedback('Large text ' + (document.body.classList.contains('large-text') ? 'enabled' : 'disabled'));
        }
        
        function toggleHighContrast() {
            document.body.classList.toggle('high-contrast');
            localStorage.setItem('highContrast', document.body.classList.contains('high-contrast'));
            showAccessibilityFeedback('High contrast ' + (document.body.classList.contains('high-contrast') ? 'enabled' : 'disabled'));
        }
        
        function showAccessibilityFeedback(message) {
            const feedback = document.createElement('div');
            feedback.className = 'accessibility-feedback';
            feedback.textContent = message;
            document.body.appendChild(feedback);
            setTimeout(() => document.body.removeChild(feedback), 3000);
        }
        
        // Initialize accessibility settings
        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('largeText') === 'true') {
                document.body.classList.add('large-text');
            }
            if (localStorage.getItem('highContrast') === 'true') {
                document.body.classList.add('high-contrast');
            }
            
            // Animate floating shapes
            animateShapes();
        });
        
        // Animate floating shapes
        function animateShapes() {
            const shapes = document.querySelectorAll('.shape');
            shapes.forEach((shape, index) => {
                const duration = 3000 + (index * 500);
                const delay = index * 200;
                
                setInterval(() => {
                    shape.style.transform = `translate(${Math.random() * 20 - 10}px, ${Math.random() * 20 - 10}px) rotate(${Math.random() * 360}deg)`;
                }, duration);
            });
        }
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>