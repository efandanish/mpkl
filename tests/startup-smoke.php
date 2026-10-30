<?php
require dirname(__DIR__) . '/scripts/bootstrap.php';

function startupExpect($condition, $message)
{
    if (!$condition) { throw new RuntimeException($message); }
    echo 'PASS ' . $message . "\n";
}

$fixture = sys_get_temp_dir() . '/cajpemajuan-startup-' . bin2hex(random_bytes(6));
mkdir($fixture . '/app/controllers', 0700, true);
mkdir($fixture . '/vendor', 0700, true);
$run = function ($code) {
    $process = proc_open(array(PHP_BINARY, '-r', $code), array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
    if (!is_resource($process)) { throw new RuntimeException('Cannot start PHP subprocess'); }
    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return array(proc_close($process), $output);
};
$code = 'require ' . var_export(APP . 'core/Startup.php', true) . '; Startup::check(' . var_export($fixture, true) . ');';
try {
    $result = $run($code);
    startupExpect($result[0] === 1 && strpos($result[1], 'Composer dependencies are missing') !== false, 'Missing dependencies produce a useful message');
    file_put_contents($fixture . '/vendor/autoload.php', '<?php');
    $result = $run($code);
    startupExpect($result[0] === 1 && strpos($result[1], 'AuthController.php') !== false, 'Missing login controller is reported');
    file_put_contents($fixture . '/app/controllers/AuthController.php', '<?php');
    $result = $run($code);
    startupExpect($result[0] === 1 && strpos($result[1], 'ErrorsController.php') !== false, 'Missing error controller is reported');

    // Force both log and template failures in a separate process.
    $result = $run('define("APP", ' . var_export($fixture . '/app/', true) . '); '
        . 'require ' . var_export(APP . 'core/Startup.php', true) . '; '
        . 'require ' . var_export(BASE_DIR . '/vendor/autoload.php', true) . '; '
        . 'Handler::register(); Handler::handleException(new RuntimeException("Test failure"));');
    startupExpect($result[0] === 1 && strpos($result[1], 'The system could not render this page') !== false, 'Log and template failures still produce a fallback message');
} finally {
    foreach (array('/vendor/autoload.php', '/app/controllers/AuthController.php') as $file) {
        if (is_file($fixture . $file)) { unlink($fixture . $file); }
    }
    rmdir($fixture . '/app/controllers');
    rmdir($fixture . '/app');
    rmdir($fixture . '/vendor');
    rmdir($fixture);
}

Handler::register();
@trigger_error('Suppressed warning for regression test', E_USER_WARNING);
startupExpect(true, 'Suppressed errors respect the PHP error mask');
try {
    trigger_error('Expected warning', E_USER_WARNING);
    throw new RuntimeException('The warning was not converted to an exception');
} catch (ErrorException $e) {
    startupExpect($e->getMessage() === 'Expected warning', 'Unsuppressed warnings are handled');
}
restore_error_handler();
restore_exception_handler();
