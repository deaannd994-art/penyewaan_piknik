-- ========================================================
-- DATABASE HARDENING & MODERNIZATION
-- Sistem Penyewaan Peralatan Piknik (Tulip.projectt)
-- File: database_secure.sql
-- Standards: OWASP Top 10, UUID v4 PK, Audit Logs, Brute-force Protection
-- ========================================================

CREATE DATABASE IF NOT EXISTS `penyewaan_piknik` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `penyewaan_piknik`;

-- Nonaktifkan pengecekan foreign key sementara untuk import bersih
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `detail_transaksi`;
DROP TABLE IF EXISTS `transaksi`;
DROP TABLE IF EXISTS `keranjang`;
DROP TABLE IF EXISTS `peralatan`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- 1. Tabel `users` (Primary Key: UUID v4 CHAR(36))
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id_user` CHAR(36) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','owner','pelanggan') NOT NULL DEFAULT 'pelanggan',
  `no_hp` VARCHAR(20) DEFAULT NULL,
  `alamat` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Data Seed Tabel `users`
-- --------------------------------------------------------
INSERT INTO `users` (`id_user`, `nama`, `email`, `password`, `role`, `no_hp`, `alamat`, `created_at`) VALUES
('a54ff647-80f1-4682-9454-de2f4e94e79e', 'Admin', 'admin@piknik.com', '$2y$10$f1Ear39geH4P.KS4A5o6mOS9kJK7UiqglgAuH.x206vsxsg6xHBz.', 'admin', '081234567890', 'Kantor Pusat Tulip Project', NOW()),
('431b21ae-fadd-4b50-a2d7-5694b5797eb7', 'Owner', 'owner@piknik.com', '$2y$10$uJ7CicSaiiycnqxDoydfDuY8X6fZ68ayikjkFDhulm7aCPok2kvPe', 'owner', '081234567891', 'Kantor Owner Tulip Project', NOW()),
('312424f6-3469-4e01-98da-79fae4f22baa', 'Dea Ananda', 'dea@gmail.com', '$2y$10$eHzrYSjPrINs2peM6c3g3.ju/5nlkiXSAvqdxohnRbljKjh4CLOT2', 'pelanggan', '083123456789', 'Cirebon', NOW()),
('523c0e22-afb4-4ea3-9f77-211e4ca942a5', 'Dea', 'deanda@gmail.com', '$2y$10$eHzrYSjPrINs2peM6c3g3.ju/5nlkiXSAvqdxohnRbljKjh4CLOT2', 'pelanggan', '083234567891', 'Kuningan', NOW()),
('7901b5a9-c149-4e96-82f0-f89d413ac467', 'El Putra', 'elputra@gmail.com', '$2y$10$eHzrYSjPrINs2peM6c3g3.ju/5nlkiXSAvqdxohnRbljKjh4CLOT2', 'pelanggan', '083812345678', 'Makasar', NOW()),
('86f43217-91bb-4998-9dc7-81852d750f26', 'Maulana Siddiq', 'siddiq@gmail.com', '$2y$10$eHzrYSjPrINs2peM6c3g3.ju/5nlkiXSAvqdxohnRbljKjh4CLOT2', 'pelanggan', '089712345678', 'Kadugede Kuningan', NOW()),
('39d76de2-eb5d-4dc5-9216-33d818ac7a82', 'Khaerunnisa', 'nisa@gmail.com', '$2y$10$eHzrYSjPrINs2peM6c3g3.ju/5nlkiXSAvqdxohnRbljKjh4CLOT2', 'pelanggan', '085612345678', 'Majalengka', NOW());

-- --------------------------------------------------------
-- 2. Tabel `peralatan` (Primary Key: UUID v4 CHAR(36))
-- --------------------------------------------------------
CREATE TABLE `peralatan` (
  `id_peralatan` CHAR(36) NOT NULL,
  `nama_peralatan` VARCHAR(150) NOT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  `gambar` VARCHAR(255) DEFAULT NULL,
  `harga_sewa` INT(11) NOT NULL,
  `stok` INT(11) NOT NULL DEFAULT 0,
  `status` ENUM('tersedia','tidak tersedia') NOT NULL DEFAULT 'tersedia',
  PRIMARY KEY (`id_peralatan`),
  KEY `idx_peralatan_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Data Seed Tabel `peralatan`
