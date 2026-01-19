<?php

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
  exit;
}

$rawBody = file_get_contents('php://input');
$data = [];

if (!empty($rawBody) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
  $data = json_decode($rawBody, true) ?? [];
} else {
  $data = $_POST;
}

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$subject = trim($data['subject'] ?? '');
$phone = trim($data['phone'] ?? '');
$message = trim($data['message'] ?? '');

if ($name === '' || $email === '' || $subject === '' || $message === '') {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Please fill out all fields.']);
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
  exit;
}

$configPath = __DIR__ . '/contact-mail-config.php';
if (!file_exists($configPath)) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Mail configuration missing.']);
  exit;
}

$config = require $configPath;

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Mail library missing.']);
  exit;
}

require $autoloadPath;

$smtpHost = $config['smtp_host'] ?? '';
$smtpPort = (int)($config['smtp_port'] ?? 465);
$smtpSecure = $config['smtp_secure'] ?? 'ssl';
$smtpUser = $config['smtp_user'] ?? '';
$smtpPass = $config['smtp_pass'] ?? '';
$mailTo = $config['mail_to'] ?? '';
$mailFrom = $config['mail_from'] ?? $smtpUser;

if ($smtpHost === '' || $smtpUser === '' || $smtpPass === '' || $mailTo === '' || $mailFrom === '') {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Mail configuration incomplete.']);
  exit;
}

$sanitizedName = preg_replace('/[\r\n]+/', ' ', $name);
$sanitizedSubject = preg_replace('/[\r\n]+/', ' ', $subject);

$body = "Name: {$sanitizedName}\n";
$body .= "Email: {$email}\n";
$body .= "Phone: " . ($phone !== '' ? $phone : 'N/A') . "\n";
$body .= "Subject: {$sanitizedSubject}\n\n";
$body .= $message . "\n";

$sendMail = function (int $port, string $secure) use (
  $smtpHost,
  $smtpUser,
  $smtpPass,
  $mailFrom,
  $mailTo,
  $email,
  $sanitizedName,
  $sanitizedSubject,
  $body
): void {
  $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
  $mailer->isSMTP();
  $mailer->Host = $smtpHost;
  $mailer->SMTPAuth = true;
  $mailer->Username = $smtpUser;
  $mailer->Password = $smtpPass;
  $mailer->Port = $port;
  $mailer->CharSet = 'UTF-8';
  $mailer->SMTPDebug = 0;
  $mailer->Debugoutput = 'error_log';

  if ($secure === 'tls') {
    $mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
  } elseif ($secure === 'ssl') {
    $mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
  }

  $mailer->SMTPOptions = [
    'ssl' => [
      'verify_peer' => false,
      'verify_peer_name' => false,
      'allow_self_signed' => true,
    ],
  ];

  $mailer->setFrom($mailFrom, 'Saffron International');
  $mailer->addAddress($mailTo);
  $mailer->addReplyTo($email, $sanitizedName);
  $mailer->Subject = $sanitizedSubject;
  $mailer->Body = $body;
  $mailer->isHTML(false);
  $mailer->send();
};

try {
  $sendMail($smtpPort, $smtpSecure);
  echo json_encode(['success' => true, 'message' => 'Message sent successfully.']);
} catch (Throwable $e) {
  $errorMessage = $e->getMessage();
  $fallbackAttempted = false;

  if ($smtpSecure === 'ssl') {
    try {
      $fallbackAttempted = true;
      $sendMail(587, 'tls');
      echo json_encode(['success' => true, 'message' => 'Message sent successfully.']);
      exit;
    } catch (Throwable $fallbackError) {
      $errorMessage = $fallbackError->getMessage();
    }
  }

  http_response_code(500);
  $extra = $fallbackAttempted ? ' (TLS fallback failed)' : '';
  echo json_encode([
    'success' => false,
    'message' => 'Unable to send message right now.',
    'error' => $errorMessage . $extra,
  ]);
}
