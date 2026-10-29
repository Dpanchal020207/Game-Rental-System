<?php
require 'db.php';
require_once 'order_email_helper.php';
if (!isLoggedIn() || isAdmin()) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $b_id = (int)$_POST['booking_id'];
    $user_id = (int)$_SESSION['user_id'];
    
    $res = mysqli_query($conn, "SELECT status FROM bookings WHERE id = $b_id AND user_id = $user_id");
    $booking = mysqli_fetch_assoc($res);
    
    if ($booking && in_array($booking['status'], ['Pending', 'Confirmed'])) {
        mysqli_begin_transaction($conn);
        try {
            mysqli_query($conn, "UPDATE bookings SET status = 'Cancelled' WHERE id = $b_id");
            
            $res_items = mysqli_query($conn, "SELECT hardware_unit_id FROM booking_items WHERE booking_id = $b_id");
            while ($item = mysqli_fetch_assoc($res_items)) {
                $h_id = $item['hardware_unit_id'];
                mysqli_query($conn, "UPDATE hardware_units SET status = 'Available' WHERE id = $h_id");
            }
            
            mysqli_commit($conn);
            sendOrderStatusUpdateEmail($b_id, 'Cancelled', $conn);
        } catch(Exception $e) {
            mysqli_rollback($conn);
        }
    }
}
redirect('customer_dashboard.php');
?>