-- --------------------------------------------------------
INSERT INTO `peralatan` (`id_peralatan`, `nama_peralatan`, `deskripsi`, `gambar`, `harga_sewa`, `stok`, `status`) VALUES
('591971f7-5503-4297-8b8e-14a45f356b82', 'Movie Set Piknik', 'Include:\r\n- Proyektor 1pcs\r\n- Layar & penyangga 1 set\r\n- Alas 2pcs\r\n- Teepee tent 1 pcs\r\n- Beanbag 1 pcs\r\n- Lampu tumblr 1 pcs\r\n- Selimut 2 pcs\r\n- Bantal 4 pcs\r\n- Mini table 30x50cm 1 pcs\r\n- Box kayu 2 pcs\r\n- HDMI Laptop 1 pcs', '1766498782_899.jpg', 500000, 1, 'tersedia'),
('02a0fdd9-eb19-4208-94dd-fd91a94120c9', 'Paket Piknik Lengkap', 'Include:\r\n- Kursi lipat 2 pcs\r\n- Meja lipat kain\r\n- Tikar piknik (motif kotak merah putih)\r\n- Keranjang rotan\r\n- Nampan kayu\r\n- Set cangkir & gelas\r\n- Topi pantai 2 pcs (properti foto)\r\n- Bunga artificial', '1766499302_755.jpg', 150000, 2, 'tersedia'),
('578ffea4-728e-4a3c-b365-41718c2eef18', 'Piknik Simple', 'Include:\r\n- Keranjang\r\n- Alat makan/piring\r\n- Tempat buah\r\n- Gelas 2 pcs\r\n- Talenan\r\n- Piring susun\r\n- Kain alas + Tikar anti air\r\n- Vas Bunga + Bunga artificial', '1766508877_328.jpg', 75000, 3, 'tersedia'),
('206a6f43-041f-4f0b-93ff-53248896b137', 'Birthday piknik', 'Include:\r\n- Alas piknik (Tikar)\r\n- Bunting flag \"Happy Birthday\"\r\n- Set balon\r\n- Meja kayu kecil\r\n- Cake stand\r\n- Bantal santai (3 pcs dengan motif berbeda)\r\n- Papan saji kayu/talenan estetik\r\n- Vas & bunga artificial\r\n- Lilin dekorasi & holder', '1766507619_768.jpg', 230000, 2, 'tersedia'),
('63141170-f786-49b7-a053-fca88dec17d0', 'Cheerfull Piknik', 'Include:\r\n- Tikar piknik (motif kotak kuning-putih/mustard)\r\n- Nampan kayu (2 pcs untuk alas saji)\r\n- Keranjang piknik (dengan lapisan kain kotak merah)\r\n- Kamera Instax/Polaroid (properti foto warna biru)\r\n- Buket bunga matahari & bunga kering\r\n- Papan kayu saji (bentuk unik/aesthetic board)\r\n- Set gelas kaca (3 pcs)\r\n- Kertas dekorasi (alas makanan motif koran/newsprint)', '1766508838_782.jpg', 65000, 3, 'tersedia'),
('1e957436-804a-42b6-b1c5-c53a7981e38c', 'Kids Set Piknik', 'Include:\r\n- Tenda Teepee (tenda kerucut putih)\r\n- Alas piknik berlapis (motif kotak merah & kain pink estetik)\r\n- Meja lipat kayu kecil (dengan taplak renda)\r\n- Keranjang piknik rotan (model klasik)\r\n- Tiered stand (rak saji tingkat untuk dessert)\r\n- Boneka dekorasi (2 pcs sebagai pemanis)\r\n- Kuda goyang kayu (rocking horse kecil)\r\n- Bunting flag kain (putih)\r\n- Set alat makan estetik (piring kayu, mangkuk, dan teko pink)', '1766509119_773.jpg', 250000, 2, 'tersedia'),
('e5aa5095-94c1-4a6c-8635-c5154f0f00cf', 'Minimalis set piknik', 'Include:\r\n- Tikar piknik\r\n- Properti Topi pantai\r\n- Tiered stand (rak saji tingkat putih 3 susun)\r\n- Keranjang rotan\r\n- Set piring kayu (2 pcs)\r\n- Set sendok & garpu kayu (2 pasang)\r\n- Placemat eceng gondok (2 pcs alas piring anyaman)\r\n- Nampan kayu kecil (pedestal kayu oval)\r\n- Properti dekorasi (bunga matahari, biji pinus, & bunga kering)', '1768401154_289.jpeg', 45000, 5, 'tersedia'),
('1d329513-683e-4731-b43c-ad649e3114e1', 'Have fun date set', 'Include:\r\n- Alas piknik putih (kain polos)\r\n- Kanvas lukis & Mini Easel (2 set)\r\n- Set cat akrilik (4 warna dasar)\r\n- Spons lukis\r\n- Vas bunga kaca & buket bunga segar\r\n- Kartu UNO (untuk aktivitas tambahan)\r\n- Nampan saji (untuk makanan)', '1768401887_889.jpeg', 115000, 5, 'tersedia');

