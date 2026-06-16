<?php
header('Content-Type: application/json');

Auth::check('affiliate_manager');

try {
    $mgrUserId = Auth::id();
    $mgr = Database::fetchOne("SELECT am.id, u.first_name, u.last_name FROM affiliate_managers am JOIN users u ON u.id=am.user_id WHERE am.user_id=?", [$mgrUserId]);

    // Affiliates managed by this manager
    $affIds = Auth::managerAffiliateIds();
    $totalAffiliates = count($affIds);

    $fraudScoreAgg = 0;
    $fraudScoreCounts = ['high' => 0, 'medium' => 0, 'low' => 0];

    if (!empty($affIds)) {
        $in30 = implode(',', array_fill(0, count($affIds), '?'));
        $since30 = date('Y-m-d 00:00:00', strtotime('-30 days'));

        $ipqsRows = Database::fetchAll(
            "SELECT fl.fraud_score
             FROM conversions cv
             JOIN fraud_logs fl ON fl.click_id = cv.click_id
             WHERE cv.affiliate_id IN ($in30)
               AND cv.converted_at >= ?
               AND cv.is_hidden = 0
               AND fl.fraud_score IS NOT NULL",
            array_merge($affIds, [$since30])
        );

        $ipqsHighCount = 0;
        $ipqsMedCount = 0;
        $ipqsLowCount = 0;

        if (!empty($ipqsRows)) {
            $ipqsSum = 0;
            foreach ($ipqsRows as $_r) {
                $s = (int)$_r['fraud_score'];
                $ipqsSum += $s;
                if ($s >= 75) { $ipqsHighCount++; }
                elseif ($s >= 40) { $ipqsMedCount++; }
                else { $ipqsLowCount++; }
            }
            $fraudScoreAgg = (int)round($ipqsSum / count($ipqsRows));
        } else {
            $sum = 0;
            foreach ($affIds as $_aid) {
                $s = FraudScore::forAffiliate((int)$_aid);
                $sum += $s;
                if ($s >= 70) { $ipqsHighCount++; }
                elseif ($s >= 40) { $ipqsMedCount++; }
                else { $ipqsLowCount++; }
            }
            $fraudScoreAgg = (int)round($sum / max(1, count($affIds)));
        }

        $fraudScoreCounts = [
            'high' => $ipqsHighCount,
            'medium' => $ipqsMedCount,
            'low' => $ipqsLowCount,
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'total_affiliates' => $totalAffiliates,
            'fraud_score_average' => $fraudScoreAgg,
            'fraud_score_counts' => $fraudScoreCounts
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
