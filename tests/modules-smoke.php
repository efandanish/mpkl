<?php
/** HTTP integration checks against a temporary, isolated database. PHP 7.4 compatible. */
require dirname(__DIR__) . '/scripts/bootstrap.php';

function moduleExpect($ok, $message)
{
    if (!$ok) {
        global $fixture;
        // Keep the runtime cause visible before disposable server logs are removed.
        if (isset($fixture) && is_file($fixture . '/server.log')) {
            $log = file_get_contents($fixture . '/server.log');
            if (preg_match_all('/^.*(?:PHP (?:Warning|Fatal|Notice|Deprecated|Parse)|ErrorException|Uncaught).*$/m', $log, $errors)) {
                echo implode("\n", array_unique($errors[0])) . "\n";
            }
        }
        throw new RuntimeException($message);
    }
    echo 'PASS ' . $message . "\n";
}
function moduleRequest($path, $data = null, $cookie = '', $multipart = false, $csrf = true)
{
    global $moduleBase;
    if ($data !== null && $csrf && !array_key_exists('csrf_token', $data)) {
        $page = moduleRequest($cookie ? 'charge' : 'auth', null, $cookie);
        if (!$cookie) { $cookie = $page['cookie']; }
        if (!preg_match('/name="csrf_token"[^>]*value="([a-f0-9]+)"/', $page['body'], $token)) {
            throw new RuntimeException('No CSRF token on fixture form.');
        }
        $data['csrf_token'] = $token[1];
    }
    $headers = $cookie ? 'Cookie: ' . $cookie . "\r\n" : '';
    $options = ['method' => $data === null ? 'GET' : 'POST', 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 10];
    if ($data !== null) {
        if ($multipart) {
            $boundary = 'module-' . bin2hex(random_bytes(12));
            $headers .= 'Content-Type: multipart/form-data; boundary=' . $boundary . "\r\n";
            $content = '';
            foreach ($data as $name => $value) {
                $content .= '--' . $boundary . "\r\nContent-Disposition: form-data; name=\"" . $name . "\"\r\n\r\n" . $value . "\r\n";
            }
            $content .= '--' . $boundary . "\r\nContent-Disposition: form-data; name=\"receipt\"; filename=\"test.pdf\"\r\nContent-Type: application/pdf\r\n\r\n%PDF-1.4\nModule receipt fixture\r\n--" . $boundary . "--\r\n";
            $options['content'] = $content;
        } else {
            $headers .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $options['content'] = http_build_query($data);
        }
    }
    $options['header'] = $headers;
    $body = file_get_contents($moduleBase . $path, false, stream_context_create(['http' => $options]));
    $result = ['body' => $body, 'status' => (int) substr($http_response_header[0], 9, 3), 'location' => '', 'cookie' => ''];
    foreach ($http_response_header as $header) {
        if (stripos($header, 'Location: ') === 0) { $result['location'] = substr($header, 10); }
        if (stripos($header, 'Set-Cookie: ') === 0) { $result['cookie'] = explode(';', substr($header, 12))[0]; }
    }
    return $result;
}
function moduleDashboardCards($html)
{
    preg_match_all('/class="stat-label">([^<]+)<\/div>.*?class="stat-value">\s*(.*?)<\/div>/s', $html, $matches, PREG_SET_ORDER);
    $cards = [];
    foreach ($matches as $match) { $cards[trim($match[1])] = trim(strip_tags($match[2])); }
    return $cards;
}
function modulePost($path, array $data, $cookie, $expected)
{
    $r = moduleRequest($path, $data, $cookie);
    moduleExpect($r['status'] === 302 && strpos($r['location'], $expected) !== false, 'POST ' . $path . ' redirects to ' . $expected);
}

