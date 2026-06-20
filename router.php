<?php
// router.php
if (preg_match('/^\/api\//', $_SERVER["REQUEST_URI"])) {
    $_GET['route'] = $_SERVER["REQUEST_URI"];
    include 'index.php';
} else {
    return false;    // serve the requested resource as-is.
}
