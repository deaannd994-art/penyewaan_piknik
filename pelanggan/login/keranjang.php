<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['pelanggan'], app_path('auth/login_pelanggan.php'));

$nama    = $_SESSION['nama'] ?? 'Pelanggan';
$id_user = $_SESSION['id_user'];
$csrf    = get_csrf_token();

$stmt = $pdo->prepare("
    SELECT 
        k.id_keranjang,
        k.jumlah,
        p.nama_peralatan,
        p.harga_sewa,
        p.gambar,
        p.stok,
        p.status AS status_peralatan
    FROM keranjang k
    JOIN peralatan p ON k.id_peralatan = p.id_peralatan
    WHERE k.id_user = :id_user
    ORDER BY k.created_at DESC
");
$stmt->execute(['id_user' => $id_user]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Keranjang Sewa</title>
<link rel="stylesheet" href="../../assets/css/pelanggan.css">
<link rel="stylesheet" href="../../assets/css/transaksi_sewa.css">

<script>
function hitungTotal() {
    let total = 0;

    document.querySelectorAll('.check-item:checked').forEach(el => {
        let harga  = parseInt(el.dataset.harga) || 0;
        let jumlah = parseInt(el.dataset.jumlah) || 0;
        total += harga * jumlah;
    });

    document.getElementById('totalHarga').innerText =
        'Rp ' + total.toLocaleString('id-ID');
}
</script>
</head>

<body>

<header class="header">
    <div class="logo">🧺 Tulip.projectt</div>
    <nav class="nav">
        <a href="dashboard.php">Home</a>
        <a href="peralatan.php">Peralatan</a>
        <a href="keranjang.php" class="active">Keranjang</a>
        <a href="riwayat.php">Riwayat</a>
        <a href="kontak.php">Kontak</a>

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

<section class="page-title">
    <h1>🛒 Keranjang Sewa</h1>
    <p>Barang Pilihanmu, Jangan lupa disewa!!</p>
</section>

<form action="checkout.php" method="post">
<?= csrf_field() ?>

<div class="cart-list">

<?php if (empty($items)): ?>
    <div style="text-align: center; padding: 40px; color: #666;">
        <p>Keranjang belanja Anda masih kosong.</p>
        <a href="peralatan.php" class="btn-primary" style="display:inline-block; margin-top:10px;">Lihat Peralatan</a>
    </div>
<?php else: ?>
    <?php foreach ($items as $row): ?>
    <div class="cart-item <?= $row['stok'] <= 0 ? 'disabled' : '' ?>">

        <input type="checkbox"
               name="id_keranjang[]"
               value="<?= e($row['id_keranjang']) ?>"
               data-harga="<?= (int)$row['harga_sewa'] ?>"
               data-jumlah="<?= (int)$row['jumlah'] ?>"
               class="check-item"
               onchange="hitungTotal()"
               <?= ($row['status_peralatan'] !== 'tersedia' || (int)$row['stok'] <= 0) ? 'disabled' : '' ?>>

        <img src="../../uploads/peralatan/<?= e($row['gambar']) ?>" alt="<?= e($row['nama_peralatan']) ?>" class="cart-img">

        <div class="cart-info">
            <h4><?= e($row['nama_peralatan']) ?></h4>
            <p class="price">Rp <?= number_format((float)$row['harga_sewa'], 0, ',', '.') ?></p>

            <?php if ($row['status_peralatan'] === 'tersedia' && (int)$row['stok'] > 0): ?>
                <span class="badge available">Tersedia (Sisa: <?= (int)$row['stok'] ?>)</span>
            <?php else: ?>
                <span class="badge empty">Tidak Tersedia</span>
            <?php endif; ?>
        </div>

        <div class="cart-qty">
        <?php if ((int)$row['stok'] > 0 && $row['status_peralatan'] === 'tersedia'): ?>
            <a href="update_qty.php?id=<?= urlencode($row['id_keranjang']) ?>&type=minus&csrf=<?= urlencode($csrf) ?>">−</a>
            <span style="display:inline-block; min-width:30px; text-align:center; font-weight:bold;"><?= (int)$row['jumlah'] ?></span>
            <a href="update_qty.php?id=<?= urlencode($row['id_keranjang']) ?>&type=plus&csrf=<?= urlencode($csrf) ?>">+</a>
        <?php else: ?>
            <span class="stok-habis">Habis</span>
        <?php endif; ?>
        </div>

        <div class="cart-subtotal">
            Rp <?= number_format((float)($row['harga_sewa'] * $row['jumlah']), 0, ',', '.') ?>
            <a href="hapus_keranjang.php?id=<?= urlencode($row['id_keranjang']) ?>&csrf=<?= urlencode($csrf) ?>" class="hapus" 
               onclick="return confirm('Hapus item ini dari keranjang?')">🗑</a>
        </div>
       
    </div>
    <?php endforeach; ?>
<?php endif; ?>

</div>

<?php if (!empty($items)): ?>
<div class="cart-summary bottom">
    <span>Total Sewa</span>
    <strong id="totalHarga">Rp 0</strong>

    <button type="submit" class="btn-primary">
        Checkout
    </button>
</div>
<?php endif; ?>

</form>

<footer class="footer">
    © <?= date('Y') ?> Tulip.projectt | Penyewaan Peralatan Piknik
</footer>

</body>
</html>
