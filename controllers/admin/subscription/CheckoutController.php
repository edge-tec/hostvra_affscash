<?php
/**
 * Admin Subscription - Checkout Controller
 */
Auth::check('admin');

$tenantId = (int)$_SESSION['tenant_id'];
$planId = (int)($_GET['plan_id'] ?? ($_POST['plan_id'] ?? 1));

$plan = Database::fetchOne("SELECT * FROM `subscription_plans` WHERE id = ? AND status = 'active'", [$planId]);
if (!$plan) {
    Helpers::redirect('/admin/subscription/renew?error=' . urlencode('Selected plan is not available.'));
}

// Fetch payment gateway configuration
$settingsRows = Database::fetchAll("SELECT * FROM `gateway_settings`");
$gw = [];
foreach ($settingsRows as $row) {
    $gw[$row['gateway_name']][$row['setting_key']] = $row['setting_value'];
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !Auth::verifyCsrf($_POST['csrf_token'])) {
        $error = 'CSRF token verification failed.';
    } else {
        $method = $_POST['payment_method'] ?? 'stripe';
        $cryptoCurrency = $_POST['crypto_currency'] ?? 'USDT-TRC20';
        $reference = trim($_POST['reference'] ?? '');

        if (!$reference) {
            $error = 'Payment reference code or transaction hash is required.';
        } else {
            try {
                Database::begin();
                $duration = (int)$plan['duration'];

                // ── Sandbox Auto-activation ─────────────────────────────────────
                // If sandbox mode is active, we automatically approve and activate the subscription instantly.
                // In live mode, Stripe payments require webhook triggers, and Crypto is marked as pending.
                $isSandbox = ($method === 'stripe' && ($gw['stripe']['sandbox_mode'] ?? '1') === '1') 
                           || ($method === 'crypto' && ($gw['crypto']['enabled'] ?? '0') === '0'); // treat as sandbox if gateway disabled

                if ($isSandbox) {
                    // Disable any other active subscriptions for this tenant
                    Database::update('subscriptions', ['status' => 'cancelled'], 'tenant_id = ? AND status = ?', [$tenantId, 'active']);

                    // Insert active subscription
                    Database::insert('subscriptions', [
                        'tenant_id'        => $tenantId,
                        'plan_id'          => $planId,
                        'price'            => $plan['price'],
                        'duration'         => $duration,
                        'status'           => 'active',
                        'starts_at'        => date('Y-m-d H:i:s'),
                        'ends_at'          => date('Y-m-d H:i:s', strtotime("+$duration days")),
                        'billing_provider' => $method,
                        'auto_renew'       => ($method === 'stripe') ? 1 : 0,
                        'created_at'       => date('Y-m-d H:i:s')
                    ]);

                    // Update Tenant Status to active
                    Database::update('tenants', ['status' => 'active'], 'id = ?', [$tenantId]);

                    // Create Invoice
                    $invoiceNum = 'INV-' . strtoupper(bin2hex(random_bytes(4)));
                    Database::insert('tenant_invoices', [
                        'invoice_number' => $invoiceNum,
                        'tenant_id'      => $tenantId,
                        'amount'         => $plan['price'],
                        'tax_amount'     => 0.00,
                        'total'          => $plan['price'],
                        'status'         => 'paid',
                        'due_date'       => date('Y-m-d'),
                        'paid_at'        => date('Y-m-d H:i:s'),
                        'created_at'     => date('Y-m-d H:i:s')
                    ]);

                    // Log Billing Transaction
                    Tenant::logBilling($tenantId, 'subscription_renewed', $plan['price'], "Renewed plan {$plan['name']} via {$method} sandbox (Ref: {$reference})");
                    
                    Database::commit();
                    Helpers::redirect('/admin/subscription/success');
                } else {
                    // LIVE MODE

                    if ($method === 'crypto') {
                        $settingsKey = strtolower(str_replace('-', '_', $cryptoCurrency));
                        if ($cryptoCurrency === 'USDT-TRC20') $settingsKey = 'usdt_trc20';
                        if ($cryptoCurrency === 'USDT-ERC20') $settingsKey = 'usdt_erc20';

                        $wallet = $gw['crypto'][$settingsKey] ?? 'TR7NHqJdjGdNF2DzFauk3FGLqmiejGLARi';

                        // Insert pending crypto payment transaction record
                        Database::insert('crypto_payments', [
                            'tenant_id' => $tenantId,
                            'amount'    => $plan['price'],
                            'currency'  => $cryptoCurrency,
                            'address'   => $wallet,
                            'tx_hash'   => $reference,
                            'status'    => 'pending',
                            'created_at'=> date('Y-m-d H:i:s')
                        ]);

                        Tenant::logBilling($tenantId, 'crypto_payment_pending', $plan['price'], "Pending Crypto {$cryptoCurrency} Payment (Tx: {$reference})");
                        Database::commit();

                        $success = "Crypto transaction registered! The system is verifying the block confirmations on-chain. Your account will automatically activate as soon as the transaction is confirmed.";
                        Helpers::redirect('/admin/subscription/renew?success=' . urlencode($success));
                    } else {
                        // Live Stripe Checkout integration placeholder/simulation
                        $error = 'Stripe live payments require a real credit card checkout. Please toggle sandbox mode inside Billing Settings to test this out.';
                        Database::rollback();
                    }
                }
            } catch (\Throwable $e) {
                Database::rollback();
                $error = 'Failed to process payment: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Secure Checkout';
require BASE_PATH . '/views/admin/subscription/checkout.php';

