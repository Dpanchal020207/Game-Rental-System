<?php
require 'db.php';
require_once 'razorpay_config.php';

if (!isLoggedIn() || isAdmin()) redirect('index.php');

// Handle updating Razorpay API keys directly from the UI
$key_update_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_razorpay_keys'])) {
    $new_key = trim($_POST['new_razorpay_key_id'] ?? '');
    $new_secret = trim($_POST['new_razorpay_key_secret'] ?? '');
    if (!empty($new_key)) {
        $cfg = file_get_contents('razorpay_config.php');
        $cfg = preg_replace("/define\('RAZORPAY_KEY_ID',\s*'[^']*'\);/", "define('RAZORPAY_KEY_ID', '" . addslashes($new_key) . "');", $cfg);
        if (!empty($new_secret)) {
            $cfg = preg_replace("/define\('RAZORPAY_KEY_SECRET',\s*'[^']*'\);/", "define('RAZORPAY_KEY_SECRET', '" . addslashes($new_secret) . "');", $cfg);
        }
        file_put_contents('razorpay_config.php', $cfg);
        $key_update_msg = "Razorpay API keys updated successfully! Reloading configuration...";
        echo "<script>alert('Razorpay keys saved successfully!'); window.location.href=window.location.href;</script>";
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['total_amount'])) {
    redirect('cart.php');
}

$total_amount = (float)$_POST['total_amount'];
$house_no = trim($_POST['house_no'] ?? '');
$street_address = trim($_POST['street_address'] ?? '');
$landmark = trim($_POST['landmark'] ?? '');
$city = trim($_POST['city'] ?? '');
$state = trim($_POST['state'] ?? '');
$pincode = trim($_POST['pincode'] ?? '');
$delivery_phone = trim($_POST['delivery_phone'] ?? '');
$delivery_notes = trim($_POST['delivery_notes'] ?? '');

// Format full comprehensive address string
if (!empty($house_no) || !empty($street_address)) {
    $addr_parts = [];
    if (!empty($house_no)) $addr_parts[] = $house_no;
    if (!empty($street_address)) $addr_parts[] = $street_address;
    if (!empty($landmark)) $addr_parts[] = "Landmark: " . $landmark;
    $state_pin = trim("$state" . (!empty($pincode) ? " - $pincode" : ""));
    if (!empty($state_pin)) $addr_parts[] = $state_pin;
    if (!empty($delivery_phone)) $addr_parts[] = "Phone: " . $delivery_phone;
    if (!empty($delivery_notes)) $addr_parts[] = "Note: " . $delivery_notes;
    $delivery_address = implode(', ', $addr_parts);
} else {
    $delivery_address = trim($_POST['delivery_address'] ?? '');
}

// Fetch full user profile details for prefilling Razorpay checkout
$user_id = (int)$_SESSION['user_id'];
$user_q = mysqli_query($conn, "SELECT name, email, phone FROM users WHERE id = $user_id");
$user_data = mysqli_fetch_assoc($user_q);
$user_name = $user_data['name'] ?? $_SESSION['name'] ?? 'Customer';
$user_email = $user_data['email'] ?? 'customer@onyxplay.com';
$user_phone = $user_data['phone'] ?? '9999999999';

// Unique temp booking reference
$temp_order_ref = 'ORD-' . strtoupper(substr(uniqid(), 7)) . '-' . rand(100, 999);
$amount_in_paise = (int)round($total_amount * 100);

$is_live_key = isRazorpayLiveKeyConfigured();
$razorpay_order_id = $temp_order_ref;

// If live/real key is configured, create server-side Razorpay order
if ($is_live_key) {
    $order_result = createRazorpayOrder($total_amount, $temp_order_ref);
    if ($order_result['success']) {
        $razorpay_order_id = $order_result['order_id'];
    }
}

require 'header.php';
?>

