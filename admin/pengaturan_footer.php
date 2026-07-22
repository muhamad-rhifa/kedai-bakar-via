<?php
// admin/pengaturan_footer.php

require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// --- OTOMATIS RUN DATA MIGRATION ---
$cols_to_check = [
    'footer_slogan' => "TEXT NULL",
    'social_instagram' => "VARCHAR(255) DEFAULT '#'",
    'social_facebook' => "VARCHAR(255) DEFAULT '#'",
    'social_tiktok' => "VARCHAR(255) DEFAULT '#'",
    'social_whatsapp' => "VARCHAR(255) DEFAULT 'https://wa.me/+6282299241324'",
    'kontak_telepon' => "VARCHAR(100) DEFAULT '+62 822-9924-1324'",
    'kontak_email' => "VARCHAR(255) DEFAULT 'info@kedaibakarvia.com'",
    'kontak_alamat' => "TEXT NULL",
    'kontak_jam_buka' => "VARCHAR(255) DEFAULT 'Buka 08.00 – 17.00 WIB'",
    'footer_copyright' => "TEXT NULL"
];

foreach ($cols_to_check as $col => $definition) {
    $check_col = mysqli_query($conn, "SHOW COLUMNS FROM pengaturan LIKE '$col'");
    if (mysqli_num_rows($check_col) == 0) {
        mysqli_query($conn, "ALTER TABLE pengaturan ADD COLUMN $col $definition");
    }
}

// Cek default record di tabel pengaturan agar minimal ada 1 baris
$cek_pengaturan = mysqli_query($conn, "SELECT id FROM pengaturan LIMIT 1");
if (mysqli_num_rows($cek_pengaturan) == 0) {
    mysqli_query($conn, "INSERT INTO pengaturan (nama_toko, footer_slogan, kontak_alamat, footer_copyright) VALUES ('Kedai Bakar Via', 'Menyajikan kelezatan bakaran dengan bumbu rempah khas pilihan sejak 2020. Kepuasan pelanggan adalah prioritas utama kami.', 'Jl. Kh. Ahmad Sugriwa, Desa Iwul Parung, Bogor', '© {year} {app_name}. All rights reserved. Made with ❤️ in Bogor.')");
} else {
    $p_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1"));
    if (empty($p_data['footer_slogan'])) {
        mysqli_query($conn, "UPDATE pengaturan SET footer_slogan = 'Menyajikan kelezatan bakaran dengan bumbu rempah khas pilihan sejak 2020. Kepuasan pelanggan adalah prioritas utama kami.'");
    }
    if (empty($p_data['kontak_alamat'])) {
        mysqli_query($conn, "UPDATE pengaturan SET kontak_alamat = 'Jl. Kh. Ahmad Sugriwa, Desa Iwul Parung, Bogor'");
    }
    if (empty($p_data['footer_copyright'])) {
        mysqli_query($conn, "UPDATE pengaturan SET footer_copyright = '© {year} {app_name}. All rights reserved. Made with ❤️ in Bogor.'");
    }
}

// Check and create footer_links table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS footer_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(100) NOT NULL,
    url VARCHAR(255) NOT NULL,
    urutan INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Seed default links in footer_links if empty
