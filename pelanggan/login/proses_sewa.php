<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['pelanggan'], app_path('auth/login_pelanggan.php'));

/* ================= CEK METHOD & CSRF ================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: keranjang.php");
    exit;
}
require_csrf();

$id_user = $_SESSION['id_user'];

/* ================= VALIDASI INPUT ================= */
$ids         = $_POST['id_keranjang'] ?? [];
$tgl_sewa    = trim($_POST['tanggal_sewa'] ?? '');
$tgl_kembali = trim($_POST['tanggal_kembali'] ?? '');
$alamat      = trim($_POST['alamat'] ?? '');
$catatan     = trim($_POST['catatan'] ?? '');
$metode      = trim($_POST['metode_pembayaran'] ?? '');

if (empty($ids) || empty($tgl_sewa) || empty($tgl_kembali) || empty($metode) || empty($alamat)) {
    die("Data sewa tidak lengkap. Silakan lengkapi semua isian formulir.");
}

$metode_valid = ['Transfer Bank', 'E-Wallet', 'Bayar di Tempat'];
if (!in_array($metode, $metode_valid, true)) {
    die("Metode pembayaran tidak valid.");
}

// Normalisasi dan validasi UUID array
if (!is_array($ids)) {
    $ids = strpos($ids, ',') !== false ? explode(',', $ids) : [$ids];
}
$validIds = [];
foreach ($ids as $id) {
    $cleanId = trim((string)$id);
    if (is_valid_uuid($cleanId)) {
        $validIds[] = $cleanId;
    }
}

if (empty($validIds)) {
    die("Item yang dipilih tidak valid.");
}

/* ================= VALIDASI TANGGAL & DURASI ================= */
try {
    $awal   = new DateTime($tgl_sewa);
    $akhir  = new DateTime($tgl_kembali);
    if ($akhir < $awal) {
        die("Tanggal pengembalian tidak boleh sebelum tanggal sewa.");
    }
    $durasi = $awal->diff($akhir)->days + 1;
} catch (Exception $e) {
    die("Format tanggal tidak valid.");
}

/* ================= SECURE UPLOAD BUKTI PEMBAYARAN ================= */
$bukti_filename = null;

