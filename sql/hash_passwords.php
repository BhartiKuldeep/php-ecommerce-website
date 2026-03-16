<?php
/**
 * Password Hash Helper
 * 
 * Run this script once to generate hashed passwords for the seed data.
 * Usage: php sql/hash_passwords.php
 * 
 * Then paste the output hashes into database.sql or run the UPDATE queries.
 */

echo "=== Password Hash Generator ===" . PHP_EOL . PHP_EOL;

$passwords = [
    'Admin@123' => 'admins table — admin@example.com',
    'User@123'  => 'users table  — user@example.com',
];

foreach ($passwords as $plain => $usage) {
    $hash = password_hash($plain, PASSWORD_DEFAULT);
    echo "Password: {$plain}" . PHP_EOL;
    echo "For:      {$usage}" . PHP_EOL;
    echo "Hash:     {$hash}" . PHP_EOL;
    echo PHP_EOL;
}

echo "Copy these hashes into your database.sql INSERT statements," . PHP_EOL;
echo "or run the following SQL after importing the schema:" . PHP_EOL . PHP_EOL;

$adminHash = password_hash('Admin@123', PASSWORD_DEFAULT);
$userHash  = password_hash('User@123', PASSWORD_DEFAULT);

echo "UPDATE admins SET password = '{$adminHash}' WHERE email = 'admin@example.com';" . PHP_EOL;
echo "UPDATE users  SET password = '{$userHash}'  WHERE email = 'user@example.com';" . PHP_EOL;
