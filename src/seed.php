<?php
/**
 * seed.php створює тестових користувачів з правильним хешуванням
 *
 * Запуск:
 * docker compose run --rm php bash -c "php /var/www/html/seed.php"
 */

require_once 'db.php';
require_once 'hasher.php';

$testUsers = [
    ['login' => 'admin', 'password' => 'admin123', 'algo' => 'md5'],
    ['login' => 'bcrypt_user', 'password' => 'bcrypt123', 'algo' => 'bcrypt'],
    ['login' => 'argon_user', 'password' => 'argon123', 'algo' => 'argon2'],
];

$db = getDB();
$results = [];

foreach ($testUsers as $u) {
    // Пропускаємо якщо вже існує
    $chk = $db->prepare('SELECT user_id FROM users WHERE user_login = ?');
    $chk->execute([$u['login']]);
    if ($chk->fetch()) {
        $results[] = ['login' => $u['login'], 'status' => 'вже існує', 'ok' => false];
        continue;
    }

    // Додаємо користувача до бази даних
    try {
        $hash = hashPassword($u['password'], $u['algo']);
        $db->prepare('INSERT INTO users (user_login, user_password, user_algo) VALUES (?, ?, ?)')
            ->execute([$u['login'], $hash, $u['algo']]);
        $results[] = ['login' => $u['login'], 'algo' => $u['algo'], 'status' => 'створено', 'ok' => true];
    } catch (Exception $ex) {
        $results[] = ['login' => $u['login'], 'status' => 'помилка: ' . $ex->getMessage(), 'ok' => false];
    }
}

print_r($results);
