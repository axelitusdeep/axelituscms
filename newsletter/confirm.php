<?php
if (!file_exists('config.php')) {
    header('Location: newsletter-unavailable.php');
    exit;
}

require_once 'config.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    showMessage('Error', 'Invalid confirmation link', 'error');
}

$pdo = getDB();
if (!$pdo) {
    showMessage('Error', 'Server problem. Please try again later.', 'error');
}

try {
    $stmt = $pdo->prepare("
        UPDATE newsletter_subscribers 
        SET confirmed = 1, confirmed_at = NOW() 
        WHERE token = ? AND confirmed = 0
    ");
    
    $stmt->execute([$token]);
    
    if ($stmt->rowCount() > 0) {
        showMessage(
            'Success!', 
            'Your newsletter subscription has been confirmed. Thank you!', 
            'success'
        );
    } else {
        $stmt = $pdo->prepare("SELECT confirmed FROM newsletter_subscribers WHERE token = ?");
        $stmt->execute([$token]);
        $result = $stmt->fetch();
        
        if ($result && $result['confirmed']) {
            showMessage(
                'Info', 
                'This subscription has already been confirmed.', 
                'info'
            );
        } else {
            showMessage(
                'Error', 
                'Invalid or expired confirmation link.', 
                'error'
            );
        }
    }
    
} catch (PDOException $e) {
    error_log("Confirmation error: " . $e->getMessage());
    showMessage('Error', 'An error occurred. Please try again later.', 'error');
}

function showMessage($title, $message, $type) {
    $colors = [
        'success' => ['bg' => '#d4edda', 'border' => '#c3e6cb', 'text' => '#155724'],
        'error' => ['bg' => '#f8d7da', 'border' => '#f5c6cb', 'text' => '#721c24'],
        'info' => ['bg' => '#d1ecf1', 'border' => '#bee5eb', 'text' => '#0c5460']
    ];
    
    $color = $colors[$type] ?? $colors['info'];
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="nl.ico?v=<?= filemtime(__DIR__ . '/newsletter/nl.ico') ?>" type="image/x-icon">
        <title><?= htmlspecialchars($title) ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: #f5f5f5;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 20px;
            }
            .message-box {
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                max-width: 500px;
                width: 100%;
                overflow: hidden;
            }
            .message-header {
                background: <?= $color['bg'] ?>;
                border-bottom: 2px solid <?= $color['border'] ?>;
                color: <?= $color['text'] ?>;
                padding: 20px;
                text-align: center;
            }
            .message-content {
                padding: 30px;
                text-align: center;
                color: #333;
                line-height: 1.6;
            }
            .btn {
                display: inline-block;
                margin-top: 20px;
                padding: 12px 30px;
                background: #007bff;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                transition: background 0.3s;
            }
            .btn:hover {
                background: #0056b3;
            }
        </style>
    </head>
    <body>
        <div class="message-box">
            <div class="message-header">
                <h1><?= htmlspecialchars($title) ?></h1>
            </div>
            <div class="message-content">
                <p><?= htmlspecialchars($message) ?></p>
                <a href="<?= SITE_URL ?>" class="btn">Back to homepage</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>