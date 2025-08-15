<?php
/**
 * Care4Mom - AI Health Coach & Insights
 * Smart health analysis, alerts, and personalized recommendations
 * Author: Care4Mom Development Team
 * Version: 1.0
 */

$page_title = 'AI Health Coach';
$body_class = 'module-page ai-page';

require_once '../includes/header.php';
requireLogin();

$current_user = getCurrentUser();
$success_message = '';
$errors = [];

// Handle alert acknowledgment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token. Please try again.');
        }
        
        if ($_POST['action'] === 'acknowledge_alert') {
            $alert_id = intval($_POST['alert_id'] ?? 0);
            
            $stmt = $pdo->prepare("
                UPDATE ai_alerts 
                SET acknowledged = 1, acknowledged_at = NOW() 
                WHERE id = ? AND user_id = ?
            ");
            
            if ($stmt->execute([$alert_id, $current_user['id']])) {
                $success_message = 'Alert acknowledged successfully!';
                logActivity('alert_acknowledged', "Acknowledged alert ID: $alert_id", $current_user['id']);
            } else {
                $errors[] = 'Failed to acknowledge alert. Please try again.';
            }
        }
        
        elseif ($_POST['action'] === 'dismiss_all_alerts') {
            $stmt = $pdo->prepare("
                UPDATE ai_alerts 
                SET acknowledged = 1, acknowledged_at = NOW() 
                WHERE user_id = ? AND acknowledged = 0
            ");
            
            if ($stmt->execute([$current_user['id']])) {
                $success_message = 'All alerts dismissed successfully!';
                logActivity('all_alerts_dismissed', 'Dismissed all pending alerts', $current_user['id']);
            } else {
                $errors[] = 'Failed to dismiss alerts. Please try again.';
            }
        }
    } catch (Exception $e) {
        logError('ai_module', $e->getMessage(), __FILE__, __LINE__);
        $errors[] = 'An error occurred. Please try again.';
    }
}

