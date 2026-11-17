<?php
require_once __DIR__ . '/../config/database.php';
init_secure_session();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    SecurityHelper::requireCsrf();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        $error = "Email dan password wajib diisi!";
    } elseif (is_login_rate_limited($pdo, $email)) {
        http_response_code(429);
        $error = "Terlalu banyak percobaan login gagal. Percobaan login dibatasi selama 15 menit.";
        log_audit($pdo, null, 'LOGIN_BLOCKED_RATE_LIMIT', 'auth/login_pelanggan.php', 'failed', "Email: {$email}");
    } else {
        $stmt = $pdo->prepare("SELECT id_user, nama, email, password, role FROM users WHERE email = :email AND role = 'pelanggan' LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && verify_password_peppered($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['login']   = true;
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['role']    = 'pelanggan';

            clear_failed_logins($pdo, $email);
            log_audit($pdo, $user['id_user'], 'LOGIN_SUCCESS', 'auth/login_pelanggan.php', 'success');

            // Mitigasi Open Redirect (OWASP A01)
            if (!empty($_SESSION['redirect'])) {
                $tujuan = $_SESSION['redirect'];
                unset($_SESSION['redirect']);
                if (str_starts_with($tujuan, '/') && !str_starts_with($tujuan, '//') && !str_starts_with($tujuan, '/\\') && !str_contains($tujuan, ':')) {
                    header("Location: " . $tujuan);
                    exit;
                }
            }
            header("Location: ../pelanggan/login/dashboard.php");
            exit;
        } else {
            record_failed_login($pdo, $email);
            log_audit($pdo, null, 'LOGIN_FAILED', 'auth/login_pelanggan.php', 'failed', "Email: {$email}");
            http_response_code(401);
            $error = "Email atau password salah!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Pelanggan - Tulip.projectt</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_path('public/assets/css/style.css') ?>">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-brand">
                <span>🧺</span> Tulip.projectt
            </div>
            <h1 class="auth-title">Masuk ke Akun Anda</h1>
            <p class="auth-subtitle">Kelola sewa peralatan piknik dan pantau pesanan Anda.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <span>⚠️</span>
                <div><?= SecurityHelper::e($error) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <?= SecurityHelper::csrfField() ?>

            <div class="form-group">
                <label class="form-label" for="loginEmail">Alamat Email</label>
                <input type="email" id="loginEmail" name="email" class="form-control" 
                       value="<?= SecurityHelper::e($_POST['email'] ?? '') ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="loginPassword">Password</label>
                <div class="password-field-wrapper">
                    <input type="password" id="loginPassword" name="password" class="form-control" required>
                    <button type="button" class="btn-toggle-password" data-target="loginPassword" aria-label="Lihat Password">
                        👁️‍🗨️
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg mt-3">
                Masuk Sekarang
            </button>
        </form>

        <div class="auth-footer">
            Belum memiliki akun? <a href="register.php" class="text-bold">Daftar sekarang</a>
            <div class="mt-2">
                <a href="<?= app_path('pelanggan/dashboard.php') ?>" class="text-sm text-muted">← Kembali ke Beranda</a>
            </div>
        </div>
    </div>
</div>

<script src="<?= app_path('public/assets/js/app.js') ?>"></script>
</body>
</html>
