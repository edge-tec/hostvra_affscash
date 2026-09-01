<?php
/**
 * Test14DayCycleBilling — Automated test suite for strict 14-Day Cycle billing period logic.
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

echo "=== Running 14-Day Cycle Rule Automated Tests ===\n\n";

// 1. Test 31-day month (August 2026 - 31 days)
runTest("August 15 mid-month trigger (July has 31 days -> start on Jul 31)", function() {
    $res = AutoInvoiceEngine::compute14DayCyclePeriod('2026-08-15');
    if ($res['start'] !== '2026-07-31' || $res['end'] !== '2026-08-14') {
        throw new Exception("Expected 2026-07-31 to 2026-08-14, got {$res['start']} to {$res['end']}");
    }
});

runTest("August 31 month-end trigger (bills Aug 15..30, carries Aug 31 forward)", function() {
    $res = AutoInvoiceEngine::compute14DayCyclePeriod('2026-08-31');
    if ($res['start'] !== '2026-08-15' || $res['end'] !== '2026-08-30') {
        throw new Exception("Expected 2026-08-15 to 2026-08-30, got {$res['start']} to {$res['end']}");
    }
});

runTest("September 01 trigger (bills previous month Period 2: Aug 15..30)", function() {
    $res = AutoInvoiceEngine::compute14DayCyclePeriod('2026-09-01');
    if ($res['start'] !== '2026-08-15' || $res['end'] !== '2026-08-30') {
        throw new Exception("Expected 2026-08-15 to 2026-08-30, got {$res['start']} to {$res['end']}");
    }
});

runTest("September 15 trigger (August had 31 days -> bills Aug 31 to Sep 14)", function() {
    $res = AutoInvoiceEngine::compute14DayCyclePeriod('2026-09-15');
    if ($res['start'] !== '2026-08-31' || $res['end'] !== '2026-09-14') {
        throw new Exception("Expected 2026-08-31 to 2026-09-14, got {$res['start']} to {$res['end']}");
    }
});

// 2. Test 30-day month (September 2026 - 30 days)
runTest("October 01 trigger (bills September Period 2: Sep 15..30)", function() {
    $res = AutoInvoiceEngine::compute14DayCyclePeriod('2026-10-01');
    if ($res['start'] !== '2026-09-15' || $res['end'] !== '2026-09-30') {
        throw new Exception("Expected 2026-09-15 to 2026-09-30, got {$res['start']} to {$res['end']}");
    }
});

runTest("October 15 trigger (September had 30 days -> bills Oct 01 to Oct 14)", function() {
    $res = AutoInvoiceEngine::compute14DayCyclePeriod('2026-10-15');
    if ($res['start'] !== '2026-10-01' || $res['end'] !== '2026-10-14') {
        throw new Exception("Expected 2026-10-01 to 2026-10-14, got {$res['start']} to {$res['end']}");
    }
});

// 3. Test 28-day February (February 2026)
runTest("February 15 trigger (January had 31 days -> bills Jan 31 to Feb 14)", function() {
    $res = AutoInvoiceEngine::compute14DayCyclePeriod('2026-02-15');
    if ($res['start'] !== '2026-01-31' || $res['end'] !== '2026-02-14') {
        throw new Exception("Expected 2026-01-31 to 2026-02-14, got {$res['start']} to {$res['end']}");
    }
});

runTest("March 01 trigger (bills February Period 2: Feb 15..28)", function() {
    $res = AutoInvoiceEngine::compute14DayCyclePeriod('2026-03-01');
    if ($res['start'] !== '2026-02-15' || $res['end'] !== '2026-02-28') {
        throw new Exception("Expected 2026-02-15 to 2026-02-28, got {$res['start']} to {$res['end']}");
    }
});

runTest("March 15 trigger (February had 28 days -> bills Mar 01 to Mar 14)", function() {
    $res = AutoInvoiceEngine::compute14DayCyclePeriod('2026-03-15');
    if ($res['start'] !== '2026-03-01' || $res['end'] !== '2026-03-14') {
        throw new Exception("Expected 2026-03-01 to 2026-03-14, got {$res['start']} to {$res['end']}");
    }
});

// 4. Test 29-day Leap Year February (February 2028)
runTest("March 01 leap year trigger (bills Feb 15..29 in leap year 2028)", function() {
    $res = AutoInvoiceEngine::compute14DayCyclePeriod('2028-03-01');
    if ($res['start'] !== '2028-02-15' || $res['end'] !== '2028-02-29') {
        throw new Exception("Expected 2028-02-15 to 2028-02-29, got {$res['start']} to {$res['end']}");
    }
});

// 5. Test Year Transitions (December 31 -> January)
runTest("December 31 month-end trigger (bills Dec 15..30, carries Dec 31 forward)", function() {
    $res = AutoInvoiceEngine::compute14DayCyclePeriod('2026-12-31');
    if ($res['start'] !== '2026-12-15' || $res['end'] !== '2026-12-30') {
        throw new Exception("Expected 2026-12-15 to 2026-12-30, got {$res['start']} to {$res['end']}");
    }
});

runTest("January 15 new year trigger (December had 31 days -> bills Dec 31, 2026 to Jan 14, 2027)", function() {
    $res = AutoInvoiceEngine::compute14DayCyclePeriod('2027-01-15');
    if ($res['start'] !== '2026-12-31' || $res['end'] !== '2027-01-14') {
        throw new Exception("Expected 2026-12-31 to 2027-01-14, got {$res['start']} to {$res['end']}");
    }
});

// 6. Test computePeriod with frequency='every_14_days'
runTest("computePeriod with frequency='every_14_days' and refDate='2026-09-15'", function() {
    $res = AutoInvoiceEngine::computePeriod('every_14_days', 1, 14, 'net14', '2026-09-15');
    if ($res['start_date'] !== '2026-08-31' || $res['end_date'] !== '2026-09-14') {
        throw new Exception("Expected 2026-08-31 to 2026-09-14, got {$res['start_date']} to {$res['end_date']}");
    }
});

echo "\nALL 14-DAY CYCLE TESTS PASSED PERFECTLY!\n";
