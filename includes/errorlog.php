<?php
/**
 * Care4Mom - Error Logging System
 * Comprehensive error handling and logging for Care4Mom app
 * Author: Care4Mom Development Team
 * Version: 1.0
 */

/**
 * Log error to database and file
 */
function logError($type, $message, $file = null, $line = null, $user_id = null) {
    global $pdo;
    
    // Get user ID from session if not provided
    if (!$user_id && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    
    // Get user agent and IP
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    // Log to file
    $log_message = date('Y-m-d H:i:s') . " [$type] $message";
    if ($file) $log_message .= " in $file";
    if ($line) $log_message .= " on line $line";
    $log_message .= " (User: $user_id, IP: $ip_address)\n";
    
    error_log($log_message, 3, __DIR__ . '/../logs/care4mom_errors.log');
    
    // Log to database if connection exists
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO error_logs (user_id, error_type, error_message, file_path, line_number, user_agent, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $type, $message, $file, $line, $user_agent, $ip_address]);
        } catch (PDOException $e) {
            // Fallback to file logging only
            error_log("Failed to log to database: " . $e->getMessage());
        }
    }
}

/**
 * Display user-friendly error modal
 */
function showErrorModal($title, $message, $show_contact = true) {
    $contact_info = $show_contact ? "
        <div class='error-contact'>
            <p><strong>Need help?</strong></p>
            <p>📞 Call: <a href='tel:555-CARE'>555-CARE (2273)</a></p>
            <p>✉️ Email: <a href='mailto:support@care4mom.com'>support@care4mom.com</a></p>
        </div>
    " : "";
    
    return "
    <div id='errorModal' class='modal error-modal' style='display: block;'>
        <div class='modal-content error-content'>
            <div class='error-icon'>⚠️</div>
            <h2>$title</h2>
            <p>$message</p>
            $contact_info
            <button onclick='closeErrorModal()' class='btn btn-primary'>OK</button>
        </div>
    </div>
    <script>
        function closeErrorModal() {
            document.getElementById('errorModal').style.display = 'none';
        }
    </script>
    ";
}

/**
 * Handle uncaught exceptions
 */
function handleException($exception) {
    logError('exception', $exception->getMessage(), $exception->getFile(), $exception->getLine());
    
    if (!headers_sent()) {
        http_response_code(500);
    }
    
    echo showErrorModal(
        "Something went wrong",
        "We've encountered an unexpected error. Our team has been notified and will fix this soon."
    );
    exit();
}

/**
 * Handle fatal errors
 */
function handleFatalError() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        logError('fatal', $error['message'], $error['file'], $error['line']);
        
        if (!headers_sent()) {
            http_response_code(500);
        }
        
        echo showErrorModal(
            "System Error",
            "A critical error occurred. Please refresh the page or contact support if the problem persists."
        );
    }
}

/**
 * Handle form validation errors
 */
function showValidationError($field, $message) {
    return "<div class='validation-error' data-field='$field'>
        <span class='error-icon'>❌</span>
        <span class='error-message'>$message</span>
    </div>";
}

/**
 * Success message display
 */
function showSuccessMessage($message) {
    return "<div class='success-message'>
        <span class='success-icon'>✅</span>
        <span class='success-text'>$message</span>
    </div>";
}

/**
 * Warning message display
 */
function showWarningMessage($message) {
    return "<div class='warning-message'>
        <span class='warning-icon'>⚠️</span>
        <span class='warning-text'>$message</span>
    </div>";
}

/**
 * Log user activity for debugging
 */
function logActivity($action, $details = null, $user_id = null) {
    if (!$user_id && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    
    $log_message = "ACTIVITY: User $user_id performed '$action'";
    if ($details) {
        $log_message .= " - $details";
    }
    
    logError('activity', $log_message, __FILE__, __LINE__, $user_id);
}

/**
 * Create logs directory if it doesn't exist
 */
function ensureLogsDirectory() {
    $logs_dir = __DIR__ . '/../logs';
    if (!file_exists($logs_dir)) {
        mkdir($logs_dir, 0755, true);
    }
}

// Set error handlers
set_exception_handler('handleException');
register_shutdown_function('handleFatalError');

// Ensure logs directory exists
ensureLogsDirectory();

// Set error reporting based on environment
if (defined('DEVELOPMENT') && DEVELOPMENT) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}
?>