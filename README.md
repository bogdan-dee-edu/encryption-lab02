# Лабораторна робота №2 - Хешування паролів в БД

> PHP 8.2

> MySQL 8.0 

> Docker Compose

### Алгоритми хешування:
- MD5
- bcrypt
- Argon2id
- scrypt

---

## Перший Запуск

Скопіювати файл `example.env` в `.env`

Потім запустити команди:
```bash
docker compose up -d --build   # ~2-3 хв на першу збірку (scrypt PECL)
docker compose run --rm php bash -c "php /var/www/html/seed.php" # створити тестові акаунти

# Відкрити: http://localhost:8080

# phpMyAadmin: http://localhost:8081
```

Тестовий акаунт: логін `admin` · пароль `admin123`

---

## Керування

```bash
docker compose up -d # запустити застосунок

docker composer down # зупинити застосунок

docker compose down -v # зупинити застосунок та видалити базу даних

# phpMyAadmin, доступ до бази даних: 
# http://localhost:8081 
# login: root / password: root_password
```

Тестовий акаунт: логін `admin` · пароль `admin123`

---

## Алгоритми хешування

### 1. Double MD5 + Salt
```
hash = MD5( APP_SALT . MD5(password) . APP_SALT )
```
- `Salt` задається через env `APP_SALT`
- Зберігається 32-символьний hex

### 2. bcrypt
```php
password_hash($pass, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST])
```
- `cost` задається через env `BCRYPT_COST` (за замовчуванням: **12**)
- Вбудований у PHP, `salt` генерується автоматично

### 3. Argon2id - Рекомендований (OWASP 2024)
```php
password_hash($pass, PASSWORD_ARGON2ID, [
    'memory_cost' => ARGON2_MEMORY,   // KB
    'time_cost'   => ARGON2_TIME,
    'threads'     => ARGON2_THREADS,
])
```
- Всі параметри через env
- Вбудований у PHP 7.3+

### 4. scrypt
```
salt = random_bytes(16)
saltHex = bin2hex(salt)
hashHex = scrypt(password, salt, n, r, p, keyLen=32)
stored = saltHex . '$' . hashHex .'$' . n:r:p
```
- Потребує PECL `ext-scrypt` (встановлюється в Dockerfile)
- Параметри через env `SCRYPT_N`, `SCRYPT_R`, `SCRYPT_P`

---


## Конфігурація docker-compose.yml

| Змінна           | Опис                     |
|------------------|--------------------------|
| `APP_SALT`       | Salt for Double MD5      |
| `BCRYPT_COST`    | bcrypt cost (10–31)      |
| `ARGON2_MEMORY`  | Memory for Argon2 (KB)   |
| `ARGON2_TIME`    | Time cost for Argon2     |
| `ARGON2_THREADS` | Threads count for Argon2 |
| `SCRYPT_N`       | CPU/memory cost scrypt   |
| `SCRYPT_R`       | Block size scrypt        |
| `SCRYPT_P`       | Parallelization scrypt   |
