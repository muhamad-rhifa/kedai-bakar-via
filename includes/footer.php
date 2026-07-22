<?php
// includes/footer.php
require_once 'db_connect.php';

// Ambil data pengaturan
$footer_query = query("SELECT * FROM pengaturan LIMIT 1");
$footer_data = ($footer_query && num_rows($footer_query) > 0) ? fetch_assoc($footer_query) : [];

// Set default values if not defined in DB
$f_slogan = !empty($footer_data['footer_slogan']) 
    ? $footer_data['footer_slogan'] 
    : 'Menyajikan kelezatan bakaran dengan bumbu rempah khas pilihan sejak 2020. Kepuasan pelanggan adalah prioritas utama kami.';

$f_ig = !empty($footer_data['social_instagram']) ? $footer_data['social_instagram'] : '#';
$f_fb = !empty($footer_data['social_facebook']) ? $footer_data['social_facebook'] : '#';
$f_tt = !empty($footer_data['social_tiktok']) ? $footer_data['social_tiktok'] : '#';
$f_wa = !empty($footer_data['social_whatsapp']) ? $footer_data['social_whatsapp'] : 'https://wa.me/+6282299241324';

$f_phone = !empty($footer_data['kontak_telepon']) ? $footer_data['kontak_telepon'] : '+62 822-9924-1324';
$f_email = !empty($footer_data['kontak_email']) ? $footer_data['kontak_email'] : 'info@kedaibakarvia.com';
$f_address = !empty($footer_data['kontak_alamat']) ? $footer_data['kontak_alamat'] : 'Jl. Kh. Ahmad Sugriwa, Desa Iwul Parung, Bogor';
$f_hours = !empty($footer_data['kontak_jam_buka']) ? $footer_data['kontak_jam_buka'] : 'Buka 08.00 – 17.00 WIB';

$f_copyright_template = !empty($footer_data['footer_copyright']) 
    ? $footer_data['footer_copyright'] 
    : '© {year} {app_name}. All rights reserved. Made with ❤️ in Bogor.';

// Replace templates
$f_copyright = str_replace(
    ['{year}', '{app_name}'], 
    [date('Y'), APP_NAME], 
    $f_copyright_template
);

// Ambil tautan layanan
$footer_links = fetch_all(query("SELECT * FROM footer_links ORDER BY urutan ASC, label ASC"));
?>
<footer>
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-brand">🔥 <?php echo APP_NAME; ?></div>
                <p class="footer-desc"><?php echo htmlspecialchars($f_slogan); ?></p>
                <div class="footer-social">
                    <a href="<?php echo htmlspecialchars($f_ig); ?>" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="<?php echo htmlspecialchars($f_fb); ?>" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="<?php echo htmlspecialchars($f_tt); ?>" target="_blank" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                    <a href="<?php echo htmlspecialchars($f_wa); ?>" target="_blank" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            <div>
                <h4>Navigasi</h4>
                <ul>
                    <li><a href="index.php">Beranda</a></li>
                    <li><a href="user/index.php">Semua Menu</a></li>
                    <li><a href="auth/login.php">Login</a></li>
                    <li><a href="auth/register.php">Daftar</a></li>
                </ul>
            </div>
            <div>
                <h4>Layanan</h4>
                <ul>
                    <?php if (count($footer_links) > 0): ?>
                        <?php foreach ($footer_links as $link): ?>
                            <li><a href="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['label']); ?></a></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><a href="informasi.php#tentang-kami">Tentang Kami</a></li>
                        <li><a href="informasi.php#cara-pesan">Cara Pesan</a></li>
                        <li><a href="informasi.php#kebijakan-privasi">Kebijakan Privasi</a></li>
                        <li><a href="informasi.php#syarat-ketentuan">Syarat & Ketentuan</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div>
                <h4>Kontak</h4>
                <ul>
                    <li><i class="fas fa-phone"></i> <?php echo htmlspecialchars($f_phone); ?></li>
                    <li><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($f_email); ?></li>
                    <li><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($f_address); ?></li>
                    <li><i class="fas fa-clock"></i> <?php echo htmlspecialchars($f_hours); ?></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p><?php echo $f_copyright; ?></p>
            <div class="footer-payments">
                <i class="fab fa-cc-visa pay-badge" style="font-size:22px;background:none;color:#aaa;"></i>
                <i class="fab fa-cc-mastercard pay-badge" style="font-size:22px;background:none;color:#aaa;"></i>
                <i class="fab fa-cc-paypal pay-badge" style="font-size:22px;background:none;color:#aaa;"></i>
                <span class="pay-badge">QRIS</span>
                <span class="pay-badge">Transfer</span>
            </div>
        </div>
    </div>
</footer>