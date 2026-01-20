<?php
declare(strict_types=1);

/**
 * Detect AJAX request (common cases)
 */
/**
 * Send response safely (always JSON)
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

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  respond(false, 'Method not allowed.', 405);
}

// Collect + sanitize (support JSON or standard form posts)
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

// Validate required
if ($name === '' || $email === '' || $subject === '' || $message === '') {
  respond(false, 'Please fill all required fields (Name, Email, Subject, Message).', 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  respond(false, 'Invalid email address.', 400);
}

// Optional phone cleanup
$phone_clean = preg_replace('/[^0-9+\-\s]/', '', $phone) ?? '';

// Where enquiry should be received
$TO_EMAIL = 'info@saffroninternational.in';
$TO_NAME  = 'Saffron International';

// Build email body
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

$subjectLine = 'Website Enquiry: ' . $subject;
$headers = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/html; charset=UTF-8';
$headers[] = 'From: ' . $TO_NAME . ' <' . $TO_EMAIL . '>';
$headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
$headers[] = 'X-Mailer: PHP/' . PHP_VERSION;

$sent = mail($TO_EMAIL, $subjectLine, $enquiryHtml, implode("\r\n", $headers));

if ($sent) {
  respond(true, 'Thank you! Your enquiry has been sent successfully.', 200);
}

respond(false, 'Email sending failed. Please try again later.', 500);
