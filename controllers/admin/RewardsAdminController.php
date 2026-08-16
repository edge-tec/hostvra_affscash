<?php
/**
 * Admin → Rewards module v2.
 *
 * Existing admin actions (save_rule, delete_rule, update_grant) are PRESERVED
 * exactly as before. This file just adds new fields + new POST actions on top:
 *   - image upload + previous-image clearing
 *   - safe embed_code handling (HTML stored as-is; rendered inside a
 *     sandboxed iframe on the affiliate side so scripts cannot escape)
 *   - duplicate / toggle_active / save_order / save_badge / delete_badge
 *   - search + filter via querystring
 */
Auth::check('admin');
$pageTitle = 'Rewards Module';

if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $sub = Helpers::post('submit_type');

    if ($sub === 'save_rule') {
        $id = (int)Helpers::postRaw('id');

        // ── Image upload (additive — only writes a new path if a file came in) ──
        $imagePathField = 'image_path_keep';
        $newImagePath   = null;
        $imageProvided  = false;
        if (!empty($_FILES['image']['tmp_name'])) {
            $allowed = ['image/png','image/jpeg','image/gif','image/webp'];
            $mime    = @mime_content_type($_FILES['image']['tmp_name']);
            $size    = (int)$_FILES['image']['size'];
            if (in_array($mime, $allowed, true) && $size <= 4 * 1024 * 1024) {
                $dir = BASE_PATH . '/assets/uploads/rewards/';
                if (!is_dir($dir)) @mkdir($dir, 0775, true);
                $ext  = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION) ?: 'jpg');
                $safe = 'rw_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . preg_replace('/[^a-z0-9]/', '', $ext);
                if (@move_uploaded_file($_FILES['image']['tmp_name'], $dir . $safe)) {
                    $newImagePath = '/assets/uploads/rewards/' . $safe;
                    $imageProvided = true;
                }
            }
        }
        if (Helpers::post('clear_image') === '1') {
            $newImagePath  = null;
            $imageProvided = true;   // explicitly clearing
        }

        // ── Safe embed sanitization ─────────────────────────────────────────
        // Admin embed code is allowed but rendered inside a sandboxed iframe
        // with srcdoc on the affiliate side (Content-Security-Policy-safe).
        // We still strip null bytes and limit length to keep storage sane.
        $embed = (string)Helpers::postRaw('embed_code');
        $embed = str_replace("\0", '', $embed);
        if (strlen($embed) > 20000) $embed = substr($embed, 0, 20000);

        $data = [
            'threshold_usd' => (float)Helpers::postRaw('threshold_usd'),
            'kind'          => Helpers::post('kind'),
            'value_amount'  => Helpers::postRaw('value_amount'),
            'value_text'    => Helpers::postRaw('value_text'),
            'title'         => Helpers::postRaw('title'),
            'description'   => Helpers::postRaw('description'),
            'active'        => isset($_POST['active']) && $_POST['active'] === '1' ? 1 : 0,
            'embed_code'    => $embed,
            'badge_label'   => Helpers::postRaw('badge_label'),
            'badge_color'   => Helpers::postRaw('badge_color'),
            'visibility'    => Helpers::post('visibility'),
            'publish_at'    => Helpers::postRaw('publish_at'),
            'expires_at'    => Helpers::postRaw('expires_at'),
            'sort_order'    => (int)Helpers::postRaw('sort_order'),
        ];
        if ($imageProvided) $data['image_path'] = $newImagePath;

        if ((float)$data['threshold_usd'] <= 0) {
            Helpers::flash('error', 'Threshold must be greater than zero.');
        } else {
            $newId = RewardsService::saveRule($data, $id ?: null);
            // Email all active affiliates when a brand-new reward is added.
            // Updates never re-blast. Inactive rules also skip — the affiliate
            // can't see them yet, so don't tell them.
            if (!$id && $newId > 0 && (int)$data['active'] === 1) {
                try {
                    require_once BASE_PATH . '/core/AffiliateBroadcastNotifier.php';
                    $rule = Database::fetchOne("SELECT * FROM reward_rules WHERE id=? LIMIT 1", [$newId]);
                    if ($rule) {
                        AffiliateBroadcastNotifier::notifyRewardAdded($rule);
                    }
                } catch (\Throwable $_e) {
                    error_log('[RewardsAdminController] notify failed: ' . $_e->getMessage());
                }
            }
            Helpers::flash('success', $id ? 'Rule updated.' : 'Rule created.');
        }
        Helpers::redirect('/admin/rewards');
    }

    if ($sub === 'delete_rule') {
        $id = (int)Helpers::postRaw('id');
        if ($id > 0) { RewardsService::deleteRule($id); Helpers::flash('success', 'Rule deleted.'); }
        Helpers::redirect('/admin/rewards');
    }

    if ($sub === 'duplicate_rule') {
        $id  = (int)Helpers::postRaw('id');
        $new = RewardsService::duplicateRule($id);
        Helpers::flash($new ? 'success' : 'error', $new ? 'Rule duplicated (created inactive — review and enable).' : 'Could not duplicate.');
        Helpers::redirect('/admin/rewards');
    }

    if ($sub === 'toggle_active') {
        $id = (int)Helpers::postRaw('id');
        if ($id > 0) { RewardsService::toggleActive($id); }
        Helpers::redirect('/admin/rewards' . ($id ? '' : ''));
    }

    if ($sub === 'save_order') {
        $orderIds = $_POST['order'] ?? [];
        if (is_array($orderIds)) {
            RewardsService::reorder($orderIds);
            Helpers::flash('success', 'Reward order saved.');
        }
        Helpers::redirect('/admin/rewards');
    }

    if ($sub === 'save_badge') {
        $label = trim((string)Helpers::postRaw('badge_new_label'));
        $color = trim((string)Helpers::postRaw('badge_new_color'));
        if ($label && RewardsService::addBadge($label, $color ?: '#4F46E5')) {
            Helpers::flash('success', 'Badge added.');
        } else {
            Helpers::flash('error', 'Could not add badge (empty label or duplicate).');
        }
        Helpers::redirect('/admin/rewards');
    }

    if ($sub === 'delete_badge') {
        $label = trim((string)Helpers::postRaw('label'));
        if ($label !== '') {
            RewardsService::deleteBadge($label);
            Helpers::flash('success', 'Badge removed.');
        }
        Helpers::redirect('/admin/rewards');
    }

    if ($sub === 'update_grant') {
        $gid    = (int)Helpers::postRaw('grant_id');
        $stat   = (string)Helpers::post('status');
        $note   = trim((string)Helpers::postRaw('admin_note'));
        if ($gid > 0) {
            RewardsService::updateGrantStatus($gid, $stat, $note ?: null);
            // Optionally trigger notifications if requested
            try {
                RewardsService::sendRewardClaimNotifications($gid);
            } catch (\Throwable $_) {}
            Helpers::flash('success', 'Grant status updated.');
        }
        Helpers::redirect('/admin/rewards?tab=grants');
    }

    if ($sub === 'resend_reward_email') {
        $gid = (int)Helpers::postRaw('grant_id');
        if ($gid > 0) {
            $res = RewardsService::sendRewardClaimNotifications($gid, true);
            $affMail = $res['affiliate_email'] ?? 'Affiliate';
            $admMail = $res['admin_email']     ?? 'Admin';

            if (!empty($res['affiliate_sent']) && !empty($res['admin_sent'])) {
                Helpers::flash('success', "Reward claim emails resent successfully to Affiliate ({$affMail}) and Admin ({$admMail}).");
            } elseif (!empty($res['affiliate_sent'])) {
                $err = !empty($res['admin_err']) ? $res['admin_err'] : implode(' ', $res['errors']);
                Helpers::flash('warning', "Affiliate email sent to {$affMail}, but Admin email failed: {$err}");
            } elseif (!empty($res['admin_sent'])) {
                $err = !empty($res['affiliate_err']) ? $res['affiliate_err'] : implode(' ', $res['errors']);
                Helpers::flash('warning', "Admin email sent to {$admMail}, but Affiliate email failed: {$err}");
            } else {
                $errStr = implode(' | ', $res['errors']);
                Helpers::flash('error', 'Email delivery failed: ' . ($errStr ?: 'Check SMTP configuration in Admin Settings > Mailer.'));
            }
        }
        Helpers::redirect('/admin/rewards?tab=grants');
    }
}

