<?php
try {
    require 'core/Database.php';
    require 'core/Config.php';
    require 'core/Helpers.php';
    $config = require 'config.php';
    Database::connect($config['db']);

    $id = 1; // test id
    $adv = Database::fetchOne("SELECT u.id as user_id FROM users u JOIN advertisers adv ON adv.user_id=u.id WHERE adv.id=?", [$id]);
    if ($adv) {
        Database::query("DELETE FROM user_active_sessions WHERE user_id=?", [$adv['user_id']]);
        $offers = Database::fetchAll("SELECT id FROM offers WHERE advertiser_id=?", [$id]);
        foreach ($offers as $o) {
            Database::query("DELETE FROM affiliate_offers WHERE offer_id=?", [$o['id']]);
            Database::query("DELETE FROM smartlink_offers WHERE offer_id=?", [$o['id']]);
            Database::query("DELETE FROM offer_links WHERE offer_id=?", [$o['id']]);
        }
        Database::query("DELETE FROM offers WHERE advertiser_id=?", [$id]);
        Database::query("DELETE FROM advertisers WHERE user_id=?", [$adv['user_id']]);
        Database::query("DELETE FROM users WHERE id=?", [$adv['user_id']]);
        echo "Success";
    } else {
        echo "Not found";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
