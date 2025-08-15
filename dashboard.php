<?php
/**
 * Care4Mom - Dashboard
 * Main dashboard with quick access to all features
 * Author: Care4Mom Development Team
 * Version: 1.0
 */

$page_title = 'Dashboard';
$body_class = 'dashboard-page';

require_once 'includes/header.php';

// Ensure user is logged in
requireLogin();

$current_user = getCurrentUser();
if (!$current_user) {
    header('Location: public/login.php');
    exit();
}

// Get user's recent data for dashboard
try {
    // Recent symptoms (last 7 days)
    $stmt = $pdo->prepare("
        SELECT symptom_name, severity, logged_at 
        FROM symptoms 
        WHERE user_id = ? AND logged_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
        ORDER BY logged_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$current_user['id']]);
    $recent_symptoms = $stmt->fetchAll();
    
    // Medication compliance today
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_meds,
               SUM(CASE WHEN taken = 1 THEN 1 ELSE 0 END) as taken_meds
        FROM medications 
        WHERE user_id = ? AND DATE(taken_at) = CURDATE()
    ");
    $stmt->execute([$current_user['id']]);
    $med_compliance = $stmt->fetch();
    
    // Recent vitals
    $stmt = $pdo->prepare("
        SELECT heart_rate, blood_pressure_systolic, blood_pressure_diastolic, recorded_at
        FROM vitals 
        WHERE user_id = ? 
        ORDER BY recorded_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$current_user['id']]);
    $latest_vitals = $stmt->fetch();
    
    // Recent mood
    $stmt = $pdo->prepare("
        SELECT mood_score, energy_level, logged_at
        FROM mood_logs 
        WHERE user_id = ? 
        ORDER BY logged_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$current_user['id']]);
    $latest_mood = $stmt->fetch();
    
    // AI alerts count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as alert_count
        FROM ai_alerts 
        WHERE user_id = ? AND acknowledged = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute([$current_user['id']]);
    $alert_count = $stmt->fetch()['alert_count'];
    
} catch (Exception $e) {
    logError('dashboard', 'Failed to load dashboard data: ' . $e->getMessage(), __FILE__, __LINE__);
    $recent_symptoms = [];
    $med_compliance = ['total_meds' => 0, 'taken_meds' => 0];
    $latest_vitals = null;
    $latest_mood = null;
    $alert_count = 0;
}

// Calculate compliance percentage
$compliance_percentage = $med_compliance['total_meds'] > 0 
    ? round(($med_compliance['taken_meds'] / $med_compliance['total_meds']) * 100) 
    : 0;
?>

