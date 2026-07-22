-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 30 Jun 2026 pada 17.39
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30



--
-- Database: `kedai_bakar_via`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id` int(11) NOT NULL,
  `pesanan_id` int(11) DEFAULT NULL,
  `menu_id` int(11) DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL,
  `harga_satuan` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `catatan_item` text DEFAULT NULL,
  `varian` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id`, `pesanan_id`, `menu_id`, `jumlah`, `harga_satuan`, `subtotal`, `catatan_item`, `varian`) VALUES
(27, 16, 1, 1, 10000.00, NULL, NULL, NULL),
(28, 16, 6, 1, 5000.00, NULL, NULL, NULL),
(32, 18, 1, 1, 10000.00, NULL, NULL, NULL),
(33, 18, 9, 1, 7000.00, NULL, NULL, NULL),
(34, 18, 7, 1, 5000.00, NULL, NULL, NULL),
(35, 19, 9, 1, 7000.00, NULL, NULL, NULL),
(36, 19, 14, 1, 10000.00, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `footer_links`
--

CREATE TABLE `footer_links` (
  `id` int(11) NOT NULL,
  `label` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `urutan` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `footer_links`
--

INSERT INTO `footer_links` (`id`, `label`, `url`, `urutan`) VALUES
(1, 'Tentang Kami', 'informasi.php#tentang-kami', 1),
(2, 'Cara Pesan', 'informasi.php#cara-pesan', 2),
(3, 'Kebijakan Privasi', 'informasi.php#kebijakan-privasi', 3),
(4, 'Syarat & Ketentuan', 'informasi.php#syarat-ketentuan', 4);

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori_menu`
--

CREATE TABLE `kategori_menu` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kategori_menu`
--

INSERT INTO `kategori_menu` (`id`, `nama_kategori`, `slug`, `deskripsi`, `icon`, `created_at`) VALUES
(1, 'Makanan', 'bakaran', 'Menu spesial bakaran dengan bumbu khas', 'fa-fire', '2026-03-05 15:26:03'),
(2, 'Minuman', 'minuman', 'Minuman segar pendamping bakaran', 'fa-mug-hot', '2026-03-05 15:26:03'),
(5, 'Snack', 'snack', 'Camilan ringan', 'fa-cookie', '2026-03-05 15:26:03'),
(6, 'Paket Hemat', 'paket-hemat', 'Paket kombo hemat', 'fa-gift', '2026-03-05 15:26:03'),
(7, 'Mie Gacowan', 'mie-gacowan', 'Mie Gacowan dengan level pedas sesuai selera', 'fa-fire', '2026-04-06 13:49:48');

-- --------------------------------------------------------

--
-- Struktur dari tabel `keranjang`
--

CREATE TABLE `keranjang` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `menu_id` int(11) DEFAULT NULL,
  `jumlah` int(11) DEFAULT 1,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `varian` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `keranjang`
--

INSERT INTO `keranjang` (`id`, `user_id`, `menu_id`, `jumlah`, `catatan`, `created_at`, `updated_at`, `varian`) VALUES
(43, 3, 15, 1, NULL, '2026-05-21 12:00:25', '2026-05-21 12:00:25', NULL),
(44, 1, 1, 1, NULL, '2026-05-21 12:33:27', '2026-05-21 12:33:27', NULL),
(47, 3, 8, 3, NULL, '2026-06-15 03:48:37', '2026-06-15 03:57:15', ''),
(48, 3, 7, 2, NULL, '2026-06-15 03:54:38', '2026-06-15 03:54:51', ''),
(49, 3, 6, 1, NULL, '2026-06-30 13:56:53', '2026-06-30 13:56:53', '');

-- --------------------------------------------------------

--
-- Struktur dari tabel `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `nama_menu` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(10,2) NOT NULL,
  `harga_diskon` decimal(10,2) DEFAULT 0.00,
  `gambar` varchar(255) DEFAULT NULL,
  `stok` int(11) DEFAULT 0,
  `status` enum('tersedia','habis') DEFAULT 'tersedia',
  `is_popular` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `menu`
--

INSERT INTO `menu` (`id`, `kategori_id`, `nama_menu`, `slug`, `deskripsi`, `harga`, `harga_diskon`, `gambar`, `stok`, `status`, `is_popular`, `created_at`, `updated_at`) VALUES
(1, 7, 'Mie gacowan pedas', 'ayam-bakar', 'Mie gacowan dengan level pedas sesuai selera', 10000.00, 0.00, '1775649107_mie.webp', 50, 'tersedia', 1, '2026-03-05 15:26:03', '2026-04-20 14:30:05'),
(6, 2, 'Es Teh Manis', 'es-teh-manis', 'Es teh manis segar', 3000.00, 0.00, '1781459450_esteh.jpg', 100, 'tersedia', 1, '2026-03-05 15:26:03', '2026-06-14 18:03:31'),
(7, 2, 'Es Teh Hijau', 'es-jeruk', 'Nikmati sensasi teh hijau premium dengan warna hijau pekat yang menggoda dan rasa yang lebih segar. ', 5000.00, 0.00, '1778763856_teh hijau.png', 80, 'tersedia', 0, '2026-03-05 15:26:03', '2026-05-14 13:04:16'),
(8, 2, 'Puding Mini', 'jus-alpukat', 'Puding mini dengan berbagai rasa', 2500.00, 0.00, '1775741678_puding.webp', 50, 'tersedia', 1, '2026-03-05 15:26:03', '2026-04-09 13:34:38'),
(9, 1, 'Tahu Isi Ayam', 'nasi-putih', 'Tahu goreng dengan isian ayam yang sangat nikmat.', 7000.00, 0.00, '1778763989_tahu.jpg', 30, 'tersedia', 1, '2026-03-05 15:26:03', '2026-05-14 13:06:29'),
(12, 5, 'Tempe Mendoan', 'tahu-crispy', 'Tempe Mendoan yg nikmat', 6000.00, 0.00, '1775741404_tempe.webp', 40, 'tersedia', 0, '2026-03-05 15:26:03', '2026-04-09 13:30:04'),
(14, 1, 'Pempek', '', 'Pempek Palembang, Gurih ikannya berasa, cukonya juara! Checkout sekarang.', 10000.00, 0.00, '1778763161_pempek.jpg', 25, 'tersedia', 0, '2026-05-14 12:49:41', '2026-05-14 12:52:41'),
(15, 1, 'Pentol Kriwil', '', 'Nikmati rasa pentol kriwil yang gurih dan lezat.', 7000.00, 0.00, '1778764085_pentol.jpg', 30, 'tersedia', 0, '2026-05-14 13:08:05', '2026-05-14 13:08:05'),
(16, 1, 'Cireng Isi Ayam', '', 'Cireng dengan isian Ayam yang lezat', 7000.00, 0.00, '1778764213_cireng.jpg', 29, 'tersedia', 0, '2026-05-14 13:10:13', '2026-05-14 13:10:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `menu_images`
--

CREATE TABLE `menu_images` (
  `id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `gambar` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `menu_images`
--

INSERT INTO `menu_images` (`id`, `menu_id`, `gambar`) VALUES
(1, 6, '1781459450_0_logoesteh.jpg');

-- --------------------------------------------------------

--
-- Struktur dari tabel `menu_ulasan`
--

CREATE TABLE `menu_ulasan` (
  `id` int(11) NOT NULL,
  `menu_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `ulasan` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `menu_variants`
--

CREATE TABLE `menu_variants` (
  `id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `grup` varchar(100) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `harga` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `menu_variants`
--

INSERT INTO `menu_variants` (`id`, `menu_id`, `grup`, `nama`, `harga`) VALUES
(2, 6, 'esteh jumbo', 'Jeruk', 2000),
(3, 6, 'esteh reguler', 'ori', 0),
(4, 6, 'esteh jumbo', 'ori', 1000);

-- --------------------------------------------------------

--
-- Struktur dari tabel `metode_pembayaran`
--

CREATE TABLE `metode_pembayaran` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `tipe` enum('qris','transfer','cod','lainnya') DEFAULT 'lainnya',
  `deskripsi` text DEFAULT NULL,
  `instruksi` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'fa-credit-card',
  `aktif` tinyint(1) DEFAULT 1,
  `urutan` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `metode_pembayaran`
--

INSERT INTO `metode_pembayaran` (`id`, `nama`, `tipe`, `deskripsi`, `instruksi`, `icon`, `aktif`, `urutan`, `created_at`) VALUES
(1, 'QRIS', 'qris', 'Bayar dengan scan QR code', 'Scan QR code menggunakan aplikasi dompet digital (GoPay, OVO, Dana, dll)', 'fa-qrcode', 1, 1, '2026-04-16 13:34:47'),
(2, 'Transfer Bank', 'transfer', 'Transfer ke rekening bank', 'Transfer ke rekening berikut:\r\n🏦 BCA: 1234567890\r\n🏦 BRI: 0987654321\r\n🏦 Mandiri: 1122334455\r\nA/N: Kedai Bakar Via', 'fa-university', 1, 2, '2026-04-16 13:34:47'),
(3, 'COD (Bayar di Tempat)', 'cod', 'Bayar saat pesanan tiba', 'Siapkan uang pas saat kurir tiba. Pembayaran dilakukan langsung kepada kurir.', 'fa-money-bill-wave', 1, 3, '2026-04-16 13:34:47');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL,
  `nama_toko` varchar(255) NOT NULL,
  `footer_slogan` text DEFAULT NULL,
  `social_instagram` varchar(255) DEFAULT '#',
  `social_facebook` varchar(255) DEFAULT '#',
  `social_tiktok` varchar(255) DEFAULT '#',
  `social_whatsapp` varchar(255) DEFAULT 'https://wa.me/+6282299241324',
  `kontak_telepon` varchar(100) DEFAULT '+62 822-9924-1324',
  `kontak_email` varchar(255) DEFAULT 'info@kedaibakarvia.com',
  `kontak_alamat` text DEFAULT NULL,
  `kontak_jam_buka` varchar(255) DEFAULT 'Buka 08.00 – 17.00 WIB',
  `footer_copyright` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `nama_toko`, `footer_slogan`, `social_instagram`, `social_facebook`, `social_tiktok`, `social_whatsapp`, `kontak_telepon`, `kontak_email`, `kontak_alamat`, `kontak_jam_buka`, `footer_copyright`) VALUES
(1, 'Kedai Bakar Via', 'Menyajikan kelezatan bakaran dengan bumbu rempah pilihan terbaik sejak 2020.', '#', '#', '#', 'https://wa.me/+6282299241324', '+62 822-9924-1324', 'info@kedaibakarvia.com', 'Jl. Kh. Ahmad Sugriwa, Desa Iwul Parung, Bogor', 'Buka 08.00 – 17.00 WIB', '© {year} {app_name}. All rights reserved. Made with ❤️ in Bogor.');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `no_pesanan` varchar(20) NOT NULL,
  `tanggal_pesanan` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_harga` decimal(10,2) DEFAULT NULL,
  `status_pesanan` enum('menunggu','diproses','selesai','dibatalkan') DEFAULT 'menunggu',
  `metode_pembayaran` varchar(50) DEFAULT NULL,
  `status_pembayaran` enum('belum_bayar','sudah_bayar','gagal') DEFAULT 'belum_bayar',
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `nama_penerima` varchar(100) DEFAULT NULL,
  `no_telepon_penerima` varchar(15) DEFAULT NULL,
  `alamat_pengiriman` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pesanan`
--

INSERT INTO `pesanan` (`id`, `user_id`, `no_pesanan`, `tanggal_pesanan`, `total_harga`, `status_pesanan`, `metode_pembayaran`, `status_pembayaran`, `bukti_pembayaran`, `catatan`, `nama_penerima`, `no_telepon_penerima`, `alamat_pengiriman`, `created_at`) VALUES
(16, 3, 'KBV-20260511-8056', '2026-05-11 12:30:09', 15000.00, 'selesai', 'bri_va', 'sudah_bayar', NULL, 'kh', NULL, NULL, 'mjb', '2026-05-11 12:30:09'),
(18, 3, 'KBV-20260514-8947', '2026-05-14 13:37:26', 22000.00, 'selesai', 'Midtrans Payment Gateway', 'sudah_bayar', NULL, 'mie pedas', NULL, NULL, 'lk barang rt 02 rw 02', '2026-05-14 13:37:26'),
(19, 4, 'KBV-20260521-9735', '2026-05-21 12:51:48', 17000.00, 'diproses', 'Midtrans Payment Gateway', 'sudah_bayar', NULL, '', NULL, NULL, 'https://maps.app.goo.gl/25yK2bkrCS9ATcG16', '2026-05-21 12:51:48');

-- --------------------------------------------------------

--
-- Struktur dari tabel `testimoni`
--

CREATE TABLE `testimoni` (
  `id` int(11) NOT NULL,
  `nama_pelanggan` varchar(100) NOT NULL,
  `tipe_pelanggan` varchar(100) NOT NULL,
  `komentar` text NOT NULL,
  `rating` int(11) DEFAULT 5,
  `is_verified` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `gambar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `testimoni`
--

INSERT INTO `testimoni` (`id`, `nama_pelanggan`, `tipe_pelanggan`, `komentar`, `rating`, `is_verified`, `created_at`, `gambar`) VALUES
(1, 'Budi Santoso', 'Pelanggan Setia ù Bogor', 'Ayam bakarnya enak banget! Bumbunya meresap sampai ke dalam, sambalnya pedas mantap. Sudah jadi langganan tetap keluarga kami.', 5, 1, '2026-05-11 13:10:49', NULL),
(2, 'Siti Aminah', 'Pelanggan Baru ù Jakarta', 'Pelayanan super cepat! Makanan masih hangat saat tiba. Packaging rapih dan higienis. Sangat recommended untuk makan siang kantor.', 5, 1, '2026-05-11 13:10:49', NULL),
(3, 'Ahmad Hidayat', 'Pelanggan VIP ù Depok', 'Harga terjangkau, porsi jumbo! Cocok banget buat makan bareng keluarga. Menu paketnya sangat menghemat pengeluaran.', 5, 1, '2026-05-11 13:10:49', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `no_telepon` varchar(15) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT 'default.jpg',
  `role` enum('admin','user') DEFAULT 'user',
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `bio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `google_id`, `password`, `nama_lengkap`, `no_telepon`, `alamat`, `foto_profil`, `role`, `reset_token`, `reset_expiry`, `created_at`, `updated_at`, `bio`) VALUES
(1, 'admin', 'admin@kedaibakarvia.com', NULL, '$2y$10$x1qgzOvRd99MbEkf/Sl3ge.P4b0vy9VoqwBhX.xX8eL7JkG1qqYAG', 'Administrator', '', '', 'user_1_1776083026.jpg', 'admin', NULL, NULL, '2026-03-05 15:26:03', '2026-04-13 12:23:53', NULL),
(2, 'user_demo', 'user@demo.com', NULL, '$2y$10$3FjB5dA0th8b9zDR0LpMXu9/tkborWwgR6NWO.hH3zcge41B9rSJe', 'User Demo', NULL, NULL, 'default.jpg', 'user', NULL, NULL, '2026-03-05 15:26:03', '2026-03-05 17:44:47', NULL),
(3, 'rhifa', 'muhamadrhifa.a27@gmail.com', NULL, '$2y$10$ny7v66dS1nQ8b9mRbMiADebOjnU5uwPWtLl36tZwpJZ8HNg2nWCbi', 'muhamad rhifa', '082219470118', 'kp.lk barang', 'default.jpg', 'user', NULL, NULL, '2026-04-06 13:12:52', '2026-04-06 13:12:52', NULL),
(4, 'rhifa2', 'muhamadrhifa.a28@gmail.com', NULL, '$2y$10$s4DKttIpIvsSbDmFbSzAKeDF4FO3eWccozR5V9H.iMBrR19ZHRBOK', 'muhmad rhifa', '083844971113', 'https://maps.app.goo.gl/25yK2bkrCS9ATcG16', 'default.jpg', 'user', NULL, NULL, '2026-05-21 12:50:43', '2026-05-21 12:50:43', NULL),
(5, 'testuser', 'testuser@gmail.com', NULL, '$2y$10$HenpSemRNdpUF0f/RXrQvOTyPm22Hvm0yWfRh7g8qpWU7NAoY/R42', 'Test User', '081234567890', 'Jl. Mawar No. 123', 'default.jpg', 'user', NULL, NULL, '2026-05-21 13:19:38', '2026-05-21 13:19:38', NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pesanan_id` (`pesanan_id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Indeks untuk tabel `footer_links`
--
ALTER TABLE `footer_links`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `kategori_menu`
--
ALTER TABLE `kategori_menu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_slug` (`slug`);

--
-- Indeks untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_menu` (`user_id`,`menu_id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Indeks untuk tabel `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_popular` (`is_popular`);

--
-- Indeks untuk tabel `menu_images`
--
ALTER TABLE `menu_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Indeks untuk tabel `menu_ulasan`
--
ALTER TABLE `menu_ulasan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `menu_variants`
--
ALTER TABLE `menu_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Indeks untuk tabel `metode_pembayaran`
--
ALTER TABLE `metode_pembayaran`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_pesanan` (`no_pesanan`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `testimoni`
--
ALTER TABLE `testimoni`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT untuk tabel `footer_links`
--
ALTER TABLE `footer_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `kategori_menu`
--
ALTER TABLE `kategori_menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT untuk tabel `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `menu_images`
--
ALTER TABLE `menu_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `menu_ulasan`
--
ALTER TABLE `menu_ulasan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `menu_variants`
--
ALTER TABLE `menu_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `metode_pembayaran`
--
ALTER TABLE `metode_pembayaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `testimoni`
--
ALTER TABLE `testimoni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_pesanan_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  ADD CONSTRAINT `keranjang_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `keranjang_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `menu`
--
ALTER TABLE `menu`
  ADD CONSTRAINT `menu_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_menu` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `menu_images`
--
ALTER TABLE `menu_images`
  ADD CONSTRAINT `menu_images_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `menu_variants`
--
ALTER TABLE `menu_variants`
  ADD CONSTRAINT `menu_variants_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
