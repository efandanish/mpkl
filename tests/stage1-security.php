<?php
/** Included by modules-smoke.php only, after its disposable database/server setup. */
if (!isset($testName, $pdo, $fixture) || !preg_match('/\Acajpemajuan_modules_[a-f0-9]+_test\z/', $testName)
    || $pdo->query('SELECT DATABASE()')->fetchColumn() !== $testName) {
    throw new RuntimeException('Run tests/modules-smoke.php; security checks require its isolated database.');
}

$notificationCount = (int) $pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn();
$readCount = (int) $pdo->query('SELECT COUNT(*) FROM notification_reads')->fetchColumn();
foreach (['notification', 'notify'] as $route) {
    modulePost($route . '/clearAll', ['user_id' => $admin['id']], $userCookie, 'unauthorized');
}
moduleExpect((int) $pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn() === $notificationCount
    && (int) $pdo->query('SELECT COUNT(*) FROM notification_reads')->fetchColumn() === $readCount,
    'Ordinary user cannot clear or dismiss another user history through either URL');
moduleExpect(strpos(moduleRequest('notification', null, $userCookie)['body'], 'notification/clearAll') === false,
    'Ordinary user has no global clear button');
moduleExpect(strpos(moduleRequest('notification', null, $adminCookie)['body'], 'notification/clearAll') !== false,
    'Admin clear action remains available');

foreach ([null, 'wrong', ['unexpected']] as $invalidToken) {
    $data = $chargeData;
    if ($invalidToken !== null) { $data['csrf_token'] = $invalidToken; }
    moduleExpect(moduleRequest('charge/create', $data, $adminCookie, false, false)['status'] === 403,
        'Missing, invalid or array CSRF token rejected');
}
foreach (['charge/create', 'charge/update/' . $chargeId, 'charge/delete/' . $chargeId,
    'charge/payment/' . $payment['id'], 'charge/cancelPayment/' . $payment['id'], 'charge/generatePaymentSchedule/' . $chargeId,
    'project/create', 'project/update/' . $projectId, 'project/delete/' . $projectId,
    'project/recordPayment/' . $schedule['id'], 'project/cancelPayment/99999', 'project/addScheduleRecord/' . $projectId,
    'project/addManualExpense/' . $projectId, 'project/addVariationRecord/' . $projectId, 'project/deleteScheduleRecord/' . $schedule['id'],
    'user/create', 'user/update/' . $normal['id'], 'user/delete/' . $normal['id'],
    'notification/clearAll', 'notify/clearAll', 'notification/read/1', 'notification/markAllRead', 'auth/logout', 'auth/login'] as $path) {
    moduleExpect(moduleRequest($path, [], $adminCookie, false, false)['status'] === 403, 'Missing CSRF blocked before mutation: ' . $path);
}
foreach (['charge', 'charge/create', 'charge/edit/' . $chargeId, 'charge/payment/' . $chargeId, 'project', 'project/create',
    'project/edit/' . $projectId, 'project/finance/' . $projectId, 'user', 'user/create', 'user/edit/' . $normal['id'], 'notification'] as $path) {
    $page = moduleRequest($path, null, $adminCookie);
    preg_match_all('/<form\b[^>]*method="POST"[^>]*>.*?<\/form>/si', $page['body'], $forms);
    moduleExpect(count($forms[0]) > 0, 'POST forms rendered: ' . $path);
    foreach ($forms[0] as $form) {
        moduleExpect(strpos($form, 'name="csrf_token"') !== false, 'Rendered POST form contains token');
    }
}
$beforeCharges = (int) $pdo->query('SELECT COUNT(*) FROM development_charges')->fetchColumn();
$validCharge = array_replace($chargeData, ['payment_plan' => 'Bayaran Penuh']);
foreach (['developer_name', 'project_name', 'osc_id', 'lot_number', 'mukim', 'file_number', 'charge_amount', 'osc_decision', 'payment_plan'] as $field) {
    $bad = $validCharge;
    unset($bad[$field]);
    moduleExpect(moduleRequest('charge/create', $bad, $adminCookie)['status'] === 422, 'Reject missing required ' . $field);
}
foreach (['-1', '0', 'abc', '1e4', '100.001', '99999999999.99', ['array']] as $amount) {
    moduleExpect(moduleRequest('charge/create', array_replace($validCharge, ['charge_amount' => $amount]), $adminCookie)['status'] === 422,
        'Reject invalid/nonpositive amount');
}
moduleExpect(moduleRequest('charge/create', array_replace($validCharge, ['developer_name' => '   ']), $adminCookie)['status'] === 422,
    'Reject whitespace-only text');
