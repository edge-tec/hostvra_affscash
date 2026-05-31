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

        if (!in_array($method, ['bank','crypto','capitalist','stripe'], true)) {
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
        if ($method !== 'stripe') {
            if ($txnId === '')  $errors[] = 'Transaction ID is required.';
        }

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
            if ($method === 'stripe') {
                $sk = Config::get('config', 'app.stripe_sk');
                if (!$sk) {
                    $errors[] = 'Stripe is not fully configured by the admin.';
                } else {
                    $appUrl = rtrim((string)(Config::get('config', 'app.url') ?: 'https://' . $_SERVER['HTTP_HOST']), '/');
                    $amountCents = (int)round($amount * 100);
                    
                    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_USERPWD, $sk . ':');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                        'payment_method_types' => ['card'],
                        'line_items' => [
                            [
                                'price_data' => [
                                    'currency' => 'usd',
                                    'product_data' => [
                                        'name' => 'Account Balance Top-Up',
                                    ],
                                    'unit_amount' => $amountCents,
                                ],
                                'quantity' => 1,
                            ]
                        ],
                        'mode' => 'payment',
                        'success_url' => $appUrl . '/advertiser/billing?action=stripe_success&session_id={CHECKOUT_SESSION_ID}',
                        'cancel_url'  => $appUrl . '/advertiser/billing/top-up',
                        'client_reference_id' => $advId,
                    ]));
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    $json = json_decode($response, true);
                    if ($httpCode === 200 && !empty($json['url'])) {
                        Helpers::redirect($json['url']);
                    } else {
                        $errors[] = 'Stripe error: ' . ($json['error']['message'] ?? 'Unknown error occurred.');
                    }
                }
            } else {
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
    }

    require BASE_PATH . '/views/advertiser/billing/top_up.php';
    return;
}

if ($action === 'stripe_success') {
    $sessionId = Helpers::get('session_id');
    if ($sessionId) {
        $sk = Config::get('config', 'app.stripe_sk');
        if ($sk) {
            $ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . urlencode($sessionId));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $sk . ':');
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $json = json_decode($response, true);
            
            if ($httpCode === 200 && ($json['payment_status'] ?? '') === 'paid') {
                $amountPaidCents = (int)($json['amount_total'] ?? 0);
                $amountPaid = $amountPaidCents / 100;
                $pi = $json['payment_intent'] ?? $sessionId;
                
                // Prevent duplicate processing
                $exists = Database::fetchOne("SELECT id FROM advertiser_payment_requests WHERE txn_id = ? AND advertiser_id = ?", [$pi, $advId]);
                
                if (!$exists && $amountPaid > 0) {
                    Database::insert('advertiser_payment_requests', [
                        'advertiser_id'   => $advId,
                        'user_id'         => (int)Auth::id(),
                        'method'          => 'stripe',
                        'amount'          => $amountPaid,
                        'txn_id'          => $pi,
                        'status'          => 'approved',
                        'admin_note'      => 'Auto-approved via Stripe',
                        'reviewed_at'     => date('Y-m-d H:i:s'),
                    ]);
                    
                    Database::query("UPDATE advertisers SET balance = balance + ? WHERE id = ?", [$amountPaid, $advId]);
                    
                    Helpers::flash('success', 'Your payment of $' . number_format($amountPaid, 2) . ' was successful and your balance has been updated.');
                } elseif ($exists) {
                    Helpers::flash('success', 'Your payment was already processed.');
                }
            } else {
                Helpers::flash('error', 'Payment verification failed. Please contact support if you were charged.');
            }
        }
    }
    Helpers::redirect('/advertiser/billing');
}

// ─── DEFAULT: billing overview with balance + request history ───────────
$requests = Database::fetchAll(
    "SELECT * FROM advertiser_payment_requests WHERE advertiser_id=? ORDER BY id DESC LIMIT 50",
    [$advId]
);
require BASE_PATH . '/views/advertiser/billing/index.php';
