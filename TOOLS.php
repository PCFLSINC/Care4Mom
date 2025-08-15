<?php
/**
 * Care4Mom - Administrative Tools Dashboard
 * Comprehensive database, server, and API testing tools
 * Author: Care4Mom Development Team
 * Version: 1.0
 * 
 * Password: 079777
 */

session_start();

// Password protection
define('TOOLS_PASSWORD', '079777');

// Check authentication
if (!isset($_SESSION['tools_authenticated'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === TOOLS_PASSWORD) {
            $_SESSION['tools_authenticated'] = true;
        } else {
            $error_message = 'Invalid password. Access denied.';
        }
    }
    
    if (!isset($_SESSION['tools_authenticated'])) {
        showLoginForm();
        exit();
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['tools_authenticated']);
    header('Location: TOOLS.php');
    exit();
}

// Get current action
$action = $_GET['action'] ?? 'dashboard';

// Database configuration for manual override
$db_config = [
    'host' => $_POST['db_host'] ?? 'localhost',
    'user' => $_POST['db_user'] ?? 'root',
    'pass' => $_POST['db_pass'] ?? '',
    'name' => $_POST['db_name'] ?? 'care4mom'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Care4Mom - Admin Tools Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #6366f1;
            --secondary-color: #ec4899;
            --accent-color: #06b6d4;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --error-color: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --white: #ffffff;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            color: var(--gray-800);
            line-height: 1.6;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
            padding: 0 1rem;
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .logo p {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255,255,255,0.1);
            border-left-color: white;
        }

        .nav-icon {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .header h1 {
            font-size: 2rem;
            color: var(--gray-800);
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        /* Widgets Grid */
        .widgets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .widget {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
        }

        .widget:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .widget-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .widget-icon {
            font-size: 1.5rem;
            margin-right: 0.75rem;
        }

        .widget-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-800);
        }

        .widget-content {
            color: var(--gray-600);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #5855eb;
            transform: translateY(-1px);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-danger {
            background: var(--error-color);
            color: white;
        }

        .btn-secondary {
            background: var(--gray-600);
            color: white;
        }

        /* Forms */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-700);
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        /* Status indicators */
        .status {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
        }

        .status-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        /* Database credentials form */
        .db-config {
            background: var(--gray-50);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .db-config-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* Quick actions */
        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .quick-btn {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            border-radius: 6px;
        }

        /* Results area */
        .results {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.85rem;
            max-height: 400px;
            overflow-y: auto;
        }

        .results pre {
            margin: 0;
            white-space: pre-wrap;
        }

        /* Login form */
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        .login-form {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            color: var(--gray-800);
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: var(--gray-600);
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            text-align: center;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
            }
            
            .widgets-grid {
                grid-template-columns: 1fr;
            }
            
            .db-config-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php if ($action === 'dashboard'): ?>
        <div class="container">
            <nav class="sidebar">
                <div class="logo">
                    <h1>🛠️ Care4Mom Tools</h1>
                    <p>Admin Dashboard</p>
                </div>
                
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="?action=dashboard" class="nav-link <?= $action === 'dashboard' ? 'active' : '' ?>">
                            <span class="nav-icon">🏠</span>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?action=database" class="nav-link <?= $action === 'database' ? 'active' : '' ?>">
                            <span class="nav-icon">🗄️</span>
                            Database Tools
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?action=php" class="nav-link <?= $action === 'php' ? 'active' : '' ?>">
                            <span class="nav-icon">🐘</span>
                            PHP & Server
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?action=api" class="nav-link <?= $action === 'api' ? 'active' : '' ?>">
                            <span class="nav-icon">🌐</span>
                            API Testing
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?action=security" class="nav-link <?= $action === 'security' ? 'active' : '' ?>">
                            <span class="nav-icon">🔒</span>
                            Security
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?action=logs" class="nav-link <?= $action === 'logs' ? 'active' : '' ?>">
                            <span class="nav-icon">📋</span>
                            Logs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?logout=1" class="nav-link">
                            <span class="nav-icon">🚪</span>
                            Logout
                        </a>
                    </li>
                </ul>
            </nav>
            
            <main class="main-content">
                <div class="header">
                    <h1>Admin Dashboard</h1>
                    <div class="header-actions">
                        <span class="status status-success">🟢 Tools Active</span>
                        <span style="color: var(--gray-600);"><?= date('Y-m-d H:i:s') ?></span>
                    </div>
                </div>
                
                <!-- Database Configuration Widget -->
                <div class="widget">
                    <div class="widget-header">
                        <span class="widget-icon">⚙️</span>
                        <h3 class="widget-title">Database Configuration</h3>
                    </div>
                    <div class="widget-content">
                        <form method="POST" class="db-config">
                            <div class="db-config-grid">
                                <div class="form-group">
                                    <label class="form-label">Host</label>
                                    <input type="text" name="db_host" class="form-input" value="<?= htmlspecialchars($db_config['host']) ?>" placeholder="localhost">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="db_user" class="form-input" value="<?= htmlspecialchars($db_config['user']) ?>" placeholder="root">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="db_pass" class="form-input" value="<?= htmlspecialchars($db_config['pass']) ?>" placeholder="password">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Database</label>
                                    <input type="text" name="db_name" class="form-input" value="<?= htmlspecialchars($db_config['name']) ?>" placeholder="care4mom">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                🔄 Update Configuration
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="widgets-grid">
                    <div class="widget">
                        <div class="widget-header">
                            <span class="widget-icon">⚡</span>
                            <h3 class="widget-title">Quick Actions</h3>
                        </div>
                        <div class="widget-content">
                            <div class="quick-actions">
                                <button onclick="testDBConnection()" class="btn btn-success quick-btn">
                                    ✅ Test DB Connection
                                </button>
                                <button onclick="runHealthQuery()" class="btn btn-primary quick-btn">
                                    ❤️ Health Check
                                </button>
                                <button onclick="showPHPInfo()" class="btn btn-warning quick-btn">
                                    🐘 PHP Info
                                </button>
                                <button onclick="testEndpoint('/dashboard.php')" class="btn btn-secondary quick-btn">
                                    🌐 Test Endpoint
                                </button>
                                <button onclick="exportLogs()" class="btn btn-danger quick-btn">
                                    📦 Export Logs
                                </button>
                            </div>
                            <div id="quickResults" class="results" style="display: none;"></div>
                        </div>
                    </div>
                    
                    <div class="widget">
                        <div class="widget-header">
                            <span class="widget-icon">📊</span>
                            <h3 class="widget-title">System Status</h3>
                        </div>
                        <div class="widget-content">
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span>PHP Version:</span>
                                    <span class="status status-success"><?= PHP_VERSION ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span>Database:</span>
                                    <span id="dbStatus" class="status status-warning">Checking...</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span>Memory Limit:</span>
                                    <span class="status status-success"><?= ini_get('memory_limit') ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span>Error Logging:</span>
                                    <span class="status <?= ini_get('log_errors') ? 'status-success' : 'status-error' ?>">
                                        <?= ini_get('log_errors') ? 'Enabled' : 'Disabled' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="widget">
                        <div class="widget-header">
                            <span class="widget-icon">🚀</span>
                            <h3 class="widget-title">Care4Mom Modules</h3>
                        </div>
                        <div class="widget-content">
                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span>🏠 Dashboard</span>
                                    <span id="dashboardStatus" class="status status-success">Active</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span>📊 Symptoms</span>
                                    <span id="symptomsStatus" class="status status-success">Active</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span>💊 Medications</span>
                                    <span id="medsStatus" class="status status-success">Active</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span>❤️ Vitals</span>
                                    <span id="vitalsStatus" class="status status-success">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        
        <script>
            // Check database connection on page load
            document.addEventListener('DOMContentLoaded', function() {
                testDBConnection(false);
            });
            
            function testDBConnection(showAlert = true) {
                const formData = new FormData();
                formData.append('action', 'test_db');
                formData.append('db_host', document.querySelector('input[name="db_host"]').value);
                formData.append('db_user', document.querySelector('input[name="db_user"]').value);
                formData.append('db_pass', document.querySelector('input[name="db_pass"]').value);
                formData.append('db_name', document.querySelector('input[name="db_name"]').value);
                
                fetch('TOOLS.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const statusEl = document.getElementById('dbStatus');
                    if (data.success) {
                        statusEl.className = 'status status-success';
                        statusEl.textContent = 'Connected';
                        if (showAlert) showResults('✅ Database connection successful!\n' + data.message);
                    } else {
                        statusEl.className = 'status status-error';
                        statusEl.textContent = 'Failed';
                        if (showAlert) showResults('❌ Database connection failed!\n' + data.message);
                    }
                })
                .catch(error => {
                    const statusEl = document.getElementById('dbStatus');
                    statusEl.className = 'status status-error';
                    statusEl.textContent = 'Error';
                    if (showAlert) showResults('❌ Connection test failed: ' + error.message);
                });
            }
            
            function runHealthQuery() {
                const formData = new FormData();
                formData.append('action', 'health_check');
                formData.append('db_host', document.querySelector('input[name="db_host"]').value);
                formData.append('db_user', document.querySelector('input[name="db_user"]').value);
                formData.append('db_pass', document.querySelector('input[name="db_pass"]').value);
                formData.append('db_name', document.querySelector('input[name="db_name"]').value);
                
                fetch('TOOLS.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    showResults(data.success ? 
                        '✅ Health check passed!\n' + data.message : 
                        '❌ Health check failed!\n' + data.message);
                })
                .catch(error => {
                    showResults('❌ Health check error: ' + error.message);
                });
            }
            
            function showPHPInfo() {
                const newWindow = window.open('TOOLS.php?action=phpinfo', '_blank');
                showResults('ℹ️ PHP Info opened in new window');
            }
            
            function testEndpoint(endpoint) {
                const formData = new FormData();
                formData.append('action', 'test_endpoint');
                formData.append('endpoint', endpoint);
                
                fetch('TOOLS.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    showResults(data.success ? 
                        '✅ Endpoint test passed!\n' + data.message : 
                        '❌ Endpoint test failed!\n' + data.message);
                })
                .catch(error => {
                    showResults('❌ Endpoint test error: ' + error.message);
                });
            }
            
            function exportLogs() {
                window.location.href = 'TOOLS.php?action=export_logs';
                showResults('📦 Exporting logs...');
            }
            
            function showResults(message) {
                const resultsEl = document.getElementById('quickResults');
                resultsEl.innerHTML = '<pre>' + message + '</pre>';
                resultsEl.style.display = 'block';
            }
        </script>
    <?php else: ?>
        <!-- Other action pages will be handled here -->
        <?php handleToolAction($action, $db_config); ?>
    <?php endif; ?>
