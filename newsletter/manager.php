<?php

session_start();

if (!file_exists('hash.php')) {
    header('Location: settings.php');
    exit;
}

$admin_accounts = include('hash.php');

if (!isset($_SESSION['newsletter_admin'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        if (isset($admin_accounts[$username]) && password_verify($password, $admin_accounts[$username]['password_hash'])) {
            $_SESSION['newsletter_admin'] = true;
            $_SESSION['admin_username'] = $username;
            header('Location: manager.php');
            exit;
        } else {
            $login_error = 'Invalid username or password';
        }
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Newsletter Manager - Login</title>
        <link rel="icon" href="nl.ico?v=<?= filemtime(__DIR__ . '/newsletter/nl.ico') ?>" type="image/x-icon">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            
            :root {
                --bg: #0a0a0a;
                --bg-elevated: #111;
                --bg-card: #161616;
                --bg-hover: #1c1c1c;
                --border: #262626;
                --border-focus: #404040;
                --text: #fafafa;
                --text-muted: #737373;
                --text-subtle: #525252;
                --accent: #fff;
                --success: #22c55e;
                --error: #ef4444;
                --blue: #3b82f6;
                --radius: 12px;
                --radius-sm: 8px;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: var(--bg);
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 20px;
            }
            
            .login-box {
                background: var(--bg-card);
                border: 1px solid var(--border);
                border-radius: var(--radius);
                padding: 40px;
                max-width: 400px;
                width: 100%;
            }
            
            .icon-lock {
                width: 48px;
                height: 48px;
                margin: 0 auto 20px;
                display: block;
            }
            
            h1 { 
                color: var(--text); 
                margin-bottom: 10px; 
                font-size: 24px;
                text-align: center;
            }
            
            p { 
                color: var(--text-muted); 
                margin-bottom: 30px; 
                font-size: 14px;
                text-align: center;
            }
            
            input[type="text"],
            input[type="password"] {
                width: 100%;
                padding: 12px;
                border: 1px solid var(--border);
                background: var(--bg-elevated);
                color: var(--text);
                border-radius: var(--radius-sm);
                font-size: 16px;
                margin-bottom: 20px;
                transition: border-color 0.3s;
            }
            
            input[type="text"]:focus,
            input[type="password"]:focus {
                outline: none;
                border-color: var(--border-focus);
            }
            
            button {
                width: 100%;
                padding: 12px;
                background: var(--accent);
                color: var(--bg);
                border: none;
                border-radius: var(--radius-sm);
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: opacity 0.2s;
            }
            
            button:hover { opacity: 0.9; }
            
            .error {
                background: rgba(239, 68, 68, 0.1);
                color: var(--error);
                border: 1px solid rgba(239, 68, 68, 0.2);
                padding: 12px;
                border-radius: var(--radius-sm);
                margin-bottom: 20px;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <svg class="icon-lock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-muted);">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            <h1>Newsletter Manager</h1>
            <p>Enter your credentials to access the admin panel</p>
            <?php if (isset($login_error)): ?>
                <div class="error"><?= htmlspecialchars($login_error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required autofocus>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: manager.php');
    exit;
}

// === CHECK IF CONFIG EXISTS (ONLY WHEN LOGGED IN) ===
if (!file_exists('config.php')) {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['enable_newsletter'])) {

            $default_config = "<?php
// config.php - Configuration

// === DATABASE SETTINGS ===
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database');      // CHANGE THIS
define('DB_USER', 'your_user');          // CHANGE THIS
define('DB_PASS', 'your_password');      // CHANGE THIS

// === EMAIL SETTINGS ===
define('SENDER_EMAIL', 'newsletter@yoursite.com');  // CHANGE THIS
define('SENDER_NAME', 'Your Company');              // CHANGE THIS
define('SITE_URL', 'https://yoursite.com');         // CHANGE THIS

// === GENERAL SETTINGS ===
define('REQUIRE_CONFIRMATION', true);  // require email confirmation?

// Database connection function
function getDB() {
    static \$pdo = null;
    
    if (\$pdo === null) {
        try {
            \$pdo = new PDO(
                \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException \$e) {
            error_log(\"Database connection failed: \" . \$e->getMessage());
            return null;
        }
    }
    
    return \$pdo;
}

// Safe JSON response function
function jsonResponse(\$success, \$message) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => \$success,
        'message' => \$message
    ]);
    exit;
}
?>";
            
            if (file_put_contents('config.php', $default_config)) {
                header('Location: manager.php');
                exit;
            } else {
                $setup_error = 'Failed to create config.php. Please check file permissions.';
            }
        } elseif (isset($_POST['disable_newsletter'])) {

            session_destroy();
            header('Location: /admin/');
            exit;
        }
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Enable Newsletter Feature</title>
        <link rel="icon" href="nl.ico?v=<?= filemtime(__DIR__ . '/nl.ico') ?>" type="image/x-icon">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            
            :root {
                --bg: #0a0a0a;
                --bg-elevated: #111;
                --bg-card: #161616;
                --bg-hover: #1c1c1c;
                --border: #262626;
                --border-focus: #404040;
                --text: #fafafa;
                --text-muted: #737373;
                --text-subtle: #525252;
                --accent: #fff;
                --success: #22c55e;
                --error: #ef4444;
                --blue: #3b82f6;
                --radius: 12px;
                --radius-sm: 8px;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: var(--bg);
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 20px;
            }
            
            .prompt-box {
                background: var(--bg-card);
                border: 1px solid var(--border);
                border-radius: var(--radius);
                padding: 40px;
                max-width: 500px;
                width: 100%;
            }
            
            .icon-mail {
                width: 64px;
                height: 64px;
                margin: 0 auto 20px;
                display: block;
                color: var(--text-muted);
            }
            
            h1 { 
                color: var(--text); 
                margin-bottom: 10px; 
                font-size: 24px;
                text-align: center;
            }
            
            p { 
                color: var(--text-muted); 
                margin-bottom: 30px; 
                font-size: 14px;
                text-align: center;
                line-height: 1.6;
            }
            
            .button-group {
                display: flex;
                gap: 12px;
                flex-direction: column;
            }
            
            button {
                width: 100%;
                padding: 14px;
                border: none;
                border-radius: var(--radius-sm);
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: opacity 0.2s;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }
            
            button:hover { opacity: 0.9; }
            
            .btn-primary {
                background: var(--success);
                color: white;
            }
            
            .btn-secondary {
                background: var(--bg-hover);
                color: var(--text);
                border: 1px solid var(--border);
            }
            
            .error {
                background: rgba(239, 68, 68, 0.1);
                color: var(--error);
                border: 1px solid rgba(239, 68, 68, 0.2);
                padding: 12px;
                border-radius: var(--radius-sm);
                margin-bottom: 20px;
                font-size: 14px;
                text-align: center;
            }
            
            .features {
                background: var(--bg-elevated);
                border: 1px solid var(--border);
                border-radius: var(--radius-sm);
                padding: 20px;
                margin-bottom: 30px;
            }
            
            .features h3 {
                color: var(--text);
                font-size: 14px;
                margin-bottom: 12px;
                font-weight: 600;
            }
            
            .features ul {
                list-style: none;
                color: var(--text-muted);
                font-size: 13px;
                line-height: 1.8;
            }
            
            .features li::before {
                content: "✓ ";
                color: var(--success);
                font-weight: bold;
                margin-right: 8px;
            }
            
            .logout-link {
                text-align: center;
                margin-top: 20px;
            }
            
            .logout-link a {
                color: var(--text-muted);
                text-decoration: none;
                font-size: 14px;
            }
            
            .logout-link a:hover {
                color: var(--text);
            }
        </style>
    </head>
    <body>
        <div class="prompt-box">
            <svg class="icon-mail" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
            
            <h1>Enable Newsletter Feature?</h1>
            <p>Would you like to enable the newsletter subscription system for your website?</p>
            
            <?php if (isset($setup_error)): ?>
                <div class="error"><?= htmlspecialchars($setup_error) ?></div>
            <?php endif; ?>
            
            <div class="features">
                <h3>This will enable:</h3>
                <ul>
                    <li>Email subscription management</li>
                    <li>Double opt-in confirmation</li>
                    <li>Subscriber database</li>
                    <li>CSV export functionality</li>
                    <li>Admin management panel</li>
                </ul>
            </div>
            
            <form method="POST" class="button-group">
                <button type="submit" name="enable_newsletter" class="btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Yes, Enable Newsletter
                </button>
                <button type="submit" name="disable_newsletter" class="btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    No, Return to Admin
                </button>
            </form>
            
            <div class="logout-link">
                <a href="?logout">Logout</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

require_once 'config.php';

// === AUTO-SETUP DATABASE ===
if (isset($_GET['setup'])) {
    $pdo = getDB();
    if ($pdo) {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `email` varchar(255) NOT NULL,
                  `token` varchar(64) NOT NULL,
                  `confirmed` tinyint(1) DEFAULT 0,
                  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                  `confirmed_at` timestamp NULL DEFAULT NULL,
                  `ip_address` varchar(45) DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `email` (`email`),
                  KEY `idx_token` (`token`),
                  KEY `idx_confirmed` (`confirmed`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $setup_message = 'Database table created successfully!';
            $setup_success = true;
        } catch (PDOException $e) {
            $setup_message = 'Error: ' . $e->getMessage();
            $setup_success = false;
        }
    } else {
        $setup_message = 'Database connection failed. Check config.php';
        $setup_success = false;
    }
}

// === UPDATE CONFIG ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_config'])) {
    $config_content = "<?php
// config.php - Configuration

// === DATABASE SETTINGS ===
define('DB_HOST', '" . addslashes($_POST['db_host']) . "');
define('DB_NAME', '" . addslashes($_POST['db_name']) . "');
define('DB_USER', '" . addslashes($_POST['db_user']) . "');
define('DB_PASS', '" . addslashes($_POST['db_pass']) . "');

// === EMAIL SETTINGS ===
define('SENDER_EMAIL', '" . addslashes($_POST['sender_email']) . "');
define('SENDER_NAME', '" . addslashes($_POST['sender_name']) . "');
define('SITE_URL', '" . addslashes($_POST['site_url']) . "');

// === GENERAL SETTINGS ===
define('REQUIRE_CONFIRMATION', " . (isset($_POST['require_confirmation']) ? 'true' : 'false') . ");

// Database connection function
function getDB() {
    static \$pdo = null;
    
    if (\$pdo === null) {
        try {
            \$pdo = new PDO(
                \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException \$e) {
            error_log(\"Database connection failed: \" . \$e->getMessage());
            return null;
        }
    }
    
    return \$pdo;
}

// Safe JSON response function
function jsonResponse(\$success, \$message) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => \$success,
        'message' => \$message
    ]);
    exit;
}
?>";

    if (file_put_contents('config.php', $config_content)) {
        $config_message = 'Configuration saved successfully!';
        $config_success = true;
        // Reload config
        require_once 'config.php';
    } else {
        $config_message = 'Error saving config.php (check permissions)';
        $config_success = false;
    }
}

