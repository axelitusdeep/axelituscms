<?php
require_once __DIR__ . '/config.php';
$site_settings = get_site_settings();

function validate_contact_form($data) {
    $errors = [];

    // Name validation
    if (empty($data['name'])) {
        $errors['name'] = 'Name is required';
    } elseif (strlen($data['name']) > 100) {
        $errors['name'] = 'Name cannot exceed 100 characters';
    }

    // Email validation
    if (empty($data['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }

    // Message validation
    if (empty($data['message'])) {
        $errors['message'] = 'Message is required';
    } elseif (strlen($data['message']) > 5000) {
        $errors['message'] = 'Message cannot exceed 5000 characters';
    }

    return $errors;
}

function send_contact_email($name, $email, $message) {
    $to = get_site_settings()['contact_email']; // CHANGE TO YOUR EMAIL
    $subject = "New Message from " . get_site_settings()['site_name'] . " Contact Form";

    $email_body = "You have received a new message from the contact form.\n\n";
    $email_body .= "- - - - - - MESSAGE DETAILS - - - - - - \n";
    $email_body .= "Name: " . $name . "\n";
    $email_body .= "Email: " . $email . "\n\n";
    $email_body .= "Message:\n" . wordwrap($message, 72) . "\n";
    $email_body .= "- - - - - - - - - - - - - - - \n";
    $email_body .= "Sent on: " . date('Y-m-d H:i:s') . "\n";
    $email_body .= "Automated message from " . get_site_settings()['site_name'] . "\n";

    // Email headers
    $headers = "From: " . $email . "\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Send email
    return mail($to, $subject, $email_body, $headers);
}

function save_contact_to_file($name, $email, $message) {
    $log_dir = __DIR__ . '/contacts';

    // Create directory if it doesn't exist
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    // Filename with month
    $filename = $log_dir . '/contacts_' . date('Y-m') . '.txt';

    // Content to save
    $log_entry = "\n" . str_repeat('-', 50) . "\n";
    $log_entry .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $log_entry .= "Name: " . $name . "\n";
    $log_entry .= "Email: " . $email . "\n";
    $log_entry .= "Message:\n" . $message . "\n";
    $log_entry .= str_repeat('-', 50) . "\n";

    // Save to file
    return file_put_contents($filename, $log_entry, FILE_APPEND | LOCK_EX);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validate data
    $errors = validate_contact_form([
        'name' => $name,
        'email' => $email,
        'message' => $message
    ]);

    if (empty($errors)) {
        // Save to file (backup)
        save_contact_to_file($name, $email, $message);

        // Send email
        $email_sent = send_contact_email($name, $email, $message);

        if ($email_sent) {
            // Success - redirect with message
            $_SESSION['contact_success'] = true;
            header('Location: ' . $_SERVER['PHP_SELF'] . '?slug=' . ($slug ?? 'contact') . '&success=1');
            exit;
        } else {
            // Sending error
            $_SESSION['contact_error'] = 'An error occurred while sending the message. Please try again later.';
        }
    } else {
        // Validation errors
        $_SESSION['contact_errors'] = $errors;
        $_SESSION['contact_data'] = [
            'name' => $name,
            'email' => $email,
            'message' => $message
        ];
    }
}

function get_contact_message() {
    if (isset($_GET['success']) && $_GET['success'] == '1') {
        return [
            'type' => 'success',
            'text' => 'Thank you for your message! We will respond as soon as possible.'
        ];
    }

    if (isset($_SESSION['contact_error'])) {
        $error = $_SESSION['contact_error'];
        unset($_SESSION['contact_error']);
        return [
            'type' => 'error',
            'text' => $error
        ];
    }

    return null;
}

function get_contact_data($field) {
    if (isset($_SESSION['contact_data'][$field])) {
        $value = $_SESSION['contact_data'][$field];
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    return '';
}

function get_contact_error($field) {
    if (isset($_SESSION['contact_errors'][$field])) {
        return $_SESSION['contact_errors'][$field];
    }
    return '';
}

if (isset($_SESSION['contact_errors']) || isset($_SESSION['contact_data'])) {
    register_shutdown_function(function() {
        unset($_SESSION['contact_errors']);
        unset($_SESSION['contact_data']);
    });
}
