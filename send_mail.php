<?php
// ══════════════════════════════════════════════════════
//  send_mail.php — EliteAli Contact Form Handler
//  Place in same folder as index.html
// ══════════════════════════════════════════════════════

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── CONFIG ────────────────────────────────────────────
define('TO_EMAIL',   'support@eliteali.com');   // ← Your email
define('FROM_EMAIL', 'noreply@eliteali.com');    // ← Your domain email
define('SITE_NAME',  'EliteAli');

// ── HELPERS ───────────────────────────────────────────
function ok($msg = 'Message sent!')  { echo json_encode(['success' => true,  'message' => $msg]); exit; }
function err($msg = 'Failed to send') { echo json_encode(['success' => false, 'message' => $msg]); exit; }
function clean($s) { return htmlspecialchars(strip_tags(trim($s)), ENT_QUOTES, 'UTF-8'); }

// ── ONLY ACCEPT POST ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Invalid request method');

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
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: " . SITE_NAME . " <" . FROM_EMAIL . ">\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

$sent = mail(TO_EMAIL, $subject, $htmlBody, $headers);

if ($sent) {
    // Also send auto-reply to the user
    $replySubject = 'Thank you for contacting ' . SITE_NAME . '!';
    $replyHtml    = '<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px}
.card{background:#fff;border-radius:12px;padding:32px;max-width:560px;margin:0 auto}
h2{color:#e8197a}p{color:#7c7a9e;font-size:14px;line-height:1.7}
.btn{display:inline-block;background:linear-gradient(135deg,#e8197a,#7c3aed);color:#fff;padding:12px 28px;border-radius:9px;text-decoration:none;font-weight:700;margin-top:16px}
</style></head><body><div class="card">
<h2>Thanks, ' . $fname . '! 🎉</h2>
<p>We received your message and our team will get back to you within <strong>24 hours</strong>.</p>
<p>In the meantime, you can reach us instantly on Telegram:</p>
<a class="btn" href="https://t.me/eliteali" target="_blank">💬 @eliteali</a>
<p style="margin-top:24px;font-size:12px;color:#aaa">This is an automated reply. Please do not respond to this email.</p>
</div></body></html>';
    $replyHeaders  = "MIME-Version: 1.0\r\n";
    $replyHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
    $replyHeaders .= "From: " . SITE_NAME . " <" . FROM_EMAIL . ">\r\n";
    mail($email, $replySubject, $replyHtml, $replyHeaders);
    ok('Message sent! We\'ll reply within 24 hours.');
} else {
    err('Mail server error. Please contact us via Telegram.');
}
