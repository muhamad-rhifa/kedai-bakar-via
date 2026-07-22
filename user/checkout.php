<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user    = getUserById($user_id);

$keranjang_result = mysqli_query($conn,
    "SELECT k.*, m.nama_menu, m.harga, m.gambar
     FROM keranjang k JOIN menu m ON k.menu_id = m.id
     WHERE k.user_id = $user_id");
$keranjang_items = [];
$total = 0;
while ($row = mysqli_fetch_assoc($keranjang_result)) {
    $vData = json_decode($row['varian'] ?? '', true);
    if(is_array($vData)) {
        $row['harga'] += ($vData['extra_harga'] ?? 0);
        $row['varian_teks'] = $vData['teks'] ?? '';
    } else {
        $row['varian_teks'] = $row['varian'] ?? '';
    }
    $row['subtotal'] = $row['harga'] * $row['jumlah'];
    $total += $row['subtotal'];
    $keranjang_items[] = $row;
}

if (count($keranjang_items) === 0) {
    header("Location: keranjang.php");
    exit();
}

// Metode pembayaran kini menggunakan Midtrans secara otomatis
$metode_list = [];

$error = '';

if (isset($_POST['buat_pesanan'])) {
    $alamat    = mysqli_real_escape_string($conn, trim($_POST['alamat_pengiriman'] ?? ''));
    $catatan   = mysqli_real_escape_string($conn, trim($_POST['catatan'] ?? ''));

    if (empty($alamat)) {
        $error = "Alamat pengiriman wajib diisi.";
    } else {
        $nama_metode = 'Midtrans Payment Gateway';
        $no_pesanan  = 'KBV-' . date('Ymd') . '-' . str_pad(rand(1,9999), 4, '0', STR_PAD_LEFT);

        $q = "INSERT INTO pesanan (no_pesanan, user_id, total_harga, status_pesanan, status_pembayaran,
              metode_pembayaran, alamat_pengiriman, catatan, tanggal_pesanan)
              VALUES ('$no_pesanan', $user_id, $total, 'menunggu', 'belum_bayar',
              '$nama_metode', '$alamat', '$catatan', NOW())";

        if (mysqli_query($conn, $q)) {
            $pesanan_id = mysqli_insert_id($conn);
            foreach ($keranjang_items as $item) {
                $mid = (int)$item['menu_id'];
                $jml = (int)$item['jumlah'];
                $hrg = (int)$item['harga'];
                $var_teks = mysqli_real_escape_string($conn, $item['varian_teks']);
                mysqli_query($conn, "INSERT INTO detail_pesanan (pesanan_id, menu_id, jumlah, harga_satuan, varian)
                                     VALUES ($pesanan_id, $mid, $jml, $hrg, '$var_teks')");
            }
            mysqli_query($conn, "DELETE FROM keranjang WHERE user_id = $user_id");
            header("Location: detail_pesanan.php?id=$pesanan_id&success=1");
            exit();
        } else {
            $error = "Gagal membuat pesanan. Silakan coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <style>
        :root{--p:#9e1616;--pd:#7a0e0e;--s:#eb570d;--dk:#1a2332;--dk2:#2d4059;--lt:#f8f9fa;--tx:#222;--txl:#666;--sh:0 4px 24px rgba(0,0,0,0.1);--sh2:0 12px 40px rgba(0,0,0,0.18);--tr:all 0.3s cubic-bezier(.4,0,.2,1);--r:14px;}
        * { margin:0; padding:0; box-sizing:border-box; }
        html{scroll-behavior:smooth;}
        body { font-family:'Inter',sans-serif; background:#f4f5f7; color:var(--tx); -webkit-font-smoothing:antialiased; min-height:100vh; }
        .container { max-width:1240px; margin:0 auto; padding:0 24px; }
        a { text-decoration:none; color:inherit; }

        /* ── NAVBAR ── */
        .navbar{position:fixed;top:0;left:0;right:0;z-index:1000;transition:var(--tr);padding:12px 0;background:rgba(255,255,255,0.97);backdrop-filter:blur(16px);box-shadow:0 2px 20px rgba(0,0,0,0.08);}
        .navbar .container{display:flex;justify-content:space-between;align-items:center;gap:20px;}
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
            padding: 110px 0 40px;
            text-align: center;
        }
        .page-header h1 { font-size:1.6rem; font-weight:800; margin-bottom:6px; }
        .breadcrumb { font-size:13px; opacity:.8; }
        .breadcrumb a { color:white; text-decoration:none; }
        .breadcrumb a:hover { text-decoration:underline; }

        /* ── CHECKOUT LAYOUT ── */
        .checkout-grid { display:grid; grid-template-columns:1fr 380px; gap:24px; padding:40px 0 80px; }
        .card { background:white; border-radius:var(--r); padding:24px; box-shadow:var(--sh); margin-bottom:24px; border:1px solid #f0f0f0; }
        .card h3 { font-size:18px; font-weight:800; color:var(--dk); margin-bottom:20px; display:flex; align-items:center; gap:8px; }
        .card h3 i { color:var(--p); }
        
        .order-item { display:flex; gap:14px; padding:16px 0; border-bottom:1px solid #f5f5f5; align-items:center; }
        .order-item:last-child { border-bottom:none; padding-bottom:0; }
        .order-item img { width:70px; height:70px; object-fit:cover; border-radius:10px; flex-shrink:0; }
        .order-item-info { flex:1; }
        .order-item-info h4 { font-size:15px; font-weight:700; color:var(--dk); margin-bottom:4px; }
        .order-item-info span { font-size:13px; color:var(--txl); }
        .order-item-price { font-weight:800; color:var(--p); font-size:15px; white-space:nowrap; }

        .form-group { margin-bottom:20px; }
        .form-group label { display:block; font-size:13px; font-weight:700; margin-bottom:8px; color:var(--txl); text-transform:uppercase; letter-spacing:0.5px; }
        .form-group input, .form-group textarea { width:100%; padding:14px 16px; border:1.5px solid #eee; border-radius:10px; font-size:14px; transition:var(--tr); background:#f8f9fa; font-family:'Inter',sans-serif; }
        .form-group input:focus, .form-group textarea:focus { outline:none; border-color:var(--p); background:white; box-shadow:0 0 0 4px rgba(158,22,22,0.1); }
        
        .payment-options { display:flex; flex-direction:column; gap:16px; }
        
        .accordion-item { border:2px solid #eee; border-radius:12px; overflow:hidden; background:white; transition:var(--tr); }
        .accordion-header { padding:16px 20px; display:flex; justify-content:space-between; align-items:center; cursor:pointer; background:#f8f9fa; font-weight:700; font-size:15px; color:var(--dk); user-select:none; }
        .accordion-header:hover { background:#f0f0f0; }
        .accordion-header i { transition:transform 0.3s ease; }
        .accordion-item.active .accordion-header i { transform:rotate(180deg); }
        .accordion-body { display:none; padding:16px; border-top:1px solid #eee; }
        .accordion-item.active .accordion-body { display:block; }
        
        .method-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:12px; }
        .method-card { border:1.5px solid #e0e0e0; border-radius:10px; padding:12px; cursor:pointer; transition:var(--tr); text-align:center; position:relative; }
        .method-card:hover { border-color:var(--p); background:#fffaf9; }
        .method-card input[type="radio"] { display:none; }
        .method-card.selected { border-color:var(--p); background:#fffaf9; box-shadow:0 0 0 1px var(--p); }
        .method-card.selected::after { content:'\f058'; font-family:'Font Awesome 6 Free'; font-weight:900; color:var(--p); position:absolute; top:8px; right:8px; font-size:16px; }
        .method-logo { height:30px; object-fit:contain; margin-bottom:8px; max-width:100%; }
        .method-name { font-size:13px; font-weight:700; color:var(--dk); display:block; margin-bottom:4px; }
        .method-price { font-size:12px; color:var(--txl); }

        .summary-row { display:flex; justify-content:space-between; font-size:14px; padding:8px 0; border-bottom:1px dashed #eee; color:#555; }
        .summary-total { display:flex; justify-content:space-between; font-size:20px; font-weight:800; color:var(--p); margin-top:16px; padding-top:16px; border-top:2px dashed #f0f0f0; }
        
        .btn-order { display:block; width:100%; padding:16px; margin-top:24px; background:var(--p); color:white; border:none; border-radius:50px; font-size:16px; font-weight:800; cursor:pointer; transition:var(--tr); font-family:'Inter',sans-serif; }
        .btn-order:hover { background:var(--pd); transform:translateY(-2px); box-shadow:0 8px 24px rgba(158,22,22,0.3); }
        .btn-back { display:inline-block; margin-top:16px; color:var(--txl); font-size:14px; font-weight:600; text-decoration:none; text-align:center; width:100%; transition:var(--tr); }
        .btn-back:hover { color:var(--p); }
        
        .alert-error { background:#fee2e2; color:#b91c1c; padding:16px; border-radius:10px; margin-bottom:24px; font-size:14px; font-weight:600; display:flex; align-items:center; gap:10px; }
        .no-metode { background:#fff3cd; color:#856404; padding:16px; border-radius:10px; font-size:14px; display:flex; align-items:center; gap:10px; font-weight:600; }

        @media(max-width:900px){ .checkout-grid{grid-template-columns:1fr;} }
        @media(max-width:768px){
            .hamburger{display:flex;}
            .nav-links{display:none;position:absolute;top:100%;left:0;right:0;background:rgba(255,255,255,0.98);padding:20px;border-top:1px solid #f0f0f0;flex-direction:column;align-items:flex-start;box-shadow:0 8px 24px rgba(0,0,0,0.1);}
            .nav-links.open{display:flex;}
            .nav-links a{width:100%;padding:12px 16px;}
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="container">
            <a href="../index.php" class="brand">🔥 Kedai <span>Bakar Via</span></a>
            <button class="hamburger" id="hamburger" onclick="document.getElementById('navLinks').classList.toggle('open');">
                <span></span><span></span><span></span>
            </button>
            <div class="nav-links" id="navLinks">
                <a href="../index.php">Beranda</a>
                <a href="index.php">Menu</a>
                <a href="pesanan_saya.php">Pesanan</a>
                <a href="../auth/logout.php" style="color:#dc3545;">Logout</a>
                <a href="keranjang.php" class="cart-pill">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="badge"><?php echo countKeranjang($user_id); ?></span>
                </a>
            </div>
        </div>
    </nav>

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="container">
            <h1>Selesaikan Pesanan</h1>
            <div class="breadcrumb" style="justify-content:center;display:flex;gap:6px;">
                <a href="../index.php">Beranda</a> &rsaquo;
                <a href="keranjang.php">Keranjang</a> &rsaquo;
                <span style="opacity:0.8;">Checkout</span>
            </div>
        </div>
    </div>

<div class="container">
    <?php if ($error): ?>
        <div class="alert-error" style="margin-top:24px;"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="checkout-grid">
            <div>
                <!-- Pesanan -->
                <div class="card">
                    <h3><i class="fas fa-shopping-bag"></i> Pesanan Anda</h3>
                    <?php foreach ($keranjang_items as $item): ?>
                    <div class="order-item">
                        <img src="../assets/images/menu/<?php echo htmlspecialchars($item['gambar'] ?: 'default.jpg'); ?>"
                             onerror="this.onerror=null;this.src='https://placehold.co/70x70?text=Menu'">
                        <div class="order-item-info">
                            <h4><?php echo htmlspecialchars($item['nama_menu']); ?></h4>
                            <?php if(!empty($item['varian_teks'])): ?>
                            <div style="font-size:12px;color:#888;margin-bottom:4px;"><i class="fas fa-tags"></i> <?php echo htmlspecialchars($item['varian_teks']); ?></div>
                            <?php endif; ?>
                            <span>Rp <?php echo number_format($item['harga'],0,',','.'); ?> &times; <?php echo $item['jumlah']; ?></span>
                        </div>
                        <div class="order-item-price">Rp <?php echo number_format($item['subtotal'],0,',','.'); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pengiriman -->
                <div class="card">
                    <h3><i class="fas fa-map-marker-alt"></i> Informasi Pengiriman</h3>
                    <div class="form-group">
                        <label>No. Telepon *</label>
                        <input type="tel" name="no_telepon" placeholder="Contoh: 08123456789"
                               value="<?php echo htmlspecialchars($_POST['no_telepon'] ?? $user['no_telepon'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Alamat Pengiriman Lengkap *</label>
                        <input type="text" id="alamat_pengiriman" name="alamat_pengiriman" placeholder="Jalan, RT/RW, Patokan..."
                               value="<?php echo htmlspecialchars($_POST['alamat_pengiriman'] ?? $user['alamat'] ?? ''); ?>" required>
                        <button type="button" onclick="openGoogleMaps()" style="margin-top:8px;display:inline-flex;align-items:center;gap:6px;background:#4285F4;color:#fff;border:none;padding:8px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;transition:all .2s;">
                            <i class="fas fa-map-marker-alt"></i> Pilih dari Google Maps
                        </button>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Catatan Tambahan (Opsional)</label>
                        <textarea name="catatan" rows="3" placeholder="Contoh: tidak pedas, sambal dipisah..."><?php echo htmlspecialchars($_POST['catatan'] ?? ''); ?></textarea>
                    </div>
                </div>



            <!-- Ringkasan -->
            <div>
                <div class="card" style="position:sticky;top:90px;">
                    <h3><i class="fas fa-receipt"></i> Rincian Pembayaran</h3>
                    <?php foreach ($keranjang_items as $item): ?>
                    <div class="summary-row" style="flex-direction:column;">
                        <div style="display:flex;justify-content:space-between;">
                            <span><?php echo htmlspecialchars($item['nama_menu']); ?> <strong style="color:var(--dk);">×<?php echo $item['jumlah']; ?></strong></span>
                            <span style="font-weight:600;color:var(--dk);">Rp <?php echo number_format($item['subtotal'],0,',','.'); ?></span>
                        </div>
                        <?php if(!empty($item['varian_teks'])): ?>
                        <span style="font-size:12px;color:#888;">(<?php echo htmlspecialchars($item['varian_teks']); ?>)</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="summary-row" style="margin-top:12px;">
                        <span>Subtotal</span>
                        <span style="font-weight:700;">Rp <?php echo number_format($total,0,',','.'); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Biaya Layanan</span>
                        <span style="color:#16a34a;font-weight:700;">Gratis</span>
                    </div>
                    
                    <div class="summary-total">
                        <span>Total Pembayaran</span>
                        <span>Rp <?php echo number_format($total,0,',','.'); ?></span>
                    </div>
                    
                    <button type="submit" name="buat_pesanan" class="btn-order">
                        <i class="fas fa-lock" style="margin-right:6px;"></i> Bayar Sekarang
                    </button>
                    <a href="keranjang.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali ke Keranjang</a>
                </div>
            </div>
        </div>
    </form>
</div>


<script>
function openGoogleMaps() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                window.open('https://www.google.com/maps/search/?api=1&query=' + pos.coords.latitude + ',' + pos.coords.longitude, '_blank');
            },
            function() {
                window.open('https://maps.app.goo.gl/PKGap3UFvYqbBVeZ7', '_blank');
            }
        );
    } else {
        window.open('https://maps.app.goo.gl/PKGap3UFvYqbBVeZ7', '_blank');
    }
}
</script>

</body>
</html>
