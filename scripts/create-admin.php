<?php
require __DIR__ . '/bootstrap.php';

try {
    $email = isset($argv[1]) ? trim($argv[1]) : '';
    $name = isset($argv[2]) ? trim($argv[2]) : 'Administrator';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '') {
        throw new RuntimeException('Usage: php scripts/create-admin.php email@example.com "Administrator Name"');
    }
    $user = new User();
    if ($user->emailExists($email)) {
        throw new RuntimeException('That email already exists. No account was changed.');
    }
    // Generate rather than accept a password in shell history or process arguments.
    $password = bin2hex(random_bytes(12));
    if (!$user->create(array('email' => $email, 'name' => $name, 'password' => $password, 'role' => 'admin'))) {
        throw new RuntimeException('Administrator could not be created.');
    }
    echo "Administrator created.\nEmail: " . $email . "\nPassword: " . $password . "\nSave this password; it is shown only once.\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
