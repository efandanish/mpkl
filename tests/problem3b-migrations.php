<?php
if (!isset($testName, $pdo) || !preg_match('/\Acajpemajuan_modules_[a-f0-9]+_test\z/', $testName)
    || $pdo->query('SELECT DATABASE()')->fetchColumn() !== $testName) {
    throw new RuntimeException('Notification migration checks require the disposable database.');
}
$notificationMigration = BASE_DIR . '/database/migrations/20261028_004_notification_audience';
$pdo->exec(file_get_contents($notificationMigration . '.down.sql'));
$pdo->exec("INSERT INTO users (name,email,password,role) VALUES ('Legacy reader','legacy-reader@test.invalid','fixture','user')");
$readerId = (int) $pdo->lastInsertId();
$pdo->exec("INSERT INTO notifications (category,action,title,message) VALUES ('project','legacy','Legacy','Preserve me')");
$oldNotificationId = (int) $pdo->lastInsertId();
$pdo->exec('INSERT INTO notification_reads (notification_id,user_id) VALUES (' . $oldNotificationId . ',' . $readerId . ')');
$oldNotification = $pdo->query('SELECT * FROM notifications WHERE id=' . $oldNotificationId)->fetch(PDO::FETCH_ASSOC);
$oldRead = $pdo->query('SELECT * FROM notification_reads WHERE notification_id=' . $oldNotificationId)->fetch(PDO::FETCH_ASSOC);
$pdo->exec(file_get_contents($notificationMigration . '.up.sql'));
$migrated = $pdo->query('SELECT * FROM notifications WHERE id=' . $oldNotificationId)->fetch(PDO::FETCH_ASSOC);
moduleExpect($migrated['audience'] === 'legacy' && $migrated['department'] === null && $migrated['reminder_key'] === null, 'Migration explicitly preserves legacy audience');
unset($migrated['audience'], $migrated['department'], $migrated['reminder_key']);
moduleExpect($migrated === $oldNotification, 'Migration preserves historical notification ID and fields');
moduleExpect($pdo->query('SELECT * FROM notification_reads WHERE notification_id=' . $oldNotificationId)->fetch(PDO::FETCH_ASSOC) === $oldRead, 'Migration preserves original read marker');
$pdo->exec(file_get_contents($notificationMigration . '.down.sql'));
moduleExpect($pdo->query('SELECT * FROM notifications WHERE id=' . $oldNotificationId)->fetch(PDO::FETCH_ASSOC) === $oldNotification, 'Rollback preserves original notification record');
$pdo->exec(file_get_contents($notificationMigration . '.up.sql'));
$pdo->exec('DELETE FROM notifications WHERE id=' . $oldNotificationId);
$pdo->exec('DELETE FROM users WHERE id=' . $readerId);
