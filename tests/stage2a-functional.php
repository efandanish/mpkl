<?php
if (!isset($testName, $pdo, $fixture) || !preg_match('/\Acajpemajuan_modules_[a-f0-9]+_test\z/', $testName)
    || $pdo->query('SELECT DATABASE()')->fetchColumn() !== $testName) {
    throw new RuntimeException('Run the isolated modules-smoke suite.');
}

function stage2aTableBody($html)
{
    preg_match('/<tbody\b[^>]*>(.*?)<\/tbody>/si', $html, $match);
    return isset($match[1]) ? $match[1] : '';
}

$historical = array_replace($chargeData, ['year' => '2024', 'osc_id' => 'OSC-STAGE2A-HISTORY',
    'developer_name' => 'Pemaju Sejarah 2024', 'payment_plan' => 'Bayaran Penuh']);
$response = moduleRequest('charge/create?year=2024', $historical, $adminCookie);
moduleExpect($response['status'] === 302 && strpos($response['location'], 'year=2024') !== false, 'Historical create retains 2024 redirect');
$saved = $pdo->query("SELECT * FROM development_charges WHERE osc_id='OSC-STAGE2A-HISTORY'")->fetch(PDO::FETCH_ASSOC);
$historyId = $saved['id'];
$initialCreatedEpoch = $pdo->query('SELECT UNIX_TIMESTAMP(created_at) FROM development_charges WHERE id=' . (int) $historyId)->fetchColumn();
moduleExpect((int) $saved['record_year'] === 2024 && (int) substr($saved['created_at'], 0, 4) === (int) date('Y'),
    'New historical record has explicit 2024 year and actual creation timestamp');
moduleExpect(strpos(stage2aTableBody(moduleRequest('charge?year=2024', null, $adminCookie)['body']), 'Pemaju Sejarah 2024') !== false,
    'Historical charge appears in 2024 list');
moduleExpect(strpos(stage2aTableBody(moduleRequest('charge?year=2026', null, $adminCookie)['body']), 'Pemaju Sejarah 2024') === false,
    'Historical charge does not leak into its creation-year list');
moduleExpect(strpos(moduleRequest('report/charges?year=2024', null, $adminCookie)['body'], 'Pemaju Sejarah 2024') !== false,
    'Charge print report uses explicit record year');
moduleExpect(strpos(moduleRequest('report/charges?year=2026', null, $adminCookie)['body'], 'Pemaju Sejarah 2024') === false,
    'Charge print report excludes another record year');
foreach (['dashboard', 'charge', 'charge/create', 'charge/edit/' . $historyId, 'charge/payment/' . $historyId, 'project/create'] as $path) {
    $page = moduleRequest($path . '?year=2024', null, $adminCookie);
    moduleExpect($page['status'] === 200 && strpos($page['body'], 'charge?year=2024') !== false
        && strpos($page['body'], 'dashboard?year=2024') !== false, 'Navigation retains 2024: ' . $path);
}
$page = moduleRequest('charge/create', null, $adminCookie);
moduleExpect(strpos($page['body'], 'charge/create?year=2024') !== false, 'Session fallback retains selected year without parameter');
$historyPayment = $charges->getPayments($historyId)[0];
$historyEdit = $historical + ['payment_schedule_id' => [$historyPayment['id']], 'payment_schedule_amount' => ['900.00'], 'payment_schedule_due_date' => ['']];
moduleRequest('dashboard?year=2025', null, $adminCookie); // another tab changes the session context
$response = moduleRequest('charge/update/' . $historyId . '?year=2024', $historyEdit, $adminCookie);
moduleExpect($response['status'] === 302 && strpos($response['location'], 'year=2024') !== false,
    'Explicit form/link year survives another tab changing the session');
moduleExpect($pdo->query('SELECT UNIX_TIMESTAMP(created_at) FROM development_charges WHERE id=' . (int) $historyId)->fetchColumn() === $initialCreatedEpoch && (int) $charges->getById($historyId)['record_year'] === 2024,
    'Edit preserves creation timestamp and effective record year');
