<?php

// index.php - TANPA session_start() karena sudah di config
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$page_title = 'Beranda';

// Handle pencarian
$search = isset($_GET['search']) ? trim(mysqli_real_escape_string($conn, $_GET['search'])) : '';
$search_condition = $search ? "AND (m.nama_menu LIKE '%$search%' OR m.deskripsi LIKE '%$search%' OR k.nama_kategori LIKE '%$search%')" : '';

// Ambil menu dari database (dengan filter pencarian jika ada)
$popular_menu_query = "SELECT m.*, k.nama_kategori 
                      FROM menu m 
                      LEFT JOIN kategori_menu k ON m.kategori_id = k.id 
                      WHERE m.status = 'tersedia' $search_condition
                      ORDER BY m.id DESC 
                      LIMIT 12";
$popular_menu = mysqli_query($conn, $popular_menu_query);
$popular_menu_items = [];
if ($popular_menu) {
    while ($row = mysqli_fetch_assoc($popular_menu)) {
        $popular_menu_items[] = $row;
    }
}

// Ambil kategori untuk navigasi
$kategori_query = "SELECT * FROM kategori_menu";
$kategori_result = mysqli_query($conn, $kategori_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root{--p:#9e1616;--pd:#7a0e0e;--s:#eb570d;--dk:#1a2332;--dk2:#2d4059;--lt:#f8f9fa;--tx:#222;--txl:#666;--sh:0 4px 24px rgba(0,0,0,0.1);--sh2:0 12px 40px rgba(0,0,0,0.18);--tr:all 0.3s cubic-bezier(.4,0,.2,1);--r:14px;}
        *{margin:0;padding:0;box-sizing:border-box;}
        html{scroll-behavior:smooth;}
        body{font-family:'Inter',sans-serif;color:var(--tx);background:#f4f5f7;-webkit-font-smoothing:antialiased;}
        .container{max-width:1240px;margin:0 auto;padding:0 24px;}
        a{text-decoration:none;color:inherit;}

        /* ── NAVBAR ── */
        .navbar{position:fixed;top:0;left:0;right:0;z-index:1000;transition:var(--tr);padding:16px 0;background:rgba(15,25,35,0.6);backdrop-filter:blur(8px);}
        .navbar.scrolled{background:rgba(15,25,35,0.97);backdrop-filter:blur(16px);padding:12px 0;box-shadow:0 2px 20px rgba(0,0,0,0.35);}
        .navbar .container{display:flex;justify-content:space-between;align-items:center;gap:20px;}
        .brand{font-size:1.4rem;font-weight:800;color:#fff;letter-spacing:-0.5px;}
        .brand span{color:#ff8c5a;}
        .nav-links{display:flex;align-items:center;gap:4px;list-style:none;}
        .nav-links a{color:rgba(255,255,255,0.85);padding:8px 14px;border-radius:8px;font-size:14px;font-weight:500;transition:var(--tr);}
        .nav-links a:hover{color:#fff;background:rgba(255,255,255,0.12);}
        .nav-btn{background:var(--p)!important;color:#fff!important;padding:9px 20px!important;border-radius:50px!important;}
        .nav-btn:hover{background:var(--pd)!important;transform:translateY(-1px);box-shadow:0 4px 16px rgba(158,22,22,0.4)!important;}
        .cart-pill{position:relative;display:flex;align-items:center;gap:6px;background:rgba(255,255,255,0.15);padding:8px 16px;border-radius:50px;color:#fff!important;font-size:14px;font-weight:600;}
        .cart-pill:hover{background:rgba(255,255,255,0.25)!important;}
        .cart-pill .badge{background:var(--s);color:#fff;border-radius:50%;width:18px;height:18px;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;}
        .hamburger{display:none;flex-direction:column;gap:5px;background:none;border:none;cursor:pointer;padding:4px;}
        .hamburger span{display:block;width:24px;height:2px;background:#fff;border-radius:2px;transition:var(--tr);}
        .hamburger.open span:nth-child(1){transform:translateY(7px) rotate(45deg);}
        .hamburger.open span:nth-child(2){opacity:0;}
        .hamburger.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg);}
        .profile-wrap{position:relative;}
        .profile-btn{display:flex;align-items:center;justify-content:center;background:none;border:none;border-radius:50%;padding:0;cursor:pointer;transition:var(--tr);box-shadow:0 0 0 2px transparent;}
        .profile-btn:hover{box-shadow:0 0 0 2px rgba(255,255,255,0.4);transform:scale(1.05);}
        .profile-btn img{width:38px;height:38px;border-radius:50%;object-fit:cover;}
        .profile-menu{position:absolute;right:0;top:calc(100% + 10px);background:#fff;border-radius:12px;box-shadow:var(--sh2);min-width:190px;overflow:hidden;display:none;}
        .profile-menu a{display:flex;align-items:center;gap:10px;padding:12px 16px;color:#333;font-size:14px;transition:background .2s;}
        .profile-menu a:hover{background:#fff8f5;}
        .profile-menu a i{color:var(--p);width:16px;}
        .profile-menu hr{border:none;border-top:1px solid #f0f0f0;}
        .profile-menu .logout-link{color:#dc3545;}
        .profile-menu .logout-link i{color:#dc3545;}

        /* ── HERO ── */
        .hero{min-height:100vh;background:linear-gradient(135deg,rgba(26,35,50,0.75) 0%,rgba(158,22,22,0.6) 100%),url('https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=1600&q=80&auto=format&fit=crop') center/cover no-repeat;display:flex;align-items:center;text-align:center;color:#fff;position:relative;overflow:hidden;}
        .hero::after{content:'';position:absolute;bottom:0;left:0;right:0;height:60px;background:linear-gradient(to top,#f4f5f7,transparent);}
        .hero-content{position:relative;z-index:1;padding:120px 0 80px;}
        .hero-tag{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.15);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.2);border-radius:50px;padding:8px 18px;font-size:13px;font-weight:600;margin-bottom:28px;animation:fadeUp .8s ease both;}
        .hero-tag i{color:#ff8c5a;}
        .hero h1{font-size:clamp(2.4rem,6vw,4rem);font-weight:800;line-height:1.1;margin-bottom:20px;letter-spacing:-1px;animation:fadeUp .8s ease .1s both;}
        .hero h1 span{color:#ff8c5a;}
        .hero p{font-size:clamp(1rem,2vw,1.25rem);opacity:.9;max-width:560px;margin:0 auto 36px;line-height:1.7;animation:fadeUp .8s ease .2s both;}
        .hero-btns{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;animation:fadeUp .8s ease .3s both;}
        .btn-hero-main{padding:15px 36px;background:var(--p);color:#fff;border-radius:50px;font-weight:700;font-size:1rem;transition:var(--tr);}
        .btn-hero-main:hover{background:var(--pd);transform:translateY(-3px);box-shadow:0 8px 28px rgba(158,22,22,0.45);}
        .btn-hero-out{padding:15px 36px;border:2px solid rgba(255,255,255,0.7);color:#fff;border-radius:50px;font-weight:600;font-size:1rem;transition:var(--tr);}
        .btn-hero-out:hover{background:rgba(255,255,255,0.15);border-color:#fff;}
        .hero-search-wrap{margin-top:42px;animation:fadeUp .8s ease .4s both;}
        .hero-search{display:flex;max-width:520px;margin:0 auto;background:#fff;border-radius:50px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.2);}
        .hero-search input{flex:1;padding:16px 22px;border:none;outline:none;font-size:15px;font-family:'Inter',sans-serif;color:#333;}
        .hero-search button{padding:14px 26px;background:var(--p);color:#fff;border:none;cursor:pointer;font-size:15px;transition:background .2s;}
        .hero-search button:hover{background:var(--pd);}
        .hero-stats{display:flex;gap:40px;justify-content:center;margin-top:56px;animation:fadeUp .8s ease .5s both;}
        .hero-stat{text-align:center;}
        .hero-stat strong{display:block;font-size:1.8rem;font-weight:800;color:#ff8c5a;}
        .hero-stat span{font-size:13px;opacity:.8;}

        /* ── TRUST BAR ── */
        .trust-bar{background:#fff;padding:20px 0;border-bottom:1px solid #eee;}
        .trust-items{display:flex;justify-content:center;align-items:center;gap:40px;flex-wrap:wrap;}
        .trust-item{display:flex;align-items:center;gap:10px;color:#555;font-size:14px;font-weight:500;}
        .trust-item i{color:var(--p);font-size:20px;}

        /* ── SECTIONS ── */
        section{padding:70px 0;}
        .sec-label{display:inline-block;background:rgba(158,22,22,0.08);color:var(--p);font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;padding:6px 14px;border-radius:50px;margin-bottom:14px;}
        .sec-title{font-size:clamp(1.6rem,3.5vw,2.2rem);font-weight:800;color:var(--dk);margin-bottom:10px;letter-spacing:-.5px;}
        .sec-sub{color:var(--txl);font-size:1rem;max-width:520px;}

        /* ── KATEGORI ── */
        .kat-scroll{display:flex;gap:14px;overflow-x:auto;padding-bottom:8px;scrollbar-width:none;}
        .kat-scroll::-webkit-scrollbar{display:none;}
        .kat-pill{flex-shrink:0;display:flex;align-items:center;gap:10px;background:#fff;border:2px solid #eee;border-radius:50px;padding:12px 22px;color:#555;font-size:14px;font-weight:600;cursor:pointer;transition:var(--tr);}
        .kat-pill:hover,.kat-pill.active{background:var(--p);border-color:var(--p);color:#fff;}
        .kat-pill i{font-size:16px;}

        /* ── MENU GRID ── */
        .menu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:24px;margin-top:32px;}
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

        /* ── PROMO BANNER ── */
        .promo-wrap{background:linear-gradient(135deg,#7a0e0e 0%,#9e1616 50%,#c0390d 100%);border-radius:24px;padding:60px 48px;display:flex;align-items:center;justify-content:space-between;gap:32px;overflow:hidden;position:relative;}
        .promo-wrap::before{content:'🔥';position:absolute;right:80px;top:50%;transform:translateY(-50%);font-size:180px;opacity:.08;}
        .promo-text h2{font-size:2rem;font-weight:800;color:#fff;margin-bottom:10px;}
        .promo-text p{color:rgba(255,255,255,.85);font-size:1rem;margin-bottom:24px;}
        .btn-promo{display:inline-block;background:#fff;color:var(--p);padding:14px 32px;border-radius:50px;font-weight:800;font-size:1rem;transition:var(--tr);}
        .btn-promo:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,0.2);}
        .promo-badge{background:rgba(255,255,255,.15);backdrop-filter:blur(8px);border-radius:20px;padding:28px 36px;text-align:center;color:#fff;flex-shrink:0;}
        .promo-badge strong{display:block;font-size:3rem;font-weight:900;line-height:1;}
        .promo-badge span{font-size:14px;opacity:.85;}

        /* ── FITUR ── */
        .fitur-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:24px;}
        .fitur-card{background:#fff;border-radius:var(--r);padding:32px 24px;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,0.06);transition:var(--tr);}
        .fitur-card:hover{transform:translateY(-4px);box-shadow:var(--sh);}
        .fitur-icon{width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,rgba(158,22,22,0.1),rgba(235,87,13,0.1));display:flex;align-items:center;justify-content:center;margin:0 auto 18px;}
        .fitur-icon i{font-size:26px;color:var(--p);}
        .fitur-card h3{font-size:1rem;font-weight:700;color:var(--dk);margin-bottom:8px;}
        .fitur-card p{font-size:13px;color:var(--txl);line-height:1.6;}

        /* ── TESTIMONI ── */
        .testi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px;}
        .testi-card{background:#fff;border-radius:var(--r);padding:28px;box-shadow:0 2px 12px rgba(0,0,0,0.06);position:relative;}
        .testi-card::before{content:'"';position:absolute;top:16px;right:24px;font-size:80px;color:var(--p);opacity:.08;font-family:Georgia,serif;line-height:1;}
        .testi-stars{color:#f59e0b;margin-bottom:14px;font-size:13px;}
        .testi-text{font-size:14px;color:#444;line-height:1.75;margin-bottom:20px;font-style:italic;}
        .testi-user{display:flex;align-items:center;gap:12px;}
        .testi-user img{width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid #f0f0f0;}
        .testi-user h4{font-size:14px;font-weight:700;color:var(--dk);margin-bottom:2px;}
        .testi-user p{font-size:12px;color:var(--txl);}
        .testi-verified{margin-left:auto;background:rgba(72,187,120,.1);color:#38a169;font-size:11px;font-weight:700;padding:3px 10px;border-radius:50px;display:flex;align-items:center;gap:4px;}

        /* ── FOOTER ── */
        footer{background:var(--dk);color:#ccc;padding:60px 0 0;}
        .footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px;margin-bottom:48px;}
        .footer-brand{font-size:1.3rem;font-weight:800;color:#fff;margin-bottom:14px;}
        .footer-brand span{color:#ff8c5a;}
        .footer-desc{font-size:14px;line-height:1.75;color:#aaa;margin-bottom:20px;}
        .footer-social{display:flex;gap:10px;}
        .footer-social a{width:36px;height:36px;background:rgba(255,255,255,.08);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#aaa;font-size:15px;transition:var(--tr);}
        .footer-social a:hover{background:var(--p);color:#fff;}
        footer h4{color:#fff;font-size:14px;font-weight:700;margin-bottom:18px;}
        footer ul{list-style:none;}
        footer ul li{margin-bottom:10px;}
        footer ul li a{color:#999;font-size:14px;transition:color .2s;}
        footer ul li a:hover{color:#ff8c5a;}
        footer ul li i{color:var(--p);margin-right:8px;width:14px;}
        .footer-bottom{border-top:1px solid rgba(255,255,255,.06);padding:20px 0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;}
        .footer-bottom p{font-size:13px;color:#666;}
        .footer-payments{display:flex;gap:10px;align-items:center;}
        .pay-badge{background:rgba(255,255,255,.08);color:#aaa;font-size:11px;font-weight:700;padding:4px 10px;border-radius:6px;}
        .pay-badge i{font-size:18px;}

        /* ── ANIMATIONS ── */
        @keyframes fadeUp{from{opacity:0;transform:translateY(28px);}to{opacity:1;transform:translateY(0);}}
        .reveal{opacity:0;transform:translateY(28px);transition:opacity .6s ease,transform .6s ease;}
        .reveal.visible{opacity:1;transform:translateY(0);}

        /* ── TOAST ── */
        .toast{position:fixed;bottom:28px;right:28px;background:var(--dk);color:#fff;padding:14px 22px;border-radius:12px;font-size:14px;font-weight:500;display:flex;align-items:center;gap:10px;box-shadow:var(--sh2);z-index:9999;transform:translateY(80px);opacity:0;transition:all .35s cubic-bezier(.4,0,.2,1);pointer-events:none;}
        .toast.show{transform:translateY(0);opacity:1;}
        .toast i{color:#4ade80;}

        /* ── MOBILE ── */
        @media(max-width:768px){
            .hamburger{display:flex;}
            .navbar-menu{display:none;position:absolute;top:100%;left:0;right:0;background:rgba(26,35,50,0.98);padding:20px;border-top:1px solid rgba(255,255,255,.1);}
            .navbar-menu.open{display:block;}
            .nav-links{flex-direction:column;align-items:flex-start;gap:4px;}
            .hero-stats{gap:24px;flex-wrap:wrap;}
            .promo-wrap{flex-direction:column;text-align:center;padding:40px 28px;}
            .promo-badge{width:100%;}
            .footer-grid{grid-template-columns:1fr 1fr;}
            .footer-bottom{flex-direction:column;text-align:center;}
            .trust-items{gap:20px;}
            .kat-pill{padding:10px 18px;}
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
        @media(max-width:480px){
            .footer-grid{grid-template-columns:1fr;}
            .hero-btns{flex-direction:column;align-items:center;}
        }
        /* Google Translate */
        .goog-te-banner-frame{display:none!important;}
        body{top:0!important;}
        #google_translate_element select{padding:6px 10px;border:1.5px solid rgba(255,255,255,.4);border-radius:20px;font-size:13px;background:rgba(255,255,255,.15);color:#fff;outline:none;cursor:pointer;}
    </style>

</head>
<body>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
    <div class="container">
        <a href="index.php" class="brand">🔥 <?php echo APP_NAME; ?></a>

        <button class="hamburger" id="hamburger" onclick="toggleNav()" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>

        <div class="navbar-menu" id="navbarMenu">
            <ul class="nav-links">
                <li><a href="index.php">Beranda</a></li>
                <li><a href="#menu-section">Menu</a></li>
                <li><a href="#fitur-section">Tentang</a></li>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <li>
                        <a href="user/keranjang.php" class="cart-pill">
                            <i class="fas fa-shopping-bag"></i>
                            <span class="badge"><?php echo countKeranjang($_SESSION['user_id']); ?></span>
                        </a>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                    <li><a href="admin/dashboard.php" class="nav-btn">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a></li>
                    <?php endif; ?>
                    <li>
                        <?php
                        $foto_nav = $_SESSION['foto_profil'] ?? '';
                        $avatar_nav = !empty($foto_nav)
                            ? 'assets/images/profil/' . $foto_nav
                            : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['nama_lengkap'] ?? 'User') . '&background=9e1616&color=fff&size=64';
                        ?>
                        <div class="profile-wrap">
                            <button class="profile-btn" onclick="toggleProfile()" id="profileBtn" title="<?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User'); ?>">
                                <img src="<?php echo $avatar_nav; ?>" alt="Avatar"
                                     onerror="this.src='https://ui-avatars.com/api/?name=User&background=9e1616&color=fff&size=64'">
                            </button>
                            <div class="profile-menu" id="profileMenu">
                                <a href="user/profil.php"><i class="fas fa-user"></i> Profil Saya</a>
                                <a href="user/pesanan_saya.php"><i class="fas fa-clipboard-list"></i> Pesanan Saya</a>
                                <hr>
                                <a href="auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </div>
                        </div>
                    </li>
                <?php else: ?>
                    <li><a href="auth/login.php" class="nav-btn">Masuk</a></li>
                <?php endif; ?>

                <li><div id="google_translate_element"></div></li>
            </ul>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <div class="hero-tag"><i class="fas fa-fire"></i> #1 Kedai Bakaran Terbaik di Desa Iwul, Parung</div>
            <h1>Cita Rasa Bakaran <span>Autentik</span><br>Yang Bikin Ketagihan</h1>
            <p>Nikmati kelezatan bakaran dengan bumbu rempah khas pilihan, dimasak langsung oleh chef berpengalaman kami.</p>
            <div class="hero-btns">
                <a href="#menu-section" class="btn-hero-main"><i class="fas fa-utensils"></i> Lihat Menu</a>
                <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="auth/register.php" class="btn-hero-out"><i class="fas fa-user-plus"></i> Daftar Gratis</a>
                <?php else: ?>
                <a href="user/keranjang.php" class="btn-hero-out"><i class="fas fa-shopping-bag"></i> Keranjang Saya</a>
                <?php endif; ?>
            </div>
            <div class="hero-search-wrap">
                <form method="GET" action="index.php" class="hero-search">
                    <input type="text" name="search" placeholder="Cari menu bakaran favoritmu..."
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
                <?php if ($search): ?>
                <p style="margin-top:12px;color:rgba(255,255,255,.85);font-size:14px;">
                    Hasil untuk: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
                    &nbsp;<a href="index.php" style="color:#ff8c5a;text-decoration:underline;">Hapus</a>
                </p>
                <?php endif; ?>
            </div>
            <div class="hero-stats">
                <div class="hero-stat"><strong>15+</strong><span>Menu Tersedia</span></div>
                <div class="hero-stat"><strong>10K+</strong><span>Pelanggan Puas</span></div>
                <div class="hero-stat"><strong>4.9★</strong><span>Rating Rata-rata</span></div>
                <div class="hero-stat"><strong>10 Mnt</strong><span>Waktu Saji</span></div>
            </div>
        </div>
    </div>
</section>

<!-- TRUST BAR -->
<div class="trust-bar">
    <div class="container">
        <div class="trust-items">
            <div class="trust-item"><i class="fas fa-shield-alt"></i> Pembayaran 100% Aman</div>
            <div class="trust-item"><i class="fas fa-clock"></i> Saji dalam 10 Menit</div>
            <div class="trust-item"><i class="fas fa-leaf"></i> Bahan Segar Setiap Hari</div>
            <div class="trust-item"><i class="fas fa-headset"></i> Layanan 7 Hari Seminggu</div>
            <div class="trust-item"><i class="fas fa-undo"></i> Garansi Kepuasan</div>
        </div>
    </div>
</div>

<!-- KATEGORI -->
<section id="kategori-menu" style="padding:50px 0 30px;">
    <div class="container">
        <div class="reveal">
            <span class="sec-label">Kategori</span>
            <h2 class="sec-title">Pilih Sesuai Seleramu</h2>
        </div>
        <div class="kat-scroll reveal" style="margin-top:24px;">
            <a href="user/index.php" class="kat-pill active"><i class="fas fa-th-large"></i> Semua Menu</a>
            <?php
            $has_kategori = $kategori_result && mysqli_num_rows($kategori_result) > 0;
            if ($has_kategori) {
                mysqli_data_seek($kategori_result, 0);
                while ($kat = mysqli_fetch_assoc($kategori_result)) { ?>
            <a href="user/index.php?kategori_id=<?php echo $kat['id']; ?>" class="kat-pill">
                <i class="fas <?php echo $kat['icon'] ?? 'fa-tag'; ?>"></i>
                <?php echo htmlspecialchars($kat['nama_kategori']); ?>
            </a>
            <?php } } else { ?>
            <span class="kat-pill"><i class="fas fa-utensils"></i> Makanan</span>
            <span class="kat-pill"><i class="fas fa-mug-hot"></i> Minuman</span>
            <span class="kat-pill"><i class="fas fa-pepper-hot"></i> Sambal</span>
            <?php } ?>

        </div>
    </div>
</section>

<!-- MENU -->
<section id="menu-section" style="padding:20px 0 70px;">
    <div class="container">
        <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px;" class="reveal">
            <div>
                <span class="sec-label">Menu Pilihan</span>
                <h2 class="sec-title">
                    <?php echo $search ? 'Hasil: "' . htmlspecialchars($search) . '"' : 'Menu Andalan Kami'; ?>
                </h2>
                <p class="sec-sub"><?php echo $search ? count($popular_menu_items) . ' menu ditemukan' : 'Dimasak dengan bahan segar berkualitas tinggi'; ?></p>
            </div>
            <a href="user/index.php" style="display:flex;align-items:center;gap:8px;color:var(--p);font-weight:700;font-size:14px;">
                Lihat Semua <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="menu-grid">
            <?php if (count($popular_menu_items) > 0): ?>
                <?php foreach ($popular_menu_items as $i => $menu): ?>
                <div class="menu-card reveal">
                    <div class="menu-card-img">
                        <img src="assets/images/menu/<?php echo htmlspecialchars($menu['gambar'] ?? 'default.jpg'); ?>"
                             alt="<?php echo htmlspecialchars($menu['nama_menu']); ?>"
                             onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=400&h=300&fit=crop&q=80'">
                        <?php if (($menu['stok'] ?? 0) == 0): ?>
                        <span class="menu-badge" style="background:#d49696;">Habis</span>
                        <?php elseif (($menu['stok'] ?? 0) <= 5): ?>
                        <span class="menu-badge" style="background:#f59e0b;">Hampir Habis</span>
                        <?php elseif ($i < 4): ?>
                        <span class="menu-badge">🔥 Terlaris</span>
                        <?php endif; ?>
                        <a href="user/detail_menu.php?id=<?php echo $menu['id']; ?>" class="btn-detail-overlay">
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
                                <a href="user/keranjang.php?add=<?php echo $menu['id']; ?>&ajax=1" class="btn-add" onclick="addToCart(event, this.href, '<?php echo htmlspecialchars($menu['nama_menu'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-shopping-bag"></i> Pesan
                                </a>
                                <?php else: ?>
                                <a href="auth/login.php" class="btn-add">
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
                <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:#999;">
                    <i class="fas fa-search" style="font-size:3rem;margin-bottom:16px;display:block;opacity:.3;"></i>
                    <p style="font-size:1.1rem;font-weight:600;">Menu tidak ditemukan</p>
                    <a href="index.php" style="color:var(--p);font-weight:700;margin-top:10px;display:inline-block;">Lihat semua menu →</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- PROMO -->
<section style="padding:0 0 70px;">
    <div class="container">
        <div class="promo-wrap reveal">
            <div class="promo-text">
                <h2><?php echo htmlspecialchars(APP_PROMO_TITLE); ?></h2>
                <p><?php echo htmlspecialchars(APP_PROMO_DESC); ?></p>
                <a href="<?php echo isset($_SESSION['user_id']) ? 'user/index.php' : 'auth/register.php'; ?>" class="btn-promo">
                    <?php echo isset($_SESSION['user_id']) ? 'Pesan Sekarang' : 'Daftar & Hemat'; ?>
                </a>
            </div>
            <div class="promo-badge">
                <strong><?php echo htmlspecialchars(APP_PROMO_DISCOUNT); ?></strong>
                <span>DISKON</span>
            </div>
        </div>
    </div>
</section>

<!-- FITUR -->
<section id="fitur-section" style="background:#fff;padding:70px 0;">
    <div class="container">
        <div style="text-align:center;margin-bottom:48px;" class="reveal">
            <span class="sec-label">Keunggulan Kami</span>
            <h2 class="sec-title">Kenapa Pilih <?php echo APP_NAME; ?>?</h2>
        </div>
        <div class="fitur-grid">
            <div class="fitur-card reveal">
                <div class="fitur-icon"><i class="fas fa-fire-alt"></i></div>
                <h3>Bumbu Khas Pilihan</h3>
                <p>Resep rahasia turun-temurun dengan rempah pilihan yang menghasilkan cita rasa autentik.</p>
            </div>
            <div class="fitur-card reveal">
                <div class="fitur-icon"><i class="fas fa-leaf"></i></div>
                <h3>Bahan Segar Setiap Hari</h3>
                <p>Kami memilih bahan baku segar setiap pagi langsung dari pasar lokal terpercaya.</p>
            </div>
            <div class="fitur-card reveal">
                <div class="fitur-icon"><i class="fas fa-clock"></i></div>
                <h3>Saji Cepat 30 Menit</h3>
                <p>Pesananmu diproses dan disajikan dalam waktu 30 menit setelah konfirmasi.</p>
            </div>
            <div class="fitur-card reveal">
                <div class="fitur-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>Pembayaran Aman</h3>
                <p>Berbagai metode pembayaran tersedia dengan sistem keamanan berlapis.</p>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONI -->
<?php
$testimoni_query = "SELECT * FROM testimoni ORDER BY id DESC LIMIT 3";
$testimoni_result = mysqli_query($conn, $testimoni_query);
$testimoni_data = [];
if ($testimoni_result) {
    while ($row = mysqli_fetch_assoc($testimoni_result)) {
        $testimoni_data[] = $row;
    }
}
?>
<section style="background:#f4f5f7;padding:70px 0;">
    <div class="container">
        <div style="text-align:center;margin-bottom:48px;" class="reveal">
            <span class="sec-label">Testimoni</span>
            <h2 class="sec-title">Kata Pelanggan Kami</h2>
            <p class="sec-sub" style="margin:0 auto;">Ribuan pelanggan puas mempercayakan cita rasa kepada kami.</p>
        </div>
        <div class="testi-grid">
            <?php if (count($testimoni_data) > 0): ?>
                <?php foreach ($testimoni_data as $testi): ?>
                <div class="testi-card reveal">
                    <div class="testi-stars">
                        <?php echo str_repeat('<i class="fas fa-star"></i>', (int)$testi['rating']); ?>
                    </div>
                    <p class="testi-text">"<?php echo htmlspecialchars($testi['komentar']); ?>"</p>
                    <div class="testi-user">
                        <?php 
                        if (!empty($testi['gambar'])):
                            $imgSrc = 'assets/images/testimoni/' . $testi['gambar'];
                        else:
                            // Auto generate avatar color based on name length
                            $colors = ['9e1616', 'eb570d', '1a2332', '2d4059'];
                            $bgColor = $colors[strlen($testi['nama_pelanggan']) % 4];
                            $imgSrc = 'https://ui-avatars.com/api/?name=' . urlencode($testi['nama_pelanggan']) . '&background=' . $bgColor . '&color=fff&size=88';
                        endif;
                        ?>
                        <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($testi['nama_pelanggan']); ?>" style="object-fit:cover;">
                        <div>
                            <h4><?php echo htmlspecialchars($testi['nama_pelanggan']); ?></h4>
                            <p><?php echo htmlspecialchars($testi['tipe_pelanggan']); ?></p>
                        </div>
                        <?php if ($testi['is_verified']): ?>
                        <span class="testi-verified"><i class="fas fa-check-circle"></i> Terverifikasi</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; color: #888; padding: 40px;">
                    Belum ada testimoni. Jadilah yang pertama memberikan ulasan!
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- FOOTER -->
<?php include 'includes/footer.php'; ?>

<!-- TOAST -->
<div class="toast" id="toast"><i class="fas fa-check-circle"></i><span id="toastMsg"></span></div>

<!-- BACK TO TOP -->
<button id="backTop" onclick="window.scrollTo({top:0,behavior:'smooth'})"
    style="position:fixed;bottom:24px;right:24px;width:46px;height:46px;background:var(--p);color:#fff;border:none;border-radius:50%;cursor:pointer;font-size:18px;box-shadow:0 4px 16px rgba(158,22,22,0.4);opacity:0;transition:all .3s;z-index:999;">
    <i class="fas fa-chevron-up"></i>
</button>

<script>
// Add to Cart via AJAX
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
                window.location.href = 'user/checkout.php';
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

// Navbar scroll
const nav = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 60);
    document.getElementById('backTop').style.opacity = window.scrollY > 300 ? '1' : '0';
});

// Hamburger
function toggleNav() {
    document.getElementById('navbarMenu').classList.toggle('open');
    document.getElementById('hamburger').classList.toggle('open');
}

// Profile dropdown
function toggleProfile() {
    const m = document.getElementById('profileMenu');
    m.style.display = m.style.display === 'block' ? 'none' : 'block';
}
document.addEventListener('click', e => {
    const btn = document.getElementById('profileBtn');
    const menu = document.getElementById('profileMenu');
    if (menu && btn && !btn.contains(e.target) && !menu.contains(e.target)) {
        menu.style.display = 'none';
    }
});

// Reveal on scroll
const reveals = document.querySelectorAll('.reveal');
const obs = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
}, { threshold: 0.12 });
reveals.forEach(r => obs.observe(r));

// Auto-scroll to results if searching
<?php if ($search): ?>
window.addEventListener('load', () => {
    const el = document.getElementById('menu-section');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
<?php endif; ?>
</script>

<!-- Google Translate -->
<script>
function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: 'id',
        includedLanguages: 'en,ar,zh-CN,ja,ko,fr,de,es,ms,hi',
        layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
        autoDisplay: false
    }, 'google_translate_element');
}
</script>
<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</body>
</html>