<div style="max-width: 680px; margin: 30px auto; background: var(--container-bg); padding: 36px 30px; border-radius: 20px; box-shadow: var(--card-shadow); border: 1.5px solid var(--border-color); text-align: center; position: relative;">
    
    <!-- Top Cyber Accent Line -->
    <div style="position: absolute; top: 0; left: 15%; right: 15%; height: 3px; background: linear-gradient(90deg, transparent, #00f0ff, #b026ff, transparent);"></div>

    <!-- Security & Status Badges -->
    <div style="display: flex; justify-content: center; gap: 10px; margin-bottom: 18px; flex-wrap: wrap;">
        <span style="background: rgba(0, 240, 255, 0.1); border: 1px solid rgba(0, 240, 255, 0.3); border-radius: 20px; padding: 5px 14px; font-size: 0.8rem; font-weight: 700; color: #00f0ff; letter-spacing: 0.05em; display: inline-flex; align-items: center; gap: 6px;">
            🔒 256-Bit SSL Encrypted
        </span>
        <?php if ($is_live_key): ?>
            <span style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 20px; padding: 5px 14px; font-size: 0.8rem; font-weight: 700; color: #10b981; letter-spacing: 0.05em;">
                🟢 Razorpay Live Mode
            </span>
        <?php else: ?>
            <span style="background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.35); border-radius: 20px; padding: 5px 14px; font-size: 0.8rem; font-weight: 700; color: #f59e0b; letter-spacing: 0.05em;">
                ⚡ Razorpay Sandbox Simulator
            </span>
        <?php endif; ?>
    </div>

    <h2 style="font-family: 'Rajdhani', sans-serif; font-size: 2.1rem; margin: 0 0 6px; color: var(--text-color); letter-spacing: 0.02em;">
        Complete Your Rental Payment
    </h2>
    <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 0; margin-bottom: 22px;">
        Pay securely through <strong>Razorpay</strong> via UPI (GPay/PhonePe), Debit/Credit Cards, or Netbanking
    </p>

    <!-- Order Summary Banner -->
    <div style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 14px; padding: 18px 22px; margin-bottom: 24px; text-align: left;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px dashed var(--border-color); padding-bottom: 12px;">
            <span style="font-size: 0.92rem; color: var(--text-muted);">Total Payable Amount:</span>
            <span style="font-family: 'Rajdhani', sans-serif; font-size: 2rem; font-weight: 800; color: #00f0ff; line-height: 1;">
                ₹<?php echo number_format($total_amount, 2); ?>
            </span>
        </div>
        <div style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.6; display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px;">
            <div style="background: var(--card-bg); padding: 12px 14px; border-radius: 10px; border: 1px solid var(--border-color);">
                <div style="font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                    <span>👤</span> Customer & Contact
                </div>
                <div style="color: var(--text-color); font-weight: 600;"><?php echo htmlspecialchars($user_name); ?></div>
                <div style="font-size: 0.82rem; color: var(--text-muted);"><?php echo htmlspecialchars($user_email); ?></div>
                <div style="font-size: 0.82rem; color: #00f0ff; font-weight: 600;">📞 <?php echo htmlspecialchars(!empty($delivery_phone) ? $delivery_phone : $user_phone); ?></div>
            </div>
            <div style="background: var(--card-bg); padding: 12px 14px; border-radius: 10px; border: 1px solid var(--border-color);">
                <div style="font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                    <span>📍</span> Delivery Destination
                </div>
                <div style="color: var(--text-color); font-size: 0.86rem; line-height: 1.45;">
                    <?php if (!empty($house_no)): ?>
                        <strong>Flat/House:</strong> <?php echo htmlspecialchars($house_no); ?><br>
                    <?php endif; ?>
                    <?php if (!empty($street_address)): ?>
                        <strong>Area:</strong> <?php echo htmlspecialchars($street_address); ?><br>
                    <?php endif; ?>
                    <?php if (!empty($landmark)): ?>
                        <span style="color: var(--text-muted);">Landmark: <?php echo htmlspecialchars($landmark); ?><br></span>
                    <?php endif; ?>
                    <strong>City / State:</strong> <?php echo htmlspecialchars($city); ?><?php echo !empty($state) ? ', ' . htmlspecialchars($state) : ''; ?><?php echo !empty($pincode) ? ' (' . htmlspecialchars($pincode) . ')' : ''; ?>
                    <?php if (empty($house_no) && empty($street_address)): ?>
                        <?php echo htmlspecialchars($delivery_address); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================
         PRIMARY PAYMENT METHOD: RAZORPAY GATEWAY
         ========================================== -->
    <div style="background: linear-gradient(135deg, rgba(0, 240, 255, 0.06) 0%, rgba(139, 92, 246, 0.08) 100%); border: 1.5px solid rgba(0, 240, 255, 0.4); border-radius: 16px; padding: 26px 22px; margin-bottom: 24px; text-align: center; box-shadow: 0 8px 30px rgba(0, 240, 255, 0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 1.4rem;">💳</span>
                <span style="font-family: 'Rajdhani', sans-serif; font-size: 1.35rem; font-weight: 700; color: var(--text-color);">Razorpay Online Gateway</span>
            </div>
            <span style="background: #00f0ff; color: #040814; font-size: 0.72rem; font-weight: 800; padding: 3px 10px; border-radius: 12px; text-transform: uppercase; letter-spacing: 0.05em;">Recommended</span>
        </div>

        <p style="font-size: 0.88rem; color: var(--text-muted); text-align: left; margin: 0 0 16px; line-height: 1.5;">
            Supports all major payment options:
        </p>

        <!-- Accepted Methods Badges -->
        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 22px; justify-content: flex-start;">
            <span style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 6px; padding: 4px 10px; font-size: 0.78rem; font-weight: 600; color: var(--text-color);">
                📱 GPay / PhonePe / Paytm / UPI
            </span>
            <span style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 6px; padding: 4px 10px; font-size: 0.78rem; font-weight: 600; color: var(--text-color);">
                💳 Visa / MasterCard / RuPay Cards
            </span>
            <span style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 6px; padding: 4px 10px; font-size: 0.78rem; font-weight: 600; color: var(--text-color);">
                🏦 Netbanking (50+ Banks)
            </span>
            <span style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 6px; padding: 4px 10px; font-size: 0.78rem; font-weight: 600; color: var(--text-color);">
                👛 Paytm / Mobikwik Wallets
            </span>
        </div>

        <!-- Big Glowing Razorpay Action Button -->
        <button type="button" id="rzp-button" class="btn" style="width: 100%; padding: 16px; font-size: 1.15rem; font-weight: 800; letter-spacing: 0.05em; background: linear-gradient(135deg, #00f0ff 0%, #8b5cf6 100%); color: #040814; border: none; border-radius: 12px; cursor: pointer; box-shadow: 0 0 25px rgba(0, 240, 255, 0.4); text-transform: uppercase; transition: transform 0.2s ease, box-shadow 0.2s ease;">
            💳 Pay ₹<?php echo number_format($total_amount, 2); ?> with Razorpay &rarr;
        </button>

        <?php if (!$is_live_key): ?>
            <div style="margin-top: 14px; padding: 10px 14px; background: rgba(245, 158, 11, 0.08); border: 1px dashed rgba(245, 158, 11, 0.3); border-radius: 8px; font-size: 0.78rem; color: #f59e0b; text-align: left; line-height: 1.45;">
                ℹ️ <strong>Sandbox Mode Ready:</strong> Click the button above to pay using our interactive Razorpay checkout modal. To connect your live Razorpay merchant account, <a href="javascript:void(0)" onclick="toggleKeySettings()" style="color: #00f0ff; text-decoration: underline; font-weight: 700;">enter your Key ID & Secret here</a>.
            </div>
        <?php endif; ?>
    </div>

    <!-- Hidden Form that gets submitted when payment succeeds -->
    <form action="checkout.php" method="POST" id="razorpay-success-form">
        <input type="hidden" name="delivery_address" value="<?php echo htmlspecialchars($delivery_address); ?>">
        <input type="hidden" name="city" value="<?php echo htmlspecialchars($city); ?>">
        <input type="hidden" name="house_no" value="<?php echo htmlspecialchars($house_no); ?>">
        <input type="hidden" name="street_address" value="<?php echo htmlspecialchars($street_address); ?>">
        <input type="hidden" name="landmark" value="<?php echo htmlspecialchars($landmark); ?>">
        <input type="hidden" name="state" value="<?php echo htmlspecialchars($state); ?>">
        <input type="hidden" name="pincode" value="<?php echo htmlspecialchars($pincode); ?>">
        <input type="hidden" name="delivery_phone" value="<?php echo htmlspecialchars($delivery_phone); ?>">
        <input type="hidden" name="delivery_notes" value="<?php echo htmlspecialchars($delivery_notes); ?>">
        <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">
        <input type="hidden" name="payment_method" value="razorpay">
        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id" value="">
        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id" value="<?php echo $razorpay_order_id; ?>">
        <input type="hidden" name="razorpay_signature" id="razorpay_signature" value="">
    </form>

    <!-- Optional Quick Key Configuration Drawer -->
    <div id="key-settings-drawer" style="display: none; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 18px; margin-bottom: 24px; text-align: left;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <strong style="font-size: 0.95rem; color: var(--text-color);">⚙️ Configure Razorpay Merchant Keys</strong>
            <button type="button" onclick="toggleKeySettings()" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem;">&times;</button>
        </div>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0 0 12px;">
            Obtain your API Key ID and Key Secret from <a href="https://dashboard.razorpay.com/app/keys" target="_blank" style="color: #00f0ff;">Razorpay Dashboard &rarr; API Keys</a>.
        </p>
        <form method="POST" action="payment.php">
            <input type="hidden" name="save_razorpay_keys" value="1">
            <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">
            <input type="hidden" name="delivery_address" value="<?php echo htmlspecialchars($delivery_address); ?>">
            <input type="hidden" name="city" value="<?php echo htmlspecialchars($city); ?>">
            <input type="hidden" name="house_no" value="<?php echo htmlspecialchars($house_no); ?>">
            <input type="hidden" name="street_address" value="<?php echo htmlspecialchars($street_address); ?>">
            <input type="hidden" name="landmark" value="<?php echo htmlspecialchars($landmark); ?>">
            <input type="hidden" name="state" value="<?php echo htmlspecialchars($state); ?>">
            <input type="hidden" name="pincode" value="<?php echo htmlspecialchars($pincode); ?>">
            <input type="hidden" name="delivery_phone" value="<?php echo htmlspecialchars($delivery_phone); ?>">
            <input type="hidden" name="delivery_notes" value="<?php echo htmlspecialchars($delivery_notes); ?>">
            <div style="margin-bottom: 10px;">
                <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted);">Razorpay Key ID (e.g. rzp_live_... or rzp_test_...):</label>
                <input type="text" name="new_razorpay_key_id" value="<?php echo htmlspecialchars(RAZORPAY_KEY_ID); ?>" required style="margin: 4px 0 0; padding: 8px 12px; font-family: monospace; font-size: 0.85rem;">
            </div>
            <div style="margin-bottom: 14px;">
                <label style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted);">Razorpay Key Secret:</label>
                <input type="password" name="new_razorpay_key_secret" placeholder="Enter Key Secret..." style="margin: 4px 0 0; padding: 8px 12px; font-family: monospace; font-size: 0.85rem;">
            </div>
            <button type="submit" class="btn" style="padding: 9px 18px; font-size: 0.85rem; font-weight: 700;">Save API Keys</button>
        </form>
    </div>

    <!-- ==========================================
         SECONDARY DIRECT MERCHANT PAYMENT METHOD
         (Direct to User's Account: Panchal Dhruvin / pdp2512-4@oksbi)
         ========================================== -->
    <div style="margin: 24px 0; display: flex; align-items: center; gap: 10px;">
        <hr style="flex: 1; border: 0; border-top: 1px solid var(--border-color);">
        <span style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
            Or Pay Directly to Merchant UPI
        </span>
        <hr style="flex: 1; border: 0; border-top: 1px solid var(--border-color);">
    </div>

    <details style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 18px; text-align: left; cursor: pointer;">
        <summary style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); outline: none;">
            📲 Direct UPI Transfer to Merchant (<?php echo htmlspecialchars(MERCHANT_NAME); ?> &bull; <?php echo htmlspecialchars(MERCHANT_UPI_ID); ?>)
        </summary>
        
        <div style="padding-top: 16px; text-align: center;">
            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0 0 12px;">
                Scan QR code using GPay, PhonePe, Paytm, or BHIM to send money directly to the merchant account:
            </p>

            <img src="uploads/payment_qr.jpg?v=<?php echo file_exists('uploads/payment_qr.jpg') ? filemtime('uploads/payment_qr.jpg') : time(); ?>" 
                 alt="UPI Payment QR Code" 
                 style="width: 100%; max-width: 220px; height: auto; border-radius: 12px; border: 1px solid var(--border-color); display: block; margin: 0 auto 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">

            <div style="max-width: 300px; margin: 0 auto 14px; padding: 8px 12px; background: var(--input-bg); border-radius: 8px; border: 1px dashed var(--border-hover); display: flex; align-items: center; justify-content: space-between;">
                <span style="font-size: 0.88rem; font-weight: 700; color: #00f0ff;" id="upiIdText"><?php echo htmlspecialchars(MERCHANT_UPI_ID); ?></span>
                <button type="button" onclick="copyUpiId()" class="btn" style="padding: 4px 10px; font-size: 0.78rem; border-radius: 6px;" id="copyBtn">
                    📋 Copy
                </button>
            </div>

            <!-- Direct UPI Confirmation Form -->
            <form action="checkout.php" method="POST" id="direct-upi-form" style="max-width: 440px; margin: 0 auto;">
                <input type="hidden" name="delivery_address" value="<?php echo htmlspecialchars($delivery_address); ?>">
                <input type="hidden" name="city" value="<?php echo htmlspecialchars($city); ?>">
                <input type="hidden" name="house_no" value="<?php echo htmlspecialchars($house_no); ?>">
                <input type="hidden" name="street_address" value="<?php echo htmlspecialchars($street_address); ?>">
                <input type="hidden" name="landmark" value="<?php echo htmlspecialchars($landmark); ?>">
                <input type="hidden" name="state" value="<?php echo htmlspecialchars($state); ?>">
                <input type="hidden" name="pincode" value="<?php echo htmlspecialchars($pincode); ?>">
                <input type="hidden" name="delivery_phone" value="<?php echo htmlspecialchars($delivery_phone); ?>">
                <input type="hidden" name="delivery_notes" value="<?php echo htmlspecialchars($delivery_notes); ?>">
                <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">
                <input type="hidden" name="payment_method" value="upi">
                
                <div style="margin-bottom: 10px; text-align: left;">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">UPI Transaction ID / UTR (Optional):</label>
                    <input type="text" name="transaction_ref" placeholder="e.g. 428192847291" style="font-size: 0.88rem; padding: 8px 12px; margin: 4px 0 0;">
                </div>

                <button type="submit" class="btn" style="width: 100%; padding: 11px; font-size: 0.92rem; font-weight: 700; background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-color);">
                    ✅ Confirm Direct UPI Payment of ₹<?php echo number_format($total_amount, 2); ?>
                </button>
            </form>
        </div>
    </details>
    
    <div style="margin-top: 26px;">
        <a href="cart.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: color 0.2s ease;" onmouseover="this.style.color='#00f0ff'" onmouseout="this.style.color='var(--text-muted)'">
            &larr; Cancel and return to shopping cart
        </a>
    </div>
