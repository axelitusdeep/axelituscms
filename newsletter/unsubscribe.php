<?php
if (!file_exists('config.php')) {
    header('Location: newsletter-unavailable.php');
    exit;
}

require_once 'config.php';

$email = $_GET['email'] ?? $_POST['email'] ?? '';
$email = filter_var($email, FILTER_VALIDATE_EMAIL);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $email) {
    $pdo = getDB();
    
    try {
        $stmt = $pdo->prepare("DELETE FROM newsletter_subscribers WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            showUnsubscribeMessage('Unsubscribed', 'Your email has been removed from our mailing list.', 'success');
        } else {
            showUnsubscribeMessage('Not Found', 'This email address is not on our list.', 'info');
        }
    } catch (PDOException $e) {
        error_log("Unsubscribe error: " . $e->getMessage());
        showUnsubscribeMessage('Error', 'An error occurred. Please try again.', 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe from Newsletter</title>
    <link rel="icon" href="nl.ico?v=<?= filemtime(__DIR__ . '/newsletter/nl.ico') ?>" type="image/x-icon">
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
        .unsubscribe-box {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        h1 { color: #333; margin-bottom: 20px; text-align: center; }
        p { color: #666; line-height: 1.6; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; }
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover { background: #c82333; }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="unsubscribe-box">
        <h1>Unsubscribe from Newsletter</h1>
        <p>We're sorry to see you go. Enter your email address to unsubscribe from our mailing list.</p>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email address:</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?= htmlspecialchars($email) ?>" 
                    required 
                    placeholder="your@email.com"
                >
            </div>
            <button type="submit">Unsubscribe</button>
        </form>
        
        <a href="<?= SITE_URL ?>" class="back-link">&larr; Back to homepage</a>
    </div>
</body>
</html>
<?php

function showUnsubscribeMessage($title, $message, $type) {
    $colors = [
        'success' => ['bg' => '#d4edda', 'text' => '#155724'],
        'error' => ['bg' => '#f8d7da', 'text' => '#721c24'],
        'info' => ['bg' => '#d1ecf1', 'text' => '#0c5460']
    ];
    $color = $colors[$type] ?? $colors['info'];
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?></title>
        <link rel="icon" href="nl.ico?v=<?= filemtime(__DIR__ . '/newsletter/nl.ico') ?>" type="image/x-icon">
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
                background: <?= $color['bg'] ?>;
                color: <?= $color['text'] ?>;
                border-radius: 8px;
                padding: 40px;
                max-width: 500px;
                text-align: center;
            }
            h1 { margin-bottom: 15px; }
            .btn {
                display: inline-block;
                margin-top: 20px;
                padding: 12px 30px;
                background: white;
                color: #333;
                text-decoration: none;
                border-radius: 5px;
            }
        </style>
    </head>
    <body>
        <div class="message-box">
            <h1><?= htmlspecialchars($title) ?></h1>
            <p><?= htmlspecialchars($message) ?></p>
            <a href="<?= SITE_URL ?>" class="btn">Back to homepage</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>