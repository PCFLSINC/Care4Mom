<?php
/**
 * Care4Mom - Symptom Tracking Module
 * Comprehensive symptom logging with AI insights
 * Author: Care4Mom Development Team
 * Version: 1.0
 */

$page_title = 'Symptom Tracking';
$body_class = 'module-page symptom-page';

// Enable debug mode temporarily
define('DEBUG_MODE', true);

require_once '../includes/header.php';
requireLogin();

$current_user = getCurrentUser();
$success_message = '';
$errors = [];

// Handle symptom logging
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token. Please try again.');
        }
        
        if ($_POST['action'] === 'log_symptom') {
            $symptom_name = sanitizeInput($_POST['symptom_name'] ?? '');
            $severity = intval($_POST['severity'] ?? 0);
            $notes = sanitizeInput($_POST['notes'] ?? '');
            $logged_at = $_POST['logged_at'] ?? date('Y-m-d H:i:s');
            
            // Validation
            if (empty($symptom_name)) $errors[] = 'Symptom name is required';
            if ($severity < 1 || $severity > 10) $errors[] = 'Severity must be between 1 and 10';
            
            if (empty($errors)) {
                $stmt = $pdo->prepare("
                    INSERT INTO symptoms (user_id, symptom_name, severity, notes, logged_at) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([$current_user['id'], $symptom_name, $severity, $notes, $logged_at])) {
                    $success_message = 'Symptom logged successfully!';
                    logActivity('symptom_logged', "Logged $symptom_name (severity: $severity)", $current_user['id']);
                    
                    // Check for AI alerts based on severity
                    if ($severity >= 8) {
                        $alert_stmt = $pdo->prepare("
                            INSERT INTO ai_alerts (user_id, alert_type, title, message, severity) 
                            VALUES (?, 'warning', 'High Severity Symptom Alert', ?, 'high')
                        ");
                        $alert_message = "You logged '$symptom_name' with high severity ($severity/10). Consider contacting your healthcare provider if this persists.";
                        $alert_stmt->execute([$current_user['id'], $alert_message]);
                    }
                } else {
                    $errors[] = 'Failed to log symptom. Please try again.';
                }
            }
        }
    } catch (Exception $e) {
        logError('symptom_module', $e->getMessage(), __FILE__, __LINE__);
        $errors[] = 'An error occurred. Please try again.';
    }
}

// Get user's symptoms with pagination
$page = intval($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    // Get total count
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM symptoms WHERE user_id = ?");
    $count_stmt->execute([$current_user['id']]);
    $total_symptoms = $count_stmt->fetchColumn();
    $total_pages = ceil($total_symptoms / $limit);
    
    // Get symptoms for this page
    $stmt = $pdo->prepare("
        SELECT * FROM symptoms 
        WHERE user_id = ? 
        ORDER BY logged_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$current_user['id'], $limit, $offset]);
    $symptoms = $stmt->fetchAll();
    
    // Get symptom statistics (SQLite and MySQL compatible)
    $stats_stmt = $pdo->prepare("
        SELECT 
            symptom_name,
            COUNT(*) as frequency,
            AVG(severity) as avg_severity,
            MAX(severity) as max_severity,
            MIN(severity) as min_severity
        FROM symptoms 
        WHERE user_id = ? AND logged_at >= datetime('now', '-30 days')
        GROUP BY symptom_name 
        ORDER BY frequency DESC 
        LIMIT 10
    ");
    $stats_stmt->execute([$current_user['id']]);
    $symptom_stats = $stats_stmt->fetchAll();
    
} catch (Exception $e) {
    logError('symptom_module', 'Failed to load symptoms: ' . $e->getMessage(), __FILE__, __LINE__);
    $symptoms = [];
    $symptom_stats = [];
    $total_symptoms = 0;
    $total_pages = 0;
}

