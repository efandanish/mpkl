<?php
if (!isset($testName, $pdo) || !preg_match('/\Acajpemajuan_modules_[a-f0-9]+_test\z/', $testName)
    || $pdo->query('SELECT DATABASE()')->fetchColumn() !== $testName) {
    throw new RuntimeException('Problem 4 checks require the disposable database.');
}
$problem4Ids = [];
$base4 = array_replace($chargeData, ['charge_amount' => '150000.00', 'payment_plan' => '3 Ansuran',
    'first_installment_due_date' => '2024-01-31', 'year' => '2026']);
$principalBefore4 = ChargeInstallments::cents((string) $charges->getTotalChargesByYear(2026));
$cashBefore4 = $charges->getTotalCollectionsByYear(2026);
$inserted4 = 0;
foreach (json_decode(file_get_contents(__DIR__ . '/fixtures/problem4-installments.json'), true) as $case4) {
    $input4 = array_replace($base4, ['charge_amount' => $case4['amount'], 'osc_id' => 'AUTO-' . count($problem4Ids)]);
    // First record exercises server-only derivation; later ones include browser preview fields.
    if ($problem4Ids) {
        $input4 += ['installment_no' => [1,2,3], 'installment_amount' => $case4['amounts'],
            'installment_due_date' => ['2024-01-31','2024-04-30','2024-07-30']];
    }
    $input4['interest'] = '999999999.99'; // Browser interest never becomes a payment obligation.
    modulePost('charge/create', $input4, $case4['amount'] === '150000.00' ? $userCookie : $adminCookie, 'charge?success=charge_added');
    $new4 = $pdo->query("SELECT * FROM development_charges WHERE osc_id='" . $input4['osc_id'] . "' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $problem4Ids[] = $new4['id'];
    $rows4 = $charges->getPayments($new4['id']);
    moduleExpect(array_column($rows4, 'amount') === $case4['amounts'] && array_column($rows4, 'due_date') === ['2024-01-31','2024-04-30','2024-07-30'], 'Persisted authoritative principal and deadlines: ' . $case4['amount']);
    moduleExpect(count($rows4) === 3 && array_sum(array_map(['ChargeInstallments','cents'], array_column($rows4,'amount'))) === ChargeInstallments::cents($case4['amount']), 'No interest in obligations; principal sums exactly: ' . $case4['amount']);
    moduleExpect((int)$new4['record_year'] === 2026 && (float)$charges->getTotalPaid($new4['id']) === 0.0, 'Record year retained and no provisional collections');
    moduleExpect((int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE entity_type='development_charge' AND action='created' AND entity_id=" . (int)$new4['id'])->fetchColumn() === 1, 'Atomic creation retains charge notification');
    $paymentPage4 = moduleRequest('charge/payment/' . $new4['id'], null, $adminCookie);
    moduleExpect($paymentPage4['status'] === 200 && strpos($paymentPage4['body'], 'action="' . $moduleBase . 'charge/payment/' . $rows4[0]['id']) !== false,
        'Admin receipt form remains available for automatic principal schedule');
    $inserted4 += ChargeInstallments::cents($case4['amount']);
}
moduleExpect((string)$charges->getTotalChargesByYear(2026) === ChargeInstallments::money($principalBefore4 + $inserted4)
    && $charges->getTotalCollectionsByYear(2026) === $cashBefore4, 'Dashboard assessed principal and collections exclude provisional interest');
$validPreview4 = $base4 + ['installment_no'=>[1,2,3], 'installment_amount'=>['100000','25000','25000'],
    'installment_due_date'=>['2024-01-31','2024-04-30','2024-07-30']];
$state4 = serialize($pdo->query('SELECT * FROM development_charges ORDER BY id')->fetchAll(PDO::FETCH_ASSOC));
foreach ([['charge_amount'=>'99999.99'], ['first_installment_due_date'=>''], ['first_installment_due_date'=>'2023-02-29'],
    ['first_installment_due_date'=>[]], ['first_installment_due_date'=>'9999-07-31'],
    ['installment_amount'=>['100001','24999','25000']], ['installment_due_date'=>['2024-01-31','2024-05-01','2024-07-30']],
    ['installment_no'=>[1,1,3]], ['installment_amount'=>['100000','25000']], ['installment_amount'=>[['nested'],'25000','25000']],
    ['installment_no'=>[1,2,3,4]], ['installment_due_date'=>'bad'], ['installment_amount'=>['1e5','25000','25000']]] as $tamper4) {
    moduleExpect(moduleRequest('charge/create', array_replace($validPreview4,$tamper4),$userCookie)['status'] === 422, 'Reject tampered/invalid automatic schedule: ' . json_encode($tamper4));
}
$missing4 = $base4; unset($missing4['first_installment_due_date']);
moduleExpect(moduleRequest('charge/create',$missing4,$adminCookie)['status']===422, 'Reject absent first deadline without implicit date');
moduleExpect(moduleRequest('charge/create',$base4,$adminCookie,false,false)['status']===403, 'Automatic creation still requires CSRF');
moduleExpect(serialize($pdo->query('SELECT * FROM development_charges ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)) === $state4, 'Rejected automatic submissions leave charges unchanged');
// Existing zero rows can survive metadata edits; no automatic recalculation is applied on edit.
$zero4 = $charges->getPayments($problem4Ids[0]);
$zeroEdit4 = array_replace($base4, ['charge_amount'=>'100000.00', 'payment_schedule_id'=>array_column($zero4,'id'),
    'payment_schedule_amount'=>array_column($zero4,'amount'), 'payment_schedule_due_date'=>array_column($zero4,'due_date')]);
modulePost('charge/update/'.$problem4Ids[0],$zeroEdit4,$adminCookie,'charge?success=charge_updated');
moduleExpect($charges->getPayments($problem4Ids[0]) === $zero4, 'Unchanged zero principal schedules survive admin metadata edit');
// Paid schedules and parent principal cannot be recalculated by admin or ordinary user.
$paidId4=$problem4Ids[1]; $paidRows4=$charges->getPayments($paidId4);
$charges->markPaymentPaid($paidRows4[0]['id'],'2026-10-01',null,'AUTO-RECEIPT');
$beforePaid4=$charges->getPayments($paidId4);
$paidEdit4=array_replace($base4,['payment_schedule_id'=>array_column(array_slice($paidRows4,1),'id'),
    'payment_schedule_amount'=>['25000','25000'], 'payment_schedule_due_date'=>['2024-04-30','2024-07-30']]);
moduleExpect(moduleRequest('charge/update/'.$paidId4,array_replace($paidEdit4,['charge_amount'=>'300000']),$adminCookie)['status']===422, 'Admin cannot recalculate paid charge principal');
modulePost('charge/update/'.$paidId4,$paidEdit4,$userCookie,'unauthorized');
moduleExpect($charges->getPayments($paidId4)===$beforePaid4 && $charges->getById($paidId4)['charge_amount']==='150000.00', 'Paid rows, receipt number and principal unchanged');
moduleExpect($charges->getTotalPaid($paidId4) == 100000, 'Paid total contains principal only');
// Removed equal-third path cannot generate a conflicting legacy schedule.
$empty4=$charges->create($base4 + ['record_year'=>2026]);$problem4Ids[]=$empty4;
moduleExpect(moduleRequest('charge/generatePaymentSchedule/'.$empty4,[],$adminCookie)['status']===422 && !$charges->getPayments($empty4), 'Legacy empty three-installment record is not auto-converted');
modulePost('charge/generatePaymentSchedule/'.$empty4,[],$userCookie,'unauthorized');
// Full payment and four manual rows retain their previous creation rules.
foreach (['Bayaran Penuh','4 Ansuran'] as $plan4) {
    $manual4=array_replace($base4,['payment_plan'=>$plan4,'charge_amount'=>'900.00','osc_id'=>'KEEP-'.$plan4]);
    unset($manual4['first_installment_due_date']);
    if ($plan4==='4 Ansuran') { $manual4 += ['installment_no'=>[1,2,3,4], 'installment_amount'=>['100','200','300','300'], 'installment_due_date'=>['2026-12-01','2026-12-02','2026-12-03','2026-12-04']]; }
    modulePost('charge/create',$manual4,$userCookie,'charge?success=charge_added');
    $s4=$pdo->prepare('SELECT id FROM development_charges WHERE osc_id=? ORDER BY id DESC LIMIT 1');$s4->execute([$manual4['osc_id']]);$id4=$s4->fetchColumn();$problem4Ids[]=$id4;
    $retained4=$charges->getPayments($id4);
    moduleExpect(array_column($retained4,'amount')===($plan4==='4 Ansuran'?['100.00','200.00','300.00','300.00']:['900.00']), 'Existing creation behavior retained: '.$plan4);
}
foreach ($problem4Ids as $id4) { $charges->delete($id4); }
