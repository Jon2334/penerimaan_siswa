<?php
require __DIR__ . '/config/config.php';
$hash = '$2y$12$4NtiCkKR.vHYpP4/wvVWb.bAF3sSeQOrMjXdKa35mOMUqKMwVJwtK';
$pdo->exec("UPDATE users SET password = '{$hash}' WHERE username = 'admin'");
echo "Password updated for admin\n";