// Common symptoms for quick selection
$common_symptoms = [
    'Dizziness', 'Hot hands/feet', 'Stomach pain', 'Fatigue', 'Nausea', 
    'Headache', 'Breathing issues', 'General pain', 'Joint pain', 'Back pain',
    'Shortness of breath', 'Chest pain', 'Loss of appetite', 'Insomnia', 'Anxiety'
];
?>

<div class="module-container">
    <!-- Module Header -->
    <div class="module-header">
        <div class="header-content">
            <h1 class="module-title">
                <span class="module-icon">📊</span>
                Symptom Tracking
            </h1>
            <p class="module-description">
                Log your symptoms to help track patterns and provide valuable information to your healthcare team.
            </p>
        </div>
        <div class="header-actions">
            <button onclick="openQuickLogModal()" class="btn btn-primary btn-large">
                <span class="btn-icon">⚡</span>
                <span class="btn-text">Quick Log</span>
            </button>
        </div>
    </div>
    
    <!-- Success/Error Messages -->
    <?php if ($success_message): ?>
        <div class="success-banner">
            <span class="success-icon">✅</span>
            <span class="success-text"><?php echo htmlspecialchars($success_message); ?></span>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="error-banner">
            <?php foreach ($errors as $error): ?>
                <div class="error-item">
                    <span class="error-icon">❌</span>
                    <span class="error-text"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistics Overview -->
    <?php if (!empty($symptom_stats)): ?>
    <div class="stats-section">
        <h2 class="section-title">Last 30 Days Overview</h2>
        <div class="stats-grid">
            <?php foreach (array_slice($symptom_stats, 0, 6) as $stat): ?>
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-name"><?php echo htmlspecialchars($stat['symptom_name']); ?></h3>
                        <span class="stat-frequency"><?php echo $stat['frequency']; ?>x</span>
                    </div>
                    <div class="stat-details">
                        <div class="stat-item">
                            <span class="stat-label">Avg Severity:</span>
                            <span class="stat-value severity-<?php echo round($stat['avg_severity']); ?>">
                                <?php echo number_format($stat['avg_severity'], 1); ?>/10
                            </span>
                        </div>
                        <div class="stat-range">
                            <span class="stat-min"><?php echo $stat['min_severity']; ?></span>
                            <div class="severity-bar">
                                <div class="severity-fill" style="width: <?php echo ($stat['avg_severity'] / 10) * 100; ?>%"></div>
                            </div>
                            <span class="stat-max"><?php echo $stat['max_severity']; ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Quick Log Form -->
    <div class="quick-log-section">
        <h2 class="section-title">Log New Symptom</h2>
        <form method="POST" class="symptom-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="log_symptom">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="symptom_name" class="form-label">Symptom</label>
                    <input type="text" id="symptom_name" name="symptom_name" class="form-input" 
                           placeholder="Enter symptom name" list="common-symptoms" required>
                    <datalist id="common-symptoms">
                        <?php foreach ($common_symptoms as $symptom): ?>
                            <option value="<?php echo htmlspecialchars($symptom); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                
                <div class="form-group">
                    <label for="logged_at" class="form-label">Date & Time</label>
                    <input type="datetime-local" id="logged_at" name="logged_at" class="form-input" 
                           value="<?php echo date('Y-m-d\TH:i'); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="severity" class="form-label">
                    Severity Level: <span id="severity-display">5</span>/10
                </label>
                <div class="severity-slider-container">
                    <input type="range" id="severity" name="severity" class="severity-slider" 
                           min="1" max="10" value="5" oninput="updateSeverityDisplay(this.value)">
                    <div class="severity-labels">
                        <span class="severity-label">1<br>Mild</span>
                        <span class="severity-label">5<br>Moderate</span>
                        <span class="severity-label">10<br>Severe</span>
                    </div>
                </div>
                <div class="severity-colors" id="severity-colors">
                    <span class="severity-dot severity-1"></span>
                    <span class="severity-dot severity-2"></span>
                    <span class="severity-dot severity-3"></span>
                    <span class="severity-dot severity-4"></span>
                    <span class="severity-dot severity-5 active"></span>
                    <span class="severity-dot severity-6"></span>
                    <span class="severity-dot severity-7"></span>
                    <span class="severity-dot severity-8"></span>
                    <span class="severity-dot severity-9"></span>
                    <span class="severity-dot severity-10"></span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="notes" class="form-label">Notes (Optional)</label>
                <textarea id="notes" name="notes" class="form-textarea" rows="3" 
                          placeholder="Additional details about this symptom..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary btn-large">
                <span class="btn-icon">📝</span>
                <span class="btn-text">Log Symptom</span>
            </button>
        </form>
    </div>
    
    <!-- Common Symptoms Quick Access -->
    <div class="quick-symptoms">
        <h3 class="subsection-title">Quick Log Common Symptoms</h3>
        <div class="symptom-buttons">
            <?php foreach (array_slice($common_symptoms, 0, 8) as $symptom): ?>
                <button onclick="quickLogSymptom('<?php echo htmlspecialchars($symptom); ?>')" 
                        class="symptom-btn">
                    <?php echo htmlspecialchars($symptom); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Symptom History -->
    <div class="history-section">
        <h2 class="section-title">Symptom History</h2>
        
        <!-- Debug info -->
        <?php if (defined('DEBUG_MODE')): ?>
            <p style="background: yellow; padding: 10px;">
                Debug: Found <?php echo count($symptoms); ?> symptoms, 
                Total: <?php echo $total_symptoms ?? 'undefined'; ?>, 
                User ID: <?php echo $current_user['id'] ?? 'undefined'; ?>
            </p>
        <?php endif; ?>
        
        <?php if (empty($symptoms)): ?>
            <div class="empty-state">
                <div class="empty-icon">📊</div>
                <h3 class="empty-title">No symptoms logged yet</h3>
                <p class="empty-description">Start logging your symptoms to track patterns and share with your healthcare team.</p>
            </div>
        <?php else: ?>
            <div class="symptom-list">
                <?php foreach ($symptoms as $symptom): ?>
                    <div class="symptom-entry">
                        <div class="symptom-header">
                            <h3 class="symptom-name"><?php echo htmlspecialchars($symptom['symptom_name']); ?></h3>
                            <div class="symptom-meta">
                                <span class="symptom-severity severity-<?php echo $symptom['severity']; ?>">
                                    <?php echo $symptom['severity']; ?>/10
                                </span>
                                <span class="symptom-date">
                                    <?php echo date('M j, Y g:i A', strtotime($symptom['logged_at'])); ?>
                                </span>
                            </div>
                        </div>
                        <?php if (!empty($symptom['notes'])): ?>
                            <div class="symptom-notes">
                                <p><?php echo nl2br(htmlspecialchars($symptom['notes'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="pagination-btn">← Previous</a>
                    <?php endif; ?>
                    
                    <span class="pagination-info">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?> 
                        (<?php echo $total_symptoms; ?> total symptoms)
                    </span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="pagination-btn">Next →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Log Modal -->
<div id="quickLogModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>⚡ Quick Symptom Log</h2>
            <button onclick="closeQuickLogModal()" class="close-btn">✕</button>
        </div>
        <div class="modal-body">
            <div class="quick-log-grid">
                <?php foreach ($common_symptoms as $symptom): ?>
                    <button onclick="quickLogSymptom('<?php echo htmlspecialchars($symptom); ?>')" 
                            class="quick-symptom-btn">
                        <?php echo htmlspecialchars($symptom); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <p class="quick-log-note">Select a symptom for quick logging with default severity (5/10). You can edit details after logging.</p>
        </div>
    </div>
</div>

<style>
/* Symptom Module Specific Styles */
.module-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: var(--spacing-lg);
}

.module-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-xl);
    padding-bottom: var(--spacing-lg);
    border-bottom: 2px solid var(--gray-200);
}

