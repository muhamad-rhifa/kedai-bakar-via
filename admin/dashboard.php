<?php
// admin/dashboard.php

require_once '../includes/db_connect.php';

// Cek apakah user sudah login dan adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Sinkronisasi status Midtrans untuk pesanan yang belum dibayar (Bypass Webhook Localhost)
require_once '../includes/functions.php';
$pending_orders = mysqli_query($conn, "SELECT id, no_pesanan FROM pesanan WHERE status_pembayaran = 'belum_bayar' AND status_pesanan != 'dibatalkan'");
if ($pending_orders) {
    while ($po = mysqli_fetch_assoc($pending_orders)) {
        $midtrans_status = checkMidtransStatus($po['no_pesanan']);
        if ($midtrans_status && in_array($midtrans_status['transaction_status'], ['capture', 'settlement'])) {
            mysqli_query($conn, "UPDATE pesanan SET status_pembayaran = 'sudah_bayar' WHERE id = " . $po['id']);
        }
    }
}

// Ambil statistik
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='user'"))['total'];
$total_menu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM menu"))['total'];
$total_pesanan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan"))['total'];
$total_pendapatan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_harga) as total FROM pesanan WHERE status_pembayaran='sudah_bayar'"))['total'];

// Ambil pesanan terbaru
$pesanan_terbaru = mysqli_query($conn, "SELECT p.*, u.nama_lengkap 
                                        FROM pesanan p 
                                        JOIN users u ON p.user_id = u.id 
                                        ORDER BY p.tanggal_pesanan DESC 
                                        LIMIT 5");

// Ambil menu populer
$menu_populer = mysqli_query($conn, "SELECT m.id, m.nama_menu, m.harga, COUNT(dp.id) as terjual 
                                     FROM detail_pesanan dp 
                                     JOIN menu m ON dp.menu_id = m.id 
                                     GROUP BY dp.menu_id, m.id, m.nama_menu, m.harga
                                     ORDER BY terjual DESC 
                                     LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <style>
        :root{--p:#9e1616;--pd:#7a0e0e;--s:#eb570d;--dk:#0f1923;--dk2:#1a2332;--tr:all 0.25s ease;--r:12px;}
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',sans-serif;-webkit-font-smoothing:antialiased;}
        body{background:#f0f2f5;display:flex;min-height:100vh;}

        /* ── SIDEBAR ── */
        .sidebar{width:260px;background:linear-gradient(180deg,#0f1923 0%,#1a2332 100%);color:#fff;height:100vh;position:fixed;left:0;top:0;overflow-y:auto;display:flex;flex-direction:column;z-index:100;}
        .sidebar::-webkit-scrollbar{width:4px;}
        .sidebar::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:2px;}
        .sidebar-header{padding:28px 24px 20px;border-bottom:1px solid rgba(255,255,255,.06);}
        .sidebar-logo{font-size:1.2rem;font-weight:800;color:#fff;margin-bottom:4px;}
        .sidebar-logo span{color:#ff8c5a;}
        .sidebar-role{font-size:11px;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:1px;}
        .sidebar-admin{display:flex;align-items:center;gap:12px;padding:16px 24px;background:rgba(255,255,255,.04);border-bottom:1px solid rgba(255,255,255,.06);}
        .sidebar-admin-avatar{width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,var(--p),var(--s));display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:800;color:#fff;flex-shrink:0;}
        .sidebar-admin-name{font-size:13px;font-weight:600;color:#fff;}
        .sidebar-admin-sub{font-size:11px;color:rgba(255,255,255,.4);}
        .sidebar-section{padding:16px 24px 6px;font-size:10px;font-weight:700;color:rgba(255,255,255,.25);text-transform:uppercase;letter-spacing:1.5px;}
        .sidebar-menu{padding:0 12px;flex:1;}
        .sidebar-menu a{display:flex;align-items:center;gap:12px;padding:11px 14px;color:rgba(255,255,255,.6);text-decoration:none;border-radius:10px;margin-bottom:2px;font-size:14px;font-weight:500;transition:var(--tr);}
        .sidebar-menu a i{width:20px;text-align:center;font-size:15px;}
        .sidebar-menu a:hover{background:rgba(255,255,255,.06);color:rgba(255,255,255,.9);}
        .sidebar-menu a.active{background:linear-gradient(135deg,rgba(158,22,22,.4),rgba(235,87,13,.2));color:#ff8c5a;font-weight:600;}
        .sidebar-menu a.active i{color:#ff8c5a;}
        .sidebar-logout{margin:12px;border:none;background:rgba(220,53,69,.12);border-radius:10px;padding:12px 14px;width:calc(100% - 24px);display:flex;align-items:center;gap:12px;color:rgba(220,100,100,.9);font-size:14px;font-weight:500;cursor:pointer;text-decoration:none;transition:var(--tr);}
        .sidebar-logout:hover{background:rgba(220,53,69,.2);color:#ff6b6b;}
        .sidebar-logout i{width:20px;text-align:center;}

        /* ── MAIN ── */
        .main-content{flex:1;margin-left:260px;padding:28px;min-height:100vh;min-width:0;}

        /* ── TOP NAV ── */
        .top-nav{background:#fff;border-radius:var(--r);box-shadow:0 1px 8px rgba(0,0,0,.06);padding:16px 24px;margin-bottom:28px;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:16px;}
        .page-title h1{font-size:1.3rem;font-weight:800;color:#1a2332;}
        .page-title p{font-size:13px;color:#888;margin-top:2px;}
        .user-info{display:flex;align-items:center;gap:12px;}
        .beranda-btn{display:flex;align-items:center;gap:7px;background:#1a2332;color:#fff;padding:9px 18px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;transition:var(--tr);}
        .beranda-btn:hover{background:#0f1923;}
        .logout-btn{display:flex;align-items:center;gap:7px;background:linear-gradient(135deg,var(--p),var(--s));color:#fff;padding:9px 18px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;transition:var(--tr);}
        .logout-btn:hover{opacity:.9;transform:translateY(-1px);}
        .top-date{font-size:13px;color:#888;}

        /* ── STAT CARDS ── */
        .cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-bottom:28px;}
        .card{border-radius:var(--r);padding:24px;display:flex;align-items:center;justify-content:space-between;cursor:default;transition:var(--tr);position:relative;overflow:hidden;}
        .card::after{content:'';position:absolute;right:-20px;bottom:-20px;width:100px;height:100px;border-radius:50%;background:rgba(255,255,255,.08);}
        .card:hover{transform:translateY(-4px);box-shadow:0 8px 28px rgba(0,0,0,.12);}
        .card:nth-child(1){background:linear-gradient(135deg,#9e1616,#c0390d);}
        .card:nth-child(2){background:linear-gradient(135deg,#1a2332,#2d4059);}
        .card:nth-child(3){background:linear-gradient(135deg,#0369a1,#0284c7);}
        .card:nth-child(4){background:linear-gradient(135deg,#059669,#10b981);}
        .card-info h3{font-size:12px;font-weight:700;color:rgba(255,255,255,.7);text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px;}
        .card-info p{font-size:2rem;font-weight:800;color:#fff;line-height:1;}
        .card-icon{width:54px;height:54px;background:rgba(255,255,255,.15);border-radius:14px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);}
        .card-icon i{font-size:24px;color:#fff;}

        /* ── GRID ── */
        .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px;}
        .box{background:#fff;border-radius:var(--r);padding:24px;box-shadow:0 1px 8px rgba(0,0,0,.06);}
        .box-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid #f0f0f0;}
        .box-header h2{font-size:15px;font-weight:700;color:#1a2332;display:flex;align-items:center;gap:8px;}
        .box-header h2 i{color:var(--p);}
        .box-header a{color:var(--p);text-decoration:none;font-size:13px;font-weight:600;display:flex;align-items:center;gap:4px;}
        .box-header a:hover{opacity:.75;}
        .box { overflow-x: auto; }

        /* ── TABLE ── */
        table{width:100%;border-collapse:collapse;}
        table th{text-align:left;padding:10px 12px;background:#f8f9fb;color:#888;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.6px;}
        table th:first-child{border-radius:8px 0 0 8px;}
        table th:last-child{border-radius:0 8px 8px 0;}
        table td{padding:12px;border-bottom:1px solid #f5f5f5;font-size:14px;color:#333;}
        table tr:last-child td{border-bottom:none;}
        table tr:hover td{background:#fafafa;}

        /* ── BADGES ── */
        .badge{padding:4px 12px;border-radius:50px;font-size:11px;font-weight:700;}
        .badge-success{background:#dcfce7;color:#15803d;}
        .badge-warning{background:#fef9c3;color:#a16207;}
        .badge-danger{background:#fee2e2;color:#b91c1c;}
        .badge-info{background:#dbeafe;color:#1d4ed8;}

        /* ── BTNS ── */
        .btn-small{padding:5px 12px;background:var(--p);color:#fff;text-decoration:none;border-radius:6px;font-size:12px;font-weight:600;transition:var(--tr);}
        .btn-small:hover{background:var(--pd);}

        /* ── RESPONSIVE ── */
        @media(max-width:900px){.grid-2{grid-template-columns:1fr;}}
        @media(max-width:768px){
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 1000;
                width: 260px;
                height: 100vh;
                bottom: auto;
            }
            .sidebar.show { transform: translateX(0); }
            .sidebar-logo, .sidebar-role, .sidebar-admin-name, .sidebar-admin-sub, .sidebar-section { display: block; }
            .sidebar-menu a span, .sidebar-logout span { display: inline; }
            .sidebar-admin { justify-content: flex-start; padding: 16px 24px; }
            .sidebar-menu a { justify-content: flex-start; padding: 11px 14px; }
            .sidebar-menu a i { width: 20px; font-size: 15px; margin-right: 12px; }
            .sidebar-logout { justify-content: flex-start; margin: 12px; width: calc(100% - 24px); }
            
            .main-content { margin-left: 0; padding: 16px; }
            .top-nav { flex-direction: column; align-items: flex-start; gap: 12px; }
            
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 999;
            }
            .sidebar-overlay.show { display: block; }
        }
        @media(max-width:480px){
            .cards{grid-template-columns:1fr; gap: 16px;}
            .box { padding: 16px; }
            table th, table td { padding: 10px; font-size: 13px; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <div class="top-nav">
            <div class="page-title">
                <h1><i class="fas fa-chart-pie" style="color:var(--p);margin-right:10px;"></i>Dashboard</h1>
                <p><?php echo date('l, d F Y'); ?> — Selamat datang kembali, <strong><?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Admin'); ?></strong></p>
            </div>
            <div class="user-info">
                <a href="../user/profil.php" class="beranda-btn" style="background:var(--dk2);">
                    <i class="fas fa-user"></i> <span>Profil</span>
                </a>
                <a href="../index.php" class="beranda-btn">
                    <i class="fas fa-home"></i> <span>Beranda</span>
                </a>
                <a href="../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Cards -->
        <div class="cards">
            <div class="card">
                <div class="card-info">
                    <h3>Total User</h3>
                    <p><?php echo $total_users ?: 0; ?></p>
                </div>
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            
            <div class="card">
                <div class="card-info">
                    <h3>Total Menu</h3>
                    <p><?php echo $total_menu ?: 0; ?></p>
                </div>
                <div class="card-icon">
                    <i class="fas fa-utensils"></i>
                </div>
            </div>
            
            <div class="card">
                <div class="card-info">
                    <h3>Total Pesanan</h3>
                    <p><?php echo $total_pesanan ?: 0; ?></p>
                </div>
                <div class="card-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
            
            <div class="card">
                <div class="card-info">
                    <h3>Pendapatan</h3>
                    <p>Rp <?php echo number_format($total_pendapatan ?: 0, 0, ',', '.'); ?></p>
                </div>
                <div class="card-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
        </div>

        <!-- Grid 2 Kolom -->
        <div class="grid-2">
            <!-- Pesanan Terbaru -->
            <div class="box">
                <div class="box-header">
                    <h2><i class="fas fa-history"></i> Pesanan Terbaru</h2>
                    <a href="kelola_pesanan.php">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>No. Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($pesanan_terbaru) > 0): ?>
                            <?php while ($p = mysqli_fetch_assoc($pesanan_terbaru)): ?>
                            <tr>
                                <td><?php echo $p['no_pesanan']; ?></td>
                                <td><?php echo $p['nama_lengkap']; ?></td>
                                <td>Rp <?php echo number_format($p['total_harga'], 0, ',', '.'); ?></td>
                                <td>
                                    <?php
                                    $badge = 'badge-warning'; // menunggu
                                    if ($p['status_pesanan'] == 'selesai')    $badge = 'badge-success';
                                    if ($p['status_pesanan'] == 'diproses')   $badge = 'badge-info';
                                    if ($p['status_pesanan'] == 'dibatalkan') $badge = 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $badge; ?>"><?php echo $p['status_pesanan']; ?></span>
                                </td>
                                <td>
                                    <a href="detail_pesanan.php?id=<?php echo $p['id']; ?>" class="btn-small">Detail</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #999;">Belum ada pesanan</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Menu Populer -->
            <div class="box">
                <div class="box-header">
                    <h2><i class="fas fa-chart-line"></i> Menu Populer</h2>
                    <a href="laporan.php">Lihat Laporan <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Menu</th>
                            <th>Harga</th>
                            <th>Terjual</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($menu_populer && mysqli_num_rows($menu_populer) > 0): ?>
                            <?php while ($m = mysqli_fetch_assoc($menu_populer)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($m['nama_menu']); ?></td>
                                <td>Rp <?php echo number_format($m['harga'], 0, ',', '.'); ?></td>
                                <td><span class="badge badge-success"><?php echo $m['terjual']; ?> porsi</span></td>
                                <td>
                                    <a href="kelola_menu.php" class="btn-small">Edit</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <?php
                            // Fallback: tampilkan menu terbaru jika belum ada penjualan
                            $fallback = mysqli_query($conn, "SELECT id, nama_menu, harga FROM menu WHERE status='tersedia' ORDER BY id DESC LIMIT 5");
                            if ($fallback && mysqli_num_rows($fallback) > 0):
                                while ($m = mysqli_fetch_assoc($fallback)):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($m['nama_menu']); ?></td>
                                <td>Rp <?php echo number_format($m['harga'], 0, ',', '.'); ?></td>
                                <td><span class="badge badge-info">-</span></td>
                                <td>
                                    <a href="kelola_menu.php" class="btn-small">Edit</a>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center;color:#999;padding:20px;">Belum ada data menu</td>
                            </tr>
                            <?php endif; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Info Cepat -->
        <div class="box">
            <div class="box-header">
                <h2><i class="fas fa-info-circle"></i> Informasi Cepat</h2>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <strong>Total Kategori:</strong><br>
                    <?php 
                    $kategori = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kategori_menu"));
                    echo $kategori['total'] ?: 0; 
                    ?> Kategori
                </div>
                
                <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <strong>Menu Tersedia:</strong><br>
                    <?php 
                    $tersedia = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM menu WHERE status='tersedia'"));
                    echo $tersedia['total'] ?: 0; 
                    ?> Menu
                </div>
                
                <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <strong>Menu Habis:</strong><br>
                    <?php 
                    $habis = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM menu WHERE status='habis'"));
                    echo $habis['total'] ?: 0; 
                    ?> Menu
                </div>
                
                <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <strong>Pesanan Hari Ini:</strong><br>
                    <?php 
                    $hari_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan WHERE DATE(tanggal_pesanan) = CURDATE()"));
                    echo $hari_ini['total'] ?: 0; 
                    ?> Pesanan
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 30px; padding: 20px; color: #999;">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            <p style="font-size: 12px;">Login sebagai: <?php echo htmlspecialchars($_SESSION['username'] ?? 'admin'); ?> (<?php echo htmlspecialchars($_SESSION['role'] ?? ''); ?>)</p>
        </div>
    </div>

    <script>
        // Logout langsung tanpa confirm dialog (lebih reliabel di semua browser)
        // Tidak ada JS yang memblokir navigasi ke logout.php
    </script>
</body>
</html>
