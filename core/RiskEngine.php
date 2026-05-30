<?php
/**
 * In-House Real-Time Fraud Risk Engine
 * Evaluates rules from the fraud_rules table against live clicks and conversions.
 */
class RiskEngine {

    /**
     * Evaluate a click against active click rules.
     * @param array $clickData {ip, ua, affiliate_id, offer_id, click_id}
     * @return array {action: 'allow'|'flag'|'block'|'auto_case', reasons: string[], block_reason: string|null}
     */
    public static function evaluateClick(array $clickData): array {
        $result = ['action' => 'allow', 'reasons' => [], 'block_reason' => null];
        
        try {
            $rules = Database::fetchAll("SELECT * FROM fraud_rules WHERE is_active=1 AND rule_type IN ('bot_ua', 'click_rate', 'ip_range', 'affiliate_age', 'datacenter_ip')");
            if (!$rules) return $result;
            
            $triggeredRules = [];
            
            foreach ($rules as $rule) {
                $triggered = false;
                $cond = json_decode($rule['condition_json'], true) ?: [];
                
                switch ($rule['rule_type']) {
                    case 'bot_ua':
                        $ua = strtolower($clickData['ua'] ?? '');
                        // Simple common bot signatures
                        if (preg_match('/(bot|crawl|spider|slurp|headless|phantomjs|puppeteer|selenium|curl|wget)/i', $ua)) {
                            $triggered = true;
                        }
                        break;
                    
                    case 'click_rate':
                        // e.g. "clicks_per_minute":20, "window_seconds":60
                        $window = (int)($cond['window_seconds'] ?? 60);
                        $threshold = (int)($cond['clicks_per_minute'] ?? 20);
                        
                        $count = Database::fetchOne(
                            "SELECT COUNT(*) as cnt FROM clicks WHERE ip_address=? AND offer_id=? AND clicked_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)",
                            [$clickData['ip'], $clickData['offer_id'], $window]
                        );
                        if (($count['cnt'] ?? 0) >= $threshold) {
                            $triggered = true;
                        }
                        break;
                        
                    case 'affiliate_age':
                        $maxAge = (int)($cond['max_age_days'] ?? 7);
                        $minClicks = (int)($cond['min_daily_clicks'] ?? 100);
                        
                        $aff = Database::fetchOne("SELECT created_at FROM affiliates WHERE id=?", [$clickData['affiliate_id']]);
                        if ($aff) {
                            $ageDays = (time() - strtotime($aff['created_at'])) / 86400;
                            if ($ageDays <= $maxAge) {
                                $todayClicks = Database::fetchOne("SELECT COUNT(*) as cnt FROM clicks WHERE affiliate_id=? AND DATE(clicked_at)=CURDATE()", [$clickData['affiliate_id']]);
                                if (($todayClicks['cnt'] ?? 0) >= $minClicks) {
                                    $triggered = true;
                                }
                            }
                        }
                        break;
                }
                
                if ($triggered) {
                    $triggeredRules[] = $rule;
                    $result['reasons'][] = '[RiskEngine] ' . $rule['name'];
                }
            }
            
            // Process actions
            self::processRuleActions($triggeredRules, $result, $clickData);
            
        } catch (\Throwable $e) {
            error_log('[RiskEngine Click] ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Evaluate a conversion against active conversion rules.
     * @param array $conversionData {ip, affiliate_id, offer_id}
     * @param array $clickData {ip, clicked_at, country}
     * @return array {action, reasons, block_reason}
     */
    public static function evaluateConversion(array $conversionData, array $clickData): array {
        $result = ['action' => 'allow', 'reasons' => [], 'block_reason' => null];
        
        try {
            $rules = Database::fetchAll("SELECT * FROM fraud_rules WHERE is_active=1 AND rule_type IN ('conversion_time', 'geo_mismatch', 'duplicate_ip', 'low_cvr', 'high_cvr')");
            if (!$rules) return $result;
            
            $triggeredRules = [];
            
            foreach ($rules as $rule) {
                $triggered = false;
                $cond = json_decode($rule['condition_json'], true) ?: [];
                
                switch ($rule['rule_type']) {
                    case 'conversion_time':
                        $maxSec = (int)($cond['max_seconds'] ?? 10);
                        $clickedAt = strtotime($clickData['clicked_at']);
                        $convertedAt = time(); // Use current time
                        if (($convertedAt - $clickedAt) <= $maxSec) {
                            $triggered = true;
                        }
                        break;
                        
                    case 'geo_mismatch':
                        $convGeo = Helpers::getGeoInfo($conversionData['ip']);
                        $convCountry = $convGeo['country'] ?? '';
                        if (!empty($clickData['country']) && !empty($convCountry) && $clickData['country'] !== $convCountry) {
                            $triggered = true;
                        }
                        break;
                        
                    case 'duplicate_ip':
                        $windowHours = (int)($cond['window_hours'] ?? 24);
                        $count = Database::fetchOne(
                            "SELECT COUNT(*) as cnt FROM conversions WHERE ip_address=? AND offer_id=? AND converted_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)",
                            [$conversionData['ip'], $conversionData['offer_id'], $windowHours]
                        );
                        if (($count['cnt'] ?? 0) > 0) {
                            $triggered = true;
                        }
                        break;
                        
                    case 'low_cvr':
                        $minClicks = (int)($cond['min_clicks'] ?? 200);
                        $cvrThresh = (float)($cond['cvr_threshold'] ?? 0.05); // 0.05%
                        
                        $stats = Database::fetchOne(
                            "SELECT SUM(clicks) as tc, SUM(conversions) as tv FROM stats_daily WHERE affiliate_id=? AND stat_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
                            [$conversionData['affiliate_id']]
                        );
                        
                        $tc = (int)($stats['tc'] ?? 0);
                        $tv = (int)($stats['tv'] ?? 0);
                        
                        if ($tc >= $minClicks) {
                            $cvr = ($tv / $tc) * 100;
                            if ($cvr < $cvrThresh) {
                                $triggered = true;
                            }
                        }
                        break;
                }
                
                if ($triggered) {
                    $triggeredRules[] = $rule;
                    $result['reasons'][] = '[RiskEngine] ' . $rule['name'];
                }
            }
            
            self::processRuleActions($triggeredRules, $result, array_merge($clickData, $conversionData));
            
        } catch (\Throwable $e) {
            error_log('[RiskEngine Conversion] ' . $e->getMessage());
        }
        
        return $result;
    }
    
    private static function processRuleActions(array $triggeredRules, array &$result, array $contextData): void {
        $hasBlock = false;
        $hasAutoCase = false;
        
        foreach ($triggeredRules as $rule) {
            // Update trigger count asynchronously
            try {
                Database::query("UPDATE fraud_rules SET trigger_count = trigger_count + 1, last_triggered = NOW() WHERE id=?", [$rule['id']]);
            } catch (\Throwable $e) {}
            
            if ($rule['action'] === 'block') {
                $hasBlock = true;
                $result['block_reason'] = '[RiskEngine] ' . $rule['name'];
            } elseif ($rule['action'] === 'auto_case') {
                $hasAutoCase = true;
                self::createFraudCase($rule, $contextData);
            }
        }
        
        if ($hasBlock) {
            $result['action'] = 'block';
        } elseif ($hasAutoCase) {
            $result['action'] = 'auto_case';
        } elseif (!empty($triggeredRules)) {
            $result['action'] = 'flag';
        }
    }
    
    private static function createFraudCase(array $rule, array $contextData): void {
        try {
            $existing = Database::fetchOne(
                "SELECT id FROM fraud_cases WHERE affiliate_id=? AND status='open' LIMIT 1",
                [$contextData['affiliate_id'] ?? 0]
            );
            
            if (!$existing) {
                Database::insert('fraud_cases', [
                    'case_ref' => 'FC-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                    'type' => 'bot_traffic', // Fallback type
                    'affiliate_id' => $contextData['affiliate_id'] ?? null,
                    'reference_id' => $contextData['click_id'] ?? null,
                    'severity' => 'high',
                    'status' => 'open',
                    'fraud_score' => 80,
                    'signals' => json_encode(['rule_triggered' => $rule['name']]),
                    'notes' => 'Auto-generated case from Risk Engine rule: ' . $rule['name']
                ]);
            }
        } catch (\Throwable $e) {}
    }
}
