<?php
Auth::check('affiliate_manager');
$pageTitle = 'Manager Dashboard';

$mgrUserId = Auth::id();
$mgr       = Database::fetchOne("SELECT am.id, u.first_name, u.last_name FROM affiliate_managers am JOIN users u ON u.id=am.user_id WHERE am.user_id=?", [$mgrUserId]);

// Affiliates managed by this manager
$affIds          = Auth::managerAffiliateIds();
$totalAffiliates = count($affIds);

// ── Real-time IPQS Fraud Score (from fraud_logs via conversions) ──────────
// Reads actual IPQualityScore fraud_score values logged per click for all
// conversions belonging to managed affiliates — last 30 days. Pure read;
// no writes, no tracking-logic changes.
$fraudScoreAgg    = 0;
$fraudScoreCounts = ['high' => 0, 'medium' => 0, 'low' => 0];
$ipqsChecked      = 0;
$ipqsHighCount    = 0;
$ipqsMedCount     = 0;
$ipqsLowCount     = 0;

if (!empty($affIds)) {
    $in30    = implode(',', array_fill(0, count($affIds), '?'));
    $since30 = date('Y-m-d 00:00:00', strtotime('-30 days'));

    try {
        // Pull every IPQS score from fraud_logs joined through conversions
        // for this manager's affiliates over the last 30 days.
        $ipqsRows = Database::fetchAll(
            "SELECT fl.fraud_score
             FROM conversions cv
             JOIN fraud_logs fl ON fl.click_id = cv.click_id
             WHERE cv.affiliate_id IN ($in30)
               AND cv.converted_at >= ?
               AND fl.fraud_score IS NOT NULL",
            array_merge($affIds, [$since30])
        );

        if (!empty($ipqsRows)) {
            $ipqsChecked = count($ipqsRows);
            $ipqsSum     = 0;
            foreach ($ipqsRows as $_r) {
                $s = (int)$_r['fraud_score'];
                $ipqsSum += $s;
                if ($s >= 75)     { $ipqsHighCount++; }
                elseif ($s >= 40) { $ipqsMedCount++; }
                else              { $ipqsLowCount++; }
            }
            $fraudScoreAgg = (int)round($ipqsSum / $ipqsChecked);
        }
    } catch (\Throwable $_e) {
        // Fallback: blended click-signal score if fraud_logs unavailable
        $sum = 0;
        foreach ($affIds as $_aid) {
            $s = FraudScore::forAffiliate((int)$_aid);
            $sum += $s;
            if ($s >= 70)     { $ipqsHighCount++; }
            elseif ($s >= 40) { $ipqsMedCount++; }
            else              { $ipqsLowCount++; }
        }
        $fraudScoreAgg = (int)round($sum / max(1, count($affIds)));
        $ipqsChecked   = 0; // signal fallback mode
    }

    $fraudScoreCounts = [
        'high'   => $ipqsHighCount,
        'medium' => $ipqsMedCount,
        'low'    => $ipqsLowCount,
    ];
}

// Commission rate and financial details are intentionally NOT passed to the view.
// The manager must not see their commission percentage or any commission calculation.
// All commission data is admin-only via /admin/affiliate-managers?action=commission_report.

require BASE_PATH . '/views/affiliate_manager/dashboard.php';
