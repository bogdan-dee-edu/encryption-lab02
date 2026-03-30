<?php

const SUPPORTED_ALGOS = ['md5', 'bcrypt', 'argon2', 'scrypt'];

const ALGO_LABELS = [
    'md5' => 'MD5',
    'bcrypt' => 'bcrypt',
    'argon2' => 'Argon2id',
    'scrypt' => 'scrypt',
];

// читання конфігурації з env variables
function cfg(string $key, string $default = ''): string
{
    $v = getenv($key);
    return ($v !== false && $v !== '') ? $v : $default;
}

// Перевірка чи встановлено scrypt
function scryptAvailable(): bool
{
    return function_exists('scrypt');
}

// Головна функція хешування пароля
function hashPassword(string $password, string $algo): string
{
    return match ($algo) {
        'md5' => hashMd5($password),
        'bcrypt' => hashBcrypt($password),
        'argon2' => hashArgon2($password),
        'scrypt' => hashScrypt($password),
        default => throw new RuntimeException("Непідтримуваний алгоритм: $algo"),
    };
}

// Перевірка відповідності введеного пароля до хешу
function verifyPassword(string $password, string $storedHash, string $algo): bool
{
    return match ($algo) {
        'md5' => hash_equals($storedHash, hashMd5($password)),
        'bcrypt', 'argon2' => password_verify($password, $storedHash),
        'scrypt' => verifyScrypt($password, $storedHash),
        default => false,
    };
}

// MD5 hash - подвійний, додається salt з конфігурації

function hashMd5(string $password): string
{
    $salt = cfg('APP_SALT');
    // Схема: MD5( salt . MD5(password) . salt )
    $inner = md5($password);
    return md5($salt . $inner . $salt);
}


// bcrypt
function hashBcrypt(string $password): string
{
    $cost = (int)cfg('BCRYPT_COST', '12');
    $cost = max(10, min(31, $cost)); // обмеження безпечного діапазону
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
}


// Argon2
function hashArgon2(string $password): string
{
    $options = [
        'memory_cost' => (int)cfg('ARGON2_MEMORY', '65536'),
        'time_cost' => (int)cfg('ARGON2_TIME', '4'),
        'threads' => (int)cfg('ARGON2_THREADS', '2'),
    ];
    return password_hash($password, PASSWORD_ARGON2ID, $options);
}


// scrypt
function hashScrypt(string $password): string
{
    if (!scryptAvailable()) {
        throw new RuntimeException('Розширення scrypt недоступне.');
    }

    $n = (int)cfg('SCRYPT_N', '16384');
    $r = (int)cfg('SCRYPT_R', '8');
    $p = (int)cfg('SCRYPT_P', '1');
    $keyLen = 32;

    // Генеруємо криптографічно стійку сіль
    $salt = random_bytes(16);
    $saltHex = bin2hex($salt);

    $hashHex = scrypt($password, $salt, $n, $r, $p, $keyLen);

    // Формат: saltHex$hashHex$N:r:p
    return $saltHex . '$' . $hashHex . '$' . implode(':', [$n, $r, $p]);
}

function verifyScrypt(string $password, string $stored): bool
{
    if (!scryptAvailable()) return false;

    $parts = explode('$', $stored);
    if (count($parts) !== 3) return false;

    [$saltHex, $storedHashHex, $params] = $parts;
    [$n, $r, $p] = array_map('intval', explode(':', $params));

    $salt = hex2bin($saltHex);
    $keyLen = (int)(strlen($storedHashHex) / 2);
    $newHashHex = scrypt($password, $salt, $n, $r, $p, $keyLen);

    return hash_equals($storedHashHex, $newHashHex);
}

/**
 * Валідація вхідних даних
 * Повертає масив помилок або порожній масив якщо все ок.
 */
function validateRegistration(array $post): array
{
    $errors = [];

    $login = trim($post['login'] ?? '');
    $pass = $post['password'] ?? '';
    $confirm = $post['confirm'] ?? '';
    $algo = $post['algo'] ?? 'md5';

    if (!preg_match('/^[a-z0-9_]{3,30}$/i', $login)) {
        $errors[] = 'Логін: лише латинські літери, цифри та "_", від 3 до 30 символів.';
    }
    if (strlen($pass) < 6) {
        $errors[] = 'Пароль має містити щонайменше 6 символів.';
    }
    if ($pass !== $confirm) {
        $errors[] = 'Паролі не збігаються.';
    }
    if (!in_array($algo, SUPPORTED_ALGOS, true)) {
        $errors[] = 'Невідомий алгоритм хешування.';
    }
    if ($algo === 'scrypt' && !scryptAvailable()) {
        $errors[] = 'scrypt недоступний на цьому сервері (перебудуйте Docker-образ).';
    }

    return $errors;
}

function validateLogin(array $post): array
{
    $errors = [];
    if (empty(trim($post['login'] ?? ''))) $errors[] = 'Введіть логін.';
    if (empty($post['password'] ?? '')) $errors[] = 'Введіть пароль.';
    return $errors;
}

// XSS protection helper (використовується у view)
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
