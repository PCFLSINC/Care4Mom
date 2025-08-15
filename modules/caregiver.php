<?php
/**
 * Care4Mom - Caregiver Communication & Coordination Module
 * Multi-user access for family members and care team coordination
 * Author: Care4Mom Development Team
 * Version: 1.0
 */

$page_title = 'Care Team Coordination';
$body_class = 'module-page caregiver-page';

require_once '../includes/header.php';
requireLogin();

$current_user = getCurrentUser();
$success_message = '';
$errors = [];

// Handle caregiver actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token. Please try again.');
        }
        
        if ($_POST['action'] === 'send_message') {
            $recipient_id = intval($_POST['recipient_id'] ?? 0);
            $message_type = sanitizeInput($_POST['message_type'] ?? 'chat');
            $subject = sanitizeInput($_POST['subject'] ?? '');
            $message = sanitizeInput($_POST['message'] ?? '');
            $priority = sanitizeInput($_POST['priority'] ?? 'medium');
            
            if (empty($message)) $errors[] = 'Message content is required';
            if ($recipient_id <= 0) $errors[] = 'Please select a recipient';
            
            if (empty($errors)) {
                // Determine patient_id and caregiver_id based on roles
                $patient_id = ($current_user['role'] === 'patient') ? $current_user['id'] : $recipient_id;
                $caregiver_id = ($current_user['role'] === 'patient') ? $recipient_id : $current_user['id'];
                
                $stmt = $pdo->prepare("
                    INSERT INTO caregiver_communications 
                    (patient_id, caregiver_id, message_type, subject, message, priority) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([$patient_id, $caregiver_id, $message_type, $subject, $message, $priority])) {
                    $success_message = 'Message sent successfully!';
                    logActivity('message_sent', "Sent $message_type to user $recipient_id", $current_user['id']);
                } else {
                    $errors[] = 'Failed to send message. Please try again.';
                }
            }
        }
        
        elseif ($_POST['action'] === 'assign_task') {
            $assignee_id = intval($_POST['assignee_id'] ?? 0);
            $subject = sanitizeInput($_POST['task_subject'] ?? '');
            $message = sanitizeInput($_POST['task_message'] ?? '');
            $due_date = $_POST['due_date'] ?? null;
            $priority = sanitizeInput($_POST['task_priority'] ?? 'medium');
            
            if (empty($subject)) $errors[] = 'Task title is required';
            if (empty($message)) $errors[] = 'Task description is required';
            if ($assignee_id <= 0) $errors[] = 'Please select an assignee';
            
            if (empty($errors)) {
                $patient_id = ($current_user['role'] === 'patient') ? $current_user['id'] : $assignee_id;
                $caregiver_id = ($current_user['role'] === 'patient') ? $assignee_id : $current_user['id'];
                
                $stmt = $pdo->prepare("
                    INSERT INTO caregiver_communications 
                    (patient_id, caregiver_id, message_type, subject, message, task_assigned, task_due_date, priority) 
                    VALUES (?, ?, 'task', ?, ?, 1, ?, ?)
                ");
                
                if ($stmt->execute([$patient_id, $caregiver_id, $subject, $message, $due_date, $priority])) {
                    $success_message = 'Task assigned successfully!';
                    logActivity('task_assigned', "Assigned task to user $assignee_id", $current_user['id']);
                } else {
                    $errors[] = 'Failed to assign task. Please try again.';
                }
            }
        }
        
        elseif ($_POST['action'] === 'complete_task') {
            $task_id = intval($_POST['task_id'] ?? 0);
            
            if ($task_id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE caregiver_communications 
                    SET task_completed = 1, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ? AND (patient_id = ? OR caregiver_id = ?)
                ");
                
                if ($stmt->execute([$task_id, $current_user['id'], $current_user['id']])) {
                    $success_message = 'Task marked as completed!';
                    logActivity('task_completed', "Completed task $task_id", $current_user['id']);
                } else {
                    $errors[] = 'Failed to update task. Please try again.';
                }
            }
        }
        
        elseif ($_POST['action'] === 'mark_read') {
            $message_id = intval($_POST['message_id'] ?? 0);
            
            if ($message_id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE caregiver_communications 
                    SET read_status = 1 
                    WHERE id = ? AND (patient_id = ? OR caregiver_id = ?)
                ");
                
                $stmt->execute([$message_id, $current_user['id'], $current_user['id']]);
            }
        }
        
    } catch (Exception $e) {
        logError('caregiver_module', $e->getMessage(), __FILE__, __LINE__);
        $errors[] = 'An error occurred. Please try again.';
    }
}

