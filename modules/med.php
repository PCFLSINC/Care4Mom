<?php
/**
 * Care4Mom - Medication Management Module
 * Track medications, dosages, and compliance
 * Author: Care4Mom Development Team
 * Version: 1.0
 */

$page_title = 'Medication Management';
$body_class = 'module-page medication-page';

require_once '../includes/header.php';
requireLogin();

$current_user = getCurrentUser();
$success_message = '';
$errors = [];

// Handle medication actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token. Please try again.');
        }
        
        if ($_POST['action'] === 'take_medication') {
            $medication_name = sanitizeInput($_POST['medication_name'] ?? '');
            $dosage = sanitizeInput($_POST['dosage'] ?? '');
            $notes = sanitizeInput($_POST['notes'] ?? '');
            $side_effects = sanitizeInput($_POST['side_effects'] ?? '');
            
            if (empty($medication_name)) $errors[] = 'Medication name is required';
            
            if (empty($errors)) {
                $stmt = $pdo->prepare("
                    INSERT INTO medications (user_id, medication_name, dosage, taken, notes, side_effects) 
                    VALUES (?, ?, ?, 1, ?, ?)
                ");
                
                if ($stmt->execute([$current_user['id'], $medication_name, $dosage, $notes, $side_effects])) {
                    $success_message = 'Medication marked as taken!';
                    logActivity('medication_taken', "Took $medication_name", $current_user['id']);
                } else {
                    $errors[] = 'Failed to record medication. Please try again.';
                }
            }
        }
        
        elseif ($_POST['action'] === 'add_schedule') {
            $medication_name = sanitizeInput($_POST['medication_name'] ?? '');
            $dosage = sanitizeInput($_POST['dosage'] ?? '');
            $frequency = intval($_POST['frequency'] ?? 1);
            $times = [];
            
            for ($i = 1; $i <= 4; $i++) {
                if (!empty($_POST["time_$i"])) {
                    $times["time_$i"] = $_POST["time_$i"];
                }
            }
            
            if (empty($medication_name)) $errors[] = 'Medication name is required';
            if ($frequency < 1 || $frequency > 4) $errors[] = 'Frequency must be between 1 and 4 times per day';
            if (count($times) !== $frequency) $errors[] = 'Please specify all dosage times';
            
            if (empty($errors)) {
                $stmt = $pdo->prepare("
                    INSERT INTO medication_schedule 
                    (user_id, medication_name, dosage, frequency_times_per_day, time_1, time_2, time_3, time_4, start_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
                ");
                
                $params = [
                    $current_user['id'], $medication_name, $dosage, $frequency,
                    $times['time_1'] ?? null,
                    $times['time_2'] ?? null,
                    $times['time_3'] ?? null,
                    $times['time_4'] ?? null
                ];
                
                if ($stmt->execute($params)) {
                    $success_message = 'Medication schedule added successfully!';
                    logActivity('medication_scheduled', "Added schedule for $medication_name", $current_user['id']);
                } else {
                    $errors[] = 'Failed to add medication schedule. Please try again.';
                }
            }
        }
    } catch (Exception $e) {
        logError('medication_module', $e->getMessage(), __FILE__, __LINE__);
        $errors[] = 'An error occurred. Please try again.';
    }
}

try {
    // Get today's medication compliance
    $today_stmt = $pdo->prepare("
        SELECT 
            medication_name,
            dosage,
            COUNT(*) as total_doses,
            SUM(CASE WHEN taken = 1 THEN 1 ELSE 0 END) as taken_doses,
            MAX(taken_at) as last_taken
        FROM medications 
        WHERE user_id = ? AND DATE(taken_at) = CURDATE()
        GROUP BY medication_name, dosage
        ORDER BY medication_name
    ");
    $today_stmt->execute([$current_user['id']]);
    $today_meds = $today_stmt->fetchAll();
    
    // Get medication schedule
    $schedule_stmt = $pdo->prepare("
        SELECT * FROM medication_schedule 
        WHERE user_id = ? AND active = 1 
        ORDER BY medication_name, time_1
    ");
    $schedule_stmt->execute([$current_user['id']]);
    $med_schedule = $schedule_stmt->fetchAll();
    
    // Get recent medication history
    $history_stmt = $pdo->prepare("
        SELECT * FROM medications 
        WHERE user_id = ? 
        ORDER BY taken_at DESC 
        LIMIT 20
    ");
    $history_stmt->execute([$current_user['id']]);
    $med_history = $history_stmt->fetchAll();
    
    // Get compliance statistics
    $stats_stmt = $pdo->prepare("
        SELECT 
            medication_name,
            COUNT(*) as total_entries,
            SUM(CASE WHEN taken = 1 THEN 1 ELSE 0 END) as taken_count,
            AVG(CASE WHEN taken = 1 THEN 1 ELSE 0 END) * 100 as compliance_rate
        FROM medications 
        WHERE user_id = ? AND taken_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY medication_name 
        ORDER BY compliance_rate DESC
    ");
    $stats_stmt->execute([$current_user['id']]);
    $compliance_stats = $stats_stmt->fetchAll();
    
} catch (Exception $e) {
    logError('medication_module', 'Failed to load medication data: ' . $e->getMessage(), __FILE__, __LINE__);
    $today_meds = [];
    $med_schedule = [];
    $med_history = [];
    $compliance_stats = [];
}

// Common medications for cancer patients
$common_medications = [
    'Zofran (Ondansetron)', 'Compazine (Prochlorperazine)', 'Ativan (Lorazepam)',
    'Dexamethasone', 'Oxycodone', 'Morphine', 'Gabapentin', 'Omeprazole',
    'Lorazepam', 'Metoclopramide', 'Simethicone', 'Docusate'
];
?>

<div class="module-container">
    <!-- Module Header -->
    <div class="module-header">
        <div class="header-content">
            <h1 class="module-title">
                <span class="module-icon">💊</span>
                Medication Management
            </h1>
            <p class="module-description">
                Track your medications, set reminders, and maintain compliance records for your healthcare team.
            </p>
        </div>
        <div class="header-actions">
            <button onclick="openQuickTakeModal()" class="btn btn-primary btn-large">
                <span class="btn-icon">✅</span>
                <span class="btn-text">Mark as Taken</span>
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
    
    <!-- Today's Schedule -->
    <div class="today-section">
        <h2 class="section-title">Today's Medications</h2>
        
        <?php if (empty($med_schedule)): ?>
            <div class="empty-schedule">
                <div class="empty-icon">💊</div>
                <h3 class="empty-title">No medication schedule set up yet</h3>
                <p class="empty-description">Add your medications and dosage times to track compliance.</p>
                <button onclick="openScheduleModal()" class="btn btn-primary">
                    <span class="btn-icon">➕</span>
                    <span class="btn-text">Add Medication Schedule</span>
                </button>
            </div>
        <?php else: ?>
            <div class="schedule-grid">
                <?php foreach ($med_schedule as $med): ?>
                    <div class="schedule-card">
                        <div class="med-header">
                            <h3 class="med-name"><?php echo htmlspecialchars($med['medication_name']); ?></h3>
                            <span class="med-dosage"><?php echo htmlspecialchars($med['dosage']); ?></span>
                        </div>
                        <div class="med-times">
                            <?php for ($i = 1; $i <= $med['frequency_times_per_day']; $i++): ?>
                                <?php if ($med["time_$i"]): ?>
                                    <div class="dose-time">
                                        <span class="time"><?php echo date('g:i A', strtotime($med["time_$i"])); ?></span>
                                        <button onclick="markTaken('<?php echo htmlspecialchars($med['medication_name']); ?>', '<?php echo htmlspecialchars($med['dosage']); ?>')" 
                                                class="take-btn">
                                            ✓ Take
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Compliance Overview -->
    <?php if (!empty($compliance_stats)): ?>
    <div class="compliance-section">
        <h2 class="section-title">30-Day Compliance Overview</h2>
        <div class="compliance-grid">
            <?php foreach ($compliance_stats as $stat): ?>
                <div class="compliance-card">
                    <div class="compliance-header">
                        <h3 class="med-name"><?php echo htmlspecialchars($stat['medication_name']); ?></h3>
                        <span class="compliance-rate <?php echo $stat['compliance_rate'] >= 80 ? 'good' : ($stat['compliance_rate'] >= 60 ? 'fair' : 'poor'); ?>">
                            <?php echo round($stat['compliance_rate']); ?>%
                        </span>
                    </div>
                    <div class="compliance-details">
                        <span class="taken-count"><?php echo $stat['taken_count']; ?> of <?php echo $stat['total_entries']; ?> doses taken</span>
                        <div class="compliance-bar">
                            <div class="compliance-fill" style="width: <?php echo $stat['compliance_rate']; ?>%"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Quick Take Form -->
    <div class="quick-take-section">
        <h2 class="section-title">Record Medication</h2>
        <form method="POST" class="medication-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="take_medication">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="medication_name" class="form-label">Medication</label>
                    <input type="text" id="medication_name" name="medication_name" class="form-input" 
                           placeholder="Enter medication name" list="common-medications" required>
                    <datalist id="common-medications">
                        <?php foreach ($common_medications as $med): ?>
                            <option value="<?php echo htmlspecialchars($med); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                
                <div class="form-group">
                    <label for="dosage" class="form-label">Dosage</label>
                    <input type="text" id="dosage" name="dosage" class="form-input" 
                           placeholder="e.g., 10mg, 1 tablet, 2 capsules">
                </div>
            </div>
            
            <div class="form-group">
                <label for="notes" class="form-label">Notes (Optional)</label>
                <textarea id="notes" name="notes" class="form-textarea" rows="2" 
                          placeholder="Any additional notes about taking this medication..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="side_effects" class="form-label">Side Effects (Optional)</label>
                <textarea id="side_effects" name="side_effects" class="form-textarea" rows="2" 
                          placeholder="Any side effects experienced..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary btn-large">
                <span class="btn-icon">✅</span>
                <span class="btn-text">Mark as Taken</span>
            </button>
        </form>
    </div>
    
    <!-- Common Medications Quick Access -->
    <div class="quick-medications">
        <h3 class="subsection-title">Quick Access - Common Medications</h3>
        <div class="medication-buttons">
            <?php foreach (array_slice($common_medications, 0, 8) as $med): ?>
                <button onclick="quickTakeMedication('<?php echo htmlspecialchars($med); ?>')" 
                        class="medication-btn">
                    <?php echo htmlspecialchars($med); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Recent History -->
    <div class="history-section">
        <h2 class="section-title">Recent Medication History</h2>
        
        <?php if (empty($med_history)): ?>
            <div class="empty-state">
                <div class="empty-icon">💊</div>
                <h3 class="empty-title">No medications recorded yet</h3>
                <p class="empty-description">Start tracking your medications to monitor compliance and side effects.</p>
            </div>
        <?php else: ?>
            <div class="history-list">
                <?php foreach ($med_history as $med): ?>
                    <div class="medication-entry">
                        <div class="med-info">
                            <h3 class="med-name"><?php echo htmlspecialchars($med['medication_name']); ?></h3>
                            <?php if (!empty($med['dosage'])): ?>
                                <span class="med-dosage"><?php echo htmlspecialchars($med['dosage']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="med-meta">
                            <span class="med-status <?php echo $med['taken'] ? 'taken' : 'missed'; ?>">
                                <?php echo $med['taken'] ? '✅ Taken' : '❌ Missed'; ?>
                            </span>
                            <span class="med-time">
                                <?php echo date('M j, Y g:i A', strtotime($med['taken_at'])); ?>
                            </span>
                        </div>
                        <?php if (!empty($med['notes']) || !empty($med['side_effects'])): ?>
                            <div class="med-details">
                                <?php if (!empty($med['notes'])): ?>
                                    <p class="med-notes"><strong>Notes:</strong> <?php echo htmlspecialchars($med['notes']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($med['side_effects'])): ?>
                                    <p class="med-side-effects"><strong>Side Effects:</strong> <?php echo htmlspecialchars($med['side_effects']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Schedule Modal -->
<div id="scheduleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>📅 Add Medication Schedule</h2>
            <button onclick="closeScheduleModal()" class="close-btn">✕</button>
        </div>
        <div class="modal-body">
            <form method="POST" class="schedule-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="add_schedule">
                
                <div class="form-group">
                    <label for="schedule_medication_name" class="form-label">Medication Name</label>
                    <input type="text" id="schedule_medication_name" name="medication_name" class="form-input" 
                           list="common-medications" required>
                </div>
                
                <div class="form-group">
                    <label for="schedule_dosage" class="form-label">Dosage</label>
                    <input type="text" id="schedule_dosage" name="dosage" class="form-input" 
                           placeholder="e.g., 10mg, 1 tablet">
                </div>
                
                <div class="form-group">
                    <label for="frequency" class="form-label">How many times per day?</label>
                    <select id="frequency" name="frequency" class="form-select" onchange="updateTimeFields()" required>
                        <option value="1">Once daily</option>
                        <option value="2">Twice daily</option>
                        <option value="3">Three times daily</option>
                        <option value="4">Four times daily</option>
                    </select>
                </div>
                
                <div class="time-fields" id="timeFields">
                    <div class="form-group time-field">
                        <label for="time_1" class="form-label">Time 1</label>
                        <input type="time" id="time_1" name="time_1" class="form-input" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-large">
                    <span class="btn-icon">📅</span>
                    <span class="btn-text">Add Schedule</span>
                </button>
            </form>
        </div>
    </div>
</div>

<style>
/* Medication Module Styles */
.schedule-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

.schedule-card {
    background: white;
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--gray-200);
}

.med-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-md);
    padding-bottom: var(--spacing-sm);
    border-bottom: 1px solid var(--gray-200);
}

.med-name {
    font-weight: 600;
    color: var(--gray-800);
    font-size: var(--font-size-lg);
}

.med-dosage {
    background: var(--primary-color);
    color: white;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-full);
    font-size: var(--font-size-sm);
    font-weight: 500;
}

.dose-time {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-sm) 0;
    border-bottom: 1px solid var(--gray-100);
}

.dose-time:last-child {
    border-bottom: none;
}

.time {
    font-weight: 500;
    color: var(--gray-700);
}

.take-btn {
    background: var(--success-color);
    color: white;
    border: none;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-md);
    font-size: var(--font-size-sm);
    font-weight: 500;
    cursor: pointer;
    transition: all var(--transition-fast);
}

.take-btn:hover {
    background: #059669;
    transform: translateY(-1px);
}

/* Compliance Styles */
.compliance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

.compliance-card {
    background: white;
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--gray-200);
}

