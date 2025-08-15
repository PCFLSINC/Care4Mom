<?php
/**
 * Care4Mom - Doctor Reports & Export
 * Generate comprehensive health reports for medical appointments
 * Author: Care4Mom Development Team
 * Version: 1.0
 */

$page_title = 'Doctor Reports';
$body_class = 'module-page reports-page';

require_once '../includes/header.php';
requireLogin();

$current_user = getCurrentUser();
$success_message = '';
$errors = [];

// Handle report generation and export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token. Please try again.');
        }
        
        if ($_POST['action'] === 'generate_report') {
            $report_type = sanitizeInput($_POST['report_type'] ?? '');
            $date_range = sanitizeInput($_POST['date_range'] ?? '7');
            $include_sections = $_POST['include_sections'] ?? [];
            $export_format = sanitizeInput($_POST['export_format'] ?? 'json');
            
            // Validation
            if (empty($report_type)) $errors[] = 'Report type is required';
            if (!in_array($date_range, ['7', '14', '30', '90'])) $errors[] = 'Invalid date range';
            if (!in_array($export_format, ['json', 'csv', 'pdf'])) $errors[] = 'Invalid export format';
            
            if (empty($errors)) {
                // Generate report data
                $report_data = generateHealthReport($current_user['id'], $report_type, $date_range, $include_sections, $pdo);
                
                if ($export_format === 'json') {
                    downloadJSONReport($report_data, $current_user, $report_type, $date_range);
                } elseif ($export_format === 'csv') {
                    downloadCSVReport($report_data, $current_user, $report_type, $date_range);
                } else {
                    // PDF export would require additional library like TCPDF
                    $errors[] = 'PDF export feature coming soon. Please use JSON or CSV format.';
                }
                
                logActivity('report_generated', "Generated $report_type report ($date_range days, $export_format)", $current_user['id']);
            }
        }
    } catch (Exception $e) {
        logError('reports_module', $e->getMessage(), __FILE__, __LINE__);
        $errors[] = 'An error occurred while generating the report. Please try again.';
    }
}

