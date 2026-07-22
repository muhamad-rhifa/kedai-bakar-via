// assets/js/script.js

// =====================================================
// GLOBAL VARIABLES
// =====================================================
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi semua komponen
    initNavbar();
    initBackToTop();
    initQuantityControls();
    initFormValidation();
    initSearchForm();
    initDropdowns();
    initCartUpdates();
    initAlerts();
});

// =====================================================
// NAVBAR TOGGLE (Mobile)
// =====================================================
function initNavbar() {
    const navbarToggler = document.getElementById('navbarToggler');
    const navbarMenu = document.getElementById('navbarMenu');
    
    if (navbarToggler && navbarMenu) {
        navbarToggler.addEventListener('click', function() {
            navbarMenu.classList.toggle('show');
        });
        
        // Tutup navbar saat klik di luar
        document.addEventListener('click', function(event) {
            if (!navbarToggler.contains(event.target) && !navbarMenu.contains(event.target)) {
                navbarMenu.classList.remove('show');
            }
        });
    }
}

// =====================================================
// BACK TO TOP BUTTON
// =====================================================
function initBackToTop() {
    const backToTop = document.getElementById('backToTop');
    
    if (backToTop) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTop.classList.add('show');
            } else {
                backToTop.classList.remove('show');
            }
        });
        
        backToTop.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
}

// =====================================================
// QUANTITY CONTROLS
// =====================================================
function initQuantityControls() {
    document.querySelectorAll('.quantity-control').forEach(control => {
        const input = control.querySelector('input[type="number"]');
        const decreaseBtn = control.querySelector('.decrease');
        const increaseBtn = control.querySelector('.increase');
        
        if (decreaseBtn) {
            decreaseBtn.addEventListener('click', function() {
                let value = parseInt(input.value);
                if (value > parseInt(input.min || 1)) {
                    input.value = value - 1;
                    updateCartItem(input.dataset.itemId, input.value);
                }
            });
        }
        
        if (increaseBtn) {
            increaseBtn.addEventListener('click', function() {
                let value = parseInt(input.value);
                if (value < parseInt(input.max || 99)) {
                    input.value = value + 1;
                    updateCartItem(input.dataset.itemId, input.value);
                }
            });
        }
        
        if (input) {
            input.addEventListener('change', function() {
                let value = parseInt(this.value);
                let min = parseInt(this.min || 1);
                let max = parseInt(this.max || 99);
                
                if (value < min) this.value = min;
                if (value > max) this.value = max;
                
                updateCartItem(this.dataset.itemId, this.value);
            });
        }
    });
}

// =====================================================
// UPDATE CART ITEM
// =====================================================
function updateCartItem(itemId, quantity) {
    // Update dilakukan via form submit di keranjang.php
}

// =====================================================
// UPDATE CART TOTAL
// =====================================================
function updateCartTotal(data) {
    const cartTotal = document.getElementById('cartTotal');
    const cartCount = document.getElementById('cartCount');
    if (cartTotal) cartTotal.textContent = formatRupiah(data.total);
    if (cartCount) cartCount.textContent = data.count;
}

// =====================================================
// FORM VALIDATION
// =====================================================
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate="true"]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    inputs.forEach(input => {
        const formGroup = input.closest('.form-group');
        
        if (!input.value.trim()) {
            isValid = false;
            showFieldError(formGroup, 'Field ini wajib diisi');
        } else {
            clearFieldError(formGroup);
        }
        
        // Validasi email
        if (input.type === 'email' && input.value) {
            if (!isValidEmail(input.value)) {
                isValid = false;
                showFieldError(formGroup, 'Format email tidak valid');
            }
        }
        
        // Validasi password
        if (input.type === 'password' && input.value) {
            if (input.value.length < 6) {
                isValid = false;
                showFieldError(formGroup, 'Password minimal 6 karakter');
            }
        }
        
        // Validasi konfirmasi password
        if (input.id === 'confirm_password') {
            const password = document.getElementById('password');
            if (password && input.value !== password.value) {
                isValid = false;
                showFieldError(formGroup, 'Konfirmasi password tidak cocok');
            }
        }
    });
    
    return isValid;
}

function showFieldError(formGroup, message) {
    formGroup.classList.add('error');
    let errorDiv = formGroup.querySelector('.error-message');
    
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        formGroup.appendChild(errorDiv);
    }
    
    errorDiv.textContent = message;
}

