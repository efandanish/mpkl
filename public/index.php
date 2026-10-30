<?php
// Keep diagnostics available even when Composer or an application class is missing.
define('BASE_DIR', str_replace('\\', '/', dirname(__DIR__)));
define('APP', BASE_DIR . '/app/');
define('IMAGES', str_replace('\\', '/', __DIR__) . '/img/');
date_default_timezone_set('Asia/Kuala_Lumpur');
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$startupFile = APP . 'core/Startup.php';
if (!is_readable($startupFile)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'System setup required: app/core/Startup.php is missing. Restore the complete project files.';
    exit(1);
}
require $startupFile;
Startup::check(BASE_DIR);

try {
    require BASE_DIR . '/vendor/autoload.php';
    Handler::register();
    $app = new App();
    define('PUBLIC_ROOT', $app->request->root());
    Session::init();
    $app->run();
} catch (Throwable $e) {
    // This also catches failures before the normal exception handler is registered.
    if (class_exists('Handler', false)) {
        Handler::handleException($e);
    } else {
        @error_log(get_class($e) . ': ' . $e->getMessage());
        Startup::failure('Application startup failed. Run composer install and check the web server PHP error log.');
    }
}