try {
    // Get care team members (caregivers, doctors, and patients)
    $team_stmt = $pdo->prepare("
        SELECT id, username, first_name, last_name, role, email, phone 
        FROM users 
        WHERE id != ? AND active = 1 
        ORDER BY role, first_name
    ");
    $team_stmt->execute([$current_user['id']]);
    $care_team = $team_stmt->fetchAll();
    
    // Get recent communications
    $comms_stmt = $pdo->prepare("
        SELECT cc.*, 
               p.first_name as patient_first, p.last_name as patient_last,
               c.first_name as caregiver_first, c.last_name as caregiver_last,
               c.role as caregiver_role
        FROM caregiver_communications cc
        JOIN users p ON cc.patient_id = p.id
        JOIN users c ON cc.caregiver_id = c.id
        WHERE cc.patient_id = ? OR cc.caregiver_id = ?
        ORDER BY cc.created_at DESC
        LIMIT 20
    ");
    $comms_stmt->execute([$current_user['id'], $current_user['id']]);
    $communications = $comms_stmt->fetchAll();
    
    // Get pending tasks
    $tasks_stmt = $pdo->prepare("
        SELECT cc.*, 
               p.first_name as patient_first, p.last_name as patient_last,
               c.first_name as caregiver_first, c.last_name as caregiver_last
        FROM caregiver_communications cc
        JOIN users p ON cc.patient_id = p.id
        JOIN users c ON cc.caregiver_id = c.id
        WHERE cc.message_type = 'task' 
        AND cc.task_completed = 0
        AND (cc.patient_id = ? OR cc.caregiver_id = ?)
        ORDER BY cc.task_due_date ASC, cc.priority DESC, cc.created_at ASC
    ");
    $tasks_stmt->execute([$current_user['id'], $current_user['id']]);
    $pending_tasks = $tasks_stmt->fetchAll();
    
    // Get unread message count
    $unread_stmt = $pdo->prepare("
        SELECT COUNT(*) as unread_count
        FROM caregiver_communications 
        WHERE (patient_id = ? OR caregiver_id = ?) 
        AND read_status = 0
        AND caregiver_id != ?
    ");
    $unread_stmt->execute([$current_user['id'], $current_user['id'], $current_user['id']]);
    $unread_count = $unread_stmt->fetch()['unread_count'];
    
} catch (Exception $e) {
    logError('caregiver_module', 'Failed to load caregiver data: ' . $e->getMessage(), __FILE__, __LINE__);
    $care_team = [];
    $communications = [];
    $pending_tasks = [];
    $unread_count = 0;
}
?>

<div class="module-container">
    <!-- Module Header -->
    <div class="module-header">
        <div class="header-content">
            <h1 class="module-title">
                <span class="module-icon">👥</span>
                Care Team Coordination
            </h1>
            <p class="module-description">
                Communicate with your care team, assign tasks, and coordinate care activities.
            </p>
        </div>
        <div class="header-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($care_team); ?></div>
                <div class="stat-label">Team Members</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $unread_count; ?></div>
                <div class="stat-label">Unread Messages</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($pending_tasks); ?></div>
                <div class="stat-label">Pending Tasks</div>
            </div>
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
    
    <!-- Quick Actions -->
    <div class="quick-actions">
        <button onclick="openMessageModal()" class="btn btn-primary">
            <span class="btn-icon">💬</span>
            <span class="btn-text">Send Message</span>
        </button>
        <button onclick="openTaskModal()" class="btn btn-secondary">
            <span class="btn-icon">📋</span>
            <span class="btn-text">Assign Task</span>
        </button>
        <button onclick="refreshCommunications()" class="btn btn-outline">
            <span class="btn-icon">🔄</span>
            <span class="btn-text">Refresh</span>
        </button>
    </div>
    
    <!-- Pending Tasks Section -->
    <?php if (!empty($pending_tasks)): ?>
    <div class="tasks-section">
        <h2 class="section-title">
            <span class="section-icon">📋</span>
            Pending Tasks
        </h2>
        <div class="tasks-grid">
            <?php foreach ($pending_tasks as $task): ?>
                <div class="task-card priority-<?php echo $task['priority']; ?>">
                    <div class="task-header">
                        <h3 class="task-title"><?php echo htmlspecialchars($task['subject']); ?></h3>
                        <div class="task-priority">
                            <span class="priority-badge priority-<?php echo $task['priority']; ?>">
                                <?php echo ucfirst($task['priority']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="task-content">
                        <p class="task-description"><?php echo htmlspecialchars($task['message']); ?></p>
                        <div class="task-meta">
                            <div class="task-assignee">
                                <span class="meta-label">Assignee:</span>
                                <?php echo htmlspecialchars($task['caregiver_first'] . ' ' . $task['caregiver_last']); ?>
                            </div>
                            <?php if ($task['task_due_date']): ?>
                                <div class="task-due">
                                    <span class="meta-label">Due:</span>
                                    <?php echo date('M j, Y g:i A', strtotime($task['task_due_date'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="task-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="complete_task">
                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                            <button type="submit" class="btn btn-success btn-sm">
                                <span class="btn-icon">✅</span>
                                <span class="btn-text">Mark Complete</span>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recent Communications -->
    <div class="communications-section">
        <h2 class="section-title">
            <span class="section-icon">💬</span>
            Recent Communications
        </h2>
        
        <?php if (empty($communications)): ?>
            <div class="empty-state">
                <div class="empty-icon">💬</div>
                <h3 class="empty-title">No communications yet</h3>
                <p class="empty-description">Start by sending a message to your care team or patient.</p>
                <button onclick="openMessageModal()" class="btn btn-primary">
                    <span class="btn-icon">💬</span>
                    <span class="btn-text">Send First Message</span>
                </button>
            </div>
        <?php else: ?>
            <div class="communications-list">
                <?php foreach ($communications as $comm): ?>
                    <div class="communication-card <?php echo !$comm['read_status'] ? 'unread' : ''; ?>">
                        <div class="comm-header">
                            <div class="comm-type">
                                <span class="type-badge type-<?php echo $comm['message_type']; ?>">
                                    <?php 
                                    $type_icons = [
                                        'chat' => '💬',
                                        'task' => '📋',
                                        'alert' => '🚨',
                                        'note' => '📝'
                                    ];
                                    echo $type_icons[$comm['message_type']] ?? '💬';
                                    ?>
                                    <?php echo ucfirst($comm['message_type']); ?>
                                </span>
                            </div>
                            <div class="comm-meta">
                                <span class="comm-from">
                                    From: <?php echo htmlspecialchars($comm['caregiver_first'] . ' ' . $comm['caregiver_last']); ?>
                                </span>
                                <span class="comm-date">
                                    <?php echo date('M j, Y g:i A', strtotime($comm['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($comm['subject']): ?>
                            <h3 class="comm-subject"><?php echo htmlspecialchars($comm['subject']); ?></h3>
                        <?php endif; ?>
                        
                        <div class="comm-content">
                            <p class="comm-message"><?php echo nl2br(htmlspecialchars($comm['message'])); ?></p>
                        </div>
                        
                        <?php if ($comm['message_type'] === 'task'): ?>
                            <div class="task-status">
                                <span class="status-label">Status:</span>
                                <span class="status-badge <?php echo $comm['task_completed'] ? 'completed' : 'pending'; ?>">
                                    <?php echo $comm['task_completed'] ? '✅ Completed' : '⏳ Pending'; ?>
                                </span>
                                <?php if ($comm['task_due_date']): ?>
                                    <span class="due-date">Due: <?php echo date('M j, Y', strtotime($comm['task_due_date'])); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!$comm['read_status']): ?>
                            <div class="comm-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="message_id" value="<?php echo $comm['id']; ?>">
                                    <button type="submit" class="btn btn-outline btn-sm">
                                        Mark as Read
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Care Team Members -->
    <div class="team-section">
        <h2 class="section-title">
            <span class="section-icon">👥</span>
            Care Team Members
        </h2>
        
        <?php if (empty($care_team)): ?>
            <div class="empty-state">
                <div class="empty-icon">👥</div>
                <h3 class="empty-title">No team members found</h3>
                <p class="empty-description">Care team members will appear here when they register.</p>
            </div>
        <?php else: ?>
            <div class="team-grid">
                <?php foreach ($care_team as $member): ?>
                    <div class="team-card">
                        <div class="member-avatar">
                            <span class="avatar-icon">
                                <?php 
                                $role_icons = [
                                    'patient' => '🤗',
                                    'caregiver' => '👨‍⚕️',
                                    'doctor' => '👩‍⚕️'
                                ];
                                echo $role_icons[$member['role']] ?? '👤';
                                ?>
                            </span>
                        </div>
                        <div class="member-info">
                            <h3 class="member-name">
                                <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                            </h3>
                            <p class="member-role"><?php echo ucfirst($member['role']); ?></p>
                            <?php if ($member['email']): ?>
                                <p class="member-contact">
                                    <span class="contact-icon">📧</span>
                                    <?php echo htmlspecialchars($member['email']); ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($member['phone']): ?>
                                <p class="member-contact">
                                    <span class="contact-icon">📞</span>
                                    <?php echo htmlspecialchars($member['phone']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="member-actions">
                            <button onclick="openMessageModal(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['first_name']); ?>')" 
                                    class="btn btn-primary btn-sm">
                                <span class="btn-icon">💬</span>
                                <span class="btn-text">Message</span>
                            </button>
                            <?php if ($current_user['role'] !== 'patient'): ?>
                                <button onclick="openTaskModal(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['first_name']); ?>')" 
                                        class="btn btn-secondary btn-sm">
                                    <span class="btn-icon">📋</span>
                                    <span class="btn-text">Assign Task</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Message Modal -->
<div id="messageModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Send Message</h2>
            <span class="modal-close" onclick="closeMessageModal()">&times;</span>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="send_message">
            
            <div class="form-group">
                <label for="recipient_id" class="form-label">To</label>
                <select id="recipient_id" name="recipient_id" class="form-select" required>
                    <option value="">Select recipient...</option>
                    <?php foreach ($care_team as $member): ?>
                        <option value="<?php echo $member['id']; ?>">
                            <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name'] . ' (' . ucfirst($member['role']) . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="message_type" class="form-label">Message Type</label>
                <select id="message_type" name="message_type" class="form-select">
                    <option value="chat">Chat Message</option>
                    <option value="alert">Important Alert</option>
                    <option value="note">Note/Update</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="subject" class="form-label">Subject (Optional)</label>
                <input type="text" id="subject" name="subject" class="form-input" 
                       placeholder="Enter message subject">
            </div>
            
            <div class="form-group">
                <label for="message" class="form-label">Message</label>
                <textarea id="message" name="message" class="form-textarea" rows="4" 
                          placeholder="Type your message here..." required></textarea>
            </div>
            
            <div class="form-group">
                <label for="priority" class="form-label">Priority</label>
                <select id="priority" name="priority" class="form-select">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            
            <div class="modal-actions">
                <button type="button" onclick="closeMessageModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <span class="btn-icon">💬</span>
                    <span class="btn-text">Send Message</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Task Assignment Modal -->
<div id="taskModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Assign Task</h2>
            <span class="modal-close" onclick="closeTaskModal()">&times;</span>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="assign_task">
            
            <div class="form-group">
                <label for="assignee_id" class="form-label">Assign To</label>
                <select id="assignee_id" name="assignee_id" class="form-select" required>
                    <option value="">Select assignee...</option>
                    <?php foreach ($care_team as $member): ?>
                        <option value="<?php echo $member['id']; ?>">
                            <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name'] . ' (' . ucfirst($member['role']) . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="task_subject" class="form-label">Task Title</label>
                <input type="text" id="task_subject" name="task_subject" class="form-input" 
                       placeholder="Enter task title" required>
            </div>
            
            <div class="form-group">
                <label for="task_message" class="form-label">Task Description</label>
                <textarea id="task_message" name="task_message" class="form-textarea" rows="4" 
                          placeholder="Describe the task details..." required></textarea>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="due_date" class="form-label">Due Date (Optional)</label>
                    <input type="datetime-local" id="due_date" name="due_date" class="form-input">
                </div>
                
                <div class="form-group">
                    <label for="task_priority" class="form-label">Priority</label>
                    <select id="task_priority" name="task_priority" class="form-select">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" onclick="closeTaskModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <span class="btn-icon">📋</span>
                    <span class="btn-text">Assign Task</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Caregiver Module Specific Styles */
.caregiver-page .header-stats {
    display: flex;
    gap: var(--spacing-md);
}

.stat-card {
    background: var(--gradient-primary);
    color: white;
    padding: var(--spacing-md);
    border-radius: var(--radius-lg);
    text-align: center;
    min-width: 100px;
}

.stat-number {
    font-size: var(--font-size-2xl);
    font-weight: 700;
    margin-bottom: var(--spacing-xs);
}

.stat-label {
    font-size: var(--font-size-sm);
    opacity: 0.9;
}

.quick-actions {
    display: flex;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-xl);
    flex-wrap: wrap;
}

/* Tasks Section */
.tasks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

.task-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    border-left: 4px solid var(--blue-500);
    box-shadow: var(--shadow-md);
    transition: var(--transition-base);
}

.task-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.task-card.priority-high {
    border-left-color: var(--red-500);
}

.task-card.priority-medium {
    border-left-color: var(--yellow-500);
}

.task-card.priority-low {
    border-left-color: var(--green-500);
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--spacing-md);
}

.task-title {
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--gray-900);
    margin: 0;
    flex: 1;
}

.priority-badge {
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-md);
    font-size: var(--font-size-xs);
    font-weight: 600;
    text-transform: uppercase;
}

.priority-badge.priority-high {
    background: var(--red-100);
    color: var(--red-800);
}

.priority-badge.priority-medium {
    background: var(--yellow-100);
    color: var(--yellow-800);
}

.priority-badge.priority-low {
    background: var(--green-100);
    color: var(--green-800);
}

.task-description {
    color: var(--gray-700);
    line-height: 1.6;
    margin-bottom: var(--spacing-md);
}

.task-meta {
    display: flex;
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-md);
    font-size: var(--font-size-sm);
    color: var(--gray-600);
}

.meta-label {
    font-weight: 600;
}

.task-actions {
    display: flex;
    gap: var(--spacing-sm);
    justify-content: flex-end;
}

/* Communications Section */
.communications-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-lg);
}

.communication-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
    transition: var(--transition-base);
}

.communication-card.unread {
    border-left: 4px solid var(--blue-500);
    background: linear-gradient(135deg, #f8faff 0%, #ffffff 100%);
}

.communication-card:hover {
    box-shadow: var(--shadow-md);
}

.comm-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-md);
}

.type-badge {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-xs);
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-md);
    font-size: var(--font-size-sm);
    font-weight: 600;
}

.type-badge.type-chat {
    background: var(--blue-100);
    color: var(--blue-800);
}

.type-badge.type-task {
    background: var(--purple-100);
    color: var(--purple-800);
}

.type-badge.type-alert {
    background: var(--red-100);
    color: var(--red-800);
}

.type-badge.type-note {
    background: var(--green-100);
    color: var(--green-800);
}

.comm-meta {
    text-align: right;
    font-size: var(--font-size-sm);
    color: var(--gray-600);
}

.comm-subject {
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--gray-900);
    margin: 0 0 var(--spacing-md) 0;
}

