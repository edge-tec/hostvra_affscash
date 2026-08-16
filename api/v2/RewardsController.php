<?php
header('Content-Type: application/json');

try {
    Auth::check('affiliate');
    $affId = (int)Auth::affiliateId();

    // Auto-grant any newly-crossed milestone rewards for this affiliate
    RewardsService::checkAndGrant($affId);

    $grants = RewardsService::grantsForAffiliate($affId, 100);

    // Build grant map first so the next-milestone query can exclude unlocked rules.
    $grantByRule = [];
    foreach ($grants as $g) { $grantByRule[(int)$g['rule_id']] = $g; }

    // Visible reward catalogue
    $visibleRewards = RewardsService::visibleRules('affiliate');

    // Next milestone: the first active un-granted reward rule using independent cycle earnings.
    $nextRule   = null;
    $nextEarned = 0.0;
    foreach ($visibleRewards as $r) {
        $rid = (int)$r['id'];
        if (isset($grantByRule[$rid])) continue;
        $nextRule   = $r;
        $nextEarned = RewardsService::currentCycleEarnings($affId, $rid);
        break;
    }

    // Attach unlock status to visible rewards
    $availableRewards = [];
    foreach ($visibleRewards as $vr) {
        $vr['is_unlocked'] = isset($grantByRule[(int)$vr['id']]);
        $availableRewards[] = $vr;
    }

    echo json_encode([
        'success' => true,
        'next_milestone' => [
            'rule' => $nextRule,
            'earned' => $nextEarned
        ],
        'available_rewards' => $availableRewards,
        'earned_rewards' => $grants
    ]);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
