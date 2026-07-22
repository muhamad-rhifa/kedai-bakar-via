<?php
// includes/db_connect.php
require_once 'config.php';

class Database {
    private $host;
    private $user;
    private $pass;
    private $dbname;
    
    private $conn;
    private $error;
    private static $instance = null;
    
    private function __construct() {
        // Assign di dalam constructor agar konstanta sudah terdefinisi
        $this->host   = DB_HOST;
        $this->user   = DB_USER;
        $this->pass   = DB_PASS;
        $this->dbname = DB_NAME;
        $this->connectDB();
    }
    
    // Singleton pattern
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    private function connectDB() {
        try {
            $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            $this->conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            error_log("Database Connection Error: " . $this->error);
            die("Database connection error. Please try again later.");
        }
    }

    
    public function getConnection() {
        return $this->conn;
    }
    
    public function getError() {
        return $this->error;
    }
    
    public function isConnected() {
        return $this->conn !== null && !$this->conn->connect_error;
    }
    
    // Escape string untuk keamanan
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
}

// Create global database instance
$db = Database::getInstance();
$conn = $db->getConnection();

// Fetch App Name and Promo dynamically
$setting_query = query("SELECT * FROM pengaturan LIMIT 1");
if ($setting_query && num_rows($setting_query) > 0) {
    $setting = fetch_assoc($setting_query);
    define('APP_NAME', $setting['nama_toko'] ?? 'Kedai Bakar Via');
    define('APP_PROMO_TITLE', ($setting['promo_title'] ?? '') ?: 'Promo Spesial Akhir Pekan! 🎉');
    define('APP_PROMO_DESC', ($setting['promo_desc'] ?? '') ?: 'Diskon 20% untuk semua menu bakaran setiap Jumat–Minggu. Jangan sampai kehabisan!');
    define('APP_PROMO_DISCOUNT', ($setting['promo_discount'] ?? '') ?: '20%');
} else {
    define('APP_NAME', 'Kedai Bakar Via');
    define('APP_PROMO_TITLE', 'Promo Spesial Akhir Pekan! 🎉');
    define('APP_PROMO_DESC', 'Diskon 20% untuk semua menu bakaran setiap Jumat–Minggu. Jangan sampai kehabisan!');
    define('APP_PROMO_DISCOUNT', '20%');
}

// Fungsi helper untuk query
function query($sql) {
    global $conn;
    return mysqli_query($conn, $sql);
}

function fetch_assoc($result) {
    return mysqli_fetch_assoc($result);
}

