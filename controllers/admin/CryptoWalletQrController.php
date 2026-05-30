<?php
/**
 * controllers/admin/CryptoWalletQrController.php
 *
 * Renders the read-only Crypto Wallet QR Code page for admins.
 *
 * Route registration (add to your router file, e.g. routes.php or bootstrap):
 *
 *   '/admin/crypto-wallets' => ['controller' => 'admin/CryptoWalletQrController', 'method' => 'GET'],
 *
 * Or, if your framework maps file paths automatically, just dropping this file
 * in controllers/admin/ is enough — the route will be /admin/crypto-wallets
 * (adjust to match your naming convention).
 *
 * Navigation link (add to your admin sidebar / nav template):
 *
 *   <a href="/admin/crypto-wallets">🪙 Crypto Wallet QR</a>
 */

Auth::check('admin');

$pageTitle = 'Crypto Wallet QR Codes';

// Pull payment methods from the same config key used in SettingsController.
// Config::getGroup('config') returns the parsed config array stored in the
// config table (or config file), exactly as SettingsController reads it.
$cfg = Config::getGroup('config');

// Pass to the view — the view reads $cfg directly (same pattern as settings/index.php)
require BASE_PATH . '/views/admin/settings/crypto_wallet_qr.php';
