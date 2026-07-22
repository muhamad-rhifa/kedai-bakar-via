<?php
// admin/export_excel.php
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Filter periode (sama persis dengan laporan.php)
$periode   = isset($_GET['periode']) ? $_GET['periode'] : 'bulan_ini';
$tgl_dari  = isset($_GET['dari'])    ? $_GET['dari']    : date('Y-m-01');
$tgl_sampai = isset($_GET['sampai']) ? $_GET['sampai']  : date('Y-m-d');

switch ($periode) {
    case 'hari_ini':
        $tgl_dari   = date('Y-m-d');
        $tgl_sampai = date('Y-m-d');
        break;
    case 'minggu_ini':
        $tgl_dari   = date('Y-m-d', strtotime('monday this week'));
        $tgl_sampai = date('Y-m-d');
        break;
    case 'bulan_ini':
        $tgl_dari   = date('Y-m-01');
        $tgl_sampai = date('Y-m-d');
        break;
    case 'tahun_ini':
        $tgl_dari   = date('Y-01-01');
        $tgl_sampai = date('Y-m-d');
        break;
}

$where_tgl = "DATE(p.tanggal_pesanan) BETWEEN '$tgl_dari' AND '$tgl_sampai'";

// Ringkasan
$ringkasan = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*) as total_pesanan,
        SUM(CASE WHEN status_pembayaran='sudah_bayar' THEN total_harga ELSE 0 END) as total_pendapatan,
        SUM(CASE WHEN status_pesanan='selesai'    THEN 1 ELSE 0 END) as pesanan_selesai,
        SUM(CASE WHEN status_pesanan='dibatalkan' THEN 1 ELSE 0 END) as pesanan_batal
    FROM pesanan p WHERE $where_tgl
"));