function fetch_all($result) {
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function num_rows($result) {
    return mysqli_num_rows($result);
}

function affected_rows() {
    global $conn;
    return mysqli_affected_rows($conn);
}

function insert_id() {
    global $conn;
    return mysqli_insert_id($conn);
}

// Callback function to inject the dark mode styles and JS toggler
function theme_injector($buffer) {
    // If not HTML content, don't modify it
    if (stripos($buffer, '</head>') === false || stripos($buffer, '</body>') === false) {
        return $buffer;
    }

    // CSS variables and styles override for dark mode
    $darkModeStyles = '
<style id="theme-dark-styles">
    /* Theme Toggle Switch style overrides */
    .dark-mode {
        --primary-color: #b92424;
        --primary-dark: #9e1616;
        --secondary-color: #f3702d;
        --dark-color: #161618;
        --light-color: #0d0d0d;
        --text-color: #f4f4f5;
        --text-light: #a1a1aa;
        --border-color: #27272a;
        --p: #b92424;
        --pd: #9e1616;
        --s: #f3702d;
        --dk: #161618;
        --dk2: #0d0d0d;
        --lt: #0d0d0d;
        --tx: #f4f4f5;
        --txl: #a1a1aa;
        --sh: 0 4px 20px rgba(0, 0, 0, 0.75);
        --sh2: 0 12px 30px rgba(0, 0, 0, 0.9);
    }
    
    .dark-mode body,
    .dark-mode main,
    .dark-mode .content-wrap,
    .dark-mode .main-content {
        background-color: #0d0d0d !important;
        background: #0d0d0d !important;
        color: #f4f4f5 !important;
    }

    .dark-mode .navbar,
    .dark-mode nav,
    .dark-mode .card,
    .dark-mode .menu-item,
    .dark-mode .info-card,
    .dark-mode .cart-items,
    .dark-mode .cart-summary,
    .dark-mode .profile-container,
    .dark-mode .auth-box,
    .dark-mode .box,
    .dark-mode .fitur-card,
    .dark-mode .testi-card,
    .dark-mode .kat-pill,
    .dark-mode .sidebar,
    .dark-mode .top-nav,
    .dark-mode .filter-bar,
    .dark-mode .summary-item,
    .dark-mode table,
    .dark-mode tr,
    .dark-mode .dropdown-menu,
    .dark-mode .table-container,
    .dark-mode .card-form,
    .dark-mode .admin-toast {
        background-color: #161618 !important;
        background: #161618 !important;
        color: #f4f4f5 !important;
        border: 1px solid #27272a !important;
        box-shadow: var(--sh) !important;
    }
    
    .dark-mode .card:hover,
    .dark-mode .menu-item:hover,
    .dark-mode .box:hover,
    .dark-mode .stat-card:hover {
        box-shadow: var(--sh2) !important;
        background-color: #1e1e22 !important;
        background: #1e1e22 !important;
    }
    
    .dark-mode input:not(.hero-search input):not(.hero-search-wrap input),
    .dark-mode textarea,
    .dark-mode select,
    .dark-mode .quantity-control button {
        background-color: #0d0d0d !important;
        color: #f8fafc !important;
        border-color: #3f3f46 !important;
    }
    
    .dark-mode .hero-search input,
    .dark-mode .hero-search-wrap input {
        background-color: #161618 !important;
        color: #f8fafc !important;
        border: none !important;
        outline: none !important;
    }
    
    .dark-mode table th,
    .dark-mode thead tr,
    .dark-mode .sidebar-header,
    .dark-mode .sidebar-section,
    .dark-mode .sidebar-admin {
        background-color: #161618 !important;
        color: #f4f4f5 !important;
        border-bottom: 1px solid #27272a !important;
    }
    
    .dark-mode table td {
        border-color: #27272a !important;
        color: #f4f4f5 !important;
    }
    
    .dark-mode [style*="background: #f8f9fa"],
    .dark-mode [style*="background:#f8f9fa"] {
        background: #161618 !important;
        color: #f4f4f5 !important;
        border: 1px solid #27272a !important;
    }
    
    .dark-mode .brand,
    .dark-mode .brand span,
    .dark-mode .logo-text,
    .dark-mode h1,
    .dark-mode h2,
    .dark-mode h3,
    .dark-mode h4,
    .dark-mode h5,
    .dark-mode h6,
    .dark-mode .nav-link,
    .dark-mode .sidebar-menu a,
    .dark-mode .sidebar-admin-name,
    .dark-mode .navbar-brand a span,
    .dark-mode .mobile-menu-btn,
    .dark-mode .navbar-toggler,
    .dark-mode .navbar-toggler i {
        color: #f8fafc !important;
    }
    
    .dark-mode .nav-links a:hover,
    .dark-mode .nav-link:hover,
    .dark-mode .sidebar-menu a:hover,
    .dark-mode .sidebar-menu a.active {
        background-color: rgba(185, 36, 36, 0.15) !important;
        color: #f3702d !important;
    }
    
    .dark-mode .text-muted,
    .dark-mode .card-text,
    .dark-mode .menu-category,
    .dark-mode .menu-description,
    .dark-mode .sidebar-admin-sub,
    .dark-mode .sidebar-section,
    .dark-mode .stat-label,
    .dark-mode .totals-row span,
    .dark-mode .testi-text,
    .dark-mode .sec-sub,
    .dark-mode .testi-user p,
    .dark-mode .detail-desc {
        color: #a1a1aa !important;
    }
    
    .dark-mode .detail-desc-title,
    .dark-mode .variant-title {
        color: #f8fafc !important;
    }
    
    .dark-mode .variant-btn {
        background-color: #161618 !important;
        border-color: #3f3f46 !important;
        color: #f4f4f5 !important;
    }
    .dark-mode .variant-btn:hover {
        border-color: var(--p) !important;
        color: var(--p) !important;
    }
    .dark-mode .variant-btn.selected {
        background-color: rgba(185, 36, 36, 0.15) !important;
        border-color: var(--p) !important;
        color: var(--p) !important;
    }
    
    .dark-mode iframe,
    .dark-mode .map-container img {
        filter: invert(90%) hue-rotate(180deg);
    }
    
    .dark-mode section:not(.hero) {
        background-color: #0d0d0d !important;
        background: #0d0d0d !important;
        color: #f4f4f5 !important;
    }
    
    .dark-mode table tr:hover td {
        background: #1c1c1f !important;
    }

    /* Pengaturan & Pengaturan Footer Dark Mode Overrides */
    .dark-mode .settings-card,
    .dark-mode .modal-box,
    .dark-mode .link-table th {
        background-color: #161618 !important;
        background: #161618 !important;
        border-color: #27272a !important;
    }
    
    .dark-mode .form-group label,
    .dark-mode .settings-header h2,
    .dark-mode .modal-title,
    .dark-mode .link-table th,
    .dark-mode .link-table td {
        color: #f4f4f5 !important;
    }
    
    .dark-mode .help-text,
    .dark-mode .tab-btn:not(.active) {
        color: #a1a1aa !important;
    }

    @media print {
        #themeToggleBtn {
            display: none !important;
        }
    }
</style>
<script>
    (function() {
        var theme = localStorage.getItem("theme");
        if (theme === "dark" || (!theme && window.matchMedia("(prefers-color-scheme: dark)").matches)) {
            document.documentElement.classList.add("dark-mode");
        }
    })();
</script>
';

    $darkModeToggle = '
<button id="themeToggleBtn" aria-label="Toggle Theme" style="
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #eb570d;
    color: white;
    border: 2px solid white;
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    cursor: pointer;
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
" onmouseover="this.style.transform=\'scale(1.15) rotate(15deg)\'" onmouseout="this.style.transform=\'scale(1) rotate(0deg)\'">
    <i class="fas fa-moon" id="themeToggleIcon"></i>
</button>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var themeBtn = document.getElementById("themeToggleBtn");
    var themeIcon = document.getElementById("themeToggleIcon");
    
    function applyTheme(isDark) {
        if (isDark) {
            document.body.classList.add("dark-mode");
            document.documentElement.classList.add("dark-mode");
            if (themeIcon) themeIcon.className = "fas fa-sun";
            themeBtn.style.background = "#161618";
        } else {
            document.body.classList.remove("dark-mode");
            document.documentElement.classList.remove("dark-mode");
            if (themeIcon) themeIcon.className = "fas fa-moon";
            themeBtn.style.background = "#eb570d";
        }
    }
    
    var theme = localStorage.getItem("theme");
    var isDark = theme === "dark" || (!theme && window.matchMedia("(prefers-color-scheme: dark)").matches);
    applyTheme(isDark);
    
    themeBtn.addEventListener("click", function() {
        var currentlyDark = document.body.classList.contains("dark-mode");
        var nextDark = !currentlyDark;
        applyTheme(nextDark);
        localStorage.setItem("theme", nextDark ? "dark" : "light");
    });
});
</script>
';

    // Inject styles before </head>
    $buffer = str_ireplace('</head>', $darkModeStyles . '</head>', $buffer);
    
    // Inject button and script before </body>
    $buffer = str_ireplace('</body>', $darkModeToggle . '</body>', $buffer);
    
    return $buffer;
}

ob_start("theme_injector");
?>