moduleExpect(moduleRequest('charge/create', array_replace($validCharge, ['osc_decision' => 'Unknown']), $adminCookie)['status'] === 422,
    'Reject invalid enum');
$manual = $chargeData + ['installment_no' => [1, 2, 3], 'installment_amount' => ['300', '300', '300'],
    'installment_due_date' => ['2026-12-01', '2026-12-02', '2026-12-03']];
foreach ([['installment_no' => '1'], ['installment_amount' => [300]], ['installment_due_date' => ['2026-02-30', '2026-12-02', '2026-12-03']],
    ['installment_no' => [1, 1, 3]], ['installment_amount' => [300, -1, 300]], ['installment_due_date' => [[], '', '']]] as $override) {
    moduleExpect(moduleRequest('charge/create', array_replace($manual, $override), $adminCookie)['status'] === 422,
        'Reject malformed, incomplete or invalid manual schedule');
}
$errorPage = moduleRequest('charge/create', array_replace($manual, ['osc_id' => '']), $adminCookie);
moduleExpect(strpos($errorPage['body'], 'Module Developer') !== false && strpos($errorPage['body'], 'data-old-input=') !== false,
    'Charge error response preserves text and installment input');
moduleExpect((int) $pdo->query('SELECT COUNT(*) FROM development_charges')->fetchColumn() === $beforeCharges,
    'Rejected submissions did not insert any charges');

// Editing is also validated before any parent/schedule write.
$currentRows = $charges->getPayments($chargeId);
$pendingRows = array_values(array_filter($currentRows, function ($row) { return $row['status'] === 'Menunggu'; }));
$edit = array_replace($chargeData, ['payment_schedule_id' => array_column($pendingRows, 'id'),
    'payment_schedule_amount' => array_column($pendingRows, 'amount'),
    'payment_schedule_due_date' => array_column($pendingRows, 'due_date')]);
$originalCharge = $charges->getById($chargeId);
moduleExpect(moduleRequest('charge/update/' . $chargeId, array_replace($edit, ['file_number' => ' ']), $adminCookie)['status'] === 422,
    'Edit rejects incomplete required field');
moduleExpect(moduleRequest('charge/update/' . $chargeId, array_replace($edit, ['payment_schedule_id' => [999999]]), $adminCookie)['status'] === 422,
    'Edit rejects schedule IDs belonging elsewhere or missing entries');
modulePost('charge/update/' . $chargeId, array_replace($edit, ['charge_amount' => '1']), $userCookie, 'unauthorized');
moduleExpect($charges->getById($chargeId) === $originalCharge, 'Rejected edits leave parent unchanged');

// A request cannot select another user's read-state target via submitted user_id.
$newNotice = ['category' => 'system', 'action' => 'stage1', 'title' => 'Stage 1 fixture', 'message' => 'Fixture'];
$notifications->create($newNotice);
$noticeId = $pdo->query('SELECT MAX(id) FROM notifications')->fetchColumn();
modulePost('notification/read/' . $noticeId, ['user_id' => $admin['id']], $userCookie, 'notification');
moduleExpect((int) $pdo->query('SELECT COUNT(*) FROM notification_reads WHERE notification_id=' . (int) $noticeId . ' AND user_id=' . (int) $admin['id'])->fetchColumn() === 0,
    'Read action scopes identity to current session');
moduleExpect(moduleRequest('notification/markAllRead', [], $userCookie, false, false)['status'] === 403,
    'Read-state POST needs CSRF token');
modulePost('notification/markAllRead', [], $userCookie, 'notification');

