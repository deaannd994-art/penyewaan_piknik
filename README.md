# Sistem Informasi Penyewaan Peralatan Piknik (Tulip.projectt)
> **Penerapan Pengerasan Keamanan Berbasis OWASP Top 10 & Helmet Security Headers**  
> Dibuat untuk memenuhi Ketentuan Tugas Project Pengembangan & Pengamanan Aplikasi Web (Mata Kuliah Cyber Security).

---

## 🌐 Informasi Aplikasi & Tautan Deployment

- **Nama Aplikasi:** Sistem Informasi Penyewaan Peralatan Piknik (Tulip.projectt)
- **Localhost Development URL:** `http://localhost/penyewaan_piknik`
- **Cloud Deployment URL (Placeholder):** `https://penyewaan-piknik.up.railway.app` *(Siap di-deploy tanpa ketergantungan path localhost)*

---

## 📖 Deskripsi Aplikasi

**Tulip.projectt** adalah aplikasi web penyewaan peralatan piknik dan perlengkapan kegiatan luar ruangan (outdoor dining/leisure sets) berbasis PHP dan MySQL. Aplikasi ini memfasilitasi interaksi antara pelanggan yang ingin memesan set piknik estetik (Movie Set, Birthday Set, Cheerfull Set, dll.), staf admin yang mengelola ketersediaan inventaris dan memvalidasi penyewaan, serta pemilik usaha (owner) yang memantau performa bisnis melalui dashboard visual dan rekap laporan pendapatan.

Sistem telah dibangun dan diperkeras (*hardened*) sesuai standar keamanan industri web global (**OWASP Top 10:2021** dan **Helmet Security Headers**), memastikan data pelanggan, integritas transaksi, serta kredensial pengguna terlindungi dari berbagai vektor serangan siber umum.

---

## 💻 Tech Stack

- **Server-Side Language:** PHP 8.2 (Native OOP & PDO with Native Prepared Statements)
- **Database Engine:** MySQL 8.0 / MariaDB 10.4 (InnoDB, UUID v4 CHAR(36) Primary Keys, Foreign Keys Cascade)
- **Web Server:** Apache 2.4 (XAMPP) / Nginx / Caddy
- **Dependency Management:** Composer 2 (`vlucas/phpdotenv`, `paragonie/random_compat`)
- **Client-Side:** HTML5, CSS3 Modern Flexbox/Grid, JavaScript Vanilla, Chart.js (CDN)
- **Arsitektur Keamanan:**
  - Password Hashing: HMAC-SHA256 Pepper Key + Argon2id / Bcrypt (`PASSWORD_ARGON2ID`)
  - Session Protection: Cookie `HttpOnly`, `SameSite=Strict`, `session.use_strict_mode=1`, Dynamic `Secure` flag
  - Header Hardening: Modul setara **Helmet.js** (CSP, HSTS, X-Frame-Options, X-Content-Type-Options, dll.)
  - Access Control: Middleware RBAC bertingkat (*Admin*, *Owner*, *Pelanggan*) & pencegahan IDOR

---

## ✨ Daftar Fitur Aplikasi

### 1. Autentikasi & Akun Pengguna
- Registrasi akun pelanggan baru dengan validasi kebijakan password kuat (NIST SP 800-63B).
- Form login pelanggan & login staf terpisah dengan proteksi brute-force lockout (maks. 5 percobaan).
- Manajemen sesi aman dengan regenerasi Session ID otomatis saat autentikasi.
- Halaman profil pelanggan (`akun_saya.php`) dengan context-aware output sanitization.

### 2. Pengelolaan Inventaris & Data (CRUD Lengkap)
- **Create (Tambah):** Tambah paket peralatan piknik baru lengkap dengan upload gambar yang divalidasi MIME type aktualnya.
- **Read (Lihat):** Menampilkan katalog interaktif untuk publik dan pelanggan, serta tabel inventaris bagi admin/owner.
- **Update (Ubah):** Mengedit detail informasi, stok, tarif sewa, maupun mengganti foto peralatan.
- **Delete (Hapus):** Menghapus data peralatan usang dengan konfirmasi token CSRF.
- **Toggle Status:** Mengubah status ketersediaan (*tersedia* / *tidak tersedia*) secara dinamis.