function moduleClientFingerprint(PDO $connection, $schema)
{
    // Read-only fingerprint; never use the client schema for fixture mutations.
    $connection->exec('START TRANSACTION READ ONLY');
    try {
        $state = [];
        foreach ($connection->query('SHOW TABLES FROM `' . str_replace('`', '``', $schema) . '`')->fetchAll(PDO::FETCH_COLUMN) as $table) {
            $rows = $connection->query('SELECT * FROM `' . str_replace('`', '``', $schema) . '`.`' . str_replace('`', '``', $table) . '`')->fetchAll(PDO::FETCH_ASSOC);
            $serialized = array_map('serialize', $rows);
            sort($serialized);
            $structure = $connection->query('SHOW CREATE TABLE `' . str_replace('`', '``', $schema) . '`.`' . str_replace('`', '``', $table) . '`')->fetch(PDO::FETCH_NUM);
            $state[$table] = hash('sha256', serialize([$serialized, $structure[1]]));
        }
        return $state;
    } finally { $connection->exec('ROLLBACK'); }
}
function moduleReceiptFingerprint()
{
    $state = [];
    $root = BASE_DIR . '/public/uploads/receipts';
    if (is_dir($root)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) { $state[$file->getPathname()] = hash_file('sha256', $file->getPathname()); }
        }
    }
    ksort($state);
    return $state;
}

