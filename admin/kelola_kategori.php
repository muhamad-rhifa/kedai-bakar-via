<?php
// admin/kelola_kategori.php

require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Tambah kategori
if (isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_kategori']);
    $slug = strtolower(str_replace(' ', '-', $nama));
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $icon = $_POST['icon'];
    
    $query = "INSERT INTO kategori_menu (nama_kategori, slug, deskripsi, icon) 
              VALUES ('$nama', '$slug', '$deskripsi', '$icon')";
    
    if (mysqli_query($conn, $query)) {
        $success = "Kategori berhasil ditambahkan!";
    } else {
        $error = "Gagal: " . mysqli_error($conn);
    }
}

// Edit kategori
if (isset($_POST['edit'])) {
    $id       = (int)$_POST['id'];
    $nama     = mysqli_real_escape_string($conn, $_POST['nama_kategori']);
    $slug     = strtolower(preg_replace('/[^a-z0-9]+/', '-', strtolower($nama)));
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $icon     = mysqli_real_escape_string($conn, $_POST['icon']);

    $query = "UPDATE kategori_menu SET nama_kategori='$nama', slug='$slug', deskripsi='$deskripsi', icon='$icon' WHERE id=$id";
    if (mysqli_query($conn, $query)) {
        $success = "Kategori berhasil diperbarui!";
    } else {
        $error = "Gagal: " . mysqli_error($conn);
    }
}

// Hapus kategori
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM kategori_menu WHERE id = $id");
    header("Location: kelola_kategori.php");
    exit();
}

