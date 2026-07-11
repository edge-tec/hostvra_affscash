<?php
if (!defined('BASE_PATH')) exit;
$seoTitle = '404 Not Found - ' . Config::get('config', 'app.name');
$seoNoindex = true;
require BASE_PATH . '/views/partials/seo_head.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require BASE_PATH . '/views/partials/seo_head.php'; ?>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background-color: #f3f4f6; color: #111827; }
        .container { text-align: center; }
        h1 { font-size: 4rem; margin: 0; font-weight: 800; color: #2563eb; }
        p { font-size: 1.25rem; margin-top: 1rem; color: #4b5563; }
        a { display: inline-block; margin-top: 1.5rem; padding: 0.75rem 1.5rem; background-color: #2563eb; color: white; text-decoration: none; border-radius: 0.375rem; font-weight: 600; }
        a:hover { background-color: #1d4ed8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <p>Oops! The page you're looking for doesn't exist.</p>
        <a href="/">Go Back Home</a>
    </div>
</body>
</html>