### 3. Transaksi & Keranjang Sewa Pelanggan
- Keranjang belanja interaktif (tambah item, update kuantitas, hapus item).
- Multi-item checkout terintegrasi transaksi basis data (`beginTransaction`, `commit`, `rollBack`).
- Pengunggahan bukti transfer bank / e-wallet dengan sanitasi nama berkas acak anti-collision (`bin2hex(random_bytes(6))`).
- Halaman riwayat dan rincian transaksi (`detail_sewa.php`) yang terproteksi penuh dari celah IDOR.

### 4. Pelaporan & Visualisasi Performa Bisnis
- **Grafik Interaktif (Chart.js):** Visualisasi tren pendapatan 30 hari terakhir pada Admin Dashboard dan tren pendapatan 12 bulan terakhir pada Owner Dashboard.
- **Tabel Rekapitulasi Transaksi:** Rekap lengkap seluruh transaksi masuk beserta rincian nama penyewa, paket, nominal, dan status.
- **Ekspor Data CSV:** Tombol unduh laporan transaksi instan berformat CSV standar UTF-8 BOM yang kompatibel dengan Microsoft Excel dan Google Sheets (`owner/laporan_transaksi.php?export=csv`).

---

## 🛡️ Tabel Pemetaan Ketentuan Tugas Cyber Security

