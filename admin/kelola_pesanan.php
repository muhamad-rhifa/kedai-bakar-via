<?php
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../includes/functions.php';

if (isset($_POST['update_status'])) {
    $pesanan_id = (int)$_POST['pesanan_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE pesanan SET status_pesanan = '$status' WHERE id = $pesanan_id");
    header("Location: kelola_pesanan.php");
    exit();
}

// Sinkronisasi status Midtrans untuk pesanan yang belum dibayar (Bypass Webhook Localhost)
$pending_orders = mysqli_query($conn, "SELECT id, no_pesanan FROM pesanan WHERE status_pembayaran = 'belum_bayar' AND status_pesanan != 'dibatalkan'");
if ($pending_orders) {
    while ($po = mysqli_fetch_assoc($pending_orders)) {
        $midtrans_status = checkMidtransStatus($po['no_pesanan']);
        if ($midtrans_status && in_array($midtrans_status['transaction_status'], ['capture', 'settlement'])) {
            mysqli_query($conn, "UPDATE pesanan SET status_pembayaran = 'sudah_bayar', status_pesanan = CASE WHEN status_pesanan = 'menunggu' THEN 'diproses' ELSE status_pesanan END WHERE id = " . $po['id']);
        }
    }
}

// Auto-update: pesanan yang sudah bayar tapi masih 'menunggu' → ubah ke 'diproses'
mysqli_query($conn, "UPDATE pesanan SET status_pesanan = 'diproses' WHERE status_pembayaran = 'sudah_bayar' AND status_pesanan = 'menunggu'");

$pesanan_query = "SELECT p.*, u.nama_lengkap, u.email 
                  FROM pesanan p 
                  JOIN users u ON p.user_id = u.id 
                  ORDER BY p.tanggal_pesanan DESC";
$pesanan_result = mysqli_query($conn, $pesanan_query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Admin KBV</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="assets/admin.css?v=2">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-nav">
            <div class="page-title">
                <h1><i class="fas fa-clipboard-list"></i> Kelola Pesanan</h1>
                <p>Kelola dan update status semua pesanan masuk</p>
            </div>
            <div class="user-info">
                <a href="../index.php" class="beranda-btn"><i class="fas fa-home"></i> Beranda</a>
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="table-container">
            <div class="box-header" style="margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;">
                <h2 style="font-size:15px;font-weight:700;color:#1a2332;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-list" style="color:var(--p);"></i> Daftar Pesanan
                </h2>
                <span style="font-size:13px;color:#888;"><?php echo mysqli_num_rows($pesanan_result); ?> pesanan ditemukan</span>
            </div>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>No. Pesanan</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Total</th>
                            <th>Status Pesanan</th>
                            <th>Status Bayar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($p = mysqli_fetch_assoc($pesanan_result)):
                        $badge_map = ['menunggu'=>'badge-menunggu','diproses'=>'badge-diproses','selesai'=>'badge-selesai','dibatalkan'=>'badge-dibatalkan'];
                        $badge = $badge_map[$p['status_pesanan']] ?? 'badge-info';
                        $badge_bayar = $p['status_pembayaran'] == 'sudah_bayar' ? 'badge-success' : 'badge-warning';
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($p['no_pesanan']); ?></strong></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($p['tanggal_pesanan'])); ?></td>
                        <td><?php echo htmlspecialchars($p['nama_lengkap']); ?></td>
                        <td><strong>Rp <?php echo number_format($p['total_harga'], 0, ',', '.'); ?></strong></td>
                        <td><span class="badge <?php echo $badge; ?>"><?php echo ucfirst($p['status_pesanan']); ?></span></td>
                        <td><span class="badge <?php echo $badge_bayar; ?>"><?php echo $p['status_pembayaran']; ?></span></td>
                        <td style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="pesanan_id" value="<?php echo $p['id']; ?>">
                                <select name="status" class="inline-select" onchange="this.form.submit()">
                                    <option value="menunggu" <?php echo $p['status_pesanan']=='menunggu'?'selected':''; ?>>Menunggu</option>
                                    <option value="diproses" <?php echo $p['status_pesanan']=='diproses'?'selected':''; ?>>Diproses</option>
                                    <option value="selesai"  <?php echo $p['status_pesanan']=='selesai' ?'selected':''; ?>>Selesai</option>
                                    <option value="dibatalkan" <?php echo $p['status_pesanan']=='dibatalkan'?'selected':''; ?>>Dibatalkan</option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                            <a href="detail_pesanan.php?id=<?php echo $p['id']; ?>" class="btn-small"><i class="fas fa-eye"></i> Detail</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
