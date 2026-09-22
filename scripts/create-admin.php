<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
if (PHP_SAPI !== 'cli') exit("Run this script from the command line.\n");
$email = strtolower(trim($argv[1] ?? ''));
$password = $argv[2] ?? '';
$name = trim($argv[3] ?? 'Directory Administrator');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) exit("Usage: php scripts/create-admin.php email password-at-least-12-chars [name]\n");
$stmt = db()->prepare('INSERT INTO administrators (name,email,password_hash) VALUES (:name,:email,:hash) ON DUPLICATE KEY UPDATE name=VALUES(name), password_hash=VALUES(password_hash)');
$stmt->execute(['name'=>$name,'email'=>$email,'hash'=>password_hash($password,PASSWORD_DEFAULT)]);
echo "Administrator account created or updated.\n";

