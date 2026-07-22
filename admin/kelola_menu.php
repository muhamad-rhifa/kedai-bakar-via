<?php
// admin/kelola_menu.php

require_once '../includes/db_connect.php';

// Cek login admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Proses tambah menu
if (isset($_POST['tambah'])) {
    $nama_menu = mysqli_real_escape_string($conn, $_POST['nama_menu']);
    $kategori_id = (int)$_POST['kategori_id'];
    $harga = (int)$_POST['harga'];
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $stok = (int)$_POST['stok'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Upload gambar
    $gambar = 'default.jpg';
    $target_dir = "../assets/images/menu/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $gambar = time() . '_' . basename($_FILES['gambar']['name']);
        $target_file = $target_dir . $gambar;
        
        if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
            $gambar = 'default.jpg';
        }
    }
    
    $query = "INSERT INTO menu (nama_menu, kategori_id, harga, deskripsi, stok, status, gambar) 
              VALUES ('$nama_menu', '$kategori_id', '$harga', '$deskripsi', '$stok', '$status', '$gambar')";
    
    if (mysqli_query($conn, $query)) {
        $menu_id = mysqli_insert_id($conn);
        
        // Upload gambar tambahan
        if (isset($_FILES['gambar_tambahan'])) {
            $count = count($_FILES['gambar_tambahan']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['gambar_tambahan']['error'][$i] == 0) {
                    $g_name = time() . '_' . $i . '_' . basename($_FILES['gambar_tambahan']['name'][$i]);
                    if (move_uploaded_file($_FILES['gambar_tambahan']['tmp_name'][$i], $target_dir . $g_name)) {
                        mysqli_query($conn, "INSERT INTO menu_images (menu_id, gambar) VALUES ($menu_id, '$g_name')");
                    }
                }
            }
        }

        // Simpan varian
        if (isset($_POST['varian_grup']) && isset($_POST['varian_nama'])) {
            $v_grup = $_POST['varian_grup'];
            $v_nama = $_POST['varian_nama'];
            $v_harga = $_POST['varian_harga'];
            for ($i = 0; $i < count($v_nama); $i++) {
                if (!empty($v_nama[$i])) {
                    $g = mysqli_real_escape_string($conn, $v_grup[$i]);
                    $n = mysqli_real_escape_string($conn, $v_nama[$i]);
                    $h = (int)$v_harga[$i];
                    mysqli_query($conn, "INSERT INTO menu_variants (menu_id, grup, nama, harga) VALUES ($menu_id, '$g', '$n', $h)");
                }
            }
        }
        $success = "Menu berhasil ditambahkan!";
    } else {
        $error = "Gagal: " . mysqli_error($conn);
    }
}

// Proses update menu
if (isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $nama_menu = mysqli_real_escape_string($conn, $_POST['nama_menu']);
    $kategori_id = (int)$_POST['kategori_id'];
    $harga = (int)$_POST['harga'];
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $stok = (int)$_POST['stok'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Ambil gambar lama
    $gambar_lama = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gambar FROM menu WHERE id=$id"))['gambar'];
    $gambar = $gambar_lama;

    // Upload gambar baru jika ada
    $target_dir = "../assets/images/menu/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $gambar = time() . '_' . basename($_FILES['gambar']['name']);
        if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $target_dir . $gambar)) {
            $gambar = $gambar_lama;
        }
    }

    $query = "UPDATE menu SET nama_menu='$nama_menu', kategori_id='$kategori_id', harga='$harga', 
              deskripsi='$deskripsi', stok='$stok', status='$status', gambar='$gambar' WHERE id=$id";

    if (mysqli_query($conn, $query)) {
        // Upload gambar tambahan
        if (isset($_FILES['gambar_tambahan'])) {
            $count = count($_FILES['gambar_tambahan']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['gambar_tambahan']['error'][$i] == 0) {
                    $g_name = time() . '_' . $i . '_' . basename($_FILES['gambar_tambahan']['name'][$i]);
                    if (move_uploaded_file($_FILES['gambar_tambahan']['tmp_name'][$i], $target_dir . $g_name)) {
                        mysqli_query($conn, "INSERT INTO menu_images (menu_id, gambar) VALUES ($id, '$g_name')");
                    }
                }
            }
        }

        // Hapus varian lama dan simpan yang baru
        mysqli_query($conn, "DELETE FROM menu_variants WHERE menu_id=$id");
        if (isset($_POST['varian_grup']) && isset($_POST['varian_nama'])) {
            $v_grup = $_POST['varian_grup'];
            $v_nama = $_POST['varian_nama'];
            $v_harga = $_POST['varian_harga'];
            for ($i = 0; $i < count($v_nama); $i++) {
                if (!empty($v_nama[$i])) {
                    $g = mysqli_real_escape_string($conn, $v_grup[$i]);
                    $n = mysqli_real_escape_string($conn, $v_nama[$i]);
                    $h = (int)$v_harga[$i];
                    mysqli_query($conn, "INSERT INTO menu_variants (menu_id, grup, nama, harga) VALUES ($id, '$g', '$n', $h)");
                }
            }
        }
        $success = "Menu berhasil diupdate!";
        header("Location: kelola_menu.php?updated=1");
        exit();
    } else {
        $error = "Gagal update: " . mysqli_error($conn);
    }
}

