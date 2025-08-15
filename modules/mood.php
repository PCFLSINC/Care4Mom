<?php
/**
 * Care4Mom - Mood & Wellness Module
 * Track mood, emotional state, and mindfulness activities
 * Author: Care4Mom Development Team
 * Version: 1.0
 */

$page_title = 'Mood & Wellness';
$body_class = 'module-page mood-page';

require_once '../includes/header.php';
requireLogin();

$current_user = getCurrentUser();
$success_message = '';
$errors = [];

// Handle mood logging
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token. Please try again.');
        }
        
        if ($_POST['action'] === 'log_mood') {
            $mood_score = intval($_POST['mood_score'] ?? 0);
            $energy_level = !empty($_POST['energy_level']) ? intval($_POST['energy_level']) : null;
            $anxiety_level = !empty($_POST['anxiety_level']) ? intval($_POST['anxiety_level']) : null;
            $notes = sanitizeInput($_POST['notes'] ?? '');
            $mindfulness_activity = sanitizeInput($_POST['mindfulness_activity'] ?? '');
            $activity_completed = isset($_POST['activity_completed']);
            
            // Validation
            if ($mood_score < 1 || $mood_score > 10) $errors[] = 'Mood score must be between 1 and 10';
            if ($energy_level && ($energy_level < 1 || $energy_level > 10)) $errors[] = 'Energy level must be between 1 and 10';
            if ($anxiety_level && ($anxiety_level < 1 || $anxiety_level > 10)) $errors[] = 'Anxiety level must be between 1 and 10';
            
            if (empty($errors)) {
                $stmt = $pdo->prepare("
                    INSERT INTO mood_logs 
                    (user_id, mood_score, energy_level, anxiety_level, notes, mindfulness_activity, activity_completed) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([
                    $current_user['id'], $mood_score, $energy_level, $anxiety_level, 
                    $notes, $mindfulness_activity, $activity_completed
                ])) {
                    $success_message = 'Mood logged successfully!';
                    logActivity('mood_logged', "Logged mood: $mood_score/10", $current_user['id']);
                    
                    // Check for concerning mood patterns and create alerts
                    if ($mood_score <= 3 || ($anxiety_level && $anxiety_level >= 8)) {
                        $alert_message = $mood_score <= 3 
                            ? "Low mood detected ($mood_score/10). Consider reaching out to your support network or healthcare provider."
                            : "High anxiety level detected ($anxiety_level/10). Consider practicing mindfulness exercises or contacting your healthcare provider.";
                        
                        $alert_stmt = $pdo->prepare("
                            INSERT INTO ai_alerts (user_id, alert_type, title, message, severity) 
                            VALUES (?, 'advice', 'Mental Health Check-in', ?, 'medium')
                        ");
                        $alert_stmt->execute([$current_user['id'], $alert_message]);
                    }
                    
                    // Suggest mindfulness activities for low mood/high anxiety
                    if (($mood_score <= 5 || ($anxiety_level && $anxiety_level >= 6)) && empty($mindfulness_activity)) {
                        $suggestion_message = "Your mood/anxiety levels suggest you might benefit from mindfulness activities. Consider trying deep breathing, meditation, or gentle movement.";
                        $suggestion_stmt = $pdo->prepare("
                            INSERT INTO ai_alerts (user_id, alert_type, title, message, severity) 
                            VALUES (?, 'advice', 'Mindfulness Suggestion', ?, 'low')
                        ");
                        $suggestion_stmt->execute([$current_user['id'], $suggestion_message]);
                    }
                } else {
                    $errors[] = 'Failed to log mood. Please try again.';
                }
            }
        }
    } catch (Exception $e) {
        logError('mood_module', $e->getMessage(), __FILE__, __LINE__);
        $errors[] = 'An error occurred. Please try again.';
    }
}

