<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONYXPLAY | Next-Gen Gaming Gear & Console Rentals</title>
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link rel="stylesheet" href="style.css?v=<?php echo file_exists('style.css') ? filemtime('style.css') : time(); ?>">
</head>
<body>
<!-- Dual-Tone Cyber Top Laser Highlight Line -->
<div class="top-laser-highlight"></div>

<header>
    <div style="display: flex; align-items: center; gap: 14px;">
        <!-- Logo and Website Name: Opens the Home Page -->
        <a href="index.php" class="brand-link" title="ONYXPLAY Gaming Rentals - Back to Home">
            <img src="img/onyxplay-icon.png" alt="ONYXPLAY Logo" class="brand-logo-img">
            <span class="brand-text">
                <span class="brand-onyx">ONYX</span><span class="brand-play">PLAY</span>
            </span>
        </a>
        <a href="catalog.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'catalog.php') ? 'active' : ''; ?>">Catalog</a>
        <a href="faq.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'faq.php') ? 'active' : ''; ?>">FAQ</a>
    </div>
    <div style="display: flex; align-items: center; gap: 8px;">
        <?php 
        $current_cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
        ?>
        <?php if(isset($_SESSION['user_id'])): ?>
            <?php if($_SESSION['role'] === 'admin'): ?>
                <a href="admin_dashboard.php" class="nav-item">Admin Dashboard</a>
                <a href="admin_gadgets.php" class="nav-item">Manage Gadgets</a>
                <a href="admin_hardware.php" class="nav-item">Manage Hardware</a>
                <a href="admin_users.php" class="nav-item">View Users</a>
            <?php else: ?>
                <a href="cart.php" id="navCartLink" class="nav-item nav-cart-btn">
                    🛒 Cart <span id="navCartBadge" class="nav-cart-badge" style="<?php echo $current_cart_count > 0 ? '' : 'display:none;'; ?>"><?php echo $current_cart_count; ?></span>
                </a>
                <a href="customer_dashboard.php" class="nav-item">My Bookings</a>
                <a href="profile.php" class="nav-item">My Profile</a>
            <?php endif; ?>
            <a href="logout.php" class="nav-item nav-logout-btn">Logout (<?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?>)</a>
        <?php else: ?>
            <a href="cart.php" id="navCartLink" class="nav-item nav-cart-btn">
                🛒 Cart <span id="navCartBadge" class="nav-cart-badge" style="<?php echo $current_cart_count > 0 ? '' : 'display:none;'; ?>"><?php echo $current_cart_count; ?></span>
            </a>
            <a href="login.php" class="nav-item">Login</a>
            <a href="register.php" class="nav-item nav-register-btn">Register</a>
        <?php endif; ?>
        <button class="theme-switch" onclick="toggleTheme()" title="Toggle Dual-Tone Light/Dark Theme">🌗 Theme</button>
    </div>
</header>
<script>
    // Initialize theme from local storage - default to esports gaming dark
    const currentTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', currentTheme);
    
    function toggleTheme() {
        let current = document.documentElement.getAttribute('data-theme') || 'dark';
        let targetTheme = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', targetTheme);
        localStorage.setItem('theme', targetTheme);
    }

    // Brand Logo & Navigation Enhancements
    document.addEventListener('DOMContentLoaded', function() {
        // Intercept clicks on Brand Logo & Name: if on homepage, scroll smoothly to the top
        document.addEventListener('click', function(e) {
            const brandLink = e.target.closest('.brand-link');
            if (brandLink) {
                const isHome = window.location.pathname.endsWith('index.php') || window.location.pathname.endsWith('/gamerental/') || window.location.pathname.endsWith('/');
                if (isHome && !window.location.search) {
                    e.preventDefault();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }
        });
    });
</script>
<div class="container">
