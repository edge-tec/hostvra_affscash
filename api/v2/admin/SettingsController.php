<?php
header('Content-Type: application/json');

try {
    Auth::check('admin');

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $config = Config::get('config') ?? [];
        echo json_encode([
            'config' => $config
        ]);
        exit;
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['config'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid configuration data payload.']);
            exit;
        }

        $currentConfig = Config::get('config') ?? [];
        $newConfig = $data['config'];
        
        foreach ($newConfig as $sectionKey => $sectionValues) {
            if (!isset($currentConfig[$sectionKey])) {
                $currentConfig[$sectionKey] = [];
            }
            if (is_array($sectionValues)) {
                foreach ($sectionValues as $key => $val) {
                    $currentConfig[$sectionKey][$key] = $val;
                }
            } else {
                $currentConfig[$sectionKey] = $sectionValues;
            }
        }

        if (Config::write('config', $currentConfig)) {
            echo json_encode(['status' => 'success', 'message' => 'Settings updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to write settings to disk']);
        }
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }
} catch (\Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
