    </main>
    
    <!-- Emergency Contact Button (always visible) -->
    <div class="emergency-contact" id="emergencyContact">
        <button onclick="showEmergencyModal()" class="emergency-btn" title="Emergency Contact">
            <span class="emergency-icon">🚨</span>
            <span class="emergency-text">Emergency</span>
        </button>
    </div>
    
    <!-- Emergency Contact Modal -->
    <div id="emergencyModal" class="modal emergency-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🚨 Emergency Contacts</h2>
                <button onclick="closeEmergencyModal()" class="close-btn">✕</button>
            </div>
            <div class="modal-body">
                <?php if ($is_logged_in && $current_user): ?>
                <div class="emergency-contacts">
                    <?php if (!empty($current_user['emergency_contact'])): ?>
                    <div class="contact-card">
                        <div class="contact-info">
                            <h3>Primary Emergency Contact</h3>
                            <p class="contact-name"><?php echo htmlspecialchars($current_user['emergency_contact']); ?></p>
                            <?php if (!empty($current_user['emergency_phone'])): ?>
                            <a href="tel:<?php echo htmlspecialchars($current_user['emergency_phone']); ?>" class="contact-phone">
                                📞 <?php echo htmlspecialchars($current_user['emergency_phone']); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($current_user['doctor_name'])): ?>
                    <div class="contact-card">
                        <div class="contact-info">
                            <h3>Doctor</h3>
                            <p class="contact-name"><?php echo htmlspecialchars($current_user['doctor_name']); ?></p>
                            <?php if (!empty($current_user['doctor_phone'])): ?>
                            <a href="tel:<?php echo htmlspecialchars($current_user['doctor_phone']); ?>" class="contact-phone">
                                📞 <?php echo htmlspecialchars($current_user['doctor_phone']); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="emergency-services">
                    <h3>Emergency Services</h3>
                    <div class="service-buttons">
                        <a href="tel:911" class="service-btn emergency">
                            <span class="service-icon">🚨</span>
                            <span class="service-text">Call 911</span>
                        </a>
                        <a href="tel:988" class="service-btn crisis">
                            <span class="service-icon">💬</span>
                            <span class="service-text">Crisis Hotline (988)</span>
                        </a>
                        <a href="tel:211" class="service-btn support">
                            <span class="service-icon">ℹ️</span>
                            <span class="service-text">Help & Info (211)</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p class="loading-text">Loading...</p>
        </div>
    </div>
    
    <!-- Core JavaScript -->
    <?php 
    require_once __DIR__ . '/common.php';
    $assets_path = get_assets_path();
    ?>
    <script src="<?php echo $assets_path; ?>js/main.js"></script>
    
    <!-- Additional page-specific scripts -->
    <?php if (isset($additional_scripts)): ?>
        <?php foreach ($additional_scripts as $script): ?>
            <script src="<?php echo htmlspecialchars($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline JavaScript for immediate functionality -->
    <script>
        // Initialize accessibility settings from localStorage
        document.addEventListener('DOMContentLoaded', function() {
            initializeAccessibility();
            
            // Initialize tooltips for accessibility
            initializeTooltips();
            
            // Check for saved preferences
            loadUserPreferences();
        });
        
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
        
        function toggleAccessibilityPanel() {
            const panel = document.getElementById('accessibilityControls');
            const isHidden = panel.style.display === 'none';
            panel.style.display = isHidden ? 'flex' : 'none';
            localStorage.setItem('accessibilityPanelHidden', !isHidden);
        }
        
        function initializeAccessibility() {
            // Restore accessibility settings
            if (localStorage.getItem('largeText') === 'true') {
                document.body.classList.add('large-text');
            }
            if (localStorage.getItem('highContrast') === 'true') {
                document.body.classList.add('high-contrast');
            }
            if (localStorage.getItem('accessibilityPanelHidden') === 'true') {
                document.getElementById('accessibilityControls').style.display = 'none';
            }
        }
        
        function showAccessibilityFeedback(message) {
            // Create temporary feedback element
            const feedback = document.createElement('div');
            feedback.className = 'accessibility-feedback';
            feedback.textContent = message;
            document.body.appendChild(feedback);
            
            // Remove after 3 seconds
            setTimeout(() => {
                document.body.removeChild(feedback);
            }, 3000);
        }
        
        // Emergency modal functions
        function showEmergencyModal() {
            document.getElementById('emergencyModal').style.display = 'flex';
        }
        
        function closeEmergencyModal() {
            document.getElementById('emergencyModal').style.display = 'none';
        }
        
        // User menu functions
        function showUserMenu() {
            const menu = document.getElementById('userMenu');
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        }
        
        // Mobile menu functions
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('mobileMenuOverlay');
            
            if (menu && overlay) {
                menu.classList.toggle('active');
                overlay.classList.toggle('active');
            }
        }
        
        function closeMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('mobileMenuOverlay');
            
            if (menu && overlay) {
                menu.classList.remove('active');
                overlay.classList.remove('active');
            }
        }
        
        // Loading overlay functions
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }
        
        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }
        
        // Initialize tooltips
        function initializeTooltips() {
            const tooltipElements = document.querySelectorAll('[title]');
            tooltipElements.forEach(element => {
                element.addEventListener('mouseenter', showTooltip);
                element.addEventListener('mouseleave', hideTooltip);
            });
        }
        
        function showTooltip(event) {
            // Tooltip implementation for accessibility
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = event.target.getAttribute('title');
            document.body.appendChild(tooltip);
            
            // Position tooltip
            const rect = event.target.getBoundingClientRect();
            tooltip.style.left = rect.left + 'px';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
            
            event.target.tooltipElement = tooltip;
        }
        
        function hideTooltip(event) {
            if (event.target.tooltipElement) {
                document.body.removeChild(event.target.tooltipElement);
                event.target.tooltipElement = null;
            }
        }
        
        // Load user preferences
        function loadUserPreferences() {
            // This function will load user-specific preferences
            // Can be expanded for theme preferences, language, etc.
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
            
            // Close user menu when clicking outside
            const userMenu = document.getElementById('userMenu');
            if (userMenu && !event.target.closest('.user-actions')) {
                userMenu.style.display = 'none';
            }
        };
    </script>
    
</body>
</html><?php