// Current database role wins over a stale admin cookie.
$users->create(['name' => 'Revoked Admin', 'email' => 'revoked@example.test', 'password' => 'module-pass', 'role' => 'admin']);
$revoked = $users->getByEmail('revoked@example.test');
$revokedCookie = moduleRequest('auth/login', ['email' => $revoked['email'], 'password' => 'module-pass'])['cookie'];
$pdo->exec("UPDATE users SET role='user' WHERE id=" . (int) $revoked['id']);
modulePost('charge/delete/' . $chargeId, [], $revokedCookie, 'unauthorized');
$users->deleteUser($revoked['id']);
moduleExpect(strpos(moduleRequest('project', null, $revokedCookie)['location'], 'auth') !== false,
    'Deleted user loses protected module access');

// Exercise the real transaction boundary with a failing operation, without a client-data write.
$atomic = new ReflectionMethod('ModuleController', 'atomic');
$atomic->setAccessible(true);
$beforeSchedules = count($charges->getPayments($chargeId));
try {
    $atomic->invoke(new ChargeController(), function () use ($charges, $chargeId) {
        $charges->addPaymentSchedule($chargeId, 99, '10.00', '2026-12-01');
        throw new DomainException('Intentional isolated rollback test');
    });
    throw new RuntimeException('Expected rollback did not occur');
} catch (DomainException $expected) {
    moduleExpect(count($charges->getPayments($chargeId)) === $beforeSchedules, 'Transaction rolls back multi-step write on exception');
}

// Project paid schedule replacement must fail atomically; normal detail edit remains allowed.
modulePost('project/recordPayment/' . $schedule['id'], ['amount' => '200', 'payment_date' => '2026-10-11'], $adminCookie, 'project/finance/' . $projectId);
$projectBefore = $projects->getById($projectId);
$schedulesBefore = $projects->getSchedules($projectId);
$badProject = array_replace($projectData, ['project_name' => 'Must roll back', 'schedule_date' => ['2026-12-01'],
    'schedule_amount' => ['999'], 'schedule_type' => ['Ansuran']]);
moduleExpect(moduleRequest('project/update/' . $projectId, $badProject, $adminCookie)['status'] === 422,
    'Reject replacement of project schedules with paid history');
moduleExpect($projects->getById($projectId) === $projectBefore && $projects->getSchedules($projectId) === $schedulesBefore,
    'Paid project schedule rejection rolls back main edit too');
modulePost('project/update/' . $projectId, array_replace($projectData, ['project_name' => 'Updated Project']), $adminCookie, 'project/finance/' . $projectId);
$testExpense = $projects->getExpenses($projectId)[0];
modulePost('project/cancelPayment/' . $testExpense['id'], [], $adminCookie, 'project/finance/' . $projectId);

moduleExpect(moduleRequest('project/recordPayment/' . $schedule['id'], ['amount' => '200', 'payment_date' => '2026-02-30'], $adminCookie)['location'] !== '',
    'Invalid project payment date rejected');
moduleExpect(moduleRequest('charge/payment/' . $payment['id'], ['payment_date' => '2026-10-11', 'receipt_number' => 'FILE-REQUIRED'], $adminCookie)['status'] === 422,
    'Receipt upload is required on server');
// Existing full-payment/legacy NULL deadlines remain untouched on unrelated edits.
$fullId = $charges->create($validCharge);
$charges->addPaymentSchedule($fullId, 1, '900.00', null);
$fullPayment = $charges->getPayments($fullId)[0];
$fullEdit = $validCharge + ['payment_schedule_id' => [$fullPayment['id']], 'payment_schedule_amount' => ['900.00'], 'payment_schedule_due_date' => ['']];
modulePost('charge/update/' . $fullId, $fullEdit, $adminCookie, 'charge?success=charge_updated');
moduleExpect($charges->getPayments($fullId)[0]['due_date'] === null, 'Legacy NULL deadline preserved without inventing a date');
// Rollback is also tested by the actual paid-project update rejection above.

// Admin global clear fallback remains available, tested only against isolated notification fixtures.
modulePost('notification/clearAll', [], $adminCookie, 'notification?success=notifications_cleared');
moduleExpect($notifications->getUnreadCount($normal['id']) === 0 && (int) $pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn() === 0,
    'Admin-only global clear works in disposable database');
echo "Stage 1 security and validation checks passed.\n";
