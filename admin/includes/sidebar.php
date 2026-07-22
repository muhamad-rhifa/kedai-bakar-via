<?php
$current = basename($_SERVER['PHP_SELF']);
$adminName = $_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'Admin';
$foto_nav = $_SESSION['foto_profil'] ?? '';
$avatar_nav = !empty($foto_nav)
    ? '../assets/images/profil/' . $foto_nav
    : 'https://ui-avatars.com/api/?name=' . urlencode($adminName) . '&background=eb570d&color=fff&size=64';
?>

<!-- CRITICAL INLINE CSS TO BYPASS BROWSER CACHE -->
<style>
    .main-content { min-width: 0 !important; }
    .box { overflow-x: auto !important; }
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%) !important;
            transition: transform 0.3s ease !important;
            z-index: 1000 !important;
            width: 260px !important;
            height: 100vh !important;
            bottom: auto !important;
            position: fixed !important;
            left: 0 !important;
            top: 0 !important;
        }
        .sidebar.show { transform: translateX(0) !important; }
        .sidebar-logo, .sidebar-role, .sidebar-admin-name, .sidebar-admin-sub, .sidebar-section { display: block !important; }
        .sidebar-menu a span, .sidebar-logout span { display: inline !important; }
        .sidebar-admin { justify-content: flex-start !important; padding: 16px 24px !important; }
        .sidebar-menu a { justify-content: flex-start !important; padding: 11px 14px !important; }
        .sidebar-menu a i { width: 20px !important; font-size: 15px !important; margin-right: 12px !important; }
        .sidebar-logout { justify-content: flex-start !important; margin: 12px !important; width: calc(100% - 24px) !important; }
        
        .main-content { margin-left: 0 !important; padding: 16px !important; }
        .top-nav { flex-direction: column !important; align-items: flex-start !important; gap: 12px !important; }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        .sidebar-overlay.show { display: block !important; }
    }
    @media (max-width: 480px) {
        .stat-cards { grid-template-columns: 1fr !important; gap: 16px !important; }
        .table-container { padding: 16px !important; }
        .box { padding: 16px !important; }
    }
</style>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">🔥 <?php echo htmlspecialchars(APP_NAME); ?></div>
        <div class="sidebar-role">Admin Panel</div>
    </div>

    <div class="sidebar-admin">
        <img src="<?php echo $avatar_nav; ?>" class="sidebar-admin-avatar" style="object-fit:cover;" 
             onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($adminName); ?>&background=eb570d&color=fff&size=64'">
        <div>
            <div class="sidebar-admin-name"><?php echo htmlspecialchars($adminName); ?></div>
            <div class="sidebar-admin-sub">Administrator</div>
        </div>
    </div>

    <div class="sidebar-section">Menu Utama</div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="<?php echo $current == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-pie"></i><span>Dashboard</span>
        </a>
        <a href="kelola_pesanan.php" class="<?php echo $current == 'kelola_pesanan.php' ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-list"></i><span>Kelola Pesanan</span>
        </a>
        <a href="kelola_menu.php" class="<?php echo $current == 'kelola_menu.php' ? 'active' : ''; ?>">
            <i class="fas fa-utensils"></i><span>Kelola Menu</span>
        </a>
        <a href="kelola_kategori.php" class="<?php echo $current == 'kelola_kategori.php' ? 'active' : ''; ?>">
            <i class="fas fa-tags"></i><span>Kelola Kategori</span>
        </a>
        <a href="kelola_ulasan.php" class="<?php echo $current == 'kelola_ulasan.php' ? 'active' : ''; ?>">
            <i class="fas fa-comments"></i><span>Kelola Ulasan</span>
        </a>

        <div class="sidebar-section">Pengguna & Laporan</div>
        <a href="kelola_user.php" class="<?php echo $current == 'kelola_user.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i><span>Kelola User</span>
        </a>
        <a href="laporan.php" class="<?php echo $current == 'laporan.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i><span>Laporan</span>
        </a>
        
        <div class="sidebar-section">Sistem</div>
        <a href="pengaturan.php" class="<?php echo $current == 'pengaturan.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i><span>Pengaturan</span>
        </a>
        <a href="pengaturan_footer.php" class="<?php echo $current == 'pengaturan_footer.php' ? 'active' : ''; ?>">
            <i class="fas fa-border-bottom"></i><span>Pengaturan Footer</span>
        </a>
        <a href="testimoni.php" class="<?php echo in_array($current, ['testimoni.php', 'testimoni_tambah.php', 'testimoni_edit.php']) ? 'active' : ''; ?>">
            <i class="fas fa-star"></i><span>Testimoni</span>
        </a>
    </div>

    <a href="../auth/logout.php" class="sidebar-logout">
        <i class="fas fa-sign-out-alt"></i><span>Logout</span>
    </a>