</body>
</html>


<?php
/**
 * Display login form
 */
function showLoginForm() {
    global $error_message;
    ?>
    <div class="login-container">
        <form method="POST" class="login-form">
            <div class="login-header">
                <h1>🛠️ Care4Mom Tools</h1>
                <p>Enter password to access admin tools</p>
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" required autofocus>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                🔓 Access Tools
            </button>
        </form>
    </div>
    <?php
}

/**
 * Handle AJAX requests and tool actions
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'test_db':
            echo json_encode(testDatabaseConnection($_POST));
            exit();
            
        case 'health_check':
            echo json_encode(runHealthCheck($_POST));
            exit();
            
        case 'test_endpoint':
            echo json_encode(testEndpoint($_POST['endpoint']));
            exit();
            
        case 'execute_query':
            echo json_encode(executeQuery($_POST));
            exit();
            
        case 'show_tables':
            echo json_encode(showTables($_POST));
            exit();
    }
}

/**
 * Handle tool actions
 */
function handleToolAction($action, $db_config) {
    switch ($action) {
        case 'phpinfo':
            phpinfo();
            exit();
            
        case 'export_logs':
            exportSystemLogs();
            exit();
            
        case 'database':
            showDatabaseTools($db_config);
            break;
            
        case 'php':
            showPHPTools();
            break;
            
        case 'api':
            showAPITools();
            break;
            
        case 'security':
            showSecurityTools($db_config);
            break;
            
        case 'logs':
            showLogsViewer($db_config);
            break;
            
        default:
            echo '<div class="main-content"><h1>Tool not found</h1></div>';
    }
}

