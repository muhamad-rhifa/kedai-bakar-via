<?php
// user/keranjang.php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php'; // Diperlukan untuk countKeranjang()

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Tambah ke keranjang
if (isset($_GET['add']) || isset($_POST['add'])) {
    $menu_id = (int) ($_GET['add'] ?? $_POST['add']);
    $jumlah = (int) ($_GET['qty'] ?? $_POST['qty'] ?? 1);
    $varian_raw = $_GET['varian'] ?? $_POST['varian'] ?? '';
    $varian = mysqli_real_escape_string($conn, $varian_raw);
    
    $cek = mysqli_query($conn, "SELECT * FROM keranjang WHERE user_id = $user_id AND menu_id = $menu_id AND varian = '$varian'");
    if (mysqli_num_rows($cek) > 0) {
        mysqli_query($conn, "UPDATE keranjang SET jumlah = jumlah + $jumlah WHERE user_id = $user_id AND menu_id = $menu_id AND varian = '$varian'");
    } else {
        mysqli_query($conn, "INSERT INTO keranjang (user_id, menu_id, jumlah, varian) VALUES ($user_id, $menu_id, $jumlah, '$varian')");
    }

    if (isset($_GET['ajax']) || isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        exit();
    }

    header("Location: keranjang.php");
    exit();
}

// Update jumlah
if (isset($_POST['update'])) {
    $id = (int) $_POST['id'];
    $jumlah = max(1, (int) $_POST['jumlah']);
    mysqli_query($conn, "UPDATE keranjang SET jumlah = $jumlah WHERE id = $id AND user_id = $user_id");
    header("Location: keranjang.php");
    exit();
}

// Hapus satu item
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM keranjang WHERE id = $id AND user_id = $user_id");
    header("Location: keranjang.php");
    exit();
}

// Hapus semua item
if (isset($_GET['hapus_semua'])) {
    mysqli_query($conn, "DELETE FROM keranjang WHERE user_id = $user_id");
    header("Location: keranjang.php");
    exit();
}