-- --------------------------------------------------------
-- 3. Tabel `transaksi` (Primary Key & FK: UUID v4 CHAR(36))
-- --------------------------------------------------------
CREATE TABLE `transaksi` (
  `id_transaksi` CHAR(36) NOT NULL,
  `id_user` CHAR(36) NOT NULL,
  `tanggal_sewa` DATE NOT NULL,
  `tanggal_kembali` DATE NOT NULL,
  `total_harga` INT(11) NOT NULL,
  `metode_pembayaran` VARCHAR(50) DEFAULT NULL,
  `bukti_pembayaran` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('menunggu','diproses','disetujui','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu',
  `alamat` TEXT DEFAULT NULL,
  `catatan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_transaksi`),
  KEY `idx_transaksi_user` (`id_user`),
  KEY `idx_transaksi_status` (`status`),
  KEY `idx_transaksi_created` (`created_at`),
  CONSTRAINT `fk_transaksi_users` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 4. Tabel `detail_transaksi` (Primary Key & FK: UUID v4 CHAR(36))
-- --------------------------------------------------------
CREATE TABLE `detail_transaksi` (
  `id_detail` CHAR(36) NOT NULL,
  `id_transaksi` CHAR(36) NOT NULL,
  `id_peralatan` CHAR(36) NOT NULL,
  `jumlah` INT(11) NOT NULL,
  `subtotal` INT(11) NOT NULL,
  PRIMARY KEY (`id_detail`),
  KEY `idx_detail_transaksi` (`id_transaksi`),
  KEY `idx_detail_peralatan` (`id_peralatan`),
  CONSTRAINT `fk_detail_transaksi` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_detail_peralatan` FOREIGN KEY (`id_peralatan`) REFERENCES `peralatan` (`id_peralatan`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Data Seed Transaksi & Detail Transaksi (Untuk Pengujian IDOR)
-- --------------------------------------------------------
INSERT INTO `transaksi` (`id_transaksi`, `id_user`, `tanggal_sewa`, `tanggal_kembali`, `total_harga`, `metode_pembayaran`, `status`, `alamat`, `catatan`, `created_at`) VALUES
('f47ac10b-58cc-4372-a567-0e02b2c3d479', '7901b5a9-c149-4e96-82f0-f89d413ac467', '2026-11-20', '2026-11-22', 150000, 'Transfer Bank', 'diproses', 'Makasar', 'Mohon dikirim pagi hari', NOW()),
('a1b2c3d4-e5f6-4a1b-8c2d-3e4f5a6b7c8d', '523c0e22-afb4-4ea3-9f77-211e4ca942a5', '2026-11-25', '2026-11-26', 75000, 'Bayar di Tempat', 'selesai', 'Kuningan', 'Catatan pesanan Dea', NOW())
ON DUPLICATE KEY UPDATE `total_harga` = VALUES(`total_harga`);

INSERT INTO `detail_transaksi` (`id_detail`, `id_transaksi`, `id_peralatan`, `jumlah`, `subtotal`) VALUES
('b8a9c0d1-e2f3-4a5b-9c0d-1e2f3a4b5c6d', 'f47ac10b-58cc-4372-a567-0e02b2c3d479', '02a0fdd9-eb19-4208-94dd-fd91a94120c9', 1, 150000),
('c7b8a9d0-e1f2-4b3c-8d9e-0f1a2b3c4d5e', 'a1b2c3d4-e5f6-4a1b-8c2d-3e4f5a6b7c8d', '578ffea4-728e-4a3c-b365-41718c2eef18', 1, 75000)
ON DUPLICATE KEY UPDATE `subtotal` = VALUES(`subtotal`);

-- --------------------------------------------------------
-- 5. Tabel `keranjang` (Primary Key & FK: UUID v4 CHAR(36))
-- --------------------------------------------------------
CREATE TABLE `keranjang` (
  `id_keranjang` CHAR(36) NOT NULL,
  `id_user` CHAR(36) NOT NULL,
  `id_peralatan` CHAR(36) NOT NULL,
  `jumlah` INT(11) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_keranjang`),
  KEY `idx_keranjang_user` (`id_user`),
  KEY `idx_keranjang_peralatan` (`id_peralatan`),
  CONSTRAINT `fk_keranjang_users` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_keranjang_peralatan` FOREIGN KEY (`id_peralatan`) REFERENCES `peralatan` (`id_peralatan`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 6. Tabel Security Audit Trail (`audit_logs`)
-- Standar: OWASP A09:2021 - Security Logging and Monitoring Failures
-- --------------------------------------------------------
CREATE TABLE `audit_logs` (
  `id_log` CHAR(36) NOT NULL,
  `id_user` CHAR(36) DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `target_resource` VARCHAR(100) NOT NULL,
  `status` ENUM('success','failed') NOT NULL DEFAULT 'success',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `idx_audit_user` (`id_user`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_created` (`created_at`),
  CONSTRAINT `fk_audit_users` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 7. Tabel Brute-Force Protection (`login_attempts`)
-- Standar: OWASP A07:2021 - Identification and Authentication Failures
-- --------------------------------------------------------
CREATE TABLE `login_attempts` (
  `id_attempt` CHAR(36) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_blocked` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_attempt`),
  KEY `idx_login_rate_limit` (`email`, `ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
