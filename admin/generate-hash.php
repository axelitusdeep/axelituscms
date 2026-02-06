<?php
/**
 * Password Hash Generator
 * Use this tool to generate secure password hashes
 */

require_once '../config.php';

// Only allow access from CLI or localhost for security
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost']);
$isCLI = php_sapi_name() === 'cli';

if (!$isLocalhost && !$isCLI) {
    http_response_code(403);
    die('Access denied. This tool is only available from localhost.');
}

$hash = '';
$password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $password = $_POST['password'];
    $hash = password_hash($password, PASSWORD_DEFAULT);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hash Generator - AxElitus CMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preload" href="/assets/fonts/inter/inter.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gray-100 py-12 px-4">
    <div class="max-w-lg mx-auto">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-100 rounded-xl mb-4">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-gray-900">Password Hash Generator</h1>
                <p class="text-gray-500 text-sm mt-1">Generate a secure password hash</p>
            </div>

            <form method="POST" action="" class="space-y-5">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Password to hash
                    </label>
                    <input type="text" 
                           id="password" 
                           name="password" 
                           required
                           value="<?php echo e($password); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Enter password">
                </div>

                <button type="submit" 
                        class="w-full py-3 px-4 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                    Generate Hash
                </button>
            </form>

            <?php if ($hash): ?>
                <div class="mt-6 p-4 bg-gray-50 rounded-xl">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Generated hash:
                    </label>
                    <div class="relative">
                        <textarea readonly 
                                  id="hashOutput"
                                  class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl text-sm font-mono resize-none"
                                  rows="3"><?php echo e($hash); ?></textarea>
                        <button type="button"
                                onclick="copyHash()"
                                class="absolute top-2 right-2 p-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                                title="Copy">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div class="text-sm text-amber-800">
                            <p class="font-medium">Security Notice</p>
                            <p class="mt-1">Do not use this tool in production. Passwords should only be hashed during registration or password changes.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-6">
            <a href="index.php" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                &larr; Back to login
            </a>
        </div>
    </div>

    <script>
        function copyHash() {
            const textarea = document.getElementById('hashOutput');
            textarea.select();
            document.execCommand('copy');
            
            // Show feedback
            const btn = event.currentTarget;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
            setTimeout(() => {
                btn.innerHTML = originalHTML;
            }, 2000);
        }
    </script>
</body>
</html>
