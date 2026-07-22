<?php
// user/profil.php - Halaman profil dengan dashboard premium
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check/migrasi kolom bio secara otomatis jika belum ada
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'bio'");
if (mysqli_num_rows($check_column) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN bio TEXT NULL");
}

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
$success = $error = '';

// Hitung Statistik
$order_count_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan WHERE user_id = $user_id");
$total_pesanan = mysqli_fetch_assoc($order_count_res)['total'] ?? 0;

$total_belanja_res = mysqli_query($conn, "SELECT SUM(total_harga) as total FROM pesanan WHERE user_id = $user_id AND status_pembayaran = 'sudah_bayar'");
$total_belanja = mysqli_fetch_assoc($total_belanja_res)['total'] ?? 0;

// Ambil Riwayat Pesanan
$riwayat_result = mysqli_query($conn, "SELECT * FROM pesanan WHERE user_id = $user_id ORDER BY tanggal_pesanan DESC LIMIT 10");
$riwayat_orders = [];
if ($riwayat_result) {
    while ($row = mysqli_fetch_assoc($riwayat_result)) {
        $riwayat_orders[] = $row;
    }
}

// Update foto profil
if (isset($_POST['update_foto'])) {
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        if (!in_array($_FILES['foto']['type'], $allowed)) {
            $error = "Format file harus JPG, PNG, atau GIF.";
        } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
            $error = "Ukuran file maksimal 2MB.";
        } else {
            $dir = '../assets/images/profil/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);

            // Hapus foto lama jika bukan default
            if (!empty($user['foto_profil']) && $user['foto_profil'] != 'default.png' && file_exists($dir . $user['foto_profil'])) {
                unlink($dir . $user['foto_profil']);
            }

            $ext      = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir . $filename)) {
                mysqli_query($conn, "UPDATE users SET foto_profil='$filename' WHERE id=$user_id");
                $_SESSION['foto_profil'] = $filename;
                $success = "Foto profil berhasil diperbarui!";
                $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
            } else {
                $error = "Gagal upload foto.";
            }
        }
    } else {
        $error = "Pilih file foto terlebih dahulu.";
    }
}

// Update data profil
if (isset($_POST['update'])) {
    $nama      = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $no_telp   = mysqli_real_escape_string($conn, $_POST['no_telepon']);
    $bio       = mysqli_real_escape_string($conn, $_POST['bio']);
    $alamat    = mysqli_real_escape_string($conn, $_POST['alamat']);

    mysqli_query($conn, "UPDATE users SET nama_lengkap='$nama', no_telepon='$no_telp', bio='$bio', alamat='$alamat' WHERE id=$user_id");
    $_SESSION['nama_lengkap'] = $nama;
    $success = "Profil berhasil diperbarui!";
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
}

// Update password
if (isset($_POST['update_password'])) {
    $pw_lama = $_POST['password_lama'];
    $pw_baru = $_POST['password_baru'];
    $pw_konf = $_POST['password_konfirmasi'];

    if (!password_verify($pw_lama, $user['password'])) {
        $error = "Password lama salah!";
    } elseif (strlen($pw_baru) < 6) {
        $error = "Password baru minimal 6 karakter!";
    } elseif ($pw_baru !== $pw_konf) {
        $error = "Konfirmasi password baru tidak cocok!";
    } else {
        $hashed = password_hash($pw_baru, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password='$hashed' WHERE id=$user_id");
        $success = "Password berhasil diubah!";
    }
}

$foto_src = !empty($user['foto_profil'])
    ? '../assets/images/profil/' . $user['foto_profil']
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['nama_lengkap']) . '&background=9e1616&color=fff&size=128';

