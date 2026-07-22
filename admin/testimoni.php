<?php
// admin/testimoni.php

require_once '../includes/db_connect.php';

// Cek login admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Proses tambah testimoni
if (isset($_POST['tambah'])) {
    $nama_pelanggan = mysqli_real_escape_string($conn, $_POST['nama_pelanggan']);
    $tipe_pelanggan = mysqli_real_escape_string($conn, $_POST['tipe_pelanggan']);
    $komentar = mysqli_real_escape_string($conn, $_POST['komentar']);
    $rating = (int)$_POST['rating'];
    $is_verified = (int)$_POST['is_verified'];
    
    $gambar = null;
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $target_dir = "../assets/images/testimoni/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $gambar = time() . '_' . basename($_FILES['gambar']['name']);
        if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $target_dir . $gambar)) {
            $gambar = null;
        }
    }
    
    $query = "INSERT INTO testimoni (nama_pelanggan, tipe_pelanggan, komentar, rating, is_verified, gambar) 
              VALUES ('$nama_pelanggan', '$tipe_pelanggan', '$komentar', $rating, $is_verified, " . ($gambar ? "'$gambar'" : "NULL") . ")";
    
    if (mysqli_query($conn, $query)) {
        $success = "Testimoni berhasil ditambahkan!";
    } else {
        $error = "Gagal: " . mysqli_error($conn);
    }
}

// Proses update testimoni
if (isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $nama_pelanggan = mysqli_real_escape_string($conn, $_POST['nama_pelanggan']);
    $tipe_pelanggan = mysqli_real_escape_string($conn, $_POST['tipe_pelanggan']);
    $komentar = mysqli_real_escape_string($conn, $_POST['komentar']);
    $rating = (int)$_POST['rating'];
    $is_verified = (int)$_POST['is_verified'];

    // Ambil gambar lama
    $gambar_lama = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gambar FROM testimoni WHERE id=$id"))['gambar'];
    $gambar = $gambar_lama;

    // Upload gambar baru jika ada
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $target_dir = "../assets/images/testimoni/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $gambar = time() . '_' . basename($_FILES['gambar']['name']);
        if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $target_dir . $gambar)) {
            $gambar = $gambar_lama;
        }
    }

    $query = "UPDATE testimoni SET nama_pelanggan='$nama_pelanggan', tipe_pelanggan='$tipe_pelanggan', 
              komentar='$komentar', rating=$rating, is_verified=$is_verified, gambar=" . ($gambar ? "'$gambar'" : "NULL") . " WHERE id=$id";

    if (mysqli_query($conn, $query)) {
        header("Location: testimoni.php?updated=1");
        exit();
    } else {
        $error = "Gagal update: " . mysqli_error($conn);
    }
}

if (isset($_GET['updated'])) {
    $success = "Testimoni berhasil diupdate!";
}

// Proses hapus testimoni
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM testimoni WHERE id = $id");
    header("Location: testimoni.php");
    exit();
}

// Ambil data untuk diedit
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_result = mysqli_query($conn, "SELECT * FROM testimoni WHERE id=$edit_id");
    $edit_data = mysqli_fetch_assoc($edit_result);
}

