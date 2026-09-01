<?php
/**
 * Test14DayCycleBilling — Automated test suite for strict Continuous 14-Day Cycle billing logic (No Calendar Month).
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

echo "=== Running Continuous 14-Day Cycle (No Calendar Month) Automated Tests ===\n\n";

// 1. Test Continuous Sequence Starting on September 1, 2026
$expectedSep1Cycles = [
    0 => ['start' => '2026-09-01', 'end' => '2026-09-14'],
    1 => ['start' => '2026-09-15', 'end' => '2026-09-28'],
    2 => ['start' => '2026-09-29', 'end' => '2026-10-12'],
    3 => ['start' => '2026-10-13', 'end' => '2026-10-26'],
    4 => ['start' => '2026-10-27', 'end' => '2026-11-09'],
    5 => ['start' => '2026-11-10', 'end' => '2026-11-23'],
    6 => ['start' => '2026-11-24', 'end' => '2026-12-07'],
    7 => ['start' => '2026-12-08', 'end' => '2026-12-21'],
    8 => ['start' => '2026-12-22', 'end' => '2027-01-04'],
    9 => ['start' => '2027-01-05', 'end' => '2027-01-18'],
];

foreach ($expectedSep1Cycles as $idx => $exp) {
    runTest("Period " . ($idx + 1) . " (Anchor: Sep 1): {$exp['start']} -> {$exp['end']}", function() use ($idx, $exp) {
        $cycle = AutoInvoiceEngine::get14DayCycleByIndex($idx, '2026-09-01');
        if ($cycle['start'] !== $exp['start'] || $cycle['end'] !== $exp['end']) {
            throw new Exception("Expected {$exp['start']} -> {$exp['end']}, got {$cycle['start']} -> {$cycle['end']}");
        }

        // Verify exact 14 calendar days
        $d1 = new DateTime($cycle['start']);
        $d2 = new DateTime($cycle['end']);
        $days = (int)$d1->diff($d2)->format('%a') + 1;
        if ($days !== 14) {
            throw new Exception("Period must have exactly 14 days, got {$days} days");
        }
    });
}

// 2. Test Trigger Days evaluating previous completed period (Anchor: Sep 1)
$triggerTests = [
    '2026-09-15' => ['start' => '2026-09-01', 'end' => '2026-09-14'],
    '2026-09-29' => ['start' => '2026-09-15', 'end' => '2026-09-28'],
    '2026-10-13' => ['start' => '2026-09-29', 'end' => '2026-10-12'],
    '2026-10-27' => ['start' => '2026-10-13', 'end' => '2026-10-26'],
    '2026-11-10' => ['start' => '2026-10-27', 'end' => '2026-11-09'],
    '2026-11-24' => ['start' => '2026-11-10', 'end' => '2026-11-23'],
    '2026-12-08' => ['start' => '2026-11-24', 'end' => '2026-12-07'],
    '2026-12-22' => ['start' => '2026-12-08', 'end' => '2026-12-21'],
    '2027-01-05' => ['start' => '2026-12-22', 'end' => '2027-01-04'],
    '2027-01-19' => ['start' => '2027-01-05', 'end' => '2027-01-18'],
];

foreach ($triggerTests as $trigDate => $exp) {
    runTest("Trigger on {$trigDate} -> Bills completed period {$exp['start']} -> {$exp['end']}", function() use ($trigDate, $exp) {
        $res = AutoInvoiceEngine::compute14DayCyclePeriod($trigDate, '2026-09-01');
        if ($res['start'] !== $exp['start'] || $res['end'] !== $exp['end']) {
            throw new Exception("Expected {$exp['start']} -> {$exp['end']}, got {$res['start']} -> {$res['end']}");
        }
    });
}

// 3. Test Example with Anchor Starting on January 10
$expectedJan10Cycles = [
    0 => ['start' => '2026-01-10', 'end' => '2026-01-23'],
    1 => ['start' => '2026-01-24', 'end' => '2026-02-06'],
    2 => ['start' => '2026-02-07', 'end' => '2026-02-20'],
    3 => ['start' => '2026-02-21', 'end' => '2026-03-06'],
    4 => ['start' => '2026-03-07', 'end' => '2026-03-20'],
    5 => ['start' => '2026-03-21', 'end' => '2026-04-03'],
    6 => ['start' => '2026-04-04', 'end' => '2026-04-17'],
];

foreach ($expectedJan10Cycles as $idx => $exp) {
    runTest("Period " . ($idx + 1) . " (Anchor: Jan 10): {$exp['start']} -> {$exp['end']}", function() use ($idx, $exp) {
        $cycle = AutoInvoiceEngine::get14DayCycleByIndex($idx, '2026-01-10');
        if ($cycle['start'] !== $exp['start'] || $cycle['end'] !== $exp['end']) {
            throw new Exception("Expected {$exp['start']} -> {$exp['end']}, got {$cycle['start']} -> {$cycle['end']}");
        }
    });
}

// 4. Test computePeriod integration
runTest("computePeriod integration for continuous 14-day cycle on 2026-10-13", function() {
    $res = AutoInvoiceEngine::computePeriod('every_14_days', 1, 14, 'net14', '2026-10-13');
    if ($res['start_date'] !== '2026-09-29' || $res['end_date'] !== '2026-10-12') {
        throw new Exception("Expected 2026-09-29 -> 2026-10-12, got {$res['start_date']} -> {$res['end_date']}");
    }
});

echo "\nALL CONTINUOUS 14-DAY CYCLE TESTS PASSED PERFECTLY!\n";
