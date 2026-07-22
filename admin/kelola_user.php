<?php
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ($id != $_SESSION['user_id']) {
        mysqli_query($conn, "DELETE FROM users WHERE id = $id");
    }
    header("Location: kelola_user.php");
    exit();
}

if (isset($_GET['role'])) {
    $id   = (int)$_GET['id'];
    $role = mysqli_real_escape_string($conn, $_GET['role']);
    mysqli_query($conn, "UPDATE users SET role = '$role' WHERE id = $id");
    header("Location: kelola_user.php");
    exit();
}

$user_result = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
$total = mysqli_num_rows($user_result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Admin KBV</title>
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
                <h1><i class="fas fa-users"></i> Kelola User</h1>
                <p>Manajemen akun pengguna platform</p>
            </div>
            <div class="user-info">
                <a href="../index.php" class="beranda-btn"><i class="fas fa-home"></i> Beranda</a>
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="table-container">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid #f0f0f0;">
                <h2 style="font-size:15px;font-weight:700;color:#1a2332;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-list" style="color:var(--p);"></i> Daftar Pengguna
                </h2>
                <span style="font-size:13px;color:#888;"><?php echo $total; ?> pengguna terdaftar</span>
            </div>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>No. Telepon</th>
                            <th>Role</th>
                            <th>Tgl Daftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($u = mysqli_fetch_assoc($user_result)): ?>
                    <tr>
                        <td>#<?php echo $u['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['nama_lengkap']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['no_telepon'] ?: '-'); ?></td>
                        <td>
                            <?php if ($u['role'] == 'admin'): ?>
                                <span class="badge badge-admin">Admin</span>
                            <?php else: ?>
                                <span class="badge badge-user">User</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                        <td style="display:flex;gap:6px;flex-wrap:wrap;">
                            <?php if ($u['role'] == 'admin'): ?>
                                <a href="kelola_user.php?role=user&id=<?php echo $u['id']; ?>" class="btn-small btn-warning"><i class="fas fa-user-minus"></i> Jadikan User</a>
                            <?php else: ?>
                                <a href="kelola_user.php?role=admin&id=<?php echo $u['id']; ?>" class="btn-small"><i class="fas fa-user-shield"></i> Jadikan Admin</a>
                            <?php endif; ?>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <a href="kelola_user.php?hapus=<?php echo $u['id']; ?>" class="btn-small btn-danger"
                                   onclick="return confirm('Yakin hapus user <?php echo htmlspecialchars($u['nama_lengkap']); ?>?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php endif; ?>
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
