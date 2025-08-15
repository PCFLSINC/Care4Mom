<?php
/**
 * Care4Mom - Vitals and Fitness Module
 * Track vitals, heart rate, blood pressure with Fitbit integration placeholder
 * Author: Care4Mom Development Team
 * Version: 1.0
 */

$page_title = 'Vitals & Fitness';
$body_class = 'module-page vitals-page';

require_once '../includes/header.php';
requireLogin();

$current_user = getCurrentUser();
$success_message = '';
$errors = [];

// Handle vitals logging
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token. Please try again.');
        }
        
        if ($_POST['action'] === 'log_vitals') {
            $heart_rate = !empty($_POST['heart_rate']) ? intval($_POST['heart_rate']) : null;
            $bp_systolic = !empty($_POST['bp_systolic']) ? intval($_POST['bp_systolic']) : null;
            $bp_diastolic = !empty($_POST['bp_diastolic']) ? intval($_POST['bp_diastolic']) : null;
            $temperature = !empty($_POST['temperature']) ? floatval($_POST['temperature']) : null;
            $oxygen_saturation = !empty($_POST['oxygen_saturation']) ? intval($_POST['oxygen_saturation']) : null;
            $steps = !empty($_POST['steps']) ? intval($_POST['steps']) : null;
            $sleep_hours = !empty($_POST['sleep_hours']) ? floatval($_POST['sleep_hours']) : null;
            $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
            $recorded_at = $_POST['recorded_at'] ?? date('Y-m-d H:i:s');
            
            // Validation
            if ($heart_rate && ($heart_rate < 30 || $heart_rate > 220)) {
                $errors[] = 'Heart rate must be between 30 and 220 bpm';
            }
            if ($bp_systolic && ($bp_systolic < 70 || $bp_systolic > 250)) {
                $errors[] = 'Systolic blood pressure must be between 70 and 250';
            }
            if ($bp_diastolic && ($bp_diastolic < 40 || $bp_diastolic > 150)) {
                $errors[] = 'Diastolic blood pressure must be between 40 and 150';
            }
            if ($temperature && ($temperature < 95 || $temperature > 110)) {
                $errors[] = 'Temperature must be between 95°F and 110°F';
            }
            if ($oxygen_saturation && ($oxygen_saturation < 70 || $oxygen_saturation > 100)) {
                $errors[] = 'Oxygen saturation must be between 70% and 100%';
            }
            
            if (empty($errors)) {
                $stmt = $pdo->prepare("
                    INSERT INTO vitals 
                    (user_id, heart_rate, blood_pressure_systolic, blood_pressure_diastolic, 
                     temperature, oxygen_saturation, steps, sleep_hours, weight, recorded_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([
                    $current_user['id'], $heart_rate, $bp_systolic, $bp_diastolic, 
                    $temperature, $oxygen_saturation, $steps, $sleep_hours, $weight, $recorded_at
                ])) {
                    $success_message = 'Vitals recorded successfully!';
                    logActivity('vitals_logged', 'Recorded vitals', $current_user['id']);
                    
                    // Check for concerning vitals and create alerts
                    $alerts = [];
                    if ($heart_rate && ($heart_rate > 100 || $heart_rate < 60)) {
                        $alerts[] = "Heart rate of {$heart_rate} bpm is outside normal range (60-100 bpm)";
                    }
                    if ($bp_systolic && $bp_diastolic && ($bp_systolic > 140 || $bp_diastolic > 90)) {
                        $alerts[] = "Blood pressure {$bp_systolic}/{$bp_diastolic} indicates high blood pressure";
                    }
                    if ($oxygen_saturation && $oxygen_saturation < 95) {
                        $alerts[] = "Oxygen saturation of {$oxygen_saturation}% is below normal (95-100%)";
                    }
                    if ($temperature && $temperature > 100.4) {
                        $alerts[] = "Temperature of {$temperature}°F indicates fever";
                    }
                    
                    // Insert alerts if any
                    foreach ($alerts as $alert_message) {
                        $alert_stmt = $pdo->prepare("
                            INSERT INTO ai_alerts (user_id, alert_type, title, message, severity) 
                            VALUES (?, 'warning', 'Vital Signs Alert', ?, 'medium')
                        ");
                        $alert_stmt->execute([$current_user['id'], $alert_message]);
                    }
                    
                } else {
                    $errors[] = 'Failed to record vitals. Please try again.';
                }
            }
        }
    } catch (Exception $e) {
        logError('vitals_module', $e->getMessage(), __FILE__, __LINE__);
        $errors[] = 'An error occurred. Please try again.';
    }
}