// === DELETE SUBSCRIBER ===
if (isset($_GET['delete'])) {
    $pdo = getDB();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
            $stmt->execute([$_GET['delete']]);
            $delete_message = 'Subscriber deleted';
        } catch (PDOException $e) {
            $delete_message = 'Error deleting subscriber';
        }
    }
}

// === EXPORT CSV ===
if (isset($_GET['export'])) {
    $pdo = getDB();
    if ($pdo) {
        $stmt = $pdo->query("SELECT email, confirmed, created_at, confirmed_at FROM newsletter_subscribers ORDER BY created_at DESC");
        $subscribers = $stmt->fetchAll();
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="newsletter-subscribers-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Email', 'Confirmed', 'Created At', 'Confirmed At']);
        
        foreach ($subscribers as $sub) {
            fputcsv($output, [
                $sub['email'],
                $sub['confirmed'] ? 'Yes' : 'No',
                $sub['created_at'],
                $sub['confirmed_at'] ?? 'N/A'
            ]);
        }
        
        fclose($output);
        exit;
    }
}

// === GET SUBSCRIBERS ===
$pdo = getDB();
$subscribers = [];
$stats = ['total' => 0, 'confirmed' => 0, 'pending' => 0];

if ($pdo) {
    try {
        // Get stats
        $stmt = $pdo->query("SELECT 
            COUNT(*) as total,
            SUM(confirmed = 1) as confirmed,
            SUM(confirmed = 0) as pending
            FROM newsletter_subscribers
        ");
        $stats = $stmt->fetch();
        
        // Get subscribers
        $stmt = $pdo->query("SELECT * FROM newsletter_subscribers ORDER BY created_at DESC LIMIT 100");
        $subscribers = $stmt->fetchAll();
    } catch (PDOException $e) {
        $db_error = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --bg: #0a0a0a;
            --bg-elevated: #111;
            --bg-card: #161616;
            --bg-hover: #1c1c1c;
            --border: #262626;
            --border-focus: #404040;
            --text: #fafafa;
            --text-muted: #737373;
            --text-subtle: #525252;
            --accent: #fff;
            --success: #22c55e;
            --error: #ef4444;
            --blue: #3b82f6;
            --radius: 12px;
            --radius-sm: 8px;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: var(--bg-card);
            border: 1px solid var(--border);
            padding: 30px;
            border-radius: var(--radius);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-title svg {
            width: 32px;
            height: 32px;
        }
        
        .header h1 { font-size: 28px; }
        
        .logout-btn {
            background: var(--bg-hover);
            color: var(--text);
            padding: 10px 20px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .logout-btn:hover { background: var(--bg-elevated); }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            padding: 25px;
            border-radius: var(--radius);
        }
        
        .stat-card h3 {
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .stat-card .number {
            font-size: 36px;
            font-weight: 700;
            color: var(--text);
        }
        
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .card h2 {
            margin-bottom: 20px;
            color: var(--text);
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card h2 svg {
            width: 24px;
            height: 24px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success);
            border-color: rgba(34, 197, 94, 0.2);
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
            border-color: rgba(239, 68, 68, 0.2);
        }
        
        .alert-warning {
            background: rgba(234, 179, 8, 0.1);
            color: #eab308;
            border-color: rgba(234, 179, 8, 0.2);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text);
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="url"] {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            background: var(--bg-elevated);
            color: var(--text);
            border-radius: var(--radius-sm);
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--border-focus);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--accent);
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: opacity 0.2s;
        }
        
        .btn:hover { opacity: 0.9; }
        
        .btn svg {
            width: 16px;
            height: 16px;
        }
        
        .btn-primary {
            background: var(--accent);
            color: var(--bg);
        }
        
        .btn-success {
            background: var(--success);
            color: var(--bg);
        }
        
        .btn-danger {
            background: var(--error);
            color: white;
        }
        
        .btn-secondary {
            background: var(--bg-hover);
            border: 1px solid var(--border);
            color: var(--text);
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        table th {
            background: var(--bg-elevated);
            font-weight: 600;
            font-size: 14px;
            color: var(--text);
        }
        
        table td {
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success);
        }
        
        .badge-warning {
            background: rgba(234, 179, 8, 0.1);
            color: #eab308;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        code {
            background: var(--bg-elevated);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: var(--text-muted);
        }
        
        ol, ul {
            color: var(--text-muted);
        }
        
        @media (max-width: 768px) {
            .grid-2 { grid-template-columns: 1fr; }
            .stats { grid-template-columns: 1fr; }
            .header {
                flex-direction: column;
                gap: 20px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
                <h1>Newsletter Manager</h1>
            </div>
            <a href="?logout" class="logout-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px;">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Logout
            </a>
        </div>
        
        <?php if (isset($setup_message)): ?>
            <div class="alert alert-<?= $setup_success ? 'success' : 'error' ?>">
                <?= htmlspecialchars($setup_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($config_message)): ?>
            <div class="alert alert-<?= $config_success ? 'success' : 'error' ?>">
                <?= htmlspecialchars($config_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($delete_message)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($delete_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($db_error)): ?>
            <div class="alert alert-error">
                Database Error: <?= htmlspecialchars($db_error) ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <h3>Total Subscribers</h3>
                <div class="number"><?= number_format($stats['total'] ?? 0) ?></div>
            </div>
            <div class="stat-card">
                <h3>Confirmed</h3>
                <div class="number" style="color: var(--success);"><?= number_format($stats['confirmed'] ?? 0) ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending</h3>
                <div class="number" style="color: #eab308;"><?= number_format($stats['pending'] ?? 0) ?></div>
            </div>
        </div>
        
        <!-- Quick Setup -->
        <?php if (!$pdo || empty($subscribers)): ?>
        <div class="card">
            <h2>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Quick Setup
            </h2>
            <div class="alert alert-warning">
                First time here? Click the button below to automatically create the database table.
            </div>
            <a href="?setup" class="btn btn-success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                Setup Database Table
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Configuration -->
        <div class="card">
            <h2>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M12 1v6m0 6v6m5.66-13.66l-4.24 4.24m0 6l-4.24 4.24m11.66-5.66l-6 0m-6 0l-6 0m13.66 5.66l-4.24-4.24m0-6l-4.24-4.24"></path>
                </svg>
                Configuration
            </h2>
            <form method="POST">
                <div class="grid-2">
                    <div>
                        <h3 style="margin-bottom: 15px; color: var(--text-muted); font-size: 16px;">Database Settings</h3>
                        <div class="form-group">
                            <label>Database Host</label>
                            <input type="text" name="db_host" value="<?= htmlspecialchars(DB_HOST) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Database Name</label>
                            <input type="text" name="db_name" value="<?= htmlspecialchars(DB_NAME) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Database User</label>
                            <input type="text" name="db_user" value="<?= htmlspecialchars(DB_USER) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Database Password</label>
                            <input type="password" name="db_pass" value="<?= htmlspecialchars(DB_PASS) ?>" required>
                        </div>
                    </div>
                    
                    <div>
                        <h3 style="margin-bottom: 15px; color: var(--text-muted); font-size: 16px;">Email Settings</h3>
                        <div class="form-group">
                            <label>Sender Email</label>
                            <input type="email" name="sender_email" value="<?= htmlspecialchars(SENDER_EMAIL) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Sender Name</label>
                            <input type="text" name="sender_name" value="<?= htmlspecialchars(SENDER_NAME) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Site URL</label>
                            <input type="url" name="site_url" value="<?= htmlspecialchars(SITE_URL) ?>" required>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="require_confirmation" id="require_confirmation" <?= REQUIRE_CONFIRMATION ? 'checked' : '' ?>>
                                <label for="require_confirmation" style="margin: 0;">Require Email Confirmation</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="update_config" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Save Configuration
                </button>
            </form>
        </div>
        
        <!-- Subscribers List -->
        <div class="card">
            <h2>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                    <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                </svg>
                Subscribers (<?= count($subscribers) ?>)
            </h2>
            
            <div class="actions">
                <a href="?export" class="btn btn-secondary btn-sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Export CSV
                </a>
            </div>
            
            <?php if (empty($subscribers)): ?>
                <div class="alert alert-warning">
                    No subscribers yet. Add the newsletter form to your website to start collecting emails!
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Subscribed</th>
                                <th>Confirmed</th>
                                <th>IP</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscribers as $sub): ?>
                                <tr>
                                    <td><?= htmlspecialchars($sub['email']) ?></td>
                                    <td>
                                        <?php if ($sub['confirmed']): ?>
                                            <span class="badge badge-success">Confirmed</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('Y-m-d H:i', strtotime($sub['created_at'])) ?></td>
                                    <td><?= $sub['confirmed_at'] ? date('Y-m-d H:i', strtotime($sub['confirmed_at'])) : '-' ?></td>
                                    <td><?= htmlspecialchars($sub['ip_address'] ?? 'N/A') ?></td>
                                    <td>
                                        <a href="?delete=<?= $sub['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this subscriber?')">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Documentation -->
        <div class="card">
            <h2>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
                Quick Guide
            </h2>
            <ol style="line-height: 2; color: var(--text-muted);">
                <li>Configure database and email settings above</li>
                <li>Click "Setup Database Table" if not done yet</li>
                <li>Add newsletter form to your website (it's already configured in your CMS)</li>
                <li>Monitor subscribers here</li>
                <li>Export CSV to use with email marketing tools</li>
            </ol>
            
            <h3 style="margin-top: 20px; margin-bottom: 10px; color: var(--text);">Important Files:</h3>
            <ul style="line-height: 2; color: var(--text-muted);">
                <li><code>/newsletter/config.php</code> - Configuration (auto-managed by this panel)</li>
                <li><code>/newsletter/subscribe.php</code> - Handles subscriptions</li>
                <li><code>/newsletter/confirm.php</code> - Email confirmation page</li>
                <li><code>/newsletter/unsubscribe.php</code> - Unsubscribe page</li>
                <li><code>/newsletter/manager.php</code> - This admin panel</li>
            </ul>
        
        </div>
    </div>
</body>
</html>