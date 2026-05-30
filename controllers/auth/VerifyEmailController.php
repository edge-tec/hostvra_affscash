<?php
// DB migrations
try { Database::query("ALTER TABLE users ADD COLUMN email_verify_token VARCHAR(64) DEFAULT NULL"); } catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE users ADD COLUMN email_verified_at DATETIME DEFAULT NULL"); } catch(\Throwable $_e) {}

$token = trim(Helpers::get('token') ?? '');

if (!$token) {
    Helpers::flash('error', 'Invalid verification link.');
    Helpers::redirect('/login');
}

$user = Database::fetchOne(
    "SELECT id, first_name, email FROM users WHERE email_verify_token=? AND email_verified_at IS NULL LIMIT 1",
    [$token]
);

if (!$user) {
    Helpers::flash('error', 'This verification link is invalid or has already been used.');
    Helpers::redirect('/login');
}

Database::update('users', [
    'email_verified_at'   => date('Y-m-d H:i:s'),
    'email_verify_token'  => null,
], 'id=?', [$user['id']]);

$siteName = Config::get('config', 'app.name') ?? 'AffiliateTracker';
Helpers::flash('success', "✓ Email verified successfully! Your account is pending admin approval. You will be notified once it is activated.");
Helpers::redirect('/login');
