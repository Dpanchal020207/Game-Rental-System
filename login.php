<?php
require 'db.php';
require_once 'otp_helper.php';
if(isLoggedIn()) redirect('index.php');

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    
    $res = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
    $user = mysqli_fetch_assoc($res);
    
    if ($user && $password === $user['password']) {
        $requires_otp = ($user['role'] === 'customer') && (PERMANENT_LOGIN_OTP || $user['is_verified'] == 0);
        
        if ($requires_otp) {
            $_SESSION['temp_user_id'] = $user['id'];
            $otp_res = sendUserOTP($user['id'], $conn, true);
            $_SESSION['last_otp_result'] = $otp_res;
            redirect('verify_otp.php');
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            if ($user['role'] === 'admin') {
                redirect('admin_dashboard.php');
            } else {
                redirect('index.php');
            }
        }
    } else {
        $error = "Invalid credentials.";
    }
}

require 'header.php';
?>

<div style="text-align: center; margin-bottom: 24px;">
    <img src="img/onyxplay-icon.png" alt="ONYXPLAY" class="brand-auth-icon">
    <h2 style="text-align: center; margin: 12px 0 4px;">Welcome Back to ONYXPLAY</h2>
    <p style="color: var(--text-muted); font-size: 0.92rem; margin: 0;">Sign in to your account to book hardware & track rentals</p>
</div>
<?php if($error): ?><p style="color:#ef4444; text-align: center; font-weight: 600;"><?php echo $error; ?></p><?php endif; ?>
<form method="POST" style="max-width: 400px; margin: 0 auto; background: var(--container-bg); padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border-color);">
    <label>Email Address:</label>
    <input type="email" name="email" required placeholder="Enter the Registered Email-ID" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
    <small style="color: var(--text-muted); font-size: 0.76rem; display: block; margin-top: -6px; margin-bottom: 12px;"></small>
    <label>Password:</label>
    <input type="password" name="password" required>
    
    <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
    
    <button type="submit" class="btn" style="width: 100%;">Login</button>
</form>

<?php require 'footer.php'; ?>
