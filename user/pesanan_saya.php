<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pesanan = mysqli_query($conn, "SELECT * FROM pesanan WHERE user_id = $user_id ORDER BY tanggal_pesanan DESC");
$total_pesanan = mysqli_num_rows($pesanan);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - <?php echo APP_NAME; ?></title>
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

        /* ── MAIN CONTENT ── */
        .main-content { padding:50px 0 80px; max-width:800px; margin:0 auto; }
        
        .pesanan-card {
            background: white; border-radius: var(--r);
            padding: 24px; margin-bottom: 24px;
            box-shadow: var(--sh); transition: var(--tr);
            border: 1px solid #f0f0f0;
        }
        .pesanan-card:hover { transform:translateY(-4px); box-shadow:var(--sh2); }
        
        .pesanan-header {
            display: flex; justify-content: space-between; align-items: flex-start;
            padding-bottom: 16px; border-bottom: 1px dashed #eee; margin-bottom: 16px;
        }
        
        .no-pesanan { font-size:16px; font-weight: 800; color: var(--p); display:block; margin-bottom:4px; }
        .tanggal { color: #888; font-size: 13px; display:flex; align-items:center; gap:6px; }
        
        .badge {
            padding: 6px 14px; border-radius: 50px; font-size: 12px; font-weight: 700;
            display:inline-flex; align-items:center; gap:6px;
        }
        .status-menunggu { background: #fff3cd; color: #856404; }
        .status-diproses { background: #e0f2fe; color: #0284c7; }
        .status-selesai { background: #dcfce7; color: #166534; }
        .status-dibatalkan { background: #fee2e2; color: #b91c1c; }
        
        .pesanan-detail {
            display: flex; justify-content: space-between; align-items: flex-end;
            margin-top: 10px;
        }
        
        .detail-info p { font-size:14px; color:#555; margin-bottom:6px; }
        .total { font-size: 20px; font-weight: 800; color: var(--dk); }
        
        .btn-detail {
            padding: 10px 24px; background: #f8f9fa; color: var(--dk);
            text-decoration: none; border-radius: 50px; font-size: 14px; font-weight:700;
            border: 1.5px solid #eee; transition: var(--tr);
        }
        .btn-detail:hover { background: var(--dk); color: white; border-color: var(--dk); }
        
        /* ── EMPTY STATE ── */
        .empty-state { text-align:center; padding:60px 20px; background:white; border-radius:var(--r); box-shadow:var(--sh); }
        .empty-state i { font-size:64px; margin-bottom:20px; color:#ddd; }
        .empty-state h3 { font-size:1.4rem; font-weight:800; color:var(--dk); margin-bottom:8px; }
        .empty-state p { color:var(--txl); font-size:14px; margin-bottom:24px; }
        .empty-state a { display:inline-block; padding:12px 28px; background:var(--p); color:white; border-radius:50px; font-weight:700; transition:var(--tr); }
        .empty-state a:hover { background:var(--pd); transform:translateY(-2px); }

        /* ── RESPONSIVE ── */
        @media (max-width:768px) {
            .hamburger{display:flex;}
            .nav-links{display:none;position:absolute;top:100%;left:0;right:0;background:rgba(255,255,255,0.98);padding:20px;border-top:1px solid #f0f0f0;flex-direction:column;align-items:flex-start;box-shadow:0 8px 24px rgba(0,0,0,0.1);}
            .nav-links.open{display:flex;}
            .nav-links a{width:100%;padding:12px 16px;}
            .pesanan-header { flex-direction: column; gap: 12px; }
            .pesanan-detail { flex-direction: column; align-items: flex-start; gap: 16px; }
            .btn-detail { width:100%; text-align:center; }
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
                <a href="pesanan_saya.php" class="active">Pesanan</a>
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
            <h1><i class="fas fa-clipboard-list"></i> Pesanan Saya</h1>
            <div class="breadcrumb" style="justify-content:center;display:flex;gap:6px;">
                <a href="../index.php">Beranda</a> &rsaquo;
                <span style="opacity:0.8;">Pesanan Saya</span>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="main-content">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
                <h2 style="font-size:1.2rem;font-weight:800;color:var(--dk);">Riwayat Pesanan</h2>
                <span style="background:#f0f0f0;padding:6px 14px;border-radius:50px;font-size:13px;font-weight:700;color:#555;"><?php echo $total_pesanan; ?> pesanan</span>
            </div>

            <?php if ($total_pesanan > 0): ?>
                <?php while ($p = mysqli_fetch_assoc($pesanan)): 
                    $status_class = 'status-' . $p['status_pesanan'];
                    $icon = match($p['status_pesanan']) {
                        'menunggu' => 'fa-clock',
                        'diproses' => 'fa-fire-burner',
                        'selesai' => 'fa-check-circle',
                        'dibatalkan' => 'fa-times-circle',
                        default => 'fa-info-circle'
                    };
                ?>
                <div class="pesanan-card">
                    <div class="pesanan-header">
                        <div>
                            <span class="no-pesanan">Pesanan #<?php echo htmlspecialchars($p['no_pesanan']); ?></span>
                            <span class="tanggal"><i class="far fa-calendar-alt"></i> <?php echo date('d M Y, H:i', strtotime($p['tanggal_pesanan'])); ?></span>
                        </div>
                        <span class="badge <?php echo $status_class; ?>">
                            <i class="fas <?php echo $icon; ?>"></i> <?php echo ucfirst($p['status_pesanan']); ?>
                        </span>
                    </div>
                    
                    <div class="pesanan-detail">
                        <div class="detail-info">
                            <p>Pembayaran: <strong><?php echo htmlspecialchars($p['metode_pembayaran'] ?: '-'); ?></strong> 
                               <span style="opacity:0.6;">(<?php echo htmlspecialchars($p['status_pembayaran']); ?>)</span>
                            </p>
                            <div class="total">Rp <?php echo number_format($p['total_harga'], 0, ',', '.'); ?></div>
                        </div>
                        <a href="detail_pesanan.php?id=<?php echo $p['id']; ?>" class="btn-detail">
                            Lihat Detail <i class="fas fa-chevron-right" style="font-size:11px;margin-left:4px;"></i>
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <h3>Belum Ada Pesanan</h3>
                    <p>Anda belum melakukan pemesanan apapun. Yuk, jelajahi menu lezat kami!</p>
                    <a href="index.php">Mulai Pesan Sekarang</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>