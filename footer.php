</div> <!-- End Container -->

<!-- ========================================================
     Add to Cart Success / Notification Popup Modal
     ======================================================== -->
<div id="cartNotificationModal" style="position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); z-index: 9999; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.25s ease;">
    <div id="cartNotificationBox" style="background: var(--container-bg); border: 1px solid var(--border-color); border-radius: 18px; max-width: 440px; width: 90%; padding: 30px 24px; text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); transform: scale(0.9) translateY(20px); transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1); position: relative;">
        <!-- Close button -->
        <button type="button" onclick="closeCartPopup()" style="position: absolute; top: 14px; right: 16px; background: none; border: none; font-size: 1.6rem; color: var(--text-muted); cursor: pointer; line-height: 1;" aria-label="Close">&times;</button>
        
        <!-- Status Icon -->
        <div id="cartPopupIconContainer" style="width: 68px; height: 68px; border-radius: 50%; background: rgba(16, 185, 129, 0.15); border: 2px solid #10b981; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
            <span id="cartPopupIcon" style="font-size: 2.2rem; color: #10b981; line-height: 1;">&#10004;</span>
        </div>

        <h3 id="cartPopupTitle" style="font-family: 'Rajdhani', sans-serif; font-size: 1.7rem; margin: 0 0 8px; color: var(--text-color);">Added to Cart!</h3>
        <p id="cartPopupMessage" style="color: var(--text-muted); font-size: 0.96rem; margin: 0 0 24px; line-height: 1.5;">Item added to cart successfully.</p>

        <div style="display: flex; gap: 12px; justify-content: center;">
            <button type="button" onclick="closeCartPopup()" class="btn" style="background: var(--input-bg); color: var(--text-color); border: 1px solid var(--border-color); flex: 1; padding: 12px 14px; font-size: 0.92rem;">
                Continue Browsing
            </button>
            <a href="cart.php" class="btn" style="flex: 1.2; padding: 12px 14px; text-decoration: none; text-align: center; font-size: 0.92rem;">
                View Cart &rarr;
            </a>
        </div>
    </div>
</div>

<script>
let cartPopupTimer = null;

function showCartSuccessPopup(message, cartCount) {
    const modal = document.getElementById('cartNotificationModal');
    const box = document.getElementById('cartNotificationBox');
    const iconContainer = document.getElementById('cartPopupIconContainer');
    const icon = document.getElementById('cartPopupIcon');
    const title = document.getElementById('cartPopupTitle');
    const msg = document.getElementById('cartPopupMessage');

    iconContainer.style.background = 'rgba(16, 185, 129, 0.15)';
    iconContainer.style.borderColor = '#10b981';
    icon.innerHTML = '&#10004;';
    icon.style.color = '#10b981';
    title.textContent = 'Added to Cart!';
    msg.textContent = message || 'Item added to cart successfully.';

    // Update nav cart badges in header
    const badges = document.querySelectorAll('#navCartBadge');
    badges.forEach(b => {
        b.textContent = cartCount;
        b.style.display = 'inline-block';
    });

    modal.style.opacity = '1';
    modal.style.pointerEvents = 'auto';
    box.style.transform = 'scale(1) translateY(0)';

    clearTimeout(cartPopupTimer);
    cartPopupTimer = setTimeout(closeCartPopup, 4500);
}

function showCartErrorPopup(message) {
    const modal = document.getElementById('cartNotificationModal');
    const box = document.getElementById('cartNotificationBox');
    const iconContainer = document.getElementById('cartPopupIconContainer');
    const icon = document.getElementById('cartPopupIcon');
    const title = document.getElementById('cartPopupTitle');
    const msg = document.getElementById('cartPopupMessage');

    iconContainer.style.background = 'rgba(239, 68, 68, 0.15)';
    iconContainer.style.borderColor = '#ef4444';
    icon.innerHTML = '&#9888;';
    icon.style.color = '#ef4444';
    title.textContent = 'Cannot Add to Cart';
    msg.textContent = message || 'Please check your dates and try again.';

    modal.style.opacity = '1';
    modal.style.pointerEvents = 'auto';
    box.style.transform = 'scale(1) translateY(0)';

    clearTimeout(cartPopupTimer);
    cartPopupTimer = setTimeout(closeCartPopup, 5000);
}