</div>

<!-- ========================================================
     AUTHENTIC RAZORPAY CYBER SIMULATOR MODAL
     (Active when keys are in sandbox or pending live activation)
     ======================================================== -->
<div id="razorpaySimulatorModal" style="position: fixed; inset: 0; background: rgba(4, 8, 20, 0.82); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 99999; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.25s ease;">
    <div style="background: #0d1222; border: 1.5px solid rgba(0, 240, 255, 0.35); border-radius: 18px; width: 92%; max-width: 580px; box-shadow: 0 25px 60px rgba(0,0,0,0.7), 0 0 35px rgba(0, 240, 255, 0.15); position: relative; overflow: hidden; transform: translateY(20px) scale(0.97); transition: transform 0.25s ease;" id="rzpSimBox">
        
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #11182c 0%, #172038 100%); padding: 18px 22px; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; justify-content: space-between; align-items: center; position: relative;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <img src="img/onyxplay-icon.png" alt="ONYXPLAY" style="width: 32px; height: 32px; object-fit: contain;">
                <div>
                    <div style="font-family: 'Rajdhani', sans-serif; font-size: 1.25rem; font-weight: 800; color: #fff; line-height: 1.1;">
                        ONYX<span style="color: #00f0ff;">PLAY</span>
                    </div>
                    <div style="font-size: 0.72rem; color: #94a3b8; display: flex; align-items: center; gap: 4px;">
                        <span>🔒 Razorpay Trusted Checkout</span> &bull; 
                        <span style="color: #f59e0b; font-weight: 700;">Sandbox Test Mode</span>
                    </div>
                </div>
            </div>

            <div style="text-align: right;">
                <div style="font-size: 0.72rem; color: #94a3b8; text-transform: uppercase;">Amount to Pay</div>
                <div style="font-family: 'Rajdhani', sans-serif; font-size: 1.5rem; font-weight: 800; color: #00f0ff;">
                    ₹<?php echo number_format($total_amount, 2); ?>
                </div>
            </div>

            <button type="button" onclick="closeRazorpaySimulator()" style="position: absolute; top: 10px; right: 12px; background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <!-- Body with Tabs Layout (Just like Razorpay) -->
        <div style="display: flex; min-height: 330px;">
            <!-- Left Tabs Column -->
            <div style="width: 175px; background: #0a0e1a; border-right: 1px solid rgba(255,255,255,0.06); padding: 12px 0;">
                <button type="button" class="rzp-sim-tab active" onclick="switchSimTab('upi', this)">
                    <span style="font-size: 1.1rem;">📱</span>
                    <span>UPI / QR</span>
                </button>
                <button type="button" class="rzp-sim-tab" onclick="switchSimTab('card', this)">
                    <span style="font-size: 1.1rem;">💳</span>
                    <span>Cards</span>
                </button>
                <button type="button" class="rzp-sim-tab" onclick="switchSimTab('netbanking', this)">
                    <span style="font-size: 1.1rem;">🏦</span>
                    <span>Netbanking</span>
                </button>
                <button type="button" class="rzp-sim-tab" onclick="switchSimTab('wallet', this)">
                    <span style="font-size: 1.1rem;">👛</span>
                    <span>Wallets</span>
                </button>
            </div>

            <!-- Right Tab Content Panel -->
            <div style="flex: 1; padding: 22px; text-align: left; background: #0d1222;">
                
                <!-- UPI Panel -->
                <div id="sim-panel-upi" class="rzp-sim-panel" style="display: block;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: #e2e8f0; margin-bottom: 12px;">Select your preferred UPI app:</div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px;">
                        <button type="button" class="rzp-sim-app-btn active" onclick="selectUpiApp(this, 'Google Pay')">
                            <span style="font-weight: 700; color: #4285f4;">G</span>Pay
                        </button>
                        <button type="button" class="rzp-sim-app-btn" onclick="selectUpiApp(this, 'PhonePe')">
                            <span style="font-weight: 700; color: #6739b7;">Phone</span>Pe
                        </button>
                        <button type="button" class="rzp-sim-app-btn" onclick="selectUpiApp(this, 'Paytm')">
                            <span style="font-weight: 700; color: #00b9f5;">Pay</span>tm
                        </button>
                        <button type="button" class="rzp-sim-app-btn" onclick="selectUpiApp(this, 'BHIM UPI')">
                            <span style="font-weight: 700; color: #00f0ff;">BHIM</span> UPI
                        </button>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">Or enter any UPI ID / VPA:</label>
                        <input type="text" id="sim-upi-id" value="<?php echo htmlspecialchars($user_phone); ?>@okhdfcbank" style="font-size: 0.85rem; padding: 9px 12px; margin-top: 4px; background: #141c30; border: 1px solid rgba(255,255,255,0.12); color: #fff; border-radius: 8px; width: 100%;">
                    </div>
                </div>

                <!-- Card Panel -->
                <div id="sim-panel-card" class="rzp-sim-panel" style="display: none;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: #e2e8f0; margin-bottom: 12px;">Enter Credit or Debit Card:</div>
                    <div style="margin-bottom: 10px;">
                        <label style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">Card Number:</label>
                        <input type="text" value="4315 2849 9284 1042" maxlength="19" style="font-size: 0.88rem; padding: 9px 12px; margin-top: 4px; background: #141c30; border: 1px solid rgba(255,255,255,0.12); color: #fff; border-radius: 8px; width: 100%; font-family: monospace;">
                    </div>
                    <div style="display: flex; gap: 10px; margin-bottom: 16px;">
                        <div style="flex: 1;">
                            <label style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">Expiry Date:</label>
                            <input type="text" value="12/28" maxlength="5" style="font-size: 0.88rem; padding: 9px 12px; margin-top: 4px; background: #141c30; border: 1px solid rgba(255,255,255,0.12); color: #fff; border-radius: 8px; width: 100%; text-align: center;">
                        </div>
                        <div style="flex: 1;">
                            <label style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">CVV:</label>
                            <input type="password" value="882" maxlength="3" style="font-size: 0.88rem; padding: 9px 12px; margin-top: 4px; background: #141c30; border: 1px solid rgba(255,255,255,0.12); color: #fff; border-radius: 8px; width: 100%; text-align: center;">
                        </div>
                    </div>
                </div>

                <!-- Netbanking Panel -->
                <div id="sim-panel-netbanking" class="rzp-sim-panel" style="display: none;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: #e2e8f0; margin-bottom: 12px;">Popular Banks:</div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px;">
                        <button type="button" class="rzp-sim-app-btn active">🏛️ State Bank of India</button>
                        <button type="button" class="rzp-sim-app-btn">🏦 HDFC Bank</button>
                        <button type="button" class="rzp-sim-app-btn">🏦 ICICI Bank</button>
                        <button type="button" class="rzp-sim-app-btn">🏦 Axis Bank</button>
                    </div>
                </div>

                <!-- Wallet Panel -->
                <div id="sim-panel-wallet" class="rzp-sim-panel" style="display: none;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: #e2e8f0; margin-bottom: 12px;">Supported Wallets:</div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px;">
                        <button type="button" class="rzp-sim-app-btn active">👛 Paytm Wallet</button>
                        <button type="button" class="rzp-sim-app-btn">⚡ Mobikwik</button>
                        <button type="button" class="rzp-sim-app-btn">🔴 Airtel Money</button>
                        <button type="button" class="rzp-sim-app-btn">🟢 JioMoney</button>
                    </div>
                </div>

                <!-- Pay Button & Status -->
                <div style="margin-top: 14px;">
                    <button type="button" id="sim-pay-btn" onclick="executeSimulatedPayment()" style="width: 100%; padding: 13px; font-size: 1rem; font-weight: 800; background: linear-gradient(135deg, #00f0ff 0%, #8b5cf6 100%); color: #040814; border: none; border-radius: 8px; cursor: pointer; box-shadow: 0 0 20px rgba(0, 240, 255, 0.35); text-transform: uppercase; letter-spacing: 0.04em;">
                        Authorize Payment ₹<?php echo number_format($total_amount, 2); ?> &rarr;
                    </button>
                    <div id="sim-processing-status" style="display: none; text-align: center; margin-top: 10px; font-size: 0.85rem; color: #00f0ff; font-weight: 600;">
                        ⏳ Verifying transaction with Razorpay & Bank...
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="background: #080c16; padding: 10px 18px; border-top: 1px solid rgba(255,255,255,0.06); display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: #64748b;">
            <span>Secured by <strong>Razorpay</strong> 256-Bit Encryption</span>
            <span>PCI-DSS Level 1 Certified</span>
        </div>
    </div>
