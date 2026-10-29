<?php
require 'db.php';
require_once 'order_email_helper.php';

if (!isLoggedIn()) redirect('login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    $user_id = (int)$_SESSION['user_id'];
    
    // Check permission: customer can resend own booking, admin can resend any
    if (isAdmin()) {
        $res = mysqli_query($conn, "SELECT id FROM bookings WHERE id = $booking_id");
    } else {
        $res = mysqli_query($conn, "SELECT id FROM bookings WHERE id = $booking_id AND user_id = $user_id");
    }
    
    if ($res && mysqli_num_rows($res) > 0) {
        $result = sendOrderConfirmationEmail($booking_id, $conn);
        if (!empty($result['success'])) {
            $_SESSION['flash_success'] = "Order confirmation email sent directly to your registered email (" . htmlspecialchars($result['email']) . ")! (If not in Primary inbox, please check your Spam / Promotions folder).";
        } else {
            $_SESSION['flash_error'] = "Failed to send email: " . htmlspecialchars($result['message'] ?? 'Unknown error');
        }
    } else {
        $_SESSION['flash_error'] = "Booking not found or permission denied.";
    }
}

redirect($_SERVER['HTTP_REFERER'] ?? 'customer_dashboard.php');
?>
