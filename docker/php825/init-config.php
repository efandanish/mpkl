<?php
// Generate container-private development keys; never copy host security keys.
$source = file_get_contents('/workspace/app/config/config.example.php');
$marker = "'' /* Generate a unique private value before use. */";
if (substr_count($source, $marker) !== 3) {
    throw new RuntimeException('Expected three private security key placeholders.');
}
foreach (range(1, 3) as $unused) {
    $position = strpos($source, $marker);
    $source = substr_replace($source, var_export(bin2hex(random_bytes(32)), true), $position, strlen($marker));
}
umask(0077);
if (file_put_contents('/workspace/app/config/config.php', $source) === false) {
    throw new RuntimeException('Cannot write container-private configuration.');
}
chmod('/workspace/app/config/config.php', 0600);
