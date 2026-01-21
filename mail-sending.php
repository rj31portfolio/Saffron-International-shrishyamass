<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();

function respond(string $status, string $message, int $code = 200, array $extra = []): void {
  http_response_code($code);
  if (ob_get_length()) {
    ob_clean();
  }
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array_merge([
    'status' => $status,
    'message' => $message,
  ], $extra));
  exit;
}

register_shutdown_function(function (): void {
  $error = error_get_last();
  if (!$error) {
    return;
  }
  $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
  if (in_array($error['type'], $fatalTypes, true)) {
    respond('error', 'Server error. Please try again later.', 500);
  }
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  respond('error', 'Method not allowed.', 405);
}

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

if ($name === '' || $email === '' || $message === '') {
  respond('error', 'Please fill all required fields.', 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  respond('error', 'Invalid email address.', 400);
}

$phoneClean = preg_replace('/[^0-9+\-\s]/', '', $phone) ?? '';

// ===== SMTP CONFIG =====
// Use a Gmail App Password for $SMTP_PASS (not your normal Gmail password).
$SMTP_HOST = 'smtp.gmail.com';
$SMTP_PORT = 587;
$SMTP_USER = 'shrishyamasuperservice@gmail.com';
$SMTP_PASS = 'ieql lysj rlqz mzyr';
$SMTP_SECURE = PHPMailer::ENCRYPTION_STARTTLS;

$TO_EMAIL = 'shrishyamasuperservice@gmail.com';
$TO_NAME = 'Website Enquiries';
$TO_EMAIL_SECONDARY = 'info@saffroninternational.in';
$TO_NAME_SECONDARY = 'Saffron International';
// =======================

$enquiryHtml = "
  <h2>New Website Enquiry</h2>
  <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
  <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
  <p><strong>Phone:</strong> " . htmlspecialchars($phoneClean) . "</p>
  <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
";

$enquiryText =
"New Website Enquiry\n\n" .
"Name: $name\n" .
"Email: $email\n" .
"Phone: $phoneClean\n\n" .
"Message:\n$message\n";

try {
  $mail = new PHPMailer(true);
  $mail->isSMTP();
  $mail->Host = $SMTP_HOST;
  $mail->SMTPAuth = true;
  $mail->Username = $SMTP_USER;
  $mail->Password = $SMTP_PASS;
  $mail->SMTPSecure = $SMTP_SECURE;
  $mail->Port = $SMTP_PORT;

  $mail->CharSet = 'UTF-8';
  $mail->setFrom($SMTP_USER, 'Saffron International Website');
  $mail->addAddress($TO_EMAIL, $TO_NAME);
  $mail->addAddress($TO_EMAIL_SECONDARY, $TO_NAME_SECONDARY);
  $mail->addReplyTo($email, $name);

  $mail->isHTML(true);
  $mail->Subject = 'Website Enquiry';
  $mail->Body = $enquiryHtml;
  $mail->AltBody = $enquiryText;

  $mail->send();
  respond('success', 'Message sent successfully', 200);
} catch (Exception $e) {
  respond('error', 'Failed to send message', 500);
}
