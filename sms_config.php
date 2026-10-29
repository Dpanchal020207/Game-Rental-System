<?php
// Centralized SMS Configuration

define('SMS_PROVIDER', 'fast2sms');

// ==========================================
// Fast2SMS Settings (https://www.fast2sms.com/)
// ==========================================
define('FAST2SMS_API_KEY', 'RNCefbtjXG4MIwJpA9rqkySLD60a8xOgQ1K5iWmT3E2ZUuHVvdP6cJMfmaswtnFSx4oNCuZRyA0TlkEd');

/**
 * Sends OTP to the provided 10-digit mobile number via SMS.
 *
 * @param string $phone 10-digit phone number
 * @param string|int $otp_code 6-digit OTP code
 * @return array ['success' => bool, 'message' => string]
 */
function sendSMS($phone, $otp_code) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    $apiKey = trim(FAST2SMS_API_KEY);

    if (empty($apiKey) || $apiKey === 'YOUR_FAST2SMS_API_KEY') {
        return [
            'success' => false,
            'message' => 'Fast2SMS API Key is not set in sms_config.php.'
        ];
    }

    $postData = [
        "variables_values" => (string)$otp_code,
        "route" => "otp",
        "numbers" => $phone
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://www.fast2sms.com/dev/bulkV2",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => [
            "authorization: " . $apiKey,
            "accept: */*",
            "cache-control: no-cache",
            "content-type: application/json"
        ],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'message' => "cURL Error: " . $error];
    }

    $result = json_decode($response, true);
    if (isset($result['return']) && $result['return'] === true) {
        return ['success' => true, 'message' => 'SMS delivered successfully.'];
    } else {
        $msg = 'Failed to send SMS via Fast2SMS.';
        if (isset($result['message'])) {
            $msg = is_array($result['message']) ? implode(', ', $result['message']) : (string)$result['message'];
        }
        return ['success' => false, 'message' => $msg];
    }
}
?>