// Action router: create / edit load dedicated form page (Shop-style modular architecture)
$action = Helpers::get('action');
if ($action === 'create') {
    $rule   = null;
    $badges = RewardsService::badges();
    require BASE_PATH . '/views/admin/rewards/edit.php';
    return;
}
if ($action === 'edit' && Helpers::get('id')) {
    $ruleId = (int)Helpers::get('id');
    $rule   = Database::fetchOne("SELECT * FROM reward_rules WHERE id = ? LIMIT 1", [$ruleId]);
    if (!$rule) {
        Helpers::flash('error', 'Reward rule not found.');
        Helpers::redirect('/admin/rewards');
    }
    $badges = RewardsService::badges();
    require BASE_PATH . '/views/admin/rewards/edit.php';
    return;
}

// Search + filter
$filters = [
    'search'     => trim((string)(Helpers::get('q') ?? '')),
    'kind'       => Helpers::get('kind') ?: '',
    'visibility' => Helpers::get('vis')  ?: '',
    'active'     => Helpers::get('active') !== null ? (string)Helpers::get('active') : '',
];

$rules   = RewardsService::rules(false, $filters);
$grants  = RewardsService::grants(200);
$badges  = RewardsService::badges();
$tab     = Helpers::get('tab') === 'grants' ? 'grants' : 'rules';

require BASE_PATH . '/views/admin/rewards/index.php';
