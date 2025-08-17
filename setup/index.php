<?php
// Prevent access if already configured
if (file_exists(__DIR__ . '/../config/config.php')) {
    die('Setup has already been completed. Delete the /setup directory for security.');
}

// Initialize session for setup process
session_start();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = $_POST['step'] ?? 1;
    
    switch ($step) {
        case 1:
            // Database configuration step
            handleDatabaseConfig();
            break;
        case 2:
            // System configuration step
            handleSystemConfig();
            break;
        case 3:
            // Complete setup
            completeSetup();
            break;
    }
}

// Get current step
$currentStep = $_SESSION['setup_step'] ?? 1;

function handleDatabaseConfig() {
    $errors = [];
    
    // Validate database configuration
    $dbHost = trim($_POST['db_host'] ?? '');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';
    $dbPort = intval($_POST['db_port'] ?? 3306);
    
    if (empty($dbHost)) $errors[] = 'Database host is required';
    if (empty($dbName)) $errors[] = 'Database name is required';
    if (empty($dbUser)) $errors[] = 'Database username is required';
    
    if (empty($errors)) {
        // Test database connection
        try {
            $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Store database config in session
            $_SESSION['db_config'] = [
                'host' => $dbHost,
                'database' => $dbName,
                'username' => $dbUser,
                'password' => $dbPass,
                'port' => $dbPort,
                'charset' => 'utf8mb4'
            ];
            
            $_SESSION['setup_step'] = 2;
            $_SESSION['setup_success'] = 'Database connection successful!';
            
        } catch (PDOException $e) {
            $errors[] = 'Database connection failed: ' . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['setup_errors'] = $errors;
    }
}

function handleSystemConfig() {
    $errors = [];
    
    // Validate system configuration
    $appUrl = trim($_POST['app_url'] ?? '');
    $appName = trim($_POST['app_name'] ?? 'Luxury Talent Booking — RCE');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPassword = $_POST['admin_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($appUrl)) $errors[] = 'Application URL is required';
    if (empty($adminEmail)) $errors[] = 'Admin email is required';
    if (empty($adminPassword)) $errors[] = 'Admin password is required';
    if ($adminPassword !== $confirmPassword) $errors[] = 'Passwords do not match';
    if (strlen($adminPassword) < 8) $errors[] = 'Password must be at least 8 characters';
    
    // Validate URL format
    if (!empty($appUrl) && !filter_var($appUrl, FILTER_VALIDATE_URL)) {
        $errors[] = 'Invalid application URL format';
    }
    
    // Validate email format
    if (!empty($adminEmail) && !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid admin email format';
    }
    
    if (empty($errors)) {
        // Store system config in session
        $_SESSION['system_config'] = [
            'app_url' => rtrim($appUrl, '/'),
            'app_name' => $appName,
            'admin_email' => $adminEmail,
            'admin_password' => $adminPassword,
            'timezone' => $_POST['timezone'] ?? 'UTC',
            'environment' => 'production'
        ];
        
        $_SESSION['setup_step'] = 3;
        $_SESSION['setup_success'] = 'System configuration saved!';
    }
    
    if (!empty($errors)) {
        $_SESSION['setup_errors'] = $errors;
    }
}

function completeSetup() {
    try {
        // Create config directory
        $configDir = __DIR__ . '/../config';
        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }
        
        // Create logs directory
        $logsDir = __DIR__ . '/../logs';
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }
        
        // Create uploads directories
        $uploadsDir = __DIR__ . '/../uploads';
        $uploadSubdirs = ['photos', 'videos', 'status', 'norm'];
        
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        
        foreach ($uploadSubdirs as $subdir) {
            $path = $uploadsDir . '/' . $subdir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
        
        // Write configuration files
        writeConfigFiles();
        
        // Import database schema and seeds
        importDatabase();
        
        // Mark setup as complete
        $_SESSION['setup_complete'] = true;
        $_SESSION['setup_success'] = 'Setup completed successfully! You can now use your application.';
        
    } catch (Exception $e) {
        $_SESSION['setup_errors'] = ['Setup failed: ' . $e->getMessage()];
    }
}

function writeConfigFiles() {
    $configDir = __DIR__ . '/../config';
    $dbConfig = $_SESSION['db_config'];
    $systemConfig = $_SESSION['system_config'];
    
    // Write database.php
    $databaseConfig = "<?php\n\nreturn [\n";
    $databaseConfig .= "    'host' => '" . addslashes($dbConfig['host']) . "',\n";
    $databaseConfig .= "    'port' => " . $dbConfig['port'] . ",\n";
    $databaseConfig .= "    'database' => '" . addslashes($dbConfig['database']) . "',\n";
    $databaseConfig .= "    'username' => '" . addslashes($dbConfig['username']) . "',\n";
    $databaseConfig .= "    'password' => '" . addslashes($dbConfig['password']) . "',\n";
    $databaseConfig .= "    'charset' => 'utf8mb4',\n";
    $databaseConfig .= "    'timezone' => '+00:00'\n";
    $databaseConfig .= "];\n";
    
    file_put_contents($configDir . '/database.php', $databaseConfig);
    
    // Write config.php
    $appConfig = "<?php\n\n";
    $appConfig .= "define('APP_NAME', '" . addslashes($systemConfig['app_name']) . "');\n";
    $appConfig .= "define('APP_URL', '" . addslashes($systemConfig['app_url']) . "');\n";
    $appConfig .= "define('APP_ENV', 'production');\n";
    $appConfig .= "define('APP_DEBUG', false);\n";
    $appConfig .= "define('APP_TIMEZONE', '" . addslashes($systemConfig['timezone']) . "');\n";
    $appConfig .= "\n// Set timezone\n";
    $appConfig .= "date_default_timezone_set(APP_TIMEZONE);\n";
    $appConfig .= "\n// Error reporting\n";
    $appConfig .= "if (APP_DEBUG) {\n";
    $appConfig .= "    error_reporting(E_ALL);\n";
    $appConfig .= "    ini_set('display_errors', 1);\n";
    $appConfig .= "} else {\n";
    $appConfig .= "    error_reporting(0);\n";
    $appConfig .= "    ini_set('display_errors', 0);\n";
    $appConfig .= "}\n";
    
    file_put_contents($configDir . '/config.php', $appConfig);
    
    // Write security.php
    $securityConfig = "<?php\n\n";
    $securityConfig .= "define('SECURITY_SALT', '" . bin2hex(random_bytes(32)) . "');\n";
    $securityConfig .= "define('SESSION_LIFETIME', 86400); // 24 hours\n";
    $securityConfig .= "define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour\n";
    $securityConfig .= "\n// Session configuration\n";
    $securityConfig .= "ini_set('session.cookie_httponly', 1);\n";
    $securityConfig .= "ini_set('session.cookie_secure', " . (isset($_SERVER['HTTPS']) ? '1' : '0') . ");\n";
    $securityConfig .= "ini_set('session.use_strict_mode', 1);\n";
    
    file_put_contents($configDir . '/security.php', $securityConfig);
}

function importDatabase() {
    $dbConfig = $_SESSION['db_config'];
    $systemConfig = $_SESSION['system_config'];
    
    // Connect to database
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Import schema
    $schemaFile = __DIR__ . '/../db/schema.sql';
    if (file_exists($schemaFile)) {
        $schema = file_get_contents($schemaFile);
        $statements = explode(';', $schema);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !preg_match('/^\s*--/', $statement)) {
                $pdo->exec($statement);
            }
        }
    }
    
    // Import seeds
    $seedsFile = __DIR__ . '/../db/seeds.sql';
    if (file_exists($seedsFile)) {
        $seeds = file_get_contents($seedsFile);
        $statements = explode(';', $seeds);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !preg_match('/^\s*--/', $statement) && !preg_match('/^\s*\/\*/', $statement)) {
                $pdo->exec($statement);
            }
        }
    }
    
    // Update super admin email if different from default
    if ($systemConfig['admin_email'] !== 'admin@rce-system.com') {
        $stmt = $pdo->prepare("UPDATE users SET email = ?, password_hash = ? WHERE id = 1");
        $stmt->execute([
            $systemConfig['admin_email'],
            password_hash($systemConfig['admin_password'], PASSWORD_DEFAULT)
        ]);
    }
}

