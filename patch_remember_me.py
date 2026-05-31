import os
import re

dir_path = "/Users/mizanurrahman/claude/affscash script/Affscash.net  latest script/project aapanel"

# 1. Modify core/Auth.php
auth_path = os.path.join(dir_path, "core/Auth.php")
with open(auth_path, "r", encoding="utf-8") as f:
    auth_content = f.read()

# Add autoLoginFromCookie call in start()
auth_content = auth_content.replace(
    "session_start();\n        }",
    "session_start();\n        }\n\n        if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {\n            self::autoLoginFromCookie($_COOKIE['remember_token']);\n        }"
)

# Modify login signature
auth_content = auth_content.replace(
    "public static function login(string $email, string $password): array {",
    "public static function login(string $email, string $password, bool $remember = false): array {"
)

# Add 2fa_pending_remember
auth_content = auth_content.replace(
    "$_SESSION['2fa_pending_name']    = trim($user['first_name'] . ' ' . $user['last_name']);\n            $_SESSION['2fa_method']          = 'totp';",
    "$_SESSION['2fa_pending_name']    = trim($user['first_name'] . ' ' . $user['last_name']);\n            $_SESSION['2fa_pending_remember'] = $remember;\n            $_SESSION['2fa_method']          = 'totp';"
)
auth_content = auth_content.replace(
    "$_SESSION['2fa_pending_name']    = trim($user['first_name'] . ' ' . $user['last_name']);\n            $_SESSION['2fa_method']          = 'email';",
    "$_SESSION['2fa_pending_name']    = trim($user['first_name'] . ' ' . $user['last_name']);\n            $_SESSION['2fa_pending_remember'] = $remember;\n            $_SESSION['2fa_method']          = 'email';"
)

# Add remember cookie in login success
auth_content = auth_content.replace(
    "Activity::logLogin($user['id'], $user['role'], session_id(), $userName);\n        } catch (Exception $e) {}",
    "Activity::logLogin($user['id'], $user['role'], session_id(), $userName);\n        } catch (Exception $e) {}\n\n        if ($remember) {\n            self::setRememberCookie($user['id']);\n        }"
)

# Modify logout
auth_content = auth_content.replace(
    "Activity::logLogout((int)$_SESSION['user_id'], session_id());\n            }",
    "Activity::logLogout((int)$_SESSION['user_id'], session_id());\n                try { Database::query(\"UPDATE `users` SET `remember_token`=NULL WHERE `id`=?\", [$_SESSION['user_id']]); } catch (\\Exception $e) {}\n            }\n            setcookie('remember_token', '', time() - 3600, '/');"
)

# Add autoLoginFromCookie and setRememberCookie methods
new_methods = """
    public static function setRememberCookie(int $userId): void {
        try { Database::query("ALTER TABLE `users` ADD COLUMN `remember_token` VARCHAR(64) DEFAULT NULL"); } catch (\Throwable $_e) {}
        $token = bin2hex(random_bytes(32));
        try { Database::query("UPDATE `users` SET `remember_token`=? WHERE `id`=?", [$token, $userId]); } catch (\Throwable $e) {}
        // 30 days
        setcookie('remember_token', $token, time() + 2592000, '/', '', false, true); 
    }

    private static function autoLoginFromCookie(string $token): void {
        try { Database::query("ALTER TABLE `users` ADD COLUMN `remember_token` VARCHAR(64) DEFAULT NULL"); } catch (\Throwable $_e) {}
        try {
            $user = Database::fetchOne("SELECT * FROM `users` WHERE `remember_token`=?", [$token]);
            if ($user && $user['status'] === 'active') {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
                
                if ($user['role'] === 'affiliate') {
                    $aff = Database::fetchOne("SELECT `id` FROM `affiliates` WHERE `user_id` = ?", [$user['id']]);
                    $_SESSION['affiliate_id'] = $aff['id'] ?? null;
                } elseif ($user['role'] === 'advertiser') {
                    $adv = Database::fetchOne("SELECT `id` FROM `advertisers` WHERE `user_id` = ?", [$user['id']]);
                    $_SESSION['advertiser_id'] = $adv['id'] ?? null;
                }
                
                Database::query("UPDATE `users` SET `last_login`=NOW() WHERE `id`=?", [$user['id']]);
                try { Activity::logLogin($user['id'], $user['role'], session_id(), $_SESSION['user_name']); } catch (\Exception $e) {}
            } else {
                setcookie('remember_token', '', time() - 3600, '/');
            }
        } catch (\Throwable $e) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
    }

"""
auth_content = auth_content.replace("    public static function generateCsrf(): string {", new_methods + "    public static function generateCsrf(): string {")

