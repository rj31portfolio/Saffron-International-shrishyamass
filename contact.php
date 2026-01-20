<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

/**
 * Always JSON response
 */
function respond(bool $ok, string $message, int $statusCode = 200, array $extra = []): void {
  http_response_code($statusCode);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array_merge([
    'success' => $ok,
    'message' => $message,
  ], $extra));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  respond(false, 'Method not allowed.', 405);
}

// Support JSON or form posts
$contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
$inputData = [];
if ($contentType !== '' && strpos($contentType, 'application/json') !== false) {
  $rawBody = (string)file_get_contents('php://input');
  $decoded = json_decode($rawBody, true);
  if (is_array($decoded)) {
    $inputData = $decoded;
  }
}
$source = $inputData ?: $_POST;

$name    = trim((string)($source['name'] ?? ''));
$email   = trim((string)($source['email'] ?? ''));
$subject = trim((string)($source['subject'] ?? ''));
$phone   = trim((string)($source['phone'] ?? ''));
$message = trim((string)($source['message'] ?? ''));

if ($name === '' || $email === '' || $subject === '' || $message === '') {
  respond(false, 'Please fill all required fields (Name, Email, Subject, Message).', 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  respond(false, 'Invalid email address.', 400);
}

$phone_clean = preg_replace('/[^0-9+\-\s]/', '', $phone) ?? '';

// ===== SMTP CONFIG (REPLACE THESE) =====
$SMTP_HOST = 'smtp.gmail.com';
$SMTP_PORT = 587; // 587 for TLS, 465 for SSL
$SMTP_USER = 'info.errajuali@gmail.com';
$SMTP_PASS = 'oqgs qaiw penx dbgh';
$SMTP_SECURE = PHPMailer::ENCRYPTION_STARTTLS; // or PHPMailer::ENCRYPTION_SMTPS

$TO_EMAIL = 'info.errajuali@gmail.com';
$TO_NAME  = 'Website Enquiries';
$TO_EMAIL_SECONDARY = 'info@saffroninternational.in';
$TO_NAME_SECONDARY  = 'Saffron International';
// ======================================

$enquiryHtml = "
  <h2>New Website Enquiry</h2>
  <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
  <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
  <p><strong>Phone:</strong> " . htmlspecialchars($phone_clean) . "</p>
  <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
  <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
  <hr>
  <p><small>IP: " . htmlspecialchars((string)($_SERVER['REMOTE_ADDR'] ?? '')) . "</small></p>
";

$enquiryText =
"New Website Enquiry\n\n" .
"Name: $name\n" .
"Email: $email\n" .
"Phone: $phone_clean\n" .
"Subject: $subject\n\n" .
"Message:\n$message\n\n" .
"IP: " . ((string)($_SERVER['REMOTE_ADDR'] ?? '')) . "\n";

try {
  $mail = new PHPMailer(true);

  $mail->isSMTP();
  $mail->Host       = $SMTP_HOST;
  $mail->SMTPAuth   = true;
  $mail->Username   = $SMTP_USER;
  $mail->Password   = $SMTP_PASS;
  $mail->SMTPSecure = $SMTP_SECURE;
  $mail->Port       = $SMTP_PORT;

  $mail->CharSet = 'UTF-8';
  $mail->setFrom($SMTP_USER, 'Saffron International Website');
  $mail->addAddress($TO_EMAIL, $TO_NAME);
  $mail->addAddress($TO_EMAIL_SECONDARY, $TO_NAME_SECONDARY);
  $mail->addReplyTo($email, $name);

  $mail->isHTML(true);
  $mail->Subject = "Website Enquiry: " . $subject;
  $mail->Body    = $enquiryHtml;
  $mail->AltBody = $enquiryText;

  $mail->send();
  respond(true, 'Thank you! Your enquiry has been sent successfully.', 200);
} catch (Exception $e) {
  respond(false, 'Email sending failed. Please try again later.', 500, [
    'error' => $e->getMessage()
  ]);
}