try {
    // Get recent mood logs
    $recent_stmt = $pdo->prepare("
        SELECT * FROM mood_logs 
        WHERE user_id = ? 
        ORDER BY logged_at DESC 
        LIMIT 10
    ");
    $recent_stmt->execute([$current_user['id']]);
    $recent_moods = $recent_stmt->fetchAll();
    
    // Get mood trends (last 30 days)
    $trends_stmt = $pdo->prepare("
        SELECT 
            DATE(logged_at) as date,
            AVG(mood_score) as avg_mood,
            AVG(energy_level) as avg_energy,
            AVG(anxiety_level) as avg_anxiety,
            COUNT(*) as entries_count
        FROM mood_logs 
        WHERE user_id = ? AND logged_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(logged_at) 
        ORDER BY date DESC
        LIMIT 30
    ");
    $trends_stmt->execute([$current_user['id']]);
    $mood_trends = $trends_stmt->fetchAll();
    
    // Get mood statistics
    $stats_stmt = $pdo->prepare("
        SELECT 
            AVG(mood_score) as avg_mood_score,
            MIN(mood_score) as min_mood_score,
            MAX(mood_score) as max_mood_score,
            AVG(energy_level) as avg_energy_level,
            AVG(anxiety_level) as avg_anxiety_level,
            COUNT(CASE WHEN activity_completed = 1 THEN 1 END) as completed_activities,
            COUNT(*) as total_entries
        FROM mood_logs 
        WHERE user_id = ? AND logged_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stats_stmt->execute([$current_user['id']]);
    $mood_stats = $stats_stmt->fetch();
    
    // Get latest mood
    $latest_stmt = $pdo->prepare("
        SELECT * FROM mood_logs 
        WHERE user_id = ? 
        ORDER BY logged_at DESC 
        LIMIT 1
    ");
    $latest_stmt->execute([$current_user['id']]);
    $latest_mood = $latest_stmt->fetch();
    
} catch (Exception $e) {
    logError('mood_module', 'Failed to load mood data: ' . $e->getMessage(), __FILE__, __LINE__);
    $recent_moods = [];
    $mood_trends = [];
    $mood_stats = null;
    $latest_mood = null;
}

// Mindfulness activities
$mindfulness_activities = [
    'Deep Breathing Exercise (5 minutes)',
    'Body Scan Meditation (10 minutes)',
    'Gratitude Journaling',
    'Progressive Muscle Relaxation',
    'Mindful Walking',
    'Guided Meditation',
    'Gentle Stretching',
    'Listening to Calming Music',
    'Nature Observation',
    'Loving-kindness Meditation'
];

// Mood emojis for visual representation
$mood_emojis = [
    1 => '😭', 2 => '😢', 3 => '😔', 4 => '😕', 5 => '😐',
    6 => '🙂', 7 => '😊', 8 => '😄', 9 => '😁', 10 => '🥰'
];
?>

<div class="module-container">
    <!-- Module Header -->
    <div class="module-header">
        <div class="header-content">
            <h1 class="module-title">
                <span class="module-icon">😊</span>
                Mood & Wellness
            </h1>
            <p class="module-description">
                Track your emotional well-being, practice mindfulness, and maintain mental health awareness throughout your care journey.
            </p>
        </div>
        <div class="header-actions">
            <button onclick="openQuickMoodModal()" class="btn btn-primary btn-large">
                <span class="btn-icon">💭</span>
                <span class="btn-text">Log Mood</span>
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
    
    <!-- Current Mood Status -->
    <?php if ($latest_mood): ?>
    <div class="current-mood-section">
        <h2 class="section-title">Current Mood Status</h2>
        <div class="mood-status-card">
            <div class="mood-display">
                <div class="mood-emoji">
                    <?php echo $mood_emojis[$latest_mood['mood_score']] ?? '😐'; ?>
                </div>
                <div class="mood-details">
                    <div class="mood-score">
                        <span class="score-label">Mood</span>
                        <span class="score-value mood-<?php echo $latest_mood['mood_score']; ?>">
                            <?php echo $latest_mood['mood_score']; ?>/10
                        </span>
                    </div>
                    <?php if ($latest_mood['energy_level']): ?>
                    <div class="energy-score">
                        <span class="score-label">Energy</span>
                        <span class="score-value energy-<?php echo $latest_mood['energy_level']; ?>">
                            <?php echo $latest_mood['energy_level']; ?>/10
                        </span>
                    </div>
                    <?php endif; ?>
                    <?php if ($latest_mood['anxiety_level']): ?>
                    <div class="anxiety-score">
                        <span class="score-label">Anxiety</span>
                        <span class="score-value anxiety-<?php echo $latest_mood['anxiety_level']; ?>">
                            <?php echo $latest_mood['anxiety_level']; ?>/10
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mood-meta">
                <span class="mood-time">
                    Logged: <?php echo date('M j, Y g:i A', strtotime($latest_mood['logged_at'])); ?>
                </span>
                <?php if (!empty($latest_mood['mindfulness_activity'])): ?>
                <span class="mindfulness-activity">
                    🧘‍♀️ <?php echo htmlspecialchars($latest_mood['mindfulness_activity']); ?>
                    <?php if ($latest_mood['activity_completed']): ?>
                        <span class="completed">✅ Completed</span>
                    <?php endif; ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- 30-Day Overview -->
    <?php if ($mood_stats && $mood_stats['total_entries'] > 0): ?>
    <div class="mood-overview">
        <h2 class="section-title">30-Day Wellness Overview</h2>
        <div class="overview-grid">
            <div class="overview-card">
                <div class="overview-icon">😊</div>
                <h3 class="overview-title">Average Mood</h3>
                <div class="overview-value mood-<?php echo round($mood_stats['avg_mood_score']); ?>">
                    <?php echo number_format($mood_stats['avg_mood_score'], 1); ?>/10
                </div>
                <div class="overview-range">
                    Range: <?php echo $mood_stats['min_mood_score']; ?> - <?php echo $mood_stats['max_mood_score']; ?>
                </div>
            </div>
            
            <?php if ($mood_stats['avg_energy_level']): ?>
            <div class="overview-card">
                <div class="overview-icon">⚡</div>
                <h3 class="overview-title">Average Energy</h3>
                <div class="overview-value energy-<?php echo round($mood_stats['avg_energy_level']); ?>">
                    <?php echo number_format($mood_stats['avg_energy_level'], 1); ?>/10
                </div>
                <div class="overview-description">Energy levels</div>
            </div>
            <?php endif; ?>
            
            <?php if ($mood_stats['avg_anxiety_level']): ?>
            <div class="overview-card">
                <div class="overview-icon">😰</div>
                <h3 class="overview-title">Average Anxiety</h3>
                <div class="overview-value anxiety-<?php echo round($mood_stats['avg_anxiety_level']); ?>">
                    <?php echo number_format($mood_stats['avg_anxiety_level'], 1); ?>/10
                </div>
                <div class="overview-description">Anxiety levels</div>
            </div>
            <?php endif; ?>
            
            <div class="overview-card">
                <div class="overview-icon">🧘‍♀️</div>
                <h3 class="overview-title">Mindfulness</h3>
                <div class="overview-value mindfulness">
                    <?php echo $mood_stats['completed_activities']; ?>
                </div>
                <div class="overview-description">Activities completed</div>
            </div>
            
            <div class="overview-card">
                <div class="overview-icon">📝</div>
                <h3 class="overview-title">Check-ins</h3>
                <div class="overview-value entries">
                    <?php echo $mood_stats['total_entries']; ?>
                </div>
                <div class="overview-description">Mood entries logged</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Mood Logging Form -->
    <div class="mood-logging-section">
        <h2 class="section-title">Log Your Mood</h2>
        <form method="POST" class="mood-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="log_mood">
            
            <div class="mood-form-grid">
                <!-- Mood Score -->
                <div class="form-group mood-score-group">
                    <label for="mood_score" class="form-label">
                        How are you feeling right now?
                        <span class="mood-emoji-display" id="moodEmojiDisplay">😐</span>
                    </label>
                    <div class="mood-slider-container">
                        <input type="range" id="mood_score" name="mood_score" class="mood-slider" 
                               min="1" max="10" value="5" oninput="updateMoodDisplay(this.value)">
                        <div class="mood-scale">
                            <span class="scale-label">😭<br>Very Sad</span>
                            <span class="scale-label">😐<br>Neutral</span>
                            <span class="scale-label">🥰<br>Very Happy</span>
                        </div>
                    </div>
                    <div class="mood-value-display">
                        <span id="moodValueDisplay">5</span>/10
                    </div>
                </div>
                
                <!-- Energy Level -->
                <div class="form-group">
                    <label for="energy_level" class="form-label">
                        Energy Level (Optional)
                        <span class="energy-display" id="energyDisplay">⚡</span>
                    </label>
                    <div class="slider-container">
                        <input type="range" id="energy_level" name="energy_level" class="energy-slider" 
                               min="1" max="10" value="5" oninput="updateEnergyDisplay(this.value)">
                        <div class="slider-scale">
                            <span class="scale-label">😴 Exhausted</span>
                            <span class="scale-label">⚡ Energized</span>
                        </div>
                    </div>
                </div>
                
                <!-- Anxiety Level -->
                <div class="form-group">
                    <label for="anxiety_level" class="form-label">
                        Anxiety Level (Optional)
                        <span class="anxiety-display" id="anxietyDisplay">😌</span>
                    </label>
                    <div class="slider-container">
                        <input type="range" id="anxiety_level" name="anxiety_level" class="anxiety-slider" 
                               min="1" max="10" value="3" oninput="updateAnxietyDisplay(this.value)">
                        <div class="slider-scale">
                            <span class="scale-label">😌 Calm</span>
                            <span class="scale-label">😰 Very Anxious</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mindfulness Activity -->
            <div class="form-group">
                <label for="mindfulness_activity" class="form-label">Mindfulness Activity (Optional)</label>
                <select id="mindfulness_activity" name="mindfulness_activity" class="form-select">
                    <option value="">Select an activity...</option>
                    <?php foreach ($mindfulness_activities as $activity): ?>
                        <option value="<?php echo htmlspecialchars($activity); ?>">
                            <?php echo htmlspecialchars($activity); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="activity-checkbox">
                    <input type="checkbox" id="activity_completed" name="activity_completed">
                    <label for="activity_completed" class="checkbox-label">I completed this activity</label>
                </div>
            </div>
            
            <!-- Notes -->
            <div class="form-group">
                <label for="notes" class="form-label">Notes (Optional)</label>
                <textarea id="notes" name="notes" class="form-textarea" rows="3" 
                          placeholder="How are you feeling? What's on your mind? Any thoughts about your mood today..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary btn-large">
                <span class="btn-icon">💭</span>
                <span class="btn-text">Log Mood</span>
            </button>
        </form>
    </div>
    
    <!-- Mindfulness Activities -->
    <div class="mindfulness-section">
        <h2 class="section-title">Mindfulness & Self-Care</h2>
        <div class="mindfulness-grid">
            <div class="mindfulness-card breathing">
                <div class="card-icon">🫁</div>
                <h3 class="card-title">Deep Breathing</h3>
                <p class="card-description">5-minute guided breathing exercise to reduce stress and promote calm.</p>
                <button onclick="startBreathingExercise()" class="btn btn-secondary">Start Exercise</button>
            </div>
            
            <div class="mindfulness-card meditation">
                <div class="card-icon">🧘‍♀️</div>
                <h3 class="card-title">Guided Meditation</h3>
                <p class="card-description">Short meditation sessions designed for comfort and healing.</p>
                <button onclick="startMeditation()" class="btn btn-secondary">Begin Meditation</button>
            </div>
            
            <div class="mindfulness-card gratitude">
                <div class="card-icon">📝</div>
                <h3 class="card-title">Gratitude Journal</h3>
                <p class="card-description">Reflect on positive moments and things you're grateful for.</p>
                <button onclick="openGratitudeJournal()" class="btn btn-secondary">Start Writing</button>
            </div>
            
            <div class="mindfulness-card relaxation">
                <div class="card-icon">🌊</div>
                <h3 class="card-title">Progressive Relaxation</h3>
                <p class="card-description">Gentle muscle relaxation technique for physical and mental relief.</p>
                <button onclick="startRelaxation()" class="btn btn-secondary">Begin Session</button>
            </div>
        </div>
    </div>
    
    <!-- Recent Mood History -->
    <div class="history-section">
        <h2 class="section-title">Recent Mood History</h2>
        
        <?php if (empty($recent_moods)): ?>
            <div class="empty-state">
                <div class="empty-icon">😊</div>
                <h3 class="empty-title">No mood entries yet</h3>
                <p class="empty-description">Start tracking your mood to identify patterns and improve your emotional well-being.</p>
            </div>
        <?php else: ?>
            <div class="mood-history">
                <?php foreach ($recent_moods as $mood): ?>
                    <div class="mood-entry">
                        <div class="mood-header">
                            <div class="mood-emoji-large">
                                <?php echo $mood_emojis[$mood['mood_score']] ?? '😐'; ?>
                            </div>
                            <div class="mood-scores">
                                <div class="mood-primary">
                                    Mood: <span class="score mood-<?php echo $mood['mood_score']; ?>"><?php echo $mood['mood_score']; ?>/10</span>
                                </div>
                                <?php if ($mood['energy_level']): ?>
                                    <div class="mood-secondary">
                                        Energy: <span class="score energy-<?php echo $mood['energy_level']; ?>"><?php echo $mood['energy_level']; ?>/10</span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($mood['anxiety_level']): ?>
                                    <div class="mood-secondary">
                                        Anxiety: <span class="score anxiety-<?php echo $mood['anxiety_level']; ?>"><?php echo $mood['anxiety_level']; ?>/10</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="mood-date">
                                <?php echo date('M j, Y g:i A', strtotime($mood['logged_at'])); ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($mood['mindfulness_activity']) || !empty($mood['notes'])): ?>
                        <div class="mood-details">
                            <?php if (!empty($mood['mindfulness_activity'])): ?>
                                <div class="mindfulness-info">
                                    🧘‍♀️ <?php echo htmlspecialchars($mood['mindfulness_activity']); ?>
                                    <?php if ($mood['activity_completed']): ?>
                                        <span class="completed-badge">✅ Completed</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($mood['notes'])): ?>
                                <div class="mood-notes">
                                    <p><?php echo nl2br(htmlspecialchars($mood['notes'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Breathing Exercise Modal -->
<div id="breathingModal" class="modal">
    <div class="modal-content breathing-modal">
        <div class="modal-header">
            <h2>🫁 Deep Breathing Exercise</h2>
            <button onclick="closeBreathingModal()" class="close-btn">✕</button>
        </div>
        <div class="modal-body">
            <div class="breathing-exercise">
                <div class="breathing-circle" id="breathingCircle">
                    <div class="breathing-text" id="breathingText">Get Ready</div>
                </div>
                <div class="breathing-controls">
                    <button onclick="startBreathingCycle()" class="btn btn-primary" id="breathingStartBtn">Start Exercise</button>
                    <button onclick="stopBreathingCycle()" class="btn btn-secondary" id="breathingStopBtn" style="display: none;">Stop</button>
                </div>
                <div class="breathing-info">
                    <p>This 5-minute breathing exercise will help you relax and reduce stress. Follow the visual guide and breathe naturally.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Mood Module Styles */
.mood-status-card {
    background: white;
    border-radius: var(--radius-xl);
    padding: var(--spacing-xl);
    box-shadow: var(--shadow-lg);
    margin-bottom: var(--spacing-xl);
    border: 1px solid var(--gray-200);
}

.mood-display {
    display: flex;
    align-items: center;
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-md);
}

.mood-emoji {
    font-size: 4rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mood-details {
    display: flex;
    gap: var(--spacing-lg);
    flex-wrap: wrap;
}

.mood-score, .energy-score, .anxiety-score {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--spacing-xs);
}

.score-label {
    font-size: var(--font-size-sm);
    color: var(--gray-600);
    font-weight: 500;
}

.score-value {
    font-size: var(--font-size-xl);
    font-weight: 700;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-md);
}

/* Mood color coding */
.mood-1, .mood-2, .mood-3 { color: #dc2626; background: #fef2f2; }
.mood-4, .mood-5 { color: #f59e0b; background: #fefbf3; }
.mood-6, .mood-7 { color: #10b981; background: #f0fdf4; }
.mood-8, .mood-9, .mood-10 { color: #059669; background: #ecfdf5; }

.energy-1, .energy-2, .energy-3 { color: #6b7280; background: #f9fafb; }
.energy-4, .energy-5, .energy-6 { color: #f59e0b; background: #fefbf3; }
.energy-7, .energy-8, .energy-9, .energy-10 { color: #059669; background: #ecfdf5; }

.anxiety-1, .anxiety-2, .anxiety-3 { color: #059669; background: #ecfdf5; }
.anxiety-4, .anxiety-5, .anxiety-6 { color: #f59e0b; background: #fefbf3; }
.anxiety-7, .anxiety-8, .anxiety-9, .anxiety-10 { color: #dc2626; background: #fef2f2; }

/* Overview Grid */
.overview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

.overview-card {
    background: white;
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    text-align: center;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--gray-200);
}

.overview-icon {
    font-size: 2.5rem;
    margin-bottom: var(--spacing-sm);
}

.overview-value {
    font-size: var(--font-size-2xl);
    font-weight: 700;
    margin: var(--spacing-sm) 0;
}

/* Mood Form */
.mood-form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--spacing-xl);
    margin-bottom: var(--spacing-xl);
}

.mood-score-group {
    text-align: center;
}

.mood-emoji-display {
    font-size: 2rem;
    margin-left: var(--spacing-sm);
}

.mood-slider-container {
    margin: var(--spacing-lg) 0;
}

.mood-slider, .energy-slider, .anxiety-slider {
    width: 100%;
    height: 12px;
    border-radius: var(--radius-full);
    outline: none;
    -webkit-appearance: none;
    margin: var(--spacing-md) 0;
}

.mood-slider {
    background: linear-gradient(to right, #dc2626, #f59e0b, #10b981, #059669);
}

.energy-slider {
    background: linear-gradient(to right, #6b7280, #f59e0b, #059669);
}

.anxiety-slider {
    background: linear-gradient(to right, #059669, #f59e0b, #dc2626);
}

.mood-slider::-webkit-slider-thumb,
.energy-slider::-webkit-slider-thumb,
.anxiety-slider::-webkit-slider-thumb {
    appearance: none;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: white;
    border: 3px solid var(--primary-color);
    cursor: pointer;
    box-shadow: var(--shadow-lg);
}

.mood-scale, .slider-scale {
    display: flex;
    justify-content: space-between;
    margin-top: var(--spacing-sm);
}

.scale-label {
    font-size: var(--font-size-xs);
    color: var(--gray-500);
    text-align: center;
}

.mood-value-display {
    font-size: var(--font-size-2xl);
    font-weight: 700;
    color: var(--primary-color);
    margin-top: var(--spacing-md);
}

/* Mindfulness Grid */
.mindfulness-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

.mindfulness-card {
    background: white;
    border-radius: var(--radius-xl);
    padding: var(--spacing-xl);
    text-align: center;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--gray-200);
    transition: all var(--transition-normal);
}

.mindfulness-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
}

.mindfulness-card .card-icon {
    font-size: 3rem;
    margin-bottom: var(--spacing-md);
}

.mindfulness-card .card-title {
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: var(--spacing-sm);
}

.mindfulness-card .card-description {
    color: var(--gray-600);
    margin-bottom: var(--spacing-md);
    line-height: 1.5;
}

/* Mood History */
.mood-entry {
    background: white;
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-md);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
}

.mood-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-md);
}

.mood-emoji-large {
    font-size: 2.5rem;
}

.mood-scores {
    flex: 1;
}

.mood-primary {
    font-size: var(--font-size-lg);
    font-weight: 600;
    margin-bottom: var(--spacing-xs);
}

.mood-secondary {
    font-size: var(--font-size-sm);
    color: var(--gray-600);
}

.mood-date {
    font-size: var(--font-size-sm);
    color: var(--gray-500);
}

.completed-badge {
    background: var(--success-color);
    color: white;
    padding: var(--spacing-xs);
    border-radius: var(--radius-sm);
    font-size: var(--font-size-xs);
    font-weight: 500;
    margin-left: var(--spacing-sm);
}

/* Breathing Exercise */
.breathing-modal .modal-content {
    max-width: 600px;
}

.breathing-exercise {
    text-align: center;
    padding: var(--spacing-xl);
}

.breathing-circle {
    width: 200px;
    height: 200px;
    border-radius: 50%;
    background: var(--gradient-primary);
    margin: 0 auto var(--spacing-xl);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 4s ease-in-out;
}

.breathing-circle.inhale {
    transform: scale(1.3);
}

.breathing-circle.exhale {
    transform: scale(1);
}

.breathing-text {
    color: white;
    font-size: var(--font-size-xl);
    font-weight: 600;
}

.breathing-controls {
    margin-bottom: var(--spacing-lg);
}

.breathing-info {
    color: var(--gray-600);
    font-style: italic;
}

/* Responsive Design */
@media (max-width: 768px) {
    .mood-display {
        flex-direction: column;
        text-align: center;
    }
    
    .mood-details {
        justify-content: center;
    }
    
    .overview-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .mindfulness-grid {
        grid-template-columns: 1fr;
    }
    
    .mood-header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-sm);
    }
}

@media (max-width: 480px) {
    .overview-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Mood display functions
function updateMoodDisplay(value) {
    const emojis = {
        1: '😭', 2: '😢', 3: '😔', 4: '😕', 5: '😐',
        6: '🙂', 7: '😊', 8: '😄', 9: '😁', 10: '🥰'
    };
    
    document.getElementById('moodEmojiDisplay').textContent = emojis[value] || '😐';
    document.getElementById('moodValueDisplay').textContent = value;
}

function updateEnergyDisplay(value) {
    const icons = {
        1: '😴', 2: '😴', 3: '😐', 4: '😐', 5: '🙂',
        6: '🙂', 7: '😊', 8: '⚡', 9: '⚡', 10: '🚀'
    };
    
    document.getElementById('energyDisplay').textContent = icons[value] || '⚡';
}

function updateAnxietyDisplay(value) {
    const icons = {
        1: '😌', 2: '😌', 3: '🙂', 4: '😐', 5: '😕',
        6: '😟', 7: '😰', 8: '😨', 9: '😰', 10: '😱'
    };
    
    document.getElementById('anxietyDisplay').textContent = icons[value] || '😌';
}

function openQuickMoodModal() {
    document.querySelector('.mood-logging-section').scrollIntoView({ behavior: 'smooth' });
    document.getElementById('mood_score').focus();
}

// Mindfulness activities
function startBreathingExercise() {
    document.getElementById('breathingModal').style.display = 'flex';
}

function closeBreathingModal() {
    document.getElementById('breathingModal').style.display = 'none';
    stopBreathingCycle();
}

let breathingInterval;
let breathingActive = false;

function startBreathingCycle() {
    const circle = document.getElementById('breathingCircle');
    const text = document.getElementById('breathingText');
    const startBtn = document.getElementById('breathingStartBtn');
    const stopBtn = document.getElementById('breathingStopBtn');
    
    breathingActive = true;
    startBtn.style.display = 'none';
    stopBtn.style.display = 'inline-block';
    
    let phase = 'inhale';
    let count = 0;
    
    function breathingCycle() {
        if (!breathingActive) return;
        
        if (phase === 'inhale') {
            text.textContent = 'Breathe In...';
            circle.classList.remove('exhale');
            circle.classList.add('inhale');
            phase = 'exhale';
        } else {
            text.textContent = 'Breathe Out...';
            circle.classList.remove('inhale');
            circle.classList.add('exhale');
            phase = 'inhale';
            count++;
        }
        
        if (count >= 10) { // 5 minutes (30 breaths)
            stopBreathingCycle();
            text.textContent = 'Great job! 🎉';
            return;
        }
        
        setTimeout(breathingCycle, 4000); // 4 seconds per phase
    }
    
    breathingCycle();
}

function stopBreathingCycle() {
    breathingActive = false;
    const circle = document.getElementById('breathingCircle');
    const text = document.getElementById('breathingText');
    const startBtn = document.getElementById('breathingStartBtn');
    const stopBtn = document.getElementById('breathingStopBtn');
    
    circle.classList.remove('inhale', 'exhale');
    text.textContent = 'Get Ready';
    startBtn.style.display = 'inline-block';
    stopBtn.style.display = 'none';
}

function startMeditation() {
    alert('Guided meditation feature will connect to meditation content library in full version.');
}

function openGratitudeJournal() {
    const notes = document.getElementById('notes');
    notes.value = 'Today I am grateful for:\n1. \n2. \n3. ';
    notes.focus();
    document.querySelector('.mood-logging-section').scrollIntoView({ behavior: 'smooth' });
}

function startRelaxation() {
    alert('Progressive relaxation guide will be available in the full version with audio guidance.');
}

// Initialize mood display
document.addEventListener('DOMContentLoaded', function() {
    updateMoodDisplay(5);
    updateEnergyDisplay(5);
    updateAnxietyDisplay(3);
});
</script>

<?php require_once '../includes/footer.php'; ?>