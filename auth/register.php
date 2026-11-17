<?php
require_once __DIR__ . '/../config/database.php';
init_secure_session();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    SecurityHelper::requireCsrf();

    $nama     = trim($_POST['nama'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $no_hp    = trim($_POST['no_hp'] ?? '');
    $alamat   = trim($_POST['alamat'] ?? '');
    $captcha  = trim($_POST['captcha_code'] ?? '');
    $role     = 'pelanggan';

    if (empty($nama) || empty($email) || empty($password)) {
        http_response_code(400);
        $error = "Nama, email, dan password wajib diisi!";
    } elseif (!validate_captcha($captcha, $pdo, 'auth/register.php')) {
        http_response_code(400);
        $error = "Kode verifikasi CAPTCHA salah atau telah kedaluwarsa. Silakan coba lagi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        $error = "Format email tidak valid!";
    } elseif (!is_strong_password($password)) {
        http_response_code(400);
        $error = "Password minimal 8 karakter dan wajib mengandung kombinasi huruf besar, huruf kecil, angka, dan simbol!";
    } else {
        // Cek email duplikat
        $cek = $pdo->prepare("SELECT id_user FROM users WHERE email = :email LIMIT 1");
        $cek->execute(['email' => $email]);
        if ($cek->fetch()) {
            http_response_code(409);
            $error = "Email sudah terdaftar!";
        } else {
            $id_user = generate_uuid();
            $hash = hash_password_peppered($password);

            $stmt = $pdo->prepare("
                INSERT INTO users (id_user, nama, email, password, role, no_hp, alamat, created_at)
                VALUES (:id_user, :nama, :email, :password, :role, :no_hp, :alamat, NOW())
            ");
            $success = $stmt->execute([
                'id_user'  => $id_user,
                'nama'     => $nama,
                'email'    => $email,
                'password' => $hash,
                'role'     => $role,
                'no_hp'    => $no_hp,
                'alamat'   => $alamat
            ]);

            if ($success) {
                session_regenerate_id(true);
                $_SESSION['login']   = true;
                $_SESSION['id_user'] = $id_user;
                $_SESSION['nama']    = $nama;
                $_SESSION['role']    = $role;

                log_audit($pdo, $id_user, 'USER_REGISTER', 'auth/register.php', 'success', "Email: {$email}");

                header("Location: ../pelanggan/login/dashboard.php");
                exit;
            } else {
                $error = "Registrasi gagal, silakan coba lagi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi Pelanggan - Tulip.projectt</title>
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
            <h1 class="auth-title">Buat Akun Pelanggan</h1>
            <p class="auth-subtitle">Daftar sekarang untuk mulai menyewa set piknik estetik impianmu.</p>
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
                <label class="form-label" for="regNama">Nama Lengkap</label>
                <input type="text" id="regNama" name="nama" class="form-control" 
                       value="<?= SecurityHelper::e($_POST['nama'] ?? '') ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="regEmail">Alamat Email</label>
                <input type="email" id="regEmail" name="email" class="form-control" 
                       value="<?= SecurityHelper::e($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="regPassword">Password Keamanan</label>
                <div class="password-field-wrapper">
                    <input type="password" id="regPassword" name="password" class="form-control" required autocomplete="new-password">
                    <button type="button" class="btn-toggle-password" data-target="regPassword" aria-label="Lihat Password">
                        👁️‍🗨️
                    </button>
                </div>

                <!-- PASSWORD STRENGTH INDICATOR BAR -->
                <div class="password-meter-container">
                    <div class="password-meter-track">
                        <div class="password-meter-bar" id="passwordMeterBar"></div>
                    </div>
                    <div class="password-meter-text">
                        <span id="passwordMeterText">Kekuatan password</span>
                        <span>Standar OWASP</span>
                    </div>
                </div>

                <!-- CRITERIA CHECKLIST GUIDE -->
                <ul class="criteria-list">
                    <li class="criteria-item" id="crit-length"><span class="criteria-icon">○</span> Minimal 8 karakter</li>
                    <li class="criteria-item" id="crit-upper"><span class="criteria-icon">○</span> Minimal 1 huruf besar (A-Z)</li>
                    <li class="criteria-item" id="crit-lower"><span class="criteria-icon">○</span> Minimal 1 huruf kecil (a-z)</li>
                    <li class="criteria-item" id="crit-number"><span class="criteria-icon">○</span> Minimal 1 angka (0-9)</li>
                    <li class="criteria-item" id="crit-symbol"><span class="criteria-icon">○</span> Minimal 1 simbol khusus (@#$%^&*!?)</li>
                </ul>
            </div>

            <div class="form-group">
                <label class="form-label" for="regPhone">Nomor WhatsApp / HP</label>
                <input type="tel" id="regPhone" name="no_hp" class="form-control" 
                       value="<?= SecurityHelper::e($_POST['no_hp'] ?? '') ?>" placeholder="08xxxxxxxxxx">
            </div>

            <div class="form-group">
                <label class="form-label" for="regAlamat">Alamat Lengkap</label>
                <textarea id="regAlamat" name="alamat" class="form-control" rows="2" 
                          placeholder="Alamat domisili atau pengiriman..."><?= SecurityHelper::e($_POST['alamat'] ?? '') ?></textarea>
            </div>

            <!-- KOTAK VERIFIKASI IMAGE CAPTCHA -->
            <div class="form-group">
                <label class="form-label" for="captchaCode">Verifikasi Keamanan (Bukan Robot)</label>
                <div class="captcha-box">
                    <img src="<?= app_path('public/captcha.php') ?>" alt="Security CAPTCHA" id="captchaRegImg" class="captcha-img">
                    <button type="button" class="btn btn-outline btn-sm btn-refresh-captcha" data-target="captchaRegImg" title="Perbarui Kode">
                        🔄 Refresh
                    </button>
                </div>
                <input type="text" id="captchaCode" name="captcha_code" class="form-control mt-2" 
                       placeholder="Ketik 6 karakter di atas..." maxlength="6" autocomplete="off" required>
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg mt-3">
                Daftar Akun Baru
            </button>
        </form>

        <div class="auth-footer">
            Sudah memiliki akun? <a href="login_pelanggan.php" class="text-bold">Masuk di sini</a>
            <div class="mt-2">
                <a href="<?= app_path('pelanggan/dashboard.php') ?>" class="text-sm text-muted">← Kembali ke Beranda</a>
            </div>
        </div>
    </div>
</div>

<script src="<?= app_path('public/assets/js/app.js') ?>"></script>
</body>
</html>
