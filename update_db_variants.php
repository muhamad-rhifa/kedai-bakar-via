<?php
require 'includes/db_connect.php';

$queries = [
    // 1. Table menu_images
    "CREATE TABLE IF NOT EXISTS menu_images (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        menu_id INT(11) NOT NULL,
        gambar VARCHAR(255) NOT NULL,
        FOREIGN KEY (menu_id) REFERENCES menu(id) ON DELETE CASCADE
    )",
    
    // 2. Table menu_variants
    "CREATE TABLE IF NOT EXISTS menu_variants (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        menu_id INT(11) NOT NULL,
        grup VARCHAR(100) NOT NULL,
        nama VARCHAR(100) NOT NULL,
        harga INT(11) DEFAULT 0,
        FOREIGN KEY (menu_id) REFERENCES menu(id) ON DELETE CASCADE
    )",

    // 3. Add column varian to keranjang
    "ALTER TABLE keranjang ADD COLUMN varian TEXT NULL",

    // 4. Add column varian to detail_pesanan
    "ALTER TABLE detail_pesanan ADD COLUMN varian TEXT NULL"
];

foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        echo "Success: " . substr($q, 0, 50) . "...\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}
echo "Done.\n";
