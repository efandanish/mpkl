<?php
// Called before any application model connects, only inside the disposable runner database.
if (!isset($testName, $pdo) || !preg_match('/\Acajpemajuan_modules_[a-f0-9]+_test\z/', $testName)
    || $pdo->query('SELECT DATABASE()')->fetchColumn() !== $testName) {
    throw new RuntimeException('Migration checks require the disposable modules-smoke database.');
}
$migrationRoot = BASE_DIR . '/database/migrations/';
// Reconstruct the pre-migration schema using the down scripts on this empty test database.
foreach (['20261023_002_charge_record_year.down.sql', '20261023_001_receipt_number.down.sql'] as $file) {
    $pdo->exec(file_get_contents($migrationRoot . $file));
}
$pdo->exec("INSERT INTO development_charges (developer_name,project_name,charge_amount,created_at) VALUES ('Legacy fixture','Legacy fixture',100,'2024-04-01 10:00:00')");
$legacyId = $pdo->lastInsertId();
$pdo->exec('INSERT INTO development_charge_payments (development_charge_id,amount,status,payment_date) VALUES (' . (int) $legacyId . ",100,'Dibayar','2025-05-01')");
$legacyCharge = $pdo->query('SELECT * FROM development_charges')->fetch(PDO::FETCH_ASSOC);
$legacyPayment = $pdo->query('SELECT * FROM development_charge_payments')->fetch(PDO::FETCH_ASSOC);
foreach (['20261023_001_receipt_number.up.sql', '20261023_002_charge_record_year.up.sql'] as $file) {
    $pdo->exec(file_get_contents($migrationRoot . $file));
}
$afterCharge = $pdo->query('SELECT * FROM development_charges')->fetch(PDO::FETCH_ASSOC);
$afterPayment = $pdo->query('SELECT * FROM development_charge_payments')->fetch(PDO::FETCH_ASSOC);
moduleExpect(array_key_exists('record_year', $afterCharge) && $afterCharge['record_year'] === null,
    'Migration leaves legacy record year NULL for review');
moduleExpect(array_key_exists('receipt_number', $afterPayment) && $afterPayment['receipt_number'] === null,
    'Migration leaves legacy receipt number NULL');
unset($afterCharge['record_year'], $afterPayment['receipt_number']);
moduleExpect($legacyCharge === $afterCharge && $legacyPayment === $afterPayment,
    'Additive migrations preserve all existing payment, amount, date and timestamp values');
// Reversal removes only new fields/index/check and leaves the old financial data intact.
foreach (['20261023_002_charge_record_year.down.sql', '20261023_001_receipt_number.down.sql'] as $file) {
    $pdo->exec(file_get_contents($migrationRoot . $file));
}
moduleExpect($pdo->query('SELECT * FROM development_charges')->fetch(PDO::FETCH_ASSOC) === $legacyCharge
    && $pdo->query('SELECT * FROM development_charge_payments')->fetch(PDO::FETCH_ASSOC) === $legacyPayment,
    'Down migration retains legacy financial rows');
foreach (['20261023_001_receipt_number.up.sql', '20261023_002_charge_record_year.up.sql'] as $file) {
    $pdo->exec(file_get_contents($migrationRoot . $file));
}
$pdo->exec('DELETE FROM development_charges WHERE id=' . (int) $legacyId);