try {
    // Get latest vitals
    $latest_stmt = $pdo->prepare("
        SELECT * FROM vitals 
        WHERE user_id = ? 
        ORDER BY recorded_at DESC 
        LIMIT 1
    ");
    $latest_stmt->execute([$current_user['id']]);
    $latest_vitals = $latest_stmt->fetch();
    
    // Get recent vitals history
    $history_stmt = $pdo->prepare("
        SELECT * FROM vitals 
        WHERE user_id = ? 
        ORDER BY recorded_at DESC 
        LIMIT 10
    ");
    $history_stmt->execute([$current_user['id']]);
    $vitals_history = $history_stmt->fetchAll();
    
    // Get vitals trends (last 30 days)
    $trends_stmt = $pdo->prepare("
        SELECT 
            DATE(recorded_at) as date,
            AVG(heart_rate) as avg_heart_rate,
            AVG(blood_pressure_systolic) as avg_bp_systolic,
            AVG(blood_pressure_diastolic) as avg_bp_diastolic,
            AVG(weight) as avg_weight,
            SUM(steps) as total_steps,
            AVG(sleep_hours) as avg_sleep
        FROM vitals 
        WHERE user_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(recorded_at) 
        ORDER BY date DESC
    ");
    $trends_stmt->execute([$current_user['id']]);
    $vitals_trends = $trends_stmt->fetchAll();
    
} catch (Exception $e) {
    logError('vitals_module', 'Failed to load vitals data: ' . $e->getMessage(), __FILE__, __LINE__);
    $latest_vitals = null;
    $vitals_history = [];
    $vitals_trends = [];
}
?>

<div class="module-container">
    <!-- Module Header -->
    <div class="module-header">
        <div class="header-content">
            <h1 class="module-title">
                <span class="module-icon">❤️</span>
                Vitals & Fitness
            </h1>
            <p class="module-description">
                Monitor your vital signs, track fitness data, and sync with wearable devices for comprehensive health tracking.
            </p>
        </div>
        <div class="header-actions">
            <button onclick="openQuickVitalsModal()" class="btn btn-primary btn-large">
                <span class="btn-icon">📊</span>
                <span class="btn-text">Record Vitals</span>
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
    
    <!-- Fitbit Integration Status -->
    <div class="fitbit-section">
        <div class="integration-card">
            <div class="integration-header">
                <div class="integration-icon">⌚</div>
                <h2 class="integration-title">Fitbit Integration</h2>
                <span class="integration-status disconnected">Not Connected</span>
            </div>
            <div class="integration-content">
                <p class="integration-description">
                    Connect your Fitbit device to automatically sync heart rate, steps, sleep, and activity data.
                </p>
                <div class="integration-features">
                    <span class="feature">📈 Automatic data sync</span>
                    <span class="feature">❤️ Continuous heart rate</span>
                    <span class="feature">👟 Step tracking</span>
                    <span class="feature">😴 Sleep monitoring</span>
                </div>
                <button onclick="showFitbitModal()" class="btn btn-secondary">
                    <span class="btn-icon">🔗</span>
                    <span class="btn-text">Connect Fitbit (Demo)</span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Current Vitals Dashboard -->
    <?php if ($latest_vitals): ?>
    <div class="current-vitals">
        <h2 class="section-title">Latest Readings</h2>
        <div class="vitals-dashboard">
            <?php if ($latest_vitals['heart_rate']): ?>
            <div class="vital-card heart-rate">
                <div class="vital-icon">❤️</div>
                <div class="vital-content">
                    <h3 class="vital-label">Heart Rate</h3>
                    <div class="vital-value"><?php echo $latest_vitals['heart_rate']; ?> <span class="vital-unit">bpm</span></div>
                    <div class="vital-status <?php echo ($latest_vitals['heart_rate'] >= 60 && $latest_vitals['heart_rate'] <= 100) ? 'normal' : 'attention'; ?>">
                        <?php echo ($latest_vitals['heart_rate'] >= 60 && $latest_vitals['heart_rate'] <= 100) ? 'Normal' : 'Needs Attention'; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($latest_vitals['blood_pressure_systolic'] && $latest_vitals['blood_pressure_diastolic']): ?>
            <div class="vital-card blood-pressure">
                <div class="vital-icon">🩺</div>
                <div class="vital-content">
                    <h3 class="vital-label">Blood Pressure</h3>
                    <div class="vital-value">
                        <?php echo $latest_vitals['blood_pressure_systolic']; ?>/<?php echo $latest_vitals['blood_pressure_diastolic']; ?>
                        <span class="vital-unit">mmHg</span>
                    </div>
                    <div class="vital-status <?php echo ($latest_vitals['blood_pressure_systolic'] < 140 && $latest_vitals['blood_pressure_diastolic'] < 90) ? 'normal' : 'attention'; ?>">
                        <?php echo ($latest_vitals['blood_pressure_systolic'] < 140 && $latest_vitals['blood_pressure_diastolic'] < 90) ? 'Normal' : 'Elevated'; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($latest_vitals['temperature']): ?>
            <div class="vital-card temperature">
                <div class="vital-icon">🌡️</div>
                <div class="vital-content">
                    <h3 class="vital-label">Temperature</h3>
                    <div class="vital-value"><?php echo number_format($latest_vitals['temperature'], 1); ?> <span class="vital-unit">°F</span></div>
                    <div class="vital-status <?php echo ($latest_vitals['temperature'] < 100.4) ? 'normal' : 'attention'; ?>">
                        <?php echo ($latest_vitals['temperature'] < 100.4) ? 'Normal' : 'Fever'; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($latest_vitals['oxygen_saturation']): ?>
            <div class="vital-card oxygen">
                <div class="vital-icon">🫁</div>
                <div class="vital-content">
                    <h3 class="vital-label">Oxygen Saturation</h3>
                    <div class="vital-value"><?php echo $latest_vitals['oxygen_saturation']; ?> <span class="vital-unit">%</span></div>
                    <div class="vital-status <?php echo ($latest_vitals['oxygen_saturation'] >= 95) ? 'normal' : 'attention'; ?>">
                        <?php echo ($latest_vitals['oxygen_saturation'] >= 95) ? 'Normal' : 'Low'; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($latest_vitals['steps']): ?>
            <div class="vital-card steps">
                <div class="vital-icon">👟</div>
                <div class="vital-content">
                    <h3 class="vital-label">Steps Today</h3>
                    <div class="vital-value"><?php echo number_format($latest_vitals['steps']); ?> <span class="vital-unit">steps</span></div>
                    <div class="vital-status normal">Recorded</div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($latest_vitals['weight']): ?>
            <div class="vital-card weight">
                <div class="vital-icon">⚖️</div>
                <div class="vital-content">
                    <h3 class="vital-label">Weight</h3>
                    <div class="vital-value"><?php echo number_format($latest_vitals['weight'], 1); ?> <span class="vital-unit">lbs</span></div>
                    <div class="vital-status normal">Recorded</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="last-updated">
            Last updated: <?php echo date('M j, Y g:i A', strtotime($latest_vitals['recorded_at'])); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Manual Entry Form -->
    <div class="vitals-entry-section">
        <h2 class="section-title">Record Vitals Manually</h2>
        <form method="POST" class="vitals-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="log_vitals">
            
            <div class="vitals-grid">
                <div class="form-group">
                    <label for="heart_rate" class="form-label">
                        <span class="label-icon">❤️</span>
                        Heart Rate (bpm)
                    </label>
                    <input type="number" id="heart_rate" name="heart_rate" class="form-input" 
                           min="30" max="220" placeholder="e.g., 72">
                    <small class="form-hint">Normal range: 60-100 bpm</small>
                </div>
                
                <div class="form-group bp-group">
                    <label class="form-label">
                        <span class="label-icon">🩺</span>
                        Blood Pressure (mmHg)
                    </label>
                    <div class="bp-inputs">
                        <input type="number" id="bp_systolic" name="bp_systolic" class="form-input" 
                               min="70" max="250" placeholder="Systolic">
                        <span class="bp-separator">/</span>
                        <input type="number" id="bp_diastolic" name="bp_diastolic" class="form-input" 
                               min="40" max="150" placeholder="Diastolic">
                    </div>
                    <small class="form-hint">Normal range: Less than 120/80</small>
                </div>
                
                <div class="form-group">
                    <label for="temperature" class="form-label">
                        <span class="label-icon">🌡️</span>
                        Temperature (°F)
                    </label>
                    <input type="number" id="temperature" name="temperature" class="form-input" 
                           min="95" max="110" step="0.1" placeholder="e.g., 98.6">
                    <small class="form-hint">Normal range: 97.0-99.0°F</small>
                </div>
                
                <div class="form-group">
                    <label for="oxygen_saturation" class="form-label">
                        <span class="label-icon">🫁</span>
                        Oxygen Saturation (%)
                    </label>
                    <input type="number" id="oxygen_saturation" name="oxygen_saturation" class="form-input" 
                           min="70" max="100" placeholder="e.g., 98">
                    <small class="form-hint">Normal range: 95-100%</small>
                </div>
                
                <div class="form-group">
                    <label for="steps" class="form-label">
                        <span class="label-icon">👟</span>
                        Steps Today
                    </label>
                    <input type="number" id="steps" name="steps" class="form-input" 
                           min="0" placeholder="e.g., 5000">
                    <small class="form-hint">Daily step count</small>
                </div>
                
                <div class="form-group">
                    <label for="sleep_hours" class="form-label">
                        <span class="label-icon">😴</span>
                        Sleep Hours
                    </label>
                    <input type="number" id="sleep_hours" name="sleep_hours" class="form-input" 
                           min="0" max="24" step="0.5" placeholder="e.g., 7.5">
                    <small class="form-hint">Hours of sleep last night</small>
                </div>
                
                <div class="form-group">
                    <label for="weight" class="form-label">
                        <span class="label-icon">⚖️</span>
                        Weight (lbs)
                    </label>
                    <input type="number" id="weight" name="weight" class="form-input" 
                           min="50" max="500" step="0.1" placeholder="e.g., 150.5">
                    <small class="form-hint">Current weight</small>
                </div>
                
                <div class="form-group">
                    <label for="recorded_at" class="form-label">
                        <span class="label-icon">📅</span>
                        Date & Time
                    </label>
                    <input type="datetime-local" id="recorded_at" name="recorded_at" class="form-input" 
                           value="<?php echo date('Y-m-d\TH:i'); ?>">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-large">
                <span class="btn-icon">📊</span>
                <span class="btn-text">Record Vitals</span>
            </button>
        </form>
    </div>
    
    <!-- Recent History -->
    <div class="history-section">
        <h2 class="section-title">Recent Vitals History</h2>
        
        <?php if (empty($vitals_history)): ?>
            <div class="empty-state">
                <div class="empty-icon">❤️</div>
                <h3 class="empty-title">No vitals recorded yet</h3>
                <p class="empty-description">Start tracking your vital signs to monitor your health trends.</p>
            </div>
        <?php else: ?>
            <div class="vitals-history">
                <?php foreach ($vitals_history as $vital): ?>
                    <div class="history-entry">
                        <div class="history-date">
                            <?php echo date('M j, Y g:i A', strtotime($vital['recorded_at'])); ?>
                        </div>
                        <div class="history-vitals">
                            <?php if ($vital['heart_rate']): ?>
                                <span class="history-vital">❤️ <?php echo $vital['heart_rate']; ?> bpm</span>
                            <?php endif; ?>
                            <?php if ($vital['blood_pressure_systolic']): ?>
                                <span class="history-vital">🩺 <?php echo $vital['blood_pressure_systolic']; ?>/<?php echo $vital['blood_pressure_diastolic']; ?></span>
                            <?php endif; ?>
                            <?php if ($vital['temperature']): ?>
                                <span class="history-vital">🌡️ <?php echo $vital['temperature']; ?>°F</span>
                            <?php endif; ?>
                            <?php if ($vital['oxygen_saturation']): ?>
                                <span class="history-vital">🫁 <?php echo $vital['oxygen_saturation']; ?>%</span>
                            <?php endif; ?>
                            <?php if ($vital['steps']): ?>
                                <span class="history-vital">👟 <?php echo number_format($vital['steps']); ?> steps</span>
                            <?php endif; ?>
                            <?php if ($vital['weight']): ?>
                                <span class="history-vital">⚖️ <?php echo $vital['weight']; ?> lbs</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Fitbit Demo Modal -->
<div id="fitbitModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>⌚ Fitbit Integration (Demo)</h2>
            <button onclick="closeFitbitModal()" class="close-btn">✕</button>
        </div>
        <div class="modal-body">
            <div class="demo-notice">
                <h3>🧪 Demo Mode</h3>
                <p>This is a demonstration of Fitbit integration. In a production environment, this would:</p>
                <ul>
                    <li>Connect to your actual Fitbit account via OAuth</li>
                    <li>Automatically sync heart rate, steps, and sleep data</li>
                    <li>Provide real-time health monitoring</li>
                    <li>Send alerts for unusual patterns</li>
                </ul>
            </div>
            <div class="demo-data">
                <h4>Sample Fitbit Data</h4>
                <div class="sample-vitals">
                    <div class="sample-vital">❤️ Heart Rate: 72 bpm (avg today)</div>
                    <div class="sample-vital">👟 Steps: 4,247 steps today</div>
                    <div class="sample-vital">😴 Sleep: 7.5 hours last night</div>
                    <div class="sample-vital">🔥 Calories: 1,842 burned today</div>
                </div>
                <button onclick="simulateFitbitSync()" class="btn btn-primary">
                    Simulate Data Sync
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Vitals Module Styles */
.vitals-dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-lg);
}

.vital-card {
    background: white;
    border-radius: var(--radius-xl);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-lg);
    text-align: center;
    border: 1px solid var(--gray-200);
    transition: all var(--transition-normal);
}

