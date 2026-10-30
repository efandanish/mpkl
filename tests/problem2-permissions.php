<?php
/** Included only by modules-smoke.php after its isolated database setup. */
if (!isset($testName, $pdo, $charges, $projects) || !preg_match('/\Acajpemajuan_modules_[a-f0-9]+_test\z/', $testName)
    || $pdo->query('SELECT DATABASE()')->fetchColumn() !== $testName) {
    throw new RuntimeException('Permission checks require the disposable modules-smoke database.');
}

$ordinaryChargeInput = array_replace($chargeData, ['year' => '2013', 'osc_id' => 'PROBLEM2-ORDINARY',
    'developer_name' => 'Ordinary creator', 'charge_amount' => '123.45', 'payment_plan' => 'Bayaran Penuh', 'osc_decision' => 'Lulus']);
$ordinaryProjectInput = array_replace($projectData, ['year' => '2013', 'project_id' => 'PROBLEM2-ORDINARY',
    'start_date' => '2013-01-01', 'end_date' => '', 'sst_value' => '456.78', 'meeting_approval_value' => '400.25',
    'approval_status' => 'Diluluskan', 'schedule_date' => ['2013-06-01'], 'schedule_amount' => ['25.50'], 'schedule_type' => ['Ansuran']]);
foreach (['charge' => $ordinaryChargeInput, 'project' => $ordinaryProjectInput] as $module => $input) {
    foreach ([$adminCookie, $userCookie] as $cookie) {
        $page = moduleRequest($module . '?year=2013', null, $cookie);
        moduleExpect(strpos($page['body'], $module . '/create?year=2013') !== false, 'Both roles see year-preserving create button: ' . $module);
        $page = moduleRequest($module . '/create?year=2013', null, $cookie);
        moduleExpect($page['status'] === 200 && strpos($page['body'], 'name="csrf_token"') !== false,
            'Both roles open protected create form: ' . $module);
    }
    moduleExpect(strpos(moduleRequest($module . '/create?year=2013')['location'], 'auth') !== false,
        'Anonymous create form remains denied: ' . $module);
    moduleExpect(strpos(moduleRequest($module . '/create?year=2013', $input)['location'], 'auth') !== false,
        'Anonymous create POST remains denied: ' . $module);
    foreach ([null, 'wrong', ['array']] as $token) {
        $bad = $input;
        if ($token !== null) { $bad['csrf_token'] = $token; }
        moduleExpect(moduleRequest($module . '/create?year=2013', $bad, $userCookie, false, false)['status'] === 403,
            'Ordinary create rejects missing/invalid CSRF: ' . $module);
    }
    $amountKey = $module === 'charge' ? 'charge_amount' : 'sst_value';
    moduleExpect(moduleRequest($module . '/create?year=2013', array_replace($input, [$amountKey => '-1']), $userCookie)['status'] === 422,
        'Ordinary create retains financial validation: ' . $module);
    moduleExpect(moduleRequest($module . '/create?year=2014', $input, $userCookie)['status'] === 400,
        'Ordinary create rejects conflicting year: ' . $module);
}

