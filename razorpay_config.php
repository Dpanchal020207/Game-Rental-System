<?php
/**
 * ONYXPLAY - Razorpay Payment Gateway Configuration
 * 
 * To receive live customer payments directly to your bank account / UPI ID:
 * 1. Log in to your Razorpay Dashboard: https://dashboard.razorpay.com
 * 2. Go to: Account & Settings -> API Keys -> Generate Key (Test or Live)
 * 3. Replace the RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET below with your own keys.
 * 4. To receive settlements into your payment method / bank account:
 *    Go to Razorpay Dashboard -> Account & Settings -> Bank Accounts and link your bank/UPI.
 *    Razorpay will automatically deposit collected rental payments directly to your account.
 */

// Razorpay API Credentials (Replace with your actual keys from dashboard.razorpay.com)
if (!defined('RAZORPAY_KEY_ID')) {
    define('RAZORPAY_KEY_ID', 'rzp_test_YourRazorpayKeyId');
}

if (!defined('RAZORPAY_KEY_SECRET')) {
    define('RAZORPAY_KEY_SECRET', 'YourRazorpayKeySecret');
}

if (!defined('RAZORPAY_CURRENCY')) {
    define('RAZORPAY_CURRENCY', 'INR');
}

if (!defined('RAZORPAY_COMPANY_NAME')) {
    define('RAZORPAY_COMPANY_NAME', 'ONYXPLAY');
}

if (!defined('RAZORPAY_THEME_COLOR')) {
    define('RAZORPAY_THEME_COLOR', '#00f0ff');
}

// Merchant Direct Payment Method Information
if (!defined('MERCHANT_UPI_ID')) {
    define('MERCHANT_UPI_ID', 'pdp2512-4@oksbi');
}

if (!defined('MERCHANT_NAME')) {
    define('MERCHANT_NAME', 'Panchal Dhruvin');
}

// When true, allows a sandbox simulated approval if real API keys are not yet activated
if (!defined('RAZORPAY_ENABLE_SIMULATION_FALLBACK')) {
    define('RAZORPAY_ENABLE_SIMULATION_FALLBACK', true);
}

/**
 * Check if a valid merchant Razorpay key is configured
 * @return bool
 */
function isRazorpayLiveKeyConfigured() {
    return defined('RAZORPAY_KEY_ID') && 
           strpos(RAZORPAY_KEY_ID, 'rzp_') === 0 && 
           RAZORPAY_KEY_ID !== 'rzp_test_YourRazorpayKeyId' &&
           strlen(RAZORPAY_KEY_ID) >= 20;
}

/**
 * Create official Razorpay Order via server-side REST API
 * 
 * @param float $amount_in_rupees
 * @param string $receipt_id
 * @return array ['success' => bool, 'order_id' => string, 'error' => string]
 */
function createRazorpayOrder($amount_in_rupees, $receipt_id) {
    if (!isRazorpayLiveKeyConfigured()) {
        return ['success' => false, 'error' => 'Keys not configured'];
    }

    if (!function_exists('curl_init')) {
        return ['success' => false, 'error' => 'cURL extension is missing in PHP'];
    }

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    $payload = json_encode([
        'amount' => (int)round($amount_in_rupees * 100),
        'currency' => RAZORPAY_CURRENCY,
        'receipt' => $receipt_id,
        'payment_capture' => 1
    ]);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        return ['success' => false, 'error' => 'cURL Error: ' . $curl_err];
    }

    $data = json_decode($response, true);
    if (($http_code === 200 || $http_code === 201) && !empty($data['id'])) {
        return ['success' => true, 'order_id' => $data['id']];
    }

    $msg = $data['error']['description'] ?? $response ?? 'Order creation failed';
    return ['success' => false, 'error' => $msg, 'http_code' => $http_code];
}

/**
 * Verify Razorpay payment signature
 * 
 * @param string $order_id
 * @param string $payment_id
 * @param string $signature
 * @return bool
 */
function verifyRazorpaySignature($order_id, $payment_id, $signature) {
    if (empty($signature) || empty($payment_id)) {
        return false;
    }
    $generated_signature = hash_hmac('sha256', $order_id . '|' . $payment_id, RAZORPAY_KEY_SECRET);
    return hash_equals($generated_signature, $signature);
}