$invalidEdit = array_replace($historyEdit, ['file_number' => '']);
$response = moduleRequest('charge/update/' . $historyId . '?year=2024', $invalidEdit, $adminCookie);
moduleExpect($response['status'] === 422 && strpos($response['body'], 'charge/update/' . $historyId . '?year=2024') !== false,
    'Failed edit retains year on retry form');

$paymentUrl = 'charge/payment/' . $historyPayment['id'] . '?year=2024';
$paymentInput = ['year' => '2024', 'payment_date' => '2025-02-01', 'receipt_number' => '  RESIT-2024-001  '];
foreach (['', '   ', ['array'], str_repeat('x', 101)] as $badNumber) {
    $r = moduleRequest($paymentUrl, array_replace($paymentInput, ['receipt_number' => $badNumber]), $adminCookie);
    moduleExpect($r['status'] === 422 && strpos($r['body'], 'No. Resit wajib diisi') !== false, 'Invalid receipt number rejected before upload');
}
$r = moduleRequest($paymentUrl, array_diff_key($paymentInput, ['receipt_number' => true]), $adminCookie, true);
moduleExpect($r['status'] === 422, 'Missing receipt number rejected');
$r = moduleRequest($paymentUrl, $paymentInput, $adminCookie);
moduleExpect($r['status'] === 422 && strpos($r['body'], 'Fail resit wajib') !== false, 'Receipt file still required server-side');
$r = moduleRequest($paymentUrl, $paymentInput, $adminCookie, true);
moduleExpect($r['status'] === 302 && strpos($r['location'], 'year=2024') !== false, 'Payment with receipt number saves and retains selected year');
$posted = $charges->getPaymentById($historyPayment['id']);
moduleExpect($posted['receipt_number'] === 'RESIT-2024-001' && $posted['payment_date'] === '2025-02-01',
    'Trimmed receipt reference stored with cash date distinct from record year');
$r = moduleRequest('charge/payment/' . $historyId . '?year=2024', null, $adminCookie);
moduleExpect(strpos($r['body'], 'RESIT-2024-001') !== false && strpos($r['body'], 'Tarikh Bayaran / Tarikh Resit') !== false,
    'Paid installment displays saved receipt number and requested date wording');
$r = moduleRequest($paymentUrl, $paymentInput, $adminCookie, true);
moduleExpect($r['status'] === 422 && $charges->getPaymentById($historyPayment['id']) === $posted,
    'Duplicate receipt posting cannot overwrite paid metadata');
moduleExpect(strpos(moduleRequest('charge/payment/' . $historyId)['location'], 'auth') !== false, 'Anonymous receipt-number page denied');

// Migration leaves uncertain years and receipt references NULL, not guessed from identifiers.
$legacyData = array_replace($historical, ['osc_id' => 'LEGACY-UNCERTAIN', 'developer_name' => 'Legacy Stage 2A']);
$legacyId = $charges->create($legacyData); // direct legacy fixture intentionally has no record_year key
$charges->addPaymentSchedule($legacyId, 1, '900', null);
$legacyPayment = $charges->getPayments($legacyId)[0];
$charges->markPaymentPaid($legacyPayment['id'], '2024-04-01');
$legacyPage = moduleRequest('charge/payment/' . $legacyId . '?year=2024', null, $adminCookie);
moduleExpect($legacyPage['status'] === 200 && strpos($legacyPage['body'], 'Belum direkodkan (rekod lama)') !== false,
    'Existing paid installment without receipt number remains readable');
moduleExpect($charges->getById($legacyId)['record_year'] === null, 'Legacy uncertain year is not assigned automatically');
moduleExpect(strpos(moduleRequest('charge?year=' . date('Y'), null, $adminCookie)['body'], 'Tahun belum disahkan') !== false,
    'Legacy creation-year fallback is explicitly labelled provisional');

foreach (['bad', '2024oops', '999', '10000', 'year%5B%5D=2024'] as $invalidYear) {
    $query = strpos($invalidYear, '=') === false ? 'year=' . $invalidYear : $invalidYear;
    moduleExpect(moduleRequest('charge?' . $query, null, $adminCookie)['status'] === 400, 'Malformed year rejected');
}
$conflicting = array_replace($historical, ['year' => '2025']);
moduleExpect(moduleRequest('charge/create?year=2024', $conflicting, $adminCookie)['status'] === 400, 'Conflicting form/query years rejected');

