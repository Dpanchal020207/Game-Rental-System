<?php
// Centralized OTP Helper Functions
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/smtp_config.php';
require_once __DIR__ . '/sms_config.php';

// Permanent 2-Factor Authentication Setting:
// When true, all customer logins permanently require email OTP verification.
if (!defined('PERMANENT_LOGIN_OTP')) {
    define('PERMANENT_LOGIN_OTP', true);
}

define('OTP_EXPIRATION_SECONDS', 300); // 5 minutes
define('OTP_RESEND_COOLDOWN_SECONDS', 30); // 30 seconds cooldown between resends

/**
 * Generate and send a fresh OTP to user via Email and SMS
 * 
 * @param int $user_id
 * @param mysqli $conn
 * @param bool $ignore_cooldown
 * @return array
 */
function sendUserOTP($user_id, $conn, $ignore_cooldown = false) {
    $user_id = (int)$user_id;
    if ($user_id <= 0) {
        return ['success' => false, 'message' => 'Invalid user ID.'];
    }
    
    // Check resend cooldown
    if (!$ignore_cooldown && isset($_SESSION['otp_sent_time'])) {
        $elapsed = time() - $_SESSION['otp_sent_time'];
        if ($elapsed < OTP_RESEND_COOLDOWN_SECONDS) {
            $wait_seconds = OTP_RESEND_COOLDOWN_SECONDS - $elapsed;
            return [
                'success' => false,
                'message' => "Please wait {$wait_seconds}s before requesting another OTP.",
                'resend_cooldown' => $wait_seconds
            ];
        }
    }
    
    // Fetch user details
    $stmt = mysqli_prepare($conn, "SELECT name, email, phone FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($res);
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found.'];
    }
    
    // Generate fresh 6-digit OTP
    $otp_code = (string)rand(100000, 999999);
    $expires_timestamp = time() + OTP_EXPIRATION_SECONDS;
    $expires_at = date('Y-m-d H:i:s', $expires_timestamp);
    
    // Update DB
    $up_stmt = mysqli_prepare($conn, "UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?");
    mysqli_stmt_bind_param($up_stmt, "ssi", $otp_code, $expires_at, $user_id);
    mysqli_stmt_execute($up_stmt);
    
    // Update Session
    $_SESSION['otp_sent_time'] = time();
    $_SESSION['otp_expires_at'] = $expires_timestamp;
    $_SESSION['temp_user_id'] = $user_id;
    
    // 1. Send via PHPMailer
    $email_sent = false;
    $email_error = '';
    try {
        $mail = getMailer();
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->addAddress($user['email'], $user['name']);
        $mail->isHTML(true);
        $mail->Subject = "Your ONYXPLAY Verification OTP: $otp_code";
        
        $plain_body = "Hello " . $user['name'] . ",\n\n"
                    . "Your 6-digit verification OTP code for ONYXPLAY is: $otp_code\n\n"
                    . "Expiration Notice: This OTP is strictly valid for 5 minutes (expires at " . date('h:i:s A', $expires_timestamp) . ").\n\n"
                    . "If you did not request this OTP or didn't attempt to register/login, you can safely ignore this email.\n\n"
                    . "ONYXPLAY • Next-Gen Gaming Gear & Console Rentals";
        
        $mail->AltBody = $plain_body;
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 520px; margin: 0 auto; background: #0b0e17; border: 1px solid #1e293b; border-radius: 14px; overflow: hidden; color: #f1f5f9; box-shadow: 0 12px 35px rgba(0,0,0,0.6);'>
                <!-- Top Dual-Tone Highlight Bar -->
                <div style='height: 4px; background: linear-gradient(90deg, #00f0ff 0%, #b026ff 100%);'></div>

                <div style='padding: 28px 24px;'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <div style='font-size: 26px; font-weight: 800; letter-spacing: 2px; line-height: 1.1;'>
                            <span style='color: #cbd5e1;'>ONYX</span><span style='color: #00f0ff;'>PLAY</span>
                        </div>
                        <div style='color: #a855f7; font-size: 13px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-top: 4px;'>Next-Gen Game Rentals</div>
                    </div>

                    <h2 style='color: #00d2ff; margin-top: 0; font-size: 20px; text-align: center;'>Account Verification</h2>
                    <p style='color: #cbd5e1; font-size: 14px;'>Hello <strong style='color: #f1f5f9;'>" . htmlspecialchars($user['name']) . "</strong>,</p>
                    <p style='color: #94a3b8; font-size: 14px;'>Your 6-digit One-Time Password (OTP) for ONYXPLAY account verification is:</p>
                    
                    <div style='background: #121726; border: 2px dashed #00d2ff; border-radius: 12px; padding: 18px; text-align: center; margin: 20px 0;'>
                        <span style='font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #00f0ff; font-family: monospace;'>$otp_code</span>
                    </div>

                    <div style='background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 8px; padding: 12px; margin-bottom: 20px; color: #fca5a5; font-size: 13px;'>
                        <strong>⏱️ Expiration Notice:</strong> This OTP is strictly valid for <strong>5 minutes</strong> (expires at " . date('h:i:s A', $expires_timestamp) . ").
                    </div>
                    <p style='color: #64748b; font-size: 13px;'>If you did not request this OTP or didn't attempt to register/login, you can safely ignore this email.</p>
                    <hr style='border: none; border-top: 1px solid #1e293b; margin: 20px 0;'>
                    <p style='color: #64748b; font-size: 12px; text-align: center; margin: 0;'>ONYXPLAY &bull; Premium Consoles, VR & Gaming Gear Rentals</p>
                </div>
            </div>
        ";
        $email_sent = $mail->send();
    } catch (Exception $e) {
        $email_error = $e->getMessage();
    }
    
    // 2. Send via SMS (Fast2SMS)
    $sms_result = null;
    if (function_exists('sendSMS')) {
        $sms_result = sendSMS($user['phone'], $otp_code);
    }
    $sms_sent = !empty($sms_result['success']);
    $sms_msg = $sms_result['message'] ?? 'SMS service unavailable';

    // Log complete audit to otp_log.txt for local verification / inspection
    $log_line = date('Y-m-d H:i:s') . " | OTP for {$user['phone']} / {$user['email']} is $otp_code (Expires at: $expires_at) | Email: " . ($email_sent ? "SUCCESS" : "FAILED ($email_error)") . " | SMS: " . ($sms_sent ? "SUCCESS" : "FAILED ($sms_msg)") . "\n";
    @file_put_contents(__DIR__ . '/otp_log.txt', $log_line, FILE_APPEND);

    // Formulate descriptive user message
    if ($email_sent && $sms_sent) {
        $status_message = "A new OTP has been sent to your email and phone number. Valid for 5 minutes.";
    } elseif ($email_sent) {
        $status_message = "OTP sent to your email (" . htmlspecialchars($user['email']) . "). Valid for 5 minutes.";
    } elseif ($sms_sent) {
        $status_message = "OTP sent to your mobile (" . htmlspecialchars($user['phone']) . "). Valid for 5 minutes.";
    } else {
        $status_message = "OTP generated. Delivery note: Check your registered email & spam folder.";
    }

    return [
        'success' => true,
        'message' => $status_message,
        'expires_at' => $expires_at,
        'expires_in' => OTP_EXPIRATION_SECONDS,
        'resend_cooldown' => OTP_RESEND_COOLDOWN_SECONDS,
        'email_sent' => $email_sent,
        'email_error' => $email_error,
        'sms_sent' => $sms_sent,
        'sms_message' => $sms_msg
    ];
}
