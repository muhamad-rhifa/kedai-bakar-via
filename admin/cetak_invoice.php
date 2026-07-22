<?php
// admin/cetak_invoice.php
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: kelola_pesanan.php"); exit(); }

$pesanan = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT p.*, u.nama_lengkap, u.email, u.no_telepon, u.alamat
    FROM pesanan p
    JOIN users u ON p.user_id = u.id
    WHERE p.id = $id
"));

if (!$pesanan) { header("Location: kelola_pesanan.php"); exit(); }

$detail_result = mysqli_query($conn, "
    SELECT dp.*, m.nama_menu
    FROM detail_pesanan dp
    JOIN menu m ON dp.menu_id = m.id
    WHERE dp.pesanan_id = $id
");
$items = [];
$subtotal_total = 0;
while ($row = mysqli_fetch_assoc($detail_result)) {
    $row['subtotal'] = $row['jumlah'] * $row['harga_satuan'];
    $subtotal_total += $row['subtotal'];
    $items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo htmlspecialchars($pesanan['no_pesanan']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f0f0f0; color:#333; }

        /* Tombol aksi — disembunyikan saat print */
        .action-bar {
            background:#2d4059; padding:14px 40px;
            display:flex; gap:12px; align-items:center;
        }
        .btn-print {
            padding:10px 24px; background:#ff6b35; color:white;
            border:none; border-radius:6px; cursor:pointer;
            font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px;
            transition:background 0.2s;
        }
        .btn-print:hover { background:#e85a2a; }
        .btn-back {
            padding:10px 20px; background:rgba(255,255,255,0.15); color:white;
            text-decoration:none; border-radius:6px; font-size:14px;
            display:flex; align-items:center; gap:8px; transition:background 0.2s;
        }
        .btn-back:hover { background:rgba(255,255,255,0.25); }

        /* Invoice wrapper */
        .invoice-wrap {
            max-width:780px; margin:30px auto; background:white;
            border-radius:12px; overflow:hidden;
            box-shadow:0 4px 24px rgba(0,0,0,0.12);
        }

        /* Header */
        .invoice-header {
            background:linear-gradient(135deg,#9e1616,#eb570d);
            color:white; padding:36px 40px;
            display:flex; justify-content:space-between; align-items:flex-start;
        }
        .brand h1 { font-size:26px; font-weight:800; margin-bottom:4px; }
        .brand p  { font-size:13px; opacity:.85; }
        .invoice-meta { text-align:right; }
        .invoice-meta .inv-no { font-size:20px; font-weight:800; margin-bottom:6px; }
        .invoice-meta p { font-size:13px; opacity:.85; }

        /* Status badge */
        .status-bar {
            background:#f8f9fa; padding:12px 40px;
            display:flex; gap:20px; flex-wrap:wrap;
            border-bottom:1px solid #eee;
        }
        .status-item { font-size:13px; color:#555; }
        .status-item strong { color:#333; }
        .badge {
            display:inline-block; padding:3px 10px; border-radius:20px;
            font-size:11px; font-weight:700;
        }
        .badge-success  { background:#d4edda; color:#155724; }
        .badge-warning  { background:#fff3cd; color:#856404; }
        .badge-danger   { background:#f8d7da; color:#721c24; }
        .badge-info     { background:#d1ecf1; color:#0c5460; }

        /* Body */
        .invoice-body { padding:32px 40px; }

        /* Info grid */
        .info-grid {
            display:grid; grid-template-columns:1fr 1fr; gap:24px;
            margin-bottom:28px;
        }
        .info-box h4 {
            font-size:11px; text-transform:uppercase; letter-spacing:1px;
            color:#999; margin-bottom:10px;
        }
        .info-box p { font-size:14px; color:#333; line-height:1.7; }
        .info-box strong { color:#111; }

        /* Items table */
        .items-table { width:100%; border-collapse:collapse; margin-bottom:24px; }
        .items-table thead tr { background:#f8f9fa; }
        .items-table th {
            padding:11px 14px; text-align:left;
            font-size:12px; text-transform:uppercase;
            letter-spacing:.5px; color:#666; font-weight:700;
        }
        .items-table td { padding:12px 14px; border-bottom:1px solid #f0f0f0; font-size:14px; }
        .items-table tr:last-child td { border-bottom:none; }
        .items-table .text-right { text-align:right; }
        .items-table .item-name { font-weight:600; color:#222; }

        /* Totals */
        .totals { margin-left:auto; width:280px; }
        .totals-row {
            display:flex; justify-content:space-between;
            padding:8px 0; font-size:14px; color:#555;
            border-bottom:1px dashed #eee;
        }
        .totals-row:last-child {
            border-bottom:none; border-top:2px solid #333;
            margin-top:4px; padding-top:12px;
            font-size:17px; font-weight:800; color:#9e1616;
        }

        /* Footer */
        .invoice-footer {
            background:#f8f9fa; padding:20px 40px;
            border-top:1px solid #eee;
            display:flex; justify-content:space-between; align-items:center;
            flex-wrap:wrap; gap:10px;
        }
        .invoice-footer p { font-size:12px; color:#888; }
        .invoice-footer .thank-you { font-size:14px; font-weight:700; color:#9e1616; }

        /* Print styles */
        @media print {
            body { background:white; }
            .action-bar { display:none !important; }
            .invoice-wrap { box-shadow:none; border-radius:0; margin:0; max-width:100%; }
            @page { margin:10mm; }
        }

        @media (max-width:600px) {
            .invoice-header { flex-direction:column; gap:16px; }
            .invoice-meta { text-align:left; }
            .info-grid { grid-template-columns:1fr; }
            .invoice-body { padding:20px; }
            .invoice-header { padding:24px 20px; }
            .totals { width:100%; }
        }
    </style>
</head>
<body>

<!-- Action Bar -->
<div class="action-bar">
    <button class="btn-print" onclick="window.print()">
        <i class="fas fa-print"></i> Cetak Invoice
    </button>
    <a href="detail_pesanan.php?id=<?php echo $id; ?>" class="btn-back">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<!-- Invoice -->
<div class="invoice-wrap">

    <!-- Header -->
    <div class="invoice-header">
        <div class="brand">
            <h1>🔥 <?php echo APP_NAME; ?></h1>
            <p>Jl. Kh. Ahmad Sugriwa, Desa Iwul Parung, Bogor</p>
            <p>Telp: 0812-3456-7890 &nbsp;|&nbsp; info@kedaibakarvia.com</p>
        </div>
        <div class="invoice-meta">
            <div class="inv-no">INVOICE</div>
            <p><?php echo htmlspecialchars($pesanan['no_pesanan']); ?></p>
            <p style="margin-top:6px;"><?php echo date('d F Y', strtotime($pesanan['tanggal_pesanan'])); ?></p>
        </div>
    </div>

    <!-- Status Bar -->
    <div class="status-bar">
        <?php
        $bp = $pesanan['status_pesanan'];
        $badge_p = match($bp) {
            'selesai'    => 'badge-success',
            'diproses'   => 'badge-info',
            'dibatalkan' => 'badge-danger',
            default      => 'badge-warning'
        };
        $bb = $pesanan['status_pembayaran'] == 'sudah_bayar' ? 'badge-success' : 'badge-warning';
        ?>
        <div class="status-item">Status Pesanan: <span class="badge <?php echo $badge_p; ?>"><?php echo $bp; ?></span></div>
        <div class="status-item">Status Pembayaran: <span class="badge <?php echo $bb; ?>"><?php echo $pesanan['status_pembayaran']; ?></span></div>
        <div class="status-item">Metode: <strong><?php echo htmlspecialchars($pesanan['metode_pembayaran'] ?: '-'); ?></strong></div>
        <div class="status-item">Tanggal: <strong><?php echo date('d/m/Y H:i', strtotime($pesanan['tanggal_pesanan'])); ?></strong></div>
    </div>

    <!-- Body -->
    <div class="invoice-body">

        <!-- Info Pelanggan & Pesanan -->
        <div class="info-grid">
            <div class="info-box">
                <h4>Tagihan Kepada</h4>
                <p>
                    <strong><?php echo htmlspecialchars($pesanan['nama_lengkap']); ?></strong><br>
                    <?php echo htmlspecialchars($pesanan['email']); ?><br>
                    <?php echo htmlspecialchars($pesanan['no_telepon'] ?: '-'); ?><br>
                    <?php echo htmlspecialchars($pesanan['alamat'] ?: '-'); ?>
                </p>
            </div>
            <div class="info-box">
                <h4>Catatan Pesanan</h4>
                <p><?php echo htmlspecialchars($pesanan['catatan'] ?: 'Tidak ada catatan.'); ?></p>
            </div>
        </div>

        <!-- Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Menu</th>
                    <th class="text-right">Harga Satuan</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td style="color:#aaa;"><?php echo $i + 1; ?></td>
                    <td class="item-name">
                        <?php echo htmlspecialchars($item['nama_menu']); ?>
                        <?php if(!empty($item['varian'])): ?>
                        <div style="font-size:11px;color:#888;margin-top:2px;font-weight:normal;"><?php echo htmlspecialchars($item['varian']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">Rp <?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?></td>
                    <td class="text-right"><?php echo $item['jumlah']; ?></td>
                    <td class="text-right"><strong>Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <div class="totals-row">
                <span>Subtotal</span>
                <span>Rp <?php echo number_format($subtotal_total, 0, ',', '.'); ?></span>
            </div>
            <div class="totals-row">
                <span>Biaya Layanan</span>
                <span>Rp 0</span>
            </div>
            <div class="totals-row">
                <span>TOTAL</span>
                <span>Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></span>
            </div>
        </div>

    </div><!-- .invoice-body -->

    <!-- Footer -->
    <div class="invoice-footer">
        <div>
            <p class="thank-you">Terima kasih telah memesan di <?php echo APP_NAME; ?>!</p>
            <p>Invoice ini dicetak pada <?php echo date('d/m/Y H:i'); ?></p>
        </div>
        <p style="font-size:11px;color:#bbb;">© <?php echo date('Y'); ?> <?php echo APP_NAME; ?></p>
    </div>

</div><!-- .invoice-wrap -->

</body>
</html>
