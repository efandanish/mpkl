<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('BASE_DIR', str_replace('\\', '/', dirname(__DIR__)));
define('APP', BASE_DIR . '/app/');
define('PUBLIC_ROOT', 'http://localhost/');
date_default_timezone_set('Asia/Kuala_Lumpur');
require APP . 'core/Startup.php';
Startup::check(BASE_DIR);
require BASE_DIR . '/vendor/autoload.php';