function closeCartPopup() {
    const modal = document.getElementById('cartNotificationModal');
    const box = document.getElementById('cartNotificationBox');
    if (modal && box) {
        modal.style.opacity = '0';
        modal.style.pointerEvents = 'none';
        box.style.transform = 'scale(0.9) translateY(20px)';
    }
}

// Click backdrop to close
document.getElementById('cartNotificationModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeCartPopup();
    }
});

// Escape key to close
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeCartPopup();
});

// Intercept Add to Cart form submissions across all pages (AJAX without redirect)
document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form && form.getAttribute('action') && form.getAttribute('action').includes('cart_action.php')) {
        // Only intercept if it's adding to cart (has gadget_id)
        if (!form.querySelector('input[name="gadget_id"]')) return;

        e.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]') || form.querySelector('.btn');
        let originalText = '';
        if (submitBtn) {
            originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = 'Adding...';
            submitBtn.disabled = true;
        }

        const formData = new FormData(form);
        formData.append('add_to_cart', '1');
        formData.append('ajax', '1');

        fetch('cart_action.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (submitBtn) {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
            if (data.status === 'success') {
                showCartSuccessPopup(data.message, data.cart_count);
            } else {
                showCartErrorPopup(data.message);
            }
        })
        .catch(err => {
            if (submitBtn) {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
            // If network/json error, fallback to normal submission
            form.submit();
        });
    }
});
</script>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script>
window.addEventListener('DOMContentLoaded', function() {
    showCartSuccessPopup("<?php echo addslashes($_SESSION['flash_success']); ?>", <?php echo count($_SESSION['cart'] ?? []); ?>);
});
</script>
<?php unset($_SESSION['flash_success']); endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
<script>
window.addEventListener('DOMContentLoaded', function() {
    showCartErrorPopup("<?php echo addslashes($_SESSION['flash_error']); ?>");
});
</script>
<?php unset($_SESSION['flash_error']); endif; ?>

<footer class="site-footer">
    <!-- Top Glowing Dual-Tone Highlight Bar -->
    <div class="footer-highlight-bar"></div>
    
    <div class="footer-inner">
        <div class="footer-brand-wrap">
            <a href="index.php" class="brand-link" style="padding: 0; justify-content: center; margin-bottom: 8px;">
                <img src="img/onyxplay-icon.png" alt="ONYXPLAY" class="brand-logo-img" style="height: 42px;">
                <span class="brand-text" style="font-size: 1.6rem;">
                    <span class="brand-onyx">ONYX</span><span class="brand-play">PLAY</span>
                </span>
            </a>
            <p class="footer-tagline">Next-Generation Gaming Consoles, VR & High-Performance Gear Rentals</p>
            <div class="footer-badges">
                <span class="footer-pill">⚡ Doorstep Delivery</span>
                <span class="footer-pill">🛡️ Sanitized & Certified Gear</span>
                <span class="footer-pill">🎮 Latest Consoles & Triple-A Titles</span>
            </div>
        </div>

        <div class="footer-links-row">
            <a href="index.php">Home</a>
            <a href="catalog.php">Gear Catalog</a>
            <a href="faq.php">Rental FAQ</a>
            <a href="cart.php">Shopping Cart</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <?php if($_SESSION['role'] === 'admin'): ?>
                    <a href="admin_dashboard.php">Admin Analytics</a>
                    <a href="admin_gadgets.php">Manage Gadgets</a>
                <?php else: ?>
                    <a href="customer_dashboard.php">My Bookings</a>
                    <a href="profile.php">My Profile</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.php">Customer Login</a>
                <a href="register.php">Create Account</a>
            <?php endif; ?>
        </div>

        <div class="footer-copy">
            &copy; <?php echo date('Y'); ?> <strong>ONYXPLAY</strong>. All rights reserved. Built for gamers, by gamers.
        </div>
    </div>
</footer>

</body>
</html>