// Ambil semua testimoni
$testi_query = "SELECT * FROM testimoni ORDER BY id DESC";
$testi_result = mysqli_query($conn, $testi_query);
$total = mysqli_num_rows($testi_result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Testimoni - Admin KBV</title>
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
                <h1><i class="fas fa-star"></i> Kelola Testimoni</h1>
                <p>Manajemen ulasan pelanggan yang tampil di halaman beranda</p>
            </div>
            <div class="user-info">
                <a href="../index.php" class="beranda-btn"><i class="fas fa-home"></i> Beranda</a>
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <button class="btn-small" onclick="toggleForm()" style="margin-bottom:20px;padding:10px 20px;font-size:13px;">
            <i class="fas fa-plus"></i> Tambah Testimoni
        </button>

        <?php if ($edit_data): ?>
        <div class="card-form" style="border-left: 4px solid #f59e0b; margin-bottom: 24px;">
            <h2 style="margin-bottom: 20px; font-size: 16px; color: #1a2332;">Edit Testimoni</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Pelanggan *</label>
                        <input type="text" name="nama_pelanggan" value="<?php echo htmlspecialchars($edit_data['nama_pelanggan']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Tipe/Lokasi Pelanggan *</label>
                        <input type="text" name="tipe_pelanggan" value="<?php echo htmlspecialchars($edit_data['tipe_pelanggan']); ?>" placeholder="Contoh: Pelanggan Setia — Bogor" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Rating *</label>
                        <select name="rating" required>
                            <option value="5" <?php echo $edit_data['rating'] == 5 ? 'selected' : ''; ?>>5 Bintang</option>
                            <option value="4" <?php echo $edit_data['rating'] == 4 ? 'selected' : ''; ?>>4 Bintang</option>
                            <option value="3" <?php echo $edit_data['rating'] == 3 ? 'selected' : ''; ?>>3 Bintang</option>
                            <option value="2" <?php echo $edit_data['rating'] == 2 ? 'selected' : ''; ?>>2 Bintang</option>
                            <option value="1" <?php echo $edit_data['rating'] == 1 ? 'selected' : ''; ?>>1 Bintang</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status Verifikasi</label>
                        <select name="is_verified">
                            <option value="1" <?php echo $edit_data['is_verified'] == 1 ? 'selected' : ''; ?>>Terverifikasi</option>
                            <option value="0" <?php echo $edit_data['is_verified'] == 0 ? 'selected' : ''; ?>>Tidak Terverifikasi</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Komentar *</label>
                    <textarea name="komentar" rows="4" required><?php echo htmlspecialchars($edit_data['komentar']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Gambar Profil (Kosongkan jika tidak diganti)</label>
                    <?php if (!empty($edit_data['gambar'])): ?>
                        <div style="margin-bottom: 8px;">
                            <img src="../assets/images/testimoni/<?php echo $edit_data['gambar']; ?>" 
                                 style="width:60px;height:60px;object-fit:cover;border-radius:50%;border:1px solid #ddd;"
                                 onerror="this.onerror=null;this.src='https://placehold.co/60x60?text=No+Image'">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="gambar" accept="image/*">
                    <small style="color:#888;">Opsional. Jika dikosongkan, akan otomatis menggunakan inisial nama.</small>
                </div>
                
                <div style="display:flex;gap:10px;margin-top:20px;">
                    <button type="submit" name="update" class="btn-small btn-success" style="padding:8px 24px;"><i class="fas fa-save"></i> Update</button>
                    <a href="testimoni.php" class="btn-small btn-secondary" style="padding:8px 24px;text-decoration:none;">Batal</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div id="formTambah" style="display: none;" class="card-form mb-4">
            <div class="box-header" style="margin-bottom:16px;">
                <h2><i class="fas fa-plus-circle" style="color:var(--p);"></i> Tambah Testimoni Baru</h2>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Pelanggan *</label>
                        <input type="text" name="nama_pelanggan" required>
                    </div>
                    <div class="form-group">
                        <label>Tipe/Lokasi Pelanggan *</label>
                        <input type="text" name="tipe_pelanggan" placeholder="Contoh: Pelanggan Setia — Bogor" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Rating *</label>
                        <select name="rating" required>
                            <option value="5">5 Bintang</option>
                            <option value="4">4 Bintang</option>
                            <option value="3">3 Bintang</option>
                            <option value="2">2 Bintang</option>
                            <option value="1">1 Bintang</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status Verifikasi</label>
                        <select name="is_verified">
                            <option value="1">Terverifikasi</option>
                            <option value="0">Tidak Terverifikasi</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Komentar *</label>
                    <textarea name="komentar" rows="4" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Gambar Profil</label>
                    <input type="file" name="gambar" accept="image/*">
                    <small style="color:#888;">Opsional. Jika dikosongkan, akan otomatis menggunakan inisial nama.</small>
                </div>
                
                <div style="display:flex;gap:10px;margin-top:20px;">
                    <button type="submit" name="tambah" class="btn-small btn-success" style="padding:8px 24px;"><i class="fas fa-save"></i> Simpan</button>
                    <button type="button" class="btn-small btn-secondary" onclick="toggleForm()" style="padding:8px 24px;">Batal</button>
                </div>
            </form>
        </div>

        <div class="table-container" style="margin-top: 24px;">
            <div class="box-header" style="margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;">
                <h2 style="font-size:15px;font-weight:700;color:#1a2332;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-list" style="color:var(--p);"></i> Daftar Testimoni
                </h2>
                <span style="font-size:13px;color:#888;"><?php echo $total; ?> testimoni</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="width:50px;">ID</th>
                        <th>Nama & Tipe</th>
                        <th>Komentar</th>
                        <th>Rating</th>
                        <th>Status</th>
                        <th style="width:130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($testi_result)): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <?php 
                                    if (!empty($row['gambar'])): 
                                        $imgSrc = '../assets/images/testimoni/' . $row['gambar'];
                                    else:
                                        $colors = ['9e1616', 'eb570d', '1a2332', '2d4059'];
                                        $bgColor = $colors[strlen($row['nama_pelanggan']) % 4];
                                        $imgSrc = 'https://ui-avatars.com/api/?name=' . urlencode($row['nama_pelanggan']) . '&background=' . $bgColor . '&color=fff&size=88';
                                    endif;
                                    ?>
                                    <img src="<?php echo $imgSrc; ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($row['nama_pelanggan']); ?></strong><br>
                                        <small style="color:#666;"><?php echo htmlspecialchars($row['tipe_pelanggan']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span style="font-style:italic;color:#444;">"<?php echo htmlspecialchars($row['komentar']); ?>"</span></td>
                            <td>
                                <span style="color:#f59e0b;font-size:12px;">
                                    <?php echo str_repeat('<i class="fas fa-star"></i>', $row['rating']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['is_verified']): ?>
                                    <span class="badge badge-success" style="background:#dcfce7;color:#166534;"><i class="fas fa-check-circle"></i> Terverifikasi</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Biasa</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="testimoni.php?edit=<?php echo $row['id']; ?>" class="btn-small btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="testimoni.php?hapus=<?php echo $row['id']; ?>" 
                                   class="btn-small btn-danger" 
                                   onclick="return confirm('Yakin hapus testimoni ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #999; padding: 30px;">Belum ada testimoni.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleForm() {
            var form = document.getElementById('formTambah');
            if (form.style.display === 'none') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }
    </script>
</body>
</html>
