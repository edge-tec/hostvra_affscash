<?php
header('Content-Type: application/json');

try {
    Auth::check('affiliate');

    $affId = Auth::affiliateId();
    $today = date('Y-m-d');
    $monthStart = date('Y-m-01');

    // Fetch Stats
    $td = Database::fetchOne(
        "SELECT SUM(clicks) as clicks, SUM(unique_clicks) as uclicks, SUM(conversions) as conv,
                SUM(approved) as approved, SUM(payout) as payout
         FROM stats_daily WHERE affiliate_id=? AND stat_date=?",
        [$affId, $today]
    ) ?? [];

    $mo = Database::fetchOne(
        "SELECT SUM(clicks) as clicks, SUM(conversions) as conv, SUM(payout) as payout
         FROM stats_daily WHERE affiliate_id=? AND stat_date>=?",
        [$affId, $monthStart]
    ) ?? [];

    $bal = Database::fetchOne("SELECT balance FROM affiliates WHERE id=?", [$affId]);

    $stats = [
        'clicks_today'   => (int)($td['clicks']   ?? 0),
        'uclicks_today'  => (int)($td['uclicks']  ?? 0),
        'conv_today'     => (int)($td['conv']      ?? 0),
        'approved_today' => (int)($td['approved']  ?? 0),
        'payout_today'   => round((float)($td['payout']  ?? 0), 2),
        'clicks_month'   => (int)($mo['clicks']   ?? 0),
        'conv_month'     => (int)($mo['conv']      ?? 0),
        'payout_month'   => round((float)($mo['payout']  ?? 0), 2),
        'balance'        => round((float)($bal['balance']     ?? 0), 2),
    ];

    // Fetch Unread Counts for Header
    $userId = Auth::id();
    $unreadNews = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM news n WHERE n.status='published' AND NOT EXISTS (SELECT 1 FROM news_reads nr WHERE nr.news_id=n.id AND nr.user_id=?)", [$userId])['c'] ?? 0);
    $unreadNotifs = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM notifications WHERE user_id=? AND is_read=0", [$userId])['c'] ?? 0);
    try {
        $unreadAlerts = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM fraud_alerts WHERE affiliate_id=? AND is_read=0 AND resolved_at IS NULL", [$affId])['c'] ?? 0);
    } catch (Throwable $e) {
        $unreadAlerts = 0;
    }
    $unreadChats = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM support_messages WHERE user_id=? AND sender='admin' AND is_read=0", [$userId])['c'] ?? 0);

    $header_counts = [
        'unread_news' => $unreadNews,
        'unread_notifs' => $unreadNotifs,
        'unread_alerts' => $unreadAlerts,
        'unread_chats' => $unreadChats
    ];

    // Fetch Recent Approved Offers
    PrivateOffer::ensureTables();
    try {
        $approvedOffers = Database::fetchAll(
            "SELECT o.id, o.name, o.description, o.payout_type, o.payout_amount as payout, o.preview_url, ao.custom_payout
             FROM offers o
             JOIN affiliate_offers ao ON ao.offer_id  = o.id
             LEFT JOIN private_offer_access poa ON poa.offer_id = o.id AND poa.affiliate_id = ?
             WHERE ao.affiliate_id=? AND ao.status='approved' AND o.status='active'
               AND (COALESCE(o.visibility,'public') != 'private' OR poa.id IS NOT NULL)
             LIMIT 10",
            [$affId, $affId]
        );
    } catch (PDOException $e) {
        // Fallback if visibility column is missing
        $approvedOffers = Database::fetchAll(
            "SELECT o.id, o.name, o.description, o.payout_type, o.payout_amount as payout, o.preview_url, ao.custom_payout
             FROM offers o
             JOIN affiliate_offers ao ON ao.offer_id  = o.id
             WHERE ao.affiliate_id=? AND ao.status='approved' AND o.status='active'
             LIMIT 10",
            [$affId]
        );
    }

    $offersList = [];
    if ($approvedOffers) {
        foreach ($approvedOffers as $offer) {
            $payout = $offer['custom_payout'] !== null ? $offer['custom_payout'] : $offer['payout'];
            $offersList[] = [
                'id' => $offer['id'],
                'name' => $offer['name'],
                'payout_type' => $offer['payout_type'],
                'payout' => (float)$payout,
                'preview_url' => $offer['preview_url']
            ];
        }
    }

    // User Info
    $user = Auth::currentUser();
    if (!$user) {
        throw new Exception("User not found or session invalid");
    }
    
    $userInfo = [
        'name' => trim($user['first_name'] . ' ' . $user['last_name']),
        'email' => $user['email'],
        'status' => $user['status']
    ];

    echo json_encode([
        'success' => true,
        'user' => $userInfo,
        'stats' => $stats,
        'header_counts' => $header_counts,
        'recent_offers' => $offersList
    ]);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
