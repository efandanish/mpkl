<?php
require dirname(__DIR__) . '/scripts/bootstrap.php';
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) { return false; }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
function calculationExpect($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
    echo 'PASS ' . $message . "\n";
}
foreach (json_decode(file_get_contents(__DIR__ . '/fixtures/problem4-installments.json'), true) as $case) {
    $result = ChargeInstallments::calculate($case['amount'], '2024-01-31');
    calculationExpect($result['amounts'] === $case['amounts'] && $result['interest'] === $case['interest'], 'Principal and reference interest: ' . $case['amount']);
    calculationExpect(array_sum(array_map(['ChargeInstallments', 'cents'], $result['amounts'])) === ChargeInstallments::cents($case['amount']), 'Exact principal conservation: ' . $case['amount']);
}
foreach ([['2024-01-31','2024-04-30','2024-07-30'], ['2023-08-31','2023-11-30','2024-02-29'],
    ['2022-08-31','2022-11-30','2023-02-28'], ['2024-02-29','2024-05-29','2024-08-29'],
    ['2023-11-30','2024-02-29','2024-05-29'], ['2099-11-30','2100-02-28','2100-05-28']] as $dates) {
    calculationExpect(array_column(ChargeInstallments::calculate('150000', $dates[0])['rows'], 2) === $dates, 'Calendar clamp: ' . $dates[0]);
}
foreach (['99999.99','0','-1','1e6','150000.001','10000000000',[],null] as $amount) {
    try { ChargeInstallments::calculate($amount, '2024-01-31'); throw new RuntimeException('Accepted invalid amount'); }
    catch (InvalidArgumentException $error) { calculationExpect(true, 'Reject invalid/below-threshold amount ' . json_encode($amount)); }
}
foreach (['',null,[], '2023-02-29','2024-02-30','2024-1-31','0000-01-01','9999-07-31'] as $date) {
    try { ChargeInstallments::calculate('150000', $date); throw new RuntimeException('Accepted invalid deadline'); }
    catch (InvalidArgumentException $error) { calculationExpect(true, 'Reject missing/invalid/out-of-range deadline ' . json_encode($date)); }
}
restore_error_handler();