try {
    // Get summary data for report preview
    $summary_stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM symptoms WHERE user_id = ? AND logged_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as symptoms_30d,
            (SELECT COUNT(*) FROM medications WHERE user_id = ? AND taken_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as medications_30d,
            (SELECT COUNT(*) FROM vitals WHERE user_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as vitals_30d,
            (SELECT COUNT(*) FROM mood_logs WHERE user_id = ? AND logged_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as mood_30d,
            (SELECT AVG(severity) FROM symptoms WHERE user_id = ? AND logged_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as avg_severity,
            (SELECT AVG(mood_score) FROM mood_logs WHERE user_id = ? AND logged_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as avg_mood
    ");
    $summary_stmt->execute([
        $current_user['id'], $current_user['id'], $current_user['id'], 
        $current_user['id'], $current_user['id'], $current_user['id']
    ]);
    $summary_data = $summary_stmt->fetch();
    
    // Get recent data for preview
    $recent_symptoms_stmt = $pdo->prepare("
        SELECT symptom_name, severity, logged_at 
        FROM symptoms 
        WHERE user_id = ? 
        ORDER BY logged_at DESC 
        LIMIT 5
    ");
    $recent_symptoms_stmt->execute([$current_user['id']]);
    $recent_symptoms = $recent_symptoms_stmt->fetchAll();
    
    $medication_compliance_stmt = $pdo->prepare("
        SELECT 
            medication_name,
            COUNT(*) as total_doses,
            SUM(CASE WHEN taken = 1 THEN 1 ELSE 0 END) as taken_doses,
            (SUM(CASE WHEN taken = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as compliance_rate
        FROM medications 
        WHERE user_id = ? AND taken_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY medication_name
        ORDER BY compliance_rate DESC
    ");
    $medication_compliance_stmt->execute([$current_user['id']]);
    $medication_compliance = $medication_compliance_stmt->fetchAll();
    
} catch (Exception $e) {
    logError('reports_module', 'Failed to load report data: ' . $e->getMessage(), __FILE__, __LINE__);
    $summary_data = [];
    $recent_symptoms = [];
    $medication_compliance = [];
}

// Report generation function
function generateHealthReport($user_id, $report_type, $days, $sections, $pdo) {
    $report = [
        'metadata' => [
            'generated_at' => date('Y-m-d H:i:s'),
            'report_type' => $report_type,
            'date_range_days' => $days,
            'user_id' => $user_id
        ],
        'data' => []
    ];
    
    $date_condition = "AND logged_at >= DATE_SUB(NOW(), INTERVAL $days DAY)";
    
    // Patient information
    $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user_data = $user_stmt->fetch();
    
    $report['patient_info'] = [
        'name' => $user_data['first_name'] . ' ' . $user_data['last_name'],
        'email' => $user_data['email'],
        'phone' => $user_data['phone'],
        'emergency_contact' => $user_data['emergency_contact'],
        'emergency_phone' => $user_data['emergency_phone'],
        'doctor_name' => $user_data['doctor_name'],
        'doctor_phone' => $user_data['doctor_phone']
    ];
    
    // Symptoms data
    if (empty($sections) || in_array('symptoms', $sections)) {
        $symptoms_stmt = $pdo->prepare("
            SELECT symptom_name, severity, notes, logged_at 
            FROM symptoms 
            WHERE user_id = ? $date_condition 
            ORDER BY logged_at DESC
        ");
        $symptoms_stmt->execute([$user_id]);
        $report['data']['symptoms'] = $symptoms_stmt->fetchAll();
        
        // Symptom summary
        $symptoms_summary_stmt = $pdo->prepare("
            SELECT 
                symptom_name,
                COUNT(*) as frequency,
                AVG(severity) as avg_severity,
                MAX(severity) as max_severity,
                MIN(severity) as min_severity
            FROM symptoms 
            WHERE user_id = ? $date_condition 
            GROUP BY symptom_name 
            ORDER BY frequency DESC
        ");
        $symptoms_summary_stmt->execute([$user_id]);
        $report['data']['symptom_summary'] = $symptoms_summary_stmt->fetchAll();
    }
    
    // Medications data
    if (empty($sections) || in_array('medications', $sections)) {
        $medications_stmt = $pdo->prepare("
            SELECT medication_name, dosage, taken, taken_at, notes, side_effects 
            FROM medications 
            WHERE user_id = ? AND taken_at >= DATE_SUB(NOW(), INTERVAL $days DAY) 
            ORDER BY taken_at DESC
        ");
        $medications_stmt->execute([$user_id]);
        $report['data']['medications'] = $medications_stmt->fetchAll();
        
        // Medication compliance
        $compliance_stmt = $pdo->prepare("
            SELECT 
                medication_name,
                COUNT(*) as total_doses,
                SUM(CASE WHEN taken = 1 THEN 1 ELSE 0 END) as taken_doses,
                (SUM(CASE WHEN taken = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as compliance_rate
            FROM medications 
            WHERE user_id = ? AND taken_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
            GROUP BY medication_name
        ");
        $compliance_stmt->execute([$user_id]);
        $report['data']['medication_compliance'] = $compliance_stmt->fetchAll();
    }
    
    // Vitals data
    if (empty($sections) || in_array('vitals', $sections)) {
        $vitals_stmt = $pdo->prepare("
            SELECT * FROM vitals 
            WHERE user_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL $days DAY) 
            ORDER BY recorded_at DESC
        ");
        $vitals_stmt->execute([$user_id]);
        $report['data']['vitals'] = $vitals_stmt->fetchAll();
    }
    
    // Mood data
    if (empty($sections) || in_array('mood', $sections)) {
        $mood_stmt = $pdo->prepare("
            SELECT * FROM mood_logs 
            WHERE user_id = ? $date_condition 
            ORDER BY logged_at DESC
        ");
        $mood_stmt->execute([$user_id]);
        $report['data']['mood_logs'] = $mood_stmt->fetchAll();
    }
    
    // AI alerts
    if (empty($sections) || in_array('alerts', $sections)) {
        $alerts_stmt = $pdo->prepare("
            SELECT * FROM ai_alerts 
            WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) 
            ORDER BY created_at DESC
        ");
        $alerts_stmt->execute([$user_id]);
        $report['data']['ai_alerts'] = $alerts_stmt->fetchAll();
    }
    
    return $report;
}

// Export functions
function downloadJSONReport($data, $user, $type, $days) {
    $filename = "care4mom_report_{$user['first_name']}_{$user['last_name']}_{$type}_{$days}days_" . date('Y-m-d') . ".json";
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit();
}

function downloadCSVReport($data, $user, $type, $days) {
    $filename = "care4mom_report_{$user['first_name']}_{$user['last_name']}_{$type}_{$days}days_" . date('Y-m-d') . ".csv";
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    $output = fopen('php://output', 'w');
    
    // Patient header
    fputcsv($output, ['Care4Mom Health Report']);
    fputcsv($output, ['Patient', $data['patient_info']['name']]);
    fputcsv($output, ['Generated', $data['metadata']['generated_at']]);
    fputcsv($output, ['Period', $data['metadata']['date_range_days'] . ' days']);
    fputcsv($output, []);
    
    // Symptoms
    if (isset($data['data']['symptoms'])) {
        fputcsv($output, ['SYMPTOMS']);
        fputcsv($output, ['Date/Time', 'Symptom', 'Severity (1-10)', 'Notes']);
        foreach ($data['data']['symptoms'] as $symptom) {
            fputcsv($output, [
                $symptom['logged_at'],
                $symptom['symptom_name'],
                $symptom['severity'],
                $symptom['notes']
            ]);
        }
        fputcsv($output, []);
    }
    
    // Medications
    if (isset($data['data']['medications'])) {
        fputcsv($output, ['MEDICATIONS']);
        fputcsv($output, ['Date/Time', 'Medication', 'Dosage', 'Taken', 'Side Effects']);
        foreach ($data['data']['medications'] as $med) {
            fputcsv($output, [
                $med['taken_at'],
                $med['medication_name'],
                $med['dosage'],
                $med['taken'] ? 'Yes' : 'No',
                $med['side_effects']
            ]);
        }
        fputcsv($output, []);
    }
    
    // Add other sections as needed...
    
    fclose($output);
    exit();
}
?>

<div class="module-container">
    <!-- Module Header -->
    <div class="module-header">
        <div class="header-content">
            <h1 class="module-title">
                <span class="module-icon">📋</span>
                Doctor Reports & Export
            </h1>
            <p class="module-description">
                Generate comprehensive health reports for your medical appointments. Export your data in multiple formats 
                to share with your healthcare team.
            </p>
        </div>
        <div class="header-actions">
            <button onclick="openQuickReportModal()" class="btn btn-primary btn-large">
                <span class="btn-icon">📊</span>
                <span class="btn-text">Generate Report</span>
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
    
    <!-- Data Overview -->
    <div class="data-overview">
        <h2 class="section-title">Available Data (Last 30 Days)</h2>
        <div class="overview-stats">
            <div class="stat-item">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo $summary_data['symptoms_30d'] ?? 0; ?></h3>
                    <p class="stat-label">Symptom Entries</p>
                </div>
            </div>
            
            <div class="stat-item">
                <div class="stat-icon">💊</div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo $summary_data['medications_30d'] ?? 0; ?></h3>
                    <p class="stat-label">Medication Records</p>
                </div>
            </div>
            
            <div class="stat-item">
                <div class="stat-icon">❤️</div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo $summary_data['vitals_30d'] ?? 0; ?></h3>
                    <p class="stat-label">Vital Sign Readings</p>
                </div>
            </div>
            
            <div class="stat-item">
                <div class="stat-icon">😊</div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo $summary_data['mood_30d'] ?? 0; ?></h3>
                    <p class="stat-label">Mood Entries</p>
                </div>
            </div>
        </div>
        
        <?php if ($summary_data): ?>
        <div class="key-insights">
            <h3 class="insights-title">Key Health Insights</h3>
            <div class="insights-grid">
                <?php if ($summary_data['avg_severity']): ?>
                <div class="insight-card">
                    <div class="insight-icon">📈</div>
                    <div class="insight-content">
                        <h4 class="insight-title">Average Symptom Severity</h4>
                        <p class="insight-value"><?php echo number_format($summary_data['avg_severity'], 1); ?>/10</p>
                        <p class="insight-description">
                            <?php echo $summary_data['avg_severity'] <= 4 ? 'Well managed' : ($summary_data['avg_severity'] <= 7 ? 'Moderate levels' : 'Needs attention'); ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($summary_data['avg_mood']): ?>
                <div class="insight-card">
                    <div class="insight-icon">💭</div>
                    <div class="insight-content">
                        <h4 class="insight-title">Average Mood Score</h4>
                        <p class="insight-value"><?php echo number_format($summary_data['avg_mood'], 1); ?>/10</p>
                        <p class="insight-description">
                            <?php echo $summary_data['avg_mood'] >= 7 ? 'Positive outlook' : ($summary_data['avg_mood'] >= 5 ? 'Stable mood' : 'May need support'); ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="insight-card">
                    <div class="insight-icon">🎯</div>
                    <div class="insight-content">
                        <h4 class="insight-title">Data Completeness</h4>
                        <p class="insight-value">
                            <?php 
                            $completeness = 0;
                            if ($summary_data['symptoms_30d'] > 0) $completeness += 25;
                            if ($summary_data['medications_30d'] > 0) $completeness += 25;
                            if ($summary_data['vitals_30d'] > 0) $completeness += 25;
                            if ($summary_data['mood_30d'] > 0) $completeness += 25;
                            echo $completeness;
                            ?>%
                        </p>
                        <p class="insight-description">
                            <?php echo $completeness >= 75 ? 'Comprehensive tracking' : ($completeness >= 50 ? 'Good tracking' : 'Consider more tracking'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Report Generation Form -->
    <div class="report-generation">
        <h2 class="section-title">Generate Health Report</h2>
        <form method="POST" class="report-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="generate_report">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="report_type" class="form-label">Report Type</label>
                    <select id="report_type" name="report_type" class="form-select" required>
                        <option value="">Select report type...</option>
                        <option value="comprehensive">Comprehensive Health Report</option>
                        <option value="symptoms_only">Symptoms Only</option>
                        <option value="medications_only">Medications Only</option>
                        <option value="vitals_only">Vitals Only</option>
                        <option value="mood_only">Mood & Wellness Only</option>
                        <option value="doctor_visit">Pre-Visit Summary</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date_range" class="form-label">Date Range</label>
                    <select id="date_range" name="date_range" class="form-select" required>
                        <option value="7">Last 7 days</option>
                        <option value="14">Last 2 weeks</option>
                        <option value="30" selected>Last 30 days</option>
                        <option value="90">Last 3 months</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="export_format" class="form-label">Export Format</label>
                    <select id="export_format" name="export_format" class="form-select" required>
                        <option value="json">JSON (Structured Data)</option>
                        <option value="csv">CSV (Spreadsheet)</option>
                        <option value="pdf">PDF (Coming Soon)</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Include Sections (for comprehensive reports)</label>
                <div class="checkbox-grid">
                    <div class="checkbox-item">
                        <input type="checkbox" id="include_symptoms" name="include_sections[]" value="symptoms" checked>
                        <label for="include_symptoms" class="checkbox-label">
                            <span class="checkbox-icon">📊</span>
                            Symptoms & Severity
                        </label>
                    </div>
                    
                    <div class="checkbox-item">
                        <input type="checkbox" id="include_medications" name="include_sections[]" value="medications" checked>
                        <label for="include_medications" class="checkbox-label">
                            <span class="checkbox-icon">💊</span>
                            Medications & Compliance
                        </label>
                    </div>
                    
                    <div class="checkbox-item">
                        <input type="checkbox" id="include_vitals" name="include_sections[]" value="vitals" checked>
                        <label for="include_vitals" class="checkbox-label">
                            <span class="checkbox-icon">❤️</span>
                            Vital Signs
                        </label>
                    </div>
                    
                    <div class="checkbox-item">
                        <input type="checkbox" id="include_mood" name="include_sections[]" value="mood" checked>
                        <label for="include_mood" class="checkbox-label">
                            <span class="checkbox-icon">😊</span>
                            Mood & Wellness
                        </label>
                    </div>
                    
                    <div class="checkbox-item">
                        <input type="checkbox" id="include_alerts" name="include_sections[]" value="alerts" checked>
                        <label for="include_alerts" class="checkbox-label">
                            <span class="checkbox-icon">🚨</span>
                            AI Alerts & Insights
                        </label>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-large">
                <span class="btn-icon">📄</span>
                <span class="btn-text">Generate & Download Report</span>
            </button>
        </form>
    </div>
    
    <!-- Quick Report Templates -->
    <div class="quick-templates">
        <h2 class="section-title">Quick Report Templates</h2>
        <div class="template-grid">
            <div class="template-card">
                <div class="template-icon">🏥</div>
                <h3 class="template-title">Doctor Visit Prep</h3>
                <p class="template-description">
                    Essential information for your next appointment: recent symptoms, medication compliance, and key concerns.
                </p>
                <button onclick="generateQuickReport('doctor_visit', 14)" class="btn btn-secondary">
                    Generate (2 weeks)
                </button>
            </div>
            
            <div class="template-card">
                <div class="template-icon">💊</div>
                <h3 class="template-title">Medication Review</h3>
                <p class="template-description">
                    Detailed medication compliance report with side effects and timing analysis for pharmacy consultation.
                </p>
                <button onclick="generateQuickReport('medications_only', 30)" class="btn btn-secondary">
                    Generate (30 days)
                </button>
            </div>
            
            <div class="template-card">
                <div class="template-icon">📈</div>
                <h3 class="template-title">Symptom Trends</h3>
                <p class="template-description">
                    Comprehensive symptom analysis showing patterns, triggers, and severity trends over time.
                </p>
                <button onclick="generateQuickReport('symptoms_only', 90)" class="btn btn-secondary">
                    Generate (3 months)
                </button>
            </div>
            
            <div class="template-card">
                <div class="template-icon">📋</div>
                <h3 class="template-title">Complete Health Summary</h3>
                <p class="template-description">
                    Full comprehensive report including all tracked data, insights, and AI recommendations.
                </p>
                <button onclick="generateQuickReport('comprehensive', 30)" class="btn btn-secondary">
                    Generate (30 days)
                </button>
            </div>
        </div>
    </div>
    
    <!-- Data Preview -->
    <div class="data-preview">
        <h2 class="section-title">Recent Data Preview</h2>
        
        <!-- Recent Symptoms -->
        <?php if (!empty($recent_symptoms)): ?>
        <div class="preview-section">
            <h3 class="preview-title">Recent Symptoms</h3>
            <div class="preview-table">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Symptom</th>
                            <th>Severity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_symptoms as $symptom): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($symptom['logged_at'])); ?></td>
                            <td><?php echo htmlspecialchars($symptom['symptom_name']); ?></td>
                            <td>
                                <span class="severity-badge severity-<?php echo $symptom['severity']; ?>">
                                    <?php echo $symptom['severity']; ?>/10
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Medication Compliance -->
        <?php if (!empty($medication_compliance)): ?>
        <div class="preview-section">
            <h3 class="preview-title">Medication Compliance (30 Days)</h3>
            <div class="compliance-list">
                <?php foreach ($medication_compliance as $med): ?>
                <div class="compliance-item">
                    <div class="med-name"><?php echo htmlspecialchars($med['medication_name']); ?></div>
                    <div class="compliance-bar">
                        <div class="compliance-fill" style="width: <?php echo $med['compliance_rate']; ?>%"></div>
                    </div>
                    <div class="compliance-text">
                        <?php echo round($med['compliance_rate']); ?>% 
                        (<?php echo $med['taken_doses']; ?>/<?php echo $med['total_doses']; ?>)
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Report Usage Tips -->
    <div class="usage-tips">
        <div class="tips-card">
            <div class="tips-header">
                <h3 class="tips-title">📚 How to Use Your Reports</h3>
            </div>
            <div class="tips-content">
                <div class="tip-item">
                    <div class="tip-icon">🏥</div>
                    <div class="tip-text">
                        <strong>For Doctor Visits:</strong> Generate a 2-week comprehensive report before each appointment. 
                        Print or email the PDF/CSV to your healthcare provider in advance.
                    </div>
                </div>
                
                <div class="tip-item">
                    <div class="tip-icon">💊</div>
                    <div class="tip-text">
                        <strong>For Medication Reviews:</strong> Use the medication-only report to discuss compliance, 
                        side effects, and timing with your pharmacist or doctor.
                    </div>
                </div>
                
                <div class="tip-item">
                    <div class="tip-icon">📊</div>
                    <div class="tip-text">
                        <strong>For Pattern Analysis:</strong> Generate longer-term reports (3 months) to identify 
                        trends, triggers, and correlations in your health data.
                    </div>
                </div>
                
                <div class="tip-item">
                    <div class="tip-icon">👥</div>
                    <div class="tip-text">
                        <strong>For Family Coordination:</strong> Share JSON reports with tech-savvy family members 
                        who can help analyze patterns and coordinate care.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Reports Module Styles */
.overview-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

.stat-item {
    background: white;
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    text-align: center;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--gray-200);
    transition: all var(--transition-normal);
}

.stat-item:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
}

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: var(--spacing-sm);
}

.stat-number {
    font-size: var(--font-size-3xl);
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: var(--spacing-xs);
}

.stat-label {
    color: var(--gray-600);
    font-weight: 500;
}

/* Key Insights */
.insights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-lg);
    margin-top: var(--spacing-md);
}

.insight-card {
    background: white;
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
    display: flex;
    gap: var(--spacing-md);
}

.insight-icon {
    font-size: 2rem;
    flex-shrink: 0;
}

.insight-title {
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: var(--spacing-xs);
}

.insight-value {
    font-size: var(--font-size-xl);
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: var(--spacing-xs);
}

.insight-description {
    font-size: var(--font-size-sm);
    color: var(--gray-600);
}

/* Form Styles */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-lg);
}

.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-md);
}

.checkbox-item {
    display: flex;
    align-items: center;
}

.checkbox-item input[type="checkbox"] {
    margin-right: var(--spacing-sm);
    transform: scale(1.2);
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    cursor: pointer;
    font-weight: 500;
}

.checkbox-icon {
    font-size: var(--font-size-lg);
}

/* Template Grid */
.template-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

.template-card {
    background: white;
    border-radius: var(--radius-xl);
    padding: var(--spacing-xl);
    text-align: center;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--gray-200);
    transition: all var(--transition-normal);
}

.template-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
}

.template-icon {
    font-size: 3rem;
    margin-bottom: var(--spacing-md);
}

.template-title {
    font-size: var(--font-size-xl);
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: var(--spacing-sm);
}

.template-description {
    color: var(--gray-600);
    line-height: 1.5;
    margin-bottom: var(--spacing-lg);
}

/* Data Preview */
.data-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.data-table th {
    background: var(--gray-100);
    padding: var(--spacing-md);
    text-align: left;
    font-weight: 600;
    color: var(--gray-700);
    border-bottom: 1px solid var(--gray-200);
}

.data-table td {
    padding: var(--spacing-md);
    border-bottom: 1px solid var(--gray-100);
}

.severity-badge {
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-full);
    font-size: var(--font-size-sm);
    font-weight: 600;
}

.severity-1, .severity-2, .severity-3 { 
    background: #d1fae5; color: #065f46; 
}
.severity-4, .severity-5, .severity-6 { 
    background: #fef3c7; color: #92400e; 
}
.severity-7, .severity-8, .severity-9, .severity-10 { 
    background: #fee2e2; color: #991b1b; 
}

/* Compliance List */
.compliance-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.compliance-item {
    background: white;
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
    display: grid;
    grid-template-columns: 1fr 2fr 1fr;
    gap: var(--spacing-md);
    align-items: center;
}

.med-name {
    font-weight: 600;
    color: var(--gray-800);
}

.compliance-bar {
    height: 8px;
    background: var(--gray-200);
    border-radius: var(--radius-full);
    overflow: hidden;
}

.compliance-fill {
    height: 100%;
    background: linear-gradient(to right, var(--danger-color), var(--warning-color), var(--success-color));
    border-radius: var(--radius-full);
    transition: width var(--transition-normal);
}

.compliance-text {
    text-align: right;
    font-weight: 500;
    color: var(--gray-700);
}

/* Usage Tips */
.tips-card {
    background: var(--gradient-primary);
    border-radius: var(--radius-xl);
    padding: var(--spacing-xl);
    color: white;
}

.tips-title {
    font-size: var(--font-size-xl);
    font-weight: 600;
    margin-bottom: var(--spacing-lg);
    text-align: center;
}

.tip-item {
    display: flex;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
    background: rgba(255, 255, 255, 0.1);
    border-radius: var(--radius-lg);
    padding: var(--spacing-md);
}

.tip-item:last-child {
    margin-bottom: 0;
}

.tip-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.tip-text {
    line-height: 1.5;
}

/* Responsive Design */
@media (max-width: 768px) {
    .overview-stats {
        grid-template-columns: 1fr 1fr;
    }
    
    .insights-grid,
    .template-grid {
        grid-template-columns: 1fr;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .checkbox-grid {
        grid-template-columns: 1fr;
    }
    
    .compliance-item {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .compliance-text {
        text-align: center;
    }
}

@media (max-width: 480px) {
    .overview-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function openQuickReportModal() {
    document.querySelector('.report-generation').scrollIntoView({ behavior: 'smooth' });
    document.getElementById('report_type').focus();
}

function generateQuickReport(type, days) {
    // Set form values and submit
    document.getElementById('report_type').value = type;
    document.getElementById('date_range').value = days;
    document.getElementById('export_format').value = 'json';
    
    // Submit the form
    document.querySelector('.report-form').submit();
}

// Form submission with loading state
document.querySelector('.report-form').addEventListener('submit', function(e) {
    const submitBtn = document.querySelector('.btn-large[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<span class="btn-icon">⏳</span><span class="btn-text">Generating Report...</span>';
    submitBtn.disabled = true;
    
    // Re-enable after 3 seconds (in case of no download)
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 3000);
});

// Quick template buttons
document.querySelectorAll('.template-card button').forEach(button => {
    button.addEventListener('click', function() {
        this.innerHTML = '<span style="animation: spin 1s linear infinite;">⏳</span> Generating...';
        this.disabled = true;
        
        setTimeout(() => {
            this.innerHTML = this.innerHTML.replace('Generating...', 'Generate').replace(/⏳/, '📄');
            this.disabled = false;
        }, 2000);
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>