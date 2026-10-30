<?php
// Container-only override. Never copied into the host application's configuration.
return [
    'DB_HOST' => 'db',
    'DB_NAME' => 'cajpemajuan_php825_dev_test',
    'DB_USER' => 'root',
    'DB_PASS' => getenv('PHP825_DB_PASSWORD'),
    'RECEIPT_STORAGE_PATH' => '/workspace/public/uploads/receipts'
];
