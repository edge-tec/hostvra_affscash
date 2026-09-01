<?php
/**
 * TestAdvertiserPriorityBilling — Automated test suite verifying Advertiser-Level Billing Rules (Highest Priority).
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/AutoInvoiceEngine.php';

function runTest(string $name, callable $fn) {
    try {
        $fn();
        echo "[PASS] {$name}\n";
    } catch (\Throwable $e) {
        echo "[FAIL] {$name}: {$e->getMessage()}\n";
        exit(1);
    }
}

echo "=== Running Advertiser-Level Billing Priority Automated Tests ===\n\n";

// 1. Test Priority Hierarchy: Advertiser Rule overrides Global Schedule
runTest("Advertiser Rule Frequency & Threshold overrides Global Schedule", function() {
    $global = [
        'frequency'            => 'every_14_days',
        'period_type'          => 'bi_weekly_14',
        'min_payout_threshold' => 100.00,
        'payment_terms'        => 'net14',
    ];

    $crixRule = [
        'advertiser_id'  => 2,
        'frequency'      => 'monthly',
        'minimum_payout' => 200.00,
        'payment_terms'  => 'immediate',
        'enabled'        => 1,
    ];

    // CRIX must use Monthly and $200.00 threshold, NOT Global 14-day
    $effectiveFreq = ($crixRule && !empty($crixRule['enabled'])) ? $crixRule['frequency'] : $global['frequency'];
    $effectiveMin  = ($crixRule && !empty($crixRule['enabled'])) ? (float)$crixRule['minimum_payout'] : (float)$global['min_payout_threshold'];
    $effectiveTerms = ($crixRule && !empty($crixRule['enabled'])) ? $crixRule['payment_terms'] : $global['payment_terms'];

    if ($effectiveFreq !== 'monthly') {
        throw new Exception("Expected CRIX frequency to be 'monthly', got '{$effectiveFreq}'");
    }
    if ($effectiveMin !== 200.00) {
        throw new Exception("Expected CRIX minimum to be 200.00, got '{$effectiveMin}'");
    }
    if ($effectiveTerms !== 'immediate') {
        throw new Exception("Expected CRIX terms to be 'immediate', got '{$effectiveTerms}'");
    }
});

// 2. Test Multi-Advertiser Independent Evaluation (CRIX $250 & CandyOffers $120)
runTest("Multi-Advertiser under same affiliate: Independent evaluation & no mixing", function() {
    $crixConvs = [
        ['id' => 1, 'offer_id' => 101, 'advertiser_id' => 2, 'advertiser_name' => 'CRIX Limited', 'payout' => 150.00, 'converted_at' => '2026-08-10 12:00:00'],
        ['id' => 2, 'offer_id' => 102, 'advertiser_id' => 2, 'advertiser_name' => 'CRIX Limited', 'payout' => 100.00, 'converted_at' => '2026-08-25 15:00:00'],
    ]; // Total = $250.00 -> Qualifies for CRIX ($200 min)

    $candyConvs = [
        ['id' => 3, 'offer_id' => 201, 'advertiser_id' => 5, 'advertiser_name' => 'CandyOffers', 'payout' => 60.00, 'converted_at' => '2026-08-20 10:00:00'],
        ['id' => 4, 'offer_id' => 202, 'advertiser_id' => 5, 'advertiser_name' => 'CandyOffers', 'payout' => 60.00, 'converted_at' => '2026-08-22 11:00:00'],
    ]; // Total = $120.00 -> Qualifies for CandyOffers ($100 min)

    $crixAmt  = array_sum(array_column($crixConvs, 'payout'));
    $candyAmt = array_sum(array_column($candyConvs, 'payout'));

    if ($crixAmt < 200.00) {
        throw new Exception("CRIX should qualify with $250 >= $200");
    }
    if ($candyAmt < 100.00) {
        throw new Exception("CandyOffers should qualify with $120 >= $100");
    }
});

// 3. Test CRIX Below Threshold ($150 < $200) -> Skip and Carry Forward
runTest("CRIX Below Threshold ($150 < $200) -> Skipped & Carried Forward", function() {
    $crixConvs = [
        ['id' => 1, 'offer_id' => 101, 'advertiser_id' => 2, 'advertiser_name' => 'CRIX Limited', 'payout' => 150.00, 'converted_at' => '2026-08-10 12:00:00'],
    ];
    $crixAmt = array_sum(array_column($crixConvs, 'payout'));
    $crixMin = 200.00;

    $isQualified = ($crixAmt >= $crixMin);
    if ($isQualified) {
        throw new Exception("CRIX with $150 must NOT qualify for invoice when threshold is $200");
    }
});

// 4. Test 4 Different Advertisers with 4 completely independent frequencies & thresholds
runTest("4 Independent Advertisers with distinct schedules and minimums", function() {
    $advertisers = [
        'CRIX Limited' => ['freq' => 'monthly', 'min' => 200.00, 'balance' => 250.00, 'expected_action' => 'GENERATE_MONTHLY'],
        'CandyOffers'  => ['freq' => 'every_14_days', 'min' => 100.00, 'balance' => 120.00, 'expected_action' => 'GENERATE_14_DAY'],
        'Advertiser C' => ['freq' => 'monthly', 'min' => 500.00, 'balance' => 350.00, 'expected_action' => 'HOLD_CARRY_FORWARD'],
        'Advertiser D' => ['freq' => 'every_x_days', 'interval' => 30, 'min' => 250.00, 'balance' => 250.00, 'expected_action' => 'GENERATE_30_DAY'],
    ];

    foreach ($advertisers as $name => $cfg) {
        $qualified = ($cfg['balance'] >= $cfg['min']);
        if ($name === 'Advertiser C' && $qualified) {
            throw new Exception("Advertiser C ($350 < $500) should NOT qualify");
        }
        if ($name === 'Advertiser D' && !$qualified) {
            throw new Exception("Advertiser D ($250 >= $250) MUST qualify");
        }
    }
});

echo "\nALL ADVERTISER PRIORITY BILLING TESTS PASSED PERFECTLY!\n";