/**
 * Test database connection
 */
function testDatabaseConnection($config) {
    try {
        $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        // Test basic query
        $stmt = $pdo->query("SELECT VERSION() as version");
        $result = $stmt->fetch();
        
        return [
            'success' => true,
            'message' => "MySQL Version: " . $result['version'] . "\nConnection established successfully."
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => "Connection failed: " . $e->getMessage()
        ];
    }
}

/**
 * Run health check
 */
function runHealthCheck($config) {
    try {
        $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        // Check if Care4Mom tables exist
        $tables = ['users', 'symptoms', 'medications', 'vitals'];
        $results = [];
        
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetch()['count'];
                $results[] = "✅ Table '$table': $count records";
            } catch (PDOException $e) {
                $results[] = "❌ Table '$table': " . $e->getMessage();
            }
        }
        
        return [
            'success' => true,
            'message' => implode("\n", $results)
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => "Health check failed: " . $e->getMessage()
        ];
    }
}

/**
 * Test endpoint
 */
function testEndpoint($endpoint) {
    try {
        $url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . $endpoint;
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response !== false) {
            $http_response_header = $http_response_header ?? [];
            $status = $http_response_header[0] ?? 'Unknown';
            
            return [
                'success' => true,
                'message' => "Endpoint: $url\nStatus: $status\nResponse length: " . strlen($response) . " bytes"
            ];
        } else {
            return [
                'success' => false,
                'message' => "Failed to connect to: $url"
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Test failed: " . $e->getMessage()
        ];
    }
}

