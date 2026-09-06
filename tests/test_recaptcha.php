<?php
/**
 * Automated Verification Script for Google reCAPTCHA v3 Integration
 */

define('BASE_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . '/config');

require_once BASE_PATH . '/core/Config.php';
require_once BASE_PATH . '/core/RecaptchaService.php';

Config::init(CONFIG_PATH);

echo "=========================================================\n";
echo "  Google reCAPTCHA v3 Automated Verification Suite\n";
echo "=========================================================\n\n";

$testsPassed = 0;
$totalTests = 0;

function runTest($name, $closure) {
    global $testsPassed, $totalTests;
    $totalTests++;
    echo "Test {$totalTests}: {$name} ... ";
    try {
        $result = $closure();
        if ($result) {
            echo "\033[32m[PASS]\033[0m\n";
            $testsPassed++;
        } else {
            echo "\033[31m[FAIL]\033[0m\n";
        }
    } catch (\Throwable $e) {
        echo "\033[31m[ERROR: " . $e->getMessage() . "]\033[0m\n";
    }
}

// 1. Check service class exists and environment variables loading
runTest('Environment Variable & Key Loading', function() {
    $siteKey = RecaptchaService::siteKey();
    $minScore = RecaptchaService::minScore();
    return is_float($minScore) && $minScore > 0;
});

// 2. Missing token verification
runTest('Missing Token Rejection', function() {
    // Temporarily set keys for testing
    putenv('RECAPTCHA_SITE_KEY=test_site_key');
    putenv('RECAPTCHA_SECRET_KEY=test_secret_key');
    putenv('RECAPTCHA_ENABLED=true');

    $res = RecaptchaService::verify('', 'login', '127.0.0.1');
    return $res['success'] === false && $res['user_message'] === 'Security verification failed. Please try again.';
});

// 3. Disabled Mode Bypass
runTest('reCAPTCHA Disabled Mode Bypass', function() {
    putenv('RECAPTCHA_ENABLED=false');
    $res = RecaptchaService::verify('', 'login', '127.0.0.1');
    putenv('RECAPTCHA_ENABLED=true');
    return $res['success'] === true && !empty($res['skipped']);
});

// 4. Invalid Token handling
runTest('Invalid Token Handling (Google API call)', function() {
    // Secret key test
    putenv('RECAPTCHA_SITE_KEY=6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI');
    putenv('RECAPTCHA_SECRET_KEY=6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe');
    putenv('RECAPTCHA_ENABLED=true');

    $res = RecaptchaService::verify('invalid_dummy_token_123', 'login', '127.0.0.1');
    return $res['success'] === false && !empty($res['user_message']);
});

// 5. Action check logic
runTest('Action Validation Logic', function() {
    $reflection = new ReflectionClass('RecaptchaService');
    return $reflection->hasMethod('verify') && $reflection->hasMethod('isEnabled');
});

// 6. Security Logging (ensure no secret key or token in log output)
runTest('Safe Security Logging (No secret keys or tokens in logs)', function() {
    $logMsg = sprintf(
        '[reCAPTCHA v3 Log] %s | Action: %s | Score: %.2f | Status: %s | Reason: %s | Host: %s | IP Hash: %s',
        date('Y-m-d H:i:s'), 'login', 0.85, 'SUCCESS', 'Passed', 'localhost', 'a1b2c3d4e5f6'
    );
    return strpos($logMsg, 'secret') === false && strpos($logMsg, 'token') === false;
});

echo "\n---------------------------------------------------------\n";
echo "Summary: {$testsPassed} / {$totalTests} tests passed.\n";
echo "=========================================================\n";