$initials = '';
$words = explode(" ", $user['nama_lengkap']);
foreach ($words as $w) {
    if (!empty($w)) $initials .= strtoupper($w[0]);
}
$initials = substr($initials, 0, 2);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <style>
        :root{--p:#9e1616;--pd:#7a0e0e;--s:#eb570d;--dk:#1a2332;--dk2:#2d4059;--lt:#f8f9fa;--tx:#222;--txl:#666;--sh:0 4px 24px rgba(0,0,0,0.06);--sh2:0 12px 40px rgba(0,0,0,0.12);--tr:all 0.3s cubic-bezier(.4,0,.2,1);--r:16px;}
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

        /* ── BREADCRUMB ── */
        .breadcrumb-bar { padding: 90px 0 10px; background:#fff; border-bottom:1px solid #eee; }
        .breadcrumb { font-size:13px; color:var(--txl); display:flex; gap:6px; }
        .breadcrumb a { color:var(--txl); text-decoration:none; }
        .breadcrumb a:hover { color:var(--p); }

        /* ── LAYOUT ── */
        .profile-grid { display:grid; grid-template-columns:320px 1fr; gap:30px; padding:40px 0 80px; }
        .sidebar-col { display:flex; flex-direction:column; gap:24px; }
        .content-col { display:flex; flex-direction:column; gap:24px; }

        /* CARDS */
        .card { background:white; border-radius:var(--r); padding:24px; box-shadow:var(--sh); border:1px solid #f0f0f0; }
        
        /* SIDEBAR CARD 1: USER INFO */
        .user-info-card { text-align:center; position:relative; }
        .avatar-container { position:relative; display:inline-block; margin-bottom:16px; }
        .avatar-circle { width:100px; height:100px; border-radius:50%; background:var(--p); color:#fff; display:flex; align-items:center; justify-content:center; font-size:36px; font-weight:800; border:4px solid #fff; box-shadow:0 8px 24px rgba(0,0,0,0.1); overflow:hidden; }
        .avatar-circle img { width:100%; height:100%; object-fit:cover; }
        .avatar-upload-btn { position:absolute; bottom:2px; right:2px; background:var(--p); color:#fff; border:none; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:12px; border:2.5px solid #fff; transition:var(--tr); }
        .avatar-upload-btn:hover { background:var(--pd); transform:scale(1.1); }
        
        .user-name { font-size:18px; font-weight:800; color:var(--dk); margin-bottom:4px; }
        .user-email { font-size:13px; color:var(--txl); margin-bottom:12px; display:block; }
        .role-badge { display:inline-block; background:rgba(158,22,22,0.08); color:var(--p); font-size:11px; font-weight:700; padding:4px 14px; border-radius:50px; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:16px; }
        .join-date { font-size:12px; color:#999; display:block; border-top:1px solid #eee; padding-top:16px; }
        .join-date i { margin-right:4px; }

        /* SIDEBAR CARD 2: STATISTIK */
        .sidebar-card h3 { font-size:14px; font-weight:800; color:var(--dk); text-transform:uppercase; letter-spacing:0.8px; margin-bottom:16px; }
        .stat-list { display:flex; flex-direction:column; gap:14px; }
        .stat-item { display:flex; justify-content:space-between; align-items:center; font-size:14px; }
        .stat-label { display:flex; align-items:center; gap:10px; color:var(--txl); font-weight:500; }
        .stat-label i { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:14px; }
        .stat-label.pesanan i { background:rgba(158,22,22,0.05); color:var(--p); }
        .stat-label.belanja i { background:#ecfdf5; color:#059669; }
        .stat-value { font-weight:700; color:var(--dk); }
        .stat-value.currency { color:var(--p); }

        /* SIDEBAR CARD 3: MENU CEPAT */
        .quick-links { display:flex; flex-direction:column; gap:6px; }
        .quick-link { display:flex; align-items:center; gap:12px; padding:12px; border-radius:10px; font-size:14px; font-weight:600; color:#555; transition:var(--tr); }
        .quick-link i { color:#888; font-size:16px; width:20px; text-align:center; }
        .quick-link:hover { background:#f8f9fa; color:var(--p); }
        .quick-link:hover i { color:var(--p); }

        /* TABS PANEL */
        .tabs-header { display:flex; border-bottom:1px solid #eee; margin-bottom:24px; gap:8px; }
        .tab-btn { background:none; border:none; padding:12px 24px; font-size:14px; font-weight:700; color:var(--txl); cursor:pointer; position:relative; transition:var(--tr); border-radius:8px 8px 0 0; }
        .tab-btn:hover { color:var(--dk); }
        .tab-btn.active { color:var(--p); }
        .tab-btn.active::after { content:''; position:absolute; bottom:-1px; left:0; right:0; height:3px; background:var(--p); border-radius:3px; }

        .tab-content { display:none; }
        .tab-content.active { display:block; }

        /* FORMS */
        .form-section-title { font-size:16px; font-weight:800; color:var(--dk); margin-bottom:20px; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
        .form-group { margin-bottom:20px; }
        .form-group label { display:block; font-size:13px; font-weight:700; color:var(--txl); margin-bottom:8px; }
        .form-group input, .form-group textarea { width:100%; padding:12px 16px; border:1.5px solid #eee; border-radius:10px; font-size:14px; transition:var(--tr); background:#f8f9fa; font-family:'Inter',sans-serif; color:var(--tx); }
        .form-group input:focus, .form-group textarea:focus { outline:none; border-color:var(--p); background:white; box-shadow:0 0 0 4px rgba(158,22,22,0.1); }
        .form-group input[disabled], .form-group input[readonly] { background:#f1f5f9; color:#94a3b8; cursor:not-allowed; border-color:#e2e8f0; }
        .form-group small { display:block; margin-top:6px; color:#94a3b8; font-size:12px; }

        .btn-submit { background:var(--p); color:white; border:none; padding:12px 24px; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; transition:var(--tr); font-family:'Inter',sans-serif; }
        .btn-submit:hover { background:var(--pd); transform:translateY(-1px); box-shadow:0 4px 12px rgba(158,22,22,0.25); }

        /* ZONA BERBAHAYA */
        .danger-card { border:1px solid #fee2e2; background:#ffff; }
        .danger-card h3 { color:#b91c1c; font-size:14px; font-weight:800; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:12px; }
        .danger-box { display:flex; justify-content:space-between; align-items:center; gap:20px; flex-wrap:wrap; }
        .danger-desc h4 { font-size:14px; font-weight:700; color:var(--dk); margin-bottom:4px; }
        .danger-desc p { font-size:13px; color:var(--txl); }
        .btn-danger { background:#fff; color:#dc2626; border:1.5px solid #fca5a5; padding:10px 20px; border-radius:30px; font-size:13px; font-weight:700; cursor:pointer; transition:var(--tr); font-family:'Inter',sans-serif; }
        .btn-danger:hover { background:#fee2e2; border-color:#ef4444; color:#b91c1c; }

        /* ALERTS */
        .alert-success { background:#dcfce7; color:#166534; padding:16px; border-radius:12px; margin-bottom:24px; font-size:14px; font-weight:600; display:flex; align-items:center; gap:8px; border:1px solid #bbf7d0; }
        .alert-error { background:#fee2e2; color:#b91c1c; padding:16px; border-radius:12px; margin-bottom:24px; font-size:14px; font-weight:600; display:flex; align-items:center; gap:8px; border:1px solid #fecaca; }

        /* RIWAYAT TABLE */
        .riwayat-table-wrap { overflow-x:auto; }
        .riwayat-table { width:100%; border-collapse:collapse; text-align:left; font-size:14px; }
        .riwayat-table th { padding:14px 16px; background:#f8f9fa; color:var(--txl); font-weight:700; border-bottom:2px solid #eee; }
        .riwayat-table td { padding:14px 16px; border-bottom:1px solid #eee; color:#444; }
        .riwayat-table tr:hover td { background:#fcfcfc; }
        .status-pill { display:inline-block; padding:4px 12px; border-radius:50px; font-size:11px; font-weight:700; text-transform:uppercase; }
        .status-pill.menunggu { background:#fef3c7; color:#d97706; }
        .status-pill.diproses { background:#e0f2fe; color:#0284c7; }
        .status-pill.selesai { background:#dcfce7; color:#166534; }
        .status-pill.dibatalkan { background:#fee2e2; color:#991b1b; }
        
        .btn-view { display:inline-flex; align-items:center; gap:6px; color:var(--p); font-weight:700; font-size:13px; text-decoration:none; }
        .btn-view:hover { text-decoration:underline; }

        /* Modal upload foto */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px); z-index:1000; align-items:center; justify-content:center; }
        .modal-overlay.show { display:flex; }
        .modal-box { background:white; border-radius:16px; padding:32px; width:90%; max-width:400px; text-align:center; box-shadow:var(--sh2); animation:modalFadeIn 0.3s ease; }
        @keyframes modalFadeIn { from{opacity:0;transform:translateY(20px);} to{opacity:1;transform:translateY(0);} }
        .modal-box h3 { font-size:20px; font-weight:800; margin-bottom:20px; color:var(--dk); display:flex; align-items:center; justify-content:center; gap:8px; }
        .modal-box input[type="file"] { width:100%; padding:14px; border:2px dashed #ddd; border-radius:10px; margin-bottom:20px; cursor:pointer; background:#f8f9fa; transition:var(--tr); }
        .modal-box input[type="file"]:hover { border-color:var(--p); background:#fff5f2; }
        .preview-img { width:120px; height:120px; border-radius:50%; object-fit:cover; margin:0 auto 24px; display:none; border:4px solid var(--p); box-shadow:0 8px 24px rgba(158,22,22,0.2); }
        
        .modal-actions { display:flex; gap:12px; }
        .btn-upload { flex:1; padding:12px; background:var(--p); color:white; border:none; border-radius:50px; cursor:pointer; font-size:14px; font-weight:700; transition:var(--tr); font-family:'Inter',sans-serif; }
        .btn-upload:hover { background:var(--pd); transform:translateY(-1px); }
        .btn-cancel { flex:1; padding:12px; background:#f1f5f9; color:var(--dk); border:none; border-radius:50px; cursor:pointer; font-size:14px; font-weight:700; transition:var(--tr); font-family:'Inter',sans-serif; }
        .btn-cancel:hover { background:#e2e8f0; }

        @media(max-width:992px) {
            .profile-grid { grid-template-columns:1fr; }
        }
        @media (max-width: 768px) {
            .hamburger{display:flex;}
            .nav-links{display:none;position:absolute;top:100%;left:0;right:0;background:rgba(255,255,255,0.98);padding:20px;border-top:1px solid #f0f0f0;flex-direction:column;align-items:flex-start;box-shadow:0 8px 24px rgba(0,0,0,0.1);}
            .nav-links.open{display:flex;}
            .nav-links a{width:100%;padding:12px 16px;}
            .form-row { grid-template-columns:1fr; gap:0; }
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="container">
            <a href="../index.php" class="brand">🔥 <?php echo APP_NAME; ?></a>
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

    <!-- BREADCRUMB -->
    <div class="breadcrumb-bar">
        <div class="container">
            <div class="breadcrumb">
                <a href="../index.php">Beranda</a> &rsaquo;
                <span style="font-weight:600; color:var(--dk);">Profil Saya</span>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="profile-grid">
            
            <!-- LEFT COLUMN: SIDEBAR -->
            <div class="sidebar-col">
                <!-- User Info -->
                <div class="card user-info-card">
                    <div class="avatar-container">
                        <div class="avatar-circle">
                            <?php if (!empty($user['foto_profil'])): ?>
                                <img src="<?php echo $foto_src; ?>" alt="Avatar">
                            <?php else: ?>
                                <span><?php echo $initials; ?></span>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="avatar-upload-btn" onclick="document.getElementById('modalFoto').classList.add('show')">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                    <h2 class="user-name"><?php echo htmlspecialchars($user['nama_lengkap']); ?></h2>
                    <span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                    <span class="role-badge">Customer</span>
                    <span class="join-date"><i class="far fa-calendar-alt"></i> Bergabung <?php echo date('d M Y', strtotime($user['created_at'] ?? '2026-05-11')); ?></span>
                </div>

                <!-- Statistik Akun -->
                <div class="card sidebar-card">
                    <h3>Statistik Akun</h3>
                    <div class="stat-list">
                        <div class="stat-item">
                            <div class="stat-label pesanan">
                                <i class="fas fa-shopping-bag"></i> Total Pesanan
                            </div>
                            <span class="stat-value"><?php echo $total_pesanan; ?></span>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label belanja">
                                <i class="fas fa-wallet"></i> Total Belanja
                            </div>
                            <span class="stat-value currency">Rp <?php echo number_format($total_belanja, 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Menu Cepat -->
                <div class="card sidebar-card">
                    <h3>Menu Cepat</h3>
                    <div class="quick-links">
                        <a href="pesanan_saya.php" class="quick-link"><i class="fas fa-receipt"></i> Pesanan Saya</a>
                        <a href="keranjang.php" class="quick-link"><i class="fas fa-shopping-cart"></i> Keranjang</a>
                        <a href="index.php" class="quick-link"><i class="fas fa-utensils"></i> Belanja Lagi</a>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: MAIN CONTENT & TABS -->
            <div class="content-col">
                
                <?php if ($success): ?>
                    <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card" style="padding:0; overflow:hidden;">
                    <!-- Tabs Navigation -->
                    <div class="tabs-header" style="padding:0 24px; background:#fafafa;">
                        <button class="tab-btn active" onclick="switchTab(event, 'edit-profil')"><i class="far fa-user-circle"></i> Edit Profil</button>
                        <button class="tab-btn" onclick="switchTab(event, 'ubah-password')"><i class="fas fa-lock"></i> Ubah Password</button>
                        <button class="tab-btn" onclick="switchTab(event, 'riwayat')"><i class="fas fa-history"></i> Riwayat</button>
                    </div>

                    <!-- Tabs Body -->
                    <div style="padding:24px;">
                        
                        <!-- TAB 1: EDIT PROFIL -->
                        <div id="edit-profil" class="tab-content active">
                            <h3 class="form-section-title">Informasi Pribadi</h3>
                            <form method="POST">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Nama Lengkap</label>
                                        <input type="text" name="nama_lengkap" value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                        <small><i class="fas fa-info-circle"></i> Email tidak dapat diubah</small>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>No. HP</label>
                                    <input type="text" name="no_telepon" value="<?php echo htmlspecialchars($user['no_telepon'] ?? ''); ?>" placeholder="Contoh: 08123456789">
                                </div>
                                <div class="form-group">
                                    <label>Bio</label>
                                    <textarea name="bio" rows="3" placeholder="Ceritakan sedikit tentang dirimu..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Alamat Pengiriman</label>
                                    <textarea id="alamat_pengiriman" name="alamat" rows="3" placeholder="Jalan, RT/RW, Komplek, Patokan..."><?php echo htmlspecialchars($user['alamat'] ?? ''); ?></textarea>
                                    <button type="button" onclick="openGoogleMaps()" style="margin-top:8px;display:inline-flex;align-items:center;gap:6px;background:#4285F4;color:#fff;border:none;padding:8px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;transition:all .2s;">
                                        <i class="fas fa-map-marker-alt"></i> Pilih dari Google Maps
                                    </button>
                                </div>
                                <button type="submit" name="update" class="btn-submit"><i class="fas fa-save"></i> Simpan Perubahan</button>
                            </form>
                        </div>

                        <!-- TAB 2: UBAH PASSWORD -->
                        <div id="ubah-password" class="tab-content">
                            <h3 class="form-section-title">Ubah Password Keamanan</h3>
                            <form method="POST">
                                <div class="form-group">
                                    <label>Password Lama</label>
                                    <input type="password" name="password_lama" required placeholder="Masukkan password lama Anda">
                                </div>
                                <div class="form-group">
                                    <label>Password Baru</label>
                                    <input type="password" name="password_baru" required placeholder="Password baru (minimal 6 karakter)">
                                </div>
                                <div class="form-group">
                                    <label>Konfirmasi Password Baru</label>
                                    <input type="password" name="password_konfirmasi" required placeholder="Ulangi password baru Anda">
                                </div>
                                <button type="submit" name="update_password" class="btn-submit"><i class="fas fa-key"></i> Perbarui Password</button>
                            </form>
                        </div>

                        <!-- TAB 3: RIWAYAT -->
                        <div id="riwayat" class="tab-content">
                            <h3 class="form-section-title">Riwayat Pesanan Terbaru</h3>
                            <?php if (count($riwayat_orders) > 0): ?>
                                <div class="riwayat-table-wrap">
                                    <table class="riwayat-table">
                                        <thead>
                                            <tr>
                                                <th>No. Pesanan</th>
                                                <th>Tanggal</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($riwayat_orders as $ord): 
                                                $lbl_map = ['menunggu'=>'menunggu','diproses'=>'diproses','selesai'=>'selesai','dibatalkan'=>'dibatalkan'];
                                                $lbl = $lbl_map[$ord['status_pesanan']] ?? 'menunggu';
                                            ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($ord['no_pesanan']); ?></strong></td>
                                                <td><?php echo date('d/m/Y', strtotime($ord['tanggal_pesanan'])); ?></td>
                                                <td><strong>Rp <?php echo number_format($ord['total_harga'], 0, ',', '.'); ?></strong></td>
                                                <td><span class="status-pill <?php echo $lbl; ?>"><?php echo htmlspecialchars($ord['status_pesanan']); ?></span></td>
                                                <td>
                                                    <a href="detail_pesanan.php?id=<?php echo $ord['id']; ?>" class="btn-view"><i class="fas fa-eye"></i> Detail</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div style="text-align:center; padding:40px 20px; color:#aaa;">
                                    <i class="fas fa-history" style="font-size:3rem; margin-bottom:16px; opacity:0.3; display:block;"></i>
                                    Belum ada riwayat transaksi.
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

                <!-- DANGER ZONE -->
                <div class="card danger-card">
                    <h3>Zona Berbahaya</h3>
                    <div class="danger-box">
                        <div class="danger-desc">
                            <h4>Hapus Semua Data Lokal</h4>
                            <p>Reset semua data termasuk cache keranjang dan data transaksi di browser ini.</p>
                        </div>
                        <button type="button" class="btn-danger" onclick="resetLocalData()"><i class="fas fa-trash-alt"></i> Reset Data</button>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- Modal Upload Foto -->
    <div class="modal-overlay" id="modalFoto">
        <div class="modal-box">
            <h3><i class="fas fa-camera" style="color:var(--p);"></i> Ganti Foto Profil</h3>
            <img id="previewImg" class="preview-img" src="" alt="Preview">
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="foto" accept="image/jpeg,image/png,image/gif" id="inputFoto" onchange="previewFoto(this)" required>
                <div class="modal-actions">
                    <button type="submit" name="update_foto" class="btn-upload"><i class="fas fa-upload"></i> Upload</button>
                    <button type="button" class="btn-cancel" onclick="document.getElementById('modalFoto').classList.remove('show')">Batal</button>
                </div>
            </form>
        </div>
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

        function switchTab(e, tabId) {
            // Remove active classes
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to target
            e.currentTarget.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }

        function previewFoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    const img = document.getElementById('previewImg');
                    img.src = e.target.result;
                    img.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function resetLocalData() {
            if (confirm('Apakah Anda yakin ingin mereset data lokal? Tindakan ini tidak dapat dibatalkan.')) {
                localStorage.clear();
                sessionStorage.clear();
                alert('Data lokal berhasil direset.');
                window.location.reload();
            }
        }

        document.getElementById('modalFoto').addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('show');
        });
    </script>
</body>
</html>
