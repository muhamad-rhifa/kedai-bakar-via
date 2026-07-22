<?php
// admin/laporan.php
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Filter periode
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'bulan_ini';
$tgl_dari = isset($_GET['dari']) ? $_GET['dari'] : date('Y-m-01');
$tgl_sampai = isset($_GET['sampai']) ? $_GET['sampai'] : date('Y-m-d');

switch ($periode) {
    case 'hari_ini':
        $tgl_dari = date('Y-m-d');
        $tgl_sampai = date('Y-m-d');
        break;
    case 'minggu_ini':
        $tgl_dari = date('Y-m-d', strtotime('monday this week'));
        $tgl_sampai = date('Y-m-d');
        break;
    case 'bulan_ini':
        $tgl_dari = date('Y-m-01');
        $tgl_sampai = date('Y-m-d');
        break;
    case 'tahun_ini':
        $tgl_dari = date('Y-01-01');
        $tgl_sampai = date('Y-m-d');
        break;
    // 'custom' pakai nilai dari form
}

$where_tgl = "DATE(p.tanggal_pesanan) BETWEEN '$tgl_dari' AND '$tgl_sampai'";

// Ringkasan
$ringkasan = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_pesanan,
        SUM(CASE WHEN status_pembayaran='sudah_bayar' THEN total_harga ELSE 0 END) as total_pendapatan,
        SUM(CASE WHEN status_pesanan='selesai' THEN 1 ELSE 0 END) as pesanan_selesai,
        SUM(CASE WHEN status_pesanan='dibatalkan' THEN 1 ELSE 0 END) as pesanan_batal
    FROM pesanan p
    WHERE $where_tgl
"));