if (isset($_GET['updated'])) {
    $success = "Menu berhasil diupdate!";
}

// Proses hapus menu
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM menu WHERE id = $id");
    header("Location: kelola_menu.php");
    exit();
}

// Ambil data menu untuk diedit
$edit_menu = null;
$edit_variants = [];
$edit_images = [];
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_result = mysqli_query($conn, "SELECT * FROM menu WHERE id=$edit_id");
    $edit_menu = mysqli_fetch_assoc($edit_result);
    
    $v_res = mysqli_query($conn, "SELECT * FROM menu_variants WHERE menu_id=$edit_id");
    if($v_res) while($vr = mysqli_fetch_assoc($v_res)) $edit_variants[] = $vr;

    $i_res = mysqli_query($conn, "SELECT * FROM menu_images WHERE menu_id=$edit_id");
    if($i_res) while($ir = mysqli_fetch_assoc($i_res)) $edit_images[] = $ir;
}

// Ambil semua menu
$menu_query = "SELECT m.*, k.nama_kategori 
               FROM menu m 
               LEFT JOIN kategori_menu k ON m.kategori_id = k.id 
               ORDER BY m.id ASC";
$menu_result = mysqli_query($conn, $menu_query);
$total = mysqli_num_rows($menu_result);

// Ambil kategori untuk dropdown
$kategori_query = "SELECT * FROM kategori_menu";
$kategori_result = mysqli_query($conn, $kategori_query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Menu - Admin KBV</title>
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
                <h1><i class="fas fa-hamburger"></i> Kelola Menu</h1>
                <p>Manajemen menu makanan, harga, dan ketersediaan</p>
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
            <i class="fas fa-plus"></i> Tambah Menu Baru
        </button>

        <?php if ($edit_menu): ?>
        <div class="card-form" style="border-left: 4px solid #f59e0b; margin-bottom: 24px;">
            <h2 style="margin-bottom: 20px; font-size: 16px; color: #1a2332;">Edit Menu: <?php echo htmlspecialchars($edit_menu['nama_menu']); ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $edit_menu['id']; ?>">
                
                <div class="form-group">
                    <label>Nama Menu *</label>
                    <input type="text" name="nama_menu" value="<?php echo htmlspecialchars($edit_menu['nama_menu']); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Kategori *</label>
                        <select name="kategori_id" required>
                            <option value="">Pilih Kategori</option>
                            <?php 
                            $kat_edit = mysqli_query($conn, "SELECT * FROM kategori_menu");
                            while ($k = mysqli_fetch_assoc($kat_edit)): 
                            ?>
                            <option value="<?php echo $k['id']; ?>" <?php echo $k['id'] == $edit_menu['kategori_id'] ? 'selected' : ''; ?>>
                                <?php echo $k['nama_kategori']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Harga (Rp) *</label>
                        <input type="number" name="harga" value="<?php echo $edit_menu['harga']; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Stok</label>
                        <input type="number" name="stok" value="<?php echo $edit_menu['stok']; ?>">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="tersedia" <?php echo $edit_menu['status'] == 'tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                            <option value="habis" <?php echo $edit_menu['status'] == 'habis' ? 'selected' : ''; ?>>Habis</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" rows="3"><?php echo htmlspecialchars($edit_menu['deskripsi']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Gambar Baru (kosongkan jika tidak diganti)</label>
                    <?php if ($edit_menu['gambar']): ?>
                        <div style="margin-bottom: 8px;">
                            <img src="../assets/images/menu/<?php echo $edit_menu['gambar']; ?>" 
                                 style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #ddd;"
                                 onerror="this.onerror=null;this.src='https://placehold.co/80x80?text=No+Image'">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="gambar" accept="image/*">
                    <small style="color:#888;">Format: JPG, JPEG, PNG. Max 2MB</small>
                </div>

                <div class="form-group">
                    <label>Gambar Tambahan (Gallery)</label>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
                        <?php foreach($edit_images as $img): ?>
                            <img src="../assets/images/menu/<?php echo $img['gambar']; ?>" style="width:60px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #ddd;">
                        <?php endforeach; ?>
                    </div>
                    <input type="file" name="gambar_tambahan[]" accept="image/*" multiple>
                    <small style="color:#888;">Anda bisa memilih lebih dari satu gambar</small>
                </div>
                
                <div class="form-group">
                    <label>Varian Menu (Opsional)</label>
                    <div id="varian-container-edit">
                        <?php foreach($edit_variants as $ev): ?>
                        <div style="display:flex;gap:10px;margin-bottom:10px;">
                            <input type="text" name="varian_grup[]" value="<?php echo htmlspecialchars($ev['grup']); ?>" placeholder="Grup (Cth: Rasa)" required style="flex:1;">
                            <input type="text" name="varian_nama[]" value="<?php echo htmlspecialchars($ev['nama']); ?>" placeholder="Nama (Cth: Matcha)" required style="flex:1;">
                            <input type="number" name="varian_harga[]" value="<?php echo $ev['harga']; ?>" placeholder="Harga Tambahan" required style="flex:1;">
                            <button type="button" onclick="this.parentElement.remove()" style="background:#dc3545;color:white;border:none;padding:0 10px;border-radius:4px;cursor:pointer;"><i class="fas fa-trash"></i></button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn-small" onclick="addVarianRow('edit')" style="margin-top:8px;">+ Tambah Varian</button>
                </div>
                
                <div style="display:flex;gap:10px;margin-top:20px;">
                    <button type="submit" name="update" class="btn-small btn-success" style="padding:8px 24px;"><i class="fas fa-save"></i> Update</button>
                    <a href="kelola_menu.php" class="btn-small btn-secondary" style="padding:8px 24px;text-decoration:none;">Batal</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div id="formTambah" style="display: none;" class="card-form mb-4">
            <div class="box-header" style="margin-bottom:16px;">
                <h2><i class="fas fa-plus-circle" style="color:var(--p);"></i> Tambah Menu Baru</h2>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Nama Menu *</label>
                    <input type="text" name="nama_menu" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Kategori *</label>
                        <select name="kategori_id" required>
                            <option value="">Pilih Kategori</option>
                            <?php 
                            mysqli_data_seek($kategori_result, 0);
                            while ($k = mysqli_fetch_assoc($kategori_result)): 
                            ?>
                            <option value="<?php echo $k['id']; ?>"><?php echo $k['nama_kategori']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Harga (Rp) *</label>
                        <input type="number" name="harga" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Stok</label>
                        <input type="number" name="stok" value="0">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="tersedia">Tersedia</option>
                            <option value="habis">Habis</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Gambar Utama</label>
                    <input type="file" name="gambar" accept="image/*">
                    <small style="color:#888;">Format: JPG, JPEG, PNG. Max 2MB</small>
                </div>
                
                <div class="form-group">
                    <label>Gambar Tambahan (Gallery)</label>
                    <input type="file" name="gambar_tambahan[]" accept="image/*" multiple>
                    <small style="color:#888;">Anda bisa memilih lebih dari satu gambar sekaligus</small>
                </div>
                
                <div class="form-group">
                    <label>Varian Menu (Opsional)</label>
                    <div id="varian-container-tambah"></div>
                    <button type="button" class="btn-small" onclick="addVarianRow('tambah')" style="margin-top:8px;">+ Tambah Varian</button>
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
                    <i class="fas fa-list" style="color:var(--p);"></i> Daftar Menu
                </h2>
                <span style="font-size:13px;color:#888;"><?php echo $total; ?> menu terdaftar</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="width:50px;">ID</th>
                        <th style="width:70px;">Gambar</th>
                        <th>Nama Menu</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th style="width:130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total > 0): ?>
                        <?php while ($menu = mysqli_fetch_assoc($menu_result)): ?>
                        <tr>
                            <td>#<?php echo $menu['id']; ?></td>
                            <td>
                                <img src="../assets/images/menu/<?php echo $menu['gambar'] ?: 'default.jpg'; ?>" 
                                     style="width:40px;height:40px;border-radius:8px;object-fit:cover;"
                                     onerror="this.onerror=null;this.src='https://placehold.co/40x40?text=No+Image'">
                            </td>
                            <td><strong><?php echo htmlspecialchars($menu['nama_menu']); ?></strong></td>
                            <td><span class="badge" style="background:#f0f0f0;color:#555;"><?php echo htmlspecialchars($menu['nama_kategori'] ?: '-'); ?></span></td>
                            <td><strong>Rp <?php echo number_format($menu['harga'], 0, ',', '.'); ?></strong></td>
                            <td><?php echo $menu['stok']; ?></td>
                            <td>
                                <?php if ($menu['status'] == 'tersedia'): ?>
                                    <span class="badge badge-success">Tersedia</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Habis</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="kelola_menu.php?edit=<?php echo $menu['id']; ?>" class="btn-small btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="kelola_menu.php?hapus=<?php echo $menu['id']; ?>" 
                                   class="btn-small btn-danger" 
                                   onclick="return confirm('Yakin hapus menu ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: #999; padding: 30px;">Belum ada menu terdaftar</td>
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

        function addVarianRow(type) {
            const container = document.getElementById('varian-container-' + type);
            const row = document.createElement('div');
            row.style.display = 'flex';
            row.style.gap = '10px';
            row.style.marginBottom = '10px';
            row.innerHTML = `
                <input type="text" name="varian_grup[]" placeholder="Grup (Cth: Rasa, Ukuran)" required style="flex:1;">
                <input type="text" name="varian_nama[]" placeholder="Pilihan (Cth: Matcha, Jumbo)" required style="flex:1;">
                <input type="number" name="varian_harga[]" placeholder="Harga Tambahan (Cth: 5000)" value="0" required style="flex:1;">
                <button type="button" onclick="this.parentElement.remove()" style="background:#dc3545;color:white;border:none;padding:0 10px;border-radius:4px;cursor:pointer;"><i class="fas fa-trash"></i></button>
            `;
            container.appendChild(row);
        }
    </script>
</body>
</html>