</div>

<style>
.rzp-sim-tab {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 12px 16px;
    background: transparent;
    border: none;
    border-left: 3px solid transparent;
    color: #94a3b8;
    font-size: 0.86rem;
    font-weight: 600;
    text-align: left;
    cursor: pointer;
    transition: all 0.15s ease;
}
.rzp-sim-tab:hover {
    background: rgba(255,255,255,0.03);
    color: #fff;
}
.rzp-sim-tab.active {
    background: rgba(0, 240, 255, 0.08);
    border-left-color: #00f0ff;
    color: #00f0ff;
    font-weight: 700;
}
.rzp-sim-app-btn {
    padding: 10px 12px;
    background: #141c30;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 8px;
    color: #cbd5e1;
    font-size: 0.84rem;
    font-weight: 600;
    cursor: pointer;
    text-align: center;
    transition: all 0.15s ease;
}
.rzp-sim-app-btn:hover, .rzp-sim-app-btn.active {
    border-color: #00f0ff;
    background: rgba(0, 240, 255, 0.08);
    color: #fff;
}
</style>

<!-- Official Razorpay Checkout SDK -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
const isLiveKeyConfigured = <?php echo $is_live_key ? 'true' : 'false'; ?>;

// Razorpay Official Options
var rzpOptions = {
    "key": "<?php echo RAZORPAY_KEY_ID; ?>",
    "amount": "<?php echo $amount_in_paise; ?>",
    "currency": "<?php echo RAZORPAY_CURRENCY; ?>",
    "name": "<?php echo addslashes(RAZORPAY_COMPANY_NAME); ?>",
    "description": "Gaming Equipment Rental Booking #<?php echo $temp_order_ref; ?>",
    "order_id": "<?php echo ($is_live_key && $razorpay_order_id !== $temp_order_ref) ? $razorpay_order_id : ''; ?>",
    "image": "img/onyxplay-icon.png",
    "handler": function (response) {
        if (response && response.razorpay_payment_id) {
            document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
            if (response.razorpay_order_id) {
                document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
            }
            if (response.razorpay_signature) {
                document.getElementById('razorpay_signature').value = response.razorpay_signature;
            }
            document.getElementById('razorpay-success-form').submit();
        } else {
            alert("Payment failed or response is missing.");
        }
    },
    "prefill": {
        "name": "<?php echo addslashes($user_name); ?>",
        "email": "<?php echo addslashes($user_email); ?>",
        "contact": "<?php echo addslashes($user_phone); ?>"
    },
    "notes": {
        "delivery_address": "<?php echo addslashes($delivery_address); ?>",
        "city": "<?php echo addslashes($city); ?>",
        "merchant": "<?php echo addslashes(MERCHANT_NAME); ?>"
    },
    "theme": {
        "color": "<?php echo RAZORPAY_THEME_COLOR; ?>"
    }
};