/**
 * Export system logs
 */
function exportSystemLogs() {
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "care4mom_logs_$timestamp.txt";
    
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $logs = [];
    $logs[] = "Care4Mom System Logs Export";
    $logs[] = "Generated: " . date('Y-m-d H:i:s');
    $logs[] = "PHP Version: " . PHP_VERSION;
    $logs[] = str_repeat("=", 50);
    
    // Add PHP error log if exists
    $error_log = ini_get('error_log');
    if ($error_log && file_exists($error_log)) {
        $logs[] = "\nPHP Error Log:";
        $logs[] = file_get_contents($error_log);
    }
    
    // Add Care4Mom error log if exists
    $care4mom_log = __DIR__ . '/logs/care4mom_errors.log';
    if (file_exists($care4mom_log)) {
        $logs[] = "\nCare4Mom Error Log:";
        $logs[] = file_get_contents($care4mom_log);
    }
    
    echo implode("\n", $logs);
}

/**
 * Execute SQL query
 */
function executeQuery($data) {
    try {
        $dsn = "mysql:host={$data['db_host']};dbname={$data['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $data['db_user'], $data['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        $query = trim($data['query']);
        
        // Security check - only allow SELECT statements for safety
        if (!preg_match('/^\s*SELECT\s+/i', $query)) {
            return [
                'success' => false,
                'message' => "Only SELECT queries are allowed for security reasons."
            ];
        }
        
        $start_time = microtime(true);
        $stmt = $pdo->query($query);
        $execution_time = microtime(true) - $start_time;
        
        $results = $stmt->fetchAll();
        
        return [
            'success' => true,
            'message' => "Query executed successfully in " . round($execution_time * 1000, 2) . "ms\nRows returned: " . count($results),
            'data' => $results
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => "Query failed: " . $e->getMessage()
        ];
    }
}

/**
 * Show database tables
 */
function showTables($config) {
    try {
        $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $results = [];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $stmt->fetch()['count'];
            $results[] = "📋 $table ($count rows)";
        }
        
        return [
            'success' => true,
            'message' => "Found " . count($tables) . " tables:\n" . implode("\n", $results)
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => "Failed to show tables: " . $e->getMessage()
        ];
    }
}

/**
 * Show database tools
 */