if ($metode !== 'Bayar di Tempat') {
    if (!isset($_FILES['bukti_pembayaran']) || $_FILES['bukti_pembayaran']['error'] !== UPLOAD_ERR_OK) {
        die("Bukti transfer pembayaran wajib diunggah untuk metode {$metode}.");
    }

    $fileTmp  = $_FILES['bukti_pembayaran']['tmp_name'];
    $fileSize = $_FILES['bukti_pembayaran']['size'];
    $maxSize  = 2 * 1024 * 1024; // 2MB

    if ($fileSize > $maxSize) {
        die("Ukuran file bukti pembayaran maksimal adalah 2MB.");
    }

    // Validasi ekstensi
    $ext = strtolower(pathinfo($_FILES['bukti_pembayaran']['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png'];
    if (!in_array($ext, $allowedExts, true)) {
        die("Format file bukti pembayaran hanya diperbolehkan JPG, JPEG, atau PNG.");
    }

    // Validasi MIME type aktual
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $fileTmp);
    finfo_close($finfo);

    $allowedMimes = ['image/jpeg', 'image/png'];
    if (!in_array($mime, $allowedMimes, true)) {
        die("Tipe MIME file bukti pembayaran tidak valid.");
    }

    $uploadDir = __DIR__ . '/../../uploads/bukti/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Nama file acak anti-collision
    $bukti_filename = 'bukti_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $targetPath = $uploadDir . $bukti_filename;

    if (!move_uploaded_file($fileTmp, $targetPath)) {
        die("Gagal menyimpan file bukti pembayaran.");
    }
}

/* ================= DATABASE TRANSACTION ================= */
try {
    $pdo->beginTransaction();

    // Ambil data keranjang milik user bersangkutan (IDOR protection)
    $placeholders = implode(',', array_fill(0, count($validIds), '?'));
    $sqlCart = "
        SELECT 
            k.id_keranjang,
            k.id_peralatan,
            k.jumlah,
            p.harga_sewa,
            p.stok,
            p.nama_peralatan
        FROM keranjang k
        JOIN peralatan p ON k.id_peralatan = p.id_peralatan
        WHERE k.id_user = ? AND k.id_keranjang IN ($placeholders)
        FOR UPDATE
    ";
    $stmtCart = $pdo->prepare($sqlCart);
    $stmtCart->execute(array_merge([$id_user], $validIds));
    $cartItems = $stmtCart->fetchAll();

    if (empty($cartItems)) {
        $pdo->rollBack();
        die("Keranjang kosong atau item telah kadaluarsa.");
    }

    $totalHarga = 0;
    $orderItems = [];

    foreach ($cartItems as $c) {
        $subtotal = (int)$c['harga_sewa'] * (int)$c['jumlah'] * $durasi;
        $totalHarga += $subtotal;

        // Cek kecukupan stok
        if ((int)$c['stok'] < (int)$c['jumlah']) {
            $pdo->rollBack();
            die("Stok untuk '" . e($c['nama_peralatan']) . "' tidak mencukupi. Tersedia: " . (int)$c['stok']);
        }

        $orderItems[] = [
            'id_peralatan' => $c['id_peralatan'],
            'jumlah'       => (int)$c['jumlah'],
            'subtotal'     => $subtotal
        ];
    }

    // 1. Insert Transaksi Baru (UUID v4)
    $id_transaksi = generate_uuid();
    $stmtTx = $pdo->prepare("
        INSERT INTO transaksi (
            id_transaksi,
            id_user,
            tanggal_sewa,
            tanggal_kembali,
            total_harga,
            metode_pembayaran,
            bukti_pembayaran,
            status,
            alamat,
            catatan,
            created_at
        ) VALUES (
            :id_transaksi,
            :id_user,
            :tgl_sewa,
            :tgl_kembali,
            :total_harga,
            :metode,
            :bukti,
            'menunggu',
            :alamat,
            :catatan,
            NOW()
        )
    ");
    $stmtTx->execute([
        'id_transaksi' => $id_transaksi,
        'id_user'      => $id_user,
        'tgl_sewa'     => $tgl_sewa,
        'tgl_kembali'  => $tgl_kembali,
        'total_harga'  => $totalHarga,
        'metode'       => $metode,
        'bukti'        => $bukti_filename,
        'alamat'       => $alamat,
        'catatan'      => $catatan
    ]);

    // 2. Insert Detail Transaksi & Kurangi Stok
    $stmtDetail = $pdo->prepare("
        INSERT INTO detail_transaksi (id_detail, id_transaksi, id_peralatan, jumlah, subtotal)
        VALUES (:id_detail, :id_transaksi, :id_peralatan, :jumlah, :subtotal)
    ");

    $stmtKurangStok = $pdo->prepare("
        UPDATE peralatan 
        SET stok = GREATEST(0, stok - :jumlah)
        WHERE id_peralatan = :id_peralatan
    ");

    foreach ($orderItems as $item) {
        $id_detail = generate_uuid();
        $stmtDetail->execute([
            'id_detail'    => $id_detail,
            'id_transaksi' => $id_transaksi,
            'id_peralatan' => $item['id_peralatan'],
            'jumlah'       => $item['jumlah'],
            'subtotal'     => $item['subtotal']
        ]);

        $stmtKurangStok->execute([
            'jumlah'       => $item['jumlah'],
            'id_peralatan' => $item['id_peralatan']
        ]);
    }

    // 3. Bersihkan Item yang sudah diproses dari Keranjang User
    $stmtDelCart = $pdo->prepare("DELETE FROM keranjang WHERE id_user = ? AND id_keranjang IN ($placeholders)");
    $stmtDelCart->execute(array_merge([$id_user], $validIds));

    $pdo->commit();

    // Security Audit Log
    log_audit($pdo, $id_user, 'CREATE_TRANSAKSI_SEWA', 'transaksi', 'success', "ID: {$id_transaksi}, Total: {$totalHarga}, Durasi: {$durasi} hari");

    header("Location: riwayat.php?success=1");
    exit;

} catch (\Throwable $th) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Hapus file bukti jika transaksi database gagal
    if ($bukti_filename && file_exists(__DIR__ . '/../../uploads/bukti/' . $bukti_filename)) {
        @unlink(__DIR__ . '/../../uploads/bukti/' . $bukti_filename);
    }
    error_log("Gagal proses sewa: " . $th->getMessage());
    die("Terjadi kesalahan saat memproses pesanan sewa Anda. Silakan coba kembali.");
}
