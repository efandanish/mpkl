<?php
// Included by modules-smoke.php; never run fixtures against the client database.
if (!isset($testName, $pdo) || !preg_match('/\Acajpemajuan_modules_[a-f0-9]+_test\z/', $testName)
    || $pdo->query('SELECT DATABASE()')->fetchColumn() !== $testName) {
    throw new RuntimeException('PHP compatibility fixtures require the disposable database.');
}
$nullableChargeId = $charges->create(array_replace($chargeData, [
    'osc_id' => null, 'lot_number' => null, 'mukim' => null, 'file_number' => null
]));
$nullableProjectId = $projects->create(array_replace($projectData, [
    'project_id' => 'PHP825-NULL', 'description' => null, 'procurement_type' => null,
    'start_date' => null, 'end_date' => null
]));
$projects->addExpense($nullableProjectId, null, '10.00', '2026-10-01', null);
foreach (['charge/edit/' . $nullableChargeId, 'charge', 'report/charges?year=2026',
    'project/edit/' . $nullableProjectId, 'project/finance/' . $nullableProjectId] as $path) {
    $page = moduleRequest($path, null, $adminCookie);
    moduleExpect($page['status'] === 200 && strpos($page['body'], 'System setup required') === false,
        'Nullable database fields render safely: ' . $path);
}
$charges->delete($nullableChargeId);
$projects->delete($nullableProjectId);
