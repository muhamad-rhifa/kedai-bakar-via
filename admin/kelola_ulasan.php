<?php
// admin/kelola_ulasan.php

require_once '../includes/db_connect.php';

// Cek login admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Proses Approve/Tolak
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    mysqli_query($conn, "UPDATE menu_ulasan SET is_approved = 1 WHERE id = $id");
    header("Location: kelola_ulasan.php?msg=approved");
    exit();
}
if (isset($_GET['hide'])) {
    $id = (int)$_GET['hide'];
    mysqli_query($conn, "UPDATE menu_ulasan SET is_approved = 0 WHERE id = $id");
    header("Location: kelola_ulasan.php?msg=hidden");
    exit();
}

// Proses Hapus
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM menu_ulasan WHERE id = $id");
    header("Location: kelola_ulasan.php?msg=deleted");
    exit();
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'approved') $success = "Ulasan berhasil disetujui dan kini ditampilkan di halaman menu.";
    if ($_GET['msg'] == 'hidden') $success = "Ulasan berhasil disembunyikan dari halaman menu.";
    if ($_GET['msg'] == 'deleted') $success = "Ulasan berhasil dihapus secara permanen.";
}

// Ambil semua ulasan produk
$query = "SELECT u.*, m.nama_menu, us.nama_lengkap 
          FROM menu_ulasan u 
          JOIN menu m ON u.menu_id = m.id 
          JOIN users us ON u.user_id = us.id 
          ORDER BY u.created_at DESC";

// Jika error missing table, skip sementara karena tabel dibikin pas user akses menu detail.
$result = false;
try {
    $result = mysqli_query($conn, $query);
} catch (Exception $e) {}

$total = $result ? mysqli_num_rows($result) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Ulasan Menu - Admin KBV</title>
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
                <h1><i class="fas fa-comments"></i> Kelola Ulasan Menu</h1>
                <p>Manajemen ulasan dari pembeli pada halaman detail produk</p>
            </div>
            <div class="user-info">
                <a href="../index.php" class="beranda-btn"><i class="fas fa-home"></i> Beranda</a>
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <div class="table-container" style="margin-top: 24px;">
            <div class="box-header" style="margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;">
                <h2 style="font-size:15px;font-weight:700;color:#1a2332;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-list" style="color:var(--p);"></i> Daftar Ulasan Masuk
                </h2>
                <span style="font-size:13px;color:#888;"><?php echo $total; ?> ulasan</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Pembeli</th>
                        <th>Menu</th>
                        <th>Rating</th>
                        <th>Ulasan</th>
                        <th>Status</th>
                        <th style="width:130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td style="font-size:13px;color:#666;"><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['nama_lengkap']); ?></strong></td>
                            <td><a href="../user/detail_menu.php?id=<?php echo $row['menu_id']; ?>" target="_blank" style="color:var(--p);font-weight:600;text-decoration:none;"><?php echo htmlspecialchars($row['nama_menu']); ?></a></td>
                            <td>
                                <span style="color:#f59e0b;font-size:12px;">
                                    <?php echo str_repeat('<i class="fas fa-star"></i>', $row['rating']); ?>
                                </span>
                            </td>
                            <td><span style="font-style:italic;color:#444;">"<?php echo htmlspecialchars($row['ulasan']); ?>"</span></td>
                            <td>
                                <?php if ($row['is_approved']): ?>
                                    <span class="badge badge-success" style="background:#dcfce7;color:#166534;"><i class="fas fa-eye"></i> Tampil</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary" style="background:#f1f5f9;color:#475569;"><i class="fas fa-eye-slash"></i> Tersembunyi</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$row['is_approved']): ?>
                                    <a href="kelola_ulasan.php?approve=<?php echo $row['id']; ?>" class="btn-small btn-success" title="Setujui (Tampilkan)">
                                        <i class="fas fa-check"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="kelola_ulasan.php?hide=<?php echo $row['id']; ?>" class="btn-small btn-secondary" title="Sembunyikan" style="background:#94a3b8;color:#fff;">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="kelola_ulasan.php?hapus=<?php echo $row['id']; ?>" 
                                   class="btn-small btn-danger" 
                                   onclick="return confirm('Yakin hapus ulasan ini?')" title="Hapus Permanen">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #999; padding: 30px;">Belum ada ulasan yang masuk.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
