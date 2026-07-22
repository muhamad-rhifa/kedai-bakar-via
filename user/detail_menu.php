<?php
// user/detail_menu.php - Halaman detail menu
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$menu_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$menu_id) { header('Location: index.php'); exit(); }

// Create menu_ulasan table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS menu_ulasan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_id INT,
    user_id INT,
    rating INT,
    ulasan TEXT,
    is_approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Cek hak ulasan
$can_review = false;
$user_id_logged = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($user_id_logged > 0) {
    $cek_beli = mysqli_query($conn, "SELECT 1 FROM pesanan p JOIN detail_pesanan dp ON p.id = dp.pesanan_id WHERE p.user_id = $user_id_logged AND dp.menu_id = $menu_id AND p.status_pesanan = 'selesai' LIMIT 1");
    if ($cek_beli && mysqli_num_rows($cek_beli) > 0) {
        $can_review = true;
    }
}

// Proses submit ulasan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_ulasan'])) {
    if ($can_review) {
        $rating = (int)$_POST['rating'];
        $ulasan = mysqli_real_escape_string($conn, $_POST['ulasan']);
        mysqli_query($conn, "INSERT INTO menu_ulasan (menu_id, user_id, rating, ulasan, is_approved) VALUES ($menu_id, $user_id_logged, $rating, '$ulasan', 0)");
        $ulasan_msg = "Ulasan berhasil dikirim dan sedang menunggu persetujuan Admin.";
    } else {
        $ulasan_err = "Anda harus membeli menu ini terlebih dahulu sebelum memberi ulasan.";
    }
}

// Ambil ulasan (hanya yg approved)
$ulasan_query = mysqli_query($conn, "SELECT u.*, us.nama_lengkap FROM menu_ulasan u JOIN users us ON u.user_id = us.id WHERE u.menu_id = $menu_id AND u.is_approved = 1 ORDER BY u.created_at DESC");
$ulasan_list = [];
if ($ulasan_query) {
    while($r = mysqli_fetch_assoc($ulasan_query)) {
        $ulasan_list[] = $r;
    }
}
if (!$menu_id) { header('Location: index.php'); exit(); }

$menu = getMenuById($menu_id);
if (!$menu) { header('Location: index.php'); exit(); }

