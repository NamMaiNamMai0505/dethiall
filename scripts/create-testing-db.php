<?php

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3307';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : 'cdhc2@123';
$db = getenv('DB_TESTING_DATABASE') ?: 'cdhc_testing';

try {
    $pdo = new PDO("mysql:host={$host};port={$port}", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "OK: database `{$db}` ready on {$host}:{$port}\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'ERR: '.$e->getMessage()."\n");
    exit(1);
}
