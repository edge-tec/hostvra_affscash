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

if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $postAction = Helpers::post('action');
    if ($postAction === 'claim_reward') {
        $grantId = (int)Helpers::post('grant_id');
        if ($grantId > 0) {
            $grant = Database::fetchOne("SELECT * FROM reward_grants WHERE id=? AND affiliate_id=? LIMIT 1", [$grantId, $affId]);
            if ($grant) {
                if ($grant['status'] === 'granted') {
                    RewardsService::updateGrantStatus($grantId, 'claimed');
                }
                RewardsService::sendRewardClaimNotifications($grantId);
                Helpers::flash('success', 'Reward claim registered! Confirmation emails have been dispatched.');
            } else {
                Helpers::flash('error', 'Reward grant not found.');
            }
        }
        Helpers::redirect('/affiliate/rewards');
    }
}

// Auto-grant any newly-crossed milestone rewards for this affiliate
RewardsService::checkAndGrant($affId);

$grants = RewardsService::grantsForAffiliate($affId, 100);

// Build grant map first so the next-milestone query can exclude unlocked rules.
$grantByRule = [];
foreach ($grants as $g) { $grantByRule[(int)$g['rule_id']] = $g; }

// Visible reward catalogue — honours visibility, publish_at and expires_at.
$visibleRewards = RewardsService::visibleRules('affiliate');

// Next milestone: the first active un-granted reward rule.
// Progress is measured using independent cycle earnings (earnings since the last unlocked grant).
$nextRule   = null;
$nextEarned = 0.0;
foreach ($visibleRewards as $r) {
    $rid = (int)$r['id'];
    if (isset($grantByRule[$rid])) {
        continue; // Exclude unlocked rules
    }
    $nextRule   = $r;
    $nextEarned = RewardsService::currentCycleEarnings($affId, $rid);
    break;
}

// Detail view — show full description of a single available reward.
$action = Helpers::get('action') ?: 'index';
if ($action === 'detail' && isset($_GET['reward_id'])) {
    $rewardId = (int)$_GET['reward_id'];
    $reward   = null;
    foreach ($visibleRewards as $vr) {
        if ((int)$vr['id'] === $rewardId) { $reward = $vr; break; }
    }
    if (!$reward) {
        $reward = Database::fetchOne("SELECT * FROM reward_rules WHERE id = ?", [$rewardId]);
    }
    if (!$reward) { Helpers::redirect('/affiliate/rewards'); }
    $isUnlocked   = isset($grantByRule[$rewardId]);
    $rewardEarned = RewardsService::earningsSinceRuleStart($affId, $rewardId);
    require BASE_PATH . '/views/affiliate/rewards/detail.php';
    return;
}

require BASE_PATH . '/views/affiliate/rewards/index.php';