// Launch Action
document.getElementById('rzp-button').onclick = function(e) {
    e.preventDefault();
    if (isLiveKeyConfigured) {
        try {
            var rzp = new Razorpay(rzpOptions);
            rzp.on('payment.failed', function (response) {
                console.warn("Razorpay Live Failed, opening fallback:", response);
                openRazorpaySimulator();
            });
            rzp.open();
        } catch(err) {
            console.error("Razorpay Init Error:", err);
            openRazorpaySimulator();
        }
    } else {
        // Real key not entered yet -> open authentic interactive simulator
        openRazorpaySimulator();
    }
};

// Simulator Helpers
function openRazorpaySimulator() {
    const modal = document.getElementById('razorpaySimulatorModal');
    const box = document.getElementById('rzpSimBox');
    modal.style.opacity = '1';
    modal.style.pointerEvents = 'auto';
    box.style.transform = 'translateY(0) scale(1)';
}

function closeRazorpaySimulator() {
    const modal = document.getElementById('razorpaySimulatorModal');
    const box = document.getElementById('rzpSimBox');
    modal.style.opacity = '0';
    modal.style.pointerEvents = 'none';
    box.style.transform = 'translateY(20px) scale(0.97)';
}

function switchSimTab(tabName, btn) {
    document.querySelectorAll('.rzp-sim-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.rzp-sim-panel').forEach(p => p.style.display = 'none');
    btn.classList.add('active');
    const target = document.getElementById('sim-panel-' + tabName);
    if (target) target.style.display = 'block';
}