.compliance-rate.good { color: var(--success-color); font-weight: 600; }
.compliance-rate.fair { color: var(--warning-color); font-weight: 600; }
.compliance-rate.poor { color: var(--danger-color); font-weight: 600; }

.compliance-bar {
    width: 100%;
    height: 8px;
    background: var(--gray-200);
    border-radius: var(--radius-full);
    overflow: hidden;
    margin-top: var(--spacing-xs);
}

.compliance-fill {
    height: 100%;
    background: var(--success-color);
    transition: width var(--transition-normal);
}

/* Medication Buttons */
.medication-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-xl);
}

.medication-btn {
    padding: var(--spacing-sm) var(--spacing-md);
    border: 2px solid var(--gray-300);
    border-radius: var(--radius-lg);
    background: white;
    color: var(--gray-700);
    cursor: pointer;
    transition: all var(--transition-fast);
    font-weight: 500;
    text-align: left;
}

.medication-btn:hover {
    border-color: var(--primary-color);
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
}

/* History Styles */
.medication-entry {
    background: white;
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-md);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
}

.med-status.taken { color: var(--success-color); font-weight: 600; }
.med-status.missed { color: var(--danger-color); font-weight: 600; }

.time-fields {
    display: grid;
    gap: var(--spacing-md);
}

