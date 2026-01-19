<?php
// contact.php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

/**
 * Detect AJAX request (common cases)
 */
function is_ajax_request(): bool {
  return (
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
  );
}

/**
 * Send response safely (JSON for AJAX, HTML for normal)
 */
function respond(bool $ok, string $message, int $statusCode = 200, array $extra = []): void {
  http_response_code($statusCode);

  if (is_ajax_request()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
      'success' => $ok,
      'message' => $message
    ], $extra));
    exit;
  }

  // Normal form submit: return simple HTML
  header('Content-Type: text/html; charset=utf-8');
  $title = $ok ? "Enquiry Sent" : "Error";
  $bg = $ok ? "#e7f7ee" : "#fde8e8";
  $bd = $ok ? "#1f9254" : "#b42318";

  echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
  echo '<title>'.htmlspecialchars($title).'</title></head><body style="font-family:Arial,sans-serif;background:'.$bg.';padding:30px;">';
  echo '<div style="max-width:720px;margin:auto;background:#fff;padding:22px;border-radius:12px;border:1px solid '.$bd.';">';
  echo '<h2 style="margin:0 0 12px 0;color:'.$bd.';">'.htmlspecialchars($title).'</h2>';
  echo '<p style="font-size:16px;line-height:1.6;margin:0 0 16px 0;">'.htmlspecialchars($message).'</p>';
  echo '<a href="/" style="display:inline-block;padding:10px 14px;background:'.$bd.';color:#fff;text-decoration:none;border-radius:8px;">Back to Home</a>';
  echo '</div></body></html>';
  exit;
}

// ✅ Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  respond(false, 'Method not allowed.', 405);
}

// ✅ Collect + sanitize
$name    = trim((string)($_POST['name'] ?? ''));
$email   = trim((string)($_POST['email'] ?? ''));
$subject = trim((string)($_POST['subject'] ?? ''));
$phone   = trim((string)($_POST['phone'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

// ✅ Validate required
if ($name === '' || $email === '' || $subject === '' || $message === '') {
  respond(false, 'Please fill all required fields (Name, Email, Subject, Message).', 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  respond(false, 'Invalid email address.', 400);
}

// optional phone cleanup
$phone_clean = preg_replace('/[^0-9+\-\s]/', '', $phone) ?? '';

// ✅ Email config (Titan SMTP)
$SMTP_HOST = 'smtp.titan.email';
$SMTP_PORT = 587;
$SMTP_USER = 'info@saffroninternational.in';
$SMTP_PASS = 'Test@123'; // ⚠️ change this password ASAP (security)

// ✅ Where enquiry should be received
$TO_EMAIL = 'info@saffroninternational.in';
$TO_NAME  = 'Saffron International';

// ✅ Build email body
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

  // SMTP setup
  $mail->isSMTP();
  $mail->Host       = $SMTP_HOST;
  $mail->SMTPAuth   = true;
  $mail->Username   = $SMTP_USER;
  $mail->Password   = $SMTP_PASS;
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
  $mail->Port       = $SMTP_PORT;

  // Email headers
  $mail->CharSet = 'UTF-8';
  $mail->setFrom($SMTP_USER, 'Saffron International Website');
  $mail->addAddress($TO_EMAIL, $TO_NAME);

  // Reply to customer
  $mail->addReplyTo($email, $name);

  // Content
  $mail->isHTML(true);
  $mail->Subject = "Website Enquiry: " . $subject;
  $mail->Body    = $enquiryHtml;
  $mail->AltBody = $enquiryText;

  // Send
  $mail->send();

  // ✅ Success response
  respond(true, 'Thank you! Your enquiry has been sent successfully.', 200);

} catch (Exception $e) {
  // ✅ Error response (no raw server HTML)
  respond(false, 'Email sending failed. Please try again later.', 500, [
    'error' => $e->getMessage()
  ]);
}