.comm-message {
    color: var(--gray-700);
    line-height: 1.6;
    margin: 0;
}

.task-status {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    margin-top: var(--spacing-md);
    padding-top: var(--spacing-md);
    border-top: 1px solid var(--gray-200);
    font-size: var(--font-size-sm);
}

.status-badge {
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-md);
    font-weight: 600;
}

.status-badge.completed {
    background: var(--green-100);
    color: var(--green-800);
}

.status-badge.pending {
    background: var(--yellow-100);
    color: var(--yellow-800);
}

.comm-actions {
    margin-top: var(--spacing-md);
    display: flex;
    justify-content: flex-end;
}

/* Team Section */
.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--spacing-lg);
}

.team-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    text-align: center;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
    transition: var(--transition-base);
}

.team-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.member-avatar {
    margin-bottom: var(--spacing-md);
}

.avatar-icon {
    font-size: 3rem;
    display: inline-block;
    width: 80px;
    height: 80px;
    background: var(--gradient-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.member-name {
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--gray-900);
    margin: 0 0 var(--spacing-xs) 0;
}

.member-role {
    color: var(--gray-600);
    font-weight: 600;
    text-transform: uppercase;
    font-size: var(--font-size-sm);
    margin-bottom: var(--spacing-md);
}

.member-contact {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-xs);
    margin-bottom: var(--spacing-xs);
    font-size: var(--font-size-sm);
    color: var(--gray-700);
}

