<?php
session_start();
require_once 'db.php';
require_once 'hasher.php';

$errors = [];
$success = '';
$activeTab = $_POST['tab'] ?? 'register';
$formLogin = '';

// РЕЄСТРАЦІЯ
if (($_POST['action'] ?? '') === 'register') {
    $activeTab = 'register';
    $formLogin = trim($_POST['login'] ?? '');
    $errors = validateRegistration($_POST);

    if (empty($errors)) {
        $algo = $_POST['algo'];
        $password = $_POST['password'];

        try {
            $db = getDB();

            // Перевірка унікальності логіну
            $chk = $db->prepare('SELECT user_id FROM users WHERE user_login = ?');
            $chk->execute([$formLogin]);
            if ($chk->fetch()) {
                $errors[] = 'Користувач з таким логіном вже існує.';
            } else {
                $hash = hashPassword($password, $algo);

                // Додаємо користувача в базу
                $ins = $db->prepare(
                        'INSERT INTO users (user_login, user_password, user_algo) VALUES (?, ?, ?)'
                );
                $ins->execute([$formLogin, $hash, $algo]);

                $success = 'Користувача «' . e($formLogin) . '» зареєстровано! Тепер увійдіть, використовуючи його логін та пароль.';
                $activeTab = 'login';
                $formLogin = '';
            }
        } catch (PDOException $ex) {
            $errors[] = 'Помилка БД: ' . e($ex->getMessage());
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
    }
}

// АВТОРИЗАЦІЯ
if (($_POST['action'] ?? '') === 'login') {
    $activeTab = 'login';
    $formLogin = trim($_POST['login'] ?? '');
    $errors = validateLogin($_POST);

    if (empty($errors)) {
        $password = $_POST['password'];

        try {
            $db = getDB();

            // Отримуємо запис за логіном користувача
            $stmt = $db->prepare('SELECT * FROM users WHERE user_login = ?');
            $stmt->execute([$formLogin]);
            $user = $stmt->fetch();

            $valid = $user && verifyPassword($password, $user['user_password'], $user['user_algo']);
            if ($valid) {
                // Генеруємо сесійний токен (64 hex-символи)
                $sessionHash = bin2hex(random_bytes(32));
                $ip = ip2long($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

                $upd = $db->prepare(
                        'UPDATE users SET user_hash = ?, user_ip = ? WHERE user_id = ?'
                );
                $upd->execute([$sessionHash, $ip, $user['user_id']]);

                // Зберігаємо в сесії лише ID і токен, пароль в сесії НЕ зберігаємо
                session_regenerate_id(true); // захист від session fixation
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_login'] = $user['user_login'];
                $_SESSION['user_hash'] = $sessionHash;

                header('Location: dashboard.php');
                exit;
            } else {
                $errors[] = 'Невірний логін або пароль.';
            }
        } catch (PDOException $ex) {
            $errors[] = 'Помилка БД: ' . e($ex->getMessage());
        }
    }
}

$scryptOk = scryptAvailable();
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Лабораторна робота №2 - Хешування паролів</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/public/style.css">
</head>
<body>
<div class="auth-card card">
    <div class="card-header">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="brand-icon"><i class="bi bi-shield-lock-fill"></i></div>
            <div>
                <h5 class="mb-0 fw-bold">Безпечна авторизація</h5>
                <p class="text-muted mb-0 small">Алгоритми хешування: MD5 / bcrypt / Argon2 / scrypt</p>
            </div>
        </div>
        <ul class="nav nav-tabs border-0" id="authTabs">
            <li class="nav-item">
                <button class="nav-link px-3 <?= $activeTab === 'register' ? 'active' : '' ?>" onclick="switchTab('register',this)">
                    <i class="bi bi-person-plus me-1"></i>Реєстрація
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link px-3 <?= $activeTab === 'login' ? 'active' : '' ?>" onclick="switchTab('login',this)">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Вхід
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body">

        <?php if ($errors): ?>
            <div class="alert alert-danger py-3 px-3">
                <i class="bi bi-exclamation-triangle-fill me-2 position-absolute top-0 end-0"></i>
                <?php if (count($errors) === 1): ?>
                    <?= e($errors[0]) ?>
                <?php else: ?>
                    <ul class="mb-0 ps-3 mt-1">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success py-3 px-3">
                <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
            </div>
        <?php endif; ?>

        <!--РЕЄСТРАЦІЯ -->
        <div id="tab-register" class="tab-panel <?= $activeTab !== 'register' ? 'd-none' : '' ?>">
            <form method="POST" action="" novalidate>
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="tab" value="register">

                <div class="mb-3">
                    <label class="form-label">Логін</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person text-muted"></i></span>
                        <input type="text" class="form-control" name="login"
                               placeholder="наприклад: shevchenko_t" maxlength="30"
                               value="<?= e($formLogin) ?>" autocomplete="username" required>
                    </div>
                    <div class="form-text text-muted">Латинські літери, цифри та "_" · 3–30 символів</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Пароль</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-key text-muted"></i></span>
                        <input type="password" class="form-control" name="password"
                               placeholder="Мінімум 6 символів" autocomplete="new-password" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Підтвердження пароля</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-key-fill text-muted"></i></span>
                        <input type="password" class="form-control" name="confirm"
                               placeholder="Повторіть пароль" autocomplete="new-password" required>
                    </div>
                </div>

                <!-- Вибір алгоритму -->
                <div class="mb-3">
                    <label class="form-label d-flex align-items-center gap-2">
                        <i class="bi bi-cpu text-primary"></i> Алгоритм хешування
                    </label>
                    <div class="algo-grid" id="algoGrid">

                        <label class="algo-card selected" data-algo="md5">
                            <input type="radio" name="algo" value="md5" checked>
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="algo-name"><i class="bi bi-hash me-1 text-secondary"></i>MD5</span>
                                <span class="badge bg-warning text-dark algo-badge">Простий</span>
                            </div>
                            <div class="algo-desc">MD5(salt·MD5(pass)·salt)<br>Сіль з env <code>APP_SALT</code></div>
                        </label>

                        <label class="algo-card" data-algo="bcrypt">
                            <input type="radio" name="algo" value="bcrypt">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="algo-name"><i
                                            class="bi bi-shield-check me-1 text-primary"></i>bcrypt</span>
                                <span class="badge bg-primary algo-badge">Надійний</span>
                            </div>
                            <div class="algo-desc">cost=<?= e(getenv('BCRYPT_COST') ?: '12') ?><br>Вбудований у PHP 8</div>
                        </label>

                        <label class="algo-card" data-algo="argon2">
                            <input type="radio" name="algo" value="argon2">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="algo-name"><i class="bi bi-shield-fill-check me-1 text-success"></i>Argon2id</span>
                                <span class="badge bg-success algo-badge">Рекомендований</span>
                            </div>
                            <div class="algo-desc">m=<?= e(getenv('ARGON2_MEMORY') ?: '65536') ?>KB
                                t=<?= e(getenv('ARGON2_TIME') ?: '4') ?><br>Вбудований у PHP 8
                            </div>
                        </label>

                        <label class="algo-card <?= !$scryptOk ? 'disabled' : '' ?>" data-algo="scrypt">
                            <input type="radio" name="algo" value="scrypt" <?= !$scryptOk ? 'disabled' : '' ?>>
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="algo-name"><i class="bi bi-hdd-stack me-1 text-danger"></i>scrypt</span>
                                <?php if ($scryptOk): ?>
                                    <span class="badge bg-danger algo-badge">Стійкий до брутфорсу</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary algo-badge">Недоступний</span>
                                <?php endif; ?>
                            </div>
                            <div class="algo-desc">N=<?= e(getenv('SCRYPT_N') ?: '16384') ?>
                                r=<?= e(getenv('SCRYPT_R') ?: '8') ?><br>
                                <?= $scryptOk ? 'PECL ext-scrypt активний' : 'Потрібен PECL ext-scrypt' ?>
                            </div>
                        </label>

                    </div><!-- /algo-grid -->
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="bi bi-person-check me-2"></i>Зареєструватися
                </button>
            </form>
        </div>

        <!-- ВХІД -->
        <div id="tab-login" class="tab-panel <?= $activeTab !== 'login' ? 'd-none' : '' ?>">
            <form method="POST" action="" novalidate>
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="tab" value="login">

                <div class="mb-3">
                    <label class="form-label">Логін</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person text-muted"></i></span>
                        <input type="text" class="form-control" name="login"
                               placeholder="Введіть логін" maxlength="30"
                               value="<?= e($formLogin) ?>" autocomplete="username" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Пароль</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-key text-muted"></i></span>
                        <input type="password" class="form-control" name="password"
                               placeholder="Введіть пароль" autocomplete="current-password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Увійти
                </button>
            </form>

            <div class="credentials-box mt-3">
                <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1"></i>Тестовий обліковий запис</div>
                логін: <code>admin</code> · пароль: <code>admin123</code> · алгоритм: <code>MD5</code><br>
                <span class="text-muted small">Алгоритм визначається автоматично з БД при вході.</span>
            </div>
        </div>

    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="/public/functions.js"></script>
</body>
</html>
