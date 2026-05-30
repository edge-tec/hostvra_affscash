<?php
/**
 * Tenant - Multi-Tenant Workspace & Subscription Engine
 */
class Tenant {
    private static ?array $resolvedTenant = null;

    /**
     * Set the database-wide active tenant ID
     */
    public static function setTenantId(?int $id): void {
        Database::setTenantId($id);
        if ($id === null) {
            self::$resolvedTenant = null;
        } elseif (!self::$resolvedTenant || self::$resolvedTenant['id'] !== $id) {
            self::$resolvedTenant = Database::fetchOne("SELECT * FROM `tenants` WHERE id = ?", [$id]);
        }
    }

    /**
     * Get currently active tenant ID
     */
    public static function getTenantId(): ?int {
        return Database::getTenantId();
    }

    /**
     * Resolve and bind tenant context based on session or HTTP hostname
     */
    public static function resolve(): ?int {
        // 1. Check logged-in user session
        if (!empty($_SESSION['tenant_id'])) {
            self::setTenantId((int)$_SESSION['tenant_id']);
            return (int)$_SESSION['tenant_id'];
        }

        // 2. Resolve via Custom Domain / HTTP Hostname (e.g. for tracking urls / subdomains)
        $host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '');
        if ($host && $host !== 'localhost' && $host !== '127.0.0.1') {
            // Check tracking domains table first
            try {
                $domainRec = Database::fetchOne("SELECT tenant_id FROM `tracking_domains` WHERE domain = ? AND is_active = 1 LIMIT 1", [$host]);
                if ($domainRec && !empty($domainRec['tenant_id'])) {
                    self::setTenantId((int)$domainRec['tenant_id']);
                    return (int)$domainRec['tenant_id'];
                }
            } catch (\Throwable $e) {}

            // Check custom domain in tenants table
            try {
                $tenantRec = Database::fetchOne("SELECT id FROM `tenants` WHERE custom_domain = ? LIMIT 1", [$host]);
                if ($tenantRec) {
                    self::setTenantId((int)$tenantRec['id']);
                    return (int)$tenantRec['id'];
                }
            } catch (\Throwable $e) {}

            // Check custom assigned admin main domain
            try {
                $tenantRec = Database::fetchOne("SELECT id FROM `tenants` WHERE admin_main_domain = ? LIMIT 1", [$host]);
                if ($tenantRec) {
                    self::setTenantId((int)$tenantRec['id']);
                    return (int)$tenantRec['id'];
                }
            } catch (\Throwable $e) {}

            // Check custom assigned admin tracking domain
            try {
                $tenantRec = Database::fetchOne("SELECT id FROM `tenants` WHERE admin_tracking_domain = ? LIMIT 1", [$host]);
                if ($tenantRec) {
                    self::setTenantId((int)$tenantRec['id']);
                    return (int)$tenantRec['id'];
                }
            } catch (\Throwable $e) {}
        }

        // Default: If no session/hostname match, return null (meaning global scope or not resolved yet)
        return null;
    }

    /**
     * Check if tenant's subscription is fully active and not expired
     */
    public static function isSubscriptionActive(int $tenantId): bool {
        $sub = self::getSubscription($tenantId);
        if (!$sub) return false;
        
        $now = date('Y-m-d H:i:s');
        return ($sub['status'] === 'active' || $sub['status'] === 'trial') 
            && $sub['ends_at'] > $now;
    }

    /**
     * Get active subscription for a tenant
     */
    public static function getSubscription(int $tenantId): ?array {
        return Database::fetchOne(
            "SELECT s.*, p.name as plan_name, p.max_users, p.max_offers, p.max_domains, p.max_click_limits, p.max_storage
             FROM `subscriptions` s
             JOIN `subscription_plans` p ON p.id = s.plan_id
             WHERE s.tenant_id = ? AND s.status IN ('active', 'trial')
             ORDER BY s.ends_at DESC LIMIT 1",
            [$tenantId]
        );
    }

    /**
     * Enforce a resource limit inside the active tenant (e.g. click limits, max offers)
     * Returns true if allowed, false if limit exceeded.
     */
    public static function checkLimit(string $metric): bool {
        $tenantId = self::getTenantId();
        if (!$tenantId) return true; // Global / Super Admin exempt

        $sub = self::getSubscription($tenantId);
        if (!$sub) return false; // No active subscription -> blocked

        switch ($metric) {
            case 'users':
                if (empty($sub['max_users'])) return true;
                $count = Database::count('users', "role != 'super_admin'");
                return $count < (int)$sub['max_users'];

            case 'offers':
                if (empty($sub['max_offers'])) return true;
                $count = Database::count('offers', "1");
                return $count < (int)$sub['max_offers'];

            case 'domains':
                if (empty($sub['max_domains'])) return true;
                $count = Database::count('tracking_domains', "1");
                return $count < (int)$sub['max_domains'];

            case 'clicks':
                if (empty($sub['max_click_limits'])) return true;
                // Count clicks in the current billing cycle (from starts_at to ends_at)
                $count = Database::count('clicks', "clicked_at BETWEEN ? AND ?", [$sub['starts_at'], $sub['ends_at']]);
                return $count < (int)$sub['max_click_limits'];

            default:
                return true;
        }
    }

    /**
     * Record a billing/subscription audit log
     */
    public static function logBilling(int $tenantId, string $action, float $amount, string $details): void {
        Database::insert('billing_logs', [
            'tenant_id'  => $tenantId,
            'action'     => $action,
            'amount'     => $amount,
            'details'    => $details,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Check if landing page is enabled for the current resolved tenant
     */
    public static function isLandingEnabled(): bool {
        $tenantId = self::getTenantId();
        if ($tenantId === null) return true; // Global/Super Admin main domain always has landing page
        try {
            $tenant = Database::fetchOne("SELECT landing_enabled FROM `tenants` WHERE id = ?", [$tenantId]);
            return $tenant ? (bool)$tenant['landing_enabled'] : true;
        } catch (\Throwable $e) {
            return true;
        }
    }
}
