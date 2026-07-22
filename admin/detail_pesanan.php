<?php
// admin/detail_pesanan.php

require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$id = (int)($_GET['id'] ?? 0);
$pesanan = mysqli_query($conn, "SELECT p.*, u.nama_lengkap, u.email, u.no_telepon, u.alamat 
                                 FROM pesanan p 
                                 JOIN users u ON p.user_id = u.id 
                                 WHERE p.id = $id");
if (mysqli_num_rows($pesanan) == 0) {
    header("Location: kelola_pesanan.php");
    exit();
}
$p = mysqli_fetch_assoc($pesanan);

$detail = mysqli_query($conn, "SELECT dp.*, m.nama_menu 
                                FROM detail_pesanan dp 
                                JOIN menu m ON dp.menu_id = m.id 
                                WHERE dp.pesanan_id = $id");

$badge_map = [
    'menunggu' => 'badge-menunggu',
    'diproses' => 'badge-diproses',
    'selesai' => 'badge-selesai',
    'dibatalkan' => 'badge-dibatalkan'
];
$badge = $badge_map[$p['status_pesanan']] ?? 'badge-info';
$badge_bayar = $p['status_pembayaran'] == 'sudah_bayar' ? 'badge-success' : 'badge-warning';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - Admin KBV</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="assets/admin.css?v=2">
    <style>
        .info-box { background: #f8f9fb; padding: 20px; border-radius: 8px; border: 1px solid #f0f0f0; }
        .info-box h3 { font-size: 14px; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .info-box h3 i { color: var(--p); }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .info-item { display: flex; flex-direction: column; gap: 4px; }
        .info-label { font-size: 12px; color: #888; font-weight: 600; text-transform: uppercase; }
        .info-value { font-size: 14px; color: #333; font-weight: 500; }
        .total-box { display: flex; justify-content: flex-end; margin-top: 24px; padding-top: 20px; border-top: 2px dashed #e5e7eb; }
        .total-content { text-align: right; }
        .total-label { font-size: 14px; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
        .total-amount { font-size: 24px; font-weight: 800; color: var(--p); }
        @media(max-width: 600px) { .info-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-nav">
            <div class="page-title">
                <h1><i class="fas fa-file-invoice"></i> Detail Pesanan #<?php echo htmlspecialchars($p['no_pesanan']); ?></h1>
                <p>Rincian lengkap pesanan pelanggan</p>
            </div>
            <div class="user-info">
                <a href="../index.php" class="beranda-btn"><i class="fas fa-home"></i> Beranda</a>
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="box">
            <div class="grid-2" style="margin-bottom: 24px;">
                <!-- Info Pelanggan -->
                <div class="info-box">
                    <h3><i class="fas fa-user"></i> Informasi Pelanggan</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Nama Lengkap</span>
                            <span class="info-value"><strong><?php echo htmlspecialchars($p['nama_lengkap']); ?></strong></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?php echo htmlspecialchars($p['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">No. Telepon</span>
                            <span class="info-value"><?php echo htmlspecialchars($p['no_telepon'] ?: '-'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Alamat Lengkap</span>
                            <span class="info-value"><?php echo htmlspecialchars($p['alamat'] ?: '-'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Info Pesanan -->
                <div class="info-box">
                    <h3><i class="fas fa-shopping-bag"></i> Informasi Pesanan</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Tanggal Pemesanan</span>
                            <span class="info-value"><?php echo date('d M Y, H:i', strtotime($p['tanggal_pesanan'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status Pesanan</span>
                            <span class="info-value"><span class="badge <?php echo $badge; ?>"><?php echo ucfirst($p['status_pesanan']); ?></span></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Metode Pembayaran</span>
                            <span class="info-value"><strong><?php require_once '../includes/functions.php'; echo formatPaymentMethod($p['metode_pembayaran']); ?></strong></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status Pembayaran</span>
                            <span class="info-value"><span class="badge <?php echo $badge_bayar; ?>"><?php echo htmlspecialchars($p['status_pembayaran']); ?></span></span>
                        </div>
                        <div class="info-item" style="grid-column: 1 / -1;">
                            <span class="info-label">Catatan Pelanggan</span>
                            <span class="info-value" style="font-style:italic;color:#666;"><?php echo htmlspecialchars($p['catatan'] ?: 'Tidak ada catatan'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detail Item -->
            <div class="box-header" style="margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid #f0f0f0;">
                <h2 style="font-size:15px;font-weight:700;color:#1a2332;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-list" style="color:var(--p);"></i> Item Pesanan
                </h2>
            </div>
            
            <div style="overflow-x:auto;">
                <table style="margin-bottom:0;">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Menu</th>
                            <th style="text-align:center;">Harga Satuan</th>
                            <th style="text-align:center;">Jumlah</th>
                            <th style="text-align:right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total = 0;
                        $no = 1;
                        while ($d = mysqli_fetch_assoc($detail)): 
                            $subtotal = $d['jumlah'] * $d['harga_satuan'];
                            $total += $subtotal;
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($d['nama_menu']); ?></strong>
                                <?php if(!empty($d['varian'])): ?>
                                <div style="font-size:12px;color:#888;margin-top:4px;"><i class="fas fa-tags"></i> <?php echo htmlspecialchars($d['varian']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">Rp <?php echo number_format($d['harga_satuan'], 0, ',', '.'); ?></td>
                            <td style="text-align:center;"><span class="badge" style="background:#f0f0f0;color:#333;font-size:13px;"><?php echo $d['jumlah']; ?>x</span></td>
                            <td style="text-align:right;font-weight:600;">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="total-box">
                <div class="total-content">
                    <div class="total-label">Total Keseluruhan</div>
                    <div class="total-amount">Rp <?php echo number_format($total, 0, ',', '.'); ?></div>
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:30px;padding-top:20px;border-top:1px solid #f0f0f0;">
                <a href="kelola_pesanan.php" class="btn-small btn-secondary" style="padding:10px 24px;font-size:14px;"><i class="fas fa-arrow-left"></i> Kembali</a>
                <a href="cetak_invoice.php?id=<?php echo $id; ?>" target="_blank" class="btn-small btn-success" style="padding:10px 24px;font-size:14px;"><i class="fas fa-print"></i> Cetak Invoice</a>
            </div>
        </div>
    </div>
</body>
</html>