$ordinaryChargeId = null;
$ordinaryProjectId = null;
try {
    modulePost('charge/create?year=2013', $ordinaryChargeInput, $userCookie, 'charge?success=charge_added&year=2013');
    $row = $pdo->query("SELECT * FROM development_charges WHERE osc_id='PROBLEM2-ORDINARY'")->fetch(PDO::FETCH_ASSOC);
    $ordinaryChargeId = $row['id'];
    moduleExpect($row['charge_amount'] === '123.45' && (int) $row['record_year'] === 2013 && $row['osc_decision'] === 'Lulus',
        'Ordinary creator saves initial amount, reporting year and existing OSC status');
    $ordinaryPayment = $charges->getPayments($ordinaryChargeId)[0];
    moduleExpect($ordinaryPayment['amount'] === '123.45' && $ordinaryPayment['status'] === 'Menunggu',
        'Ordinary creation saves initial payment schedule without recording receipt');

    modulePost('project/create?year=2013', $ordinaryProjectInput, $userCookie, 'project/finance/');
    $row = $pdo->query("SELECT * FROM projects WHERE project_id='PROBLEM2-ORDINARY'")->fetch(PDO::FETCH_ASSOC);
    $ordinaryProjectId = $row['id'];
    moduleExpect($row['sst_value'] === '456.78' && $row['meeting_approval_value'] === '400.25' && $row['approval_status'] === 'Diluluskan',
        'Ordinary creator saves initial project amounts and existing approval status');
    $ordinarySchedule = $projects->getSchedules($ordinaryProjectId)[0];
    moduleExpect($ordinarySchedule['amount'] === '25.50', 'Ordinary creation saves initial project schedule');
    $projectBeforeDuplicate = $projects->getById($ordinaryProjectId);
    $duplicate = moduleRequest('project/create?year=2013', array_replace($ordinaryProjectInput, ['project_name' => 'Must not overwrite']), $userCookie);
    moduleExpect($duplicate['status'] === 422 && $projects->getById($ordinaryProjectId) === $projectBeforeDuplicate,
        'Ordinary duplicate project ID is rejected without overwriting');

    // Seed paid history only in the disposable database to exercise cancellation denial.
    $charges->markPaymentPaid($ordinaryPayment['id'], '2013-06-01', null, 'PROBLEM2-PAID');
    $projects->addExpense($ordinaryProjectId, $ordinarySchedule['id'], '25.50', '2013-06-01', 'Protected fixture');
    $projects->markSchedulePaid($ordinarySchedule['id']);
    $ordinaryExpense = $projects->getExpenses($ordinaryProjectId)[0];

    $snapshot = function () use ($pdo) {
        $state = [];
        foreach (['development_charges', 'development_charge_payments', 'projects', 'project_payment_schedules', 'project_expenses', 'project_variations', 'users'] as $table) {
            $state[$table] = $pdo->query('SELECT * FROM ' . $table . ' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        }
        return $state;
    };
    $before = $snapshot();
    $restricted = [
        'charge/update/' . $ordinaryChargeId => array_replace($ordinaryChargeInput, ['charge_amount' => '1.00', 'developer_name' => 'Tampered']),
        'project/update/' . $ordinaryProjectId => array_replace($ordinaryProjectInput, ['sst_value' => '1.00', 'meeting_approval_value' => '1.00', 'project_name' => 'Tampered']),
        'charge/delete/' . $ordinaryChargeId => [],
        'charge/payment/' . $ordinaryPayment['id'] => ['payment_date' => '2013-06-01', 'receipt_number' => 'FORGED'],
        'charge/cancelPayment/' . $ordinaryPayment['id'] => [],
        'charge/generatePaymentSchedule/' . $ordinaryChargeId => [],
        'project/delete/' . $ordinaryProjectId => [],
        'project/recordPayment/' . $ordinarySchedule['id'] => ['amount' => '25.50', 'payment_date' => '2013-06-01'],
        'project/cancelPayment/' . $ordinaryExpense['id'] => [],
        'project/addScheduleRecord/' . $ordinaryProjectId => ['amount' => '1.00'],
        'project/deleteScheduleRecord/' . $ordinarySchedule['id'] => [],
        'project/addManualExpense/' . $ordinaryProjectId => ['amount' => '1.00'],
        'project/addVariationRecord/' . $ordinaryProjectId => ['amount' => '1.00'],
        'user/create' => ['name' => 'Forged admin', 'email' => 'forged@example.test', 'password' => 'test-pass', 'role' => 'admin'],
        'user/update/' . $normal['id'] => ['role' => 'admin'],
        'user/delete/' . $admin['id'] => []
    ];
    foreach ($restricted as $path => $input) {
        modulePost($path, $input, $userCookie, 'unauthorized');
    }
    moduleExpect($snapshot() === $before, 'Direct ordinary-user POSTs leave all saved financial and user data unchanged');
    foreach (['user', 'user/create', 'user/edit/' . $admin['id']] as $path) {
        moduleExpect(strpos(moduleRequest($path, null, $userCookie)['location'], 'unauthorized') !== false,
            'User management remains admin-only: ' . $path);
    }
    foreach (['charge/edit/' . $ordinaryChargeId, 'project/edit/' . $ordinaryProjectId, 'project/finance/' . $ordinaryProjectId] as $path) {
        $page = moduleRequest($path . '?year=2013', null, $userCookie);
        moduleExpect($page['status'] === 200 && strpos($page['body'], 'disabled') !== false,
            'Ordinary creator retains read-only saved detail page: ' . $path);
    }
} finally {
    if ($ordinaryChargeId) { $charges->delete($ordinaryChargeId); }
    if ($ordinaryProjectId) { $projects->delete($ordinaryProjectId); }
}