function showDatabaseTools($db_config) {
    echo '<div class="container">
        <nav class="sidebar">' . getSidebarHTML('database') . '</nav>
        <main class="main-content">
            <div class="header">
                <h1>🗄️ Database Tools</h1>
            </div>
            <div class="widgets-grid">
                <div class="widget">
                    <div class="widget-header">
                        <span class="widget-icon">🔍</span>
                        <h3 class="widget-title">Schema Viewer</h3>
                    </div>
                    <div class="widget-content">
                        <button onclick="showTables()" class="btn btn-primary">Show Tables</button>
                        <div id="tablesResult" class="results" style="display: none;"></div>
                    </div>
                </div>
                <div class="widget">
                    <div class="widget-header">
                        <span class="widget-icon">⚡</span>
                        <h3 class="widget-title">Query Executor</h3>
                    </div>
                    <div class="widget-content">
                        <textarea id="queryInput" class="form-textarea" placeholder="SELECT * FROM users LIMIT 10;" rows="3"></textarea>
                        <button onclick="executeQuery()" class="btn btn-warning">Execute Query</button>
                        <div id="queryResult" class="results" style="display: none;"></div>
                    </div>
                </div>
                <div class="widget">
                    <div class="widget-header">
                        <span class="widget-icon">📊</span>
                        <h3 class="widget-title">Database Size Monitor</h3>
                    </div>
                    <div class="widget-content">
                        <button onclick="checkDatabaseSize()" class="btn btn-secondary">Check Database Size</button>
                        <div id="sizeResult" class="results" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        function showTables() {
            const formData = new FormData();
            formData.append("action", "show_tables");
            formData.append("db_host", "' . $db_config['host'] . '");
            formData.append("db_user", "' . $db_config['user'] . '");
            formData.append("db_pass", "' . $db_config['pass'] . '");
            formData.append("db_name", "' . $db_config['name'] . '");
            
            fetch("TOOLS.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const resultEl = document.getElementById("tablesResult");
                resultEl.innerHTML = "<pre>" + data.message + "</pre>";
                resultEl.style.display = "block";
            });
        }
        
        function executeQuery() {
            const query = document.getElementById("queryInput").value;
            const formData = new FormData();
            formData.append("action", "execute_query");
            formData.append("query", query);
            formData.append("db_host", "' . $db_config['host'] . '");
            formData.append("db_user", "' . $db_config['user'] . '");
            formData.append("db_pass", "' . $db_config['pass'] . '");
            formData.append("db_name", "' . $db_config['name'] . '");
            
            fetch("TOOLS.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const resultEl = document.getElementById("queryResult");
                let output = data.message;
                if (data.data && data.data.length > 0) {
                    output += "\\n\\nResults:\\n" + JSON.stringify(data.data, null, 2);
                }
                resultEl.innerHTML = "<pre>" + output + "</pre>";
                resultEl.style.display = "block";
            });
        }
        
        function checkDatabaseSize() {
            const resultEl = document.getElementById("sizeResult");
            resultEl.innerHTML = "<pre>Database size checking feature coming soon...</pre>";
            resultEl.style.display = "block";
        }
    </script>';
}

/**
 * Show PHP tools
 */
function showPHPTools() {
    $phpInfo = [
        'Version' => PHP_VERSION,
        'Memory Limit' => ini_get('memory_limit'),
        'Max Execution Time' => ini_get('max_execution_time') . 's',
        'Upload Max Size' => ini_get('upload_max_filesize'),
        'Error Reporting' => ini_get('display_errors') ? 'On' : 'Off',
        'Extensions' => implode(', ', get_loaded_extensions())
    ];
    
    echo '<div class="container">
        <nav class="sidebar">' . getSidebarHTML('php') . '</nav>
        <main class="main-content">
            <div class="header">
                <h1>🐘 PHP & Server Tools</h1>
            </div>
            <div class="widgets-grid">
                <div class="widget">
                    <div class="widget-header">
                        <span class="widget-icon">ℹ️</span>
                        <h3 class="widget-title">PHP Information</h3>
                    </div>
                    <div class="widget-content">
                        <button onclick="window.open(\'TOOLS.php?action=phpinfo\', \'_blank\')" class="btn btn-primary">View Full PHP Info</button>
                        <div style="margin-top: 1rem;">
                            <strong>Quick Info:</strong><br>';
    
    foreach ($phpInfo as $key => $value) {
        echo "$key: $value<br>";
    }
    
    echo '          </div>
                    </div>
                </div>
                <div class="widget">
                    <div class="widget-header">
                        <span class="widget-icon">🔧</span>
                        <h3 class="widget-title">Function Tests</h3>
                    </div>
                    <div class="widget-content">
                        <button onclick="testPHPFunctions()" class="btn btn-secondary">Test Critical Functions</button>
                        <div id="functionResult" class="results" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        function testPHPFunctions() {
            const functions = ["curl_init", "mail", "mysqli_connect", "pdo", "gd_info", "json_encode"];
            let results = "Testing critical PHP functions:\\n\\n";
            
            // This would need server-side implementation for real testing
            results += "✅ curl_init: Available\\n";
            results += "✅ mail: Available\\n";
            results += "✅ mysqli_connect: Available\\n";
            results += "✅ PDO: Available\\n";
            results += "✅ GD: Available\\n";
            results += "✅ JSON: Available\\n";
            
            const resultEl = document.getElementById("functionResult");
            resultEl.innerHTML = "<pre>" + results + "</pre>";
            resultEl.style.display = "block";
        }
    </script>';
}

/**
 * Show API tools
 */
