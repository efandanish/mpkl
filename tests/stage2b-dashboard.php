<?php
/** Included only by the isolated HTTP runner. Never connects to client data. */
if (!isset($testName, $pdo, $charges) || !preg_match('/\Acajpemajuan_modules_[a-f0-9]+_test\z/', $testName)
    || $pdo->query('SELECT DATABASE()')->fetchColumn() !== $testName) {
    throw new RuntimeException('Dashboard checks require the disposable modules-smoke database.');
}

$auditYear = 2011; // Separate from all Stage 1/2A fixtures.
$fixtureIds = [];
$newCharge = function ($amount, $year) use ($pdo, &$fixtureIds) {
    $statement = $pdo->prepare('INSERT INTO development_charges (developer_name, project_name, charge_amount, record_year, created_at) VALUES (?, ?, ?, ?, ?)');
    $statement->execute(['Dashboard fixture', 'Dashboard fixture', $amount, $year, '2011-06-01 12:00:00']);
    $fixtureIds[] = $pdo->lastInsertId();
    return end($fixtureIds);
};
$newPayment = function ($id, $amount, $date, $status = 'Dibayar') use ($pdo) {
    $statement = $pdo->prepare('INSERT INTO development_charge_payments (development_charge_id, amount, payment_date, status) VALUES (?, ?, ?, ?)');
    $statement->execute([$id, $amount, $date, $status]);
    return $pdo->lastInsertId();
};
$checkCollections = function ($expected, $message) use ($charges, $auditYear) {
    moduleExpect($charges->getTotalCollectionsByYear($auditYear) === $expected, $message);
};
try {
    $unpaid = $newCharge('100.10', $auditYear);
    $newPayment($unpaid, '100.10', null, 'Menunggu');
    $checkCollections('0.00', 'Unpaid assessment contributes no collections');
    $partial = $newCharge('200.20', $auditYear);
    $newPayment($partial, '50.05', '2011-06-01');
    $newPayment($partial, '150.15', null, 'Menunggu');
    $checkCollections('50.05', 'Partial payment counts only received installment');
    $full = $newCharge('300.30', $auditYear);
    $newPayment($full, '100.10', '2011-07-01');
    $newPayment($full, '200.20', '2011-08-01');
    $checkCollections('350.35', 'Fully paid installments are each counted once with exact cents');
    $cancelled = $newPayment($full, '7.07', '2011-08-02');
    $checkCollections('357.42', 'Posted receipt contributes before cancellation');
    $charges->cancelPayment($cancelled);
    $checkCollections('350.35', 'Cancelled receipt is excluded');
    $newPayment($full, '8.08', '2011-08-03', 'Menunggu');
    $newPayment($full, '9.09', null);
    $checkCollections('350.35', 'Pending rows with dates and paid rows without dates are excluded');
    $older = $newCharge('400.40', 2010);
    $newPayment($older, '40.04', '2011-09-01');
    $checkCollections('390.39', 'Receipt from earlier charge cohort belongs to payment year');
    $newPayment($older, '1.01', '2010-12-31');
    $newPayment($older, '2.02', '2011-01-01');
    $newPayment($older, '3.03', '2011-12-31');
    $newPayment($older, '4.04', '2012-01-01');
    $checkCollections('395.44', 'Calendar boundaries include January 1 and December 31 only');
    moduleExpect($charges->getTotalCollectionsByYear(2010) === '1.01'
        && $charges->getTotalCollectionsByYear(2012) === '4.04'
        && $charges->getTotalCollectionsByYear(2009) === '0.00', 'Adjacent years and empty year have independent totals');
    $legacy = $newCharge('5.05', null);
    $newPayment($legacy, '0.06', '2011-10-01');
    $checkCollections('395.50', 'Legacy registration uncertainty does not change dated receipts');
    moduleExpect($charges->getTotalChargesByYear($auditYear) === '605.65'
        && $charges->getProvisionalChargeCountByYear($auditYear) === 1, 'Assessed total includes unpaid charges and provisional creation-year fallback');
    foreach ([$adminCookie, $userCookie] as $cookie) {
        $page = moduleRequest('dashboard?year=2011', null, $cookie);
        moduleExpect($page['status'] === 200 && moduleDashboardCards($page['body']) === ['Baki Bawa Hadapan'=>'Belum Disahkan','Jumlah Caj Pemajuan'=>'RM 605.65','Jumlah Kutipan'=>'RM 395.50','Jumlah Perbelanjaan'=>'RM 0.00','Baki Akhir Semasa'=>'Belum Tersedia'],
            'Dashboard renders correct assessed/received/expense values and explicitly unavailable balances');
        moduleExpect(strpos($page['body'], 'Jumlah Caj Pemajuan') !== false
            && strpos($page['body'], 'Jumlah Kutipan') !== false
            && strpos($page['body'], 'Sementara: termasuk 1 rekod lama') !== false,
            'Both dashboard roles see separate labels and provisional year warning');
        $page = moduleRequest('dashboard?year=2009', null, $cookie);
        moduleExpect(strpos($page['body'], 'Sementara:') === false, 'Confirmed/empty cohort has no provisional warning');
    }
    $checkCollections('395.50', 'Repeated dashboard reads never count receipts twice');
} finally {
    foreach ($fixtureIds as $fixtureId) {
        $statement = $pdo->prepare('DELETE FROM development_charges WHERE id = ?');
        $statement->execute([$fixtureId]);
    }
}
