/**
 * Care4Mom - Main JavaScript
 * Core JavaScript functionality for the Care4Mom application
 * Author: Care4Mom Development Team
 * Version: 1.0
 */

// Global application object
const Care4Mom = {
    // Application settings
    settings: {
        refreshInterval: 5 * 60 * 1000, // 5 minutes
        alertCheckInterval: 2 * 60 * 1000, // 2 minutes
        autoSaveInterval: 30 * 1000, // 30 seconds
    },
    
    // Initialize the application
    init: function() {
        this.setupEventListeners();
        this.initializeAccessibility();
        this.startPeriodicUpdates();
        this.setupFormValidation();
        this.initializeTooltips();
        console.log('Care4Mom application initialized');
    },
    
    // Set up global event listeners
    setupEventListeners: function() {
        // Global error handling
        window.addEventListener('error', this.handleGlobalError);
        window.addEventListener('unhandledrejection', this.handleUnhandledPromise);
        
        // Navigation helpers
        document.addEventListener('click', this.handleNavigationClicks);
        
        // Form helpers
        document.addEventListener('submit', this.handleFormSubmissions);
        
        // Keyboard shortcuts
        document.addEventListener('keydown', this.handleKeyboardShortcuts);
    },
    
    // Initialize accessibility features
    initializeAccessibility: function() {
        // Load saved accessibility preferences
        const largeText = localStorage.getItem('largeText') === 'true';
        const highContrast = localStorage.getItem('highContrast') === 'true';
        const accessibilityPanelHidden = localStorage.getItem('accessibilityPanelHidden') === 'true';
        
        if (largeText) {
            document.body.classList.add('large-text');
        }
        
        if (highContrast) {
            document.body.classList.add('high-contrast');
        }
        
        if (accessibilityPanelHidden) {
            const panel = document.getElementById('accessibilityControls');
            if (panel) panel.style.display = 'none';
        }
        
        // Set up ARIA labels and focus management
        this.setupAriaLabels();
        this.setupFocusManagement();
    },
    
    // Start periodic updates for real-time features
    startPeriodicUpdates: function() {
        // Check for new alerts periodically
        setInterval(() => {
            this.checkForNewAlerts();
        }, this.settings.alertCheckInterval);
        
        // Auto-save form data
        setInterval(() => {
            this.autoSaveFormData();
        }, this.settings.autoSaveInterval);
    },
    
    // Form validation setup
    setupFormValidation: function() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            // Add real-time validation
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('blur', this.validateField);
                input.addEventListener('input', this.clearFieldError);
            });
        });
    },
    
    // Initialize tooltips for better accessibility
    initializeTooltips: function() {
        const tooltipElements = document.querySelectorAll('[title]');
        tooltipElements.forEach(element => {
            element.addEventListener('mouseenter', this.showTooltip);
            element.addEventListener('mouseleave', this.hideTooltip);
            element.addEventListener('focus', this.showTooltip);
            element.addEventListener('blur', this.hideTooltip);
        });
    },
    
    // Handle global JavaScript errors
    handleGlobalError: function(event) {
        console.error('Global error:', event.error);
        Care4Mom.logError('javascript', event.error.message, event.filename, event.lineno);
        Care4Mom.showUserFriendlyError('An unexpected error occurred. Please refresh the page.');
    },
    
    // Handle unhandled promise rejections
    handleUnhandledPromise: function(event) {
        console.error('Unhandled promise rejection:', event.reason);
        Care4Mom.logError('promise', event.reason.toString());
    },
    
    // Handle navigation clicks
    handleNavigationClicks: function(event) {
        const target = event.target.closest('[data-navigate]');
        if (target) {
            const url = target.getAttribute('data-navigate');
            if (url) {
                window.location.href = url;
            }
        }
    },
    
    // Handle form submissions
    handleFormSubmissions: function(event) {
        const form = event.target;
        if (form.tagName === 'FORM') {
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                Care4Mom.setButtonLoading(submitBtn, true);
            }
            
            // Store form data for potential recovery
            Care4Mom.storeFormData(form);
        }
    },
    
    // Handle keyboard shortcuts
    handleKeyboardShortcuts: function(event) {
        // Alt + S: Quick symptom log
        if (event.altKey && event.key === 's') {
            event.preventDefault();
            window.location.href = 'modules/symptom.php';
        }
        
        // Alt + M: Quick medication log
        if (event.altKey && event.key === 'm') {
            event.preventDefault();
            window.location.href = 'modules/med.php';
        }
        
        // Alt + H: Go to dashboard (home)
        if (event.altKey && event.key === 'h') {
            event.preventDefault();
            window.location.href = 'dashboard.php';
        }
        
        // Escape: Close modals
        if (event.key === 'Escape') {
            Care4Mom.closeAllModals();
        }
    },
    
    // Validate individual form fields
    validateField: function(event) {
        const field = event.target;
        const value = field.value.trim();
        const type = field.type;
        const required = field.hasAttribute('required');
        
        // Clear previous errors
        Care4Mom.clearFieldError(event);
        
        // Required field validation
        if (required && !value) {
            Care4Mom.showFieldError(field, 'This field is required');
            return false;
        }
        
        // Email validation
        if (type === 'email' && value && !Care4Mom.validateEmail(value)) {
            Care4Mom.showFieldError(field, 'Please enter a valid email address');
            return false;
        }
        
        // Number range validation
        if (type === 'number' && value) {
            const min = field.getAttribute('min');
            const max = field.getAttribute('max');
            const numValue = parseFloat(value);
            
            if (min && numValue < parseFloat(min)) {
                Care4Mom.showFieldError(field, `Value must be at least ${min}`);
                return false;
            }
            
            if (max && numValue > parseFloat(max)) {
                Care4Mom.showFieldError(field, `Value must be no more than ${max}`);
                return false;
            }
        }
        
        return true;
    },
    
    // Clear field errors
    clearFieldError: function(event) {
        const field = event.target;
        const errorElement = field.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
        field.classList.remove('error');
    },
    
    // Show field error
    showFieldError: function(field, message) {
        field.classList.add('error');
        
        const errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        errorElement.textContent = message;
        errorElement.style.color = '#dc2626';
        errorElement.style.fontSize = '0.875rem';
        errorElement.style.marginTop = '0.25rem';
        
        field.parentNode.appendChild(errorElement);
    },
    
    // Utility functions
    validateEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },
    
    // Set button loading state
    setButtonLoading: function(button, loading) {
        if (loading) {
            button.disabled = true;
            button.setAttribute('data-original-text', button.innerHTML);
            button.innerHTML = '<span style="animation: spin 1s linear infinite;">⏳</span> Loading...';
        } else {
            button.disabled = false;
            const originalText = button.getAttribute('data-original-text');
            if (originalText) {
                button.innerHTML = originalText;
            }
        }
    },
    
    // Show user-friendly error message
    showUserFriendlyError: function(message) {
        const notification = document.createElement('div');
        notification.className = 'error-notification';
        notification.innerHTML = `
            <div style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: #fee2e2;
                border: 2px solid #fecaca;
                color: #dc2626;
                padding: 1rem;
                border-radius: 0.5rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                z-index: 9999;
                max-width: 400px;
            ">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 1.25rem;">❌</span>
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" 
                            style="margin-left: auto; background: none; border: none; font-size: 1.25rem; cursor: pointer;">✕</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    },
    
    // Show success message
    showSuccessMessage: function(message) {
        const notification = document.createElement('div');
        notification.className = 'success-notification';
        notification.innerHTML = `
            <div style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: #d1fae5;
                border: 2px solid #a7f3d0;
                color: #065f46;
                padding: 1rem;
                border-radius: 0.5rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                z-index: 9999;
                max-width: 400px;
            ">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 1.25rem;">✅</span>
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" 
                            style="margin-left: auto; background: none; border: none; font-size: 1.25rem; cursor: pointer;">✕</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 3000);
    },
    
    // Log errors to server
    logError: function(type, message, file, line) {
        // In a production environment, this would send the error to the server
        console.error(`[${type}] ${message} ${file ? `in ${file}` : ''} ${line ? `at line ${line}` : ''}`);
        
        // Store locally for debugging
        const errors = JSON.parse(localStorage.getItem('care4mom_errors') || '[]');
        errors.push({
            type: type,
            message: message,
            file: file,
            line: line,
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent,
            url: window.location.href
        });
        
        // Keep only last 50 errors
        if (errors.length > 50) {
            errors.splice(0, errors.length - 50);
        }
        
        localStorage.setItem('care4mom_errors', JSON.stringify(errors));
    },
    
    // Check for new alerts
    checkForNewAlerts: function() {
        // In a production environment, this would make an AJAX call to check for new alerts
        // For now, we'll just log that we're checking
        console.log('Checking for new health alerts...');
    },
    
    // Auto-save form data
    autoSaveFormData: function() {
        const forms = document.querySelectorAll('form[data-autosave]');
        forms.forEach(form => {
            const formData = new FormData(form);
            const data = {};
            
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            const formId = form.id || 'unnamed_form';
            localStorage.setItem(`care4mom_autosave_${formId}`, JSON.stringify({
                data: data,
                timestamp: Date.now(),
                url: window.location.href
            }));
        });
    },
    
    // Store form data for recovery
    storeFormData: function(form) {
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        const formId = form.id || 'form_' + Date.now();
        localStorage.setItem(`care4mom_form_backup_${formId}`, JSON.stringify({
            data: data,
            timestamp: Date.now(),
            url: window.location.href
        }));
    },
    
    // Close all open modals
    closeAllModals: function() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.style.display = 'none';
        });
    },
    
    // Set up ARIA labels for better accessibility
    setupAriaLabels: function() {
        // Add ARIA labels to buttons without text
        const iconButtons = document.querySelectorAll('button:not([aria-label])');
        iconButtons.forEach(button => {
            const title = button.getAttribute('title');
            if (title) {
                button.setAttribute('aria-label', title);
            }
        });
        
        // Add role attributes where needed
        const cards = document.querySelectorAll('.card, .module-card');
        cards.forEach(card => {
            if (!card.getAttribute('role')) {
                card.setAttribute('role', 'article');
            }
        });
    },
    
    // Set up focus management for better keyboard navigation
    setupFocusManagement: function() {
        // Skip links for keyboard users
        const skipLink = document.createElement('a');
        skipLink.href = '#mainContent';
        skipLink.textContent = 'Skip to main content';
        skipLink.className = 'skip-link';
        skipLink.style.cssText = `
            position: absolute;
            top: -40px;
            left: 6px;
            background: #000;
            color: #fff;
            padding: 8px;
            text-decoration: none;
            border-radius: 4px;
            opacity: 0;
            transition: all 0.3s;
        `;
        
        skipLink.addEventListener('focus', function() {
            this.style.top = '6px';
            this.style.opacity = '1';
        });
        
        skipLink.addEventListener('blur', function() {
            this.style.top = '-40px';
            this.style.opacity = '0';
        });
        
        document.body.insertBefore(skipLink, document.body.firstChild);
    },
    
    // Show tooltip
    showTooltip: function(event) {
        const element = event.target;
        const title = element.getAttribute('title');
        
        if (!title) return;
        
        // Remove title to prevent browser default tooltip
        element.setAttribute('data-original-title', title);
        element.removeAttribute('title');
        
        const tooltip = document.createElement('div');
        tooltip.className = 'custom-tooltip';
        tooltip.textContent = title;
        tooltip.style.cssText = `
            position: absolute;
            background: #1f2937;
            color: white;
            padding: 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            z-index: 9999;
            pointer-events: none;
            max-width: 200px;
            word-wrap: break-word;
        `;
        
        document.body.appendChild(tooltip);
        
        // Position tooltip
        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + 'px';
        tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
        
        // Adjust if tooltip goes off screen
        if (tooltip.offsetLeft + tooltip.offsetWidth > window.innerWidth) {
            tooltip.style.left = (window.innerWidth - tooltip.offsetWidth - 10) + 'px';
        }
        
        if (tooltip.offsetTop < 0) {
            tooltip.style.top = (rect.bottom + 5) + 'px';
        }
        
        element.tooltipElement = tooltip;
    },
    
    // Hide tooltip
    hideTooltip: function(event) {
        const element = event.target;
        
        if (element.tooltipElement) {
            document.body.removeChild(element.tooltipElement);
            element.tooltipElement = null;
        }
        
        // Restore original title
        const originalTitle = element.getAttribute('data-original-title');
        if (originalTitle) {
            element.setAttribute('title', originalTitle);
            element.removeAttribute('data-original-title');
        }
    }
};

// Accessibility helper functions (global for use in HTML)
function toggleLargeText() {
    document.body.classList.toggle('large-text');
    localStorage.setItem('largeText', document.body.classList.contains('large-text'));
    Care4Mom.showSuccessMessage('Large text ' + (document.body.classList.contains('large-text') ? 'enabled' : 'disabled'));
}

function toggleHighContrast() {
    document.body.classList.toggle('high-contrast');
    localStorage.setItem('highContrast', document.body.classList.contains('high-contrast'));
    Care4Mom.showSuccessMessage('High contrast ' + (document.body.classList.contains('high-contrast') ? 'enabled' : 'disabled'));
}

function toggleAccessibilityPanel() {
    const panel = document.getElementById('accessibilityControls');
    const isHidden = panel.style.display === 'none';
    panel.style.display = isHidden ? 'flex' : 'none';
    localStorage.setItem('accessibilityPanelHidden', !isHidden);
}

// Initialize the application when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    Care4Mom.init();
});

// Export for use in other scripts
window.Care4Mom = Care4Mom;