try {
    // Get pending alerts
    $pending_alerts_stmt = $pdo->prepare("
        SELECT * FROM ai_alerts 
        WHERE user_id = ? AND acknowledged = 0 
        ORDER BY severity DESC, created_at DESC
    ");
    $pending_alerts_stmt->execute([$current_user['id']]);
    $pending_alerts = $pending_alerts_stmt->fetchAll();
    
    // Get recent acknowledged alerts
    $recent_alerts_stmt = $pdo->prepare("
        SELECT * FROM ai_alerts 
        WHERE user_id = ? AND acknowledged = 1 
        ORDER BY acknowledged_at DESC 
        LIMIT 10
    ");
    $recent_alerts_stmt->execute([$current_user['id']]);
    $recent_alerts = $recent_alerts_stmt->fetchAll();
    
    // Get health insights data
    $insights_stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM symptoms WHERE user_id = ? AND logged_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as symptoms_week,
            (SELECT COUNT(*) FROM symptoms WHERE user_id = ? AND severity >= 7 AND logged_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as high_severity_symptoms,
            (SELECT AVG(severity) FROM symptoms WHERE user_id = ? AND logged_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as avg_severity,
            (SELECT COUNT(*) FROM medications WHERE user_id = ? AND taken = 1 AND DATE(taken_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as meds_taken,
            (SELECT COUNT(*) FROM medications WHERE user_id = ? AND DATE(taken_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as total_meds,
            (SELECT AVG(mood_score) FROM mood_logs WHERE user_id = ? AND logged_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as avg_mood,
            (SELECT COUNT(*) FROM vitals WHERE user_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as vitals_entries
    ");
    $insights_stmt->execute([
        $current_user['id'], $current_user['id'], $current_user['id'], 
        $current_user['id'], $current_user['id'], $current_user['id'], $current_user['id']
    ]);
    $health_insights = $insights_stmt->fetch();
    
    // Calculate medication compliance
    $compliance_rate = $health_insights['total_meds'] > 0 
        ? round(($health_insights['meds_taken'] / $health_insights['total_meds']) * 100) 
        : 0;
    
    // Get symptom patterns
    $pattern_stmt = $pdo->prepare("
        SELECT 
            symptom_name,
            COUNT(*) as frequency,
            AVG(severity) as avg_severity,
            DATE(logged_at) as log_date
        FROM symptoms 
        WHERE user_id = ? AND logged_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY symptom_name, DATE(logged_at)
        ORDER BY log_date DESC, frequency DESC
        LIMIT 20
    ");
    $pattern_stmt->execute([$current_user['id']]);
    $symptom_patterns = $pattern_stmt->fetchAll();
    
    // Get correlations (simplified analysis)
    $correlations_stmt = $pdo->prepare("
        SELECT 
            s.symptom_name,
            s.severity,
            s.logged_at as symptom_date,
            m.mood_score,
            m.logged_at as mood_date,
            DATEDIFF(s.logged_at, m.logged_at) as day_diff
        FROM symptoms s
        LEFT JOIN mood_logs m ON DATE(s.logged_at) = DATE(m.logged_at) AND s.user_id = m.user_id
        WHERE s.user_id = ? AND s.logged_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
        ORDER BY s.logged_at DESC
        LIMIT 10
    ");
    $correlations_stmt->execute([$current_user['id']]);
    $correlations = $correlations_stmt->fetchAll();
    
} catch (Exception $e) {
    logError('ai_module', 'Failed to load AI data: ' . $e->getMessage(), __FILE__, __LINE__);
    $pending_alerts = [];
    $recent_alerts = [];
    $health_insights = [];
    $symptom_patterns = [];
    $correlations = [];
    $compliance_rate = 0;
}

// Generate AI recommendations based on data
function generateRecommendations($insights, $compliance_rate, $patterns) {
    $recommendations = [];
    
    if ($compliance_rate < 80) {
        $recommendations[] = [
            'type' => 'medication',
            'priority' => 'high',
            'title' => 'Improve Medication Compliance',
            'message' => "Your medication compliance is {$compliance_rate}%. Consider setting reminders or using a pill organizer to improve adherence.",
            'action' => 'Set up medication reminders'
        ];
    }
    
    if ($insights['avg_severity'] && $insights['avg_severity'] > 6) {
        $recommendations[] = [
            'type' => 'symptom',
            'priority' => 'high',
            'title' => 'High Symptom Severity',
            'message' => "Your average symptom severity this week is " . number_format($insights['avg_severity'], 1) . "/10. Consider discussing pain management with your healthcare team.",
            'action' => 'Contact your doctor'
        ];
    }
    
    if ($insights['avg_mood'] && $insights['avg_mood'] < 5) {
        $recommendations[] = [
            'type' => 'mood',
            'priority' => 'medium',
            'title' => 'Mental Health Support',
            'message' => "Your mood has been lower than usual. Consider practicing mindfulness exercises or reaching out to a counselor.",
            'action' => 'Try mindfulness activities'
        ];
    }
    
    if ($insights['symptoms_week'] > 10) {
        $recommendations[] = [
            'type' => 'wellness',
            'priority' => 'medium',
            'title' => 'Frequent Symptoms',
            'message' => "You've logged {$insights['symptoms_week']} symptoms this week. Consider tracking triggers and discussing patterns with your doctor.",
            'action' => 'Review symptom patterns'
        ];
    }
    
    if ($insights['vitals_entries'] == 0) {
        $recommendations[] = [
            'type' => 'vitals',
            'priority' => 'low',
            'title' => 'Track Your Vitals',
            'message' => "Regular vital sign monitoring can help detect health changes early. Consider recording your vitals weekly.",
            'action' => 'Record vitals'
        ];
    }
    
    return $recommendations;
}

$ai_recommendations = generateRecommendations($health_insights, $compliance_rate, $symptom_patterns);
?>

<div class="module-container">
    <!-- Module Header -->
    <div class="module-header">
        <div class="header-content">
            <h1 class="module-title">
                <span class="module-icon">🤖</span>
                AI Health Coach
            </h1>
            <p class="module-description">
                Get personalized health insights, smart alerts, and AI-powered recommendations based on your health data.
            </p>
        </div>
        <div class="header-actions">
            <?php if (!empty($pending_alerts)): ?>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="dismiss_all_alerts">
                <button type="submit" class="btn btn-secondary">
                    <span class="btn-icon">✅</span>
                    <span class="btn-text">Dismiss All Alerts</span>
                </button>
            </form>
            <?php endif; ?>
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
    
    <!-- Health Score Dashboard -->
    <div class="health-score-section">
        <h2 class="section-title">Weekly Health Overview</h2>
        <div class="health-score-grid">
            <div class="score-card medication">
                <div class="score-icon">💊</div>
                <h3 class="score-title">Medication Compliance</h3>
                <div class="score-value">
                    <span class="score-number"><?php echo $compliance_rate; ?>%</span>
                    <div class="score-bar">
                        <div class="score-fill medication-fill" style="width: <?php echo $compliance_rate; ?>%"></div>
                    </div>
                </div>
                <div class="score-status <?php echo $compliance_rate >= 80 ? 'good' : ($compliance_rate >= 60 ? 'fair' : 'poor'); ?>">
                    <?php echo $compliance_rate >= 80 ? 'Excellent' : ($compliance_rate >= 60 ? 'Good' : 'Needs Improvement'); ?>
                </div>
            </div>
            
            <div class="score-card symptoms">
                <div class="score-icon">📊</div>
                <h3 class="score-title">Symptom Management</h3>
                <div class="score-value">
                    <span class="score-number"><?php echo $health_insights['symptoms_week'] ?? 0; ?></span>
                    <span class="score-unit">symptoms logged</span>
                </div>
                <div class="score-status <?php echo ($health_insights['avg_severity'] ?? 5) <= 5 ? 'good' : 'attention'; ?>">
                    Avg severity: <?php echo number_format($health_insights['avg_severity'] ?? 0, 1); ?>/10
                </div>
            </div>
            
            <div class="score-card mood">
                <div class="score-icon">😊</div>
                <h3 class="score-title">Mental Wellness</h3>
                <div class="score-value">
                    <span class="score-number"><?php echo number_format($health_insights['avg_mood'] ?? 5, 1); ?></span>
                    <span class="score-unit">/10 average mood</span>
                </div>
                <div class="score-status <?php echo ($health_insights['avg_mood'] ?? 5) >= 6 ? 'good' : 'attention'; ?>">
                    <?php echo ($health_insights['avg_mood'] ?? 5) >= 6 ? 'Positive' : 'Monitor Closely'; ?>
                </div>
            </div>
            
            <div class="score-card vitals">
                <div class="score-icon">❤️</div>
                <h3 class="score-title">Vitals Tracking</h3>
                <div class="score-value">
                    <span class="score-number"><?php echo $health_insights['vitals_entries'] ?? 0; ?></span>
                    <span class="score-unit">entries this week</span>
                </div>
                <div class="score-status <?php echo ($health_insights['vitals_entries'] ?? 0) > 0 ? 'good' : 'inactive'; ?>">
                    <?php echo ($health_insights['vitals_entries'] ?? 0) > 0 ? 'Active' : 'Start Tracking'; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pending Alerts -->
    <?php if (!empty($pending_alerts)): ?>
    <div class="alerts-section">
        <h2 class="section-title">
            🚨 Health Alerts 
            <span class="alert-count"><?php echo count($pending_alerts); ?> pending</span>
        </h2>
        <div class="alerts-list">
            <?php foreach ($pending_alerts as $alert): ?>
                <div class="alert-item severity-<?php echo $alert['severity']; ?>">
                    <div class="alert-header">
                        <div class="alert-type-icon">
                            <?php 
                            $icons = [
                                'warning' => '⚠️',
                                'advice' => '💡',
                                'reminder' => '🔔',
                                'emergency' => '🚨'
                            ];
                            echo $icons[$alert['alert_type']] ?? '💡';
                            ?>
                        </div>
                        <h3 class="alert-title"><?php echo htmlspecialchars($alert['title']); ?></h3>
                        <span class="alert-severity severity-<?php echo $alert['severity']; ?>">
                            <?php echo ucfirst($alert['severity']); ?>
                        </span>
                    </div>
                    <div class="alert-content">
                        <p class="alert-message"><?php echo htmlspecialchars($alert['message']); ?></p>
                        <?php if (!empty($alert['recommendation'])): ?>
                            <div class="alert-recommendation">
                                <strong>Recommendation:</strong> <?php echo htmlspecialchars($alert['recommendation']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="alert-footer">
                        <span class="alert-time">
                            <?php echo date('M j, Y g:i A', strtotime($alert['created_at'])); ?>
                        </span>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="acknowledge_alert">
                            <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline">Acknowledge</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- AI Recommendations -->
    <?php if (!empty($ai_recommendations)): ?>
    <div class="recommendations-section">
        <h2 class="section-title">🤖 AI Recommendations</h2>
        <div class="recommendations-grid">
            <?php foreach ($ai_recommendations as $rec): ?>
                <div class="recommendation-card priority-<?php echo $rec['priority']; ?>">
                    <div class="rec-header">
                        <div class="rec-icon">
                            <?php 
                            $type_icons = [
                                'medication' => '💊',
                                'symptom' => '📊',
                                'mood' => '😊',
                                'wellness' => '🌟',
                                'vitals' => '❤️'
                            ];
                            echo $type_icons[$rec['type']] ?? '💡';
                            ?>
                        </div>
                        <h3 class="rec-title"><?php echo htmlspecialchars($rec['title']); ?></h3>
                        <span class="rec-priority priority-<?php echo $rec['priority']; ?>">
                            <?php echo ucfirst($rec['priority']); ?>
                        </span>
                    </div>
                    <div class="rec-content">
                        <p class="rec-message"><?php echo htmlspecialchars($rec['message']); ?></p>
                        <button onclick="takeRecommendedAction('<?php echo $rec['type']; ?>')" class="btn btn-sm btn-primary">
                            <?php echo htmlspecialchars($rec['action']); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Health Insights -->
    <div class="insights-section">
        <h2 class="section-title">📈 Health Pattern Analysis</h2>
        
        <!-- Symptom Patterns -->
        <?php if (!empty($symptom_patterns)): ?>
        <div class="pattern-analysis">
            <h3 class="subsection-title">Recent Symptom Patterns</h3>
            <div class="pattern-list">
                <?php 
                $grouped_patterns = [];
                foreach ($symptom_patterns as $pattern) {
                    $grouped_patterns[$pattern['symptom_name']][] = $pattern;
                }
                ?>
                <?php foreach ($grouped_patterns as $symptom => $data): ?>
                    <div class="pattern-item">
                        <div class="pattern-header">
                            <h4 class="pattern-symptom"><?php echo htmlspecialchars($symptom); ?></h4>
                            <span class="pattern-frequency"><?php echo count($data); ?> occurrences</span>
                        </div>
                        <div class="pattern-details">
                            <span class="pattern-severity">
                                Avg severity: <?php echo number_format(array_sum(array_column($data, 'avg_severity')) / count($data), 1); ?>/10
                            </span>
                            <span class="pattern-trend">
                                <?php 
                                $trend = count($data) > 5 ? 'increasing' : (count($data) > 2 ? 'stable' : 'occasional');
                                echo ucfirst($trend);
                                ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Mood-Symptom Correlations -->
        <?php if (!empty($correlations)): ?>
        <div class="correlation-analysis">
            <h3 class="subsection-title">Mood & Symptom Correlations</h3>
            <div class="correlation-list">
                <?php foreach (array_slice($correlations, 0, 5) as $corr): ?>
                    <?php if ($corr['mood_score']): ?>
                    <div class="correlation-item">
                        <div class="correlation-data">
                            <span class="symptom-info">
                                <?php echo htmlspecialchars($corr['symptom_name']); ?> 
                                (severity: <?php echo $corr['severity']; ?>/10)
                            </span>
                            <span class="mood-info">
                                Mood: <?php echo $corr['mood_score']; ?>/10
                            </span>
                        </div>
                        <div class="correlation-insight">
                            <?php 
                            if ($corr['mood_score'] <= 5 && $corr['severity'] >= 6) {
                                echo "Lower mood may correlate with higher symptom severity";
                            } elseif ($corr['mood_score'] >= 7 && $corr['severity'] <= 4) {
                                echo "Better mood appears to correlate with lower symptoms";
                            } else {
                                echo "Normal correlation between mood and symptoms";
                            }
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Acknowledged Alerts -->
    <?php if (!empty($recent_alerts)): ?>
    <div class="history-section">
        <h2 class="section-title">Recent Alert History</h2>
        <div class="history-list">
            <?php foreach ($recent_alerts as $alert): ?>
                <div class="history-item">
                    <div class="history-header">
                        <span class="history-type">
                            <?php 
                            $icons = [
                                'warning' => '⚠️',
                                'advice' => '💡',
                                'reminder' => '🔔',
                                'emergency' => '🚨'
                            ];
                            echo $icons[$alert['alert_type']] ?? '💡';
                            ?>
                            <?php echo htmlspecialchars($alert['title']); ?>
                        </span>
                        <span class="history-time">
                            <?php echo date('M j, g:i A', strtotime($alert['acknowledged_at'])); ?>
                        </span>
                    </div>
                    <div class="history-message">
                        <?php echo htmlspecialchars($alert['message']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- AI Coach Info -->
    <div class="ai-info-section">
        <div class="ai-info-card">
            <div class="ai-avatar">🤖</div>
            <div class="ai-info-content">
                <h3 class="ai-info-title">About Your AI Health Coach</h3>
                <p class="ai-info-description">
                    Your AI Health Coach analyzes patterns in your symptoms, medications, mood, and vitals to provide 
                    personalized insights and early warnings. The AI learns from your data to offer increasingly 
                    relevant recommendations for your health journey.
                </p>
                <div class="ai-features">
                    <span class="ai-feature">📊 Pattern Recognition</span>
                    <span class="ai-feature">⚠️ Early Warning System</span>
                    <span class="ai-feature">💡 Personalized Recommendations</span>
                    <span class="ai-feature">🔍 Health Correlations</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* AI Module Styles */
.health-score-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

.score-card {
    background: white;
    border-radius: var(--radius-xl);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-lg);
    text-align: center;
    border: 1px solid var(--gray-200);
    transition: all var(--transition-normal);
}

.score-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-xl);
}

.score-icon {
    font-size: 2.5rem;
    margin-bottom: var(--spacing-sm);
}

.score-title {
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: var(--spacing-md);
}

.score-value {
    margin-bottom: var(--spacing-sm);
}

.score-number {
    font-size: var(--font-size-2xl);
    font-weight: 700;
    color: var(--primary-color);
}

.score-unit {
    font-size: var(--font-size-sm);
    color: var(--gray-600);
    display: block;
}

.score-bar {
    width: 100%;
    height: 8px;
    background: var(--gray-200);
    border-radius: var(--radius-full);
    overflow: hidden;
    margin: var(--spacing-sm) 0;
}

.score-fill {
    height: 100%;
    border-radius: var(--radius-full);
    transition: width var(--transition-normal);
}

.medication-fill {
    background: linear-gradient(to right, var(--danger-color), var(--warning-color), var(--success-color));
}

.score-status {
    font-size: var(--font-size-sm);
    font-weight: 500;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-full);
}

.score-status.good {
    background: #d1fae5;
    color: #065f46;
}

.score-status.fair {
    background: #fef3c7;
    color: #92400e;
}

.score-status.poor, .score-status.attention {
    background: #fee2e2;
    color: #991b1b;
}

.score-status.inactive {
    background: var(--gray-100);
    color: var(--gray-600);
}

/* Alert Styles */
.alert-count {
    background: var(--danger-color);
    color: white;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-full);
    font-size: var(--font-size-sm);
    font-weight: 500;
    margin-left: var(--spacing-sm);
}

.alerts-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-xl);
}

.alert-item {
    background: white;
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-md);
    border-left: 4px solid var(--gray-300);
}

.alert-item.severity-critical {
    border-left-color: var(--danger-color);
    background: #fef2f2;
}

.alert-item.severity-high {
    border-left-color: #f97316;
    background: #fff7ed;
}

.alert-item.severity-medium {
    border-left-color: var(--warning-color);
    background: #fefbf3;
}

.alert-item.severity-low {
    border-left-color: var(--info-color);
    background: #eff6ff;
}

.alert-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-sm);
}

.alert-type-icon {
    font-size: var(--font-size-xl);
}

.alert-title {
    flex: 1;
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--gray-800);
}

.alert-severity {
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-full);
    font-size: var(--font-size-xs);
    font-weight: 600;
    text-transform: uppercase;
}

.alert-severity.severity-critical {
    background: var(--danger-color);
    color: white;
}

.alert-severity.severity-high {
    background: #f97316;
    color: white;
}

.alert-severity.severity-medium {
    background: var(--warning-color);
    color: white;
}

.alert-severity.severity-low {
    background: var(--info-color);
    color: white;
}

.alert-recommendation {
    background: rgba(99, 102, 241, 0.1);
    border-radius: var(--radius-md);
    padding: var(--spacing-sm);
    margin-top: var(--spacing-sm);
    color: var(--primary-dark);
}

.alert-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: var(--spacing-md);
    padding-top: var(--spacing-sm);
    border-top: 1px solid var(--gray-200);
}

.alert-time {
    font-size: var(--font-size-sm);
    color: var(--gray-500);
}

/* Recommendations */
.recommendations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

.recommendation-card {
    background: white;
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--gray-200);
    transition: all var(--transition-normal);
}

