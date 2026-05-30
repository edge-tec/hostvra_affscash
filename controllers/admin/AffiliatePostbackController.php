<?php
Auth::checkAny(['admin', 'affiliate_manager']);
$pageTitle = 'Affiliate Postback Management';

// Runtime table migration
try { Database::query("ALTER TABLE `postbacks` ADD COLUMN `admin_status` ENUM('pending','approved','rejected') DEFAULT 'approved'"); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE `postbacks` MODIFY COLUMN `admin_status` ENUM('pending','approved','rejected') DEFAULT 'approved'"); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE `postbacks` ADD COLUMN `admin_note` VARCHAR(255) DEFAULT NULL"); } catch (\Throwable $e) {}
// Approve all active postbacks that are still in pending state (default was incorrectly 'pending')
try { Database::query("UPDATE `postbacks` SET `admin_status`='approved' WHERE `status`='active' AND (`admin_status` IS NULL OR `admin_status`='pending')"); } catch (\Throwable $e) {}

// ── POST actions ──────────────────────────────────────────────────────────
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $action = Helpers::post('pb_admin_action');
    $id     = (int)Helpers::postRaw('pb_id');

    // Approve
    if ($action === 'approve' && $id) {
        Database::update('postbacks', ['admin_status'=>'approved','status'=>'active'], 'id=?', [$id]);
        Helpers::flash('success', 'Postback approved and activated.');
    }
    // Reject
    elseif ($action === 'reject' && $id) {
        $note = trim(Helpers::postRaw('admin_note'));
        Database::update('postbacks', ['admin_status'=>'rejected','status'=>'inactive','admin_note'=>$note], 'id=?', [$id]);
        Helpers::flash('success', 'Postback rejected.');
    }
    // Toggle active/inactive
    elseif ($action === 'toggle' && $id) {
        $pb = Database::fetchOne("SELECT status FROM postbacks WHERE id=?", [$id]);
        if ($pb) Database::update('postbacks', ['status'=>$pb['status']==='active'?'inactive':'active'], 'id=?', [$id]);
        Helpers::flash('success', 'Postback status toggled.');
    }
    // Admin add postback for affiliate
    elseif ($action === 'add') {
        $affId   = (int)Helpers::postRaw('affiliate_id');
        $url     = trim(Helpers::postRaw('url'));
        $method  = Helpers::post('method') === 'POST' ? 'POST' : 'GET';
        $event   = in_array(Helpers::post('event'), ['conversion','click','rejection']) ? Helpers::post('event') : 'conversion';
        $offerId = (int)Helpers::postRaw('offer_id') ?: null;
        if ($affId && $url) {
            Database::insert('postbacks', [
                'affiliate_id'   => $affId,
                'offer_id'       => $offerId,
                'event'          => $event,
                'method'         => $method,
                'url'            => $url,
                'status'         => 'active',
                'fire_on_status' => 'approved',
                'admin_status'   => 'approved',
            ]);
            Helpers::flash('success', 'Postback added for affiliate.');
        }
    }
    // Admin edit postback URL
    elseif ($action === 'edit' && $id) {
        $url    = trim(Helpers::postRaw('url'));
        $method = Helpers::post('method') === 'POST' ? 'POST' : 'GET';
        if ($url) {
            Database::update('postbacks', ['url'=>$url,'method'=>$method], 'id=?', [$id]);
            Helpers::flash('success', 'Postback updated.');
        }
    }
    // Delete
    elseif ($action === 'delete' && $id) {
        Database::query("DELETE FROM postbacks WHERE id=?", [$id]);
        Helpers::flash('success', 'Postback deleted.');
    }

    $redirectAff = (int)Helpers::postRaw('redirect_aff');
    Helpers::redirect('/admin/affiliate-postbacks' . ($redirectAff ? '?aff='.$redirectAff : ''));
}

// ── Filter ────────────────────────────────────────────────────────────────
$filterAffId = (int)Helpers::get('aff');
$filterStatus = Helpers::get('status', 'all');

$where  = [];
$params = [];
if ($filterAffId) { $where[] = 'pb.affiliate_id=?'; $params[] = $filterAffId; }
if ($filterStatus === 'pending')  { $where[] = "pb.admin_status='pending'"; }
if ($filterStatus === 'rejected') { $where[] = "pb.admin_status='rejected'"; }
if ($filterStatus === 'active')   { $where[] = "pb.status='active'"; }
$whereStr = $where ? 'WHERE '.implode(' AND ',$where) : '';

$postbacks = Database::fetchAll(
    "SELECT pb.*,
            CONCAT(u.first_name,' ',u.last_name) as aff_name,
            u.email as aff_email,
            af.affiliate_code,
            o.name as offer_name
     FROM postbacks pb
     JOIN affiliates af ON af.id=pb.affiliate_id
     JOIN users u ON u.id=af.user_id
     LEFT JOIN offers o ON o.id=pb.offer_id
     $whereStr
     ORDER BY pb.created_at DESC
     LIMIT 500",
    $params
);

// Counts
$pendingCount  = Database::fetchOne("SELECT COUNT(*) as c FROM postbacks WHERE admin_status='pending'")['c'] ?? 0;
$totalCount    = Database::fetchOne("SELECT COUNT(*) as c FROM postbacks")['c'] ?? 0;

// Lists for add form
$affiliateList = Database::fetchAll(
    "SELECT af.id, af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) as label
     FROM affiliates af JOIN users u ON u.id=af.user_id
     WHERE u.status='active' ORDER BY u.first_name, u.last_name"
);
$offerList = Database::fetchAll("SELECT id, name FROM offers WHERE status='active' ORDER BY name");

require BASE_PATH . '/views/admin/affiliate_postbacks/index.php';
