<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

// Proses pembatalan pesanan
if (isset($_POST['batalkan']) && isset($_POST['pesanan_id'])) {
    $pid = (int)$_POST['pesanan_id'];
    $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM pesanan WHERE id=$pid AND user_id=$user_id AND status_pesanan='menunggu'"));
    if ($cek) {
        mysqli_query($conn, "DELETE FROM detail_pesanan WHERE pesanan_id=$pid");
        mysqli_query($conn, "DELETE FROM pesanan WHERE id=$pid AND user_id=$user_id");
        header("Location: pesanan_saya.php?dibatalkan=1");
        exit();
    }
}

// Ambil data pesanan
$pesanan = mysqli_query($conn, "SELECT * FROM pesanan WHERE id = $id AND user_id = $user_id");
$p = mysqli_fetch_assoc($pesanan);

if (!$p) {
    header("Location: pesanan_saya.php");
    exit();
}

$detail = mysqli_query($conn, "SELECT dp.*, m.nama_menu, m.gambar 
                                FROM detail_pesanan dp 
                                JOIN menu m ON dp.menu_id = m.id 
                                WHERE dp.pesanan_id = $id");

$snapToken = '';
$snapError = '';
if ($p['status_pembayaran'] == 'belum_bayar' && $p['status_pesanan'] != 'dibatalkan') {
    // Cek langsung ke API Midtrans karena webhook tidak masuk ke localhost
    $midtrans_status = checkMidtransStatus($p['no_pesanan']);
    if ($midtrans_status && in_array($midtrans_status['transaction_status'], ['capture', 'settlement'])) {
        mysqli_query($conn, "UPDATE pesanan SET status_pembayaran = 'sudah_bayar' WHERE id = " . $p['id']);
        $p['status_pembayaran'] = 'sudah_bayar';
    } else {
        $user = getUserById($user_id);
        $snapResult = getMidtransSnapToken($p, $user);
        $snapToken = $snapResult['token'];
        $snapError = $snapResult['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?php echo $p['no_pesanan']; ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <?php if ($snapToken): ?>
    <script type="text/javascript"
            src="<?php echo MIDTRANS_IS_PRODUCTION ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js'; ?>"
            data-client-key="<?php echo MIDTRANS_CLIENT_KEY; ?>"></script>
    <?php endif; ?>
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
        
        .card {
            background: white; border-radius: var(--r);
            padding: 30px; margin-bottom: 24px;
            box-shadow: var(--sh);
            border: 1px solid #f0f0f0;
        }
        
        .header {
            display: flex; justify-content: space-between; align-items: flex-start;
            padding-bottom: 20px; border-bottom: 2px dashed #eee; margin-bottom: 24px;
        }
        
        .no-pesanan { font-size:22px; font-weight: 800; color: var(--dk); display:block; margin-bottom:4px; }
        
        .status-badge {
            padding: 6px 14px; border-radius: 50px; font-size: 13px; font-weight: 700;
            display:inline-flex; align-items:center; gap:6px;
        }
        .status-menunggu { background: #fff3cd; color: #856404; }
        .status-diproses { background: #e0f2fe; color: #0284c7; }
        .status-selesai { background: #dcfce7; color: #166534; }
        .status-dibatalkan { background: #fee2e2; color: #b91c1c; }
        
        .info-grid {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;
            background: #f8f9fa; padding: 24px; border-radius: var(--r);
            margin-bottom: 30px; border: 1px solid #eee;
        }
        .info-item { display:flex; flex-direction:column; gap:6px; }
        .info-label { font-size:12px; font-weight:700; color:var(--txl); text-transform:uppercase; letter-spacing:0.5px; }
        .info-value { font-size:15px; font-weight:600; color:var(--dk); line-height:1.4; }
        
        .section-title { font-size:18px; font-weight:800; color:var(--dk); margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        .section-title i { color:var(--p); }

        /* ── ITEM LIST ── */
        .item-list { display:flex; flex-direction:column; gap:16px; margin-bottom:24px; }
        .item-row {
            display:flex; gap:16px; align-items:center;
            padding-bottom:16px; border-bottom:1px solid #f0f0f0;
        }
        .item-row:last-child { border-bottom:none; padding-bottom:0; }
        .item-img { width:64px; height:64px; border-radius:8px; object-fit:cover; flex-shrink:0; }
        .item-info { flex:1; }
        .item-name { font-size:15px; font-weight:700; color:var(--dk); margin-bottom:4px; }
        .item-price { font-size:13px; color:var(--txl); }
        .item-subtotal { font-size:16px; font-weight:800; color:var(--p); text-align:right; }

        .total-box {
            background:#fff8f8; padding:20px 24px; border-radius:var(--r);
            display:flex; justify-content:space-between; align-items:center;
            border:1px dashed #fca5a5; margin-bottom:30px;
        }
        .total-box .label { font-size:16px; font-weight:700; color:var(--txl); }
        .total-box .value { font-size:24px; font-weight:800; color:var(--p); }

        /* ── PAYMENT BOX ── */
        .payment-box {
            border-radius: var(--r); padding: 24px; text-align: center; margin-bottom: 24px;
        }
        .qris-box { background: #fffcf8; border: 2px dashed #fca5a5; }
        .transfer-box { background: #f0f7ff; border: 2px dashed #93c5fd; }
        .payment-box h3 { font-size:18px; font-weight:800; margin-bottom:8px; }
        .qris-box h3 { color: var(--pd); }
        .transfer-box h3 { color: #1e3a8a; }
        .payment-box p { font-size:14px; color:#555; margin-bottom:16px; }
        .qris-img { width: 220px; height: 220px; border-radius: 12px; background: white; padding: 12px; box-shadow:var(--sh); margin-bottom:16px; }
        .payment-nominal { font-size: 24px; font-weight: 800; margin-bottom: 8px; }
        .qris-box .payment-nominal { color: var(--p); }
        .transfer-box .payment-nominal { color: #2563eb; }

        .bank-list { display:flex; flex-direction:column; gap:12px; text-align:left; max-width:400px; margin:0 auto 20px; }
        .bank-item { background:white; padding:16px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05); display:flex; justify-content:space-between; align-items:center; }
        .bank-name { font-weight:800; color:var(--dk); margin-bottom:2px; }
        .bank-an { font-size:12px; color:#666; }
        .bank-no { font-size:18px; font-weight:800; color:#2563eb; letter-spacing:1px; }

        .note-box { background: #fff3cd; padding: 16px 20px; border-radius: 8px; margin-bottom: 24px; font-size:14px; color:#856404; display:flex; gap:12px; align-items:flex-start; }
        .note-box i { font-size:18px; margin-top:2px; }

        .btn-group { display:flex; gap:12px; margin-top:30px; }
        .btn-action {
            flex:1; padding: 14px; border-radius: 50px; font-size: 15px; font-weight:700;
            text-align:center; text-decoration:none; transition: var(--tr); cursor:pointer;
            display:inline-flex; align-items:center; justify-content:center; gap:8px;
        }
        .btn-back { background: #f1f5f9; color: var(--dk); }
        .btn-back:hover { background: #e2e8f0; }
        .btn-cancel { background: #fee2e2; color: #dc2626; border:none; }
        .btn-cancel:hover { background: #fecaca; }

        /* Modal konfirmasi */
        .modal-overlay {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter:blur(4px);
            z-index: 1000; align-items: center; justify-content: center;
        }
        .modal-overlay.show { display: flex; }
        .modal-box {
            background: white; border-radius: 16px; padding: 32px;
            max-width: 400px; width: 90%; text-align: center; box-shadow: var(--sh2);
            animation: modalFadeIn 0.3s ease;
        }
        @keyframes modalFadeIn { from{opacity:0;transform:translateY(20px);} to{opacity:1;transform:translateY(0);} }
        .modal-box .modal-icon { font-size: 56px; color: #dc2626; margin-bottom: 16px; }
        .modal-box h3 { font-size: 20px; font-weight:800; color: var(--dk); margin-bottom: 12px; }
        .modal-box p { font-size: 14px; color: var(--txl); margin-bottom: 24px; line-height:1.5; }
        .modal-actions { display: flex; gap: 12px; }
        .modal-actions button { flex:1; padding: 12px; border-radius: 50px; font-size: 14px; font-weight: 700; cursor: pointer; transition:var(--tr); border:none; }
        .btn-modal-ya { background: #dc2626; color: white; }
        .btn-modal-ya:hover { background: #b91c1c; }
        .btn-modal-batal { background: #f1f5f9; color: var(--dk); }
        .btn-modal-batal:hover { background: #e2e8f0; }

        @media (max-width:768px) {
            .hamburger{display:flex;}
            .nav-links{display:none;position:absolute;top:100%;left:0;right:0;background:rgba(255,255,255,0.98);padding:20px;border-top:1px solid #f0f0f0;flex-direction:column;align-items:flex-start;box-shadow:0 8px 24px rgba(0,0,0,0.1);}
            .nav-links.open{display:flex;}
            .nav-links a{width:100%;padding:12px 16px;}
            .info-grid { grid-template-columns: 1fr; }
            .header { flex-direction: column; gap: 12px; }
            .total-box { flex-direction: column; align-items: flex-start; gap:8px; }
            .btn-group { flex-direction:column; }
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
            <h1>Detail Pesanan</h1>
            <div class="breadcrumb" style="justify-content:center;display:flex;gap:6px;">
                <a href="../index.php">Beranda</a> &rsaquo;
                <a href="pesanan_saya.php">Pesanan Saya</a> &rsaquo;
                <span style="opacity:0.8;">#<?php echo $p['no_pesanan']; ?></span>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="main-content">
            <div class="card">
                <div class="header">
                    <div>
                        <span class="no-pesanan">Pesanan #<?php echo $p['no_pesanan']; ?></span>
                        <div style="font-size:14px;color:#666;margin-top:4px;"><i class="far fa-clock"></i> <?php echo date('d F Y, H:i', strtotime($p['tanggal_pesanan'])); ?></div>
                    </div>
                    <?php
                    $status_class = 'status-' . $p['status_pesanan'];
                    $icon = match($p['status_pesanan']) {
                        'menunggu' => 'fa-clock',
                        'diproses' => 'fa-fire-burner',
                        'selesai' => 'fa-check-circle',
                        'dibatalkan' => 'fa-times-circle',
                        default => 'fa-info-circle'
                    };
                    ?>
                    <span class="status-badge <?php echo $status_class; ?>">
                        <i class="fas <?php echo $icon; ?>"></i> <?php echo ucfirst($p['status_pesanan']); ?>
                    </span>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Metode Pembayaran</span>
                        <span class="info-value"><?php echo formatPaymentMethod($p['metode_pembayaran']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status Pembayaran</span>
                        <span class="info-value" style="color:<?php echo $p['status_pembayaran']=='sudah_bayar'?'#166534':'#b91c1c'; ?>;">
                            <?php echo $p['status_pembayaran']=='sudah_bayar' ? '<i class="fas fa-check-circle"></i> Sudah Bayar' : '<i class="fas fa-exclamation-circle"></i> Belum Bayar'; ?>
                        </span>
                    </div>
                    <div class="info-item" style="grid-column:1/-1;">
                        <span class="info-label">Alamat Pengiriman</span>
                        <span class="info-value"><?php echo htmlspecialchars($p['alamat_pengiriman']) ?: '-'; ?></span>
                    </div>
                </div>

                <?php if ($p['catatan']): ?>
                <div class="note-box">
                    <i class="fas fa-comment-dots"></i>
                    <div>
                        <strong style="display:block;margin-bottom:4px;">Catatan Tambahan:</strong>
                        <?php echo htmlspecialchars($p['catatan']); ?>
                    </div>
                </div>
                <?php endif; ?>

                <h3 class="section-title"><i class="fas fa-utensils"></i> Item Pesanan</h3>
                <div class="item-list">
                    <?php 
                    $total = 0;
                    while ($d = mysqli_fetch_assoc($detail)): 
                        $subtotal = $d['jumlah'] * $d['harga_satuan'];
                        $total += $subtotal;
                    ?>
                    <div class="item-row">
                        <img src="../assets/images/menu/<?php echo htmlspecialchars($d['gambar'] ?: 'default.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($d['nama_menu']); ?>" 
                             class="item-img"
                             onerror="this.onerror=null;this.src='https://placehold.co/64x64?text=Menu'">
                        <div class="item-info">
                            <div class="item-name"><?php echo htmlspecialchars($d['nama_menu']); ?></div>
                            <?php if(!empty($d['varian'])): ?>
                            <div style="font-size:12px;color:#888;margin-bottom:4px;"><i class="fas fa-tags"></i> <?php echo htmlspecialchars($d['varian']); ?></div>
                            <?php endif; ?>
                            <div class="item-price"><?php echo $d['jumlah']; ?> × Rp <?php echo number_format($d['harga_satuan'], 0, ',', '.'); ?></div>
                        </div>
                        <div class="item-subtotal">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <div class="total-box">
                    <div class="label">Total Pembayaran</div>
                    <div class="value">Rp <?php echo number_format($total, 0, ',', '.'); ?></div>
                </div>

                <?php if ($p['status_pembayaran'] == 'belum_bayar' && $p['status_pesanan'] != 'dibatalkan'): ?>
                    <div class="payment-box transfer-box" style="background:#fffcf8; border:2px dashed #fca5a5;">
                        <h3><i class="fas fa-wallet"></i> Lakukan Pembayaran</h3>
                        <p>Pesanan Anda sedang menunggu pembayaran. Silakan klik tombol di bawah untuk menyelesaikan pembayaran via Midtrans.</p>
                        <div class="payment-nominal" style="color:var(--p);">Rp <?php echo number_format($total, 0, ',', '.'); ?></div>
                        
                        <?php if ($snapError): ?>
                        <div style="background:#fee2e2; color:#b91c1c; padding:12px; border-radius:8px; margin-top:16px; font-size:13px; text-align:left;">
                            <strong><i class="fas fa-exclamation-circle"></i> Error Konfigurasi:</strong><br>
                            <?php echo $snapError; ?>
                        </div>
                        <?php else: ?>
                        <button id="pay-button" class="btn-action" style="background:var(--p); color:white; border:none; margin-top:16px; width:100%;">
                            <i class="fas fa-credit-card"></i> Bayar Sekarang
                        </button>
                        <?php endif; ?>
                    </div>
                <?php elseif ($p['status_pembayaran'] == 'sudah_bayar'): ?>
                    <div style="background:#dcfce7;color:#166534;padding:16px;border-radius:var(--r);text-align:center;font-weight:700;display:flex;align-items:center;justify-content:center;gap:8px;">
                        <i class="fas fa-check-circle" style="font-size:20px;"></i> Pembayaran Telah Diterima
                    </div>
                <?php endif; ?>

                <div class="btn-group">
                    <a href="pesanan_saya.php" class="btn-action btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
                    <?php if ($p['status_pesanan'] == 'menunggu'): ?>
                        <button type="button" class="btn-action btn-cancel" onclick="document.getElementById('modalBatal').classList.add('show')">
                            <i class="fas fa-times-circle"></i> Batalkan Pesanan
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Pembatalan -->
    <?php if ($p['status_pesanan'] == 'menunggu'): ?>
    <div class="modal-overlay" id="modalBatal">
        <div class="modal-box">
            <div class="modal-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <h3>Batalkan Pesanan?</h3>
            <p>Apakah Anda yakin ingin membatalkan pesanan <strong>#<?php echo $p['no_pesanan']; ?></strong>?<br>Tindakan ini tidak dapat dibatalkan.</p>
            <div class="modal-actions">
                <form method="POST" style="flex:1;">
                    <input type="hidden" name="pesanan_id" value="<?php echo $p['id']; ?>">
                    <button type="submit" name="batalkan" class="btn-modal-ya" style="width:100%;">Ya, Batalkan</button>
                </form>
                <button type="button" class="btn-modal-batal" onclick="document.getElementById('modalBatal').classList.remove('show')">Kembali</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        document.getElementById('modalBatal')?.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('show');
        });

        <?php if ($snapToken): ?>
        function triggerSnap() {
            window.snap.pay('<?php echo $snapToken; ?>', {
                onSuccess: function(result){
                    window.location.href = "pesanan_saya.php?success=1";
                },
                onPending: function(result){
                    alert("Menunggu pembayaran Anda!");
                },
                onError: function(result){
                    alert("Pembayaran gagal!");
                },
                onClose: function(){
                    console.log('Customer closed the popup without finishing the payment');
                }
            });
        }

        var payButton = document.getElementById('pay-button');
        if (payButton) {
            payButton.addEventListener('click', triggerSnap);
        }

        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        // Trigger auto popup untuk checkout pertama kali
        setTimeout(triggerSnap, 500);
        <?php endif; ?>
        <?php endif; ?>
    </script>
</body>
</html>