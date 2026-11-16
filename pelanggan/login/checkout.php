<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['pelanggan'], app_path('auth/login_pelanggan.php'));

$nama    = $_SESSION['nama'] ?? 'Pelanggan';
$id_user = $_SESSION['id_user'];

/* =======================
   AMBIL ITEM CHECKOUT
======================= */
$items = $_POST['id_keranjang'] ?? [];

// Dari GET (checkout langsung dari halaman detail atau sewa sekarang)
if (isset($_GET['id'])) {
    $idPeralatan = $_GET['id'];
    if (is_valid_uuid($idPeralatan)) {
        // Cek apakah sudah ada di keranjang
        $stmtCek = $pdo->prepare("SELECT id_keranjang FROM keranjang WHERE id_user = :id_user AND id_peralatan = :id_peralatan LIMIT 1");
        $stmtCek->execute(['id_user' => $id_user, 'id_peralatan' => $idPeralatan]);
        $row = $stmtCek->fetch();

        if ($row) {
            $items = [$row['id_keranjang']];
        } else {
            // Masukkan ke keranjang terlebih dahulu
            $newCartId = generate_uuid();
            $ins = $pdo->prepare("INSERT INTO keranjang (id_keranjang, id_user, id_peralatan, jumlah, created_at) VALUES (:id_k, :id_u, :id_p, 1, NOW())");
            $ins->execute([
                'id_k' => $newCartId,
                'id_u' => $id_user,
                'id_p' => $idPeralatan
            ]);
            $items = [$newCartId];
        }
    }
}

// Normalisasi array
if (!is_array($items)) {
    $items = strpos($items, ',') !== false ? explode(',', $items) : [$items];
}

// Filter hanya UUID yang valid
$validItems = [];
foreach ($items as $item) {
    $itemStr = trim((string)$item);
    if (is_valid_uuid($itemStr)) {
        $validItems[] = $itemStr;
    }
}

if (empty($validItems)) {
    die("Keranjang sewa kosong atau pilihan item tidak valid. Silakan pilih item dari keranjang.");
}

/* =======================
   DATA PERALATAN (PDO Parameterized)
======================= */
$placeholders = implode(',', array_fill(0, count($validItems), '?'));
$sql = "
    SELECT k.id_keranjang, k.jumlah, p.nama_peralatan, p.harga_sewa, p.gambar, p.stok, p.status
    FROM keranjang k
    JOIN peralatan p ON k.id_peralatan = p.id_peralatan
    WHERE k.id_user = ? AND k.id_keranjang IN ($placeholders)
";

$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge([$id_user], $validItems));
$cartList = $stmt->fetchAll();