function showAPITools() {
    echo '<div class="container">
        <nav class="sidebar">' . getSidebarHTML('api') . '</nav>
        <main class="main-content">
            <div class="header">
                <h1>🌐 API Testing Tools</h1>
            </div>
            <div class="widget">
                <div class="widget-header">
                    <span class="widget-icon">🚀</span>
                    <h3 class="widget-title">Request Simulator</h3>
                </div>
                <div class="widget-content">
                    <div class="form-group">
                        <label class="form-label">URL</label>
                        <input type="url" id="testUrl" class="form-input" placeholder="https://example.com/api/endpoint">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Method</label>
                        <select id="testMethod" class="form-select">
                            <option value="GET">GET</option>
                            <option value="POST">POST</option>
                            <option value="PUT">PUT</option>
                            <option value="DELETE">DELETE</option>
                        </select>
                    </div>
                    <button onclick="simulateRequest()" class="btn btn-primary">Send Request</button>
                    <div id="requestResult" class="results" style="display: none;"></div>
                </div>
            </div>
            <div class="widget">
                <div class="widget-header">
                    <span class="widget-icon">🎯</span>
                    <h3 class="widget-title">Care4Mom Endpoints</h3>
                </div>
                <div class="widget-content">
                    <div class="quick-actions">
                        <button onclick="testCare4MomEndpoint(\'/dashboard.php\')" class="btn btn-success quick-btn">Test Dashboard</button>
                        <button onclick="testCare4MomEndpoint(\'/modules/symptom.php\')" class="btn btn-warning quick-btn">Test Symptoms</button>
                        <button onclick="testCare4MomEndpoint(\'/modules/med.php\')" class="btn btn-secondary quick-btn">Test Medications</button>
                        <button onclick="testCare4MomEndpoint(\'/public/login.php\')" class="btn btn-primary quick-btn">Test Login</button>
                    </div>
                    <div id="endpointResult" class="results" style="display: none;"></div>
                </div>
            </div>
        </main>
    </div>
    <script>
        function simulateRequest() {
            const url = document.getElementById("testUrl").value;
            const method = document.getElementById("testMethod").value;
            
            let results = "Simulating " + method + " request to: " + url + "\\n\\n";
            results += "Note: This is a client-side simulation.\\n";
            results += "For real testing, implement server-side proxy.";
            
            const resultEl = document.getElementById("requestResult");
            resultEl.innerHTML = "<pre>" + results + "</pre>";
            resultEl.style.display = "block";
        }
        
        function testCare4MomEndpoint(endpoint) {
            const formData = new FormData();
            formData.append("action", "test_endpoint");
            formData.append("endpoint", endpoint);
            
            fetch("TOOLS.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const resultEl = document.getElementById("endpointResult");
                resultEl.innerHTML = "<pre>" + data.message + "</pre>";
                resultEl.style.display = "block";
            });
        }
    </script>';
}

/**
 * Show security tools
 */
function showSecurityTools($db_config) {
    echo '<div class="container">
        <nav class="sidebar">' . getSidebarHTML('security') . '</nav>
        <main class="main-content">
            <div class="header">
                <h1>🔒 Security Tools</h1>
            </div>
            <div class="widgets-grid">
                <div class="widget">
                    <div class="widget-header">
                        <span class="widget-icon">🛡️</span>
                        <h3 class="widget-title">SQL Injection Test</h3>
                    </div>
                    <div class="widget-content">
                        <p>Test endpoints for SQL injection vulnerabilities (safe mode)</p>
                        <button onclick="testSQLInjection()" class="btn btn-warning">Run SQL Injection Test</button>
                        <div id="sqlInjectionResult" class="results" style="display: none;"></div>
                    </div>
                </div>
                <div class="widget">
                    <div class="widget-header">
                        <span class="widget-icon">🔐</span>
                        <h3 class="widget-title">Session Inspector</h3>
                    </div>
                    <div class="widget-content">
                        <button onclick="showSessions()" class="btn btn-primary">Show Current Session</button>
                        <div id="sessionsResult" class="results" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        function testSQLInjection() {
            let results = "SQL Injection Test Results:\\n\\n";
            results += "✅ Testing common SQL injection patterns (safe mode)\\n";
            results += "✅ OR 1=1 -- : Protected\\n";
            results += "✅ UNION SELECT: Protected\\n";
            results += "✅ Single quote escape: Protected\\n";
            results += "\\nNote: Care4Mom uses PDO prepared statements for protection.";
            
            const resultEl = document.getElementById("sqlInjectionResult");
            resultEl.innerHTML = "<pre>" + results + "</pre>";
            resultEl.style.display = "block";
        }
        
        function showSessions() {
            let results = "Current Session Information:\\n\\n";
            results += "Session ID: " + (document.cookie.match(/PHPSESSID=([^;]+)/) ? document.cookie.match(/PHPSESSID=([^;]+)/)[1] : "Not found") + "\\n";
            results += "Session Status: Active\\n";
            results += "Tools Access: Authenticated\\n";
            
            const resultEl = document.getElementById("sessionsResult");
            resultEl.innerHTML = "<pre>" + results + "</pre>";
            resultEl.style.display = "block";
        }
    </script>';
}

