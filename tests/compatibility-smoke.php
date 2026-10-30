<?php
require dirname(__DIR__) . '/scripts/bootstrap.php';

// Surface warnings/deprecations as failures, including dependency rendering.
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) { return false; }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

function verify($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo 'PASS ' . $message . "\n";
}

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
foreach (array(
    '/index.php' => '/',
    '/public/index.php' => '/',
    '/renamed-app/public/index.php' => '/renamed-app/',
    '/public-office/public/index.php' => '/public-office/'
) as $script => $expected) {
    $_SERVER['SCRIPT_NAME'] = $script;
    $request = new Request();
    verify($request->getBaseUrl() === $expected, 'URL root for ' . $script);
}

$_SERVER['HTTP_HOST'] = 'localhost:8080';
$_SERVER['SCRIPT_NAME'] = '/index.php';
verify((new Request())->root() === 'http://localhost:8080/', 'URL root preserves development server port');
$_SERVER['HTTP_HOST'] = 'localhost';

$_GET = ['year' => '2025', 'url' => 'dashboard', 'redirect' => 'auth', 'filter' => 'A&B'];
$_SERVER['REQUEST_URI'] = '/dashboard?year=2025';
verify((new Request())->fullUrl() === 'http://localhost/dashboard?year=2025&filter=A%26B', 'Query URLs preserve year and encoding without nullable numeric prefix');
verify((new Redirector())->to('/charge', ['year' => '2025']) instanceof Response, 'Query redirects build without nullable numeric prefix');
$_GET = [];
$_SERVER['REQUEST_URI'] = '/';
$savedServer = $_SERVER;
unset($_SERVER['HTTP_HOST'], $_SERVER['SERVER_NAME'], $_SERVER['SERVER_ADDR'], $_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']);
verify((new Request())->fullUrl() === 'http://', 'CLI construction tolerates missing HTTP context without deprecated null conversions');
$_SERVER = $savedServer;

$method = new ReflectionMethod('Database', 'getPDOType');
if (PHP_VERSION_ID < 80100) { $method->setAccessible(true); }
foreach (array(
    array(0, PDO::PARAM_INT), array(42, PDO::PARAM_INT),
    array(false, PDO::PARAM_BOOL), array(true, PDO::PARAM_BOOL),
    array(null, PDO::PARAM_NULL), array('', PDO::PARAM_STR),
    array('0', PDO::PARAM_STR), array('abc', PDO::PARAM_STR),
    array(12.5, PDO::PARAM_STR)
) as $case) {
    verify($method->invoke(null, $case[0]) === $case[1], 'PDO type for ' . var_export($case[0], true));
}

foreach (array(400, 401, 403, 404, 500) as $code) {
    $response = (new ErrorsController())->error($code);
    verify($response instanceof Response, 'Error response ' . $code);
}
$controller = new Controller();
$firstModel = $controller->loadModel('User');
verify($controller->User === $firstModel && isset($controller->User), 'Loaded model retains identity and isset semantics');
$controller->user = $firstModel;
verify($controller->user === $firstModel, 'Lowercase model alias retains identity');
unset($controller->user);
verify(!isset($controller->user), 'Model alias can be unset');
verify($controller->user instanceof User, 'Magic model loading still works');
$controller->loadComponents(['Security']);
verify($controller->Security instanceof SecurityComponent && isset($controller->Security), 'Components load without dynamic properties');
$mailer = new \PHPMailer\PHPMailer\PHPMailer();
verify($mailer->addAddress('test@example.com'), 'Mail dependency loads and accepts recipient');
$captcha = new CompatibleCaptchaBuilder();
$captcha->build();
verify(strlen($captcha->get()) > 0, 'CAPTCHA renders with GD');
foreach ([true, false] as $interpolation) {
    $captcha = CompatibleCaptchaBuilder::create('A7b2Z')->setInterpolation($interpolation);
    $captcha->build(151, 41);
    verify(strlen($captcha->get()) > 0 && $captcha->testPhrase('A7b2Z'), 'CAPTCHA odd dimensions with interpolation ' . ($interpolation ? 'on' : 'off'));
    $fingerprint = $captcha->getFingerprint();
    $replay = CompatibleCaptchaBuilder::create('A7b2Z')->setInterpolation($interpolation);
    $replay->build(151, 41, null, $fingerprint);
    verify($captcha->get() === $replay->get(), 'CAPTCHA fingerprint replay is identical');
}
restore_error_handler();
echo "Compatibility smoke checks passed.\n";