// Menu terlaris
$menu_terlaris = mysqli_query($conn, "
    SELECT m.nama_menu, m.harga, SUM(dp.jumlah) as total_qty,
           SUM(dp.jumlah * dp.harga_satuan) as total_omzet
    FROM detail_pesanan dp
    JOIN menu m ON dp.menu_id = m.id
    JOIN pesanan p ON dp.pesanan_id = p.id
    WHERE $where_tgl AND p.status_pembayaran = 'sudah_bayar'
    GROUP BY dp.menu_id, m.nama_menu, m.harga
    ORDER BY total_qty DESC
");

// Riwayat pesanan
$riwayat = mysqli_query($conn, "
    SELECT p.no_pesanan, u.nama_lengkap, u.email, u.no_telepon,
           p.tanggal_pesanan, p.total_harga,
           p.status_pesanan, p.status_pembayaran, p.metode_pembayaran, p.catatan
    FROM pesanan p
    JOIN users u ON p.user_id = u.id
    WHERE $where_tgl
    ORDER BY p.tanggal_pesanan DESC
");

// ── Set header untuk download Excel ──
$filename = 'Laporan_Penjualan_' . $tgl_dari . '_sd_' . $tgl_sampai . '.xls';
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Gunakan UTF-8 BOM agar karakter Indonesia terbaca di Excel
echo "\xEF\xBB\xBF";
?>
<html>
<head><meta charset="UTF-8"></head>
<body>

<!-- ══ SHEET: RINGKASAN ══ -->
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <td colspan="2" style="background:#9e1616;color:white;font-size:16pt;font-weight:bold;">
            Laporan Penjualan - <?php echo APP_NAME; ?>
        </td>
    </tr>
    <tr>
        <td style="background:#f0f0f0;font-weight:bold;">Periode</td>
        <td><?php echo date('d/m/Y', strtotime($tgl_dari)); ?> s/d <?php echo date('d/m/Y', strtotime($tgl_sampai)); ?></td>
    </tr>
    <tr>
        <td style="background:#f0f0f0;font-weight:bold;">Dicetak Pada</td>
        <td><?php echo date('d/m/Y H:i'); ?></td>
    </tr>
    <tr><td colspan="2"></td></tr>

    <tr>
        <td colspan="2" style="background:#2d4059;color:white;font-weight:bold;">RINGKASAN</td>
    </tr>
    <tr>
        <td style="background:#f8f9fa;font-weight:bold;">Total Pesanan</td>
        <td><?php echo $ringkasan['total_pesanan'] ?? 0; ?></td>
    </tr>
    <tr>
        <td style="background:#f8f9fa;font-weight:bold;">Total Pendapatan</td>
        <td>Rp <?php echo number_format($ringkasan['total_pendapatan'] ?? 0, 0, ',', '.'); ?></td>
    </tr>
    <tr>
        <td style="background:#f8f9fa;font-weight:bold;">Pesanan Selesai</td>
        <td><?php echo $ringkasan['pesanan_selesai'] ?? 0; ?></td>
    </tr>
    <tr>
        <td style="background:#f8f9fa;font-weight:bold;">Pesanan Dibatalkan</td>
        <td><?php echo $ringkasan['pesanan_batal'] ?? 0; ?></td>
    </tr>
</table>

<br><br>

<!-- ══ MENU TERLARIS ══ -->
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <td colspan="4" style="background:#2d4059;color:white;font-weight:bold;">MENU TERLARIS</td>
    </tr>
    <tr style="background:#f0f0f0;font-weight:bold;">
        <td>No</td>
        <td>Nama Menu</td>
        <td>Total Terjual (Porsi)</td>
        <td>Total Omzet</td>
    </tr>
    <?php
    $no = 1;
    if ($menu_terlaris && mysqli_num_rows($menu_terlaris) > 0):
        while ($m = mysqli_fetch_assoc($menu_terlaris)):
    ?>
    <tr>
        <td><?php echo $no++; ?></td>
        <td><?php echo htmlspecialchars($m['nama_menu']); ?></td>
        <td><?php echo $m['total_qty']; ?></td>
        <td>Rp <?php echo number_format($m['total_omzet'], 0, ',', '.'); ?></td>
    </tr>
    <?php endwhile; else: ?>
    <tr><td colspan="4" style="color:#999;text-align:center;">Belum ada data penjualan</td></tr>
    <?php endif; ?>
</table>

<br><br>

<!-- ══ RIWAYAT PESANAN ══ -->
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <td colspan="9" style="background:#2d4059;color:white;font-weight:bold;">RIWAYAT PESANAN</td>
    </tr>
    <tr style="background:#f0f0f0;font-weight:bold;">
        <td>No</td>
        <td>No. Pesanan</td>
        <td>Pelanggan</td>
        <td>Email</td>
        <td>No. Telepon</td>
        <td>Tanggal</td>
        <td>Total</td>
        <td>Status Pesanan</td>
        <td>Status Pembayaran</td>
        <td>Metode Bayar</td>
        <td>Catatan</td>
    </tr>
    <?php
    $no = 1;
    if ($riwayat && mysqli_num_rows($riwayat) > 0):
        while ($p = mysqli_fetch_assoc($riwayat)):
    ?>
    <tr>
        <td><?php echo $no++; ?></td>
        <td><?php echo htmlspecialchars($p['no_pesanan']); ?></td>
        <td><?php echo htmlspecialchars($p['nama_lengkap']); ?></td>
        <td><?php echo htmlspecialchars($p['email']); ?></td>
        <td><?php echo htmlspecialchars($p['no_telepon'] ?: '-'); ?></td>
        <td><?php echo date('d/m/Y H:i', strtotime($p['tanggal_pesanan'])); ?></td>
        <td>Rp <?php echo number_format($p['total_harga'], 0, ',', '.'); ?></td>
        <td><?php echo htmlspecialchars($p['status_pesanan']); ?></td>
        <td><?php echo htmlspecialchars($p['status_pembayaran']); ?></td>
        <td><?php echo htmlspecialchars($p['metode_pembayaran'] ?: '-'); ?></td>
        <td><?php echo htmlspecialchars($p['catatan'] ?: '-'); ?></td>
    </tr>
    <?php endwhile; else: ?>
    <tr><td colspan="11" style="color:#999;text-align:center;">Tidak ada pesanan pada periode ini</td></tr>
    <?php endif; ?>
</table>

</body>
</html>