if (empty($cartList)) {
    die("Tidak ada item yang dapat di-checkout.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Checkout</title>
<link rel="stylesheet" href="../../assets/css/pelanggan.css">
<link rel="stylesheet" href="../../assets/css/transaksi_sewa.css?v=2">
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
            <button class="btn-login"><?= e($nama) ?> ▾</button>
            <div class="akun-menu">
                <a href="akun_saya.php">Akun Saya</a>
                <a href="../../auth/logout_pelanggan.php">Logout</a>
            </div>
        </div>
    </nav>
</header>

<section class="checkout-page">
<form action="proses_sewa.php" method="post" enctype="multipart/form-data">
<?= csrf_field() ?>

<?php foreach ($validItems as $idk): ?>
    <input type="hidden" name="id_keranjang[]" value="<?= e($idk) ?>">
<?php endforeach; ?>

<div class="checkout-wrapper">

<!-- DATA SEWA -->
<div class="card">
    <h3>Data Penyewaan</h3>

    <div class="form-group">
        <label>Tanggal Sewa</label>
        <input type="date" name="tanggal_sewa" required min="<?= date('Y-m-d') ?>">
    </div>

    <div class="form-group">
        <label>Tanggal Kembali</label>
        <input type="date" name="tanggal_kembali" required min="<?= date('Y-m-d') ?>">
    </div>

    <div class="form-group">
        <label>Alamat Lengkap Pengiriman / Pengambilan</label>
        <textarea name="alamat" required rows="3"></textarea>
    </div>

    <div class="form-group">
        <label>Catatan (Opsional)</label>
        <textarea name="catatan" rows="2"></textarea>
    </div>

    <h3>Pembayaran</h3>
    <div class="form-group">
        <label>Metode Pembayaran</label>
        <select name="metode_pembayaran" id="metodePembayaran" required>
            <option value="">-- Pilih Metode --</option>
            <option value="Transfer Bank">Transfer Bank (BCA / Mandiri)</option>
            <option value="E-Wallet">E-Wallet (GoPay / OVO / Dana)</option>
            <option value="Bayar di Tempat">Bayar di Tempat (COD)</option>
        </select>
    </div>

    <div class="form-group upload-box" id="buktiBox">
        <label>Bukti Pembayaran (JPG/PNG, Max 2MB)</label>
        <input type="file" name="bukti_pembayaran" accept=".jpg,.jpeg,.png">
    </div>
</div>

<!-- RINGKASAN -->
<div class="card">
    <h3>Ringkasan Pesanan</h3>

    <?php
    $subtotalPerHari = 0;
    foreach ($cartList as $row):
        $itemTotal = (int)$row['harga_sewa'] * (int)$row['jumlah'];
        $subtotalPerHari += $itemTotal;
    ?>
    <div class="ringkasan-item">
        <img src="../../uploads/peralatan/<?= e($row['gambar']) ?>" alt="<?= e($row['nama_peralatan']) ?>">
        <span><?= e($row['nama_peralatan']) ?> × <?= (int)$row['jumlah'] ?></span>
        <strong>Rp <?= number_format($itemTotal, 0, ',', '.') ?></strong>
    </div>
    <?php endforeach; ?>

    <input type="hidden" id="subtotalPerHari" value="<?= $subtotalPerHari ?>">
    <input type="hidden" name="total_harga" id="totalFinal">
    <input type="hidden" name="durasi" id="durasiHari">

    <div class="ringkasan-total">
        <p>Biaya Sewa / Hari</p>
        <h2 id="totalHarga">Rp <?= number_format($subtotalPerHari, 0, ',', '.') ?></h2>
        <p id="durasiInfo">Pilih tanggal sewa dan kembali</p>
    </div>

    <button type="submit" class="btn-checkout">Konfirmasi Sewa</button>
</div>

</div>
</form>
</section>

<script>
function hitungTotalCheckout() {
    const tglSewa = document.querySelector('[name="tanggal_sewa"]').value;
    const tglKembali = document.querySelector('[name="tanggal_kembali"]').value;
    const subtotal = parseInt(document.getElementById('subtotalPerHari').value) || 0;

    if (!tglSewa || !tglKembali) return;

    const start = new Date(tglSewa);
    const end = new Date(tglKembali);
    const diffTime = end - start;
    const durasi = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

    if (durasi <= 0) {
        document.getElementById('durasiInfo').innerText = 'Tanggal kembali tidak boleh sebelum tanggal sewa!';
        document.getElementById('durasiInfo').style.color = 'red';
        return;
    }

    document.getElementById('durasiInfo').style.color = '#555';
    const total = subtotal * durasi;
    document.getElementById('totalHarga').innerText =
        'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('durasiInfo').innerText =
        'Durasi sewa: ' + durasi + ' hari';

    document.getElementById('totalFinal').value = total;
    document.getElementById('durasiHari').value = durasi;
}

document.querySelector('[name="tanggal_sewa"]').addEventListener('change', hitungTotalCheckout);
document.querySelector('[name="tanggal_kembali"]').addEventListener('change', hitungTotalCheckout);

document.getElementById('metodePembayaran').addEventListener('change', function(){
    document.getElementById('buktiBox').style.display =
        this.value === 'Bayar di Tempat' ? 'none' : 'block';
});
</script>

</body>
</html>
