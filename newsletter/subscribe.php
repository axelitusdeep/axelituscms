<?php
if (!file_exists('config.php')) {
    header('Location: newsletter-unavailable.php');
    exit;
}

session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

$pdo = getDB();
if (!$pdo) {
    jsonResponse(false, 'Server error. Please try again later.');
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$email = $email ? strtolower(trim($email)) : null;

if (!$email) {
    jsonResponse(false, 'Please enter a valid email address');
}

try {
    $stmt = $pdo->prepare("SELECT id, confirmed FROM newsletter_subscribers WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        if ($existing['confirmed']) {
            jsonResponse(false, 'This email is already subscribed to our newsletter');
        } else {
            jsonResponse(false, 'Please check your email and confirm your subscription');
        }
    }
    
    $token = bin2hex(random_bytes(32));
    
    $stmt = $pdo->prepare("
        INSERT INTO newsletter_subscribers (email, token, created_at, confirmed, ip_address) 
        VALUES (?, ?, NOW(), ?, ?)
    ");
    
    $confirmed = REQUIRE_CONFIRMATION ? 0 : 1;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $stmt->execute([$email, $token, $confirmed, $ip]);
    
    if (REQUIRE_CONFIRMATION) {
        sendConfirmationEmail($email, $token);
        $message = 'Please check your email and click the confirmation link';
    } else {
        $message = 'Thank you for subscribing to our newsletter!';
    }
    
    jsonResponse(true, $message);
    
} catch (PDOException $e) {
    error_log("Newsletter subscription error: " . $e->getMessage());
    jsonResponse(false, 'An error occurred. Please try again later.');
}

function sendConfirmationEmail($email, $token) {
    $confirmLink = SITE_URL . "/newsletter/confirm.php?token=" . urlencode($token);
    $unsubscribeLink = SITE_URL . "/newsletter/unsubscribe.php?email=" . urlencode($email);
    
    $subject = "Confirm your newsletter subscription - " . SENDER_NAME;
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 30px 20px; background: #f9f9f9; }
            .button { display: inline-block; padding: 12px 30px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>" . SENDER_NAME . "</h1>
            </div>
            <div class='content'>
                <h2>Welcome!</h2>
                <p>Thank you for subscribing to our newsletter.</p>
                <p>To confirm your subscription, please click the button below:</p>
                <div style='text-align: center;'>
                    <a href='$confirmLink' class='button'>Confirm Subscription</a>
                </div>
                <p>Or copy and paste this link into your browser:</p>
                <p style='word-break: break-all; color: #007bff;'>$confirmLink</p>
                <p style='margin-top: 30px; font-size: 14px; color: #666;'>
                    If you didn't sign up for this newsletter, please ignore this message or 
                    <a href='$unsubscribeLink'>click here to unsubscribe</a>.
                </p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SENDER_NAME . ". All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SENDER_NAME . " <" . SENDER_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . SENDER_EMAIL . "\r\n";
    
    return mail($email, $subject, $message, $headers);
}
?>