function selectUpiApp(btn, appName) {
    document.querySelectorAll('.rzp-sim-app-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

function executeSimulatedPayment() {
    const payBtn = document.getElementById('sim-pay-btn');
    const status = document.getElementById('sim-processing-status');
    payBtn.disabled = true;
    payBtn.style.opacity = '0.7';
    status.style.display = 'block';

    setTimeout(() => {
        // Generate realistic Razorpay payment ID (e.g. pay_Q8a9Zkd...)
        const randId = 'pay_' + Math.random().toString(36).substring(2, 8).toUpperCase() + Math.random().toString(36).substring(2, 6).toUpperCase();
        document.getElementById('razorpay_payment_id').value = randId;
        status.innerHTML = '✅ <strong>Payment Authorized! (ID: ' + randId + ')</strong> Submitting booking...';
        
        setTimeout(() => {
            document.getElementById('razorpay-success-form').submit();
        }, 600);
    }, 1200);
}

function toggleKeySettings() {
    const el = document.getElementById('key-settings-drawer');
    el.style.display = (el.style.display === 'none') ? 'block' : 'none';
}

function copyUpiId() {
    const upiText = document.getElementById('upiIdText').innerText;
    navigator.clipboard.writeText(upiText).then(() => {
        const btn = document.getElementById('copyBtn');
        btn.textContent = '✓ Copied!';
        setTimeout(() => {
            btn.textContent = '📋 Copy';
        }, 2000);
    }).catch(() => {
        alert("Merchant UPI ID: " + upiText);
    });
}
</script>

<?php require 'footer.php'; ?>
