<?php
// Execute only inside the isolated compose workspace, never from the host deployment.
if (PHP_VERSION !== '8.2.5' || !is_file('/runtime/local.example.php') || !is_dir('/source')) {
    fwrite(STDERR, "Use scripts/php825 docker/php825/verify.php inside the exact-version container.\n");
    exit(1);
}
require dirname(__DIR__, 2) . '/scripts/bootstrap.php';
error_reporting(E_ALL);
function runtimeRun(array $command, $label, $quiet = false) {
    $out = tempnam('/tmp', 'php825-out-');
    $err = tempnam('/tmp', 'php825-err-');
    $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['file', $out, 'w'], 2 => ['file', $err, 'w']], $pipes, BASE_DIR);
    if (!is_resource($process)) { throw new RuntimeException('Cannot run ' . $label); }
    $status = proc_close($process);
    $output = file_get_contents($out) . file_get_contents($err);
    unlink($out); unlink($err);
    if (!$quiet || $status !== 0 || preg_match('/PHP (Warning|Deprecated|Notice|Fatal|Parse)|Uncaught/', $output)) { echo $output; }
    if ($status !== 0 || preg_match('/PHP (Warning|Deprecated|Notice|Fatal|Parse)|Uncaught/', $output)) {
        throw new RuntimeException($label . ' failed; exit=' . $status);
    }
    if (!$quiet) { echo 'PASS command: ' . $label . "\n"; }
}
echo 'RUNTIME ' . PHP_VERSION . ' ' . PHP_SAPI . ' ' . php_uname('m') . ' ZTS=' . PHP_ZTS . "\n";
foreach (['pdo','pdo_mysql','mbstring','openssl','fileinfo','gd'] as $extension) {
    if (!extension_loaded($extension)) { throw new RuntimeException('Missing extension: ' . $extension); }
    echo 'PASS extension: ' . $extension . "\n";
}
$gd = gd_info();
if (empty($gd['FreeType Support']) || empty($gd['JPEG Support']) || empty($gd['PNG Support'])) { throw new RuntimeException('Missing GD rendering support'); }
echo 'PASS GD FreeType/JPEG/PNG' . "\n";
$pdo = new PDO('mysql:host=' . Config::get('DB_HOST') . ';dbname=' . Config::get('DB_NAME'), Config::get('DB_USER'), Config::get('DB_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
echo 'DATABASE ' . $pdo->query('SELECT VERSION()')->fetchColumn() . ' ' . $pdo->query('SELECT DATABASE()')->fetchColumn() . "\n";
if (Config::get('DB_HOST') !== 'db' || Config::get('DB_NAME') !== 'cajpemajuan_php825_dev_test') { throw new RuntimeException('Unexpected database target'); }
$count = 0;
foreach (['app','scripts','tests','public','vendor','docker'] as $directory) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(BASE_DIR . '/' . $directory, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            runtimeRun([PHP_BINARY, '-d', 'error_reporting=-1', '-l', $file->getPathname()], 'Lint ' . $file->getPathname(), true);
            $count++;
        }
    }
}
echo 'PASS syntax: ' . $count . ' PHP files' . "\n";
runtimeRun([PHP_BINARY, '/usr/local/bin/composer', 'validate', '--no-check-publish'], 'Composer validate');
runtimeRun([PHP_BINARY, '/usr/local/bin/composer', 'check-platform-reqs', '--lock'], 'Composer real platform requirements');
foreach (['problem4-calculator.php','compatibility-smoke.php','startup-smoke.php','modules-smoke.php'] as $test) {
    runtimeRun([PHP_BINARY, '-d', 'error_reporting=-1', BASE_DIR . '/tests/' . $test], $test);
}
echo "EXACT PHP 8.2.5 VERIFICATION PASSED\n";
