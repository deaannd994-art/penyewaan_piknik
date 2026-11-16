<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['pelanggan'], app_path('auth/login_pelanggan.php'));

$nama = $_SESSION['nama'] ?? 'Pelanggan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Kontak & Ketentuan</title>
<link rel="stylesheet" href="../../assets/css/pelanggan.css">
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="logo">🧺 Tulip.projectt</div>
    <nav class="nav">
        <a href="dashboard.php">Home</a>
        <a href="peralatan.php">Peralatan</a>
        <a href="keranjang.php">Keranjang</a>
        <a href="riwayat.php">Riwayat</a>
        <a href="kontak.php" class="active">Kontak</a>

        <div class="akun-wrapper">
            <button class="btn-login">
                <?= e($nama) ?> ▾
            </button>

            <div class="akun-menu">
                <a href="akun_saya.php">Akun Saya</a>
                <a href="../../auth/logout_pelanggan.php">Logout</a>
            </div>
        </div>
    </nav>
</header>

<!-- TITLE -->
<section class="page-title">
    <h1>📞 Kontak & Ketentuan Penyewaan</h1>
    <p>Informasi penting sebelum melakukan pemesanan</p>
</section>

<!-- INFORMASI -->
<section class="info-section">
    <div class="info-container">

        <div class="info-left">
            <h2>Ketentuan Penyewaan</h2>
            <ul class="ketentuan-list">
                <li>Pemesanan dilakukan melalui sistem dan data harus diisi dengan benar.</li>
                <li>Penyewaan diproses setelah pembayaran dikonfirmasi.</li>
                <li>Pembayaran yang sudah dilakukan tidak dapat dikembalikan.</li>
                <li>Peralatan diambil dan dikembalikan sesuai jadwal yang disepakati.</li>
                <li>Keterlambatan pengembalian akan dikenakan denda.</li>
                <li>Penyewa wajib menjaga kondisi dan kebersihan peralatan.</li>
                <li>Kerusakan atau kehilangan peralatan menjadi tanggung jawab penyewa.</li>
                <li>Dilarang memindahtangankan peralatan tanpa izin pihak penyedia.</li>
                <li>Pembatalan sepihak dapat dikenakan biaya administrasi.</li>
                <li>Dengan melakukan penyewaan, penyewa dianggap menyetujui seluruh ketentuan.</li>
            </ul>
        </div>

        <div class="info-right">
            <h2>Kontak Kami</h2>

            <div class="kontak-item">
                <span>📍</span>
                <p><strong>Alamat</strong><br>Cirebon, Jawa Barat</p>
            </div>

            <div class="kontak-item">
                <span>📱</span>
                <p><strong>WhatsApp</strong><br>083167894444</p>
            </div>

            <div class="kontak-item">
                <span>📧</span>
                <p><strong>Email</strong><br>tulip.projectt@gmail.com</p>
            </div>

            <div class="kontak-item">
                <span>🕘</span>
                <p><strong>Jam Operasional</strong><br>08.00 – 20.00 WIB</p>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    © <?= date('Y') ?> Tulip.projectt | Penyewaan Peralatan Piknik
</footer>

</body>
</html>
