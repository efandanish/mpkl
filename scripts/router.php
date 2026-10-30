<?php
// Development server only: php -S 127.0.0.1:8080 -t public scripts/router.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$public = realpath(dirname(__DIR__) . '/public');
$file = realpath($public . '/' . ltrim($path, '/'));
if ($path !== '/' && $file !== false && strpos($file, $public . DIRECTORY_SEPARATOR) === 0 && is_file($file)) {
    return false;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_GET['url'] = trim($path, '/');
require $public . '/index.php';
