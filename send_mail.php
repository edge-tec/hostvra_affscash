<?php
// ══════════════════════════════════════════════════════
//  send_mail.php — Affscash Contact Form Handler
//  Place in same folder as index.html
// ══════════════════════════════════════════════════════

header('Content-Type: application/json');
// Restrict CORS to configured app domain instead of wildcard
$_allowedOrigin = '';
try {
    $_allowedOrigin = rtrim(Config::get('config', 'app.url') ?? '', '/');
} catch (\Throwable $_e) {}
if ($_allowedOrigin) {
    $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($requestOrigin && stripos($requestOrigin, parse_url($_allowedOrigin, PHP_URL_HOST)) !== false) {
        header('Access-Control-Allow-Origin: ' . $requestOrigin);
    } else {
        header('Access-Control-Allow-Origin: ' . $_allowedOrigin);
    }
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── CONFIG ────────────────────────────────────────────
define('TO_EMAIL',   'support@affscash.net');   // ← Your email
define('FROM_EMAIL', 'noreply@affscash.net');    // ← Your domain email
define('SITE_NAME',  'Affscash');

define('BASE_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . '/config');
require_once BASE_PATH . '/core/Config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Mailer.php';

try {
    Config::init(CONFIG_PATH);
} catch (Exception $e) {
    // If db/config is completely broken, we will fail later gracefully.
}

// ── HELPERS ───────────────────────────────────────────
function ok($msg = 'Message sent!')  { echo json_encode(['success' => true,  'message' => $msg]); exit; }
function err($msg = 'Failed to send') { echo json_encode(['success' => false, 'message' => $msg]); exit; }
function clean($s) { return htmlspecialchars(strip_tags(trim($s)), ENT_QUOTES, 'UTF-8'); }

// ── ONLY ACCEPT POST ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Invalid request method');

// ── RATE LIMITING (IP-based, max 5 per 15 min) ───────
$_contactIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
try {
    Database::query("CREATE TABLE IF NOT EXISTS `contact_form_limits` (
        `ip_address` VARCHAR(45) PRIMARY KEY,
        `attempts` INT NOT NULL DEFAULT 0,
        `first_attempt` DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $limitRow = Database::fetchOne("SELECT `attempts`, `first_attempt` FROM `contact_form_limits` WHERE `ip_address` = ?", [$_contactIp]);
    if ($limitRow) {
        if (strtotime($limitRow['first_attempt']) > time() - 900) {
            if ($limitRow['attempts'] >= 5) {
                err('Too many messages sent. Please try again in 15 minutes.');
            }
            Database::query("UPDATE `contact_form_limits` SET `attempts` = `attempts` + 1 WHERE `ip_address` = ?", [$_contactIp]);
        } else {
            Database::query("UPDATE `contact_form_limits` SET `attempts` = 1, `first_attempt` = NOW() WHERE `ip_address` = ?", [$_contactIp]);
        }
    } else {
        Database::query("INSERT INTO `contact_form_limits` (`ip_address`, `attempts`, `first_attempt`) VALUES (?, 1, NOW())", [$_contactIp]);
    }
} catch (\Throwable $_e) {
    // Rate limiting failed silently — allow the message through
}

// ── PARSE BODY ────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);
if (!$body) err('Invalid request data');

$fname   = clean($body['fname']   ?? '');
$lname   = clean($body['lname']   ?? '');
$email   = clean($body['email']   ?? '');
$message = clean($body['message'] ?? '');

// ── VALIDATE ──────────────────────────────────────────
if (!$fname || !$lname || !$email || !$message) err('All fields are required');
if (!filter_var($email, FILTER_VALIDATE_EMAIL))  err('Invalid email address');
if (strlen($message) > 5000) err('Message too long');

// ── SPAM HONEYPOT CHECK ───────────────────────────────
if (!empty($body['website'])) ok('Message received!'); // silent honeypot

// ── BUILD EMAIL ───────────────────────────────────────
$subject = SITE_NAME . ' — New Contact Message from ' . $fname . ' ' . $lname;

$htmlBody = '<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px}
.card{background:#fff;border-radius:12px;padding:32px;max-width:560px;margin:0 auto;box-shadow:0 4px 20px rgba(0,0,0,.08)}
h2{color:#7c3aed;margin-bottom:20px}
.row{margin-bottom:14px}
.label{font-size:11px;font-weight:700;color:#8b87b0;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px}
.value{font-size:14px;color:#1a1535;padding:10px 14px;background:#f7f5ff;border-radius:8px;border:1px solid #ede8fc}
.msg{white-space:pre-wrap}
.footer{margin-top:24px;padding-top:16px;border-top:1px solid #ede8fc;font-size:12px;color:#8b87b0}
</style></head><body>
<div class="card">
  <h2>📩 New Contact Message</h2>
  <div class="row"><div class="label">Name</div><div class="value">' . $fname . ' ' . $lname . '</div></div>
  <div class="row"><div class="label">Email</div><div class="value"><a href="mailto:' . $email . '">' . $email . '</a></div></div>
  <div class="row"><div class="label">Message</div><div class="value msg">' . nl2br($message) . '</div></div>
  <div class="footer">Sent via ' . SITE_NAME . ' contact form · ' . date('Y-m-d H:i:s T') . '</div>
</div></body></html>';

$plainBody = "New contact message from $fname $lname\n"
           . "Email: $email\n\n"
           . "Message:\n$message\n\n"
           . "Sent: " . date('Y-m-d H:i:s');

// ── SEND ──────────────────────────────────────────────
$sent = false;
try {
    $sent = Mailer::sendRaw(TO_EMAIL, SITE_NAME . ' Support', $subject, $htmlBody, 'blast');
} catch (Throwable $e) {
    $sent = false;
}

if ($sent) {
    ok('Message sent! We\'ll reply within 24 hours.');
} else {
    err('Mail server error. Please contact us via Telegram.');
}
