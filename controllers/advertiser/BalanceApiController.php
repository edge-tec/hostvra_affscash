<?php
Auth::check('advertiser');
header('Content-Type: application/json');

$balance = 0.0;
$unread  = 0;
try {
    $row = Database::fetchOne("SELECT balance FROM advertisers WHERE user_id=?", [Auth::id()]);
    if ($row) $balance = (float)$row['balance'];
} catch (\Throwable $e) {}
try {
    $cnt = Database::fetchOne("SELECT COUNT(*) AS c FROM notifications WHERE user_id=? AND is_read=0", [Auth::id()]);
    $unread = (int)($cnt['c'] ?? 0);
} catch (\Throwable $e) {}

echo json_encode(['balance' => $balance, 'unread' => $unread]);
