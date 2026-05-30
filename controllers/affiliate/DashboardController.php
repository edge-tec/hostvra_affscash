<?php
Auth::check('affiliate');
$pageTitle = 'Analytics Dashboard';

// Schema migrations
try { Database::query("ALTER TABLE conversions ADD COLUMN is_hidden TINYINT(1) NOT NULL DEFAULT 0"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE conversions ADD COLUMN is_test TINYINT(1) NOT NULL DEFAULT 0"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE conversions ADD COLUMN country VARCHAR(10) DEFAULT NULL"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE affiliate_managers ADD COLUMN discord VARCHAR(200) DEFAULT NULL"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE affiliate_managers ADD COLUMN skype VARCHAR(200) DEFAULT NULL"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE affiliate_managers ADD COLUMN telegram VARCHAR(200) DEFAULT NULL"); } catch(Exception $e) {}

$affId = Auth::affiliateId();
$aff   = Database::fetchOne("SELECT * FROM affiliates WHERE id=?", [$affId]);

$appUrl = Helpers::trackingUrl();

// Approved offers for tracking links section.
// Private offers are excluded unless this affiliate is on the grant list
// (private_offer_access) — keeps the dashboard consistent with the offers list.
PrivateOffer::ensureTables();
$approvedOffers = Database::fetchAll(
    "SELECT o.*, ao.custom_payout
     FROM offers o
     JOIN affiliate_offers ao         ON ao.offer_id  = o.id
     LEFT JOIN private_offer_access poa ON poa.offer_id = o.id AND poa.affiliate_id = ?
     WHERE ao.affiliate_id=? AND ao.status='approved' AND o.status='active'
       AND (COALESCE(o.visibility,'public') != 'private' OR poa.id IS NOT NULL)",
    [$affId, $affId]
);

// Manager info
$managerInfo = null;
try {
    $managerInfo = Database::fetchOne(
        "SELECT u.first_name, u.last_name, u.email, am.skype, am.telegram, am.discord
         FROM affiliate_manager_affiliates ama
         JOIN affiliate_managers am ON am.id=ama.manager_id
         JOIN users u ON u.id=am.user_id
         WHERE ama.affiliate_id=? LIMIT 1", [$affId]
    );
    if (!$managerInfo) $managerInfo = Database::fetchOne(
        "SELECT u.first_name, u.last_name, u.email, am.skype, am.telegram, am.discord
         FROM affiliates af
         JOIN affiliate_managers am ON am.id=af.manager_id
         JOIN users u ON u.id=am.user_id
         WHERE af.id=? LIMIT 1", [$affId]
    ) ?: null;
} catch (Exception $e) {}

$_serverTz = Config::get('config','app.timezone') ?? 'UTC';

// Default date range (last 30 days) — JS will override these if a different TZ is saved client-side
$defaultFrom = date('Y-m-d', strtotime('-29 days'));
$defaultTo   = date('Y-m-d');

require BASE_PATH . '/views/affiliate/dashboard.php';
