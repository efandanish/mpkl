<?php
require __DIR__ . '/bootstrap.php';
$failed = false;
$check = function ($ok, $message) use (&$failed) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $message . "\n";
    if (!$ok) { $failed = true; }
};
$check(PHP_VERSION_ID >= 70400 && PHP_VERSION_ID < 80300, 'PHP 7.4 through 8.2 (running ' . PHP_VERSION . ')');
if (in_array('--target-php825', $argv, true)) {
    $check(PHP_VERSION === '8.2.5', 'Exact upgrade target PHP 8.2.5 (running ' . PHP_VERSION . ')');
}
foreach (array('AuthController', 'ErrorsController', 'DashboardController', 'UserController', 'ProjectController', 'ChargeController', 'NotifyController', 'NotificationController', 'ModuleController', 'ReportController') as $controller) {
    $check(class_exists($controller), 'Controller: ' . $controller);
}
foreach (array('pdo', 'pdo_mysql', 'mbstring', 'gd', 'openssl', 'fileinfo') as $extension) {
    $check(extension_loaded($extension), 'PHP extension: ' . $extension);
}
foreach (array('app/logs', 'public/uploads/receipts', 'app/uploads', 'app/backups') as $directory) {
    $check(is_dir(BASE_DIR . '/' . $directory) && is_writable(BASE_DIR . '/' . $directory), 'Writable: ' . $directory);
}
try {
    $db = Database::openConnection();
    foreach (array('users', 'projects', 'project_expenses', 'project_payment_schedules', 'project_variations', 'development_charges', 'development_charge_payments', 'notifications', 'notification_reads') as $table) {
        $db->prepare('SELECT 1 FROM `' . $table . '` LIMIT 1');
        $db->execute();
        $check(true, 'Database table: ' . $table);
    }
    foreach (array('development_charges' => 'record_year', 'development_charge_payments' => 'receipt_number') as $table => $column) {
        // Native MariaDB prepares do not accept a placeholder in SHOW COLUMNS LIKE.
        $db->prepare('SELECT DATA_TYPE, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column');
        $db->bindValue(':table', $table);
        $db->bindValue(':column', $column);
        $db->execute();
        $metadata = $db->fetchAssociative();
        $check((bool) $metadata, 'Stage 2A column: ' . $table . '.' . $column . ' (manual migrations required if missing)');
        if ($metadata) {
            $validType = $column === 'record_year'
                ? $metadata['DATA_TYPE'] === 'smallint' && stripos($metadata['COLUMN_TYPE'], 'unsigned') !== false
                : $metadata['DATA_TYPE'] === 'varchar' && (int) $metadata['CHARACTER_MAXIMUM_LENGTH'] === 100;
            $check($validType && $metadata['IS_NULLABLE'] === 'YES',
                'Stage 2A definition: ' . $table . '.' . $column . ' (' .
                ($column === 'record_year' ? 'SMALLINT UNSIGNED NULL' : 'VARCHAR(100) NULL') . ')');
        }
    }
    $db->prepare('SELECT COLUMN_NAME, SEQ_IN_INDEX, NON_UNIQUE, SUB_PART, INDEX_TYPE
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index');
    $db->bindValue(':table', 'development_charges');
    $db->bindValue(':index', 'idx_charge_record_year');
    $db->execute();
    $index = $db->fetchAllAssociative();
    $check(count($index) === 1 && $index[0]['COLUMN_NAME'] === 'record_year'
        && (int) $index[0]['SEQ_IN_INDEX'] === 1 && (int) $index[0]['NON_UNIQUE'] === 1
        && $index[0]['SUB_PART'] === null && $index[0]['INDEX_TYPE'] === 'BTREE',
        'Stage 2A index: idx_charge_record_year (record_year, non-unique)');

    $db->prepare('SELECT CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = :table AND CONSTRAINT_NAME = :constraint');
    $db->bindValue(':table', 'development_charges');
    $db->bindValue(':constraint', 'chk_charge_record_year');
    $db->execute();
    $constraint = $db->fetchAssociative();
    // Ignore MariaDB formatting of the known migration expression; never write test rows.
    $clause = $constraint ? preg_replace('/[\s`()]+/', '', strtolower($constraint['CHECK_CLAUSE'])) : '';
    $check($clause === 'record_yearisnullorrecord_yearbetween1000and9999',
        'Stage 2A check: chk_charge_record_year (NULL or 1000-9999)');
    $db->prepare("SELECT COUNT(*) AS total FROM users WHERE role = 'admin'");
    $db->execute();
    $result = $db->fetchAssociative();
    $check((int)$result['total'] > 0, 'Administrator exists');
} catch (Throwable $e) {
    $check(false, 'Database: ' . $e->getMessage());
}
exit($failed ? 1 : 0);
