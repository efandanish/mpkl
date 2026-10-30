<?php
if (!isset($testName, $pdo, $users) || !preg_match('/\Acajpemajuan_modules_[a-f0-9]+_test\z/', $testName)
    || $pdo->query('SELECT DATABASE()')->fetchColumn() !== $testName) {
    throw new RuntimeException('Department checks require the disposable database.');
}
$departmentInput = ['name' => 'Department User', 'email' => 'department@example.test', 'password' => 'department-pass',
    'role' => 'user', 'department' => 'Jabatan Kejuruteraan'];
$legacyUser = $users->getByEmail('user@example.test');
moduleExpect($legacyUser['department'] === null && password_verify('module-pass', $legacyUser['password']),
    'Existing unassigned user and original password remain accessible');
$legacyUpdate = ['name' => $legacyUser['name'], 'email' => $legacyUser['email'], 'password' => '', 'role' => 'user'];
modulePost('user/update/' . $legacyUser['id'], $legacyUpdate + ['department' => ''], $adminCookie, 'user_updated');
moduleExpect($users->getById($legacyUser['id'])['department'] === null, 'Legacy NULL user remains editable without department');
$countBefore = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
foreach (['', 'Unknown', 'Kejuruteraan', str_repeat('x', 151), ['unexpected']] as $badDepartment) {
    moduleExpect(moduleRequest('user/create', array_replace($departmentInput, ['department' => $badDepartment]), $adminCookie)['status'] === 422,
        'New ordinary registration rejects missing/invalid department');
}
$missingDepartment = $departmentInput;
unset($missingDepartment['department']);
moduleExpect(moduleRequest('user/create', $missingDepartment, $adminCookie)['status'] === 422, 'New ordinary registration requires department field');
moduleExpect($pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === $countBefore, 'Rejected registrations create no accounts');
modulePost('user/create', $departmentInput, $adminCookie, 'user_created');
$departmentUser = $users->getByEmail($departmentInput['email']);
try {
    moduleExpect($departmentUser['department'] === 'Jabatan Kejuruteraan' && $departmentUser['role'] === 'user'
        && password_verify('department-pass', $departmentUser['password']), 'New account saves department with existing role and password hashing');
    $login = moduleRequest('auth/login', ['email' => $departmentInput['email'], 'password' => 'department-pass']);
    moduleExpect(strpos($login['location'], 'dashboard') !== false, 'Department-assigned user can log in');
    $departmentCookie = $login['cookie'];
    $listing = moduleRequest('user', null, $adminCookie);
    moduleExpect(strpos($listing['body'], '<th>Jabatan</th>') !== false && strpos($listing['body'], 'Jabatan Kejuruteraan') !== false
        && strpos($listing['body'], 'Belum ditetapkan') !== false, 'User listing shows department and legacy NULL label');
    foreach (Departments::options() as $option) {
        foreach (['user/create', 'user/edit/' . $departmentUser['id'], 'project/create'] as $path) {
            moduleExpect(strpos(moduleRequest($path, null, $adminCookie)['body'], 'value="' . $option . '"') !== false,
                'User and project forms share department option: ' . $path . ' / ' . $option);
        }
    }
    $updateDepartment = array_replace($departmentInput, ['password' => '', 'department' => 'Jabatan Landskap']);
    modulePost('user/update/' . $departmentUser['id'], $updateDepartment, $adminCookie, 'user_updated');
    $saved = $users->getByEmail($departmentInput['email']);
    moduleExpect($saved['department'] === 'Jabatan Landskap' && $saved['password'] === $departmentUser['password'], 'Department update without password keeps password hash');
    $edit = moduleRequest('user/edit/' . $departmentUser['id'], null, $adminCookie);
    moduleExpect(strpos($edit['body'], 'value="Jabatan Landskap" selected') !== false, 'Saved department is selected on editing');
    $updateDepartment['password'] = 'new-department-pass';
    $updateDepartment['department'] = 'Jabatan Perancang Bandar';
    modulePost('user/update/' . $departmentUser['id'], $updateDepartment, $adminCookie, 'user_updated');
    $saved = $users->getByEmail($departmentInput['email']);
    moduleExpect($saved['department'] === 'Jabatan Perancang Bandar' && password_verify('new-department-pass', $saved['password']), 'Department persists with password change');
    foreach (['Invalid', ['array']] as $badDepartment) {
        moduleExpect(moduleRequest('user/update/' . $departmentUser['id'], array_replace($updateDepartment, ['department' => $badDepartment]), $adminCookie)['status'] === 422,
            'Invalid department update rejected');
    }
    modulePost('user/update/' . $departmentUser['id'], $updateDepartment, $departmentCookie, 'unauthorized');
    moduleExpect(moduleRequest('user/update/' . $departmentUser['id'], $updateDepartment, $adminCookie, false, false)['status'] === 403,
        'Department update still requires CSRF');
    moduleExpect($users->getByEmail($departmentInput['email']) === $saved, 'Rejected and ordinary-user department edits leave entire user unchanged');
    modulePost('user/update/' . $legacyUser['id'], $legacyUpdate + ['department' => 'Jabatan Khidmat Pengurusan'], $adminCookie, 'user_updated');
    modulePost('user/update/' . $legacyUser['id'], $legacyUpdate, $adminCookie, 'user_updated');
    moduleExpect($users->getById($legacyUser['id'])['department'] === 'Jabatan Khidmat Pengurusan', 'Existing user assignment persists when department omitted on update');
    moduleExpect(moduleRequest('project/create', array_replace($projectData, ['project_id' => 'INVALID-DEPARTMENT', 'department' => 'Unknown']), $userCookie)['status'] === 422,
        'Project creation validates shared department list server-side');
} finally {
    $users->deleteUser($departmentUser['id']);
    $users->updateUser($legacyUser['id'], $legacyUpdate + ['department' => null]);
}
