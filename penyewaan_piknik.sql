-- Database: penyewaan_piknik
-- Aplikasi Penyewaan Peralatan Piknik (Tulip.projectt)

CREATE DATABASE IF NOT EXISTS `penyewaan_piknik` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `penyewaan_piknik`;

-- --------------------------------------------------------
-- Struktur Tabel `users`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','owner','pelanggan') NOT NULL DEFAULT 'pelanggan',
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Data Seed Tabel `users`
-- Password default:
-- admin@piknik.com -> admin123
-- owner@piknik.com -> owner123
-- pelanggan (dea@gmail.com, dll) -> password123
-- --------------------------------------------------------
INSERT INTO `users` (`id_user`, `nama`, `email`, `password`, `role`, `no_hp`, `alamat`, `created_at`) VALUES
(1, 'Admin', 'admin@piknik.com', '$2y$10$f1Ear39geH4P.KS4A5o6mOS9kJK7UiqglgAuH.x206vsxsg6xHBz.', 'admin', '081234567890', 'Kantor Pusat Tulip Project', NOW()),
(2, 'Owner', 'owner@piknik.com', '$2y$10$uJ7CicSaiiycnqxDoydfDuY8X6fZ68ayikjkFDhulm7aCPok2kvPe', 'owner', '081234567891', 'Kantor Owner Tulip Project', NOW()),
(3, 'Dea Ananda', 'dea@gmail.com', '$2y$10$eHzrYSjPrINs2peM6c3g3.ju/5nlkiXSAvqdxohnRbljKjh4CLOT2', 'pelanggan', '083123456789', 'Cirebon', NOW()),
(4, 'Dea', 'deanda@gmail.com', '$2y$10$eHzrYSjPrINs2peM6c3g3.ju/5nlkiXSAvqdxohnRbljKjh4CLOT2', 'pelanggan', '083234567891', 'Kuningan', NOW()),
(5, 'El Putra', 'elputra@gmail.com', '$2y$10$eHzrYSjPrINs2peM6c3g3.ju/5nlkiXSAvqdxohnRbljKjh4CLOT2', 'pelanggan', '083812345678', 'Makasar', NOW()),
(6, 'Maulana Siddiq', 'siddiq@gmail.com', '$2y$10$eHzrYSjPrINs2peM6c3g3.ju/5nlkiXSAvqdxohnRbljKjh4CLOT2', 'pelanggan', '089712345678', 'Kadugede Kuningan', NOW()),
(7, 'Khaerunnisa', 'nisa@gmail.com', '$2y$10$eHzrYSjPrINs2peM6c3g3.ju/5nlkiXSAvqdxohnRbljKjh4CLOT2', 'pelanggan', '085612345678', 'Majalengka', NOW())
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- --------------------------------------------------------
-- Struktur Tabel `peralatan`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `peralatan` (
  `id_peralatan` int(11) NOT NULL AUTO_INCREMENT,
  `nama_peralatan` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `harga_sewa` int(11) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `status` enum('tersedia','tidak tersedia') NOT NULL DEFAULT 'tersedia',
  PRIMARY KEY (`id_peralatan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Data Seed Tabel `peralatan`
-- --------------------------------------------------------
INSERT INTO `peralatan` (`id_peralatan`, `nama_peralatan`, `deskripsi`, `gambar`, `harga_sewa`, `stok`, `status`) VALUES
(1, 'Movie Set Piknik', 'Include:\r\n- Proyektor 1pcs\r\n- Layar & penyangga 1 set\r\n- Alas 2pcs\r\n- Teepee tent 1 pcs\r\n- Beanbag 1 pcs\r\n- Lampu tumblr 1 pcs\r\n- Selimut 2 pcs\r\n- Bantal 4 pcs\r\n- Mini table 30x50cm 1 pcs\r\n- Box kayu 2 pcs\r\n- HDMI Laptop 1 pcs', '1766498782_899.jpg', 500000, 1, 'tersedia'),
(2, 'Paket Piknik Lengkap', 'Include:\r\n- Kursi lipat 2 pcs\r\n- Meja lipat kain\r\n- Tikar piknik (motif kotak merah putih)\r\n- Keranjang rotan\r\n- Nampan kayu\r\n- Set cangkir & gelas\r\n- Topi pantai 2 pcs (properti foto)\r\n- Bunga artificial', '1766499302_755.jpg', 150000, 2, 'tersedia'),
(3, 'Piknik Simple', 'Include:\r\n- Keranjang\r\n- Alat makan/piring\r\n- Tempat buah\r\n- Gelas 2 pcs\r\n- Talenan\r\n- Piring susun\r\n- Kain alas + Tikar anti air\r\n- Vas Bunga + Bunga artificial', '1766508877_328.jpg', 75000, 3, 'tersedia'),
(4, 'Birthday piknik', 'Include:\r\n- Alas piknik (Tikar)\r\n- Bunting flag \"Happy Birthday\"\r\n- Set balon\r\n- Meja kayu kecil\r\n- Cake stand\r\n- Bantal santai (3 pcs dengan motif berbeda)\r\n- Papan saji kayu/talenan estetik\r\n- Vas & bunga artificial\r\n- Lilin dekorasi & holder', '1766507619_768.jpg', 230000, 2, 'tersedia'),
(5, 'Cheerfull Piknik', 'Include:\r\n- Tikar piknik (motif kotak kuning-putih/mustard)\r\n- Nampan kayu (2 pcs untuk alas saji)\r\n- Keranjang piknik (dengan lapisan kain kotak merah)\r\n- Kamera Instax/Polaroid (properti foto warna biru)\r\n- Buket bunga matahari & bunga kering\r\n- Papan kayu saji (bentuk unik/aesthetic board)\r\n- Set gelas kaca (3 pcs)\r\n- Kertas dekorasi (alas makanan motif koran/newsprint)', '1766508838_782.jpg', 65000, 3, 'tersedia'),
(6, 'Kids Set Piknik', 'Include:\r\n- Tenda Teepee (tenda kerucut putih)\r\n- Alas piknik berlapis (motif kotak merah & kain pink estetik)\r\n- Meja lipat kayu kecil (dengan taplak renda)\r\n- Keranjang piknik rotan (model klasik)\r\n- Tiered stand (rak saji tingkat untuk dessert)\r\n- Boneka dekorasi (2 pcs sebagai pemanis)\r\n- Kuda goyang kayu (rocking horse kecil)\r\n- Bunting flag kain (putih)\r\n- Set alat makan estetik (piring kayu, mangkuk, dan teko pink)', '1766509119_773.jpg', 250000, 2, 'tersedia'),
(7, 'Minimalis set piknik', 'Include:\r\n- Tikar piknik\r\n- Properti Topi pantai\r\n- Tiered stand (rak saji tingkat putih 3 susun)\r\n- Keranjang rotan\r\n- Set piring kayu (2 pcs)\r\n- Set sendok & garpu kayu (2 pasang)\r\n- Placemat eceng gondok (2 pcs alas piring anyaman)\r\n- Nampan kayu kecil (pedestal kayu oval)\r\n- Properti dekorasi (bunga matahari, biji pinus, & bunga kering)', '1768401154_289.jpeg', 45000, 5, 'tersedia'),
(8, 'Have fun date set', 'Include:\r\n- Alas piknik putih (kain polos)\r\n- Kanvas lukis & Mini Easel (2 set)\r\n- Set cat akrilik (4 warna dasar)\r\n- Spons lukis\r\n- Vas bunga kaca & buket bunga segar\r\n- Kartu UNO (untuk aktivitas tambahan)\r\n- Nampan saji (untuk makanan)', '1768401887_889.jpeg', 115000, 5, 'tersedia')
ON DUPLICATE KEY UPDATE `id_peralatan` = VALUES(`id_peralatan`);

-- --------------------------------------------------------
-- Struktur Tabel `transaksi`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `transaksi` (
  `id_transaksi` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `tanggal_sewa` date NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `total_harga` int(11) NOT NULL,
  `metode_pembayaran` varchar(50) DEFAULT NULL,
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `status` enum('menunggu','diproses','disetujui','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu',
  `alamat` text DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_transaksi`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `fk_transaksi_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur Tabel `detail_transaksi`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `detail_transaksi` (
  `id_detail` int(11) NOT NULL AUTO_INCREMENT,
  `id_transaksi` int(11) NOT NULL,
  `id_peralatan` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `subtotal` int(11) NOT NULL,
  PRIMARY KEY (`id_detail`),
  KEY `id_transaksi` (`id_transaksi`),
  KEY `id_peralatan` (`id_peralatan`),
  CONSTRAINT `fk_detail_transaksi` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_detail_peralatan` FOREIGN KEY (`id_peralatan`) REFERENCES `peralatan` (`id_peralatan`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur Tabel `keranjang`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `keranjang` (
  `id_keranjang` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_peralatan` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_keranjang`),
  KEY `id_user` (`id_user`),
  KEY `id_peralatan` (`id_peralatan`),
  CONSTRAINT `fk_keranjang_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_keranjang_peralatan` FOREIGN KEY (`id_peralatan`) REFERENCES `peralatan` (`id_peralatan`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

