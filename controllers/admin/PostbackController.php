<?php
Auth::check('admin');
$pageTitle = 'Global Postbacks';

// ── Runtime migrations ────────────────────────────────────────────────────
try { Database::query("ALTER TABLE `global_postbacks` MODIFY COLUMN `status` ENUM('active','inactive','pending','rejected') DEFAULT 'pending'"); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE `global_postbacks` ADD COLUMN IF NOT EXISTS `type`         ENUM('advertiser','affiliate') NOT NULL DEFAULT 'advertiser'"); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE `global_postbacks` ADD COLUMN IF NOT EXISTS `affiliate_id` INT UNSIGNED DEFAULT NULL"); } catch (\Throwable $e) {}

// ── Helper: sync affiliate.global_postback_url from global_postbacks ─────
// Mirrors the active affiliate-type postback URL back into affiliates table
// so that postback.php (which reads affiliates.global_postback_url) keeps working.
function syncAffiliateGlobalPb(int $affId): void {
    $active = Database::fetchOne(
        "SELECT url FROM global_postbacks
         WHERE type='affiliate' AND affiliate_id=? AND status='active'
         ORDER BY id DESC LIMIT 1",
        [$affId]
    );
    if ($active) {
        Database::update('affiliates', [
            'global_postback_url'    => $active['url'],
            'global_pb_admin_status' => 'approved',
            'global_pb_active'       => 1,
            'global_pb_reviewed_at'  => date('Y-m-d H:i:s'),
            'global_pb_reviewed_by'  => Auth::id(),
        ], 'id=?', [$affId]);
    } else {
        // No active postback — deactivate without clearing the URL (preserves history)
        Database::update('affiliates', ['global_pb_active' => 0], 'id=?', [$affId]);
    }
}

// ── POST actions ──────────────────────────────────────────────────────────
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $postAction = Helpers::post('pb_action');

    // ── Add ───────────────────────────────────────────────────────────────
    if ($postAction === 'add') {
        $type   = Helpers::post('type') === 'affiliate' ? 'affiliate' : 'advertiser';
        $url    = trim(Helpers::postRaw('url'));
        $method = Helpers::post('method') === 'POST' ? 'POST' : 'GET';
        $affId  = $type === 'affiliate' ? (int)Helpers::postRaw('affiliate_id') : null;

        if (!$url) {
            Helpers::flash('error', 'Postback URL is required.');
            Helpers::redirect('/admin/postbacks');
        }
        if ($type === 'affiliate' && !$affId) {
            Helpers::flash('error', 'Please select an affiliate.');
            Helpers::redirect('/admin/postbacks');
        }

        // Build a clean name for the record
        $name = $type === 'affiliate' ? ('Affiliate Global Postback') : 'Advertiser Global Postback';

        Database::insert('global_postbacks', [
            'type'         => $type,
            'affiliate_id' => $affId,
            'event'        => 'conversion',
            'method'       => $method,
            'url'          => $url,
            'status'       => 'active',
            'name'         => $name,
        ]);

        if ($type === 'affiliate' && $affId) {
            syncAffiliateGlobalPb($affId);
        }

        Helpers::flash('success', ($type === 'affiliate' ? 'Affiliate' : 'Advertiser') . ' global postback added and activated.');
        Helpers::redirect('/admin/postbacks');
    }

    // ── Toggle active/inactive ────────────────────────────────────────────
    if ($postAction === 'toggle') {
        $id = (int)Helpers::postRaw('id');
        $pb = Database::fetchOne("SELECT * FROM global_postbacks WHERE id=?", [$id]);
        if ($pb) {
            $newStatus = $pb['status'] === 'active' ? 'inactive' : 'active';
            Database::update('global_postbacks', ['status' => $newStatus], 'id=?', [$id]);
            if ($pb['type'] === 'affiliate' && $pb['affiliate_id']) {
                syncAffiliateGlobalPb((int)$pb['affiliate_id']);
            }
            Helpers::flash('success', 'Postback ' . ($newStatus === 'active' ? 'activated' : 'deactivated') . '.');
        }
        Helpers::redirect('/admin/postbacks');
    }

    // ── Delete ────────────────────────────────────────────────────────────
    if ($postAction === 'delete') {
        $id = (int)Helpers::postRaw('id');
        $pb = Database::fetchOne("SELECT * FROM global_postbacks WHERE id=?", [$id]);
        Database::delete('global_postbacks', 'id=?', [$id]);
        if ($pb && $pb['type'] === 'affiliate' && $pb['affiliate_id']) {
            syncAffiliateGlobalPb((int)$pb['affiliate_id']);
        }
        Helpers::flash('success', 'Postback deleted.');
        Helpers::redirect('/admin/postbacks');
    }

    Helpers::redirect('/admin/postbacks');
}

// ── Page data ─────────────────────────────────────────────────────────────
$postbacks = Database::fetchAll(
    "SELECT gp.*,
            CONCAT(u.first_name,' ',u.last_name) AS aff_name,
            af.affiliate_code
     FROM global_postbacks gp
     LEFT JOIN affiliates af ON af.id = gp.affiliate_id
     LEFT JOIN users u ON u.id = af.user_id
     ORDER BY gp.type ASC, gp.status DESC, gp.created_at DESC"
);

$activeCount = count(array_filter($postbacks, fn($p) => $p['status'] === 'active'));

$affiliateList = Database::fetchAll(
    "SELECT af.id, af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) AS label
     FROM affiliates af
     JOIN users u ON u.id = af.user_id
     WHERE u.status = 'active'
     ORDER BY u.first_name, u.last_name"
);

$logs = Database::fetchAll(
    "SELECT gpl.*, gp.name AS pb_name, gp.type AS pb_type
     FROM global_postback_logs gpl
     LEFT JOIN global_postbacks gp ON gp.id = gpl.global_postback_id
     ORDER BY gpl.fired_at DESC LIMIT 50"
);

require BASE_PATH . '/views/admin/postbacks/index.php';