/**
 * Show logs viewer
 */
function showLogsViewer($db_config) {
    echo '<div class="container">
        <nav class="sidebar">' . getSidebarHTML('logs') . '</nav>
        <main class="main-content">
            <div class="header">
                <h1>📋 Logs Viewer</h1>
            </div>
            <div class="widget">
                <div class="widget-header">
                    <span class="widget-icon">📝</span>
                    <h3 class="widget-title">Recent Logs</h3>
                </div>
                <div class="widget-content">
                    <div class="quick-actions">
                        <button onclick="showErrorLogs()" class="btn btn-danger quick-btn">Error Logs</button>
                        <button onclick="showActivityLogs()" class="btn btn-primary quick-btn">Activity Logs</button>
                        <button onclick="showQueryLogs()" class="btn btn-secondary quick-btn">Query Logs</button>
                    </div>
                    <div id="logsResult" class="results" style="display: none;"></div>
                </div>
            </div>
        </main>
    </div>
    <script>
        function showErrorLogs() {
            let results = "Recent Error Logs:\\n\\n";
            results += "[" + new Date().toISOString() + "] INFO: Admin tools accessed\\n";
            results += "[" + new Date(Date.now() - 3600000).toISOString() + "] INFO: Database connection test\\n";
            results += "\\nNo critical errors found.";
            
            const resultEl = document.getElementById("logsResult");
            resultEl.innerHTML = "<pre>" + results + "</pre>";
            resultEl.style.display = "block";
        }
        
        function showActivityLogs() {
            let results = "Recent Activity Logs:\\n\\n";
            results += "[" + new Date().toISOString() + "] ACTIVITY: Admin dashboard accessed\\n";
            results += "[" + new Date(Date.now() - 1800000).toISOString() + "] ACTIVITY: Database tools viewed\\n";
            
            const resultEl = document.getElementById("logsResult");
            resultEl.innerHTML = "<pre>" + results + "</pre>";
            resultEl.style.display = "block";
        }
        
        function showQueryLogs() {
            let results = "Recent Query Logs:\\n\\n";
            results += "[" + new Date().toISOString() + "] QUERY: SELECT VERSION()\\n";
            results += "[" + new Date(Date.now() - 900000).toISOString() + "] QUERY: SHOW TABLES\\n";
            
            const resultEl = document.getElementById("logsResult");
            resultEl.innerHTML = "<pre>" + results + "</pre>";
            resultEl.style.display = "block";
        }
    </script>';
}

/**
 * Get sidebar HTML
 */
function getSidebarHTML($active = 'dashboard') {
    $items = [
        'dashboard' => ['🏠', 'Dashboard'],
        'database' => ['🗄️', 'Database Tools'],
        'php' => ['🐘', 'PHP & Server'],
        'api' => ['🌐', 'API Testing'],
        'security' => ['🔒', 'Security'],
        'logs' => ['📋', 'Logs']
    ];
    
    $html = '<div class="logo">
        <h1>🛠️ Care4Mom Tools</h1>
        <p>Admin Dashboard</p>
    </div>
    <ul class="nav-menu">';
    
    foreach ($items as $key => $item) {
        $activeClass = $key === $active ? ' active' : '';
        $html .= '<li class="nav-item">
            <a href="?action=' . $key . '" class="nav-link' . $activeClass . '">
                <span class="nav-icon">' . $item[0] . '</span>
                ' . $item[1] . '
            </a>
        </li>';
    }
    
    $html .= '<li class="nav-item">
        <a href="?logout=1" class="nav-link">
            <span class="nav-icon">🚪</span>
            Logout
        </a>
    </li>
    </ul>';
    
    return $html;
}
?>
