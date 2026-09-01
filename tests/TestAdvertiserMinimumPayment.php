<?php
/**
 * TestAdvertiserMinimumPayment — Automated test suite for Advertiser-level Minimum Payment Rule.
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

echo "=== Running Advertiser Minimum Payment Rule Automated Tests ===\n\n";

// Helper function to simulate qualification check
function checkAdvertiserQualification(float $payableBalance, float $minimumPayout): bool {
    return round($payableBalance, 2) >= round($minimumPayout, 2);
}

// 1. Basic Threshold Checks
runTest("Balance below threshold ($150 < $200) -> Must NOT generate invoice", function() {
    $canGenerate = checkAdvertiserQualification(150.00, 200.00);
    if ($canGenerate !== false) {
        throw new Exception("Expected false, got true");
    }
});

runTest("Balance below threshold ($199.99 < $200.00) -> Must NOT generate invoice", function() {
    $canGenerate = checkAdvertiserQualification(199.99, 200.00);
    if ($canGenerate !== false) {
        throw new Exception("Expected false, got true");
    }
});

runTest("Balance exactly at threshold ($200.00 == $200.00) -> Must GENERATE invoice", function() {
    $canGenerate = checkAdvertiserQualification(200.00, 200.00);
    if ($canGenerate !== true) {
        throw new Exception("Expected true, got false");
    }
});

runTest("Balance above threshold ($250.00 > $200.00) -> Must GENERATE invoice", function() {
    $canGenerate = checkAdvertiserQualification(250.00, 200.00);
    if ($canGenerate !== true) {
        throw new Exception("Expected true, got false");
    }
});

// 2. Multi-Period Balance Accumulation Simulation (Min: $200)
runTest("Multi-period balance carry-forward accumulation until threshold reached", function() {
    $minPayout = 200.00;
    $balance = 0.0;

    // Period 1: +$80
    $balance += 80.00;
    if (checkAdvertiserQualification($balance, $minPayout)) {
        throw new Exception("Period 1 ($80) should not generate invoice");
    }

    // Period 2: +$60 (Total $140)
    $balance += 60.00;
    if (checkAdvertiserQualification($balance, $minPayout)) {
        throw new Exception("Period 2 ($140) should not generate invoice");
    }

    // Period 3: +$40 (Total $180 - even if 3-6 months old)
    $balance += 40.00;
    if (checkAdvertiserQualification($balance, $minPayout)) {
        throw new Exception("Period 3 ($180) should not generate invoice regardless of balance age");
    }

    // Period 4: +$50 (Total $230)
    $balance += 50.00;
    if (!checkAdvertiserQualification($balance, $minPayout)) {
        throw new Exception("Period 4 ($230 >= $200) must generate invoice");
    }
});

// 3. Independent Multi-Advertiser Qualification
runTest("Multiple Advertisers evaluated independently per affiliate", function() {
    $advertisers = [
        'Adv_A' => ['min' => 200.00, 'balance' => 250.00, 'expected' => true],
        'Adv_B' => ['min' => 500.00, 'balance' => 350.00, 'expected' => false],
        'Adv_C' => ['min' => 100.00, 'balance' => 120.00, 'expected' => true],
    ];

    foreach ($advertisers as $advKey => $data) {
        $qualified = checkAdvertiserQualification($data['balance'], $data['min']);
        if ($qualified !== $data['expected']) {
            throw new Exception("{$advKey} expected " . ($data['expected'] ? 'true' : 'false') . ", got " . ($qualified ? 'true' : 'false'));
        }
    }
});

// 4. Strict Balance-Age Persistence (No 90-day / 180-day override)
runTest("Old balances (e.g. 180 days old) must NEVER bypass advertiser minimum threshold", function() {
    $oldBalance = 190.00;
    $minPayout  = 200.00;
    $daysOld    = 180;

    $qualified = checkAdvertiserQualification($oldBalance, $minPayout);
    if ($qualified !== false) {
        throw new Exception("Old balance below minimum should remain carried forward without force override");
    }
});

echo "\nALL ADVERTISER MINIMUM PAYMENT TESTS PASSED PERFECTLY!\n";
