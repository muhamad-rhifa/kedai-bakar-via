<?php
// includes/config.php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kedai_bakar_via');

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