</div>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Container for Toast Notifications -->
<div class="admin-toast-container" id="adminToastContainer"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hamburger menu logic
    const topNav = document.querySelector('.page-title') || document.querySelector('.top-nav');
    if (topNav) {
        const btn = document.createElement('button');
        btn.innerHTML = '<i class="fas fa-bars"></i>';
        btn.className = 'mobile-menu-btn';
        btn.style.cssText = 'background:none; border:none; font-size:24px; color:#1a2332; cursor:pointer; margin-right:16px; display:none;';
        
        // Insert button before h1 or inside topNav
        const titleH1 = document.querySelector('.page-title h1');
        if (titleH1) {
            titleH1.parentNode.insertBefore(btn, titleH1);
        } else {
            topNav.prepend(btn);
        }
        
        const style = document.createElement('style');
        style.innerHTML = '@media(max-width:768px) { .mobile-menu-btn { display: inline-block !important; vertical-align: middle; padding: 8px 12px 8px 0; } .page-title h1 { display: inline-block !important; vertical-align: middle; margin-bottom: 0; } .page-title p { display: block; margin-top: 6px; width: 100%; } }';
        document.head.appendChild(style);

        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        if (sidebar && overlay) {
            btn.addEventListener('click', () => {
                sidebar.classList.add('show');
                overlay.classList.add('show');
            });
            
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });
        }
    }

    let lastOrderId = 0;
    let knownPaidOrders = new Set();
    
    // Initial fetch to get the current latest order ID and known paid orders
    fetch('api_cek_pesanan.php?last_id=0')
        .then(response => response.json())
        .then(data => {
            if(data.latest_id) {
                lastOrderId = data.latest_id;
            }
            if(data.paid_orders && Array.isArray(data.paid_orders)) {
                data.paid_orders.forEach(id => knownPaidOrders.add(id));
            }
            // Start polling every 10 seconds
            setInterval(checkNewOrders, 10000);
        })
        .catch(err => console.error('Error fetching initial order ID:', err));

    function checkNewOrders() {
        if(lastOrderId === 0) return; // Wait until we have a base ID
        
        fetch('api_cek_pesanan.php?last_id=' + lastOrderId)
            .then(response => response.json())
            .then(data => {
                let shouldPlaySound = false;

                // Check new orders (just created)
                if(data.new_orders > 0) {
                    lastOrderId = data.latest_id;
                    data.orders_data.forEach(order => {
                        showAdminToast('Pesanan Baru Masuk!', 'Pesanan #'+order.no_pesanan+' baru saja dibuat.', 'kelola_pesanan.php');
                    });
                    shouldPlaySound = true;
                }

                // Check newly paid orders
                if(data.paid_orders && Array.isArray(data.paid_orders)) {
                    data.paid_orders.forEach(order => {
                        if(!knownPaidOrders.has(order.id)) {
                            // It's a newly paid order!
                            knownPaidOrders.add(order.id);
                            showAdminToast('Pembayaran Lunas!', 'Pesanan #'+order.no_pesanan+' telah dibayar.', 'kelola_pesanan.php');
                            shouldPlaySound = true;
                        }
                    });
                }

                if (shouldPlaySound) {
                    let audio = new Audio('../assets/sounds/notif.mp3');
                    audio.play().catch(e => console.log('Audio autoplay blocked by browser', e));
                }
            })
            .catch(err => console.error('Error checking new orders:', err));
    }

    function showAdminToast(title, desc, link) {
        const container = document.getElementById('adminToastContainer');
        const toast = document.createElement('div');
        toast.className = 'admin-toast';
        
        toast.innerHTML = `
            <div class="admin-toast-icon"><i class="fas fa-bell"></i></div>
            <div class="admin-toast-content">
                <div class="admin-toast-title">${title}</div>
                <div class="admin-toast-desc">${desc}</div>
                <a href="${link}" class="admin-toast-action">Lihat Pesanan &rarr;</a>
            </div>
            <button class="admin-toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        `;
        
        container.appendChild(toast);
        
        // Trigger animation
        setTimeout(() => toast.classList.add('show'), 10);
        
        // Auto remove after 8 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 8000);
    }
});
</script>
