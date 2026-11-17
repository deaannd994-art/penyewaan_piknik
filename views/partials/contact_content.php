<!-- KONTEN KONTAK & KETENTUAN PENYEWAAN (100% Responsive & Strict CSP Compliant) -->
<div class="page-header">
    <h1 class="page-title">📞 Kontak & Ketentuan Penyewaan</h1>
    <p class="page-subtitle">Informasi penting dan syarat layanan sebelum Anda melakukan pemesanan set piknik.</p>
</div>

<div class="story-grid">
    <!-- KARTU KIRI: KETENTUAN & TATA TERTIB SEWA -->
    <div class="checkout-card">
        <h2 class="checkout-card-title">
            <span>📜</span> Ketentuan & Tata Tertib Sewa
        </h2>
        <ul class="criteria-list">
            <li class="criteria-item is-valid"><span class="criteria-icon">✓</span> Pemesanan wajib dilakukan melalui sistem resmi Tulip.projectt.</li>
            <li class="criteria-item is-valid"><span class="criteria-icon">✓</span> Jadwal sewa aktif setelah verifikasi bukti pembayaran oleh tim kami.</li>
            <li class="criteria-item is-valid"><span class="criteria-icon">✓</span> Pengambilan dan pengembalian dilakukan tepat waktu sesuai tanggal sewa.</li>
            <li class="criteria-item is-valid"><span class="criteria-icon">✓</span> Penyewa wajib merawat kebersihan dan keutuhan seluruh komponen set piknik.</li>
            <li class="criteria-item is-valid"><span class="criteria-icon">✓</span> Kerusakan atau kehilangan barang menjadi tanggung jawab penyewa sepenuhnya.</li>
            <li class="criteria-item is-valid"><span class="criteria-icon">✓</span> Keterlambatan pengembalian tanpa konfirmasi dikenakan penyesuaian biaya sewa.</li>
        </ul>
    </div>

    <!-- KARTU KANAN: HUBUNGI TIM CUSTOMER CARE -->
    <div class="checkout-card">
        <h2 class="checkout-card-title">
            <span>💬</span> Hubungi Tim Customer Care
        </h2>
        <div class="detail-specs">
            <div class="detail-spec-item">
                <span>📍 Alamat Workshop</span>
                <div>
                    <?php 
                    $mapsQueryUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode('Sindanglaut, Cirebon, Jawa Barat');
                    ?>
                    <a href="<?= SecurityHelper::e($mapsQueryUrl) ?>" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="maps-address-link" 
                       title="Buka lokasi di Google Maps">
                        <strong>Sindanglaut, Cirebon, Jawa Barat</strong>
                        <span class="maps-link-icon">↗</span>
                    </a>
                </div>
            </div>
            <div class="detail-spec-item">
                <span>📱 WhatsApp Resmi</span>
                <strong>0857 1759 7300</strong>
            </div>
            <div class="detail-spec-item">
                <span>📧 Email Informasi</span>
                <strong>tulip.projectt@gmail.com</strong>
            </div>
            <div class="detail-spec-item">
                <span>🕘 Jam Operasional</span>
                <strong>08.00 – 20.00 WIB (Setiap Hari)</strong>
            </div>
        </div>

        <?php 
        $waGeneralMsg = "Halo Admin Piknik, saya ingin tanya-tanya mengenai informasi penyewaan peralatan piknik.";
        $waUrl = whatsapp_url($waGeneralMsg);
        ?>
        <div class="mt-3">
            <a href="<?= SecurityHelper::e($waUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-whatsapp btn-full btn-lg">
                💬 Hubungi Admin via WhatsApp
            </a>
        </div>
    </div>
</div>
