<?php
require 'db.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['ajax']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $gadget_id = (int)$_POST['gadget_id'];
    $delivery_date = $_POST['delivery_date'] ?? '';
    $return_date = $_POST['return_date'] ?? '';
    
    // Validation
    if (empty($delivery_date) || empty($return_date)) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Please select both delivery and return dates.']);
            exit();
        }
        $_SESSION['flash_error'] = 'Please select both delivery and return dates.';
        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
    }

    if ($delivery_date < date('Y-m-d')) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Delivery date cannot be in the past.']);
            exit();
        }
        $_SESSION['flash_error'] = 'Delivery date cannot be in the past.';
        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
    }

    if ($delivery_date >= $return_date) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Return date must be strictly after delivery date.']);
            exit();
        }
        $_SESSION['flash_error'] = 'Return date must be strictly after delivery date.';
        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
    }
    
    $_SESSION['cart'][] = [
        'gadget_id' => $gadget_id,
        'delivery_date' => $delivery_date,
        'return_date' => $return_date
    ];
    
    $cart_count = count($_SESSION['cart']);

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'Item added to cart successfully!',
            'cart_count' => $cart_count
        ]);
        exit();
    }
    
    $_SESSION['flash_success'] = 'Item added to cart successfully!';
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

if (isset($_GET['remove'])) {
    $index = (int)$_GET['remove'];
    if (isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // reindex
    }
    redirect('cart.php');
}
?>
