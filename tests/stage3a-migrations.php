<?php
if (!isset($testName, $pdo) || !preg_match('/\Acajpemajuan_modules_[a-f0-9]+_test\z/', $testName)
    || $pdo->query('SELECT DATABASE()')->fetchColumn() !== $testName) {
    throw new RuntimeException('Department migration checks require the disposable database.');
}
$migrationRoot = BASE_DIR . '/database/migrations/20261027_003_user_department';
$pdo->exec(file_get_contents($migrationRoot . '.down.sql'));
$statement = $pdo->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
$statement->execute(['Legacy department fixture', 'legacy-migration@example.test', password_hash('legacy-pass', PASSWORD_DEFAULT), 'user']);
$migrationUserId = $pdo->lastInsertId();
$beforeMigration = $pdo->query('SELECT * FROM users WHERE id=' . (int) $migrationUserId)->fetch(PDO::FETCH_ASSOC);
$pdo->exec(file_get_contents($migrationRoot . '.up.sql'));
$afterMigration = $pdo->query('SELECT * FROM users WHERE id=' . (int) $migrationUserId)->fetch(PDO::FETCH_ASSOC);
moduleExpect(array_key_exists('department', $afterMigration) && $afterMigration['department'] === null,
    'User department migration leaves legacy assignment NULL');
unset($afterMigration['department']);
moduleExpect($afterMigration === $beforeMigration, 'Department migration preserves legacy ID, password, role and timestamps');
$pdo->exec(file_get_contents($migrationRoot . '.down.sql'));
moduleExpect($pdo->query('SELECT * FROM users WHERE id=' . (int) $migrationUserId)->fetch(PDO::FETCH_ASSOC) === $beforeMigration,
    'Department rollback retains all original user fields');
$pdo->exec(file_get_contents($migrationRoot . '.up.sql'));
$pdo->exec('DELETE FROM users WHERE id=' . (int) $migrationUserId);
