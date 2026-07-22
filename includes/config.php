<?php
// includes/config.php
session_start();

// Database configuration (Railway-ready with FrankenPHP support)
define('DB_HOST', $_SERVER['MYSQLHOST'] ?? getenv('MYSQLHOST') ?: 'localhost');
define('DB_USER', $_SERVER['MYSQLUSER'] ?? getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', $_SERVER['MYSQLPASSWORD'] ?? getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME', $_SERVER['MYSQLDATABASE'] ?? getenv('MYSQLDATABASE') ?: 'kedai_bakar_via');
define('DB_PORT', $_SERVER['MYSQLPORT'] ?? getenv('MYSQLPORT') ?: '3306');

// Base URL
define('BASE_URL', 'http://localhost/kedai-bakar-via/');

// Application name (Now dynamically fetched from DB in db_connect.php)
// define('APP_NAME', 'Kedai Bakar Via');

// Contact information
define('CONTACT_PHONE', '0812-3456-7890');
define('CONTACT_EMAIL', 'info@kedaibakarvia.com');
define('CONTACT_ADDRESS', 'Jl. Raya Bakaran No. 123, Jakarta');

// Payment Gateway Configuration (Midtrans)
define('MIDTRANS_SERVER_KEY', 'YOUR_MIDTRANS_SERVER_KEY');
define('MIDTRANS_CLIENT_KEY', 'YOUR_MIDTRANS_CLIENT_KEY');
define('MIDTRANS_IS_PRODUCTION', false);

// Timezone
date_default_timezone_set('Asia/Jakarta');
?>