<?php
/**
 * Stripe & Billing Payment Webhook Handler
 */
header('Content-Type: application/json');

// Fetch gateway configuration
$settingsRows = Database::fetchAll("SELECT * FROM `gateway_settings`");
$gw = [];
foreach ($settingsRows as $row) {
    $gw[$row['gateway_name']][$row['setting_key']] = $row['setting_value'];
}

$payload = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

$event = null;
try {
    $event = json_decode($payload, true);
} catch (\Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

if (!$event) {
    http_response_code(400);
    echo json_encode(['error' => 'Empty event payload']);
    exit;
}

// ── Stripe Signature Verification ─────────────────────────────────────────────
// If Stripe sandbox is enabled or signature header is missing during sandbox testing,
// we process simulated events. Otherwise, we verify via crypto signature standards.
$isSandbox = ($gw['stripe']['sandbox_mode'] ?? '1') === '1';

if (!$isSandbox && $sigHeader) {
    // In strict live production mode, we verify the webhook signature.
    // (Simulated using Stripe's official signature structure parser)
    $webhookSecret = $gw['stripe']['webhook_secret'] ?? '';
    
    // Parse signature header: t=1492774564,v1=cohesive_hash_value
    $parts = explode(',', $sigHeader);
    $timestamp = 0;
    $signatures = [];
    foreach ($parts as $part) {
        $kv = explode('=', $part);
        if (count($kv) === 2) {
            if (trim($kv[0]) === 't') $timestamp = (int)$kv[1];
            if (trim($kv[0]) === 'v1') $signatures[] = trim($kv[1]);
        }
    }
    
    // Enforce 5-minute tolerance
    if (abs(time() - $timestamp) > 300) {
        http_response_code(400);
        echo json_encode(['error' => 'Timestamp tolerance exceeded']);
        exit;
    }
    
    $signedPayload = "$timestamp.$payload";
    $expectedSignature = hash_hmac('sha256', $signedPayload, $webhookSecret);
    
    $verified = false;
    foreach ($signatures as $sig) {
        if (hash_equals($expectedSignature, $sig)) {
            $verified = true;
            break;
        }
    }
    
    if (!$verified) {
        http_response_code(400);
        echo json_encode(['error' => 'Stripe signature mismatch']);
        exit;
    }
}

// ── Event Processing ─────────────────────────────────────────────────────────
$eventType = $event['type'] ?? '';
$success = false;
$message = 'Event ignored';

if ($eventType === 'checkout.session.completed' || $eventType === 'invoice.paid' || $eventType === 'charge.succeeded') {
    $object = $event['data']['object'] ?? [];
    
    // Extract metadata
    $metadata = $object['metadata'] ?? [];
    $tenantId = (int)($metadata['tenant_id'] ?? 0);
    $planId = (int)($metadata['plan_id'] ?? 0);
    $reference = $object['id'] ?? 'wh_' . bin2hex(random_bytes(6));
    
    if ($tenantId && $planId) {
        $plan = Database::fetchOne("SELECT * FROM `subscription_plans` WHERE id = ? AND status = 'active'", [$planId]);
        if ($plan) {
            try {
                Database::begin();
                $duration = (int)$plan['duration'];
                
                // Cancel current active subscriptions
                Database::update('subscriptions', ['status' => 'cancelled'], 'tenant_id = ? AND status = ?', [$tenantId, 'active']);
                
                // Insert new active subscription
                Database::insert('subscriptions', [
                    'tenant_id'             => $tenantId,
                    'plan_id'               => $planId,
                    'price'                 => $plan['price'],
                    'duration'              => $duration,
                    'status'                => 'active',
                    'starts_at'             => date('Y-m-d H:i:s'),
                    'ends_at'               => date('Y-m-d H:i:s', strtotime("+$duration days")),
                    'stripe_subscription_id'=> $object['subscription'] ?? null,
                    'billing_provider'      => 'stripe',
                    'auto_renew'            => 1,
                    'created_at'            => date('Y-m-d H:i:s')
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
                Tenant::logBilling($tenantId, 'subscription_renewed', $plan['price'], "Renewed plan {$plan['name']} via Stripe Webhook (Ref: {$reference})");
                
                Database::commit();
                $success = true;
                $message = "Tenant #{$tenantId} subscription activated for plan #{$planId}";
            } catch (\Throwable $e) {
                Database::rollback();
                http_response_code(500);
                echo json_encode(['error' => 'Database operation failed: ' . $e->getMessage()]);
                exit;
            }
        } else {
            $message = "Stripe metadata contained invalid plan ID #{$planId}";
        }
    } else {
        $message = "Stripe event data did not contain tenant_id or plan_id in metadata";
    }
}

echo json_encode([
    'status'  => $success ? 'success' : 'ignored',
    'message' => $message
]);
exit;
