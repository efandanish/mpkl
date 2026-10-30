<?php
require dirname(__DIR__) . '/scripts/bootstrap.php';
// Use a separate test database; transaction rollback does not reset AUTO_INCREMENT.
if (substr(Config::get('DB_NAME'), -5) !== '_test') {
    fwrite(STDERR, "Configure a separate database whose name ends in _test.\n");
    exit(1);
}
function expect($ok, $message)
{
    if (!$ok) { throw new RuntimeException($message); }
    echo 'PASS ' . $message . "\n";
}
$db = Database::openConnection();
$db->beginTransaction();
try {
    $token = bin2hex(random_bytes(6));
    $users = new User();
    $email = $token . '@example.com';
    expect($users->create(array('email' => $email, 'name' => 'Smoke User', 'password' => $token, 'role' => 'user')), 'Create user');
    $user = $users->getByEmail($email);
    expect(password_verify($token, $user['password']), 'Stored password verifies');

    $charges = new DevelopmentCharge();
    $chargeId = $charges->create(array('developer_name' => 'Smoke Developer', 'project_name' => 'Smoke Charge', 'osc_id' => $token, 'lot_number' => null, 'mukim' => null, 'file_number' => null, 'charge_amount' => '900.00', 'osc_decision' => 'Lulus', 'payment_plan' => '3 Ansuran'));
    expect($chargeId && $charges->getById($chargeId), 'Create and read development charge');
    expect($charges->addPaymentSchedule($chargeId, 1, '300.00', date('Y-m-d')), 'Create charge payment schedule');
    $payment = $charges->getPayments($chargeId)[0];
    expect($charges->markPaymentPaid($payment['id'], date('Y-m-d'), 'uploads/receipts/test.pdf'), 'Record charge payment');
    expect((float)$charges->getTotalPaid($chargeId) === 300.0, 'Charge paid total');
    expect($charges->cancelPayment($payment['id']), 'Cancel charge payment');
    expect((float)$charges->getTotalPaid($chargeId) === 0.0, 'Charge cancellation restores total');

    $projects = new Project();
    $projectId = $projects->create(array('project_id' => $token, 'department' => 'Smoke', 'project_name' => 'Smoke Project', 'description' => '', 'approval_status' => 'Diluluskan', 'meeting_count' => 0, 'procurement_type' => null, 'sst_value' => '1000.00', 'meeting_approval_value' => '1000.00', 'payment_method' => 'One-off', 'start_date' => null, 'end_date' => null, 'project_status' => 'Belum Mula'));
    expect($projectId && $projects->getById($projectId), 'Create and read project');
    expect($projects->addSchedule($projectId, date('Y-m-d'), '100.00', 'One-off'), 'Create project payment schedule');
    $schedule = $projects->getSchedules($projectId)[0];
    expect($projects->addExpense($projectId, $schedule['id'], '100.00', date('Y-m-d'), 'Smoke expense'), 'Record project expense');
    expect($projects->markSchedulePaid($schedule['id']), 'Mark project schedule paid');
    expect((float)$projects->getTotalExpenses($projectId) === 100.0, 'Project expense total');
    $expense = $projects->getExpenses($projectId)[0];
    expect($projects->deleteExpense($expense['id']) && $projects->markSchedulePending($schedule['id']), 'Cancel project payment');
    expect((float)$projects->getTotalExpenses($projectId) === 0.0, 'Project cancellation restores total');
    expect($projects->addVariation($projectId, 'Smoke variation', 'Tambah', '50.00', date('Y-m-d')), 'Create project variation');
    expect((float)$projects->getVariationTotal($projectId) === 50.0, 'Project variation total');

    $notifications = new Notification();
    expect($notifications->create(array('category' => 'system', 'action' => 'test', 'title' => 'Smoke', 'message' => 'Smoke test')), 'Create notification');
    expect($notifications->getUnreadCount($user['id']) > 0, 'Unread notification count');
    expect($notifications->markAllAsRead($user['id']), 'Mark notifications read');
    expect($notifications->getUnreadCount($user['id']) === 0, 'Read notification count');
    echo "Database checks passed; test records rolled back.\n";
} finally {
    $db->rollBack();
}