<div class="dashboard-container">
    <!-- Welcome Header -->
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1 class="welcome-title">
                Good <?php echo date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening'); ?>, 
                <?php echo htmlspecialchars($current_user['first_name']); ?>! 
                <span class="welcome-emoji">😊</span>
            </h1>
            <p class="welcome-subtitle">
                <?php echo date('l, F j, Y'); ?> • 
                <span class="user-role-badge"><?php echo ucfirst($current_user['role']); ?></span>
            </p>
        </div>
        
        <?php if ($alert_count > 0): ?>
        <div class="alert-banner">
            <span class="alert-icon">🚨</span>
            <span class="alert-text">You have <?php echo $alert_count; ?> new health alert<?php echo $alert_count > 1 ? 's' : ''; ?></span>
            <a href="modules/ai.php" class="alert-link">View Alerts</a>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2 class="section-title">Quick Actions</h2>
        <div class="action-grid">
            <button onclick="openSymptomModal()" class="action-card primary">
                <div class="action-icon">📊</div>
                <div class="action-content">
                    <h3 class="action-title">Log Symptom</h3>
                    <p class="action-description">Quick symptom entry</p>
                </div>
            </button>
            
            <button onclick="openMedicationModal()" class="action-card secondary">
                <div class="action-icon">💊</div>
                <div class="action-content">
                    <h3 class="action-title">Take Medication</h3>
                    <p class="action-description">Mark dose as taken</p>
                </div>
            </button>
            
            <button onclick="openVitalsModal()" class="action-card accent">
                <div class="action-icon">❤️</div>
                <div class="action-content">
                    <h3 class="action-title">Record Vitals</h3>
                    <p class="action-description">Blood pressure, heart rate</p>
                </div>
            </button>
            
            <button onclick="openMoodModal()" class="action-card success">
                <div class="action-icon">😊</div>
                <div class="action-content">
                    <h3 class="action-title">Log Mood</h3>
                    <p class="action-description">How are you feeling?</p>
                </div>
            </button>
        </div>
    </div>
    
    <!-- Dashboard Overview -->
    <div class="dashboard-overview">
        <h2 class="section-title">Today's Overview</h2>
        <div class="overview-grid">
            
            <!-- Medication Compliance -->
            <div class="overview-card">
                <div class="card-header">
                    <div class="card-icon">💊</div>
                    <h3 class="card-title">Medications Today</h3>
                </div>
                <div class="card-content">
                    <div class="compliance-display">
                        <div class="compliance-number"><?php echo $compliance_percentage; ?>%</div>
                        <div class="compliance-text">
                            <?php echo $med_compliance['taken_meds']; ?> of <?php echo $med_compliance['total_meds']; ?> taken
                        </div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $compliance_percentage; ?>%"></div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="modules/med.php" class="card-link">View All Medications →</a>
                </div>
            </div>
            
            <!-- Recent Symptoms -->
            <div class="overview-card">
                <div class="card-header">
                    <div class="card-icon">📊</div>
                    <h3 class="card-title">Recent Symptoms</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($recent_symptoms)): ?>
                        <p class="empty-state">No symptoms logged this week</p>
                    <?php else: ?>
                        <div class="symptom-list">
                            <?php foreach (array_slice($recent_symptoms, 0, 3) as $symptom): ?>
                                <div class="symptom-item">
                                    <span class="symptom-name"><?php echo htmlspecialchars($symptom['symptom_name']); ?></span>
                                    <span class="symptom-severity severity-<?php echo $symptom['severity']; ?>">
                                        <?php echo $symptom['severity']; ?>/10
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="modules/symptom.php" class="card-link">View All Symptoms →</a>
                </div>
            </div>
            
            <!-- Latest Vitals -->
            <div class="overview-card">
                <div class="card-header">
                    <div class="card-icon">❤️</div>
                    <h3 class="card-title">Latest Vitals</h3>
                </div>
                <div class="card-content">
                    <?php if (!$latest_vitals): ?>
                        <p class="empty-state">No vitals recorded yet</p>
                    <?php else: ?>
                        <div class="vitals-display">
                            <?php if ($latest_vitals['heart_rate']): ?>
                                <div class="vital-item">
                                    <span class="vital-label">Heart Rate</span>
                                    <span class="vital-value"><?php echo $latest_vitals['heart_rate']; ?> bpm</span>
                                </div>
                            <?php endif; ?>
                            <?php if ($latest_vitals['blood_pressure_systolic']): ?>
                                <div class="vital-item">
                                    <span class="vital-label">Blood Pressure</span>
                                    <span class="vital-value">
                                        <?php echo $latest_vitals['blood_pressure_systolic']; ?>/<?php echo $latest_vitals['blood_pressure_diastolic']; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="modules/vitals.php" class="card-link">View All Vitals →</a>
                </div>
            </div>
            
            <!-- Mood & Wellness -->
            <div class="overview-card">
                <div class="card-header">
                    <div class="card-icon">😊</div>
                    <h3 class="card-title">Mood & Wellness</h3>
                </div>
                <div class="card-content">
                    <?php if (!$latest_mood): ?>
                        <p class="empty-state">No mood logged yet</p>
                    <?php else: ?>
                        <div class="mood-display">
                            <div class="mood-score">
                                <span class="mood-emoji">
                                    <?php 
                                    $mood_emojis = ['😢', '😔', '😐', '😊', '😄'];
                                    $emoji_index = min(4, max(0, floor($latest_mood['mood_score'] / 2)));
                                    echo $mood_emojis[$emoji_index];
                                    ?>
                                </span>
                                <span class="mood-text">Mood: <?php echo $latest_mood['mood_score']; ?>/10</span>
                            </div>
                            <?php if ($latest_mood['energy_level']): ?>
                                <div class="energy-level">
                                    <span class="energy-text">Energy: <?php echo $latest_mood['energy_level']; ?>/10</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="modules/mood.php" class="card-link">View Mood History →</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Module Navigation -->
    <div class="module-navigation">
        <h2 class="section-title">Care Modules</h2>
        <div class="module-grid">
            <a href="modules/symptom.php" class="module-card">
                <div class="module-icon">📊</div>
                <h3 class="module-title">Symptom Tracking</h3>
                <p class="module-description">Log and monitor symptoms with AI insights</p>
            </a>
            
            <a href="modules/med.php" class="module-card">
                <div class="module-icon">💊</div>
                <h3 class="module-title">Medications</h3>
                <p class="module-description">Track medications and side effects</p>
            </a>
            
            <a href="modules/vitals.php" class="module-card">
                <div class="module-icon">❤️</div>
                <h3 class="module-title">Vitals & Fitness</h3>
                <p class="module-description">Monitor vitals and Fitbit integration</p>
            </a>
            
            <a href="modules/mood.php" class="module-card">
                <div class="module-icon">😊</div>
                <h3 class="module-title">Mood & Wellness</h3>
                <p class="module-description">Mental health and mindfulness support</p>
            </a>
            
            <a href="modules/ai.php" class="module-card">
                <div class="module-icon">🤖</div>
                <h3 class="module-title">AI Health Coach</h3>
                <p class="module-description">Smart insights and health alerts</p>
            </a>
            
            <a href="modules/report.php" class="module-card">
                <div class="module-icon">📋</div>
                <h3 class="module-title">Doctor Reports</h3>
                <p class="module-description">Generate and export health reports</p>
            </a>
            
            <?php if ($current_user['role'] === 'caregiver' || $current_user['role'] === 'doctor'): ?>
            <a href="modules/caregiver.php" class="module-card">
                <div class="module-icon">👥</div>
                <h3 class="module-title">Care Team</h3>
                <p class="module-description">Collaborate with family and caregivers</p>
            </a>
            <?php endif; ?>
            
            <a href="modules/wellness.php" class="module-card">
                <div class="module-icon">🌟</div>
                <h3 class="module-title">Wellness Resources</h3>
                <p class="module-description">Support groups and helpful resources</p>
            </a>
        </div>
    </div>
