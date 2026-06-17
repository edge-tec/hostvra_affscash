<?php
class SettingsController {
    public function __construct() {
        Auth::checkApi('admin');
    }

    public function index() {
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            $config = Config::get('config') ?? [];
            ApiResponse::success([
                'config' => $config
            ]);
        } elseif ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['config'])) {
                ApiResponse::error('Invalid configuration data payload.');
            }

            $currentConfig = Config::get('config') ?? [];
            $newConfig = $data['config'];
            
            // We do a deep merge or just replace the keys that are provided
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
                ApiResponse::success(['message' => 'Settings updated successfully']);
            } else {
                ApiResponse::error('Failed to write settings to disk');
            }
        } else {
            ApiResponse::error('Method not allowed', 405);
        }
    }
}
