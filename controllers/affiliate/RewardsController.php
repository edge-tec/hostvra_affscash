<?php
/**
 * Affiliate → My Rewards (granted milestone rewards + next-target widget).
 *
 * Progress toward each reward is counted from that reward's start date
 * (publish_at if set, otherwise created_at). Earnings from before the reward
 * went live do not count — the counter "starts when the reward starts".
 */
Auth::check('affiliate');
$pageTitle = 'My Rewards';

$affId = (int)Auth::affiliateId();

$grants = RewardsService::grantsForAffiliate($affId, 100);

// Build grant map first so the next-milestone query can exclude unlocked rules.
$grantByRule = [];
foreach ($grants as $g) { $grantByRule[(int)$g['rule_id']] = $g; }

// Next milestone: cheapest active, visible, un-granted rule whose earnings
// inside its publish_at → expires_at window are still below the threshold.
// Earnings outside that window (before publish_at or after expires_at) do not
// count toward this rule — each reward stands on its own.
$nextCandidates = Database::fetchAll(
    "SELECT r.*, COALESCE(r.publish_at, r.created_at) AS start_date,
            COALESCE((
                SELECT SUM(c.payout)
                FROM conversions c
                WHERE c.affiliate_id = ?
                  AND c.status = 'approved'
                  AND COALESCE(c.is_hidden, 0) = 0
                  AND (c.hide_reason IS NULL OR c.hide_reason NOT LIKE '%traffic_back%')
                  AND NOT EXISTS (SELECT 1 FROM clicks _ck_tb WHERE _ck_tb.click_id = c.click_id AND _ck_tb.source = 'traffic_back')
                  AND NOT EXISTS (SELECT 1 FROM traffic_back_logs _tbl_tb WHERE _tbl_tb.click_id = c.click_id)
                  AND c.converted_at >= COALESCE(r.publish_at, r.created_at)
                  AND (r.expires_at IS NULL OR c.converted_at < r.expires_at)
            ), 0) AS earned_in_window
     FROM reward_rules r
     WHERE r.active = 1
       AND (r.publish_at IS NULL OR r.publish_at <= NOW())
       AND (r.expires_at IS NULL OR r.expires_at >  NOW())
       AND r.id NOT IN (SELECT rule_id FROM reward_grants WHERE affiliate_id = ?)
     ORDER BY r.threshold_usd ASC",
    [$affId, $affId]
) ?: [];

$nextRule    = null;
$nextEarned  = 0.0;
foreach ($nextCandidates as $cand) {
    if ((float)$cand['earned_in_window'] < (float)$cand['threshold_usd']) {
        $nextRule   = $cand;
        $nextEarned = (float)$cand['earned_in_window'];
        break;
    }
}

// Visible reward catalogue — honours visibility, publish_at and expires_at.
$visibleRewards = RewardsService::visibleRules('affiliate');

// Detail view — show full description of a single available reward.
$action = Helpers::get('action') ?: 'index';
if ($action === 'detail' && isset($_GET['reward_id'])) {
    $rewardId = (int)$_GET['reward_id'];
    $reward   = null;
    foreach ($visibleRewards as $vr) {
        if ((int)$vr['id'] === $rewardId) { $reward = $vr; break; }
    }
    // Only show rewards that are in the visible catalogue for this affiliate.
    if (!$reward) { Helpers::redirect('/affiliate/rewards'); }
    $isUnlocked   = isset($grantByRule[$rewardId]);
    $rewardEarned = RewardsService::earningsSinceRuleStart($affId, $rewardId);
    require BASE_PATH . '/views/affiliate/rewards/detail.php';
    return;
}

require BASE_PATH . '/views/affiliate/rewards/index.php';
