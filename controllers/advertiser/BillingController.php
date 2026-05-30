<?php
Auth::check('advertiser');
AdvBudget::ensureSchema();

$pageTitle = 'Billing';
$advId     = (int)Auth::advertiserId();
$action    = Helpers::get('action') ?: 'index';

$adv = Database::fetchOne("SELECT * FROM advertisers WHERE id=?", [$advId]);
$flashMsg = '';

// ─── ACTION: top-up form / submit ────────────────────────────────────────
if ($action === 'top_up') {
    $methods = AdvBudget::paymentMethods();
    $errors  = [];

    // Schema: ensure crypto_type column exists
    try { Database::query("ALTER TABLE advertiser_payment_requests ADD COLUMN crypto_type VARCHAR(10) DEFAULT NULL"); } catch (\Throwable $_e) {}

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $method     = Helpers::post('method');
        $amount     = (float)Helpers::postRaw('amount');
        $txnId      = trim((string)(Helpers::postRaw('txn_id') ?? ''));
        $cryptoType = '';

        if (!in_array($method, ['bank','crypto','capitalist'], true)) {
            $errors[] = 'Please select a payment method.';
        } elseif (empty($methods[$method])) {
            $errors[] = 'This payment method is currently unavailable. Please contact support.';
        }

        // Validate crypto coin selection
        $validCoins = ['usdt','btc','ltc','eth','bnb','trx','other'];
        if ($method === 'crypto') {
            $cryptoType = strtolower(trim(Helpers::postRaw('crypto_type') ?? ''));
            if (!in_array($cryptoType, $validCoins, true)) {
                $errors[] = 'Please select a cryptocurrency.';
            }
        }
        if ($amount <= 0)   $errors[] = 'Amount must be greater than zero.';
        if ($txnId === '')  $errors[] = 'Transaction ID is required.';

        // Optional screenshot upload — strict mime + size validation.
        $screenshotPath = null;
        if (!empty($_FILES['screenshot']['tmp_name']) && is_uploaded_file($_FILES['screenshot']['tmp_name'])) {
            $file = $_FILES['screenshot'];
            if ($file['size'] > 6 * 1024 * 1024) {
                $errors[] = 'Screenshot is larger than 6 MB.';
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
                if (!isset($allowed[$mime])) {
                    $errors[] = 'Screenshot must be a JPG, PNG, WEBP, or PDF file.';
                } else {
                    $dir = BASE_PATH . '/uploads/payment_requests/' . $advId;
                    if (!is_dir($dir)) @mkdir($dir, 0775, true);
                    $name = 'pr_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
                    $abs  = $dir . '/' . $name;
                    if (@move_uploaded_file($file['tmp_name'], $abs)) {
                        $screenshotPath = $advId . '/' . $name;
                    }
                }
            }
        }

        if (!$errors) {
            Database::insert('advertiser_payment_requests', [
                'advertiser_id'   => $advId,
                'user_id'         => (int)Auth::id(),
                'method'          => $method,
                'amount'          => $amount,
                'txn_id'          => $txnId,
                'screenshot_path' => $screenshotPath,
                'status'          => 'pending',
                'crypto_type'     => $cryptoType ?: null,
            ]);
            // Notify admin via in-app notification (instant) — admin queue lives at /admin/payment-requests.
            try {
                Database::insert('notifications', [
                    'user_id'     => null,
                    'target_role' => 'admin',
                    'type'        => 'info',
                    'title'       => 'New Payment Request',
                    'message'     => 'Advertiser submitted a $' . number_format($amount, 2) . ' top-up via ' . ucfirst($method) . ($cryptoType ? ' (' . strtoupper($cryptoType) . ')' : '') . ' (Txn: ' . substr($txnId, 0, 30) . ').',
                    'link'        => '/admin/payment-requests',
                    'is_read'     => 0,
                ]);
            } catch (\Throwable $_e) {}

            Helpers::flash('success', 'Payment request submitted. We will review it shortly and credit your balance once approved.');
            Helpers::redirect('/advertiser/billing');
        }
    }

    require BASE_PATH . '/views/advertiser/billing/top_up.php';
    return;
}

// ─── DEFAULT: billing overview with balance + request history ───────────
$requests = Database::fetchAll(
    "SELECT * FROM advertiser_payment_requests WHERE advertiser_id=? ORDER BY id DESC LIMIT 50",
    [$advId]
);
require BASE_PATH . '/views/advertiser/billing/index.php';
