<?php
/**
 * Super Admin - Domain Health Monitor & SSL Assignment Controller
 */
Auth::check();
if (Auth::role() !== 'super_admin') {
    Helpers::redirect('/');
}

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Resolve host system public IP
$serverIp = DomainManager::getServerIp();
if (empty($serverIp) || $serverIp === '127.0.0.1' || $serverIp === '::1') {
    $serverIp = '185.151.30.22'; // Simulated fallback public IP
}

if ($action === 'add_domain' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $domain = strtolower(trim($_POST['domain'] ?? ''));
    $tenantId = (int)($_POST['tenant_id'] ?? 0);

    if (empty($domain) || empty($tenantId)) {
        $error = "Domain and Tenant ID are required.";
    } else {
        $existing = Database::fetchOne("SELECT id FROM tracking_domains WHERE domain = ?", [$domain]);
        if ($existing) {
            $error = "Domain is already added to the system.";
        } else {
            Database::insert('tracking_domains', [
                'tenant_id' => $tenantId,
                'domain' => $domain,
                'is_active' => 1,
                'is_default' => 0
            ]);
            // Check domain health automatically
            DomainManager::checkDomain($domain);
            $success = "Domain added successfully.";
        }
    }
    Helpers::redirect('/super_admin/domains?success=' . urlencode($success) . '&error=' . urlencode($error));
}

if ($action === 'save_ssl' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $domain = trim($_POST['domain'] ?? '');
    $cert = $_POST['ssl_cert'] ?? '';
    $key = $_POST['ssl_key'] ?? '';
    $ca = $_POST['ssl_ca'] ?? '';

    if (empty($domain) || empty($cert) || empty($key)) {
        $error = "Domain, Certificate, and Private Key are required.";
    } else {
        $td = Database::fetchOne("SELECT id FROM tracking_domains WHERE domain = ?", [$domain]);
        if ($td) {
            $res = DomainManager::applyCustomSsl($domain, $cert, $key, $ca);
            if ($res['ok']) {
                Database::update('tracking_domains', [
                    'ssl_cert' => $cert,
                    'ssl_key' => $key,
                    'ssl_ca' => $ca
                ], 'domain=?', [$domain]);
                $success = $res['message'];
            } else {
                $error = $res['message'];
            }
        } else {
            $error = "Domain not found.";
        }
    }
    Helpers::redirect('/super_admin/domains?success=' . urlencode($success) . '&error=' . urlencode($error));
}

if ($action === 'verify') {
    $domainId = (int)($_GET['id'] ?? 0);
    $td = Database::fetchOne("SELECT domain FROM tracking_domains WHERE id = ?", [$domainId]);
    if ($td) {
        $res = DomainManager::checkDomain($td['domain']);
        $success = "Domain diagnostics updated for {$td['domain']}.";
        if ($res['server_status'] === 'error') {
            $error = $res['message'];
        }
    } else {
        $error = "Domain not found.";
    }
    Helpers::redirect('/super_admin/domains?success=' . urlencode($success) . '&error=' . urlencode($error));
}

// Fetch all domain assignments
DomainManager::ensureColumns();
$domains = Database::fetchAll(
    "SELECT td.*, t.company_name 
     FROM `tracking_domains` td 
     LEFT JOIN `tenants` t ON t.id = td.tenant_id 
     ORDER BY td.id DESC"
);

$tenants = Database::fetchAll("SELECT id, company_name FROM tenants ORDER BY id DESC");

$pageTitle = 'Domain & SSL Management';
require BASE_PATH . '/views/super_admin/domains.php';
