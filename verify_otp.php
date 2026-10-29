<?php
require 'db.php';
require_once 'otp_helper.php';

if (isLoggedIn() || !isset($_SESSION['temp_user_id'])) {
    redirect('index.php');
}

$user_id = (int)$_SESSION['temp_user_id'];
$error = '';
$success = '';

// Handle AJAX Resend Request
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resend_otp') {
    $resend_result = sendUserOTP($user_id, $conn);
    
    // Check if AJAX
    if (isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode($resend_result);
        exit();
    }
    
    // Non-AJAX fallback
    if ($resend_result['success']) {
        $success = $resend_result['message'];
    } else {
        $error = $resend_result['message'];
    }
}

// Fetch user data
$res = mysqli_query($conn, "SELECT name, email, phone, otp_code, otp_expires_at FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($res);

if (!$user) {
    unset($_SESSION['temp_user_id']);
    redirect('login.php');
}

// If no OTP or expiration has elapsed, generate a fresh one now
$is_expired = empty($user['otp_expires_at']) || (strtotime($user['otp_expires_at']) <= time());
if (empty($user['otp_code']) || $is_expired) {
    sendUserOTP($user_id, $conn, true);
    $res = mysqli_query($conn, "SELECT name, email, phone, otp_code, otp_expires_at FROM users WHERE id = $user_id");
    $user = mysqli_fetch_assoc($res);
}

// Handle OTP Verification Submission
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['otp']) && (!isset($_POST['action']) || $_POST['action'] !== 'resend_otp')) {
    $entered_otp = trim($_POST['otp']);
    
    $now = time();
    $expiry_time = !empty($user['otp_expires_at']) ? strtotime($user['otp_expires_at']) : 0;
    
    if (empty($entered_otp)) {
        $error = "Please enter the 6-digit OTP.";
    } elseif ($expiry_time > 0 && $now > $expiry_time) {
        $error = "⚠️ This OTP has expired. Please click 'Resend OTP' to receive a new code.";
    } elseif (!empty($user['otp_code']) && $entered_otp === $user['otp_code']) {
        // OTP Verified successfully!
        unset($_SESSION['temp_user_id']);
        unset($_SESSION['otp_sent_time']);
        unset($_SESSION['otp_expires_at']);
        if (isset($_SESSION['2factor_session_id'])) {
            unset($_SESSION['2factor_session_id']);
        }
        
        mysqli_query($conn, "UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE id = $user_id");
        $_SESSION['user_id'] = $user_id;
        
        // Fetch role and name
        $res_user_info = mysqli_query($conn, "SELECT name, role FROM users WHERE id = $user_id");
        $row_user_info = mysqli_fetch_assoc($res_user_info);
        
        $_SESSION['role'] = $row_user_info['role']; 
        $_SESSION['name'] = $row_user_info['name'];
        
        if ($_SESSION['role'] === 'admin') {
            redirect('admin_dashboard.php');
        } else {
            redirect('index.php');
        }
    } else {
        $error = "Invalid OTP. Please check the code entered and try again.";
    }
}

// Calculate timer values
$now = time();
$expiry_timestamp = !empty($user['otp_expires_at']) ? strtotime($user['otp_expires_at']) : ($now + OTP_EXPIRATION_SECONDS);
$seconds_remaining = max(0, $expiry_timestamp - $now);

$sent_timestamp = $_SESSION['otp_sent_time'] ?? ($expiry_timestamp - OTP_EXPIRATION_SECONDS);
$cooldown_remaining = max(0, ($sent_timestamp + OTP_RESEND_COOLDOWN_SECONDS) - $now);

require 'header.php';
?>

