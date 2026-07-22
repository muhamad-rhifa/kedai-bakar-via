<?php
// informasi.php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$page_title = 'Layanan & Informasi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root{--p:#9e1616;--pd:#7a0e0e;--s:#eb570d;--dk:#1a2332;--dk2:#2d4059;--lt:#f8f9fa;--tx:#222;--txl:#666;--sh:0 4px 24px rgba(0,0,0,0.1);--sh2:0 12px 40px rgba(0,0,0,0.18);--tr:all 0.3s cubic-bezier(.4,0,.2,1);--r:14px;}
        * { margin:0; padding:0; box-sizing:border-box; }
        html{scroll-behavior:smooth; scroll-padding-top: 100px;}
        body { font-family:'Inter',sans-serif; background:#f4f5f7; color:var(--tx); -webkit-font-smoothing:antialiased; min-height:100vh; display:flex; flex-direction:column; }
        .container { max-width:1000px; margin:0 auto; padding:0 24px; }
        a { text-decoration:none; color:inherit; }

        /* ── NAVBAR ── */
        .navbar{position:fixed;top:0;left:0;right:0;z-index:1000;transition:var(--tr);padding:12px 0;background:rgba(255,255,255,0.97);backdrop-filter:blur(16px);box-shadow:0 2px 20px rgba(0,0,0,0.08);}
        .navbar .container{display:flex;justify-content:space-between;align-items:center;gap:20px;max-width:1240px;}
        .brand{font-size:1.4rem;font-weight:800;color:var(--dk);letter-spacing:-0.5px;}
        .brand span{color:#ff8c5a;}
        .nav-links{display:flex;align-items:center;gap:4px;list-style:none;}
        .nav-links a{color:var(--txl);padding:8px 14px;border-radius:8px;font-size:14px;font-weight:600;transition:var(--tr);}
        .nav-links a:hover, .nav-links a.active{color:var(--p);background:rgba(158,22,22,0.05);}
        .cart-pill{position:relative;display:flex;align-items:center;gap:6px;background:var(--p);padding:8px 16px;border-radius:50px;color:#fff!important;font-size:14px;font-weight:600;transition:var(--tr);}
        .cart-pill:hover{background:var(--pd)!important;}
        .cart-pill .badge{background:var(--s);color:#fff;border-radius:50%;width:18px;height:18px;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;}
        .hamburger{display:none;flex-direction:column;gap:5px;background:none;border:none;cursor:pointer;padding:4px;}
        .hamburger span{display:block;width:24px;height:2px;background:var(--dk);border-radius:2px;transition:var(--tr);}

        /* ── PAGE HEADER ── */
        .page-header {
            background: linear-gradient(135deg, rgba(26,35,50,0.85) 0%, rgba(158,22,22,0.8) 100%), url('https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=1600&q=80&auto=format&fit=crop') center/cover no-repeat;
            color: white;
            padding: 120px 0 60px;
            text-align: center;
        }
        .page-header h1 { font-size:2rem; font-weight:800; margin-bottom:12px; }
        .breadcrumb { font-size:14px; opacity:.8; display:flex; justify-content:center; gap:8px; }
        .breadcrumb a { color:white; font-weight:500; }
        .breadcrumb a:hover { text-decoration:underline; }

        /* ── CONTENT ── */
        .content-wrap { flex:1; padding: 60px 0; }
        .info-card {
            background: #fff;
            border-radius: var(--r);
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 40px;
            margin-bottom: 30px;
            scroll-margin-top: 100px;
        }
        .info-card h2 {
            color: var(--dk);
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 16px;
        }
        .info-card h2 i { color: var(--p); }
        .info-card p {
            color: var(--txl);
            line-height: 1.8;
            margin-bottom: 16px;
            font-size: 1.05rem;
        }
        .info-card ul {
            list-style: none;
            margin-bottom: 16px;
        }
        .info-card ul li {
            position: relative;
            padding-left: 24px;
            margin-bottom: 12px;
            color: var(--txl);
            line-height: 1.6;
        }
        .info-card ul li::before {
            content: '\f058';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            left: 0;
            top: 2px;
            color: var(--p);
            font-size: 14px;
        }

        /* ── INTERACTIVE MAP WIDGET ── */
        .map-link { display: block; border-radius: var(--r); overflow: hidden; box-shadow: var(--sh); transition: var(--tr); border: 1px solid #eee; margin-top: 15px; }
        .map-link:hover { transform: translateY(-4px); box-shadow: var(--sh2); }
        .map-container { position: relative; width: 100%; height: 350px; overflow: hidden; background: #eee; }
        .map-img { width: 100%; height: 100%; object-fit: cover; transition: var(--tr); }
        .map-link:hover .map-img { transform: scale(1.03); }
        .map-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.05); display: flex; align-items: flex-end; justify-content: flex-end; padding: 20px; transition: var(--tr); }
        .map-link:hover .map-overlay { background: rgba(0,0,0,0.15); }
        .map-btn { background: #4285F4; color: white; padding: 12px 20px; border-radius: 50px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(66,133,244,0.35); transition: var(--tr); border: 1.5px solid rgba(255,255,255,0.2); text-transform: uppercase; letter-spacing: 0.5px; }
        .map-link:hover .map-btn { background: #357ae8; transform: scale(1.05); }

        /* ── FOOTER ── */
        footer{background:var(--dk);color:#ccc;padding:60px 0 0;text-align:left;}
        .footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px;margin-bottom:48px;}
        .footer-brand{font-size:1.3rem;font-weight:800;color:#fff;margin-bottom:14px;}
        .footer-brand span{color:#ff8c5a;}
        .footer-desc{font-size:14px;line-height:1.75;color:#aaa;margin-bottom:20px;}
        .footer-social{display:flex;gap:10px;}
        .footer-social a{width:36px;height:36px;background:rgba(255,255,255,.08);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#aaa;font-size:15px;transition:var(--tr);}
        .footer-social a:hover{background:var(--p);color:#fff;}
        footer h4{color:#fff;font-size:14px;font-weight:700;margin-bottom:18px;}
        footer ul{list-style:none;}
        footer ul li{margin-bottom:10px;padding-left:0;}
        footer ul li::before{display:none;}
        footer ul li a{color:#999;font-size:14px;transition:color .2s;}
        footer ul li a:hover{color:#ff8c5a;}
        footer ul li i{color:var(--p);margin-right:8px;width:14px;}
        .footer-bottom{border-top:1px solid rgba(255,255,255,.06);padding:20px 0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;}
        .footer-bottom p{font-size:13px;color:#666;}
        .footer-payments{display:flex;gap:10px;align-items:center;}
        .pay-badge{background:rgba(255,255,255,.08);color:#aaa;font-size:11px;font-weight:700;padding:4px 10px;border-radius:6px;}
        .pay-badge i{font-size:18px;}

        /* ── RESPONSIVE ── */
        @media (max-width:768px) {
            .hamburger{display:flex;}
            .nav-links{display:none;position:absolute;top:100%;left:0;right:0;background:rgba(255,255,255,0.98);padding:20px;border-top:1px solid #f0f0f0;flex-direction:column;align-items:flex-start;box-shadow:0 8px 24px rgba(0,0,0,0.1);}
            .nav-links.open{display:flex;}
            .nav-links a{width:100%;padding:12px 16px;}
            .info-card { padding: 30px 20px; }
            .footer-grid{grid-template-columns:1fr 1fr;}
            .footer-bottom{flex-direction:column;text-align:center;}
        }
        @media (max-width:480px) {
            .footer-grid{grid-template-columns:1fr;}
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="container" style="max-width:1240px;">
        <a href="index.php" class="brand">🔥 <?php echo APP_NAME; ?></a>
        <button class="hamburger" id="hamburger" onclick="document.getElementById('navLinks').classList.toggle('open');">
            <span></span><span></span><span></span>
        </button>
        <div class="nav-links" id="navLinks">
            <a href="index.php">Beranda</a>
            <a href="user/index.php">Menu</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="user/pesanan_saya.php">Pesanan</a>
                <a href="auth/logout.php" style="color:#dc3545;">Logout</a>
                <a href="user/keranjang.php" class="cart-pill">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="badge"><?php echo countKeranjang($_SESSION['user_id']); ?></span>
                </a>
            <?php else: ?>
                <a href="auth/login.php">Login</a>
                <a href="auth/register.php" style="background:var(--p);color:#fff;border-radius:50px;padding:8px 20px;">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- PAGE HEADER -->
<div class="page-header">
    <div class="container">
        <h1>Layanan & Informasi</h1>
        <div class="breadcrumb">
            <a href="index.php">Beranda</a> &rsaquo;
            <span>Layanan</span>
        </div>
    </div>
</div>

<!-- CONTENT -->
<div class="content-wrap">
    <div class="container">
        
        <!-- TENTANG KAMI -->
        <div class="info-card" id="tentang-kami">
            <h2><i class="fas fa-store"></i> Tentang Kami</h2>
            <p><strong><?php echo APP_NAME; ?></strong> adalah pelopor cita rasa bakaran autentik yang berlokasi di Desa Iwul, Parung, Bogor. Berdiri dengan semangat untuk menyajikan hidangan berkualitas tinggi dengan harga yang terjangkau.</p>
            <p>Kami menggunakan bumbu rempah rahasia khas yang meresap sempurna ke dalam setiap hidangan, disajikan segar setiap hari karena kepuasan pelanggan adalah prioritas utama kami.</p>
            
            <div style="margin-top:24px;">
                <p style="font-weight:600; color:var(--dk); margin-bottom:12px;"><i class="fas fa-map-marked-alt"></i> Lokasi Kedai Kami (Klik untuk membuka Google Maps):</p>
                <a href="https://maps.app.goo.gl/PKGap3UFvYqbBVeZ7" target="_blank" class="map-link">
                    <div class="map-container">
                        <img src="assets/images/lokasi_map.png" alt="Peta Lokasi <?php echo htmlspecialchars(APP_NAME); ?>" class="map-img">
                        <div class="map-overlay">
                            <span class="map-btn"><i class="fas fa-directions"></i> Petunjuk Arah</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- CARA PESAN -->
        <div class="info-card" id="cara-pesan">
            <h2><i class="fas fa-concierge-bell"></i> Cara Pesan</h2>
            <p>Pemesanan di <?php echo APP_NAME; ?> sangat mudah dan praktis:</p>
            <ul>
                <li><strong>Pilih Menu:</strong> Jelajahi berbagai pilihan menu bakaran, makanan, dan minuman di halaman Beranda atau Menu.</li>
                <li><strong>Tambah ke Keranjang:</strong> Klik tombol "Tambah" pada menu yang Anda inginkan. Anda bisa mengatur jumlah pesanan di halaman keranjang.</li>
                <li><strong>Checkout:</strong> Lanjutkan ke proses checkout, pastikan detail alamat dan nomor telepon sudah benar.</li>
                <li><strong>Pembayaran:</strong> Pilih metode pembayaran yang tersedia (QRIS, Transfer Bank, E-Wallet, dll).</li>
                <li><strong>Selesai:</strong> Pesanan Anda akan segera kami proses dan antar dalam keadaan hangat!</li>
            </ul>
        </div>

        <!-- KEBIJAKAN PRIVASI -->
        <div class="info-card" id="kebijakan-privasi">
            <h2><i class="fas fa-user-shield"></i> Kebijakan Privasi</h2>
            <p>Kami di <?php echo APP_NAME; ?> sangat menghargai dan melindungi privasi data pelanggan kami. Informasi pribadi seperti nama, nomor telepon, alamat email, dan alamat pengiriman hanya digunakan untuk keperluan operasional pesanan.</p>
            <p>Kami tidak akan pernah menyebarluaskan, menjual, atau membagikan data pribadi Anda kepada pihak ketiga mana pun di luar keperluan pemrosesan pembayaran (melalui payment gateway resmi) dan pengiriman.</p>
        </div>

        <!-- SYARAT & KETENTUAN -->
        <div class="info-card" id="syarat-ketentuan">
            <h2><i class="fas fa-file-contract"></i> Syarat & Ketentuan</h2>
            <p>Dengan menggunakan layanan <?php echo APP_NAME; ?>, Anda menyetujui ketentuan berikut:</p>
            <ul>
                <li>Pesanan yang sudah dibayar dan diproses oleh dapur tidak dapat dibatalkan secara sepihak.</li>
                <li>Estimasi waktu penyajian dan pengiriman dapat berubah menyesuaikan dengan kondisi cuaca dan antrean pesanan.</li>
                <li>Jika terjadi kendala pada menu yang diterima (tidak sesuai pesanan/kurang), harap hubungi kontak kami maksimal 1 jam setelah pesanan diterima dengan menyertakan bukti foto.</li>
                <li>Harga yang tertera pada website dapat berubah sewaktu-waktu tanpa pemberitahuan sebelumnya, namun harga pada saat checkout adalah harga final yang harus dibayar.</li>
            </ul>
        </div>

    </div>
</div>

<!-- FOOTER -->
<?php include 'includes/footer.php'; ?>

</body>
</html>
