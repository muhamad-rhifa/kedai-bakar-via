<?php
// user/index.php - Halaman daftar menu
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Filter
$search      = isset($_GET['search'])      ? trim(mysqli_real_escape_string($conn, $_GET['search']))      : '';
$kategori_id = isset($_GET['kategori_id']) ? (int)$_GET['kategori_id']                                    : 0;
$sort        = isset($_GET['sort'])        ? $_GET['sort']                                                 : 'terbaru';

// Build WHERE
$where = "m.status = 'tersedia'";
if ($search)      $where .= " AND (m.nama_menu LIKE '%$search%' OR m.deskripsi LIKE '%$search%')";
if ($kategori_id) $where .= " AND m.kategori_id = $kategori_id";

// Sort
$order = match($sort) {
    'termurah'  => 'm.harga ASC',
    'termahal'  => 'm.harga DESC',
    'nama'      => 'm.nama_menu ASC',
    default     => 'm.id DESC'
};

// Ambil menu
$menu_result = mysqli_query($conn, "
    SELECT m.*, k.nama_kategori, k.icon
    FROM menu m
    LEFT JOIN kategori_menu k ON m.kategori_id = k.id
    WHERE $where
    ORDER BY $order
");
$menus = [];
while ($row = mysqli_fetch_assoc($menu_result)) $menus[] = $row;

// Ambil semua kategori
$kat_result = mysqli_query($conn, "SELECT * FROM kategori_menu ORDER BY nama_kategori");
$kategori_list = [];
while ($row = mysqli_fetch_assoc($kat_result)) $kategori_list[] = $row;

// Nama kategori aktif
$nama_kategori_aktif = '';
if ($kategori_id) {
    foreach ($kategori_list as $k) {
        if ($k['id'] == $kategori_id) { $nama_kategori_aktif = $k['nama_kategori']; break; }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nama_kategori_aktif ?: 'Semua Menu'; ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <style>
        :root{--p:#9e1616;--pd:#7a0e0e;--s:#eb570d;--dk:#1a2332;--dk2:#2d4059;--lt:#f8f9fa;--tx:#222;--txl:#666;--sh:0 4px 24px rgba(0,0,0,0.1);--sh2:0 12px 40px rgba(0,0,0,0.18);--tr:all 0.3s cubic-bezier(.4,0,.2,1);--r:14px;}
        * { margin:0; padding:0; box-sizing:border-box; }
        html{scroll-behavior:smooth;}
        body { font-family:'Inter',sans-serif; background:#f4f5f7; color:var(--tx); -webkit-font-smoothing:antialiased; }
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
        .nav-btn{background:var(--p)!important;color:#fff!important;padding:9px 20px!important;border-radius:50px!important;}
        .nav-btn:hover{background:var(--pd)!important;transform:translateY(-1px);box-shadow:0 4px 16px rgba(158,22,22,0.4)!important;}
        .cart-pill{position:relative;display:flex;align-items:center;gap:6px;background:var(--p);padding:8px 16px;border-radius:50px;color:#fff!important;font-size:14px;font-weight:600;transition:var(--tr);}
        .cart-pill:hover{background:var(--pd)!important;}
        .cart-pill .badge{background:var(--s);color:#fff;border-radius:50%;width:18px;height:18px;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;}
        .hamburger{display:none;flex-direction:column;gap:5px;background:none;border:none;cursor:pointer;padding:4px;}
        .hamburger span{display:block;width:24px;height:2px;background:var(--dk);border-radius:2px;transition:var(--tr);}

        /* ── HERO BANNER ── */
        .hero-banner {
            background: linear-gradient(135deg, rgba(26,35,50,0.85) 0%, rgba(158,22,22,0.8) 100%), url('https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=1600&q=80&auto=format&fit=crop') center/cover no-repeat;
            padding: 140px 0 60px;
            color: white;
            text-align: center;
        }
        .hero-banner h1 { font-size:clamp(2rem, 4vw, 2.8rem); font-weight:800; margin-bottom:12px; letter-spacing:-0.5px; }
        .hero-banner p { opacity:0.9; margin-bottom:30px; font-size:1.1rem; }
        .hero-search-wrap{max-width:520px;margin:0 auto;}
        .hero-search{display:flex;background:#fff;border-radius:50px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.2);}
        .hero-search input{flex:1;padding:16px 22px;border:none;outline:none;font-size:15px;font-family:'Inter',sans-serif;color:#333;}
        .hero-search button{padding:14px 26px;background:var(--p);color:#fff;border:none;cursor:pointer;font-size:15px;transition:background .2s;}
        .hero-search button:hover{background:var(--pd);}

        /* ── FILTER BAR ── */
        .filter-bar {
            background:white; border-bottom:1px solid #eee;
            padding:16px 0; position:sticky; top:64px; z-index:90;
            box-shadow:0 2px 8px rgba(0,0,0,0.04);
        }
        .filter-bar .container { display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
        .kat-scroll{display:flex;gap:10px;overflow-x:auto;padding-bottom:4px;scrollbar-width:none;flex:1;}
        .kat-scroll::-webkit-scrollbar{display:none;}
        .kat-pill{flex-shrink:0;display:flex;align-items:center;gap:8px;background:#f8f9fa;border:1.5px solid #eee;border-radius:50px;padding:10px 20px;color:#555;font-size:13.5px;font-weight:600;cursor:pointer;transition:var(--tr);}
        .kat-pill:hover{border-color:var(--p);color:var(--p);}
        .kat-pill.active{background:var(--p);border-color:var(--p);color:#fff;}
        .sort-select {
            padding:10px 16px; border:1.5px solid #eee; border-radius:50px;
            font-size:13.5px; font-weight:600; outline:none; cursor:pointer; background:#f8f9fa;
            color:#555; transition:var(--tr); font-family:'Inter',sans-serif;
        }
        .sort-select:focus { border-color:var(--p); }

        /* ── MAIN CONTENT ── */
        .main-content { padding:50px 0 80px; }
        .results-info {
            display:flex; justify-content:space-between; align-items:center;
            margin-bottom:30px; flex-wrap:wrap; gap:10px;
        }
        .results-info h2 { font-size:1.6rem; font-weight:800; color:var(--dk); letter-spacing:-0.5px; }
        .results-info span { color:var(--p); font-size:14px; font-weight:700; background:rgba(158,22,22,0.1); padding:6px 14px; border-radius:50px; }

        /* ── MENU GRID ── */
        .menu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:24px;}
        .menu-card{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.05);transition:var(--tr);position:relative;display:flex;flex-direction:column;border:1px solid #f0f0f0;}
        .menu-card:hover{transform:translateY(-6px);box-shadow:var(--sh2);}
        .menu-card-img{position:relative;overflow:hidden;height:180px;}
        .menu-card-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s ease;}
        .menu-card:hover .menu-card-img img{transform:scale(1.07);}
        .menu-badge{position:absolute;top:10px;left:10px;background:var(--s);color:#fff;font-size:11px;font-weight:700;padding:4px 10px;border-radius:8px;z-index:2;}
        .btn-detail-overlay{position:absolute;bottom:12px;left:50%;transform:translateX(-50%);background:#fff;color:var(--dk);font-size:12px;font-weight:700;padding:6px 16px;border-radius:50px;display:flex;align-items:center;gap:6px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:2;transition:var(--tr);white-space:nowrap;}
        .btn-detail-overlay:hover{background:var(--p);color:#fff;}
        .menu-card-body{padding:16px;flex:1;display:flex;flex-direction:column;}
        .menu-name{font-size:1.1rem;font-weight:800;color:var(--dk);margin-bottom:4px;line-height:1.3;}
        .menu-desc{font-size:13px;color:var(--txl);margin-bottom:12px;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;line-height:1.5;flex:1;}
        .menu-price-stock{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}
        .menu-price{font-size:1.15rem;font-weight:800;color:var(--p);}
        .menu-stock{font-size:12px;font-weight:700;}
        .menu-stock.text-p{color:var(--p);}
        .menu-stock.text-muted{color:#888;}
        .menu-footer{display:flex;align-items:center;justify-content:center;margin-top:auto;}
        .btn-add{display:flex;align-items:center;justify-content:center;gap:6px;background:var(--p);color:#fff;padding:10px;border-radius:12px;font-size:14px;font-weight:700;transition:var(--tr);cursor:pointer;width:100%;}
        .btn-add:hover{background:var(--pd);transform:translateY(-1px);}
        .btn-add.disabled{background:#d49696;color:#fff;cursor:not-allowed;pointer-events:none;}

        /* ── EMPTY STATE ── */
        .empty-state { text-align:center; padding:80px 20px; color:#aaa; grid-column:1/-1; }
        .empty-state i { font-size:64px; margin-bottom:20px; color:#ddd; }
        .empty-state h3 { font-size:1.4rem; font-weight:800; color:var(--dk); margin-bottom:8px; }
        .empty-state a { display:inline-block; margin-top:20px; padding:12px 28px; background:var(--p); color:white; border-radius:50px; font-weight:700; transition:var(--tr); }
        .empty-state a:hover { background:var(--pd); }

        /* ── TOAST ── */
        .toast{position:fixed;bottom:28px;right:28px;background:var(--dk);color:#fff;padding:14px 22px;border-radius:12px;font-size:14px;font-weight:600;display:flex;align-items:center;gap:10px;box-shadow:var(--sh2);z-index:9999;transform:translateY(80px);opacity:0;transition:all .35s cubic-bezier(.4,0,.2,1);pointer-events:none;}
        .toast.show{transform:translateY(0);opacity:1;}
        .toast i{color:#4ade80;}

        /* ── RESPONSIVE ── */
        @media(max-width:768px){
            .hamburger{display:flex;}
            .nav-links{display:none;position:absolute;top:100%;left:0;right:0;background:rgba(255,255,255,0.98);padding:20px;border-top:1px solid #f0f0f0;flex-direction:column;align-items:flex-start;box-shadow:0 8px 24px rgba(0,0,0,0.1);}
            .nav-links.open{display:flex;}
            .nav-links a{width:100%;padding:12px 16px;}
            .hero-banner{padding:120px 0 50px;}
            .menu-grid{grid-template-columns:repeat(2,1fr);gap:12px;}
            .menu-card-img{height:140px;}
            .menu-card-body{padding:12px;}
            .menu-name{font-size:14px;}
            .menu-desc{font-size:11px;-webkit-line-clamp:2;}
            .menu-price{font-size:14px;}
            .menu-stock{font-size:11px;}
            .btn-detail-overlay{font-size:11px;padding:5px 12px;bottom:8px;}
            .btn-add{padding:8px;font-size:13px;border-radius:10px;}
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a href="../index.php" class="brand">🔥 <?php echo APP_NAME; ?></a>
        <button class="hamburger" id="hamburger" onclick="toggleNav()">
            <span></span><span></span><span></span>
        </button>
        <ul class="nav-links" id="navLinks">
            <li><a href="../index.php">Beranda</a></li>
            <li><a href="index.php" class="active">Menu</a></li>
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
                <li><a href="../auth/login.php">Login</a></li>
                <li><a href="../auth/register.php" class="nav-btn">Daftar</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<!-- HERO BANNER -->
<div class="hero-banner">
    <div class="container">
        <h1><?php echo $nama_kategori_aktif ? '🍽️ ' . htmlspecialchars($nama_kategori_aktif) : 'Jelajahi Semua Menu'; ?></h1>
        <p><?php echo $search ? 'Hasil pencarian: "' . htmlspecialchars($search) . '"' : 'Temukan berbagai pilihan bakaran dan minuman favorit Anda.'; ?></p>
        <form method="GET" action="index.php" class="hero-search-wrap">
            <div class="hero-search">
                <?php if ($kategori_id): ?>
                    <input type="hidden" name="kategori_id" value="<?php echo $kategori_id; ?>">
                <?php endif; ?>
                <input type="text" name="search" placeholder="Cari menu favorit..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit"><i class="fas fa-search"></i> Cari</button>
            </div>
        </form>
    </div>
</div>

<!-- FILTER BAR -->
<div class="filter-bar">
    <div class="container">
        <div class="kat-scroll">
            <a href="index.php<?php echo $search ? '?search='.urlencode($search) : ''; ?>"
               class="kat-pill <?php echo !$kategori_id ? 'active' : ''; ?>">
                Semua
            </a>
            <?php foreach ($kategori_list as $k): ?>
            <a href="index.php?kategori_id=<?php echo $k['id']; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>"
               class="kat-pill <?php echo $kategori_id == $k['id'] ? 'active' : ''; ?>">
               <i class="fas <?php echo htmlspecialchars($k['icon']); ?>"></i> <?php echo htmlspecialchars($k['nama_kategori']); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <select class="sort-select" onchange="applySort(this.value)">
            <option value="terbaru"  <?php echo $sort=='terbaru'  ? 'selected':'' ?>>Terbaru</option>
            <option value="termurah" <?php echo $sort=='termurah' ? 'selected':'' ?>>Termurah</option>
            <option value="termahal" <?php echo $sort=='termahal' ? 'selected':'' ?>>Termahal</option>
            <option value="nama"     <?php echo $sort=='nama'     ? 'selected':'' ?>>A–Z</option>
        </select>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    <div class="container">
        <div class="results-info">
            <h2><?php echo $nama_kategori_aktif ?: 'Semua Menu'; ?></h2>
            <span><?php echo count($menus); ?> menu ditemukan</span>
        </div>

        <div class="menu-grid">
            <?php if (count($menus) > 0): ?>
                <?php foreach ($menus as $menu): ?>
                <div class="menu-card reveal">
                    <div class="menu-card-img">
                        <img src="../assets/images/menu/<?php echo htmlspecialchars($menu['gambar'] ?? 'default.jpg'); ?>"
                             alt="<?php echo htmlspecialchars($menu['nama_menu']); ?>"
                             onerror="this.onerror=null;this.src='https://placehold.co/400x200?text=<?php echo urlencode($menu['nama_menu']); ?>'">
                        <?php if (($menu['stok'] ?? 0) == 0): ?>
                        <span class="menu-badge" style="background:#d49696;">Habis</span>
                        <?php elseif (($menu['stok'] ?? 0) <= 5): ?>
                        <span class="menu-badge" style="background:#f59e0b;">Hampir Habis</span>
                        <?php endif; ?>
                        <a href="detail_menu.php?id=<?php echo $menu['id']; ?>" class="btn-detail-overlay">
                            <i class="fas fa-eye"></i> Lihat Detail
                        </a>
                    </div>
                    <div class="menu-card-body">
                        <div class="menu-name"><?php echo htmlspecialchars($menu['nama_menu']); ?></div>
                        <div class="menu-desc"><?php echo htmlspecialchars(substr($menu['deskripsi'] ?? 'Menu lezat khas ' . APP_NAME, 0, 90)); ?></div>
                        <div class="menu-price-stock">
                            <span class="menu-price">Rp <?php echo number_format($menu['harga'] ?? 0, 0, ',', '.'); ?></span>
                            <span class="menu-stock <?php echo (($menu['stok'] ?? 0) == 0) ? 'text-p' : 'text-muted'; ?>">
                                <?php echo (($menu['stok'] ?? 0) == 0) ? 'Habis' : 'Sisa ' . ($menu['stok'] ?? 0); ?>
                            </span>
                        </div>
                        <div class="menu-footer">
                            <?php if (($menu['stok'] ?? 0) > 0): ?>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="keranjang.php?add=<?php echo $menu['id']; ?>&ajax=1" class="btn-add" onclick="addToCart(event, this.href, '<?php echo htmlspecialchars($menu['nama_menu'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-shopping-bag"></i> Pesan
                                </a>
                                <?php else: ?>
                                <a href="../auth/login.php" class="btn-add">
                                    <i class="fas fa-shopping-bag"></i> Pesan
                                </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="btn-add disabled"><i class="fas fa-times-circle"></i> Stok Habis</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>Menu tidak ditemukan</h3>
                    <p><?php echo $search ? 'Coba kata kunci lain' : 'Belum ada menu di kategori ini'; ?></p>
                    <a href="index.php">Lihat Semua Menu</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- TOAST NOTIFICATION -->
<div class="toast" id="toast">
    <i class="fas fa-check-circle"></i>
    <span id="toastMsg"></span>
</div>

<script>
function toggleNav() {
    document.getElementById('navLinks').classList.toggle('open');
}

function applySort(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', val);
    window.location.href = url.toString();
}

function addToCart(e, url, menuName, isBuyNow = false) {
    e.preventDefault();
    
    const btn = e.currentTarget;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.style.pointerEvents = 'none';

    fetch(url)
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            if (isBuyNow) {
                window.location.href = 'checkout.php';
            } else {
                const toast = document.getElementById('toast');
                document.getElementById('toastMsg').textContent = menuName + ' ditambahkan ke keranjang';
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 3000);
                
                const badges = document.querySelectorAll('.cart-pill .badge');
                badges.forEach(b => {
                    b.textContent = parseInt(b.textContent) + 1;
                });
            }
        }
    })
    .catch(err => {
        console.error(err);
        alert('Gagal menambahkan ke keranjang');
    })
    .finally(() => {
        if (!isBuyNow) {
            btn.innerHTML = originalHtml;
            btn.style.pointerEvents = 'auto';
        }
    });
}

// Tutup nav saat klik di luar
document.addEventListener('click', function(e) {
    const nav = document.getElementById('navLinks');
    if (!e.target.closest('.navbar')) nav.classList.remove('open');
});
</script>
</body>
</html>