function clearFieldError(formGroup) {
    formGroup.classList.remove('error');
    const errorDiv = formGroup.querySelector('.error-message');
    if (errorDiv) {
        errorDiv.remove();
    }
}

function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// =====================================================
// SEARCH FORM
// =====================================================
function initSearchForm() {
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');
    
    if (searchForm && searchInput) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const query = searchInput.value.trim();
            if (query) {
                window.location.href = `user/index.php?search=${encodeURIComponent(query)}`;
            }
        });
    }
}

// =====================================================
// DROPDOWNS
// =====================================================
function initDropdowns() {
    document.querySelectorAll('.dropdown').forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        if (toggle && menu) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                menu.classList.toggle('show');
            });
            
            // Tutup dropdown saat klik di luar
            document.addEventListener('click', function(event) {
                if (!dropdown.contains(event.target)) {
                    menu.classList.remove('show');
                }
            });
        }
    });
}

// =====================================================
// CART UPDATES (Real-time)
// =====================================================
function initCartUpdates() {
    // Tidak perlu polling - badge sudah di-render server-side
}

// =====================================================
// NOTIFICATIONS
// =====================================================
function showNotification(message, type = 'info') {
    // Buat elemen notifikasi
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.textContent = message;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.maxWidth = '300px';
    notification.style.animation = 'slideIn 0.3s ease';
    
    document.body.appendChild(notification);
    
    // Hapus setelah 3 detik
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// =====================================================
// ALERTS AUTO CLOSE
// =====================================================
function initAlerts() {
    document.querySelectorAll('.alert:not(.persistent)').forEach(alert => {
        setTimeout(() => {
            alert.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
}

// =====================================================
// FORMAT RUPIAH
// =====================================================
function formatRupiah(angka) {
    return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// =====================================================
// ADD TO CART
// =====================================================
function addToCart(menuId, quantity = 1) {
    window.location.href = 'user/keranjang.php?add=' + menuId + '&qty=' + quantity;
}

// =====================================================
// REMOVE FROM CART
// =====================================================
function removeFromCart(itemId) {
    if (confirm('Apakah Anda yakin ingin menghapus item ini dari keranjang?')) {
        window.location.href = 'keranjang.php?hapus=' + itemId;
    }
}

// =====================================================
// PREVIEW IMAGE UPLOAD
// =====================================================
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// =====================================================
// CONFIRM ACTION
// =====================================================
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// =====================================================
// LOADING SPINNER
// =====================================================
function showLoading() {
    const spinner = document.createElement('div');
    spinner.className = 'loading-spinner';
    spinner.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(spinner);
}

function hideLoading() {
    const spinner = document.querySelector('.loading-spinner');
    if (spinner) {
        spinner.remove();
    }
}

// =====================================================
// FILTER MENU
// =====================================================
function filterMenu(category) {
    const menuItems = document.querySelectorAll('.menu-item');
    
    menuItems.forEach(item => {
        if (category === 'all' || item.dataset.category === category) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// =====================================================
// SORT MENU
// =====================================================
function sortMenu(criteria) {
    const menuGrid = document.querySelector('.menu-grid');
    const menuItems = Array.from(document.querySelectorAll('.menu-item'));
    
    menuItems.sort((a, b) => {
        switch(criteria) {
            case 'price-asc':
                return getPrice(a) - getPrice(b);
            case 'price-desc':
                return getPrice(b) - getPrice(a);
            case 'name-asc':
                return getName(a).localeCompare(getName(b));
            case 'name-desc':
                return getName(b).localeCompare(getName(a));
            default:
                return 0;
        }
    });
    
    menuItems.forEach(item => menuGrid.appendChild(item));
}

function getPrice(item) {
    const priceEl = item.querySelector('.menu-price');
    return parseInt(priceEl.textContent.replace(/[^0-9]/g, ''));
}

function getName(item) {
    const nameEl = item.querySelector('.menu-name');
    return nameEl.textContent;
}

// =====================================================
// EXPORT FUNCTIONS (untuk digunakan di HTML)
// =====================================================
window.addToCart = addToCart;
window.removeFromCart = removeFromCart;
window.previewImage = previewImage;
window.confirmAction = confirmAction;
window.filterMenu = filterMenu;
window.sortMenu = sortMenu;