// Ambil data keranjang
$keranjang_result = mysqli_query($conn, "SELECT k.*, m.nama_menu, m.harga, m.gambar 
                                   FROM keranjang k 
                                   JOIN menu m ON k.menu_id = m.id 
                                   WHERE k.user_id = $user_id");
$keranjang_items = [];
while ($row = mysqli_fetch_assoc($keranjang_result)) {
    $vData = json_decode($row['varian'] ?? '', true);
    if(is_array($vData)) {
        $row['harga'] += ($vData['extra_harga'] ?? 0);
        $row['varian_teks'] = $vData['teks'] ?? '';
    } else {
        $row['varian_teks'] = $row['varian'] ?? '';
    }
    $keranjang_items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - <?php echo APP_NAME; ?></title>
    <meta name="description" content="Keranjang belanja Anda di <?php echo APP_NAME; ?> — restoran bakaran terbaik">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

        /* ── LAYOUT ── */
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 24px;
            padding: 30px 0 60px;
        }

        /* ── SECTION CARD ── */
        .section-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .section-card-header {
            padding: 18px 24px;
            border-bottom: 1px solid #f0f0f0;
            display: flex; justify-content: space-between; align-items: center;
        }
        .section-card-header h2 {
            font-size: 16px; font-weight: 700; color: var(--dk);
            display:flex; align-items:center; gap:8px;
        }
        .section-card-header h2 i { color: var(--p); }
        .clear-cart {
            font-size: 13px; color: #dc3545; text-decoration: none;
            display:flex; align-items:center; gap:4px; transition:var(--transition);
        }
        .clear-cart:hover { opacity:.7; }

        /* ── CART ITEM ── */
        .cart-item {
            display: flex; gap:16px;
            padding: 20px 24px;
            border-bottom: 1px solid #f5f5f5;
            align-items: flex-start;
            transition: background 0.2s;
        }
        .cart-item:last-child { border-bottom: none; }
        .cart-item:hover { background: #fafafa; }
        .cart-item-img {
            width:88px; height:88px; object-fit:cover;
            border-radius:10px; flex-shrink:0;
        }
        .cart-item-body { flex:1; }
        .cart-item-name { font-size:15px; font-weight:700; color:var(--dk); margin-bottom:4px; }
        .cart-item-price { font-size:14px; color:var(--p); font-weight:600; margin-bottom:12px; }
        .qty-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .qty-control {
            display:flex; align-items:center;
            border:1.5px solid #e0e0e0; border-radius:8px; overflow:hidden;
        }
        .qty-btn {
            width:32px; height:32px; background:none; border:none;
            cursor:pointer; font-size:16px; font-weight:700; color:#555;
            transition:var(--transition);
            display:flex; align-items:center; justify-content:center;
        }
        .qty-btn:hover { background:var(--p); color:white; }
        .qty-input {
            width:42px; height:32px;
            border:none; border-left:1.5px solid #e0e0e0; border-right:1.5px solid #e0e0e0;
            text-align:center; font-size:14px; font-weight:600;
            font-family:'Inter',sans-serif;
        }
        .qty-input:focus { outline:none; }
        .btn-update {
            padding:5px 14px; background:var(--dk); color:white;
            border:none; border-radius:6px; font-size:12px; font-weight:600;
            cursor:pointer; transition:var(--transition);
            font-family:'Inter',sans-serif; display:flex; align-items:center; gap:4px;
        }
        .btn-update:hover { background:#1e2b3a; }
        .btn-hapus {
            display:flex; align-items:center; gap:4px;
            color:#dc3545; font-size:12px; font-weight:500;
            text-decoration:none; padding:5px 10px;
            border-radius:6px; transition:var(--transition);
        }
        .btn-hapus:hover { background:#fdf2f3; }
        .cart-item-subtotal {
            font-weight:800; color:var(--p); font-size:15px;
            white-space:nowrap; min-width:100px; text-align:right; padding-top:4px;
        }

        /* ── SUMMARY CARD ── */
        .summary-card {
            background:white; border-radius:var(--radius);
            box-shadow:var(--shadow); position:sticky; top:80px;
        }
        .summary-header {
            padding:18px 24px; border-bottom:1px solid #f0f0f0;
            font-size:16px; font-weight:700; color:var(--dk);
        }
        .summary-body { padding:20px 24px; }
        .summary-row {
            display:flex; justify-content:space-between;
            font-size:14px; padding:8px 0; color:#555;
            border-bottom:1px dashed #eee;
        }
        .summary-row:last-of-type { border-bottom:none; }
        .summary-total {
            display:flex; justify-content:space-between;
            font-size:18px; font-weight:800; color:var(--p);
            padding:14px 0 6px; border-top:2px solid #f0f0f0; margin-top:4px;
        }
        .btn-checkout {
            display:block; width:100%; padding:15px;
            background:var(--p); color:white; border:none;
            border-radius:50px; font-size:16px; font-weight:700;
            cursor:pointer; text-decoration:none; text-align:center;
            transition:var(--tr); font-family:'Inter',sans-serif;
            margin-top:16px;
        }
        .btn-checkout:hover { background:var(--pd); transform:translateY(-1px); box-shadow:0 4px 16px rgba(158,22,22,0.3); }
        .btn-continue {
            display:block; text-align:center; padding:11px;
            margin-top:10px; color:#555; font-size:14px;
            text-decoration:none; border:1.5px solid #e0e0e0;
            border-radius:8px; transition:var(--transition);
        }
        .btn-continue:hover { border-color:var(--p); color:var(--p); }
        .trust-badges {
            display:flex; justify-content:center; gap:16px;
            padding:14px 0 0; border-top:1px solid #f0f0f0; margin-top:12px;
        }
        .trust-badge {
            display:flex; flex-direction:column; align-items:center;
            gap:4px; font-size:11px; color:#888;
        }
        .trust-badge i { font-size:18px; color:#4caf50; }

        /* ── EMPTY STATE ── */
        .empty-state { text-align:center; padding:80px 20px; color:#aaa; }
        .empty-state i { font-size:72px; margin-bottom:20px; display:block; color:#ddd; }
        .empty-state h2 { font-size:1.4rem; color:#666; margin-bottom:8px; }
        .empty-state p { margin-bottom:20px; font-size:14px; }
        .btn-go-menu {
            display:inline-flex; align-items:center; gap:8px;
            padding:12px 28px; background:var(--p); color:white;
            border-radius:30px; text-decoration:none; font-weight:700;
            font-size:15px; transition:var(--tr);
        }
        .btn-go-menu:hover { background:var(--pd); transform:translateY(-2px); }

        /* ── RESPONSIVE ── */
        @media (max-width:900px) {
            .cart-layout { grid-template-columns:1fr; }
            .summary-card { position:static; }
        }
        @media (max-width:768px) {
            .hamburger{display:flex;}
            .nav-links{display:none;position:absolute;top:100%;left:0;right:0;background:rgba(255,255,255,0.98);padding:20px;border-top:1px solid #f0f0f0;flex-direction:column;align-items:flex-start;box-shadow:0 8px 24px rgba(0,0,0,0.1);}
            .nav-links.open{display:flex;}
            .nav-links a{width:100%;padding:12px 16px;}
        }
        @media (max-width:600px) {
            .cart-item { flex-direction:column; }
            .cart-item-img { width:100%; height:180px; border-radius:8px; }
            .cart-item-subtotal { text-align:left; min-width:unset; }
        }
    </style>
</head>
<body>

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
            <a href="keranjang.php" class="cart-pill active">
                <i class="fas fa-shopping-cart"></i>
                <span class="badge"><?php echo countKeranjang($user_id); ?></span>
            </a>
        </div>
    </div>
</nav>

<!-- PAGE HEADER -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-shopping-cart"></i> Keranjang Belanja</h1>
        <div class="breadcrumb" style="justify-content:center;display:flex;gap:6px;">
            <a href="../index.php">Beranda</a> &rsaquo;
            <a href="index.php">Menu</a> &rsaquo;
            <span style="opacity:0.8;">Keranjang</span>
        </div>
    </div>
</div>

<div class="container">
<?php if (count($keranjang_items) > 0):
    $total = 0;
    foreach ($keranjang_items as $item) $total += $item['harga'] * $item['jumlah'];
?>
<div class="cart-layout">

    <!-- KIRI: Daftar Item -->
    <div>
        <div class="section-card">
            <div class="section-card-header">
                <h2><i class="fas fa-list"></i> Item Pesanan (<?php echo count($keranjang_items); ?>)</h2>
                <a href="keranjang.php?hapus_semua=1" class="clear-cart"
                   onclick="return confirm('Kosongkan seluruh keranjang?')">
                    <i class="fas fa-trash-alt"></i> Kosongkan
                </a>
            </div>

            <?php foreach ($keranjang_items as $item):
                $subtotal = $item['harga'] * $item['jumlah'];
            ?>
            <div class="cart-item">
                <img class="cart-item-img"
                     src="../assets/images/menu/<?php echo htmlspecialchars($item['gambar'] ?: 'default.jpg'); ?>"
                     alt="<?php echo htmlspecialchars($item['nama_menu']); ?>"
                     onerror="this.onerror=null;this.src='https://placehold.co/88x88?text=Menu'">

                <div class="cart-item-body">
                    <div class="cart-item-name"><?php echo htmlspecialchars($item['nama_menu']); ?></div>
                    <?php if(!empty($item['varian_teks'])): ?>
                    <div style="font-size:12px;color:#888;margin-bottom:4px;"><i class="fas fa-tags"></i> <?php echo htmlspecialchars($item['varian_teks']); ?></div>
                    <?php endif; ?>
                    <div class="cart-item-price">Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?> / porsi</div>

                    <div class="qty-row">
                        <form method="POST" style="display:flex;align-items:center;gap:8px;">
                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                            <div class="qty-control">
                                <button type="button" class="qty-btn"
                                        onclick="let i=this.nextElementSibling;if(parseInt(i.value)>1){i.value=parseInt(i.value)-1;}">−</button>
                                <input class="qty-input" type="number" name="jumlah"
                                       value="<?php echo $item['jumlah']; ?>" min="1" max="99">
                                <button type="button" class="qty-btn"
                                        onclick="let i=this.previousElementSibling;if(parseInt(i.value)<99){i.value=parseInt(i.value)+1;}">+</button>
                            </div>
                            <input type="hidden" name="update" value="1">
                            <button type="submit" class="btn-update">
                                <i class="fas fa-sync-alt"></i> Update
                            </button>
                        </form>

                        <a href="keranjang.php?hapus=<?php echo $item['id']; ?>"
                           class="btn-hapus"
                           onclick="return confirm('Hapus item ini dari keranjang?')">
                            <i class="fas fa-trash"></i> Hapus
                        </a>
                    </div>
                </div>

                <div class="cart-item-subtotal">
                    Rp <?php echo number_format($subtotal, 0, ',', '.'); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- KANAN: Ringkasan Pesanan -->
    <div>
        <div class="summary-card">
            <div class="summary-header">
                <i class="fas fa-receipt" style="color:var(--p);margin-right:8px;"></i>
                Ringkasan Pesanan
            </div>
            <div class="summary-body">
                <?php foreach ($keranjang_items as $item): ?>
                <div class="summary-row" style="flex-direction:column;">
                    <div style="display:flex;justify-content:space-between;">
                        <span><?php echo htmlspecialchars($item['nama_menu']); ?> ×<?php echo $item['jumlah']; ?></span>
                        <span>Rp <?php echo number_format($item['harga'] * $item['jumlah'], 0, ',', '.'); ?></span>
                    </div>
                    <?php if(!empty($item['varian_teks'])): ?>
                    <span style="font-size:12px;color:#888;">(<?php echo htmlspecialchars($item['varian_teks']); ?>)</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <div class="summary-row" style="margin-top:6px;">
                    <span>Subtotal</span>
                    <span>Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                </div>
                <div class="summary-row">
                    <span>Biaya Layanan</span>
                    <span style="color:#4caf50;font-weight:600;">Gratis</span>
                </div>

                <div class="summary-total">
                    <span>Total</span>
                    <span>Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                </div>

                <a href="checkout.php" class="btn-checkout">
                    <i class="fas fa-lock"></i> Lanjut ke Checkout
                </a>
                <a href="index.php" class="btn-continue">
                    <i class="fas fa-arrow-left"></i> Lanjut Belanja
                </a>

                <div class="trust-badges">
                    <div class="trust-badge"><i class="fas fa-shield-alt"></i>Aman</div>
                    <div class="trust-badge"><i class="fas fa-clock"></i>Cepat</div>
                    <div class="trust-badge"><i class="fas fa-star"></i>Terpercaya</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<div class="empty-state" style="padding-top:60px;">
    <i class="fas fa-shopping-cart"></i>
    <h2>Keranjang Masih Kosong</h2>
    <p>Yuk, mulai pilih menu lezat dari <?php echo APP_NAME; ?>!</p>
    <a href="index.php" class="btn-go-menu">
        <i class="fas fa-utensils"></i> Lihat Menu
    </a>
</div>
<?php endif; ?>
</div>

</body>
</html>