| No | Ketentuan Keamanan | Standar / Mitigasi | File Sumber Penanganan | Fungsi / Mekanisme Teknis | Hasil Uji Otomatis |
|---|---|---|---|---|:---:|
| **1** | **SQL Injection pada Form Login** | OWASP A03: Injection | [`auth/login_pelanggan.php`](file:///c:/xampp/htdocs/penyewaan_piknik/auth/login_pelanggan.php), [`auth/login_admin.php`](file:///c:/xampp/htdocs/penyewaan_piknik/auth/login_admin.php), [`config/database.php`](file:///c:/xampp/htdocs/penyewaan_piknik/config/database.php) | 100% Native Prepared Statements (`PDO::ATTR_EMULATE_PREPARES => false`), parameterized queries, penolakan payload `' OR 1=1 --` dengan HTTP `401 Unauthorized`. | **PASSED (401)** |
| **2** | **Cross-Site Scripting (XSS)** | OWASP A03: Injection | [`config/security.php`](file:///c:/xampp/htdocs/penyewaan_piknik/config/security.php), [`pelanggan/login/detail_sewa.php`](file:///c:/xampp/htdocs/penyewaan_piknik/pelanggan/login/detail_sewa.php), [`auth/register.php`](file:///c:/xampp/htdocs/penyewaan_piknik/auth/register.php) | Context-aware output encoding menggunakan fungsi `e()` (`htmlspecialchars(..., ENT_QUOTES \| ENT_HTML5, 'UTF-8')`). Payload `<script>` diubah menjadi `&lt;script&gt;`. | **PASSED (Encoded)** |
| **3** | **Kontrol Akses (RBAC) & Proteksi IDOR** | OWASP A01: Broken Access Control | [`config/security.php`](file:///c:/xampp/htdocs/penyewaan_piknik/config/security.php), [`admin/dashboard.php`](file:///c:/xampp/htdocs/penyewaan_piknik/admin/dashboard.php), [`pelanggan/login/detail_sewa.php`](file:///c:/xampp/htdocs/penyewaan_piknik/pelanggan/login/detail_sewa.php) | Middleware `require_auth(['admin'])` menolak role pelanggan dengan HTTP `403 Forbidden`. Verifikasi kepemilikan data sewa (`WHERE t.id_transaksi = :id AND t.id_user = :user`) menolak IDOR dengan HTTP `403`. | **PASSED (403 Forbidden)** |
| **4** | **Brute Force Login & Lockout** | OWASP A07: Authentication Failures | [`config/security.php`](file:///c:/xampp/htdocs/penyewaan_piknik/config/security.php), [`auth/login_pelanggan.php`](file:///c:/xampp/htdocs/penyewaan_piknik/auth/login_pelanggan.php), Tabel `login_attempts` | Fungsi `is_login_rate_limited()` & `record_failed_login()`. Maksimal 5x gagal, percobaan ke-6 langsung diblokir dengan status HTTP `429 Too Many Requests` selama lockout 15 menit. | **PASSED (429 Too Many Requests)** |
| **5** | **Kebijakan Password Lemah** | OWASP A07: Authentication Failures / NIST SP 800-63B | [`config/security.php`](file:///c:/xampp/htdocs/penyewaan_piknik/config/security.php), [`auth/register.php`](file:///c:/xampp/htdocs/penyewaan_piknik/auth/register.php) | Fungsi `is_strong_password()`. Wajib minimal 8 karakter, kombinasi huruf besar (A-Z), huruf kecil (a-z), angka (0-9), dan simbol khusus. Password sederhana seperti `123456` ditolak dengan HTTP `400 Bad Request`. | **PASSED (400 Bad Request)** |
| **6** | **Security Headers (Setara Helmet)** | OWASP A05: Security Misconfiguration | [`config/SecurityHeaders.php`](file:///c:/xampp/htdocs/penyewaan_piknik/config/SecurityHeaders.php), [`config/security.php`](file:///c:/xampp/htdocs/penyewaan_piknik/config/security.php) | Menerapkan `Content-Security-Policy`, `Strict-Transport-Security` (HSTS), `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`, `COOP`, `CORP`, serta menghapus `X-Powered-By`. | **PASSED (Helmet Standard)** |
| **7** | **Pencegahan Kebocoran Secret** | OWASP A05: Security Misconfiguration | [`.gitignore`](file:///c:/xampp/htdocs/penyewaan_piknik/.gitignore), [`.env`](file:///c:/xampp/htdocs/penyewaan_piknik/.env), [`config/env.php`](file:///c:/xampp/htdocs/penyewaan_piknik/config/env.php) | Isolasi seluruh konfigurasi sensitif (kredensial DB, `APP_PEPPER_KEY`, parameter session) ke `.env`. Gitignore secara ketat memblokir `.env`, `vendor/`, dan direktori `uploads/`. Kode sumber 100% bebas hardcoded secret. | **PASSED (0 Secret Leaks)** |
| **8** | **Audit Kerentanan Dependensi** | OWASP A06: Vulnerable and Outdated Components | [`composer.json`](file:///c:/xampp/htdocs/penyewaan_piknik/composer.json), [`composer.lock`](file:///c:/xampp/htdocs/penyewaan_piknik/composer.lock), `vendor/` | Manajemen paket dependensi terisolasi menggunakan Composer (`vlucas/phpdotenv`, `paragonie/random_compat`). Perintah `composer audit` bersih dengan status *No security vulnerability advisories found*. | **PASSED (0 Advisories)** |

---

## ⚙️ Konfigurasi Environment Variable (`.env.example`)

Seluruh kredensial dan konfigurasi rahasia dikelola secara terisolasi pada file `.env`. File `.env` telah didaftarkan pada `.gitignore` dan **dilarang keras** untuk di-commit ke repositori Git.

Contoh konfigurasi aman (tersedia di [`.env.example`](file:///c:/xampp/htdocs/penyewaan_piknik/.env.example)):
```ini
# ========================================================
# ENVIRONMENT CONFIGURATION TEMPLATE (.env.example)
# Sistem Penyewaan Peralatan Piknik (Tulip.projectt)
# ========================================================

# Application Settings
APP_NAME="Penyewaan Piknik"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/penyewaan_piknik

# Database Configuration (XAMPP Default / Cloud DB)
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=penyewaan_piknik
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

# Security, Cryptography & Session Hardening
# Generate a 64-character random hex string for production pepper
APP_PEPPER_KEY=your_secret_pepper_key_here
SESSION_LIFETIME=7200
LOGIN_MAX_ATTEMPTS=5
LOGIN_LOCKOUT_MINUTES=15
```

---

## 🛠️ Panduan Instalasi & Menjalankan Lokal

1. **Clone Repositori:**
   ```bash
   cd C:\xampp\htdocs
   git clone <URL_REPOSITORY_ANDA> penyewaan_piknik
   cd penyewaan_piknik
   ```

2. **Salin File Environment:**
   ```bash
   cp .env.example .env
   ```
   *Sesuaikan konfigurasi basis data dan masukkan kunci acak 64-karakter pada `APP_PEPPER_KEY`.*

3. **Install Dependensi Composer:**
   ```bash
   ./composer.bat install
   # atau jika composer terinstal global:
   composer install
   ```

4. **Import Basis Data:**
   - Buka phpMyAdmin (`http://localhost/phpmyadmin`).
   - Buat basis data bernama `penyewaan_piknik`.
   - Import file [`database_secure.sql`](file:///c:/xampp/htdocs/penyewaan_piknik/database_secure.sql).

5. **Buka Aplikasi di Browser:**
   - Akses: `http://localhost/penyewaan_piknik`

---

## 🚀 Panduan Menjalankan Skrip Pengujian Otomatis

Proyek ini telah dilengkapi skrip verifikasi otomatis yang menguji seluruh 8 ketentuan secara berurutan dan mencetak output berwarna rapi yang siap di-screenshot untuk lampiran laporan:

### Opsi A: Menjalankan via Git Bash / Terminal Linux (`.sh`)
```bash
cd /c/xampp/htdocs/penyewaan_piknik
bash verify_8_requirements.sh
```

### Opsi B: Menjalankan via Windows PowerShell (`.ps1`)
```powershell
cd C:\xampp\htdocs\penyewaan_piknik
powershell -ExecutionPolicy Bypass -File .\verify_8_requirements.ps1
```

### Opsi C: Menjalankan Audit Dependensi Mandiri
```bash
./composer.bat audit
# atau:
php composer.phar audit
```
Output:
```text
No security vulnerability advisories found.
```

---

## 👥 Kredensial Pengujian (Demo Accounts)

Semua akun default menggunakan enkripsi **HMAC-SHA256 Pepper + Argon2id/Bcrypt**:

| Role | Nama Pengguna | Email | Password Default | Akses Halaman |
|---|---|---|---|---|
| **Admin** | Administrator | `admin@piknik.com` | `Admin@123` | [`/admin/dashboard.php`](http://localhost/penyewaan_piknik/admin/dashboard.php) |
| **Owner** | Pemilik Usaha | `owner@piknik.com` | `Owner@123` | [`/owner/dashboard.php`](http://localhost/penyewaan_piknik/owner/dashboard.php) |
| **Pelanggan** | Dea | `deanda@gmail.com` | `Password@123` | [`/pelanggan/login/dashboard.php`](http://localhost/penyewaan_piknik/pelanggan/login/dashboard.php) |
| **Pelanggan 2**| El Putra | `elputra@gmail.com` | `Password@123` | Digunakan untuk pengujian IDOR |

---

## 🤖 Ringkasan Penggunaan AI Agent

Dalam pengerjaan dan pengerasan arsitektur keamanan proyek ini, **AI Agent (Antigravity by Google DeepMind)** digunakan sebagai Senior Cyber Security Auditor dan Code Reviewer untuk:
1. **Analisis Statis & Audit Keamanan:** Memindai seluruh codebase terhadap potensi kerentanan OWASP Top 10 (SQLi, XSS, Broken Access Control, IDOR, Information Exposure).
2. **Pengerasan Header HTTP (Helmet Equivalent):** Mengabstraksikan dan mengimplementasikan kelas `SecurityHeaders` dengan standar keamanan setara Helmet JS.
3. **Penyusunan Skrip Uji Otomatis:** Membangun `verify_8_requirements.sh` dan `verify_8_requirements.ps1` yang mengeksekusi 8 pengujian berulang (*automated continuous testing*).
4. **Desain Kriptografi:** Menerapkan skema password hashing bertingkat (*Peppered Argon2id*) dan penataan primary key berbasis RFC 4122 UUID v4.
5. **Decoupling Arsitektur Cloud:** Mengembangkan helper `app_path()` untuk memastikan aplikasi bebas dari ketergantungan path statis `localhost` saat di-deploy ke platform cloud (Railway/Render/Vercel).
