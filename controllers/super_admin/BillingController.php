<?php
/**
 * Super Admin - Billing & Financial Operations Controller
 */
Auth::check();
if (Auth::role() !== 'super_admin') {
    Helpers::redirect('/');
}

$pageTitle = 'Billing Management';

// Handle Action
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = (int)($_GET['id'] ?? 0);
    
    if ($action === 'confirm_crypto') {
        $payment = Database::fetchOne("SELECT * FROM `crypto_payments` WHERE id = ? AND status = 'pending'", [$id]);
        if ($payment) {
            try {
                Database::begin();
                $tenantId = $payment['tenant_id'];
                
                // Fetch the plan matching the price or the cheapest active plan
                $plan = Database::fetchOne("SELECT * FROM `subscription_plans` WHERE price = ? AND status = 'active' LIMIT 1", [$payment['amount']]);
                if (!$plan) {
                    $plan = Database::fetchOne("SELECT * FROM `subscription_plans` WHERE status = 'active' ORDER BY price ASC LIMIT 1");
                }
                
                if ($plan) {
                    $duration = (int)$plan['duration'];
                    
                    // Cancel current active subscriptions
                    Database::update('subscriptions', ['status' => 'cancelled'], 'tenant_id = ? AND status = ?', [$tenantId, 'active']);
                    
                    // Insert subscription
                    Database::insert('subscriptions', [
                        'tenant_id'        => $tenantId,
                        'plan_id'          => $plan['id'],
                        'price'            => $payment['amount'],
                        'duration'         => $duration,
                        'status'           => 'active',
                        'starts_at'        => date('Y-m-d H:i:s'),
                        'ends_at'          => date('Y-m-d H:i:s', strtotime("+$duration days")),
                        'billing_provider' => 'crypto',
                        'auto_renew'       => 0,
                        'created_at'       => date('Y-m-d H:i:s')
                    ]);
                    
                    // Update tenant status to active
                    Database::update('tenants', ['status' => 'active'], 'id = ?', [$tenantId]);
                    
                    // Update crypto payment status
                    Database::update('crypto_payments', [
                        'status' => 'confirmed',
                        'confirmed_at' => date('Y-m-d H:i:s')
                    ], 'id = ?', [$id]);
                    
                    // Create paid Invoice
                    $invoiceNum = 'INV-' . strtoupper(bin2hex(random_bytes(4)));
                    Database::insert('tenant_invoices', [
                        'invoice_number' => $invoiceNum,
                        'tenant_id'      => $tenantId,
                        'amount'         => $payment['amount'],
                        'tax_amount'     => 0.00,
                        'total'          => $payment['amount'],
                        'status'         => 'paid',
                        'due_date'       => date('Y-m-d'),
                        'paid_at'        => date('Y-m-d H:i:s'),
                        'created_at'     => date('Y-m-d H:i:s')
                    ]);
                    
                    // Log Billing transaction
                    Tenant::logBilling($tenantId, 'subscription_renewed', $payment['amount'], "Confirmed Crypto {$payment['currency']} Payment (Ref: {$payment['tx_hash']})");
                    
                    Database::commit();
                    Helpers::redirect('/super_admin/billing?success=' . urlencode('Cryptocurrency payment approved and tenant subscription activated!'));
                } else {
                    Database::rollback();
                    Helpers::redirect('/super_admin/billing?error=' . urlencode('No active subscription plans found. Please configure a plan first.'));
                }
            } catch (\Throwable $e) {
                Database::rollback();
                Helpers::redirect('/super_admin/billing?error=' . urlencode('Failed to approve payment: ' . $e->getMessage()));
            }
        }
    } elseif ($action === 'reject_crypto') {
        Database::update('crypto_payments', ['status' => 'rejected'], 'id = ? AND status = ?', [$id, 'pending']);
        Helpers::redirect('/super_admin/billing?success=' . urlencode('Cryptocurrency payment marked as rejected.'));
    }
}

// Fetch all billing logs
$billingLogs = Database::fetchAll(
    "SELECT b.*, t.company_name 
     FROM `billing_logs` b 
     JOIN `tenants` t ON t.id = b.tenant_id 
     ORDER BY b.created_at DESC"
);

// Fetch tenant invoices
$invoices = Database::fetchAll(
    "SELECT i.*, t.company_name 
     FROM `tenant_invoices` i 
     JOIN `tenants` t ON t.id = i.tenant_id 
     ORDER BY i.created_at DESC"
);

// Fetch crypto payments
$cryptoPayments = Database::fetchAll(
    "SELECT c.*, t.company_name 
     FROM `crypto_payments` c 
     JOIN `tenants` t ON t.id = c.tenant_id 
     ORDER BY c.created_at DESC"
);

require BASE_PATH . '/views/super_admin/billing.php';