/* Responsive Design */
@media (max-width: 768px) {
    .schedule-grid,
    .compliance-grid {
        grid-template-columns: 1fr;
    }
    
    .medication-buttons {
        grid-template-columns: 1fr;
    }
    
    .dose-time {
        flex-direction: column;
        gap: var(--spacing-sm);
        align-items: flex-start;
    }
}
</style>

<script>
function openScheduleModal() {
    document.getElementById('scheduleModal').style.display = 'flex';
}

function closeScheduleModal() {
    document.getElementById('scheduleModal').style.display = 'none';
}

function openQuickTakeModal() {
    document.querySelector('.quick-take-section').scrollIntoView({ behavior: 'smooth' });
    document.getElementById('medication_name').focus();
}

function quickTakeMedication(medName) {
    document.getElementById('medication_name').value = medName;
    document.querySelector('.medication-form').scrollIntoView({ behavior: 'smooth' });
    document.getElementById('dosage').focus();
}

function markTaken(medName, dosage) {
    document.getElementById('medication_name').value = medName;
    document.getElementById('dosage').value = dosage;
    document.querySelector('.medication-form').scrollIntoView({ behavior: 'smooth' });
}

function updateTimeFields() {
    const frequency = parseInt(document.getElementById('frequency').value);
    const timeFields = document.getElementById('timeFields');
    
    let html = '';
    for (let i = 1; i <= frequency; i++) {
        html += `
            <div class="form-group time-field">
                <label for="time_${i}" class="form-label">Time ${i}</label>
                <input type="time" id="time_${i}" name="time_${i}" class="form-input" required>
            </div>
        `;
    }
    
    timeFields.innerHTML = html;
}

// Initialize time fields
document.addEventListener('DOMContentLoaded', function() {
    updateTimeFields();
});
</script>

<?php require_once '../includes/footer.php'; ?>