// Ambil semua kategori
$kategori_query = "SELECT * FROM kategori_menu ORDER BY id DESC";
$kategori_result = mysqli_query($conn, $kategori_query);
$total = mysqli_num_rows($kategori_result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Admin KBV</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="assets/admin.css?v=2">
    <style>
        .icon-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 10px; margin-top: 10px; }
        .icon-item { text-align: center; padding: 10px 5px; border: 1.5px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: var(--tr); font-size: 11px; }
        .icon-item:hover { border-color: var(--p); background: #fef2f2; }
        .icon-item i { display: block; font-size: 20px; margin-bottom: 6px; color: #555; }
        .icon-item.selected { background: linear-gradient(135deg, var(--p), var(--s)); color: white; border-color: transparent; }
        .icon-item.selected i { color: white; }
        /* Modal Override */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; }
        .modal-overlay.show { display:flex; }
        .modal-box { background:white; border-radius:var(--r); padding:28px; width:90%; max-width:480px; box-shadow:0 8px 32px rgba(0,0,0,0.2); }
        .modal-box h3 { margin-bottom:20px; font-size:16px; color:#333; border-bottom:1px solid #f0f0f0; padding-bottom:12px; }
        .modal-footer { display:flex; gap:10px; margin-top:24px; justify-content:flex-end; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-nav">
            <div class="page-title">
                <h1><i class="fas fa-tags"></i> Kelola Kategori</h1>
                <p>Manajemen kategori menu makanan dan minuman</p>
            </div>
            <div class="user-info">
                <a href="../index.php" class="beranda-btn"><i class="fas fa-home"></i> Beranda</a>
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if (isset($success)) echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> $success</div>"; ?>
        <?php if (isset($error))   echo "<div class='alert alert-error'><i class='fas fa-exclamation-circle'></i> $error</div>"; ?>

        <button class="btn-small" onclick="toggleForm()" style="margin-bottom:20px;padding:10px 20px;font-size:13px;">
            <i class="fas fa-plus"></i> Tambah Kategori
        </button>

        <div id="formTambah" style="display: none;" class="card-form mb-4">
            <div class="box-header" style="margin-bottom:16px;">
                <h2><i class="fas fa-plus-circle" style="color:var(--p);"></i> Tambah Kategori Baru</h2>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Nama Kategori *</label>
                    <input type="text" name="nama_kategori" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Pilih Icon (Font Awesome)</label>
                    <input type="hidden" name="icon" id="iconInput" value="fa-tag">
                    <div class="icon-grid">
                        <div class="icon-item" onclick="pilihIcon('fa-fire')"><i class="fas fa-fire"></i> fire</div>
                        <div class="icon-item" onclick="pilihIcon('fa-mug-hot')"><i class="fas fa-mug-hot"></i> mug</div>
                        <div class="icon-item" onclick="pilihIcon('fa-utensils')"><i class="fas fa-utensils"></i> nasi</div>
                        <div class="icon-item" onclick="pilihIcon('fa-pepper-hot')"><i class="fas fa-pepper-hot"></i> pedas</div>
                        <div class="icon-item" onclick="pilihIcon('fa-cookie')"><i class="fas fa-cookie"></i> snack</div>
                        <div class="icon-item" onclick="pilihIcon('fa-gift')"><i class="fas fa-gift"></i> paket</div>
                        <div class="icon-item" onclick="pilihIcon('fa-fish')"><i class="fas fa-fish"></i> ikan</div>
                        <div class="icon-item" onclick="pilihIcon('fa-drumstick-bite')"><i class="fas fa-drumstick-bite"></i> ayam</div>
                        <div class="icon-item" onclick="pilihIcon('fa-stroopwafel')"><i class="fas fa-stroopwafel"></i> mie</div>
                        <div class="icon-item selected" onclick="pilihIcon('fa-tag')"><i class="fas fa-tag"></i> lain</div>
                    </div>
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
                    <i class="fas fa-list" style="color:var(--p);"></i> Daftar Kategori
                </h2>
                <span style="font-size:13px;color:#888;"><?php echo $total; ?> kategori</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="width:50px;">ID</th>
                        <th style="width:60px;">Icon</th>
                        <th>Nama Kategori</th>
                        <th>Slug</th>
                        <th>Deskripsi</th>
                        <th style="width:150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($k = mysqli_fetch_assoc($kategori_result)): ?>
                    <tr>
                        <td>#<?php echo $k['id']; ?></td>
                        <td style="text-align:center;"><i class="fas <?php echo $k['icon'] ?: 'fa-tag'; ?>" style="font-size:18px;color:var(--p);"></i></td>
                        <td><strong><?php echo htmlspecialchars($k['nama_kategori']); ?></strong></td>
                        <td><span class="badge" style="background:#f0f0f0;color:#555;"><?php echo htmlspecialchars($k['slug']); ?></span></td>
                        <td style="color:#666;"><?php echo htmlspecialchars($k['deskripsi'] ?: '-'); ?></td>
                        <td>
                            <button class="btn-small btn-warning"
                                onclick="bukaEdit(<?php echo $k['id']; ?>, '<?php echo addslashes($k['nama_kategori']); ?>', '<?php echo addslashes($k['deskripsi']); ?>', '<?php echo $k['icon'] ?: 'fa-tag'; ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="kelola_kategori.php?hapus=<?php echo $k['id']; ?>" class="btn-small btn-danger" onclick="return confirm('Yakin hapus kategori ini?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if($total == 0): ?>
                    <tr><td colspan="6" style="text-align:center;color:#aaa;padding:30px;">Belum ada kategori</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Edit Kategori -->
    <div class="modal-overlay" id="modalEdit">
        <div class="modal-box">
            <h3><i class="fas fa-edit"></i> Edit Kategori</h3>
            <form method="POST">
                <input type="hidden" name="id" id="editId">
                <div class="form-group">
                    <label>Nama Kategori</label>
                    <input type="text" name="nama_kategori" id="editNama" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" id="editDeskripsi" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Icon</label>
                    <input type="hidden" name="icon" id="editIconInput" readonly>
                    <div class="icon-grid">
                        <div class="icon-item edit-icon-item" data-icon="fa-fire"          onclick="pilihIconEdit(this,'fa-fire')"><i class="fas fa-fire"></i> fire</div>
                        <div class="icon-item edit-icon-item" data-icon="fa-mug-hot"       onclick="pilihIconEdit(this,'fa-mug-hot')"><i class="fas fa-mug-hot"></i> mug</div>
                        <div class="icon-item edit-icon-item" data-icon="fa-utensils"      onclick="pilihIconEdit(this,'fa-utensils')"><i class="fas fa-utensils"></i> nasi</div>
                        <div class="icon-item edit-icon-item" data-icon="fa-pepper-hot"    onclick="pilihIconEdit(this,'fa-pepper-hot')"><i class="fas fa-pepper-hot"></i> pedas</div>
                        <div class="icon-item edit-icon-item" data-icon="fa-cookie"        onclick="pilihIconEdit(this,'fa-cookie')"><i class="fas fa-cookie"></i> snack</div>
                        <div class="icon-item edit-icon-item" data-icon="fa-gift"          onclick="pilihIconEdit(this,'fa-gift')"><i class="fas fa-gift"></i> paket</div>
                        <div class="icon-item edit-icon-item" data-icon="fa-fish"          onclick="pilihIconEdit(this,'fa-fish')"><i class="fas fa-fish"></i> ikan</div>
                        <div class="icon-item edit-icon-item" data-icon="fa-drumstick-bite" onclick="pilihIconEdit(this,'fa-drumstick-bite')"><i class="fas fa-drumstick-bite"></i> ayam</div>
                        <div class="icon-item edit-icon-item" data-icon="fa-stroopwafel"   onclick="pilihIconEdit(this,'fa-stroopwafel')"><i class="fas fa-stroopwafel"></i> mie</div>
                        <div class="icon-item edit-icon-item" data-icon="fa-tag"           onclick="pilihIconEdit(this,'fa-tag')"><i class="fas fa-tag"></i> lainnya</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-small btn-secondary" onclick="tutupEdit()" style="padding:8px 20px;">Batal</button>
                    <button type="submit" name="edit" class="btn-small btn-success" style="padding:8px 20px;"><i class="fas fa-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleForm() {
            var form = document.getElementById('formTambah');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
        
        function pilihIcon(icon) {
            document.getElementById('iconInput').value = icon;
            document.querySelectorAll('#formTambah .icon-item').forEach(el => el.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
        }

        function bukaEdit(id, nama, deskripsi, icon) {
            document.getElementById('editId').value          = id;
            document.getElementById('editNama').value        = nama;
            document.getElementById('editDeskripsi').value   = deskripsi;
            document.getElementById('editIconInput').value   = icon;

            document.querySelectorAll('.edit-icon-item').forEach(el => {
                if (el.dataset.icon === icon) {
                    el.classList.add('selected');
                } else {
                    el.classList.remove('selected');
                }
            });

            document.getElementById('modalEdit').classList.add('show');
        }

        function tutupEdit() {
            document.getElementById('modalEdit').classList.remove('show');
        }

        function pilihIconEdit(el, icon) {
            document.getElementById('editIconInput').value = icon;
            document.querySelectorAll('.edit-icon-item').forEach(e => e.classList.remove('selected'));
            el.classList.add('selected');
        }

        document.getElementById('modalEdit').addEventListener('click', function(e) {
            if (e.target === this) tutupEdit();
        });
    </script>
</body>
</html>