.contact-icon {
    opacity: 0.7;
}

.member-actions {
    display: flex;
    gap: var(--spacing-sm);
    justify-content: center;
    margin-top: var(--spacing-md);
}

/* Modal Enhancements */
.modal-form .form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-md);
}

/* Responsive Design */
@media (max-width: 768px) {
    .quick-actions {
        justify-content: center;
    }
    
    .header-stats {
        flex-direction: column;
        gap: var(--spacing-sm);
    }
    
    .tasks-grid,
    .team-grid {
        grid-template-columns: 1fr;
    }
    
    .task-header,
    .comm-header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-sm);
    }
    
    .task-meta {
        flex-direction: column;
        gap: var(--spacing-xs);
    }
    
    .member-actions {
        flex-direction: column;
    }
    
    .modal-form .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function openMessageModal(recipientId = null, recipientName = null) {
    const modal = document.getElementById('messageModal');
    const recipientSelect = document.getElementById('recipient_id');
    
    if (recipientId) {
        recipientSelect.value = recipientId;
    }
    
    modal.style.display = 'flex';
}

function closeMessageModal() {
    const modal = document.getElementById('messageModal');
    modal.style.display = 'none';
    
    // Reset form
    modal.querySelector('form').reset();
}

function openTaskModal(assigneeId = null, assigneeName = null) {
    const modal = document.getElementById('taskModal');
    const assigneeSelect = document.getElementById('assignee_id');
    
    if (assigneeId) {
        assigneeSelect.value = assigneeId;
    }
    
    modal.style.display = 'flex';
}

function closeTaskModal() {
    const modal = document.getElementById('taskModal');
    modal.style.display = 'none';
    
    // Reset form
    modal.querySelector('form').reset();
}

function refreshCommunications() {
    location.reload();
}

// Close modals when clicking outside
window.onclick = function(event) {
    const messageModal = document.getElementById('messageModal');
    const taskModal = document.getElementById('taskModal');
    
    if (event.target === messageModal) {
        closeMessageModal();
    }
    if (event.target === taskModal) {
        closeTaskModal();
    }
}

// Auto-refresh communications every 30 seconds
setInterval(function() {
    // Only refresh if no modals are open
    const messageModal = document.getElementById('messageModal');
    const taskModal = document.getElementById('taskModal');
    
    if (messageModal.style.display !== 'flex' && taskModal.style.display !== 'flex') {
        refreshCommunications();
    }
}, 30000);
</script>

<?php require_once '../includes/footer.php'; ?>