<div style="max-width: 480px; margin: 30px auto; background: var(--container-bg); padding: 35px 30px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.06); border: 1px solid var(--border-color);">
    
    <div style="text-align: center; margin-bottom: 25px;">
        <img src="img/onyxplay-icon.png" alt="ONYXPLAY" class="brand-auth-icon" style="margin-bottom: 10px;">
        <h2 style="margin: 0 0 8px 0; font-size: 1.6rem; font-weight: 700; color: var(--text-color);">Verify ONYXPLAY Account</h2>
        <p style="margin: 0; color: var(--text-muted); font-size: 0.92rem; line-height: 1.5;">
            An OTP has been sent to your email (<strong style="color: var(--text-color);"><?php echo htmlspecialchars($user['email']); ?></strong>).
        </p>
        <p style="margin: 6px 0 0 0; color: #94a3b8; font-size: 0.82rem;">
            📬 <em>Please check your <strong>Inbox</strong> as well as your <strong>Spam / Junk</strong> folder.</em>
        </p>
    </div>

    <!-- OTP Expiration & Countdown Card -->
    <div id="expirationCard" style="background: var(--table-header-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px 18px; margin-bottom: 22px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
            <div style="font-size: 0.88rem; color: var(--text-color);">
                ⏱️ <strong>OTP Expiration:</strong> <span style="color: #00d2ff; font-weight: 600;">5 Minutes</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 0.85rem; color: var(--text-muted);">Time Left:</span>
                <span id="expiryTimer" style="font-family: monospace; font-size: 1.15rem; font-weight: 800; color: #10b981; background: var(--container-bg); padding: 2px 8px; border-radius: 6px; border: 1px solid var(--border-color);">
                    --:--
                </span>
            </div>
        </div>
        
        <!-- Live status notification -->
        <div id="expiryStatusNotice" style="display: none; margin-top: 10px; font-size: 0.85rem; color: #dc2626; font-weight: 600; text-align: center;">
            ⚠️ This OTP has expired. Please request a new code below.
        </div>
    </div>

    <!-- Alert Messages -->
    <div id="alertSuccess" style="<?php echo $success ? '' : 'display:none;'; ?> background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-size: 0.9rem;">
        <span id="alertSuccessText"><?php echo htmlspecialchars($success); ?></span>
    </div>

    <div id="alertError" style="<?php echo $error ? '' : 'display:none;'; ?> background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-size: 0.9rem;">
        <span id="alertErrorText"><?php echo htmlspecialchars($error); ?></span>
    </div>

    <!-- OTP Input Form -->
    <form method="POST" id="otpForm" action="verify_otp.php">
        <label for="otpInput" style="display: block; font-weight: 600; font-size: 0.92rem; margin-bottom: 8px; color: var(--text-color);">
            Enter 6-Digit OTP:
        </label>
        
        <input 
            type="text" 
            id="otpInput" 
            name="otp" 
            required 
            maxlength="6" 
            pattern="[0-9]{6}"
            placeholder="&bull; &bull; &bull; &bull; &bull; &bull;" 
            autocomplete="one-time-code"
            autofocus
            style="width: 100%; box-sizing: border-box; text-align: center; font-size: 1.6rem; letter-spacing: 12px; font-weight: 700; padding: 12px; border-radius: 10px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-color); margin-bottom: 18px;"
            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);"
        >
        
        <button type="submit" id="verifyBtn" class="btn" style="width: 100%; padding: 13px; font-size: 1rem; font-weight: 700; border-radius: 8px;">
            Verify & Continue &rarr;
        </button>
    </form>

    <!-- Resend OTP Section with Cooldown Timer -->
    <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid var(--border-color); text-align: center;">
        <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0 0 10px 0;">
            Didn't receive the OTP code or did it expire?
        </p>
        
        <button 
            type="button" 
            id="resendBtn" 
            class="btn" 
            style="background: transparent; color: #00d2ff; border: 1px solid #00d2ff; padding: 9px 20px; font-size: 0.9rem; font-weight: 600; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px;"
        >
            <span id="resendIcon">&#8635;</span> 
            <span id="resendBtnText">Resend OTP</span>
        </button>
    </div>

    <!-- Hidden Fallback Form for non-JS -->
    <form id="fallbackResendForm" method="POST" action="verify_otp.php" style="display:none;">
        <input type="hidden" name="action" value="resend_otp">
    </form>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initial states passed from server PHP
    let secondsLeft = <?php echo (int)$seconds_remaining; ?>;
    let cooldownLeft = <?php echo (int)$cooldown_remaining; ?>;
    
    const expiryTimerEl = document.getElementById('expiryTimer');
    const expiryStatusNotice = document.getElementById('expiryStatusNotice');
    const resendBtn = document.getElementById('resendBtn');
    const resendBtnText = document.getElementById('resendBtnText');
    const resendIcon = document.getElementById('resendIcon');
    const otpInput = document.getElementById('otpInput');
    const alertSuccess = document.getElementById('alertSuccess');
    const alertSuccessText = document.getElementById('alertSuccessText');
    const alertError = document.getElementById('alertError');
    const alertErrorText = document.getElementById('alertErrorText');

    function formatTime(totalSeconds) {
        const m = Math.floor(totalSeconds / 60);
        const s = totalSeconds % 60;
        return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    }

    // 1. Expiration Countdown Timer (5 Minutes)
    function updateExpiryTimer() {
        if (secondsLeft <= 0) {
            expiryTimerEl.textContent = '00:00';
            expiryTimerEl.style.color = '#dc2626';
            expiryStatusNotice.style.display = 'block';
        } else {
            expiryTimerEl.textContent = formatTime(secondsLeft);
            if (secondsLeft <= 60) {
                expiryTimerEl.style.color = '#f59e0b'; // Amber warning when < 1 min
            } else {
                expiryTimerEl.style.color = '#10b981'; // Green when healthy
            }
            expiryStatusNotice.style.display = 'none';
            secondsLeft--;
        }
    }

    // 2. Resend Cooldown Timer (30s)
    function updateCooldownTimer() {
        if (cooldownLeft <= 0) {
            resendBtn.disabled = false;
            resendBtn.style.opacity = '1';
            resendBtn.style.cursor = 'pointer';
            resendBtnText.textContent = 'Resend OTP';
            resendIcon.style.display = 'inline';
        } else {
            resendBtn.disabled = true;
            resendBtn.style.opacity = '0.6';
            resendBtn.style.cursor = 'not-allowed';
            resendBtnText.textContent = 'Resend OTP (' + cooldownLeft + 's)';
            resendIcon.style.display = 'none';
            cooldownLeft--;
        }
    }

    // Start ticks
    updateExpiryTimer();
    updateCooldownTimer();
    const expiryInterval = setInterval(updateExpiryTimer, 1000);
    const cooldownInterval = setInterval(updateCooldownTimer, 1000);

    // 3. Handle Resend Click via AJAX (with fallback)
    resendBtn.addEventListener('click', function(e) {
        if (resendBtn.disabled) return;
        
        // Disable button while processing
        resendBtn.disabled = true;
        resendBtn.style.opacity = '0.6';
        resendBtn.style.cursor = 'wait';
        resendBtnText.textContent = 'Sending new OTP...';
        resendIcon.style.display = 'none';
        
        // Hide previous messages
        alertSuccess.style.display = 'none';
        alertError.style.display = 'none';
        
        const formData = new FormData();
        formData.append('action', 'resend_otp');
        formData.append('ajax', '1');
        
        fetch('verify_otp.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Success! Reset timers
                secondsLeft = data.expires_in || 300;
                cooldownLeft = data.resend_cooldown || 30;
                
                alertSuccessText.textContent = '✓ ' + (data.message || 'A new OTP has been sent. Valid for 5 minutes.');
                alertSuccess.style.display = 'block';
                
                // Clear input and focus
                otpInput.value = '';
                otpInput.focus();
                
                updateExpiryTimer();
                updateCooldownTimer();
            } else {
                alertErrorText.textContent = data.message || 'Failed to resend OTP. Please try again.';
                alertError.style.display = 'block';
                cooldownLeft = data.resend_cooldown || 5;
                updateCooldownTimer();
            }
        })
        .catch(err => {
            console.error('AJAX Resend Error:', err);
            // Fallback to standard form submission if AJAX fails
            document.getElementById('fallbackResendForm').submit();
        });
    });
});
</script>

<?php require 'footer.php'; ?>