.vital-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-xl);
}

.vital-icon {
    font-size: 2.5rem;
    margin-bottom: var(--spacing-sm);
}

.vital-label {
    font-size: var(--font-size-sm);
    color: var(--gray-600);
    font-weight: 500;
    margin-bottom: var(--spacing-xs);
}

.vital-value {
    font-size: var(--font-size-2xl);
    font-weight: 700;
    color: var(--gray-800);
    margin-bottom: var(--spacing-xs);
}

.vital-unit {
    font-size: var(--font-size-sm);
    color: var(--gray-500);
    font-weight: 400;
}

.vital-status {
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-full);
    font-size: var(--font-size-xs);
    font-weight: 600;
    text-transform: uppercase;
}

.vital-status.normal {
    background: #d1fae5;
    color: #065f46;
}

.vital-status.attention {
    background: #fed7d7;
    color: #9b2c2c;
}

/* Integration Card */
.integration-card {
    background: white;
    border-radius: var(--radius-xl);
    padding: var(--spacing-xl);
    box-shadow: var(--shadow-lg);
    margin-bottom: var(--spacing-xl);
    border: 1px solid var(--gray-200);
}

.integration-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-md);
}

.integration-icon {
    font-size: 2rem;
}

.integration-status.disconnected {
    background: #fed7d7;
    color: #9b2c2c;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-full);
    font-size: var(--font-size-xs);
    font-weight: 600;
    margin-left: auto;
}