// Menu terlaris
$menu_terlaris = mysqli_query($conn, "
    SELECT m.nama_menu, m.harga, SUM(dp.jumlah) as total_qty, SUM(dp.jumlah * dp.harga_satuan) as total_omzet
    FROM detail_pesanan dp
    JOIN menu m ON dp.menu_id = m.id
    JOIN pesanan p ON dp.pesanan_id = p.id
    WHERE $where_tgl AND p.status_pembayaran = 'sudah_bayar'
    GROUP BY dp.menu_id, m.nama_menu, m.harga
    ORDER BY total_qty DESC
    LIMIT 10
");

// Pendapatan per hari (7 hari terakhir untuk chart)
$pendapatan_harian = mysqli_query($conn, "
    SELECT DATE(tanggal_pesanan) as tgl, SUM(total_harga) as total
    FROM pesanan
    WHERE status_pembayaran='sudah_bayar'
      AND DATE(tanggal_pesanan) BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
    GROUP BY DATE(tanggal_pesanan)
    ORDER BY tgl ASC
");
$chart_labels = [];
$chart_data   = [];
// Isi semua 7 hari (termasuk yang 0)
for ($i = 6; $i >= 0; $i--) {
    $chart_labels[] = date('d/m', strtotime("-$i days"));
    $chart_data[date('Y-m-d', strtotime("-$i days"))] = 0;
}
while ($row = mysqli_fetch_assoc($pendapatan_harian)) {
    $chart_data[$row['tgl']] = (int)$row['total'];
}
$chart_values = array_values($chart_data);

// Riwayat pesanan
$riwayat = mysqli_query($conn, "
    SELECT p.*, u.nama_lengkap
    FROM pesanan p
    JOIN users u ON p.user_id = u.id
    WHERE $where_tgl
    ORDER BY p.tanggal_pesanan DESC
    LIMIT 50
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - Admin KBV</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="assets/admin.css?v=2">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Laporan-specific overrides */
        body{display:flex;}
        .empty-row td{text-align:center;color:#aaa;padding:30px;}
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-nav">
            <div class="page-title">
                <h1><i class="fas fa-chart-bar"></i> Laporan Penjualan</h1>
                <p>Analisis pendapatan dan performa penjualan</p>
            </div>
            <div class="user-info">
                <a href="../index.php" class="beranda-btn"><i class="fas fa-home"></i> Beranda</a>
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Filter -->
        <div class="filter-bar">
            <div style="width:100%;margin-bottom:8px;">
                <div class="periode-chips">
                    <a href="?periode=hari_ini"  class="chip <?php echo $periode=='hari_ini'  ? 'active':''; ?>">Hari Ini</a>
                    <a href="?periode=minggu_ini" class="chip <?php echo $periode=='minggu_ini' ? 'active':''; ?>">Minggu Ini</a>
                    <a href="?periode=bulan_ini"  class="chip <?php echo $periode=='bulan_ini'  ? 'active':''; ?>">Bulan Ini</a>
                    <a href="?periode=tahun_ini"  class="chip <?php echo $periode=='tahun_ini'  ? 'active':''; ?>">Tahun Ini</a>
                </div>
            </div>
            <form method="GET">
                <input type="hidden" name="periode" value="custom">
                <div class="filter-group">
                    <label>Dari Tanggal</label>
                    <input type="date" name="dari" value="<?php echo $tgl_dari; ?>">
                </div>
                <div class="filter-group">
                    <label>Sampai Tanggal</label>
                    <input type="date" name="sampai" value="<?php echo $tgl_sampai; ?>">
                </div>
                <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
            </form>
            <!-- Tombol Export Excel -->
            <a href="export_excel.php?periode=<?php echo urlencode($periode); ?>&dari=<?php echo $tgl_dari; ?>&sampai=<?php echo $tgl_sampai; ?>"
               class="btn-export">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
        </div>

        <!-- Stat Cards -->
        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-receipt"></i></div>
                <div>
                    <div class="stat-label">Total Pesanan</div>
                    <div class="stat-value"><?php echo $ringkasan['total_pesanan'] ?? 0; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-money-bill-wave"></i></div>
                <div>
                    <div class="stat-label">Total Pendapatan</div>
                    <div class="stat-value">Rp <?php echo number_format($ringkasan['total_pendapatan'] ?? 0, 0, ',', '.'); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="stat-label">Pesanan Selesai</div>
                    <div class="stat-value"><?php echo $ringkasan['pesanan_selesai'] ?? 0; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
                <div>
                    <div class="stat-label">Pesanan Dibatalkan</div>
                    <div class="stat-value"><?php echo $ringkasan['pesanan_batal'] ?? 0; ?></div>
                </div>
            </div>
        </div>

        <!-- Chart + Menu Terlaris -->
        <div class="grid-2">
            <!-- Grafik Pendapatan 7 Hari -->
            <div class="box">
                <div class="box-header">
                    <h2><i class="fas fa-chart-line" style="color:#ff6b35;"></i> Pendapatan 7 Hari Terakhir</h2>
                </div>
                <div class="chart-wrap">
                    <canvas id="chartPendapatan"></canvas>
                </div>
            </div>

            <!-- Menu Terlaris -->
            <div class="box">
                <div class="box-header">
                    <h2><i class="fas fa-fire" style="color:#ff6b35;"></i> Menu Terlaris</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Menu</th>
                            <th>Terjual</th>
                            <th>Omzet</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($menu_terlaris && mysqli_num_rows($menu_terlaris) > 0):
                            $rank = 1;
                            while ($m = mysqli_fetch_assoc($menu_terlaris)): ?>
                        <tr>
                            <td>
                                <span class="rank-badge <?php echo $rank<=3 ? 'rank-'.$rank : 'rank-other'; ?>">
                                    <?php echo $rank; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($m['nama_menu']); ?></td>
                            <td><strong><?php echo $m['total_qty']; ?></strong> porsi</td>
                            <td>Rp <?php echo number_format($m['total_omzet'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php $rank++; endwhile; else: ?>
                        <tr class="empty-row"><td colspan="4"><i class="fas fa-inbox"></i><br>Belum ada data penjualan</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Riwayat Pesanan -->
        <div class="box">
            <div class="box-header">
                <h2><i class="fas fa-history" style="color:#ff6b35;"></i> Riwayat Pesanan</h2>
                <span style="font-size:13px;color:#888;">
                    <?php echo date('d/m/Y', strtotime($tgl_dari)); ?> –
                    <?php echo date('d/m/Y', strtotime($tgl_sampai)); ?>
                </span>
            </div>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>No. Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Status Pesanan</th>
                            <th>Status Bayar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($riwayat && mysqli_num_rows($riwayat) > 0):
                            while ($p = mysqli_fetch_assoc($riwayat)):
                                $badge_pesanan = match($p['status_pesanan']) {
                                    'selesai'    => 'badge-success',
                                    'diproses'   => 'badge-warning',
                                    'dibatalkan' => 'badge-danger',
                                    default      => 'badge-info'
                                };
                                $badge_bayar = $p['status_pembayaran'] == 'sudah_bayar' ? 'badge-success' : 'badge-warning';
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($p['no_pesanan']); ?></strong></td>
                            <td><?php echo htmlspecialchars($p['nama_lengkap']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($p['tanggal_pesanan'])); ?></td>
                            <td>Rp <?php echo number_format($p['total_harga'], 0, ',', '.'); ?></td>
                            <td><span class="badge <?php echo $badge_pesanan; ?>"><?php echo $p['status_pesanan']; ?></span></td>
                            <td><span class="badge <?php echo $badge_bayar; ?>"><?php echo $p['status_pembayaran']; ?></span></td>
                            <td><a href="detail_pesanan.php?id=<?php echo $p['id']; ?>" style="color:#ff6b35;font-size:13px;"><i class="fas fa-eye"></i> Detail</a></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr class="empty-row"><td colspan="7"><i class="fas fa-inbox"></i><br>Tidak ada pesanan pada periode ini</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="text-align:center;margin-top:24px;padding:16px;color:#aaa;font-size:13px;">
            &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> &mdash; Admin Panel
        </div>
    </div>

    <script>
        const ctx = document.getElementById('chartPendapatan').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: <?php echo json_encode($chart_values); ?>,
                    backgroundColor: 'rgba(255,107,53,0.7)',
                    borderColor: '#ff6b35',
                    borderWidth: 2,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: v => 'Rp ' + v.toLocaleString('id-ID')
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
