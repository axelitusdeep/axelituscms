<?php
require_once '../config.php';
require_login();

session_start();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_account'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($username) || empty($password)) {
        $message = 'Username and password are required';
        $message_type = 'error';
    } elseif (strlen($username) < 3) {
        $message = 'Username must be at least 3 characters';
        $message_type = 'error';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters';
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match';
        $message_type = 'error';
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        $message = 'Username can only contain letters, numbers, underscore and dash';
        $message_type = 'error';
    } else {
        $accounts = file_exists('hash.php') ? include('hash.php') : [];
        
        if (isset($accounts[$username])) {
            $message = 'Username already exists';
            $message_type = 'error';
        } else {

            $accounts[$username] = [
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $php_content = "<?php\n// hash.php - Hashed Admin Credentials\n// DO NOT EDIT MANUALLY - Use create-acc.php to manage accounts\n\nreturn " . var_export($accounts, true) . ";\n";
            
            if (file_put_contents('hash.php', $php_content)) {
                $message = 'Account created successfully! You can now login to manager.php';
                $message_type = 'success';
                
                // Clear form
                $_POST = [];
            } else {
                $message = 'Error: Could not write to hash.php (check permissions)';
                $message_type = 'error';
            }
        }
    }
}

if (isset($_GET['delete']) && file_exists('hash.php')) {
    $accounts = include('hash.php');
    $username_to_delete = $_GET['delete'];
    
    if (isset($accounts[$username_to_delete])) {
        unset($accounts[$username_to_delete]);
        
        if (empty($accounts)) {
            
            if (unlink('hash.php')) {
                $message = 'Last account deleted. hash.php removed.';
                $message_type = 'success';
            }
        } else {
            $php_content = "<?php\n// hash.php - Hashed Admin Credentials\n// DO NOT EDIT MANUALLY - Use create-acc.php to manage accounts\n\nreturn " . var_export($accounts, true) . ";\n";
            
            if (file_put_contents('hash.php', $php_content)) {
                $message = 'Account deleted successfully';
                $message_type = 'success';
            }
        }
    }
}

$existing_accounts = file_exists('hash.php') ? include('hash.php') : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Creator - Newsletter Manager</title>
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
            --warning: #eab308;
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
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            background: var(--bg-card);
            border: 1px solid var(--border);
            padding: 30px;
            border-radius: var(--radius);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .header-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 15px;
            color: var(--accent);
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            color: var(--text-muted);
            font-size: 14px;
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
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
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
            color: var(--warning);
            border-color: rgba(234, 179, 8, 0.2);
        }
        
        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            border-color: rgba(59, 130, 246, 0.2);
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
        
        .form-group input {
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
        
        .form-help {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 5px;
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
        
        .btn-danger {
            background: var(--error);
            color: white;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
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
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }
        
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            opacity: 0.3;
        }
        
        code {
            background: var(--bg-elevated);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: var(--text-muted);
        }
        
        .password-strength {
            height: 4px;
            background: var(--border);
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s, background-color 0.3s;
        }
        
        .strength-weak { background: var(--error); width: 33%; }
        .strength-medium { background: var(--warning); width: 66%; }
        .strength-strong { background: var(--success); width: 100%; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <svg class="header-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7" r="4"></circle>
                <line x1="20" y1="8" x2="20" y2="14"></line>
                <line x1="23" y1="11" x2="17" y2="11"></line>
            </svg>
            <h1>Account Creator</h1>
            <p>Create and manage admin accounts for Newsletter Manager</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>">
                <?php if ($message_type === 'success'): ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                <?php elseif ($message_type === 'error'): ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                <?php else: ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                <?php endif; ?>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Create Account Form -->
        <div class="card">
            <h2>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <polyline points="17 11 19 13 23 9"></polyline>
                </svg>
                Create New Account
            </h2>
            
            <form method="POST" id="accountForm">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="username" required minlength="3" pattern="[a-zA-Z0-9_-]+" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    <div class="form-help">Minimum 3 characters. Only letters, numbers, underscore and dash allowed.</div>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" id="password" required minlength="8">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="form-help">Minimum 8 characters. Use a strong password with letters, numbers and symbols.</div>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required minlength="8">
                </div>
                
                <button type="submit" name="create_account" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Create Account
                </button>
            </form>
        </div>
        
        <!-- Existing Accounts -->
        <?php if (!empty($existing_accounts)): ?>
        <div class="card">
            <h2>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Existing Accounts (<?= count($existing_accounts) ?>)
            </h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($existing_accounts as $username => $account): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($username) ?></strong></td>
                        <td><?= htmlspecialchars($account['created_at']) ?></td>
                        <td>
                            <a href="?delete=<?= urlencode($username) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete account \'<?= htmlspecialchars($username) ?>\'?\n\nThis action cannot be undone.')">
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
        <?php else: ?>
        <div class="card">
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <p>No accounts created yet.<br>Create your first admin account above.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Instructions -->
        <div class="card">
            <h2>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                Important Information
            </h2>
            
            <ul style="line-height: 2; color: var(--text-muted); margin-left: 20px;">
                <li>Passwords are hashed using bcrypt (industry standard)</li>
                <li>Each account gets stored in <code>hash.php</code></li>
                <li>Use these credentials to login at <code>manager.php</code></li>
                <li>You can create multiple accounts before accessing the manager</li>
            </ul>
        </div>
    </div>
    
    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const length = password.length;
            
            strengthBar.className = 'password-strength-bar';
            
            if (length === 0) {
                strengthBar.style.width = '0';
            } else if (length < 8) {
                strengthBar.classList.add('strength-weak');
            } else if (length < 12 || !/[0-9]/.test(password) || !/[a-zA-Z]/.test(password)) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        });
        
        // Form validation
        document.getElementById('accountForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
        });
    </script>
</body>
</html>