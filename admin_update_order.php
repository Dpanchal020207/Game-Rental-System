<?php
require 'db.php';
require_once 'order_email_helper.php';
if (!isAdmin()) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $b_id = (int)$_POST['booking_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    if ($status) {
        mysqli_begin_transaction($conn);
        try {
            mysqli_query($conn, "UPDATE bookings SET status = '$status' WHERE id = $b_id");
            
            if ($status === 'Returned' || $status === 'Cancelled') {
                $res_items = mysqli_query($conn, "SELECT hardware_unit_id FROM booking_items WHERE booking_id = $b_id");
                while ($item = mysqli_fetch_assoc($res_items)) {
                    $h_id = $item['hardware_unit_id'];
                    mysqli_query($conn, "UPDATE hardware_units SET status = 'Available' WHERE id = $h_id");
                }
            }
            
            mysqli_commit($conn);
            sendOrderStatusUpdateEmail($b_id, $status, $conn);
        } catch(Exception $e) {
            mysqli_rollback($conn);
        }
    }
}
redirect('admin_dashboard.php');
?>
