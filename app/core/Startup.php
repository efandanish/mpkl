<?php

/** Startup diagnostics must work without Composer, controllers, or the database. */
class Startup
{
    public static function check($root)
    {
        if (PHP_VERSION_ID < 70400 || PHP_VERSION_ID >= 80300) {
            self::failure('This project supports PHP 7.4 through 8.2; the upgrade target is exactly PHP 8.2.5. The running PHP version is ' . PHP_VERSION . '. Select a supported runtime for both CLI and the web server.');
        }
        if (!is_readable($root . '/vendor/autoload.php')) {
            self::failure('Composer dependencies are missing. Run composer install in the project directory.');
        }
        foreach (array('AuthController', 'ErrorsController') as $controller) {
            if (!is_readable($root . '/app/controllers/' . $controller . '.php')) {
                self::failure('Required controller is missing: app/controllers/' . $controller . '.php. Restore this file from the complete project.');
            }
        }
    }

    public static function failure($message)
    {
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, 'Startup failed: ' . $message . "\n");
            exit(1);
        }
        // Discard partially rendered templates before showing a standalone page.
        while (ob_get_level() > 0) {
            if (!@ob_end_clean()) { break; }
        }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>System setup required</title></head><body>'
            . '<main style="max-width:720px;margin:60px auto;padding:24px;font-family:Arial,sans-serif;line-height:1.6">'
            . '<h1>System setup required</h1><p>' . $message . '</p>'
            . '<p>Follow README.md, then run <code>php scripts/check-setup.php</code>.</p>'
            . '<p>For application errors, check <code>app/logs/log.txt</code> and the web server PHP error log.</p>'
            . '</main></body></html>';
        exit(1);
    }
}