.recommendation-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.recommendation-card.priority-high {
    border-left: 4px solid var(--danger-color);
}

.recommendation-card.priority-medium {
    border-left: 4px solid var(--warning-color);
}

.recommendation-card.priority-low {
    border-left: 4px solid var(--info-color);
}

.rec-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-md);
}

.rec-icon {
    font-size: var(--font-size-xl);
}

.rec-title {
    flex: 1;
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--gray-800);
}

.rec-priority {
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-full);
    font-size: var(--font-size-xs);
    font-weight: 500;
    text-transform: uppercase;
}

.rec-priority.priority-high {
    background: #fee2e2;
    color: #991b1b;
}

.rec-priority.priority-medium {
    background: #fef3c7;
    color: #92400e;
}

.rec-priority.priority-low {
    background: #dbeafe;
    color: #1e40af;
}

/* Pattern Analysis */
.pattern-list, .correlation-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.pattern-item, .correlation-item {
    background: white;
    border-radius: var(--radius-lg);
    padding: var(--spacing-md);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
}

.pattern-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-sm);
}

.pattern-symptom {
    font-weight: 600;
    color: var(--gray-800);
}

.pattern-frequency {
    background: var(--primary-color);
    color: white;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-full);
    font-size: var(--font-size-xs);
    font-weight: 500;
}

