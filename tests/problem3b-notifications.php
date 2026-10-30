<?php
if (!isset($testName, $pdo) || !preg_match('/\Acajpemajuan_modules_[a-f0-9]+_test\z/', $testName)
    || $pdo->query('SELECT DATABASE()')->fetchColumn() !== $testName) {
    throw new RuntimeException('Notification checks require the disposable database.');
}
$notificationModel = new Notification();
$fixtureUsers = [];
foreach (['eng' => 'Jabatan Kejuruteraan', 'land' => 'Jabatan Landskap', 'none' => null] as $label => $department) {
    $users->create(['name' => 'Notify ' . $label, 'email' => $label . '@notify.test', 'password' => 'notify-pass',
        'role' => 'user', 'department' => $department]);
    $fixtureUsers[$label] = $users->getByEmail($label . '@notify.test');
    $login = moduleRequest('auth/login', ['email' => $label . '@notify.test', 'password' => 'notify-pass']);
    $fixtureUsers[$label]['cookie'] = $login['cookie'];
}
$fixtureProjects = [];
foreach (['eng' => ['Jabatan Kejuruteraan', 'Dalam Proses'], 'land' => ['Jabatan Landskap', 'Dalam Proses'],
    'done' => ['Jabatan Kejuruteraan', 'Selesai'], 'cancel' => ['Jabatan Kejuruteraan', 'Dibatalkan'],
    'unknown' => ['Unknown', 'Dalam Proses'], 'missing' => ['', 'Dalam Proses'], 'undated' => ['Jabatan Kejuruteraan', 'Dalam Proses']] as $label => $values) {
    $data = array_replace($projectData, ['project_id' => 'NOTIFY-' . $label, 'project_name' => 'Notification fixture ' . $label,
        'department' => $values[0], 'project_status' => $values[1],
        'end_date' => $label === 'undated' ? null : date('Y-m-d', strtotime('-1 day'))]);
    $projects->create($data);
    $row = $pdo->query("SELECT * FROM projects WHERE project_id = 'NOTIFY-" . $label . "'")->fetch(PDO::FETCH_ASSOC);
    $fixtureProjects[$label] = $row['id'];
    foreach ([-1, 0, 3, 10] as $offset) {
        $projects->addSchedule($row['id'], date('Y-m-d', strtotime($offset . ' days')), 12, 'Ansuran');
    }
}
// Legacy records bypass the new create path exactly as pre-migration rows do.
$pdo->exec("INSERT INTO notifications (category, action, title, message, entity_type, entity_id)
    VALUES ('project', 'legacy_fixture', 'Legacy fixture', 'Historical project notification', 'project', " . (int) $fixtureProjects['eng'] . ')');
$legacyId = (int) $pdo->lastInsertId();
$notificationModel->markAsRead($legacyId, $fixtureUsers['land']['id']);
// Paid schedules must not generate new reminders.
$paidScheduleId = $projects->addSchedule($fixtureProjects['eng'], date('Y-m-d', strtotime('-2 days')), 13, 'Ansuran');
$pdo->exec("UPDATE project_payment_schedules SET status='Dibayar' WHERE project_id=" . (int) $fixtureProjects['eng'] . ' AND amount=13');
$notificationModel->create(['category' => 'system', 'action' => 'global_fixture', 'title' => 'Global fixture', 'message' => 'Global fixture']);
$globalId = (int) $pdo->query("SELECT id FROM notifications WHERE action='global_fixture' ORDER BY id DESC LIMIT 1")->fetchColumn();
foreach ([$adminCookie, $fixtureUsers['eng']['cookie'], $fixtureUsers['land']['cookie']] as $cookie) {
    moduleExpect(moduleRequest('dashboard', null, $cookie)['status'] === 200, 'Dashboard generates department reminders');
}
$reminders = $pdo->query("SELECT * FROM notifications WHERE action = 'project_overdue' OR action LIKE 'project_payment_%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($fixtureProjects as $label => $id) {
    $overdue = array_values(array_filter($reminders, function ($n) use ($id) { return $n['action'] === 'project_overdue' && (int) $n['entity_id'] === (int) $id; }));
    moduleExpect(count($overdue) === (in_array($label, ['done', 'cancel', 'undated'], true) ? 0 : 1), 'Overdue detection: ' . $label);
    $paymentCount = (int) $pdo->query("SELECT COUNT(*) FROM notifications n JOIN project_payment_schedules s ON s.id = n.entity_id
        WHERE n.entity_type = 'project_payment_schedule' AND n.action LIKE 'project_payment_%' AND s.project_id = " . (int) $id)->fetchColumn();
    moduleExpect($paymentCount === (in_array($label, ['done', 'cancel'], true) ? 0 : 3), 'Pending overdue/today/upcoming reminders: ' . $label);
}
$engNotification = $pdo->query("SELECT * FROM notifications WHERE action='project_overdue' AND entity_id=" . (int) $fixtureProjects['eng'])->fetch(PDO::FETCH_ASSOC);
$unknownNotification = $pdo->query("SELECT * FROM notifications WHERE action='project_overdue' AND entity_id=" . (int) $fixtureProjects['unknown'])->fetch(PDO::FETCH_ASSOC);
moduleExpect((int) $pdo->query("SELECT COUNT(*) FROM notifications n JOIN project_payment_schedules s ON s.id=n.entity_id WHERE n.entity_type='project_payment_schedule' AND s.project_id=" . (int) $fixtureProjects['eng'] . " AND s.status='Dibayar'")->fetchColumn() === 0, 'Paid schedule excluded');
moduleExpect($engNotification['audience'] === 'department' && $engNotification['department'] === 'Jabatan Kejuruteraan', 'Stored audience resolves from project database');
moduleExpect($unknownNotification['audience'] === 'admin' && $unknownNotification['department'] === null, 'Unknown project department is admin-only');
foreach ($fixtureUsers as $label => $user) {
    $all = $notificationModel->getAllForUser($user['id']);
    $ids = array_column($all, 'id');
    moduleExpect(in_array($engNotification['id'], $ids) === ($label === 'eng'), 'Department visibility: ' . $label);
    moduleExpect(!in_array($unknownNotification['id'], $ids), 'Admin-only hidden: ' . $label);
    moduleExpect(in_array($legacyId, $ids) && in_array($globalId, $ids), 'Legacy/global visibility: ' . $label);
    moduleExpect($notificationModel->getUnreadCount($user['id']) === count(array_filter($all, function ($n) { return !$n['is_read']; })), 'Unread count agrees with history: ' . $label);
    moduleExpect(array_column($notificationModel->getRecentForUser($user['id']), 'id') === array_slice($ids, 0, 10), 'Dropdown agrees with history: ' . $label);
    moduleExpect((bool) $notificationModel->getById($engNotification['id'], $user['id']) === ($label === 'eng'), 'Individual lookup authorization: ' . $label);
    $history = moduleRequest('notification', null, $user['cookie']);
    moduleExpect((strpos($history['body'], 'Notification fixture eng') !== false) === ($label === 'eng'), 'HTTP history filters department: ' . $label);
}
moduleExpect($notificationModel->getById($unknownNotification['id'], $admin['id']) !== false, 'Admin sees unknown department notification');
moduleExpect(count($notificationModel->getAllForUser($admin['id'])) === (int) $pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn(), 'Admin sees all notifications');
$land = $fixtureUsers['land'];
moduleExpect(!$notificationModel->markAsRead($engNotification['id'], $land['id']), 'Model rejects unauthorized read');
modulePost('notify/read/' . $engNotification['id'], ['user_id' => $admin['id'], 'department' => 'Jabatan Kejuruteraan'], $land['cookie'], 'notification');
moduleExpect((int) $pdo->query('SELECT COUNT(*) FROM notification_reads WHERE notification_id=' . (int) $engNotification['id'] . ' AND user_id=' . (int) $land['id'])->fetchColumn() === 0, 'Forged ID and browser audience cannot write read marker');
modulePost('notification/markAllRead', [], $land['cookie'], 'notification');
moduleExpect($notificationModel->getUnreadCount($land['id']) === 0, 'Mark all clears visible unread only');
moduleExpect((int) $pdo->query('SELECT COUNT(*) FROM notification_reads WHERE notification_id=' . (int) $engNotification['id'] . ' AND user_id=' . (int) $land['id'])->fetchColumn() === 0, 'Mark all leaves hidden notifications untouched');
modulePost('notification/read/' . $engNotification['id'], [], $fixtureUsers['eng']['cookie'], 'project/finance/' . $fixtureProjects['eng']);
$paymentNotification = $pdo->query("SELECT n.* FROM notifications n JOIN project_payment_schedules s ON s.id=n.entity_id WHERE n.entity_type='project_payment_schedule' AND s.project_id=" . (int) $fixtureProjects['eng'] . " LIMIT 1")->fetch(PDO::FETCH_ASSOC);
modulePost('notification/read/' . $paymentNotification['id'], [], $fixtureUsers['eng']['cookie'], 'project/finance/' . $fixtureProjects['eng']);
moduleExpect(moduleRequest('notification/read/' . $engNotification['id'], [], $adminCookie, false, false)['status'] === 403, 'Notification read retains CSRF');
modulePost('notification/clearAll', [], $land['cookie'], 'unauthorized');
$beforeRepeat = (int) $pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn();
moduleRequest('dashboard', null, $adminCookie);
moduleExpect((int) $pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn() === $beforeRepeat, 'Repeated dashboard does not duplicate reminders');
// Bypass existence check to prove database uniqueness also protects racing project reminder inserts.
$notificationModel->create($engNotification);
moduleExpect((int) $pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn() === $beforeRepeat, 'Unique reminder key suppresses duplicate insert');
$pdo->exec("UPDATE users SET department='Jabatan Kejuruteraan' WHERE id=" . (int) $land['id']);
moduleExpect((bool) $notificationModel->getById($engNotification['id'], $land['id']), 'Membership refreshes from database');
moduleExpect(strpos(moduleRequest('notification', null, $land['cookie'])['body'], 'Notification fixture eng') !== false, 'Existing session uses current database department');
// Browser-like audience metadata cannot override project routing at creation.
$notificationModel->create(['category' => 'project', 'action' => 'routing_fixture', 'title' => 'Routing fixture',
    'message' => 'Routing fixture', 'entity_type' => 'project', 'entity_id' => $fixtureProjects['eng'],
    'audience' => 'global', 'department' => 'Jabatan Landskap']);
$routing = $pdo->query("SELECT * FROM notifications WHERE action='routing_fixture'")->fetch(PDO::FETCH_ASSOC);
moduleExpect($routing['audience'] === 'department' && $routing['department'] === 'Jabatan Kejuruteraan', 'Caller cannot override database project audience');
// Deletion must capture the saved department before the project disappears.
modulePost('project/delete/' . $fixtureProjects['land'], [], $adminCookie, 'project');
$deletedNotification = $pdo->query("SELECT * FROM notifications WHERE entity_type='project' AND action='deleted' AND entity_id=" . (int) $fixtureProjects['land'])->fetch(PDO::FETCH_ASSOC);
moduleExpect($deletedNotification['department'] === 'Jabatan Landskap', 'Project deletion notification retains database department');
modulePost('notification/read/' . $deletedNotification['id'], [], $adminCookie, 'notification');
foreach ($fixtureProjects as $id) { $projects->delete($id); }
foreach ($fixtureUsers as $user) { $users->deleteUser($user['id']); }