.integration-features {
    display: flex;
    gap: var(--spacing-md);
    margin: var(--spacing-md) 0;
    flex-wrap: wrap;
}

.feature {
    background: var(--gray-100);
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-md);
    font-size: var(--font-size-sm);
    color: var(--gray-700);
}

/* Form Styles */
.vitals-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

.bp-group .bp-inputs {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.bp-separator {
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--gray-500);
}

.label-icon {
    margin-right: var(--spacing-xs);
}

/* History Styles */
.history-entry {
    background: white;
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-md);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
}

.history-date {
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: var(--spacing-sm);
}

.history-vitals {
    display: flex;
    gap: var(--spacing-md);
    flex-wrap: wrap;
}

.history-vital {
    background: var(--gray-100);
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-md);
    font-size: var(--font-size-sm);
    color: var(--gray-700);
}

/* Demo Styles */
.demo-notice {
    background: #e0f2fe;
    border: 2px solid #0284c7;
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-lg);
}

.sample-vitals {
    background: var(--gray-50);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    margin: var(--spacing-md) 0;
}

.sample-vital {
    padding: var(--spacing-sm) 0;
    border-bottom: 1px solid var(--gray-200);
    font-weight: 500;
}

.sample-vital:last-child {
    border-bottom: none;
}

/* Responsive Design */
@media (max-width: 768px) {
    .vitals-dashboard {
        grid-template-columns: 1fr 1fr;
    }
    
    .vitals-grid {
        grid-template-columns: 1fr;
    }
    
    .integration-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .integration-features {
        flex-direction: column;
    }
    
    .history-vitals {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .vitals-dashboard {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function openQuickVitalsModal() {
    document.querySelector('.vitals-entry-section').scrollIntoView({ behavior: 'smooth' });
    document.getElementById('heart_rate').focus();
}

function showFitbitModal() {
    document.getElementById('fitbitModal').style.display = 'flex';
}

function closeFitbitModal() {
    document.getElementById('fitbitModal').style.display = 'none';
}

function simulateFitbitSync() {
    // Simulate data sync
    document.getElementById('heart_rate').value = '72';
    document.getElementById('steps').value = '4247';
    document.getElementById('sleep_hours').value = '7.5';
    
    closeFitbitModal();
    
    // Show success message
    alert('Demo data synced! Heart rate, steps, and sleep data have been populated.');
    
    // Scroll to form
    document.querySelector('.vitals-form').scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php require_once '../includes/footer.php'; ?>