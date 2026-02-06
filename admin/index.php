<?php
require_once '../config.php';
require_once __DIR__ . '/login_tracking.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to dashboard if already logged in  
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error = '';
$success = '';

// Initialize login tracker with error handling
try {
    $loginTracker = new LoginTracker();
    
    // Check if IP is banned
    $clientIP = $loginTracker->getClientIP();
    if ($loginTracker->isIPBanned($clientIP)) {
        http_response_code(403);
        die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Access Denied</title><style>body{font-family:Arial,sans-serif;background:#f1f5f9;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}div{background:#fff;padding:40px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.1);text-align:center;}h1{color:#dc2626;margin:0 0 10px;}p{color:#64748b;margin:0;}</style></head><body><div><h1>Access Denied</h1><p>Your IP address has been banned.</p></div></body></html>');
    }
} catch (Exception $e) {
    error_log('Login tracker initialization failed: ' . $e->getMessage());
    $loginTracker = null;
}

function checkRateLimit() {
    $rateLimitFile = DATA_DIR . '/rate_limit.json';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $now = time();
    $windowSeconds = 300; // 5 minutes
    $maxAttempts = 5;
    
    $rateLimits = [];
    if (file_exists($rateLimitFile)) {
        $rateLimits = json_decode(@file_get_contents($rateLimitFile), true) ?: [];
    }
    
    $rateLimits = array_filter($rateLimits, function($data) use ($now, $windowSeconds) {
        return ($now - $data['first_attempt']) < $windowSeconds;
    });
    
    if (isset($rateLimits[$ip])) {
        if ($rateLimits[$ip]['attempts'] >= $maxAttempts) {
            $timeLeft = $windowSeconds - ($now - $rateLimits[$ip]['first_attempt']);
            return [
                'allowed' => false,
                'timeLeft' => ceil($timeLeft / 60)
            ];
        }
    }
    
    return ['allowed' => true];
}

function recordLoginAttempt($success = false) {
    $rateLimitFile = DATA_DIR . '/rate_limit.json';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $now = time();
    
    $rateLimits = [];
    if (file_exists($rateLimitFile)) {
        $rateLimits = json_decode(@file_get_contents($rateLimitFile), true) ?: [];
    }
    
    if ($success) {
        unset($rateLimits[$ip]);
    } else {
        if (!isset($rateLimits[$ip])) {
            $rateLimits[$ip] = [
                'attempts' => 1,
                'first_attempt' => $now
            ];
        } else {
            $rateLimits[$ip]['attempts']++;
        }
    }
    
    @file_put_contents($rateLimitFile, json_encode($rateLimits));
}

$adminFile = DATA_DIR . '/admin.json';
$adminExists = file_exists($adminFile);

if (!$adminExists && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $email = strtolower(trim($_POST['email'] ?? ''));
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $adminData = [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'email'    => $email,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if (file_put_contents($adminFile, json_encode($adminData, JSON_PRETTY_PRINT))) {
            $success = 'Admin account created successfully. You can now log in.';
            $adminExists = true;
        } else {
            $error = 'Failed to create account. Check write permissions.';
        }
    }
}

if ($adminExists && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $rateLimitCheck = checkRateLimit();
    if (!$rateLimitCheck['allowed']) {
        $error = 'Too many login attempts. Please try again in ' . $rateLimitCheck['timeLeft'] . ' minute(s).';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Please fill in all fields.';
            recordLoginAttempt(false);
        } else {
            $adminData = json_decode(@file_get_contents($adminFile), true) ?: [];
            
            if ($adminData && $adminData['username'] === $username && password_verify($password, $adminData['password'])) {
                recordLoginAttempt(true);

                $adminEmail   = $adminData['email'] ?? '';
                $lastLoginFile = DATA_DIR . '/lastlogin.json';
                $currentIP    = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $currentUA    = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                $now          = date('Y-m-d H:i:s');

                $prevLogin = [];
                if (file_exists($lastLoginFile)) {
                    $prevLogin = json_decode(@file_get_contents($lastLoginFile), true) ?: [];
                }

                $shouldNotify = empty($prevLogin)
                    || $prevLogin['ip']         !== $currentIP
                    || $prevLogin['user_agent'] !== $currentUA;

                if ($adminEmail !== '' && $shouldNotify) {
                    $alertTo     = $adminEmail;
                    $alertFrom   = 'alert@flatlypage.com';
                    $alertSubject = 'New login detected - AxElitus CMS';

                    $currentIPSafe = htmlspecialchars($currentIP, ENT_QUOTES, 'UTF-8');
                    $currentUASafe = htmlspecialchars($currentUA, ENT_QUOTES, 'UTF-8');
                    $nowSafe = htmlspecialchars($now, ENT_QUOTES, 'UTF-8');

                    $prevBlock = '';
                    if (!empty($prevLogin)) {
                        $prevIPSafe = htmlspecialchars($prevLogin['ip'] ?? '', ENT_QUOTES, 'UTF-8');
                        $prevUASafe = htmlspecialchars($prevLogin['user_agent'] ?? '', ENT_QUOTES, 'UTF-8');
                        $prevTimeSafe = htmlspecialchars($prevLogin['time'] ?? '', ENT_QUOTES, 'UTF-8');
                        
                        $prevBlock = "
                            <tr><td colspan='2' style='padding:16px 0 4px;font-size:12px;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;'>Previous login</td></tr>
                            <tr>
                                <td style='padding:4px 0;color:#64748b;font-size:13px;'>IP address</td>
                                <td style='padding:4px 0;color:#1e293b;font-size:13px;font-weight:500;'>{$prevIPSafe}</td>
                            </tr>
                            <tr>
                                <td style='padding:4px 0;color:#64748b;font-size:13px;'>Device</td>
                                <td style='padding:4px 0;color:#1e293b;font-size:13px;font-weight:500;'>{$prevUASafe}</td>
                            </tr>
                            <tr>
                                <td style='padding:4px 0;color:#64748b;font-size:13px;'>Time</td>
                                <td style='padding:4px 0;color:#1e293b;font-size:13px;font-weight:500;'>{$prevTimeSafe}</td>
                            </tr>";
                    }

                    $alertHtml = "<!DOCTYPE html>
<html><head><meta charset='UTF-8'></head>
<body style='font-family:Arial,sans-serif;background:#f1f5f9;margin:0;padding:24px 0;'>
<div style='max-width:520px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);'>
  <div style='background:#1e293b;padding:28px 32px;'>
    <h1 style='margin:0;color:#fff;font-size:18px;font-weight:600;'>AxElitus CMS</h1>
    <p style='margin:4px 0 0;color:#94a3b8;font-size:13px;'>Security Notification</p>
  </div>
  <div style='padding:28px 32px 32px;'>
    <p style='margin:0 0 20px;font-size:15px;color:#1e293b;font-weight:600;'>A new login was detected on your account.</p>
    <table style='width:100%;border-collapse:collapse;'>
      <tr><td colspan='2' style='padding:0 0 4px;font-size:12px;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;'>Current login</td></tr>
      <tr>
        <td style='padding:4px 0;color:#64748b;font-size:13px;width:100px;'>IP address</td>
        <td style='padding:4px 0;color:#1e293b;font-size:13px;font-weight:500;'>{$currentIPSafe}</td>
      </tr>
      <tr>
        <td style='padding:4px 0;color:#64748b;font-size:13px;'>Device</td>
        <td style='padding:4px 0;color:#1e293b;font-size:13px;font-weight:500;'>{$currentUASafe}</td>
      </tr>
      <tr>
        <td style='padding:4px 0;color:#64748b;font-size:13px;'>Time (UTC)</td>
        <td style='padding:4px 0;color:#1e293b;font-size:13px;font-weight:500;'>{$nowSafe}</td>
      </tr>
      {$prevBlock}
    </table>
    <hr style='border:none;border-top:1px solid #e2e8f0;margin:24px 0 20px;'>
    <p style='margin:0;font-size:12px;color:#94a3b8;'>If you did not log in, secure your account immediately by changing your password.</p>
  </div>
</div>
</body></html>";

                    $alertHeaders  = "MIME-Version: 1.0\r\n";
                    $alertHeaders .= "Content-type: text/html; charset=UTF-8\r\n";
                    $alertHeaders .= "From: AxElitus CMS <{$alertFrom}>\r\n";
                    $alertHeaders .= "Reply-To: {$alertFrom}\r\n";

                    @mail($alertTo, $alertSubject, $alertHtml, $alertHeaders);
                }

                $newLogin = [
                    'ip'         => $currentIP,
                    'user_agent' => $currentUA,
                    'time'       => $now,
                ];
                @file_put_contents($lastLoginFile, json_encode($newLogin, JSON_PRETTY_PRINT));

                // Record login in tracking system
                if ($loginTracker !== null) {
                    try {
                        $loginTracker->recordLogin($username);
                    } catch (Exception $e) {
                        error_log('Failed to record login in tracker: ' . $e->getMessage());
                    }
                }

                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_login_time'] = time();

                // Regenerate session ID for security
                session_regenerate_id(true);

                header('Location: /admin/dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
                recordLoginAttempt(false);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $adminExists ? 'Login' : 'Setup'; ?> - Admin Dashboard</title>
    <link rel="icon" href="/admin/admin.ico" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preload" href="/assets/fonts/inter/inter.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <script src="/assets/js/admin-theme.js?v=2"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo/Brand -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-2xl mb-4">
                <img src="/logos/flatlypage_light.svg" alt="Logo" class="w-10 h-10">
            </div>
            <h1 class="text-2xl font-bold text-white">AxElitus CMS</h1>
            <p class="text-slate-400 mt-2">
                <?php echo $adminExists ? 'Zaloguj się' : 'Stwórz konto'; ?>
            </p>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-red-700 text-sm"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <p class="text-green-700 text-sm"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$adminExists): ?>
                <!-- Registration Form -->
                <form method="POST" action="" class="space-y-5">
                    <input type="hidden" name="action" value="register">
                    
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                            Nazwa
                        </label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               required
                               autocomplete="username"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                               placeholder="Wpisz nazwę">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Hasło
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required
                               minlength="8"
                               autocomplete="new-password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                               placeholder="Minimum 8 słów">
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Potwierdź hasło
                        </label>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               required
                               minlength="8"
                               autocomplete="new-password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                               placeholder="Potwierdź hasło">
                    </div>

                    <div>
                        <label for="reg_email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email <span class="text-gray-400 font-normal">(opcjonalny do alertów)</span>
                        </label>
                        <input type="email"
                               id="reg_email"
                               name="email"
                               autocomplete="email"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                               placeholder="biuro@example.com">
                    </div>

                    <button type="submit" 
                            class="w-full py-3 px-4 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition-all">
                        Stwórz konto
                    </button>
                </form>
            <?php else: ?>
                <!-- Login Form -->
                <form method="POST" action="" class="space-y-5">
                    <input type="hidden" name="action" value="login">
                    
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                            Nazwa
                        </label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               required
                               autocomplete="username"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                               placeholder="Wpisz nazwę">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Hasło
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required
                               autocomplete="current-password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                               placeholder="Wpisz hasło">
                    </div>

                    <button type="submit" 
                            class="w-full py-3 px-4 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition-all">
                        Zaloguj się
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <p class="text-center text-slate-500 text-sm mt-8">
            AxElitus CMS &copy; <?php echo date('Y'); ?>
        </p>
    </div>
</body>
</html>