$cek_links = mysqli_query($conn, "SELECT id FROM footer_links LIMIT 1");
if (mysqli_num_rows($cek_links) == 0) {
    mysqli_query($conn, "INSERT INTO footer_links (label, url, urutan) VALUES 
        ('Tentang Kami', 'informasi.php#tentang-kami', 1),
        ('Cara Pesan', 'informasi.php#cara-pesan', 2),
        ('Kebijakan Privasi', 'informasi.php#kebijakan-privasi', 3),
        ('Syarat & Ketentuan', 'informasi.php#syarat-ketentuan', 4)
    ");
}
// ------------------------------------

// Proses Simpan Data Footer
if (isset($_POST['simpan_footer'])) {
    $footer_slogan = mysqli_real_escape_string($conn, $_POST['footer_slogan']);
    $social_instagram = mysqli_real_escape_string($conn, $_POST['social_instagram']);
    $social_facebook = mysqli_real_escape_string($conn, $_POST['social_facebook']);
    $social_tiktok = mysqli_real_escape_string($conn, $_POST['social_tiktok']);
    $social_whatsapp = mysqli_real_escape_string($conn, $_POST['social_whatsapp']);
    
    $kontak_telepon = mysqli_real_escape_string($conn, $_POST['kontak_telepon']);
    $kontak_email = mysqli_real_escape_string($conn, $_POST['kontak_email']);
    $kontak_alamat = mysqli_real_escape_string($conn, $_POST['kontak_alamat']);
    $kontak_jam_buka = mysqli_real_escape_string($conn, $_POST['kontak_jam_buka']);
    
    $footer_copyright = mysqli_real_escape_string($conn, $_POST['footer_copyright']);
    
    $update = query("UPDATE pengaturan SET 
        footer_slogan = '$footer_slogan',
        social_instagram = '$social_instagram',
        social_facebook = '$social_facebook',
        social_tiktok = '$social_tiktok',
        social_whatsapp = '$social_whatsapp',
        kontak_telepon = '$kontak_telepon',
        kontak_email = '$kontak_email',
        kontak_alamat = '$kontak_alamat',
        kontak_jam_buka = '$kontak_jam_buka',
        footer_copyright = '$footer_copyright'
    ");
    
    if ($update) {
        $success = "Pengaturan footer berhasil disimpan!";
    } else {
        $error = "Gagal menyimpan pengaturan footer: " . mysqli_error($conn);
    }
}

// Proses Tambah Link
if (isset($_POST['tambah_link'])) {
    $label = mysqli_real_escape_string($conn, $_POST['label']);
    $url = mysqli_real_escape_string($conn, $_POST['url']);
    $urutan = intval($_POST['urutan']);
    
    $insert = query("INSERT INTO footer_links (label, url, urutan) VALUES ('$label', '$url', $urutan)");
    if ($insert) {
        $success = "Link Layanan berhasil ditambahkan!";
    } else {
        $error = "Gagal menambah link: " . mysqli_error($conn);
    }
}

// Proses Edit Link
if (isset($_POST['edit_link'])) {
    $id = intval($_POST['id']);
    $label = mysqli_real_escape_string($conn, $_POST['label']);
    $url = mysqli_real_escape_string($conn, $_POST['url']);
    $urutan = intval($_POST['urutan']);
    
    $update = query("UPDATE footer_links SET label = '$label', url = '$url', urutan = $urutan WHERE id = $id");
    if ($update) {
        $success = "Link Layanan berhasil diperbarui!";
    } else {
        $error = "Gagal memperbarui link: " . mysqli_error($conn);
    }
}

// Proses Hapus Link
if (isset($_GET['hapus_link'])) {
    $id = intval($_GET['hapus_link']);
    $delete = query("DELETE FROM footer_links WHERE id = $id");
    if ($delete) {
        header("Location: pengaturan_footer.php?success=Link+berhasil+dihapus");
        exit();
    } else {
        $error = "Gagal menghapus link: " . mysqli_error($conn);
    }
}

// Mengambil feedback URL
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}

// Ambil data pengaturan saat ini
$pengaturan = fetch_assoc(query("SELECT * FROM pengaturan LIMIT 1"));
$links = fetch_all(query("SELECT * FROM footer_links ORDER BY urutan ASC, label ASC"));

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Footer - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="assets/admin.css?v=2">
    <style>
        .settings-container {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .settings-card {
            background: white;
            border-radius: var(--r);
            padding: 32px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
        }
        .settings-header {
            margin-bottom: 24px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .settings-header h2 {
            font-size: 18px;
            color: var(--dk);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .form-group label {
            font-weight: 600;
            color: var(--dk);
            font-size: 14px;
        }
        .form-group input, .form-group textarea {
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            transition: all 0.2s;
            width: 100%;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--p);
            box-shadow: 0 0 0 3px rgba(158, 22, 22, 0.1);
        }
        .help-text {
            font-size: 12px;
            color: var(--txl);
            margin-top: -4px;
        }
        
        /* Tab CSS */
        .tab-nav {
            display: flex;
            gap: 8px;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 24px;
        }
        .tab-btn {
            padding: 12px 20px;
            font-weight: 700;
            font-size: 14px;
            color: var(--txl);
            background: none;
            border: none;
            cursor: pointer;
            position: relative;
            font-family: 'Inter', sans-serif;
            transition: var(--tr);
        }
        .tab-btn:hover {
            color: var(--p);
        }
        .tab-btn.active {
            color: var(--p);
        }
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--p);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }

        /* Link Management Table */
        .link-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            font-size: 14px;
        }
        .link-table th, .link-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        .link-table th {
            background: #f8fafc;
            color: var(--dk);
            font-weight: 700;
        }
        .link-table tr:hover td {
            background: #fafafa;
        }
        .action-btns {
            display: flex;
            gap: 8px;
        }
        .btn-edit-link {
            background: #f1f5f9;
            color: var(--dk);
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: var(--tr);
        }
        .btn-edit-link:hover {
            background: #e2e8f0;
        }
        .btn-delete-link {
            background: #fee2e2;
            color: #b91c1c;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: var(--tr);
        }
        .btn-delete-link:hover {
            background: #fecaca;
        }

        /* Modal styling */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.show { display: flex; }
        .modal-box {
            background: white;
            border-radius: var(--r);
            padding: 32px;
            width: 90%;
            max-width: 450px;
            box-shadow: var(--sh2);
            animation: modalIn 0.3s ease;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-title {
            font-size: 18px;
            font-weight: 800;
            color: var(--dk);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
        }
        .btn-secondary {
            background: #f1f5f9;
            color: var(--dk);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-primary {
            background: var(--p);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-primary:hover { background: var(--pd); }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-nav">
            <div class="page-title">
                <h1><i class="fas fa-border-bottom"></i> Pengaturan Footer</h1>
                <p>Kelola slogan, info kontak, tautan sosial media, dan menu layanan di bagian kaki halaman</p>
            </div>
            <div class="user-info">
                <a href="../index.php" class="beranda-btn" target="_blank"><i class="fas fa-external-link-alt"></i> Lihat Website</a>
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if (isset($success)) echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> $success</div>"; ?>
        <?php if (isset($error))   echo "<div class='alert alert-error'><i class='fas fa-exclamation-circle'></i> $error</div>"; ?>

        <div class="tab-nav">
            <button class="tab-btn active" onclick="switchTab(event, 'informasi-footer')">Informasi Footer</button>
            <button class="tab-btn" onclick="switchTab(event, 'layanan-footer')">Menu Layanan</button>
        </div>

        <!-- TAB 1: INFORMASI GENERAL FOOTER -->
        <div id="informasi-footer" class="tab-content active">
            <form method="POST">
                <div class="settings-container">
                    <!-- Column 1: Slogan & Deskripsi -->
                    <div class="settings-card">
                        <div class="settings-header">
                            <h2><i class="fas fa-quote-left" style="color: var(--p);"></i> Slogan & Deskripsi Toko</h2>
                        </div>
                        <div class="form-group">
                            <label for="footer_slogan">Slogan / Deskripsi Singkat</label>
                            <textarea id="footer_slogan" name="footer_slogan" rows="3" required placeholder="Tuliskan deskripsi singkat toko Anda di footer..."><?php echo htmlspecialchars($pengaturan['footer_slogan'] ?? ''); ?></textarea>
                            <span class="help-text">Muncul di kolom pertama footer di bawah nama toko Anda.</span>
                        </div>
                    </div>

                    <!-- Column 2: Media Sosial -->
                    <div class="settings-card">
                        <div class="settings-header">
                            <h2><i class="fas fa-share-alt" style="color: var(--p);"></i> Tautan Media Sosial</h2>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="social_instagram"><i class="fab fa-instagram"></i> Instagram Link</label>
                                <input type="text" id="social_instagram" name="social_instagram" value="<?php echo htmlspecialchars($pengaturan['social_instagram'] ?? '#'); ?>" placeholder="#">
                            </div>
                            <div class="form-group">
                                <label for="social_facebook"><i class="fab fa-facebook-f"></i> Facebook Link</label>
                                <input type="text" id="social_facebook" name="social_facebook" value="<?php echo htmlspecialchars($pengaturan['social_facebook'] ?? '#'); ?>" placeholder="#">
                            </div>
                            <div class="form-group">
                                <label for="social_tiktok"><i class="fab fa-tiktok"></i> TikTok Link</label>
                                <input type="text" id="social_tiktok" name="social_tiktok" value="<?php echo htmlspecialchars($pengaturan['social_tiktok'] ?? '#'); ?>" placeholder="#">
                            </div>
                            <div class="form-group">
                                <label for="social_whatsapp"><i class="fab fa-whatsapp"></i> WhatsApp Link</label>
                                <input type="text" id="social_whatsapp" name="social_whatsapp" value="<?php echo htmlspecialchars($pengaturan['social_whatsapp'] ?? '#'); ?>" placeholder="https://wa.me/...">
                            </div>
                        </div>
                    </div>

                    <!-- Column 3: Informasi Kontak -->
                    <div class="settings-card">
                        <div class="settings-header">
                            <h2><i class="fas fa-address-book" style="color: var(--p);"></i> Detail Kontak Utama</h2>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="kontak_telepon"><i class="fas fa-phone"></i> Nomor Telepon</label>
                                <input type="text" id="kontak_telepon" name="kontak_telepon" value="<?php echo htmlspecialchars($pengaturan['kontak_telepon'] ?? ''); ?>" placeholder="Contoh: +62 822-9924-1324">
                            </div>
                            <div class="form-group">
                                <label for="kontak_email"><i class="fas fa-envelope"></i> Email Toko</label>
                                <input type="text" id="kontak_email" name="kontak_email" value="<?php echo htmlspecialchars($pengaturan['kontak_email'] ?? ''); ?>" placeholder="Contoh: info@kedaibakarvia.com">
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="kontak_alamat"><i class="fas fa-map-marker-alt"></i> Alamat Lengkap Toko</label>
                                <textarea id="kontak_alamat" name="kontak_alamat" rows="2" placeholder="Tuliskan alamat fisik toko Anda..."><?php echo htmlspecialchars($pengaturan['kontak_alamat'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="kontak_jam_buka"><i class="fas fa-clock"></i> Jam Operasional / Jam Buka</label>
                                <input type="text" id="kontak_jam_buka" name="kontak_jam_buka" value="<?php echo htmlspecialchars($pengaturan['kontak_jam_buka'] ?? ''); ?>" placeholder="Contoh: Buka 08.00 – 17.00 WIB">
                            </div>
                        </div>
                    </div>

                    <!-- Column 4: Hak Cipta -->
                    <div class="settings-card">
                        <div class="settings-header">
                            <h2><i class="fas fa-copyright" style="color: var(--p);"></i> Teks Hak Cipta (Copyright)</h2>
                        </div>
                        <div class="form-group">
                            <label for="footer_copyright">Teks Copyright</label>
                            <input type="text" id="footer_copyright" name="footer_copyright" value="<?php echo htmlspecialchars($pengaturan['footer_copyright'] ?? ''); ?>" placeholder="© {year} {app_name}. All rights reserved.">
                            <span class="help-text">Gunakan <code>{year}</code> untuk tahun otomatis saat ini, dan <code>{app_name}</code> untuk nama toko dinamis.</span>
                        </div>
                        
                        <div style="margin-top: 24px;">
                            <button type="submit" name="simpan_footer" class="btn-small btn-success" style="padding: 12px 32px; font-size: 14px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fas fa-save"></i> Simpan Informasi Footer
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- TAB 2: MENU LAYANAN FOOTER (LINKS) -->
        <div id="layanan-footer" class="tab-content">
            <div class="settings-card">
                <div class="settings-header">
                    <h2><i class="fas fa-list-ul" style="color: var(--p);"></i> Tautan Menu Layanan</h2>
                    <button class="btn-small btn-success" onclick="openAddModal()" style="display:inline-flex; align-items:center; gap:6px;">
                        <i class="fas fa-plus-circle"></i> Tambah Menu
                    </button>
                </div>
                
                <table class="link-table">
                    <thead>
                        <tr>
                            <th width="30%">Nama Menu</th>
                            <th width="40%">Target URL / Link</th>
                            <th width="15%" style="text-align: center;">Urutan Tampil</th>
                            <th width="15%" style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($links) > 0): ?>
                            <?php foreach ($links as $link): ?>
                                <tr>
                                    <td style="font-weight: 600; color: var(--dk);"><?php echo htmlspecialchars($link['label']); ?></td>
                                    <td><code><?php echo htmlspecialchars($link['url']); ?></code></td>
                                    <td style="text-align: center; font-weight: 700; color: var(--p);"><?php echo $link['urutan']; ?></td>
                                    <td style="text-align: center;">
                                        <div class="action-btns" style="justify-content: center;">
                                            <button class="btn-edit-link" onclick="openEditModal(<?php echo $link['id']; ?>, '<?php echo htmlspecialchars($link['label'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($link['url'], ENT_QUOTES); ?>', <?php echo $link['urutan']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn-delete-link" onclick="confirmDelete(<?php echo $link['id']; ?>, '<?php echo htmlspecialchars($link['label'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-trash-alt"></i> Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--txl); padding: 30px;">
                                    <i class="fas fa-info-circle" style="font-size: 24px; color: #ccc; margin-bottom: 8px; display: block;"></i>
                                    Belum ada menu layanan yang ditambahkan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH LINK -->
    <div class="modal" id="modalTambah">
        <div class="modal-box">
            <div class="modal-title"><i class="fas fa-plus-circle" style="color: var(--p);"></i> Tambah Menu Layanan</div>
            <form method="POST">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="label_tambah">Nama Menu / Label</label>
                    <input type="text" id="label_tambah" name="label" required placeholder="Contoh: Tentang Kami">
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="url_tambah">Target URL / Tujuan Link</label>
                    <input type="text" id="url_tambah" name="url" required placeholder="Contoh: informasi.php#tentang-kami">
                </div>
                <div class="form-group">
                    <label for="urutan_tambah">Urutan Tampil</label>
                    <input type="number" id="urutan_tambah" name="urutan" value="0" min="0" required>
                    <span class="help-text">Angka lebih kecil tampil paling atas.</span>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('modalTambah')">Batal</button>
                    <button type="submit" name="tambah_link" class="btn-primary">Tambah Link</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT LINK -->
    <div class="modal" id="modalEdit">
        <div class="modal-box">
            <div class="modal-title"><i class="fas fa-edit" style="color: var(--p);"></i> Edit Menu Layanan</div>
            <form method="POST">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="edit_label">Nama Menu / Label</label>
                    <input type="text" id="edit_label" name="label" required>
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="edit_url">Target URL / Tujuan Link</label>
                    <input type="text" id="edit_url" name="url" required>
                </div>
                <div class="form-group">
                    <label for="edit_urutan">Urutan Tampil</label>
                    <input type="number" id="edit_urutan" name="urutan" min="0" required>
                    <span class="help-text">Angka lebih kecil tampil paling atas.</span>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('modalEdit')">Batal</button>
                    <button type="submit" name="edit_link" class="btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function switchTab(e, tabId) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            e.currentTarget.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }

        function openAddModal() {
            document.getElementById('modalTambah').classList.add('show');
        }

        function openEditModal(id, label, url, urutan) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_label').value = label;
            document.getElementById('edit_url').value = url;
            document.getElementById('edit_urutan').value = urutan;
            document.getElementById('modalEdit').classList.add('show');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        function confirmDelete(id, label) {
            if (confirm('Apakah Anda yakin ingin menghapus menu layanan "' + label + '"?')) {
                window.location.href = 'pengaturan_footer.php?hapus_link=' + id;
            }
        }

        // Close modal when clicking overlay background
        window.onclick = function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>
