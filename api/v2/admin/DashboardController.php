<?php
header('Content-Type: application/json');

// Ensure only admins can access this endpoint
if (!Auth::check('admin', false)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    // Total counts
    $totalAffiliates   = Database::count('users', 'role=? AND status=?', ['affiliate', 'active']);
    $pendingAffiliates = Database::count('users', 'role=? AND status=?', ['affiliate', 'pending']);
    $totalAdvertisers  = Database::count('users', 'role=? AND status=?', ['advertiser', 'active']);
    $totalOffers       = Database::count('offers', 'status=?', ['active']);

    // Profit summary (last 30 days)
    $profitSummary = Database::fetchOne(
        "SELECT SUM(revenue) as total_revenue, SUM(payout) as total_payout, SUM(revenue-payout) as total_profit
         FROM conversions WHERE status='approved' AND is_hidden=0 AND converted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );

    if (!$profitSummary) {
        $profitSummary = ['total_revenue' => 0, 'total_payout' => 0, 'total_profit' => 0];
    } else {
        $profitSummary['total_revenue'] = (float)($profitSummary['total_revenue'] ?? 0);
        $profitSummary['total_payout'] = (float)($profitSummary['total_payout'] ?? 0);
        $profitSummary['total_profit'] = (float)($profitSummary['total_profit'] ?? 0);
    }

    // Top profitable offers (last 30 days)
    $topProfitOffers = Database::fetchAll(
        "SELECT o.name, o.id,
                SUM(c.revenue) as revenue, SUM(c.payout) as payout,
                SUM(c.revenue - c.payout) as profit,
                COUNT(*) as conversions
         FROM conversions c JOIN offers o ON o.id=c.offer_id
         WHERE c.status='approved' AND c.is_hidden=0 AND c.converted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY o.id ORDER BY profit DESC LIMIT 5"
    );

    echo json_encode([
        'success' => true,
        'data' => [
            'total_affiliates' => $totalAffiliates,
            'pending_affiliates' => $pendingAffiliates,
            'total_advertisers' => $totalAdvertisers,
            'total_offers' => $totalOffers,
            'profit_summary' => $profitSummary,
            'top_profit_offers' => $topProfitOffers
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