// Get messages from session
$errors = $_SESSION['setup_errors'] ?? [];
$success = $_SESSION['setup_success'] ?? '';

// Clear messages
unset($_SESSION['setup_errors'], $_SESSION['setup_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Luxury Talent Booking RCE</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .setup-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
        }
        
        .setup-header {
            background: #000;
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .setup-header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .setup-header p {
            opacity: 0.8;
        }
        
        .setup-progress {
            display: flex;
            background: #f8f9fa;
            padding: 0;
        }
        
        .progress-step {
            flex: 1;
            padding: 1rem;
            text-align: center;
            font-size: 0.9rem;
            font-weight: 500;
            border-right: 1px solid #dee2e6;
        }
        
        .progress-step:last-child {
            border-right: none;
        }
        
        .progress-step.active {
            background: #000;
            color: white;
        }
        
        .progress-step.completed {
            background: #28a745;
            color: white;
        }
        
        .setup-content {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #000;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #000;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .btn:hover {
            background: #333;
            transform: translateY(-1px);
        }
        
        .btn-full {
            width: 100%;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .setup-complete {
            text-align: center;
            padding: 2rem;
        }
        
        .setup-complete h2 {
            color: #28a745;
            margin-bottom: 1rem;
        }
        
        .credentials-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            text-align: left;
        }
        
        .credentials-box h4 {
            margin-bottom: 1rem;
            color: #333;
        }
        
        .credential-item {
            margin-bottom: 0.5rem;
            font-family: monospace;
            font-size: 0.9rem;
        }
        
        .help-text {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1>Luxury Talent Booking — RCE</h1>
            <p>Setup Wizard</p>
        </div>
        
        <div class="setup-progress">
            <div class="progress-step <?php echo $currentStep >= 1 ? ($currentStep > 1 ? 'completed' : 'active') : ''; ?>">
                1. Database
            </div>
            <div class="progress-step <?php echo $currentStep >= 2 ? ($currentStep > 2 ? 'completed' : 'active') : ''; ?>">
                2. System
            </div>
            <div class="progress-step <?php echo $currentStep >= 3 ? 'active' : ''; ?>">
                3. Complete
            </div>
        </div>
        
        <div class="setup-content">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul style="margin: 0; padding-left: 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['setup_complete'])): ?>
                <div class="setup-complete">
                    <h2>🎉 Setup Complete!</h2>
                    <p>Your Luxury Talent Booking application has been successfully configured.</p>
                    
                    <div class="credentials-box">
                        <h4>Demo Login Credentials:</h4>
                        <div class="credential-item"><strong>Super Admin:</strong> superadmin / admin123 (Security Token: rce-admin-2024)</div>
                        <div class="credential-item"><strong>Tenant Admin:</strong> demoadmin / demo123</div>
                        <div class="credential-item"><strong>Client:</strong> eliteprod / client123</div>
                        <div class="credential-item"><strong>Talent:</strong> sarahchen / talent123</div>
                    </div>
                    
                    <p style="margin: 1.5rem 0;">
                        <strong>Important:</strong> For security, please delete the <code>/setup</code> directory now.
                    </p>
                    
                    <a href="/" class="btn">Go to Application</a>
                </div>
                
                <?php
                // Auto-delete setup directory after 30 seconds
                echo "<script>
                    setTimeout(function() {
                        if (confirm('Setup is complete. Delete the setup directory now for security?')) {
                            fetch('/setup/cleanup.php', {method: 'POST'})
                                .then(() => window.location.href = '/')
                                .catch(() => window.location.href = '/');
                        }
                    }, 30000);
                </script>";
                ?>
                
            <?php elseif ($currentStep === 1): ?>
                <h2>Database Configuration</h2>
                <p>Please provide your MySQL database connection details.</p>
                
                <form method="POST">
                    <input type="hidden" name="step" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="db_host">Database Host</label>
                            <input type="text" id="db_host" name="db_host" value="localhost" required>
                        </div>
                        <div class="form-group">
                            <label for="db_port">Port</label>
                            <input type="number" id="db_port" name="db_port" value="3306" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">Database Name</label>
                        <input type="text" id="db_name" name="db_name" required>
                        <div class="help-text">The database must already exist and be empty.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_user">Database Username</label>
                        <input type="text" id="db_user" name="db_user" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_pass">Database Password</label>
                        <input type="password" id="db_pass" name="db_pass">
                    </div>
                    
                    <button type="submit" class="btn btn-full">Test Connection & Continue</button>
                </form>
                
            <?php elseif ($currentStep === 2): ?>
                <h2>System Configuration</h2>
                <p>Configure your application settings and create the super admin account.</p>
                
                <form method="POST">
                    <input type="hidden" name="step" value="2">
                    
                    <div class="form-group">
                        <label for="app_url">Application URL</label>
                        <input type="url" id="app_url" name="app_url" value="<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['REQUEST_URI'])); ?>" required>
                        <div class="help-text">The full URL where your application will be accessible (without trailing slash).</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="app_name">Application Name</label>
                        <input type="text" id="app_name" name="app_name" value="Luxury Talent Booking — RCE" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="timezone">Timezone</label>
                        <select id="timezone" name="timezone">
                            <option value="UTC">UTC</option>
                            <option value="America/New_York">Eastern Time</option>
                            <option value="America/Chicago">Central Time</option>
                            <option value="America/Denver">Mountain Time</option>
                            <option value="America/Los_Angeles" selected>Pacific Time</option>
                            <option value="Europe/London">London</option>
                            <option value="Europe/Paris">Paris</option>
                            <option value="Asia/Tokyo">Tokyo</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_email">Super Admin Email</label>
                        <input type="email" id="admin_email" name="admin_email" required>
                        <div class="help-text">This will be your super admin login email.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_password">Super Admin Password</label>
                        <input type="password" id="admin_password" name="admin_password" required minlength="8">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                    </div>
                    
                    <button type="submit" class="btn btn-full">Save Configuration & Continue</button>
                </form>
                
            <?php elseif ($currentStep === 3): ?>
                <h2>Complete Setup</h2>
                <p>Ready to complete the installation. This will:</p>
                
                <ul style="margin: 1.5rem 0; padding-left: 2rem;">
                    <li>Create configuration files</li>
                    <li>Import database schema and sample data</li>
                    <li>Set up directory structure</li>
                    <li>Create your super admin account</li>
                </ul>
                
                <form method="POST">
                    <input type="hidden" name="step" value="3">
                    <button type="submit" class="btn btn-full">Complete Setup</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