$sixProjects = [];
for ($i = 1; $i <= 6; $i++) {
    $input = array_replace($projectData, ['year' => '2024', 'project_id' => 'STAGE2A-PROJECT-' . $i,
        'project_name' => 'Projek Sejarah ' . $i, 'start_date' => $i === 6 ? '' : '2024-01-01', 'end_date' => '']);
    $r = moduleRequest('project/create?year=2024', $input, $adminCookie);
    moduleExpect($r['status'] === 302 && strpos($r['location'], 'project/finance/') !== false && strpos($r['location'], 'year=2024') !== false,
        'Unique project ' . $i . ' created through HTTP with preserved year');
    $sixProjects[] = $input;
}
moduleExpect((int) $pdo->query("SELECT COUNT(*) FROM projects WHERE project_id LIKE 'STAGE2A-PROJECT-%'")->fetchColumn() === 6,
    'All six unique HTTP projects persisted');
$undated = $pdo->query("SELECT * FROM projects WHERE project_id='STAGE2A-PROJECT-6'")->fetch(PDO::FETCH_ASSOC);
moduleExpect($undated['start_date'] === null && $undated['end_date'] === null, 'Empty optional project dates persist as NULL');
$r = moduleRequest('project?year=2024', null, $adminCookie);
moduleExpect(strpos($r['body'], 'Projek Sejarah 1') !== false && strpos($r['body'], 'Projek Sejarah 6') !== false
    && strpos($r['body'], 'Projek Belum Bertarikh') !== false, 'Year-specific and undated projects both remain discoverable');
$r = moduleRequest('report/projects?year=2024', null, $adminCookie);
moduleExpect(strpos($r['body'], 'Projek Sejarah 1') !== false && strpos($r['body'], 'Projek Sejarah 6') === false,
    'Project annual report keeps start-year semantics without assigning undated records');
$original = $pdo->query("SELECT * FROM projects WHERE project_id='STAGE2A-PROJECT-1'")->fetch(PDO::FETCH_ASSOC);
$duplicate = array_replace($sixProjects[0], ['project_name' => 'Must not overwrite', 'description' => 'Kekalkan catatan',
    'schedule_date' => ['2024-04-01'], 'schedule_amount' => ['50'], 'schedule_type' => ['Ansuran']]);
$r = moduleRequest('project/create?year=2024', $duplicate, $adminCookie);
moduleExpect($r['status'] === 422 && strpos($r['body'], 'ID Projek telah digunakan') !== false, 'Duplicate project ID has actionable Malay error');
moduleExpect(strpos($r['body'], 'Must not overwrite') !== false && strpos($r['body'], 'Kekalkan catatan') !== false
    && strpos($r['body'], 'value="2024-04-01"') !== false && strpos($r['body'], 'project/create?year=2024') !== false,
    'Duplicate error preserves text, schedule and selected year');
moduleExpect($pdo->query("SELECT * FROM projects WHERE project_id='STAGE2A-PROJECT-1'")->fetch(PDO::FETCH_ASSOC) === $original,
    'Duplicate submission never overwrites the original project');

// Force an insertion failure after the parent insert; only the isolated schema has this trigger.
$pdo->exec("CREATE TRIGGER stage2a_fail_schedule BEFORE INSERT ON project_payment_schedules FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Intentional isolated failure'");
try {
    $broken = array_replace($duplicate, ['project_id' => 'STAGE2A-ROLLBACK']);
    $r = moduleRequest('project/create?year=2024', $broken, $adminCookie);
    moduleExpect($r['status'] === 500 && strpos($r['body'], 'Tiada perubahan disimpan') !== false
        && strpos($r['body'], 'STAGE2A-ROLLBACK') !== false, 'Insertion error is actionable and preserves submitted input');
    moduleExpect((int) $pdo->query("SELECT COUNT(*) FROM projects WHERE project_id='STAGE2A-ROLLBACK'")->fetchColumn() === 0,
        'Schedule insertion failure rolls back parent project');
} finally { $pdo->exec('DROP TRIGGER stage2a_fail_schedule'); }
echo "Stage 2A receipt, year, migration and creation checks passed.\n";
