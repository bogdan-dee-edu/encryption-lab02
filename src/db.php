<?php

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host = getenv('MYSQL_HOST') ?: 'db';
    $name = getenv('MYSQL_DATABASE') ?: 'lab2_db';
    $user = getenv('MYSQL_USER') ?: 'lab2_user';
    $pass = getenv('MYSQL_PASSWORD') ?: 'lab2_pass';

    $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
