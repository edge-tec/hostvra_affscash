<?php
/**
 * Super Admin - Billing & Payment Gateway Settings Controller
 */
Auth::check();
if (Auth::role() !== 'super_admin') {
    Helpers::redirect('/');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !Auth::verifyCsrf($_POST['csrf_token'])) {
        $error = 'CSRF token verification failed.';
    } else {
        $settings = [
            'stripe' => [
                'enabled'        => isset($_POST['stripe_enabled']) ? '1' : '0',
                'sandbox_mode'   => isset($_POST['stripe_sandbox']) ? '1' : '0',
                'secret_key'     => trim($_POST['stripe_secret'] ?? ''),
                'webhook_secret' => trim($_POST['stripe_webhook'] ?? '')
            ],
            'crypto' => [
                'enabled'      => isset($_POST['crypto_enabled']) ? '1' : '0',
                'btc_address'  => trim($_POST['btc_address'] ?? ''),
                'eth_address'  => trim($_POST['eth_address'] ?? ''),
                'usdt_trc20'   => trim($_POST['usdt_trc20'] ?? ''),
                'usdt_erc20'   => trim($_POST['usdt_erc20'] ?? ''),
                'bnb_address'  => trim($_POST['bnb_address'] ?? ''),
                'ltc_address'  => trim($_POST['ltc_address'] ?? ''),
                'doge_address' => trim($_POST['doge_address'] ?? '')
            ],
            'trial' => [
                'enabled'       => isset($_POST['trial_enabled']) ? '1' : '0',
                'duration_days' => (int)($_POST['trial_duration'] ?? 14)
            ]
        ];

        try {
            Database::begin();
            foreach ($settings as $gateway => $keys) {
                foreach ($keys as $key => $val) {
                    Database::query(
                        "INSERT INTO `gateway_settings` (`gateway_name`, `setting_key`, `setting_value`) 
                         VALUES (?, ?, ?) 
                         ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)",
                        [$gateway, $key, (string)$val]
                    );
                }
            }
            Database::commit();

            // Save global SMTP Settings inside config.json safely
            $smtpHost       = trim($_POST['smtp_host'] ?? '');
            $smtpPort       = (int)($_POST['smtp_port'] ?? 587);
            $smtpUsername   = trim($_POST['smtp_username'] ?? '');
            $smtpPassword   = $_POST['smtp_password'] ?? '';
            $smtpEncryption = trim($_POST['smtp_encryption'] ?? 'tls');
            $smtpFromEmail  = trim($_POST['smtp_from_email'] ?? '');
            $smtpFromName   = trim($_POST['smtp_from_name'] ?? '');

            Config::set('config', 'smtp.host', $smtpHost);
            Config::set('config', 'smtp.port', $smtpPort);
            Config::set('config', 'smtp.username', $smtpUsername);
            Config::set('config', 'smtp.password', $smtpPassword);
            Config::set('config', 'smtp.encryption', $smtpEncryption);
            Config::set('config', 'smtp.from_email', $smtpFromEmail);
            Config::set('config', 'smtp.from_name', $smtpFromName);

            $success = 'Gateway and SMTP email settings updated successfully!';
        } catch (\Throwable $e) {
            Database::rollback();
            $error = 'Failed to save settings: ' . $e->getMessage();
        }
    }
}

// Fetch all settings
$settingsRows = Database::fetchAll("SELECT * FROM `gateway_settings`");
$gw = [];
foreach ($settingsRows as $row) {
    $gw[$row['gateway_name']][$row['setting_key']] = $row['setting_value'];
}

$pageTitle = 'Payment Gateway Settings';
require BASE_PATH . '/views/super_admin/billing_settings.php';