</div>

<!-- Quick Log Modals will be inserted here by JavaScript -->
<div id="quickLogModals"></div>

<style>
/* Dashboard Specific Styles */
.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: var(--spacing-lg);
}

.dashboard-header {
    margin-bottom: var(--spacing-xl);
    text-align: center;
}

.welcome-title {
    font-size: var(--font-size-3xl);
    font-weight: 700;
    color: var(--gray-800);
    margin-bottom: var(--spacing-sm);
}

.welcome-emoji {
    font-size: var(--font-size-2xl);
    margin-left: var(--spacing-sm);
}

.welcome-subtitle {
    font-size: var(--font-size-lg);
    color: var(--gray-600);
    margin-bottom: var(--spacing-md);
}

.user-role-badge {
    background: var(--gradient-primary);
    color: white;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-full);
    font-size: var(--font-size-sm);
    font-weight: 500;
}

.alert-banner {
    background: #fef2f2;
    border: 2px solid #fecaca;
    border-radius: var(--radius-lg);
    padding: var(--spacing-md);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    color: #dc2626;
    margin-top: var(--spacing-md);
}

.alert-link {
    color: #dc2626;
    font-weight: 600;
    text-decoration: none;
    margin-left: auto;
}

.section-title {
    font-size: var(--font-size-2xl);
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: var(--spacing-lg);
    text-align: center;
}

/* Quick Actions */
.quick-actions {
    margin-bottom: var(--spacing-2xl);
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-lg);
}

.action-card {
    background: var(--white);
    border: none;
    border-radius: var(--radius-xl);
    padding: var(--spacing-lg);
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    cursor: pointer;
    transition: all var(--transition-normal);
    box-shadow: var(--shadow-lg);
    text-align: left;
}

.action-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
}

.action-card.primary { border-left: 6px solid var(--primary-color); }
.action-card.secondary { border-left: 6px solid var(--secondary-color); }
.action-card.accent { border-left: 6px solid var(--accent-color); }
.action-card.success { border-left: 6px solid var(--success-color); }

.action-icon {
    font-size: var(--font-size-3xl);
    flex-shrink: 0;
}

.action-title {
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: var(--spacing-xs);
}

.action-description {
    color: var(--gray-600);
    font-size: var(--font-size-sm);
}

/* Overview Cards */
.overview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-2xl);
}

.overview-card {
    background: var(--white);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
    transition: all var(--transition-normal);
}

.overview-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-xl);
}

.card-header {
    padding: var(--spacing-lg);
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.card-icon {
    font-size: var(--font-size-2xl);
}

.card-title {
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--gray-800);
}

.card-content {
    padding: var(--spacing-lg);
    min-height: 120px;
}

.card-footer {
    padding: var(--spacing-md) var(--spacing-lg);
    background: var(--gray-50);
    border-top: 1px solid var(--gray-200);
}

.card-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    font-size: var(--font-size-sm);
}

.empty-state {
    color: var(--gray-500);
    text-align: center;
    font-style: italic;
}

/* Module Navigation */
.module-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--spacing-lg);
}

.module-card {
    background: var(--white);
    border-radius: var(--radius-xl);
    padding: var(--spacing-xl);
    text-decoration: none;
    color: inherit;
    box-shadow: var(--shadow-lg);
    transition: all var(--transition-normal);
    text-align: center;
    border: 1px solid var(--gray-200);
}

.module-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
    border-color: var(--primary-color);
}

.module-icon {
    font-size: 3rem;
    margin-bottom: var(--spacing-md);
}

.module-title {
    font-size: var(--font-size-xl);
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: var(--spacing-sm);
}

.module-description {
    color: var(--gray-600);
    line-height: 1.5;
}

/* Responsive Design */
@media (max-width: 768px) {
    .dashboard-container {
        padding: var(--spacing-md);
    }
    
    .action-grid {
        grid-template-columns: 1fr;
    }
    
    .overview-grid {
        grid-template-columns: 1fr;
    }
    
    .module-grid {
        grid-template-columns: 1fr;
    }
    
    .welcome-title {
        font-size: var(--font-size-2xl);
    }
}
</style>

<script>
// Quick action modal functions (to be implemented)
function openSymptomModal() {
    alert('Symptom logging modal will be implemented in the next phase');
}

function openMedicationModal() {
    alert('Medication modal will be implemented in the next phase');
}

function openVitalsModal() {
    alert('Vitals modal will be implemented in the next phase');
}

function openMoodModal() {
    alert('Mood modal will be implemented in the next phase');
}
</script>

<?php require_once 'includes/footer.php'; ?>