// Ambil menu serupa (kategori sama, exclude current)
$related_query = mysqli_query($conn, "
    SELECT m.*, k.nama_kategori 
    FROM menu m 
    LEFT JOIN kategori_menu k ON m.kategori_id = k.id 
    WHERE m.status = 'tersedia' AND m.id != {$menu_id} AND m.kategori_id = " . (int)($menu['kategori_id'] ?? 0) . "
    ORDER BY RAND() LIMIT 4
");
$related = [];
if ($related_query) { while ($r = mysqli_fetch_assoc($related_query)) $related[] = $r; }

// Ambil gambar tambahan
$images_query = mysqli_query($conn, "SELECT gambar FROM menu_images WHERE menu_id = $menu_id");
$images = [];
$images[] = $menu['gambar']; // Gambar utama
if ($images_query) { while($row = mysqli_fetch_assoc($images_query)) { $images[] = $row['gambar']; } }

// Ambil varian
$var_query = mysqli_query($conn, "SELECT * FROM menu_variants WHERE menu_id = $menu_id ORDER BY grup, id");
$variant_groups = [];
if ($var_query) {
    while($row = mysqli_fetch_assoc($var_query)) {
        $variant_groups[$row['grup']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($menu['nama_menu']); ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <style>
        :root{--p:#9e1616;--pd:#7a0e0e;--s:#eb570d;--dk:#1a2332;--dk2:#2d4059;--lt:#f8f9fa;--tx:#222;--txl:#666;--sh:0 4px 24px rgba(0,0,0,0.1);--sh2:0 12px 40px rgba(0,0,0,0.18);--tr:all 0.3s cubic-bezier(.4,0,.2,1);--r:14px;}
        *{margin:0;padding:0;box-sizing:border-box;}
        html{scroll-behavior:smooth;}
        body{font-family:'Inter',sans-serif;background:#f4f5f7;color:var(--tx);-webkit-font-smoothing:antialiased;}
        .container{max-width:1240px;margin:0 auto;padding:0 24px;}
        a{text-decoration:none;color:inherit;}

        /* NAVBAR */
        .navbar{position:fixed;top:0;left:0;right:0;z-index:1000;padding:12px 0;background:rgba(255,255,255,0.97);backdrop-filter:blur(16px);box-shadow:0 2px 20px rgba(0,0,0,0.08);}
        .navbar .container{display:flex;justify-content:space-between;align-items:center;gap:20px;}
        .brand{font-size:1.4rem;font-weight:800;color:var(--dk);letter-spacing:-0.5px;}
        .nav-links{display:flex;align-items:center;gap:4px;list-style:none;}
        .nav-links a{color:var(--txl);padding:8px 14px;border-radius:8px;font-size:14px;font-weight:600;transition:var(--tr);}
        .nav-links a:hover,.nav-links a.active{color:var(--p);background:rgba(158,22,22,0.05);}
        .nav-btn{background:var(--p)!important;color:#fff!important;padding:9px 20px!important;border-radius:50px!important;}
        .nav-btn:hover{background:var(--pd)!important;transform:translateY(-1px);}
        .cart-pill{position:relative;display:flex;align-items:center;gap:6px;background:var(--p);padding:8px 16px;border-radius:50px;color:#fff!important;font-size:14px;font-weight:600;transition:var(--tr);}
        .cart-pill:hover{background:var(--pd)!important;}
        .cart-pill .badge{background:var(--s);color:#fff;border-radius:50%;width:18px;height:18px;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;}
        .hamburger{display:none;flex-direction:column;gap:5px;background:none;border:none;cursor:pointer;padding:4px;}
        .hamburger span{display:block;width:24px;height:2px;background:var(--dk);border-radius:2px;transition:var(--tr);}

        /* BREADCRUMB */
        .breadcrumb-bar{padding:100px 0 0;background:#fff;border-bottom:1px solid #eee;}
        .breadcrumb{display:flex;align-items:center;gap:8px;padding:16px 0;font-size:13px;color:var(--txl);flex-wrap:wrap;}
        .breadcrumb a{color:var(--txl);transition:color .2s;}
        .breadcrumb a:hover{color:var(--p);}
        .breadcrumb .sep{color:#ccc;}
        .breadcrumb .current{color:var(--dk);font-weight:600;}

        /* DETAIL LAYOUT */
        .detail-section{padding:40px 0 60px;background:#fff;}
        .detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:start;}

        /* IMAGE & GALLERY */
        .gallery-wrap { position:relative; }
        .detail-img-wrap{position:relative;border-radius:20px;overflow:hidden;background:#f8f9fa;aspect-ratio:1/1;margin-bottom:12px;}
        .detail-img-wrap img{width:100%;height:100%;object-fit:cover;transition:transform .5s ease;}
        .detail-img-wrap:hover img{transform:scale(1.04);}
        .detail-badge{position:absolute;top:16px;left:16px;padding:6px 16px;border-radius:50px;font-size:12px;font-weight:700;color:#fff;}
        .detail-badge.tersedia{background:var(--s);}
        .detail-badge.habis{background:#6c757d;}
        
        .gallery-thumbs { display:flex; gap:10px; overflow-x:auto; padding-bottom:8px; }
        .gallery-thumbs::-webkit-scrollbar { height:6px; }
        .gallery-thumbs::-webkit-scrollbar-thumb { background:#ccc; border-radius:4px; }
        .thumb-img { width:60px; height:60px; border-radius:12px; object-fit:cover; cursor:pointer; border:2px solid transparent; transition:var(--tr); opacity:0.6; }
        .thumb-img.active { border-color:var(--p); opacity:1; }
        .thumb-img:hover { opacity:1; }

        /* INFO */
        .detail-info{padding:8px 0;}
        .detail-cat{display:inline-block;background:rgba(158,22,22,0.08);color:var(--p);font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;padding:6px 16px;border-radius:50px;margin-bottom:16px;}
        .detail-name{font-size:clamp(1.6rem,3vw,2.2rem);font-weight:800;color:var(--dk);margin-bottom:12px;letter-spacing:-0.5px;line-height:1.2;}
        .detail-rating{display:flex;align-items:center;gap:8px;margin-bottom:20px;}
        .detail-rating .stars{color:#f59e0b;font-size:14px;}
        .detail-rating span{font-size:14px;color:var(--txl);font-weight:500;}
        .detail-price{font-size:2rem;font-weight:800;color:var(--p);margin-bottom:20px;}
        .detail-stock{display:inline-flex;align-items:center;gap:6px;font-size:14px;font-weight:600;margin-bottom:24px;padding:6px 14px;border-radius:50px;}
        .detail-stock.in{background:rgba(72,187,120,0.1);color:#38a169;}
        .detail-stock.out{background:rgba(220,53,69,0.1);color:#dc3545;}
        .detail-desc-title{font-size:15px;font-weight:700;color:var(--dk);margin-bottom:8px;}
        .detail-desc{font-size:14px;color:var(--txl);line-height:1.8;margin-bottom:28px;}
        
        /* VARIANTS */
        .variant-group { margin-bottom: 20px; }
        .variant-title { font-size:14px; font-weight:700; color:var(--dk); margin-bottom:8px; }
        .variant-options { display:flex; flex-wrap:wrap; gap:10px; }
        .variant-btn { padding:8px 16px; border:1.5px solid #e0e0e0; border-radius:8px; background:#fff; color:var(--tx); font-size:13px; font-weight:600; cursor:pointer; transition:var(--tr); user-select:none; }
        .variant-btn:hover { border-color:var(--p); color:var(--p); }
        .variant-btn.selected { border-color:var(--p); background:#fffaf9; color:var(--p); box-shadow:0 0 0 1px var(--p); }

        /* QTY + CART */
        .qty-row{display:flex;align-items:center;gap:16px;margin-bottom:20px;flex-wrap:wrap;}
        .qty-control{display:flex;align-items:center;border:2px solid #eee;border-radius:12px;overflow:hidden;}
        .qty-control button{width:44px;height:44px;background:#f8f9fa;border:none;font-size:18px;cursor:pointer;color:var(--dk);transition:background .2s;font-weight:600;}
        .qty-control button:hover{background:#eee;}
        .qty-control input{width:56px;height:44px;text-align:center;border:none;border-left:2px solid #eee;border-right:2px solid #eee;font-size:16px;font-weight:700;font-family:'Inter',sans-serif;outline:none;color:var(--dk);}
        .btn-cart{flex:1;min-width:200px;display:flex;align-items:center;justify-content:center;gap:10px;background:var(--p);color:#fff;padding:14px 32px;border-radius:12px;font-size:15px;font-weight:700;border:none;cursor:pointer;transition:var(--tr);font-family:'Inter',sans-serif;}
        .btn-cart:hover{background:var(--pd);transform:translateY(-2px);box-shadow:0 8px 24px rgba(158,22,22,0.35);}
        .btn-cart.disabled{background:#e5e7eb;color:#9ca3af;cursor:not-allowed;pointer-events:none;transform:none;box-shadow:none;}

        /* RELATED */
        .related-section{padding:60px 0 80px;background:#f4f5f7;}
        .related-title{font-size:1.5rem;font-weight:800;color:var(--dk);margin-bottom:32px;letter-spacing:-0.5px;}
        .menu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:24px;}
        .menu-card{background:#fff;border-radius:var(--r);overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.06);transition:var(--tr);display:block;}
        .menu-card:hover{transform:translateY(-6px);box-shadow:var(--sh2);}
        .menu-card-img{position:relative;overflow:hidden;height:200px;}
        .menu-card-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s ease;}
        .menu-card:hover .menu-card-img img{transform:scale(1.07);}
        .menu-badge{position:absolute;top:12px;left:12px;background:var(--s);color:#fff;font-size:11px;font-weight:700;padding:4px 12px;border-radius:50px;}
        .menu-badge.habis{background:#6c757d;}
        .menu-card-body{padding:16px 18px 18px;}
        .menu-cat-tag{font-size:11px;font-weight:700;color:var(--p);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px;}
        .menu-name{font-size:1rem;font-weight:700;color:var(--dk);margin-bottom:10px;line-height:1.3;}
        .menu-footer{display:flex;align-items:center;justify-content:space-between;}
        .menu-price{font-size:1.1rem;font-weight:800;color:var(--p);}

        /* TOAST */
        .toast{position:fixed;bottom:28px;right:28px;background:var(--dk);color:#fff;padding:14px 22px;border-radius:12px;font-size:14px;font-weight:600;display:flex;align-items:center;gap:10px;box-shadow:var(--sh2);z-index:9999;transform:translateY(80px);opacity:0;transition:all .35s cubic-bezier(.4,0,.2,1);pointer-events:none;}
        .toast.show{transform:translateY(0);opacity:1;}
        .toast i{color:#4ade80;}

        /* ANIMATIONS */
        @keyframes fadeUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
        .fade-in{animation:fadeUp .6s ease both;}
        .fade-in-d1{animation-delay:.1s;}
        .fade-in-d2{animation-delay:.2s;}
        .fade-in-d3{animation-delay:.3s;}

        /* RESPONSIVE */
        @media(max-width:768px){
            .hamburger{display:flex;}
            .nav-links{display:none;position:absolute;top:100%;left:0;right:0;background:rgba(255,255,255,0.98);padding:20px;border-top:1px solid #f0f0f0;flex-direction:column;align-items:flex-start;box-shadow:0 8px 24px rgba(0,0,0,0.1);}
            .nav-links.open{display:flex;}
            .detail-grid{grid-template-columns:1fr;gap:32px;}
            .detail-img-wrap{max-height:400px;}
            .qty-row{flex-direction:column;align-items:stretch;}
            .btn-cart{min-width:unset;}
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a href="../index.php" class="brand">🔥 <?php echo APP_NAME; ?></a>
        <button class="hamburger" onclick="document.getElementById('navLinks').classList.toggle('open')">
            <span></span><span></span><span></span>
        </button>
        <ul class="nav-links" id="navLinks">
            <li><a href="../index.php">Beranda</a></li>
            <li><a href="index.php">Menu</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="pesanan_saya.php">Pesanan</a></li>
                <li><a href="profil.php">Profil</a></li>
                <li><a href="../auth/logout.php" style="color:#dc3545;">Logout</a></li>
                <li>
                    <a href="keranjang.php" class="cart-pill">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="badge"><?php echo countKeranjang($_SESSION['user_id']); ?></span>
                    </a>
                </li>
            <?php else: ?>
                <li><a href="../auth/login.php" class="nav-btn">Masuk</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<!-- BREADCRUMB -->
<div class="breadcrumb-bar">
    <div class="container">
        <div class="breadcrumb">
            <a href="../index.php">Beranda</a>
            <span class="sep"><i class="fas fa-chevron-right" style="font-size:10px;"></i></span>
            <a href="index.php">Menu</a>
            <span class="sep"><i class="fas fa-chevron-right" style="font-size:10px;"></i></span>
            <a href="index.php?kategori_id=<?php echo $menu['kategori_id'] ?? ''; ?>"><?php echo htmlspecialchars($menu['nama_kategori'] ?? 'Menu'); ?></a>
            <span class="sep"><i class="fas fa-chevron-right" style="font-size:10px;"></i></span>
            <span class="current"><?php echo htmlspecialchars($menu['nama_menu']); ?></span>
        </div>
    </div>
</div>

<!-- DETAIL -->
<section class="detail-section">
    <div class="container">
        <div class="detail-grid">
            <!-- Image Gallery -->
            <div class="gallery-wrap fade-in">
                <div class="detail-img-wrap">
                    <img id="mainImage" src="../assets/images/menu/<?php echo htmlspecialchars($images[0] ?? 'default.jpg'); ?>"
                         alt="<?php echo htmlspecialchars($menu['nama_menu']); ?>"
                         onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=600&h=600&fit=crop&q=80'">
                    <?php if (($menu['stok'] ?? 0) > 0): ?>
                        <span class="detail-badge tersedia"><?php echo htmlspecialchars($menu['nama_kategori'] ?? 'Menu'); ?></span>
                    <?php else: ?>
                        <span class="detail-badge habis">Habis</span>
                    <?php endif; ?>
                </div>
                <?php if (count($images) > 1): ?>
                <div class="gallery-thumbs">
                    <?php foreach($images as $index => $img): ?>
                        <img src="../assets/images/menu/<?php echo htmlspecialchars($img); ?>" 
                             class="thumb-img <?php echo $index===0 ? 'active' : ''; ?>"
                             onclick="changeMainImage(this, '<?php echo htmlspecialchars($img); ?>')"
                             onerror="this.onerror=null;this.style.display='none'">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Info -->
            <div class="detail-info">
                <span class="detail-cat fade-in"><?php echo htmlspecialchars($menu['nama_kategori'] ?? 'Menu'); ?></span>
                <h1 class="detail-name fade-in fade-in-d1"><?php echo htmlspecialchars($menu['nama_menu']); ?></h1>
                <div class="detail-rating fade-in fade-in-d1">
                    <div class="stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                    </div>
                    <span>4.8 / 5</span>
                </div>
                <div class="detail-price fade-in fade-in-d2">Rp <?php echo number_format($menu['harga'] ?? 0, 0, ',', '.'); ?></div>
                
                <?php if (($menu['stok'] ?? 0) > 0): ?>
                    <div class="detail-stock in fade-in fade-in-d2"><i class="fas fa-check-circle"></i> Stok: <?php echo $menu['stok']; ?></div>
                <?php else: ?>
                    <div class="detail-stock out fade-in fade-in-d2"><i class="fas fa-times-circle"></i> Stok Habis</div>
                <?php endif; ?>

                <div class="fade-in fade-in-d3">
                    <p class="detail-desc-title">Deskripsi</p>
                    <p class="detail-desc"><?php echo nl2br(htmlspecialchars($menu['deskripsi'] ?? 'Menu lezat khas ' . APP_NAME . '. Dimasak dengan bumbu rempah pilihan dan bahan segar berkualitas tinggi.')); ?></p>
                </div>

                <!-- Varian -->
                <?php if (!empty($variant_groups)): ?>
                <div class="variants-wrap fade-in fade-in-d3" style="margin-bottom:24px;">
                    <?php foreach($variant_groups as $grup => $opsi): ?>
                    <div class="variant-group" data-grup="<?php echo htmlspecialchars($grup); ?>">
                        <div class="variant-title"><?php echo htmlspecialchars($grup); ?></div>
                        <div class="variant-options">
                            <?php foreach($opsi as $op): ?>
                            <div class="variant-btn" 
                                 data-nama="<?php echo htmlspecialchars($op['nama']); ?>" 
                                 data-harga="<?php echo $op['harga']; ?>"
                                 onclick="selectVariant(this, '<?php echo htmlspecialchars($grup); ?>')">
                                <?php echo htmlspecialchars($op['nama']); ?> 
                                <?php if($op['harga'] > 0) echo '(+Rp ' . number_format($op['harga'],0,',','.') . ')'; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Qty + Add to Cart -->
                <div style="margin-bottom: 16px;">
                    <span style="font-size:14px;font-weight:700;color:var(--text-color, var(--dk));margin-right:12px;">Jumlah:</span>
                    <div class="qty-control" style="display:inline-flex;">
                        <button type="button" onclick="changeQty(-1)">−</button>
                        <input type="number" id="qtyInput" value="1" min="1" max="<?php echo $menu['stok'] ?? 0; ?>" readonly>
                        <button type="button" onclick="changeQty(1)">+</button>
                    </div>
                </div>

                <div class="qty-row fade-in fade-in-d3" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <?php if (($menu['stok'] ?? 0) > 0): ?>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button class="btn-cart" id="btnCart" onclick="addToCartQty(false)" style="border-radius: 8px; justify-content: center; font-size: 14px; padding: 12px;">
                                <i class="fas fa-shopping-cart"></i> Tambah ke Keranjang
                            </button>
                            <button class="btn-cart" id="btnBuyNow" onclick="addToCartQty(true)" style="background: #f43f5e; border-radius: 8px; justify-content: center; font-size: 14px; padding: 12px;">
                                <i class="fas fa-bolt"></i> Beli Sekarang
                            </button>
                        <?php else: ?>
                            <a href="../auth/login.php" class="btn-cart" style="grid-column: span 2; justify-content: center; border-radius: 8px;">
                                <i class="fas fa-sign-in-alt"></i> Login untuk Memesan
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="btn-cart disabled" disabled style="grid-column: span 2; justify-content: center; border-radius: 8px;">
                            <i class="fas fa-ban"></i> Stok Habis
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- RELATED PRODUCTS -->
<?php if (count($related) > 0): ?>
<section class="related-section">
    <div class="container">
        <h2 class="related-title">Produk Serupa</h2>
        <div class="menu-grid">
            <?php foreach ($related as $rel): ?>
            <a href="detail_menu.php?id=<?php echo $rel['id']; ?>" class="menu-card">
                <div class="menu-card-img">
                    <img src="../assets/images/menu/<?php echo htmlspecialchars($rel['gambar'] ?? 'default.jpg'); ?>"
                         alt="<?php echo htmlspecialchars($rel['nama_menu']); ?>"
                         onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=400&h=300&fit=crop&q=80'">
                    <?php if (($rel['stok'] ?? 0) == 0): ?>
                        <span class="menu-badge habis">Habis</span>
                    <?php else: ?>
                        <span class="menu-badge"><?php echo htmlspecialchars($rel['nama_kategori'] ?? 'Menu'); ?></span>
                    <?php endif; ?>
                </div>
                <div class="menu-card-body">
                    <div class="menu-cat-tag"><?php echo htmlspecialchars($rel['nama_kategori'] ?? 'Menu'); ?></div>
                    <div class="menu-name"><?php echo htmlspecialchars($rel['nama_menu']); ?></div>
                    <div class="menu-footer">
                        <div class="menu-price">Rp <?php echo number_format($rel['harga'] ?? 0, 0, ',', '.'); ?></div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ULASAN SECTION -->
<!-- ULASAN SECTION -->
<style>
.review-wrapper { padding: 40px 0; border-top: 1px solid var(--border-color, #eee); }
.review-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 10px; }
.review-title { font-size: 20px; font-weight: 700; color: var(--text-color, #1a2332); margin: 0; }
.review-title span { color: #888; font-size: 16px; font-weight: normal; }
.review-stats { color: #f59e0b; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.review-stats span { color: var(--text-muted, #888); font-weight: normal; font-size: 13px; }

.review-list { display: flex; flex-direction: column; gap: 16px; margin-bottom: 30px; }
.review-card { background: var(--card-bg, #f8f9fa); border: 1px solid var(--border-color, #eee); border-radius: 12px; padding: 20px; transition: 0.3s; }
.review-card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
.review-user { display: flex; align-items: center; gap: 12px; }
.review-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background: #ddd; }
.review-user-info h4 { margin: 0; font-size: 14px; font-weight: 700; color: var(--text-color, #1a2332); }
.review-date { font-size: 12px; color: var(--text-muted, #888); }
.review-stars { color: #f59e0b; font-size: 12px; margin-top: 4px; }
.review-text { font-size: 14px; color: var(--text-color, #444); line-height: 1.6; margin: 0; }

.review-form-box { background: var(--card-bg, #fff); border: 1px solid var(--border-color, #eee); border-radius: 12px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
.review-locked { text-align: center; padding: 20px; background: var(--card-bg, #f8f9fa); border: 1px solid var(--border-color, #eee); border-radius: 12px; color: var(--text-color, #555); font-size: 14px; }
.review-locked a { color: var(--p, #9e1616); font-weight: 600; text-decoration: none; }

body.dark-mode .review-wrapper { --border-color: #333; --text-color: #f1f5f9; --text-muted: #94a3b8; --card-bg: #1e293b; background: transparent; }
body.dark-mode .review-form-box { background: #1e293b; border-color: #333; }
body.dark-mode .review-locked { background: #162032; border-color: #333; color: #cbd5e1; }
</style>

<section class="review-wrapper" id="ulasan">
    <div class="container">
        
        <?php if(isset($ulasan_msg)): ?>
            <div style="background:#dcfce7;color:#166534;padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:14px;font-weight:600;"><i class="fas fa-check-circle"></i> <?php echo $ulasan_msg; ?></div>
        <?php endif; ?>
        <?php if(isset($ulasan_err)): ?>
            <div style="background:#fee2e2;color:#b91c1c;padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:14px;font-weight:600;"><i class="fas fa-exclamation-circle"></i> <?php echo $ulasan_err; ?></div>
        <?php endif; ?>

        <?php
        // Hitung rata-rata
        $avg_query = mysqli_query($conn, "SELECT COUNT(*) as total, AVG(rating) as rata FROM menu_ulasan WHERE menu_id = $menu_id AND is_approved = 1");
        $avg_data = mysqli_fetch_assoc($avg_query);
        $total_ulasan = $avg_data['total'] ?? 0;
        $rata_rata = $avg_data['rata'] ? number_format($avg_data['rata'], 1) : '0.0';
        ?>

        <div class="review-header">
            <h2 class="review-title">Ulasan Pelanggan <span>(<?php echo $total_ulasan; ?>)</span></h2>
            <?php if($total_ulasan > 0): ?>
            <div class="review-stats">
                <?php echo $rata_rata; ?> <i class="fas fa-star"></i>
                <span>rata-rata dari <?php echo $total_ulasan; ?> ulasan</span>
            </div>
            <?php endif; ?>
        </div>

        <div class="review-list">
            <?php if(count($ulasan_list) > 0): ?>
                <?php foreach($ulasan_list as $ul): ?>
                    <?php 
                    $colors = ['9e1616', 'eb570d', '1a2332', '2d4059'];
                    $bgColor = $colors[strlen($ul['nama_lengkap']) % 4];
                    $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($ul['nama_lengkap']) . '&background=' . $bgColor . '&color=fff';
                    ?>
                    <div class="review-card">
                        <div class="review-card-top">
                            <div class="review-user">
                                <img src="<?php echo $avatar; ?>" class="review-avatar">
                                <div class="review-user-info">
                                    <h4><?php echo htmlspecialchars($ul['nama_lengkap']); ?></h4>
                                    <div class="review-stars">
                                        <?php echo str_repeat('<i class="fas fa-star"></i>', $ul['rating']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="review-date"><?php echo date('d M Y', strtotime($ul['created_at'])); ?></div>
                        </div>
                        <p class="review-text"><?php echo nl2br(htmlspecialchars($ul['ulasan'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="review-locked" style="background:transparent;border:none;">
                    <i class="fas fa-comment-slash" style="font-size:32px;color:var(--text-muted);margin-bottom:12px;display:block;"></i>
                    Belum ada ulasan untuk menu ini.
                </div>
            <?php endif; ?>
        </div>

        <?php if (!$can_review): ?>
            <div class="review-locked">
                <span style="color:var(--p);font-weight:600;">Selesaikan pesanan</span> untuk memberikan ulasan.
            </div>
        <?php else: ?>
            <div class="review-form-box" id="ulasan-form">
                <h3 style="font-size:16px;font-weight:700;margin-bottom:16px;color:var(--text-color);">Tulis Ulasan Anda</h3>
                <form method="POST" action="detail_menu.php?id=<?php echo $menu_id; ?>#ulasan">
                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-size:14px;font-weight:600;margin-bottom:8px;color:var(--text-color);">Rating Anda:</label>
                        <select name="rating" style="width:100%;padding:12px;border-radius:8px;border:1px solid var(--border-color,#eee);outline:none;background:var(--card-bg,#fff);color:var(--text-color);" required>
                            <option value="5">5 Bintang (Sangat Puas)</option>
                            <option value="4">4 Bintang (Puas)</option>
                            <option value="3">3 Bintang (Cukup)</option>
                            <option value="2">2 Bintang (Kurang)</option>
                            <option value="1">1 Bintang (Sangat Kurang)</option>
                        </select>
                    </div>
                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-size:14px;font-weight:600;margin-bottom:8px;color:var(--text-color);">Komentar:</label>
                        <textarea name="ulasan" rows="4" style="width:100%;padding:12px;border-radius:8px;border:1px solid var(--border-color,#eee);outline:none;font-family:inherit;font-size:14px;background:var(--card-bg,#fff);color:var(--text-color);" placeholder="Ceritakan pengalaman Anda..." required></textarea>
                    </div>
                    <button type="submit" name="submit_ulasan" style="background:var(--p);color:#fff;padding:12px 24px;border:none;border-radius:8px;font-weight:600;cursor:pointer;transition:0.3s;width:100%;">
                        Kirim Ulasan
                    </button>
                </form>
            </div>
        <?php endif; ?>

    </div>
</section>

<!-- TOAST -->
<div class="toast" id="toast"><i class="fas fa-check-circle"></i><span id="toastMsg"></span></div>

<script>
const basePrice = <?php echo (int)($menu['harga'] ?? 0); ?>;
const maxStock = <?php echo (int)($menu['stok'] ?? 0); ?>;
const menuId = <?php echo (int)$menu_id; ?>;
const menuName = <?php echo json_encode($menu['nama_menu']); ?>;

let variantExtraPrice = 0;
let selectedVariants = {};

function changeMainImage(thumb, imgName) {
    document.getElementById('mainImage').src = '../assets/images/menu/' + imgName;
    document.querySelectorAll('.thumb-img').forEach(el => el.classList.remove('active'));
    thumb.classList.add('active');
}

function selectVariant(btn, grup) {
    const isSelected = btn.classList.contains('selected');
    const parent = btn.closest('.variant-group');
    
    // Hilangkan semua pilihan di grup ini
    parent.querySelectorAll('.variant-btn').forEach(el => el.classList.remove('selected'));
    
    if (isSelected) {
        // Jika sebelumnya sudah terpilih, maka batalkan (deselect)
        delete selectedVariants[grup];
    } else {
        // Jika belum terpilih, tandai sebagai terpilih
        btn.classList.add('selected');
        selectedVariants[grup] = {
            nama: btn.getAttribute('data-nama'),
            harga: parseInt(btn.getAttribute('data-harga'))
        };
    }
    
    updatePrice();
}

function updatePrice() {
    variantExtraPrice = 0;
    for (let grup in selectedVariants) {
        variantExtraPrice += selectedVariants[grup].harga;
    }
    const totalPrice = basePrice + variantExtraPrice;
    document.querySelector('.detail-price').innerHTML = 'Rp ' + totalPrice.toLocaleString('id-ID');
}

function changeQty(delta) {
    const input = document.getElementById('qtyInput');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > maxStock) val = maxStock;
    input.value = val;
}

function addToCartQty(isBuyNow = false) {
    const qty = parseInt(document.getElementById('qtyInput').value);
    const btn = isBuyNow ? document.getElementById('btnBuyNow') : document.getElementById('btnCart');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menambahkan...';
    btn.style.pointerEvents = 'none';

    // Siapkan string varian json
    let varianJson = '';
    if (Object.keys(selectedVariants).length > 0) {
        let vArr = [];
        for (let grup in selectedVariants) {
            vArr.push(grup + ": " + selectedVariants[grup].nama);
        }
        let vData = {
            teks: vArr.join(", "),
            extra_harga: variantExtraPrice
        };
        // Jangan di-encodeURIComponent karena kita akan kirim via FormData POST
        varianJson = JSON.stringify(vData);
    }

    let formData = new FormData();
    formData.append('add', menuId);
    formData.append('qty', qty);
    formData.append('varian', varianJson);
    formData.append('ajax', 1);

    // Add items (send JSON varian data)
    fetch('keranjang.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
        .then(async r => {
            if (!r.ok) {
                const text = await r.text();
                throw new Error("HTTP " + r.status + ": " + text.substring(0, 50));
            }
            const text = await r.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error("Invalid JSON: " + text.substring(0, 50));
            }
        })
        .then(() => {
            if (isBuyNow) {
                window.location.href = 'checkout.php';
            } else {
                btn.innerHTML = '<i class="fas fa-check"></i> Ditambahkan!';
                showToast(menuName + ' ditambahkan ke keranjang');
                
                // Update badge cart jika ada
                const badges = document.querySelectorAll('.cart-pill .badge');
                badges.forEach(b => {
                    b.textContent = parseInt(b.textContent || 0) + 1;
                });

                setTimeout(() => { 
                    btn.innerHTML = orig; 
                    btn.style.pointerEvents = 'auto'; 
                }, 2000);
            }
        })
        .catch((e) => { 
            console.error(e);
            btn.innerHTML = orig; 
            btn.style.pointerEvents = 'auto'; 
            alert('Gagal: ' + e.message); 
        });
}

function showToast(msg) {
    const t = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

document.addEventListener('click', e => {
    if (!e.target.closest('.navbar')) document.getElementById('navLinks').classList.remove('open');
});
</script>
</body>
</html>
