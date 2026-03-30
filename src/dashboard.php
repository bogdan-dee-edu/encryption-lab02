<?php
session_start();
require_once 'db.php';
require_once 'hasher.php';

// Перевірка сесії
if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Верифікація сесійного токену через БД (prepared statement)
try {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $me = $stmt->fetch();

    // Порівнюємо токени через hash_equals (захист від timing attack)
    if (! $me || ! hash_equals($me['user_hash'], $_SESSION['user_hash'])) {
        session_destroy();
        header('Location: index.php');
        exit;
    }
} catch (PDOException $ex) {
    die('Помилка БД: ' . e($ex->getMessage()));
}

// Вихід / Logout
if (isset($_GET['logout'])) {
    $db->prepare('UPDATE users SET user_hash = "" WHERE user_id = ?')
            ->execute([$_SESSION['user_id']]);
    session_destroy();
    header('Location: index.php');
    exit;
}

// Рендер Дашборду
$users = $db->query('SELECT * FROM users ORDER BY user_id')
        ->fetchAll();

$algoColors = [
        'md5' => 'warning',
        'bcrypt' => 'primary',
        'argon2' => 'success',
        'scrypt' => 'danger',
];
$algoIcons = [
        'md5' => 'bi-hash',
        'bcrypt' => 'bi-shield-check',
        'argon2' => 'bi-shield-fill-check',
        'scrypt' => 'bi-hdd-stack',
];
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Панель керування</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/public/style-dashboard.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-light bg-white border-bottom shadow-sm px-3">
    <div class="container-fluid">
    <span class="navbar-brand d-flex align-items-center gap-2 mb-0">
      <span class="brand-icon"><i class="bi bi-shield-lock-fill"></i></span>
      <span class="fw-bold">Панель керування</span>
    </span>
        <div class="d-flex align-items-center gap-2">
      <span class="text-muted small">
        <i class="bi bi-person-circle me-1"></i><?= e($me['user_login']) ?>
        <span class="badge bg-<?= e($algoColors[$me['user_algo']] ?? 'secondary') ?> ms-1 security-badge">
          <?= e(ALGO_LABELS[$me['user_algo']] ?? $me['user_algo']) ?>
        </span>
      </span>
            <a href="?logout=1" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i>Вийти
            </a>
        </div>
    </div>
</nav>

<div class="container py-4" style="max-width:1140px">
    <!--  Інформація про поточного користувача  -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card d-flex align-items-start gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-person-fill"></i></div>
                <div>
                    <div class="label">Логін</div>
                    <div class="value fw-semibold"><?= e($me['user_login']) ?></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card d-flex align-items-start gap-3">
                <div class="stat-icon bg-<?= e($algoColors[$me['user_algo']] ?? 'secondary') ?> bg-opacity-10 text-<?= e($algoColors[$me['user_algo']] ?? 'secondary') ?>">
                    <i class="bi <?= e($algoIcons[$me['user_algo']] ?? 'bi-shield') ?>"></i>
                </div>
                <div>
                    <div class="label">Алгоритм</div>
                    <div class="value fw-semibold"><?= e(ALGO_LABELS[$me['user_algo']] ?? $me['user_algo']) ?></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card d-flex align-items-start gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-key-fill"></i></div>
                <div class="overflow-hidden">
                    <div class="label">Хеш пароля</div>
                    <div class="value mono" title="<?= e($me['user_password']) ?>">
                        <?= e($me['user_password']) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card d-flex align-items-start gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-fingerprint"></i></div>
                <div class="overflow-hidden">
                    <div class="label">Сесійний токен</div>
                    <div class="value mono"><?= e($me['user_hash']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!--  Таблиця користувачів  -->
        <div class="col-12">
            <div class="section-card">
                <div class="section-header d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0 fw-bold"><i class="bi bi-table me-2 text-primary"></i>Таблиця <code>users</code>
                        </h6>
                        <small class="text-muted"><?= count($users) ?> записів · MySQL 8</small>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th style="width:40px">ID</th>
                            <th>Логін</th>
                            <th>Алгоритм</th>
                            <th>Хеш пароля</th>
                            <th>Сесійний токен</th>
                            <th>IP</th>
                            <th>Реєстрація</th>
                            <th style="width:60px"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr class="<?= $u['user_id'] == $_SESSION['user_id'] ? 'table-primary' : '' ?>">
                                <td class="text-muted"><?= (int)$u['user_id'] ?></td>
                                <td class="fw-medium"><?= e($u['user_login']) ?></td>
                                <td>
                                    <span class="algo-pill bg-<?= e($algoColors[$u['user_algo']] ?? 'secondary') ?> bg-opacity-15">
                                        <i class="bi <?= e($algoIcons[$u['user_algo']] ?? 'bi-shield') ?> me-1"></i>
                                        <?= e(ALGO_LABELS[$u['user_algo']] ?? $u['user_algo']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="mono hash-cell d-inline-block" title="<?= e($u['user_password']) ?>">
                                        <?= e($u['user_password']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($u['user_hash']): ?>
                                        <span class="mono"><?= e(substr($u['user_hash'], 0, 12)) ?>…</span>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted"><?= $u['user_ip'] ? long2ip((int)$u['user_ip']) : '—' ?></td>
                                <td class="text-muted small"><?= e($u['created_at']) ?></td>
                                <td>
                                    <?php if ($u['user_id'] == $_SESSION['user_id']): ?>
                                        <span class="badge bg-primary small">Ви</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!--  Пам'ятка про алгоритми хешування  -->
        <div class="col-md-12">
            <div class="section-card h-100">
                <div class="section-header">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>Пам'ятка по алгоритмам хешування</h6>
                </div>
                <div class="p-3">
                    <table class="table table-sm mb-0">
                        <thead>
                        <tr>
                            <th>Алгоритм</th>
                            <th>Схема</th>
                            <th>Сіль</th>
                            <th>Рекомендація</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td><span class="badge bg-warning text-dark">MD5</span></td>
                            <td><code>MD5(salt·MD5(pass)·salt)</code></td>
                            <td>env <code>APP_SALT</code></td>
                            <td class="text-danger">Лише навчально</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-primary">bcrypt</span></td>
                            <td><code>password_hash(BCRYPT)</code></td>
                            <td>Автоматична</td>
                            <td class="text-success">Прийнятний</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-success">Argon2id</span></td>
                            <td><code>password_hash(ARGON2ID)</code></td>
                            <td>Автоматична</td>
                            <td class="text-primary fw-bold">Рекомендація: OWASP 2024</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-danger">scrypt</span></td>
                            <td><code>scrypt(pass, salt, N, r, p)</code></td>
                            <td><code>random_bytes(16)</code></td>
                            <td class="text-success">Стійкий до брутфорсу</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="/public/functions.js"></script>
</body>
</html>
