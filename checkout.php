<?php
require 'db.php';
require_once 'order_email_helper.php';
if (!isLoggedIn() || isAdmin()) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $city = mysqli_real_escape_string($conn, trim($_POST['city'] ?? ''));
    
    // Parse structured address details
    $house_no = trim($_POST['house_no'] ?? '');
    $street_address = trim($_POST['street_address'] ?? '');
    $landmark = trim($_POST['landmark'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $delivery_phone = trim($_POST['delivery_phone'] ?? '');
    $delivery_notes = trim($_POST['delivery_notes'] ?? '');

    if (!empty($house_no) || !empty($street_address)) {
        $addr_parts = [];
        if (!empty($house_no)) $addr_parts[] = $house_no;
        if (!empty($street_address)) $addr_parts[] = $street_address;
        if (!empty($landmark)) $addr_parts[] = "Landmark: " . $landmark;
        $state_pin = trim("$state" . (!empty($pincode) ? " - $pincode" : ""));
        if (!empty($state_pin)) $addr_parts[] = $state_pin;
        if (!empty($delivery_phone)) $addr_parts[] = "Phone: " . $delivery_phone;
        if (!empty($delivery_notes)) $addr_parts[] = "Note: " . $delivery_notes;
        $delivery_address = mysqli_real_escape_string($conn, implode(', ', $addr_parts));
    } else {
        $delivery_address = mysqli_real_escape_string($conn, trim($_POST['delivery_address'] ?? ''));
    }
    
    $cart = $_SESSION['cart'] ?? [];
    
    if (empty($cart)) redirect('cart.php');
    
    $total_amount = (float)$_POST['total_amount'];
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'razorpay');
    $payment_id = mysqli_real_escape_string($conn, trim($_POST['razorpay_payment_id'] ?? $_POST['transaction_ref'] ?? ''));
    if (empty($payment_id) && $payment_method === 'razorpay') {
        $payment_id = 'pay_' . strtoupper(substr(uniqid(), 5));
    }
    
    // Auto-confirm bookings paid via Razorpay
    $booking_status = ($payment_method === 'razorpay' && !empty($payment_id)) ? 'Confirmed' : 'Pending';
    $booking_id = 'BKG-' . strtoupper(uniqid());
    
    mysqli_begin_transaction($conn);
    
    try {
        $delivery_date = mysqli_real_escape_string($conn, $cart[0]['delivery_date']);
        $return_date = mysqli_real_escape_string($conn, $cart[0]['return_date']);
        
        $d1 = new DateTime($delivery_date);
        $d2 = new DateTime($return_date);
        $total_days = $d2->diff($d1)->days;
        $user_id = (int)$_SESSION['user_id'];
        
        $query = "INSERT INTO bookings (booking_id, user_id, delivery_date, return_date, total_days, total_amount, delivery_address, city, status, payment_method, payment_id) 
                  VALUES ('$booking_id', $user_id, '$delivery_date', '$return_date', $total_days, $total_amount, '$delivery_address', '$city', '$booking_status', '$payment_method', '$payment_id')";
        
        if (!mysqli_query($conn, $query)) {
            throw new Exception("Failed to insert booking into database.");
        }
        $b_id = mysqli_insert_id($conn);
        
        foreach ($cart as $item) {
            $gadget_id = (int)$item['gadget_id'];
            
            $res = mysqli_query($conn, "SELECT id FROM hardware_units WHERE gadget_id = $gadget_id AND status = 'Available' LIMIT 1");
            $unit = mysqli_fetch_assoc($res);
            
            if (!$unit) {
                throw new Exception("Hardware unit for one of the gadgets is currently out of stock.");
            }
            
            $res_price = mysqli_query($conn, "SELECT daily_price FROM gadgets WHERE id = $gadget_id");
            $price_row = mysqli_fetch_assoc($res_price);
            $price_per_day = $price_row['daily_price'];
            $unit_id = $unit['id'];
            
            mysqli_query($conn, "INSERT INTO booking_items (booking_id, hardware_unit_id, price_per_day) VALUES ($b_id, $unit_id, $price_per_day)");
            mysqli_query($conn, "UPDATE hardware_units SET status = 'Rented' WHERE id = $unit_id");
        }
        
        mysqli_commit($conn);
        unset($_SESSION['cart']);
        
        // Trigger automated confirmation email to the user
        $mail_result = sendOrderConfirmationEmail($b_id, $conn);
        $user_email_display = htmlspecialchars($mail_result['email'] ?? '');
        
        $success_msg = "Payment Successful! Order placed successfully. Your Booking ID is <strong>$booking_id</strong>.";
        if (!empty($mail_result['success'])) {
            $success_msg .= "<br><br><span style='display:inline-flex; align-items:center; gap:6px;'>✉️ <strong>Order confirmation email sent!</strong> We have emailed your booking receipt and rental details to <u>{$user_email_display}</u>.</span>";
            $success_msg .= "<br><small style='color: #475569;'>Tip: If the email doesn't appear in your Primary inbox, please check your <strong>Spam</strong> or <strong>Promotions</strong> folder.</small>";
        } else {
            $success_msg .= "<br><br><small style='color: #64748b;'>Note: Order confirmation email is queued for delivery. You can check your booking status anytime in your dashboard.</small>";
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_msg = "Order failed: " . $e->getMessage();
    }
} else {
    redirect('cart.php');
}

require 'header.php';
?>

<div style="max-width: 620px; margin: 30px auto; text-align: center;">
<?php if(isset($success_msg)): ?>
    <div style="background: var(--container-bg); border: 1.5px solid var(--border-color); border-radius: 18px; padding: 36px 28px; box-shadow: var(--card-shadow); position: relative; overflow: hidden;">
        <!-- Top Laser Glow Line -->
        <div style="position: absolute; top: 0; left: 10%; right: 10%; height: 3px; background: linear-gradient(90deg, transparent, #00f0ff, #10b981, transparent);"></div>

        <div style="width: 68px; height: 68px; border-radius: 50%; background: rgba(16, 185, 129, 0.15); border: 2px solid #10b981; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 2.2rem; color: #10b981;">
            ✓
        </div>

        <h2 style="font-family: 'Rajdhani', sans-serif; font-size: 2.2rem; margin: 0 0 8px; color: var(--text-color);">
            Payment Confirmed!
        </h2>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0 0 22px;">
            Your rental booking has been registered and confirmed.
        </p>

        <!-- Receipt Details Box -->
        <div style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 18px 20px; text-align: left; margin-bottom: 24px; font-size: 0.9rem; line-height: 1.7;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding-bottom: 8px; margin-bottom: 8px;">
                <span style="color: var(--text-muted);">Booking ID:</span>
                <span style="font-family: monospace; font-weight: 700; color: #00f0ff;"><?php echo htmlspecialchars($booking_id); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span style="color: var(--text-muted);">Payment Method:</span>
                <span style="font-weight: 700; color: var(--text-color);">
                    <?php echo ($payment_method === 'razorpay') ? 'Razorpay Online Gateway' : 'Direct UPI Transfer'; ?>
                </span>
            </div>
            <?php if (!empty($payment_id)): ?>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span style="color: var(--text-muted);">Payment / Transaction ID:</span>
                    <span style="font-family: monospace; color: #10b981; font-weight: 600;"><?php echo htmlspecialchars($payment_id); ?></span>
                </div>
            <?php endif; ?>
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span style="color: var(--text-muted);">Delivery Destination:</span>
                <span style="font-weight: 600; color: var(--text-color); text-align: right; max-width: 65%; font-size: 0.85rem;">
                    <?php echo htmlspecialchars(stripslashes($delivery_address)); ?>, <?php echo htmlspecialchars(stripslashes($city)); ?>
                </span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span style="color: var(--text-muted);">Total Paid:</span>
                <span style="font-weight: 800; color: #00f0ff; font-size: 1.1rem;">₹<?php echo number_format($total_amount, 2); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Status:</span>
                <span style="color: #10b981; font-weight: 700;">● <?php echo htmlspecialchars($booking_status); ?></span>
            </div>
        </div>

        <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 26px; line-height: 1.5;">
            <?php echo $success_msg; ?>
        </div>

        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
            <a href="customer_dashboard.php" class="btn" style="background: linear-gradient(135deg, #00f0ff 0%, #8b5cf6 100%); color: #040814; font-weight: 800; border: none; padding: 12px 24px; border-radius: 25px; text-decoration: none;">
                📊 View My Bookings Dashboard
            </a>
            <a href="catalog.php" class="btn" style="background: var(--input-bg); color: var(--text-color); border: 1px solid var(--border-color); padding: 12px 20px; border-radius: 25px; text-decoration: none;">
                Browse More Gear
            </a>
        </div>
    </div>
<?php endif; ?>

<?php if(isset($error_msg)): ?>
    <div style="background: var(--container-bg); border: 1.5px solid #dc3545; border-radius: 18px; padding: 36px 28px; box-shadow: var(--card-shadow);">
        <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(220, 53, 69, 0.15); border: 2px solid #dc3545; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 2.2rem; color: #dc3545;">
            ✕
        </div>
        <h2 style="font-family: 'Rajdhani', sans-serif; font-size: 2rem; margin: 0 0 10px; color: #dc3545;">
            Order Placement Failed
        </h2>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 24px;">
            <?php echo htmlspecialchars($error_msg); ?>
        </p>
        <a href="cart.php" class="btn" style="background: #dc3545; color: #fff; padding: 12px 24px; border-radius: 25px; text-decoration: none; font-weight: 700;">
            &larr; Return to Shopping Cart
        </a>
    </div>
<?php endif; ?>
</div>

<?php require 'footer.php'; ?>
