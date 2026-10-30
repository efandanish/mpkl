<?php
// Docker publishes this development listener on host loopback only.
if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/__runtime') {
    header('Content-Type: application/json');
    echo json_encode(['version' => PHP_VERSION, 'sapi' => PHP_SAPI, 'architecture' => php_uname('m'),
        'ini' => php_ini_scanned_files()]);
    return;
}
require '/workspace/scripts/router.php';
