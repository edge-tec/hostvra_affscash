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

    // Next milestone
    $nextCandidates = Database::fetchAll(
        "SELECT r.*, COALESCE(r.publish_at, r.created_at) AS start_date,
                COALESCE((
                    SELECT SUM(c.payout)
                    FROM conversions c
                    WHERE c.affiliate_id = ?
                      AND c.status = 'approved'
                      AND COALESCE(c.is_hidden, 0) = 0
                      AND c.converted_at >= COALESCE(r.publish_at, r.created_at)
                      AND (r.expires_at IS NULL OR c.converted_at <= r.expires_at)
                ), 0) AS earned_in_window
         FROM reward_rules r
         WHERE r.active = 1
           AND (r.publish_at IS NULL OR r.publish_at <= NOW())
           AND (r.expires_at IS NULL OR r.expires_at >= NOW())
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

    // Visible reward catalogue
    $visibleRewards = RewardsService::visibleRules('affiliate');

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
