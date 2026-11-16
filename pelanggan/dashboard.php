<?php
require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->query("
    SELECT * FROM peralatan
    WHERE status = 'tersedia'
    ORDER BY nama_peralatan ASC
    LIMIT 3
");
$rekomendasi = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Pelanggan - Tulip.projectt</title>
    <link rel="stylesheet" href="../assets/css/pelanggan.css">
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="logo">🧺 Tulip.projectt</div>
    <nav class="nav">
        <a href="dashboard.php" class="active">Home</a>
        <a href="peralatan.php">Peralatan</a>
        <a href="kontak.php">Kontak</a>
        <a href="../auth/login_pelanggan.php" class="btn-login">Login / Sign Up</a>
    </nav>
</header>

<!-- HERO -->
<section class="hero-slider">
    <div class="slides">
        <div class="slide active" style="background-image:url('../assets/basic set piknik.png')"></div>
        <div class="slide" style="background-image:url('../assets/piknik kids set.jpg')"></div>
        <div class="slide" style="background-image:url('../assets/piknik nature vibes.jpeg')"></div>
    </div>

    <div class="hero-content">
        <h1>Nikmati Piknik Tanpa Ribet</h1>
        <p>Sewa peralatan piknik lengkap & praktis</p>
        <a href="peralatan.php" class="btn-primary">Mulai Sewa</a>
    </div>
</section>

<!-- REKOMENDASI -->
<section class="rekomendasi">
    <h1>Rekomendasi Set Piknik</h1>
    <p class="sub">Bikin momen biasa jadi luar biasa bersama tulip.projectt</p>

    <div class="rekomendasi-grid">
         <?php foreach ($rekomendasi as $row): ?>
            <div class="card-piknik">
                <a href="detail_peralatan.php?id=<?= urlencode($row['id_peralatan']) ?>&from=dashboard">
                    <img src="../uploads/peralatan/<?= e($row['gambar']) ?>" alt="<?= e($row['nama_peralatan']) ?>">
                    <h4><?= e($row['nama_peralatan']) ?></h4>
                </a>
                <div class="harga">Rp <?= number_format($row['harga_sewa'], 0, ',', '.') ?> / hari</div>
                <a href="../auth/login_pelanggan.php" class="btn-login btn-full">
                    + Keranjang
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ABOUT / STORY -->
<section class="about-highlight">
    <div class="about-container">
        <div class="about-text">
            <h2>Wujudkan Kencan Impianmu Jadi Kenyataan!</h2>
            <p>
                <strong>tulip.projectt</strong> hadir sebagai solusi praktis untuk kamu yang ingin
                menciptakan momen spesial tanpa ribet. Kami menyediakan jasa sewa set piknik dan
                dekorasi estetik yang dirancang khusus untuk mempermudah persiapanmu, sekaligus
                memberikan pengalaman kencan piknik (<em>picnic-date</em>) yang unik, nyaman,
                dan tak terlupakan.
            </p>
            <p>
                Selama perjalanan kami, kami merasa terhormat telah menjadi <em>"sahabat"</em>
                yang menemani ribuan momen berharga pelanggan.
            </p>
            <h4>Kenapa harus <span>tulip.projectt</span>?</h4>
            <ul>
                <li>✨ <strong>Pengalaman Kencan Unik</strong> – Mengubah ruang biasa jadi penuh keajaiban</li>
                <li>📸 <strong>Pencipta Momen Berharga</strong> – Pulang dengan senyuman & foto cantik</li>
                <li>🤍 <strong>Sahabat Terbaikmu</strong> – Vendor yang peduli detail kencan impianmu</li>
                <li>🎒 <strong>Praktis & Estetik</strong> – Datang & nikmati, kami urus semuanya</li>
            </ul>
        </div>
        <div class="about-image">
            <img src="../assets/piknik date.jpg" alt="Tulip Project Picnic">
        </div>
    </div>
</section>

<!-- MAP -->
<section class="map">
    <h2>📍 Lokasi</h2>
    <p>Cirebon, Indonesia</p>
    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3961.53836053651!2d108.61503457761557!3d-6.8258591507703095!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6f1bb6e7c2c683%3A0xb4a947c8101e411b!2sPerumahan%20BTN%20Sindanglaut%20indah!5e0!3m2!1sen!2sus!4v1766491858687!5m2!1sen!2sus" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
</section>

<!-- FOOTER -->
<footer class="footer">
    © <?= date('Y') ?> Tulip.projectt | Penyewaan Peralatan Piknik
</footer>

<script>
let slides = document.querySelectorAll('.slide');
let index = 0;
if (slides.length > 0) {
    setInterval(() => {
        slides[index].classList.remove('active');
        index = (index + 1) % slides.length;
        slides[index].classList.add('active');
    }, 4000);
}
</script>

</body>
</html>
