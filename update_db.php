<?php
require 'includes/db_connect.php';

$queries = [
    "ALTER TABLE pengaturan ADD COLUMN promo_title VARCHAR(255) NULL",
    "ALTER TABLE pengaturan ADD COLUMN promo_desc TEXT NULL",
    "ALTER TABLE pengaturan ADD COLUMN promo_discount VARCHAR(50) NULL",
    "UPDATE pengaturan SET promo_title = 'Promo Spesial Akhir Pekan! 🎉', promo_desc = 'Diskon 20% untuk semua menu bakaran setiap Jumat–Minggu. Jangan sampai kehabisan!', promo_discount = '20%' WHERE id > 0"
];

foreach ($queries as $q) {
    mysqli_query($conn, $q);
    echo mysqli_error($conn) . "\n";
}
echo "Done.\n";