.module-title {
    font-size: var(--font-size-3xl);
    font-weight: 700;
    color: var(--gray-800);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-xs);
}

.module-icon {
    font-size: var(--font-size-2xl);
}

.module-description {
    color: var(--gray-600);
    font-size: var(--font-size-lg);
    max-width: 600px;
}

/* Severity Styling */
.severity-1, .severity-2 { color: #10b981; }
.severity-3, .severity-4 { color: #f59e0b; }
.severity-5, .severity-6 { color: #f97316; }
.severity-7, .severity-8 { color: #ef4444; }
.severity-9, .severity-10 { color: #dc2626; font-weight: bold; }

.severity-slider-container {
    position: relative;
    margin: var(--spacing-md) 0;
}

.severity-slider {
    width: 100%;
    height: 8px;
    border-radius: var(--radius-full);
    background: linear-gradient(to right, #10b981, #f59e0b, #ef4444, #dc2626);
    outline: none;
    -webkit-appearance: none;
}

.severity-slider::-webkit-slider-thumb {
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: white;
    border: 2px solid var(--primary-color);
    cursor: pointer;
    box-shadow: var(--shadow-md);
}

.severity-labels {
    display: flex;
    justify-content: space-between;
    margin-top: var(--spacing-sm);
    font-size: var(--font-size-xs);
    color: var(--gray-500);
}

.severity-colors {
    display: flex;
    justify-content: space-between;
    margin-top: var(--spacing-sm);
    padding: 0 10px;
}

.severity-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    opacity: 0.3;
    transition: opacity var(--transition-fast);
}

.severity-dot.active {
    opacity: 1;
    transform: scale(1.2);
    box-shadow: var(--shadow-md);
}

.severity-1.severity-dot { background: #10b981; }
.severity-2.severity-dot { background: #34d399; }
.severity-3.severity-dot { background: #fbbf24; }
.severity-4.severity-dot { background: #f59e0b; }
.severity-5.severity-dot { background: #f97316; }
.severity-6.severity-dot { background: #ea580c; }
.severity-7.severity-dot { background: #ef4444; }
.severity-8.severity-dot { background: #dc2626; }
.severity-9.severity-dot { background: #b91c1c; }
.severity-10.severity-dot { background: #991b1b; }

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

.stat-card {
    background: white;
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--gray-200);
}

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-md);
}

.stat-name {
    font-weight: 600;
    color: var(--gray-800);
}

.stat-frequency {
    background: var(--primary-color);
    color: white;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-full);
    font-size: var(--font-size-sm);
    font-weight: 500;
}

/* Quick Symptoms */
.symptom-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-xl);
}

.symptom-btn {
    padding: var(--spacing-sm) var(--spacing-md);
    border: 2px solid var(--gray-300);
    border-radius: var(--radius-lg);
    background: white;
    color: var(--gray-700);
    cursor: pointer;
    transition: all var(--transition-fast);
    font-weight: 500;
}

.symptom-btn:hover {
    border-color: var(--primary-color);
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
}

/* Responsive Design */
@media (max-width: 768px) {
    .module-header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-md);
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .symptom-buttons {
        grid-template-columns: 1fr 1fr;
    }
}
</style>

<script>
function updateSeverityDisplay(value) {
    document.getElementById('severity-display').textContent = value;
    
    // Update severity dots
    const dots = document.querySelectorAll('.severity-dot');
    dots.forEach((dot, index) => {
        if (index + 1 <= value) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });
}

function openQuickLogModal() {
    document.getElementById('quickLogModal').style.display = 'flex';
}

function closeQuickLogModal() {
    document.getElementById('quickLogModal').style.display = 'none';
}

function quickLogSymptom(symptomName) {
    // Fill the form with the selected symptom
    document.getElementById('symptom_name').value = symptomName;
    
    // Close modal if open
    closeQuickLogModal();
    
    // Scroll to form
    document.querySelector('.symptom-form').scrollIntoView({ behavior: 'smooth' });
    
    // Focus on severity slider
    document.getElementById('severity').focus();
}

// Initialize severity display
document.addEventListener('DOMContentLoaded', function() {
    updateSeverityDisplay(5);
});
</script>

<?php require_once '../includes/footer.php'; ?>