with open(auth_path, "w", encoding="utf-8") as f:
    f.write(auth_content)


# 2. Modify views/auth/login.php
login_view_path = os.path.join(dir_path, "views/auth/login.php")
with open(login_view_path, "r", encoding="utf-8") as f:
    lv_content = f.read()

checkbox_html = """            <div class="form-group" style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                <input type="checkbox" id="remember" name="remember" value="1" <?= (isset($_POST['remember']) && $_POST['remember'] === '1') ? 'checked' : '' ?>>
                <label for="remember" style="margin:0;font-size:13px;font-weight:500;">Remember Me for 30 days</label>
            </div>
            <?php if (Turnstile::isEnabled()): ?>"""

lv_content = lv_content.replace("            <?php if (Turnstile::isEnabled()): ?>", checkbox_html)
with open(login_view_path, "w", encoding="utf-8") as f:
    f.write(lv_content)


# 3. Modify controllers/auth/LoginController.php
login_ctrl_path = os.path.join(dir_path, "controllers/auth/LoginController.php")
with open(login_ctrl_path, "r", encoding="utf-8") as f:
    lc_content = f.read()

lc_content = lc_content.replace(
    "$result   = Auth::login($email, $password);",
    "$remember = (Helpers::postRaw('remember') === '1');\n        $result   = Auth::login($email, $password, $remember);"
)
with open(login_ctrl_path, "w", encoding="utf-8") as f:
    f.write(lc_content)


# 4. Modify controllers/auth/TwoFactorController.php
twofa_ctrl_path = os.path.join(dir_path, "controllers/auth/TwoFactorController.php")
with open(twofa_ctrl_path, "r", encoding="utf-8") as f:
    tf_content = f.read()

tf_content = tf_content.replace(
    "unset($_SESSION['2fa_pending_user_id'], $_SESSION['2fa_pending_role'],\n                  $_SESSION['2fa_pending_email'], $_SESSION['2fa_pending_name'],\n                  $_SESSION['2fa_code'], $_SESSION['2fa_expires'],\n                  $_SESSION['2fa_method'], $_SESSION['2fa_attempts']);",
    "if (!empty($_SESSION['2fa_pending_remember'])) {\n                Auth::setRememberCookie($userId);\n            }\n            unset($_SESSION['2fa_pending_user_id'], $_SESSION['2fa_pending_role'],\n                  $_SESSION['2fa_pending_email'], $_SESSION['2fa_pending_name'],\n                  $_SESSION['2fa_code'], $_SESSION['2fa_expires'],\n                  $_SESSION['2fa_method'], $_SESSION['2fa_attempts'], $_SESSION['2fa_pending_remember']);"
)
tf_content = tf_content.replace(
    "unset($_SESSION['2fa_pending_user_id'], $_SESSION['2fa_pending_role'],\n                  $_SESSION['2fa_pending_email'], $_SESSION['2fa_pending_name'],\n                  $_SESSION['2fa_code'], $_SESSION['2fa_method'],\n                  $_SESSION['2fa_expires'], $_SESSION['2fa_attempts']);",
    "unset($_SESSION['2fa_pending_user_id'], $_SESSION['2fa_pending_role'],\n                  $_SESSION['2fa_pending_email'], $_SESSION['2fa_pending_name'],\n                  $_SESSION['2fa_code'], $_SESSION['2fa_method'],\n                  $_SESSION['2fa_expires'], $_SESSION['2fa_attempts'], $_SESSION['2fa_pending_remember']);"
)
tf_content = tf_content.replace(
    "unset($_SESSION['2fa_pending_user_id'], $_SESSION['2fa_pending_role'],\n          $_SESSION['2fa_pending_email'], $_SESSION['2fa_pending_name'],\n          $_SESSION['2fa_code'], $_SESSION['2fa_expires']);",
    "unset($_SESSION['2fa_pending_user_id'], $_SESSION['2fa_pending_role'],\n          $_SESSION['2fa_pending_email'], $_SESSION['2fa_pending_name'],\n          $_SESSION['2fa_code'], $_SESSION['2fa_expires'], $_SESSION['2fa_pending_remember']);"
)

with open(twofa_ctrl_path, "w", encoding="utf-8") as f:
    f.write(tf_content)

print("Remember me successfully applied.")