.pattern-details {
    display: flex;
    gap: var(--spacing-md);
    font-size: var(--font-size-sm);
    color: var(--gray-600);
}

/* AI Info */
.ai-info-card {
    background: var(--gradient-primary);
    border-radius: var(--radius-xl);
    padding: var(--spacing-xl);
    color: white;
    display: flex;
    gap: var(--spacing-lg);
    align-items: center;
}

.ai-avatar {
    font-size: 4rem;
    flex-shrink: 0;
}

.ai-info-title {
    font-size: var(--font-size-xl);
    font-weight: 600;
    margin-bottom: var(--spacing-sm);
}

.ai-info-description {
    margin-bottom: var(--spacing-md);
    line-height: 1.6;
    opacity: 0.95;
}

.ai-features {
    display: flex;
    gap: var(--spacing-sm);
    flex-wrap: wrap;
}

.ai-feature {
    background: rgba(255, 255, 255, 0.2);
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-md);
    font-size: var(--font-size-sm);
    font-weight: 500;
}

/* Responsive Design */
@media (max-width: 768px) {
    .health-score-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .recommendations-grid {
        grid-template-columns: 1fr;
    }
    
    .alert-header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-xs);
    }
    
    .alert-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-sm);
    }
    
    .ai-info-card {
        flex-direction: column;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .health-score-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function takeRecommendedAction(type) {
    const actions = {
        'medication': '../modules/med.php',
        'symptom': '../modules/symptom.php',
        'mood': '../modules/mood.php',
        'wellness': '../modules/wellness.php',
        'vitals': '../modules/vitals.php'
    };
    
    if (actions[type]) {
        window.location.href = actions[type];
    } else {
        alert('This feature will be implemented in the full version.');
    }
}

// Auto-refresh alerts every 5 minutes
setInterval(function() {
    // In a real implementation, this would use AJAX to refresh alerts
    // For demo purposes, we'll just show a notification
    console.log('AI Coach: Checking for new health alerts...');
}, 5 * 60 * 1000);
</script>

<?php require_once '../includes/footer.php'; ?>