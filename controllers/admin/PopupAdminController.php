<?php
/**
 * Admin → Reward Popup configuration.
 *
 * Edits the single-row popup_config (title, body, CTA, image, enabled flag).
 * Saving content auto-bumps the version, which resets dismissals so the
 * pop-up is shown to every affiliate on their next dashboard hit.
 */
Auth::check('admin');
$pageTitle = 'Reward Popup';

if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $sub = Helpers::post('submit_type');

    if ($sub === 'save_popup') {
        $imgPath = PopupService::get()['image_path'] ?? null;
        if (!empty($_FILES['image']['tmp_name'])) {
            $mimeMap = [
                'image/png'  => 'png',
                'image/jpeg' => 'jpg',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            ];
            $mime = @mime_content_type($_FILES['image']['tmp_name']);
            if (isset($mimeMap[$mime]) && (int)$_FILES['image']['size'] <= 4 * 1024 * 1024) {
                $dir = BASE_PATH . '/assets/uploads/popup/';
                if (!is_dir($dir)) @mkdir($dir, 0775, true);
                $ext  = $mimeMap[$mime];
                $safe = 'popup_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (@move_uploaded_file($_FILES['image']['tmp_name'], $dir . $safe)) {
                    $imgPath = '/assets/uploads/popup/' . $safe;
                }
            }
        }
        if (Helpers::post('clear_image') === '1') $imgPath = null;

        PopupService::save([
            'enabled'    => Helpers::post('enabled') === '1',
            'title'      => Helpers::postRaw('title'),
            'body'       => Helpers::postRaw('body'),
            'image_path' => $imgPath,
            'cta_label'  => Helpers::postRaw('cta_label'),
            'cta_url'    => Helpers::postRaw('cta_url'),
        ]);
        Helpers::flash('success', 'Popup saved. Affiliates will see it on their next dashboard visit.');
        Helpers::redirect('/admin/popup');
    }
}

$cfg = PopupService::get();
require BASE_PATH . '/views/admin/popup/index.php';
