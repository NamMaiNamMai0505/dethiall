<?php

$host = '127.0.0.1';
$port = '3307';
$user = 'root';
$pass = 'cdhc2@123';
$db = 'cdhc_testing';

$pdo = new PDO("mysql:host={$host};port={$port}", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$pdo->exec("DROP DATABASE IF EXISTS `{$db}`");
$pdo->exec("CREATE DATABASE `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "OK: reset `{$db}`\n";
