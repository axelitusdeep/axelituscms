<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/login_tracking.php";

require_login();

$message = '';
$message_type = '';
$loginTracker = new LoginTracker();

$loginTracker->updateActivity();

$adminFile = DATA_DIR . '/admin.json';
$adminData = json_decode(file_get_contents($adminFile), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($token)) {
        $message = 'Invalid request. Please try again.';
        $message_type = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'logout_session':
                $loginId = $_POST['login_id'] ?? '';
                if ($loginTracker->logoutSession($loginId)) {
                    $message = 'Session logged out successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to logout session.';
                    $message_type = 'error';
                }
                break;
                
            case 'ban_ip':
                $ipToBan = $_POST['ip_address'] ?? '';
                if ($loginTracker->banIP($ipToBan)) {
                    $message = 'IP address banned and all sessions terminated.';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to ban IP address.';
                    $message_type = 'error';
                }
                break;
                
            case 'unban_ip':
                $ipToUnban = $_POST['ip_address'] ?? '';
                if ($loginTracker->unbanIP($ipToUnban)) {
                    $message = 'IP address unbanned successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to unban IP address.';
                    $message_type = 'error';
                }
                break;
                
            case 'clean_old_sessions':
                if ($loginTracker->cleanOldSessions(30)) {
                    $message = 'Old sessions cleaned successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to clean old sessions.';
                    $message_type = 'error';
                }
                break;
                
            case 'update_username':
                $newUsername = trim($_POST['new_username'] ?? '');
                $currentPassword = $_POST['current_password'] ?? '';
                
                if (empty($newUsername)) {
                    $message = 'Username cannot be empty.';
                    $message_type = 'error';
                } elseif (!password_verify($currentPassword, $adminData['password'])) {
                    $message = 'Current password is incorrect.';
                    $message_type = 'error';
                } else {
                    $adminData['username'] = $newUsername;
                    $adminData['updated_at'] = date('Y-m-d H:i:s');
                    
                    if (file_put_contents($adminFile, json_encode($adminData, JSON_PRETTY_PRINT))) {
                        $_SESSION['admin_username'] = $newUsername;
                        $message = 'Username updated successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to update username.';
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'change_password':
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                if (empty($currentPassword) || empty($newPassword)) {
                    $message = 'Please fill in all password fields.';
                    $message_type = 'error';
                } elseif (!password_verify($currentPassword, $adminData['password'])) {
                    $message = 'Current password is incorrect.';
                    $message_type = 'error';
                } elseif (strlen($newPassword) < 8) {
                    $message = 'New password must be at least 8 characters.';
                    $message_type = 'error';
                } elseif ($newPassword !== $confirmPassword) {
                    $message = 'New passwords do not match.';
                    $message_type = 'error';
                } else {
                    $adminData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                    $adminData['updated_at'] = date('Y-m-d H:i:s');
                    
                    if (file_put_contents($adminFile, json_encode($adminData, JSON_PRETTY_PRINT))) {
                        $message = 'Password changed successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to change password.';
                        $message_type = 'error';
                    }
                }
                break;

            case 'update_email':
                $newEmail = strtolower(trim($_POST['new_email'] ?? ''));
                $currentPassword = $_POST['current_password'] ?? '';
                
                if ($newEmail !== '' && !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                    $message = 'Please enter a valid email address.';
                    $message_type = 'error';
                } elseif (!password_verify($currentPassword, $adminData['password'])) {
                    $message = 'Current password is incorrect.';
                    $message_type = 'error';
                } else {
                    $adminData['email'] = $newEmail;
                    $adminData['updated_at'] = date('Y-m-d H:i:s');
                    
                    if (file_put_contents($adminFile, json_encode($adminData, JSON_PRETTY_PRINT))) {
                        $message = 'Email updated successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to update email.';
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'logout':
                if (isset($_SESSION['login_token'])) {
                    $activeLogins = $loginTracker->getActiveLogins();
                    foreach ($activeLogins as $login) {
                        if ($login['is_current']) {
                            $loginTracker->logoutSession($login['id']);
                            break;
                        }
                    }
                }
                
                $_SESSION = array();
                
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }
                
                session_destroy();
                header('Location: /admin');
                exit;
                break;
        }
    }
}

$csrf_token = generate_csrf_token();
$site_settings = get_site_settings();
$activeLogins = $loginTracker->getActiveLogins();
$bannedIPs = $loginTracker->getBannedIPs();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - <?= e($site_settings['site_name'] ?? SITE_NAME) ?></title>
    <link rel="icon" href="admin.ico?v=<?= filemtime(__DIR__ . '/../css/admin.css') ?>" type="image/x-icon">
    <link rel="preload" href="/assets/fonts/inter/inter.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/css/admin.css?v=<?= filemtime(__DIR__ . '/../css/admin.css') ?>">
    <script src="/assets/js/admin-theme.js?v=2"></script>
    <style>
        .session-card {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
        }
        .session-card.current {
            border-color: var(--primary);
            background: var(--primary-light, rgba(59, 130, 246, 0.05));
        }
        .session-info {
            display: flex;
            align-items: start;
            gap: 12px;
        }
        .session-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .session-details {
            flex: 1;
        }
        .session-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-current {
            background: var(--primary);
        }
        .badge-info {
            background: var(--bg);
            border: 1px solid var(--border);
            color: var(--text-muted);
        }
        .ip-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="app">
        <!-- Sidebar -->
        <aside class="sidebar">
            <button class="mobile-nav-toggle" onclick="document.querySelector('.sidebar').classList.remove('open')">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="6" y1="6" x2="18" y2="18"/>
                    <line x1="6" y1="18" x2="18" y2="6"/>
                </svg>
            </button>

            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <img src="/logos/logo.webp" alt="Logo" style="width: 20px;">
                    <span>AxElitus CMS</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-label">Content</div>
                    <a href="/admin/dashboard.php?tab=index" class="nav-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        Homepage
                    </a>
                    <a href="/admin/dashboard.php?tab=products" class="nav-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                        Pages
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-label">Site</div>
                    <a href="/admin/dashboard.php?tab=settings" class="nav-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        Settings
                    </a>
                    <a href="themes.php" class="nav-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7h-3a2 2 0 0 1-2-2V2"/><path d="M9 18a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h7l4 4v10a2 2 0 0 1-2 2Z"/><path d="M3 7.6v12.8A1.6 1.6 0 0 0 4.6 22h9.8"/></svg>
                        Themes
                    </a>
                    <a href="extensions.php" class="nav-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        Extensions
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-label">Quick Actions</div>
                    <a href="/admin/dashboard.php?tab=new" class="nav-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                        New Page
                    </a>
                    <a href="/" target="_blank" class="nav-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        View Site
                    </a>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <a href="/admin/account.php" class="user-menu active">
                    <div class="user-avatar">
                        <?= strtoupper(substr($adminData['username'], 0, 1)) ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name">
                            <?= e($adminData['username']) ?>
                        </div>
                        <div class="user-role">Account Settings</div>
                    </div>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main">
            <header class="main-header">
                <button class="mobile-nav-toggle" onclick="document.querySelector('.sidebar').classList.add('open')">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>

                <div class="main-header-inner">
                    <h1>Account Settings</h1>
                    <div class="header-actions">
                        <button type="button" onclick="toggleTheme()" class="btn btn-secondary btn-sm" id="theme-toggle-btn">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                            </svg>
                            <span>Theme</span>
                        </button>
                        <a href="/admin/dashboard.php" class="btn btn-secondary btn-sm">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><path d="M19 12H5"/><polyline points="12 19 5 12 12 5"/></svg>
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            </header>
            
            <div class="main-content">
                <?php if ($message): ?>
                    <div class="message <?= $message_type ?>">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="20" height="20">
                            <?php if ($message_type === 'success'): ?>
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                            <?php else: ?>
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            <?php endif; ?>
                        </svg>
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>

                <div style="max-width: 1200px;">
                    <!-- Account Info Card -->
                    <div class="card" style="margin-bottom: 24px;">
                        <div class="card-header">
                            <h3 class="card-title">Account Information</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: flex; align-items: center; gap: 20px; padding: 20px; background: var(--bg); border-radius: 12px; border: 1px solid var(--border);">
                                <div class="user-avatar" style="width: 64px; height: 64px; font-size: 24px;">
                                    <?= strtoupper(substr($adminData['username'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 1.125rem; margin-bottom: 4px;">
                                        <?= e($adminData['username']) ?>
                                    </div>
                                    <div style="color: var(--text-muted); font-size: 0.875rem;">
                                        Administrator
                                    </div>
                                    <?php if (isset($adminData['created_at'])): ?>
                                        <div style="color: var(--text-muted); font-size: 0.8125rem; margin-top: 8px;">
                                            Account created: <?= date('F j, Y', strtotime($adminData['created_at'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Sessions Card -->
                    <div class="card" style="margin-bottom: 24px;">
                        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 class="card-title">Active Sessions (<?= count($activeLogins) ?>)</h3>
                            <form method="POST" action="" style="margin: 0;">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                <input type="hidden" name="action" value="clean_old_sessions">
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16">
                                        <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                                    </svg>
                                    Clean Old Sessions
                                </button>
                            </form>
                        </div>
                        <div class="card-body">
                            <?php if (empty($activeLogins)): ?>
                                <p style="color: var(--text-muted); text-align: center; padding: 20px;">No active sessions</p>
                            <?php else: ?>
                                <?php foreach ($activeLogins as $login): ?>
                                    <div class="session-card <?= $login['is_current'] ? 'current' : '' ?>">
                                        <div class="session-info">
                                            <div class="session-icon">
                                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="20" height="20">
                                                    <?php if (stripos($login['platform'], 'windows') !== false): ?>
                                                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                                                        <line x1="8" y1="21" x2="16" y2="21"/>
                                                        <line x1="12" y1="17" x2="12" y2="21"/>
                                                    <?php elseif (stripos($login['platform'], 'ios') !== false || stripos($login['platform'], 'android') !== false): ?>
                                                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                                        <line x1="12" y1="18" x2="12.01" y2="18"/>
                                                    <?php else: ?>
                                                        <rect x="2" y="7" width="20" height="15" rx="2" ry="2"/>
                                                        <polyline points="17 2 12 7 7 2"/>
                                                    <?php endif; ?>
                                                </svg>
                                            </div>
                                            <div class="session-details">
                                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                                    <strong><?= e($login['platform']) ?> • <?= e($login['browser']) ?></strong>
                                                    <?php if ($login['is_current']): ?>
                                                        <span class="badge badge-current">Current Session</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 4px;">
                                                    <strong>IP:</strong> <?= e($login['ip']) ?>
                                                </div>
                                                <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 4px;">
                                                    <strong>Language:</strong> <?= e($login['language']) ?>
                                                </div>
                                                <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 4px;">
                                                    <strong>Login:</strong> <?= date('M j, Y g:i A', strtotime($login['login_time'])) ?>
                                                </div>
                                                <div style="font-size: 0.875rem; color: var(--text-muted);">
                                                    <strong>Last Activity:</strong> <?= date('M j, Y g:i A', strtotime($login['last_activity'])) ?>
                                                </div>
                                                
                                                <div class="session-actions">
                                                    <?php if (!$login['is_current']): ?>
                                                        <form method="POST" action="" style="margin: 0; display: inline-block;">
                                                            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                                            <input type="hidden" name="action" value="logout_session">
                                                            <input type="hidden" name="login_id" value="<?= e($login['id']) ?>">
                                                            <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Logout this session?')">
                                                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="14" height="14">
                                                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                                                                </svg>
                                                                Logout
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <form method="POST" action="" style="margin: 0; display: inline-block;">
                                                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                                        <input type="hidden" name="action" value="ban_ip">
                                                        <input type="hidden" name="ip_address" value="<?= e($login['ip']) ?>">
                                                        <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Ban this IP address? All sessions from this IP will be terminated.')">
                                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="14" height="14">
                                                                <circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                                                            </svg>
                                                            Ban IP
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Banned IPs Card -->
                    <?php if (!empty($bannedIPs)): ?>
                        <div class="card" style="margin-bottom: 24px;">
                            <div class="card-header">
                                <h3 class="card-title">Banned IP Addresses (<?= count($bannedIPs) ?>)</h3>
                            </div>
                            <div class="card-body">
                                <?php foreach ($bannedIPs as $ip): ?>
                                    <div class="ip-item">
                                        <div>
                                            <div style="font-weight: 500; font-family: 'Courier New', monospace;"><?= e($ip) ?></div>
                                            <div style="font-size: 0.875rem; color: var(--text-muted); margin-top: 4px;">
                                                All access denied from this IP
                                            </div>
                                        </div>
                                        <form method="POST" action="" style="margin: 0;">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                            <input type="hidden" name="action" value="unban_ip">
                                            <input type="hidden" name="ip_address" value="<?= e($ip) ?>">
                                            <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Unban this IP address?')">
                                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="14" height="14">
                                                    <polyline points="20 6 9 17 4 12"/>
                                                </svg>
                                                Unban
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Change Username Card -->
                    <div class="card" style="margin-bottom: 24px;">
                        <div class="card-header">
                            <h3 class="card-title">Change Username</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                <input type="hidden" name="action" value="update_username">
                                
                                <div class="form-group">
                                    <label class="form-label">New Username</label>
                                    <input type="text" 
                                           name="new_username" 
                                           class="form-input" 
                                           value="<?= e($adminData['username']) ?>"
                                           required
                                           autocomplete="username">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Current Password (to confirm)</label>
                                    <input type="password" 
                                           name="current_password" 
                                           class="form-input" 
                                           required
                                           autocomplete="current-password"
                                           placeholder="Enter your current password">
                                </div>
                                
                                <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                                    <button type="submit" class="btn btn-primary">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                        Update Username
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Change Email Card -->
                    <div class="card" style="margin-bottom: 24px;">
                        <div class="card-header">
                            <h3 class="card-title">Change Email</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                <input type="hidden" name="action" value="update_email">
                                
                                <div class="form-group">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" 
                                        name="new_email" 
                                        class="form-input" 
                                        value="<?= e($adminData['email'] ?? '') ?>"
                                        autocomplete="email"
                                        placeholder="you@example.com">
                                    <p class="form-hint">Used for login security alerts</p>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Current Password (to confirm)</label>
                                    <input type="password" 
                                        name="current_password" 
                                        class="form-input" 
                                        required
                                        autocomplete="current-password"
                                        placeholder="Enter your current password">
                                </div>
                                
                                <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                                    <button type="submit" class="btn btn-primary">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                        Update Email
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password Card -->
                    <div class="card" style="margin-bottom: 24px;">
                        <div class="card-header">
                            <h3 class="card-title">Change Password</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="form-group">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" 
                                           name="current_password" 
                                           class="form-input" 
                                           required
                                           autocomplete="current-password"
                                           placeholder="Enter your current password">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">New Password</label>
                                    <input type="password" 
                                           name="new_password" 
                                           class="form-input" 
                                           required
                                           minlength="8"
                                           autocomplete="new-password"
                                           placeholder="Minimum 8 characters">
                                    <p class="form-hint">Must be at least 8 characters long</p>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" 
                                           name="confirm_password" 
                                           class="form-input" 
                                           required
                                           minlength="8"
                                           autocomplete="new-password"
                                           placeholder="Re-enter new password">
                                </div>
                                
                                <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                                    <button type="submit" class="btn btn-primary">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                        Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Logout Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Session Management</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 20px; background: var(--bg); border-radius: 12px; border: 1px solid var(--border);">
                                <div>
                                    <div style="font-weight: 600; margin-bottom: 4px;">Sign Out</div>
                                    <div style="color: var(--text-muted); font-size: 0.875rem;">
                                        End your current session and return to login
                                    </div>
                                </div>
                                <form method="POST" action="" style="margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                    <input type="hidden" name="action" value="logout">
                                    <button type="submit" class="btn btn-secondary">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                        Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Auto-hide messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                setTimeout(() => {
                    message.style.opacity = '0';
                    setTimeout(() => {
                        message.remove();
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>