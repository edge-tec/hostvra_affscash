<?php
Auth::check('affiliate');
$pageTitle = 'Live Support';
$affId = Auth::affiliateId();

// ensure table exists
try { Database::query("CREATE TABLE IF NOT EXISTS support_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    affiliate_id INT NOT NULL,
    sender_id INT NOT NULL,
    sender_role VARCHAR(30) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_aff (affiliate_id)
)"); } catch(Exception $e) {}

// manager info
$managerInfo = null;
try {
    $managerInfo = Database::fetchOne(
        "SELECT u.first_name, u.last_name, u.email, am.id as manager_id
         FROM affiliate_manager_affiliates ama JOIN affiliate_managers am ON am.id=ama.manager_id JOIN users u ON u.id=am.user_id
         WHERE ama.affiliate_id=? LIMIT 1", [$affId]);
    if (!$managerInfo) $managerInfo = Database::fetchOne(
        "SELECT u.first_name, u.last_name, u.email FROM affiliates af JOIN affiliate_managers am ON am.id=af.manager_id JOIN users u ON u.id=am.user_id WHERE af.id=? LIMIT 1", [$affId]);
} catch(Exception $e) {}

require BASE_PATH . '/views/affiliate/support.php';