$testName = 'cajpemajuan_modules_' . bin2hex(random_bytes(5)) . '_test';
$pdo = new PDO('mysql:host=' . Config::get('DB_HOST'), Config::get('DB_USER'), Config::get('DB_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec("SET time_zone = '+00:00'");
$clientSchema = Config::get('DB_NAME');
$clientBefore = moduleClientFingerprint($pdo, $clientSchema);
$receiptsBefore = moduleReceiptFingerprint();
$server = null;
$fixture = sys_get_temp_dir() . '/' . $testName;
mkdir($fixture, 0700);
try {
    $pdo->exec('CREATE DATABASE `' . $testName . '` CHARACTER SET utf8mb4');
    $pdo->exec('USE `' . $testName . '`');
    $pdo->exec(file_get_contents(BASE_DIR . '/database/schema.sql'));
    if (!in_array('--skip-migrations', $argv, true)) {
        require __DIR__ . '/stage2a-migrations.php';
        require __DIR__ . '/stage3a-migrations.php';
        require __DIR__ . '/problem3b-migrations.php';
    }
    Config::set('DB_NAME', $testName);
    Config::set('RECEIPT_STORAGE_PATH', $fixture . '/receipts');
    mkdir($fixture . '/receipts', 0700);
    $users = new User();
    $users->create(['name' => 'Module Admin', 'email' => 'admin@example.test', 'password' => 'module-pass', 'role' => 'admin']);
    $users->create(['name' => 'Module User', 'email' => 'user@example.test', 'password' => 'module-pass', 'role' => 'user']);
    $admin = $users->getByEmail('admin@example.test');
    $normal = $users->getByEmail('user@example.test');
    $chargeData = ['year' => '2026', 'developer_name' => 'Module Developer', 'project_name' => 'Module Charge', 'osc_id' => 'OSC-TEST', 'lot_number' => '1', 'mukim' => 'Test', 'file_number' => 'TEST', 'charge_amount' => '900', 'osc_decision' => 'Lulus', 'payment_plan' => '3 Ansuran'];
    $projectData = ['year' => '2026', 'project_id' => 'PROJECT-TEST', 'department' => 'Jabatan Kejuruteraan', 'project_name' => 'Module Project', 'description' => 'Test', 'approval_status' => 'Diluluskan', 'meeting_count' => '1', 'procurement_type' => 'Tender', 'sst_value' => '1000', 'meeting_approval_value' => '1000', 'payment_method' => 'Ansuran', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'project_status' => 'Dalam Proses'];
    // Run the real front controller, changing only the database and session directory.
    $router = file_get_contents(BASE_DIR . '/public/index.php');
    $router = str_replace("dirname(__DIR__)", var_export(BASE_DIR, true), $router);
    $router = str_replace("__DIR__) . '/img/'", var_export(BASE_DIR . '/public', true) . ") . '/img/'", $router);
    $router = str_replace("    Session::init();", "    Config::set('DB_NAME', " . var_export($testName, true) . ");\n    Config::set('RECEIPT_STORAGE_PATH', " . var_export($fixture . '/receipts', true) . ");\n    Session::init();", $router);
    $router = str_replace('<?php', "<?php\n\$_SERVER['SCRIPT_NAME'] = '/index.php';\n\$_GET['url'] = trim(parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');", $router);
    file_put_contents($fixture . '/router.php', $router);
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $port = (int) substr(strrchr($address, ':'), 1);
    $moduleBase = 'http://127.0.0.1:' . $port . '/';
    $server = proc_open([PHP_BINARY, '-d', 'track_errors=0', '-d', 'error_reporting=-1', '-d', 'log_errors=1', '-d', 'session.save_path=' . $fixture, '-S', '127.0.0.1:' . $port, $fixture . '/router.php'], [0 => ['pipe', 'r'], 1 => ['file', $fixture . '/server.log', 'a'], 2 => ['file', $fixture . '/server.log', 'a']], $pipes, BASE_DIR . '/public');
    moduleExpect(is_resource($server), 'Start isolated HTTP server');
    for ($i = 0; $i < 50; $i++) {
        $ready = @fsockopen('127.0.0.1', $port);
        if ($ready) { fclose($ready); break; }
        usleep(100000);
    }
    $r = moduleRequest('auth');
    moduleExpect($r['status'] === 200 && strpos($r['body'], 'Selamat Datang') !== false, 'Login page');
    $r = moduleRequest('auth/login', ['email' => 'admin@example.test', 'password' => 'module-pass']);
    $adminCookie = $r['cookie'];
    moduleExpect($adminCookie && strpos($r['location'], 'dashboard') !== false, 'Admin login');
    $r = moduleRequest('auth/login', ['email' => 'user@example.test', 'password' => 'module-pass']);
    $userCookie = $r['cookie'];
    moduleExpect($userCookie && strpos($r['location'], 'dashboard') !== false, 'Normal user login');
    foreach (['charge/getFormData', 'charge/requireAdmin', 'notification/notificationTarget/1', 'charge/initialize', 'charge/loadModel/User', 'module/initialize'] as $path) {
        $r = moduleRequest($path, null, $adminCookie);
        moduleExpect($r['status'] === 404, 'Internal controller method is not a route: ' . $path);
    }
    foreach (['dashboard', 'charge', 'project', 'notification', 'notify'] as $path) {
        foreach ([$adminCookie, $userCookie] as $cookie) {
            $r = moduleRequest($path, null, $cookie);
            moduleExpect($r['status'] === 200 && strpos($r['body'], 'modal fade') === false, 'Standalone GET ' . $path);
        }
    }
    $r = moduleRequest('dashboard', null, $adminCookie);
    moduleExpect(strpos($r['body'], 'chargeSearch') === false && strpos($r['body'], 'projectSearch') === false, 'Dashboard contains no tables');
    foreach ([$adminCookie, $userCookie] as $cookie) {
        $r = moduleRequest('dashboard?year=2025', null, $cookie);
        moduleExpect(strpos($r['body'], 'Ringkasan Akaun Caj Pemajuan bagi tahun 2025') !== false && substr_count($r['body'], 'class="dashboard-summary-pane"') === 5, 'Dashboard year header and five financial cards');
        moduleExpect(strpos($r['body'], '<table') === false && strpos($r['body'], 'modal fade') === false, 'Dashboard remains overview only');
        preg_match('/<aside.*?<\/aside>/s', $r['body'], $sidebar);
        moduleExpect(strpos($sidebar[0], 'notification') === false && strpos($sidebar[0], 'sidebarToggle') !== false, 'Sidebar omits notification and retains collapse control');
        moduleExpect(strpos($r['body'], 'href="' . $moduleBase . 'notification?year=2025"') !== false && strpos($r['body'], 'notification-button') !== false, 'Notification bell and history link preserved');
        moduleExpect(strpos($r['body'], 'system-summary-title') === false && strpos($r['body'], 'dashboard-system-card') === false, 'Dashboard has no secondary summary section');
    }
    $r = moduleRequest('charge?year=2025', null, $userCookie);
    moduleExpect(strpos($r['body'], 'action="' . $moduleBase . 'charge"') !== false && strpos($r['body'], 'name="year"') !== false, 'Year filter stays inside charge module');
    moduleExpect(strpos($r['body'], 'user/create') === false && strpos($r['body'], 'charge/create?year=2025') !== false, 'Normal user can create charges while user management stays hidden');
    foreach (['charge/create', 'project/create', 'user', 'user/create', 'user/edit/' . $admin['id']] as $path) {
        moduleExpect(moduleRequest($path, null, $adminCookie)['status'] === 200, 'Admin GET ' . $path);
        $ordinaryPage = moduleRequest($path, null, $userCookie);
        if (in_array($path, ['charge/create', 'project/create'], true)) {
            moduleExpect($ordinaryPage['status'] === 200, 'Normal user opens ' . $path);
        } else {
            moduleExpect(strpos($ordinaryPage['location'], 'unauthorized') !== false, 'Normal user denied ' . $path);
        }
    }
    foreach (['charge', 'project', 'user', 'notification'] as $path) {
        moduleExpect(strpos(moduleRequest($path)['location'], 'auth') !== false, 'Anonymous denied ' . $path);
    }
    // Historical manual 3 Ansuran fixture: deliberately outside the new creation rules.
    $charges = new DevelopmentCharge();
    $legacyChargeId = $charges->create($chargeData + ['record_year' => 2026]);
    foreach ([1, 2, 3] as $number) { $charges->addPaymentSchedule($legacyChargeId, $number, '300.00', '2026-12-0' . $number); }
    moduleExpect((bool) $legacyChargeId, 'Historical manual charge fixture created without automatic conversion');
    $charge = $charges->getAllByYear(2026)[0];
    $chargeId = $charge['id'];
    moduleExpect(count($charges->getPayments($chargeId)) === 3, 'Charge installments created');
    $payment = $charges->getPayments($chargeId)[1];
    $r = moduleRequest('charge/payment/' . $payment['id'], ['year' => '2026', 'payment_date' => '2026-10-11', 'receipt_number' => 'STAGE1-001'], $adminCookie, true);
    moduleExpect($r['status'] === 302 && strpos($r['location'], 'charge/payment/' . $chargeId) !== false, 'Charge receipt upload redirects to parent charge');
    $receipt = $charges->getPaymentById($payment['id'])['receipt_file'];
    moduleExpect($receipt && is_file($fixture . '/receipts/' . basename($receipt)), 'Receipt file uploaded');
    moduleExpect(strpos(moduleRequest('charge/payment/' . $chargeId, null, $adminCookie)['body'], htmlspecialchars($receipt)) !== false, 'Receipt viewing link');

    moduleExpect($charges->getPaymentById($payment['id'])['status'] === 'Dibayar', 'Charge payment date/status saved');
    $pending = $charges->getPayments($chargeId)[0];
    $charges->markPaymentPaid($pending['id'], '2026-10-11', null);
    $summary = $charges->getDashboardSummaryByYear(2026);
    moduleExpect((int) $summary['record_count'] === 1 && (float) $summary['paid_amount'] === (float) $charges->getTotalPaid($chargeId), 'Charge summary counts records once and matches existing paid total');
    $charges->cancelPayment($pending['id']);
    $summary = $charges->getDashboardSummaryByYear(2025);
    moduleExpect((int) $summary['record_count'] === 0 && (float) $summary['paid_amount'] === 0.0, 'Charge summary excludes other registration years');
    foreach ([$adminCookie, $userCookie] as $cookie) {
        $r = moduleRequest('dashboard?year=2026', null, $cookie);
        moduleExpect(moduleDashboardCards($r['body']) === ['Baki Bawa Hadapan'=>'Belum Disahkan','Jumlah Caj Pemajuan'=>'RM 900.00','Jumlah Kutipan'=>'RM 300.00','Jumlah Perbelanjaan'=>'RM 0.00','Baki Akhir Semasa'=>'Belum Tersedia'], 'Assessed charges and collections differ; empty expense ledger is zero and balances are unverified for both roles');
        $r = moduleRequest('dashboard?year=2025', null, $cookie);
        moduleExpect(moduleDashboardCards($r['body']) === ['Baki Bawa Hadapan'=>'Belum Disahkan','Jumlah Caj Pemajuan'=>'RM 0.00','Jumlah Kutipan'=>'RM 0.00','Jumlah Perbelanjaan'=>'RM 0.00','Baki Akhir Semasa'=>'Belum Tersedia'], 'Year selector changes verified totals without fabricating opening or closing balances');
    }

    $update = array_replace($chargeData, ['developer_name' => 'Updated Developer', 'payment_plan' => '4 Ansuran', 'payment_schedule_id' => [$payment['id'], $pending['id']], 'payment_schedule_amount' => [999, 350], 'payment_schedule_due_date' => ['2026-12-04', '2026-12-05']]);
    moduleExpect(moduleRequest('charge/update/' . $chargeId, $update, $adminCookie)['status'] === 422, 'Reject paid installment and locked plan tampering');
    moduleExpect($charges->getById($chargeId)['payment_plan'] === '3 Ansuran' && (float) $charges->getPaymentById($payment['id'])['amount'] === 300.0, 'Paid installment and plan remain locked');
    $pendingRows = array_values(array_filter($charges->getPayments($chargeId), function ($row) { return $row['status'] === 'Menunggu'; }));
    $update = array_replace($chargeData, ['developer_name' => 'Updated Developer', 'payment_schedule_id' => array_column($pendingRows, 'id'), 'payment_schedule_amount' => [350, 250], 'payment_schedule_due_date' => ['2026-12-05', '2026-12-03']]);
    modulePost('charge/update/' . $chargeId, $update, $adminCookie, 'charge?success=charge_updated');
    moduleExpect((float) $charges->getPaymentById($pending['id'])['amount'] === 350.0, 'Pending installments remain editable with reconciled principal');
    modulePost('charge/cancelPayment/' . $payment['id'], [], $adminCookie, 'charge/payment/' . $chargeId);
    clearstatcache(true, $fixture . '/receipts/' . basename($receipt));
    moduleExpect($charges->getPaymentById($payment['id'])['status'] === 'Menunggu' && !is_file($fixture . '/receipts/' . basename($receipt)), 'Cancel charge payment removes receipt');
    modulePost('project/create', $projectData + ['schedule_date' => ['2026-12-01'], 'schedule_amount' => [200], 'schedule_type' => ['Ansuran']], $adminCookie, 'project');
    $projects = new Project();
    $project = $projects->getAllByYear(2026)[0];
    $projectId = $project['id'];
    $schedule = $projects->getSchedules($projectId)[0];
    modulePost('project/update/' . $projectId, array_replace($projectData, ['project_name' => 'Updated Project']), $adminCookie, 'project/finance/' . $projectId);
    moduleExpect($projects->getById($projectId)['project_name'] === 'Updated Project', 'Project edit persists');
    modulePost('project/recordPayment/' . $schedule['id'], ['amount' => 200, 'payment_date' => '2026-10-11', 'description' => 'Test expense'], $adminCookie, 'project/finance/' . $projectId);
    moduleExpect((float) $projects->getTotalExpenses($projectId) === 200.0, 'Project payment and expenditure total');
    $expense = $projects->getExpenses($projectId)[0];
    modulePost('project/cancelPayment/' . $expense['id'], [], $adminCookie, 'project/finance/' . $projectId);
    moduleExpect((float) $projects->getTotalExpenses($projectId) === 0.0 && $projects->getScheduleById($schedule['id'])['status'] === 'Menunggu', 'Cancel project payment restores schedule');
    modulePost('project/addScheduleRecord/' . $projectId, ['claim_date' => '2026-12-03', 'amount' => 50, 'type' => 'Ansuran'], $adminCookie, 'project/finance/' . $projectId);
    modulePost('project/addManualExpense/' . $projectId, ['amount' => 75, 'payment_date' => '2026-10-11', 'description' => 'Manual expense'], $adminCookie, 'project/finance/' . $projectId);
    modulePost('project/addVariationRecord/' . $projectId, ['description' => 'Variation', 'amount' => 20, 'variation_type' => 'Tambah', 'variation_date' => '2026-10-11'], $adminCookie, 'project/finance/' . $projectId);
    moduleExpect((float) $projects->getVariationTotal($projectId) === 20.0, 'Project variation persists');
    $projects->addExpense($projectId, null, 25, '2026-10-11', 'Second summary fixture');
    $summary = $projects->getDashboardSummaryByYear(2026);
    moduleExpect((int) $summary['record_count'] === 1 && (float) $summary['expenditure_amount'] === (float) $projects->getTotalExpenses($projectId), 'Project summary counts records once and matches existing expenditure total');
    $summary = $projects->getDashboardSummaryByYear(2025);
    moduleExpect((int) $summary['record_count'] === 0 && (float) $summary['expenditure_amount'] === 0.0, 'Project summary excludes other start years');
    foreach ([$adminCookie, $userCookie] as $cookie) {
        $r = moduleRequest('dashboard?year=2026', null, $cookie);
        moduleExpect(substr_count($r['body'], 'class="dashboard-summary-pane"') === 5 && preg_match('/class="stat-value">\s*RM 900\.00/', $r['body']) === 1, 'Dashboard shows separate assessed and received figures');
    }

    foreach (['charge/edit/' . $chargeId, 'charge/payment/' . $chargeId, 'project/edit/' . $projectId, 'project/finance/' . $projectId] as $path) {
        foreach ([$adminCookie, $userCookie] as $cookie) {
            $r = moduleRequest($path, null, $cookie);
            moduleExpect($r['status'] === 200 && strpos($r['body'], 'modal fade') === false, 'Record page ' . $path);
        }
    }
    foreach (['charge/update/' . $chargeId, 'charge/payment/' . $payment['id'], 'project/update/' . $projectId, 'project/addManualExpense/' . $projectId, 'user/delete/' . $admin['id']] as $path) {
        modulePost($path, [], $userCookie, 'unauthorized');
    }
    modulePost('user/create', ['name' => 'New User', 'email' => 'new@example.test', 'password' => 'new-pass', 'role' => 'user', 'department' => 'Jabatan Kejuruteraan'], $adminCookie, 'user?success=user_created');
    $created = $users->getByEmail('new@example.test');
    modulePost('user/create', ['name' => 'Duplicate', 'email' => 'new@example.test', 'password' => 'new-pass', 'role' => 'user', 'department' => 'Jabatan Kejuruteraan'], $adminCookie, 'user_email_exists');
    modulePost('user/update/' . $created['id'], ['name' => 'Updated User', 'email' => 'new@example.test', 'password' => '', 'role' => 'user'], $adminCookie, 'user_updated');
    moduleExpect(password_verify('new-pass', $users->getByEmail('new@example.test')['password']), 'Optional user password unchanged');
    modulePost('user/update/' . $created['id'], ['name' => 'Updated User', 'email' => 'admin@example.test', 'password' => '', 'role' => 'user'], $adminCookie, 'user_email_exists');
    modulePost('user/update/' . $created['id'], ['name' => 'Updated User', 'email' => 'new@example.test', 'password' => 'changed-pass', 'role' => 'admin'], $adminCookie, 'user_updated');
    $updated = $users->getByEmail('new@example.test');
    moduleExpect($updated['role'] === 'admin' && password_verify('changed-pass', $updated['password']), 'Role and optional password update');
    modulePost('user/delete/' . $admin['id'], [], $adminCookie, 'cannot_delete_self');
    $notifications = new Notification();
    foreach ([['development_charge', $chargeId, 'updated', 'charge/edit/' . $chargeId], ['development_charge_payment', $payment['id'], 'cancelled', 'charge/payment/' . $chargeId], ['project', $projectId, 'updated', 'project/finance/' . $projectId], ['project_payment_schedule', $schedule['id'], 'project_payment_due_soon', 'project/finance/' . $projectId], ['user', $created['id'], 'user_updated', 'user/edit/' . $created['id']], ['project', $projectId, 'deleted', 'notification'], ['project', 99999, 'updated', 'notification'], [null, null, 'created', 'notification'], ['unknown', 1, 'test', 'notification']] as $case) {
        $notifications->create(['category' => 'system', 'action' => $case[2], 'title' => 'Route Test', 'message' => 'Route Test', 'entity_type' => $case[0], 'entity_id' => $case[1]]);
        $id = $pdo->query('SELECT MAX(id) FROM notifications')->fetchColumn();
        modulePost('notification/read/' . $id, [], $adminCookie, $case[3]);
        moduleExpect((int) $pdo->query('SELECT COUNT(*) FROM notification_reads WHERE notification_id = ' . (int) $id . ' AND user_id = ' . (int) $admin['id'])->fetchColumn() === 1, 'Notification marked read');
        if ($case[0] === 'user') { modulePost('notification/read/' . $id, [], $userCookie, 'notification'); }
    }
    modulePost('notification/markAllRead', [], $userCookie, 'notification');
    moduleExpect($notifications->getUnreadCount($normal['id']) === 0, 'Mark all read resets unread count');
    foreach (['report/charges?year=2026', 'report/charges?year=2026&status=Belum%20Dibayar', 'report/projects?year=2026', 'report/projects?year=2026&department=Jabatan%20Kejuruteraan&project_status=Dalam%20Proses'] as $path) {
        $r = moduleRequest($path, null, $userCookie);
        moduleExpect($r['status'] === 200 && strpos($r['body'], '<table') !== false, 'Report ' . $path);
    }
    foreach (['charge/edit/99999', 'charge/payment/99999', 'project/edit/99999', 'project/finance/99999'] as $path) {
        moduleExpect(moduleRequest($path, null, $userCookie)['status'] === 302, 'Missing record redirects safely ' . $path);
    }
    require __DIR__ . '/stage1-security.php';
    require __DIR__ . '/stage2a-functional.php';
    require __DIR__ . '/stage2b-dashboard.php';
    require __DIR__ . '/problem2-permissions.php';
    require __DIR__ . '/stage3a-departments.php';
    require __DIR__ . '/problem3b-notifications.php';
    require __DIR__ . '/php825-nullable.php';
    require __DIR__ . '/problem4-http.php';
    require __DIR__ . '/f02-reconciliation.php';
    require __DIR__ . '/f01-dashboard.php';

    modulePost('project/deleteScheduleRecord/' . $schedule['id'], [], $adminCookie, 'project/finance/' . $projectId);
    moduleExpect(!$projects->getScheduleById($schedule['id']), 'Delete pending project schedule');
    modulePost('user/delete/' . $created['id'], [], $adminCookie, 'user_deleted');
    moduleExpect(!$users->getById($created['id']), 'Delete other user');
    modulePost('charge/delete/' . $chargeId, [], $adminCookie, 'charge_deleted');
    moduleExpect(!$charges->getById($chargeId), 'Delete charge');
    modulePost('project/delete/' . $projectId, [], $adminCookie, 'project');
    moduleExpect(!$projects->getById($projectId), 'Delete project');
    modulePost('auth/logout', [], $userCookie, 'auth');
    moduleExpect(strpos(moduleRequest('charge', null, $userCookie)['location'], 'auth') !== false, 'Logout prevents module access');
    $log = file_get_contents($fixture . '/server.log');
    if (preg_match_all('/^.*(?:PHP (?:Warning|Fatal|Notice|Deprecated|Parse)|ErrorException|Uncaught).*$/m', $log, $errors)) { echo implode("\n", array_unique($errors[0])) . "\n"; }
    moduleExpect(!preg_match('/PHP (Warning|Fatal|Notice|Deprecated|Parse)|ErrorException|Uncaught/', $log), 'No PHP errors in HTTP checks');
    echo "Module HTTP checks passed against isolated database.\n";
} finally {
    if (is_resource($server)) { proc_terminate($server); proc_close($server); }
    if (isset($receipt) && is_file($fixture . '/receipts/' . basename($receipt))) { unlink($fixture . '/receipts/' . basename($receipt)); }
    $pdo->exec('DROP DATABASE IF EXISTS `' . $testName . '`');
    foreach (glob($fixture . '/receipts/*') as $file) { unlink($file); }
    if (is_dir($fixture . '/receipts')) { rmdir($fixture . '/receipts'); }
    foreach (glob($fixture . '/*') as $file) { if (is_file($file)) { unlink($file); } }
    rmdir($fixture);
    moduleExpect(moduleClientFingerprint($pdo, $clientSchema) === $clientBefore, 'Client database row fingerprints unchanged');
    moduleExpect(moduleReceiptFingerprint() === $receiptsBefore, 'Client receipt paths and contents unchanged');
}
