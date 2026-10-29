<?php
require 'db.php';
if(isLoggedIn()) redirect('index.php');

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = isset($_POST['confirm_password']) ? mysqli_real_escape_string($conn, $_POST['confirm_password']) : '';
    
    // Validations: Supports standard providers as well as @utu.ac.in institutional emails
    $valid_email_regex = '/^[a-zA-Z0-9._%+-]+@(gmail\.com|yahoo\.com|ymail\.com|hotmail\.com|outlook\.com|icloud\.com|aol\.com|protonmail\.com|zoho\.com|utu\.ac\.in|([a-zA-Z0-9.-]+\.)?utu\.ac\.in)$/i';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match($valid_email_regex, $email)) {
        $error = "Invalid email format. Please use an official @utu.ac.in or standard email provider.";
    } elseif (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
        $error = "Invalid phone number. Must be 10 digits and start with 6, 7, 8, or 9.";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
        $error = "Invalid name. Only letters and spaces are allowed.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $check_res = mysqli_query($conn, "SELECT id, is_verified FROM users WHERE email = '$email'");
    
        if (mysqli_num_rows($check_res) > 0) {
            $existing_user = mysqli_fetch_assoc($check_res);
            if ($existing_user['is_verified'] == 0) {
                // Account exists but is unverified: update details & resend fresh OTP
                $user_id = (int)$existing_user['id'];
                mysqli_query($conn, "UPDATE users SET name = '$name', phone = '$phone', password = '$password' WHERE id = $user_id");
                $_SESSION['temp_user_id'] = $user_id;
                
                require_once 'otp_helper.php';
                $otp_result = sendUserOTP($user_id, $conn, true);
                $_SESSION['last_otp_result'] = $otp_result;
                
                redirect('verify_otp.php');
            } else {
                $error = "An account with this email already exists. <a href='login.php' style='color:#00d2ff; text-decoration:underline;'>Please Login here</a>.";
            }
        } else {
            $query = "INSERT INTO users (name, email, phone, password, is_verified) VALUES ('$name', '$email', '$phone', '$password', 0)";
            if (mysqli_query($conn, $query)) {
                $user_id = mysqli_insert_id($conn);
                $_SESSION['temp_user_id'] = $user_id;
                
                require_once 'otp_helper.php';
                $otp_result = sendUserOTP($user_id, $conn, true);
                $_SESSION['last_otp_result'] = $otp_result;
                
                redirect('verify_otp.php');
            } else {
                $error = "Registration failed: " . mysqli_error($conn);
            }
        }
    }
}

require 'header.php';
?>

<div style="text-align: center; margin-bottom: 24px;">
    <img src="img/onyxplay-icon.png" alt="ONYXPLAY" class="brand-auth-icon">
    <h2 style="text-align: center; margin: 12px 0 4px;">Join ONYXPLAY</h2>
    <p style="color: var(--text-muted); font-size: 0.92rem; margin: 0;">Create an account for doorstep console & gear rentals</p>
</div>
<?php if($error): ?><p style="color:#ef4444; text-align: center; font-weight: 600;"><?php echo $error; ?></p><?php endif; ?>
<form id="registerForm" method="POST" style="max-width: 400px; margin: 0 auto; background: var(--container-bg); padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border-color);">
    <label>Name:</label>
    <input type="text" id="name" name="name" required pattern="[A-Za-z\s]+" title="Only letters and spaces are allowed" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" placeholder="Enter your full name">
    
    <label>Email Address:</label>
    <input type="email" id="email" name="email" required placeholder="Enter Your Email Id" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
    <small style="color: var(--text-muted); font-size: 0.76rem; display: block; margin-top: -6px; margin-bottom: 8px;"></small>
    <span id="email-error" style="color:red; font-size:12px; display:block; margin-top:-4px; margin-bottom:10px;"></span>
    
    <label>Phone Number:</label>
    <input type="text" id="phone" name="phone" required maxlength="10" pattern="[6-9][0-9]{9}" title="Must be a 10 digit number starting with 6-9" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" placeholder="10-digit mobile number">
    <span id="phone-error" style="color:red; font-size:12px; display:block; margin-top:-10px; margin-bottom:10px;"></span>
    
    <label>Password:</label>
    <input type="password" id="password" name="password" required minlength="6">
    
    <label>Confirm Password:</label>
    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
    <span id="password-error" style="color:red; font-size:12px; display:block; margin-top:-10px; margin-bottom:10px;"></span>
    
    <div style="margin-bottom: 15px;">
        <input type="checkbox" id="show_password" style="width: auto; display: inline-block;">
        <label for="show_password" style="display: inline-block;">Show Password</label>
    </div>
    
    <button type="submit" id="submitBtn" class="btn" style="width: 100%;">Register</button>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('email');
    const emailError = document.getElementById('email-error');
    const phoneInput = document.getElementById('phone');
    const phoneError = document.getElementById('phone-error');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordError = document.getElementById('password-error');
    const showPasswordCheckbox = document.getElementById('show_password');
    const submitBtn = document.getElementById('submitBtn');

    function validateEmail() {
        const email = emailInput.value.trim();
        // Restrict to popular mail extensions and @utu.ac.in institutional emails
        const emailRegex = /^[a-zA-Z0-9._%+-]+@(gmail\.com|yahoo\.com|ymail\.com|hotmail\.com|outlook\.com|icloud\.com|aol\.com|protonmail\.com|zoho\.com|utu\.ac\.in|([a-zA-Z0-9.-]+\.)?utu\.ac\.in)$/i;
        if (email.length > 0 && !emailRegex.test(email)) {
            emailError.textContent = 'please enter the valid email id';
            return false;
        } else {
            emailError.textContent = '';
            return true;
        }
    }

    function validatePhone() {
        const phone = phoneInput.value;
        const phoneRegex = /^[6-9][0-9]{9}$/;
        if (phone.length > 0 && !phoneRegex.test(phone)) {
            phoneError.textContent = 'Invalid phone number. Must be 10 digits starting with 6-9.';
            return false;
        } else {
            phoneError.textContent = '';
            return true;
        }
    }

    function validatePassword() {
        if (confirmPasswordInput.value.length > 0 && passwordInput.value !== confirmPasswordInput.value) {
            passwordError.textContent = 'Passwords do not match.';
            return false;
        } else {
            passwordError.textContent = '';
            return true;
        }
    }

    emailInput.addEventListener('input', validateEmail);
    phoneInput.addEventListener('input', validatePhone);
    passwordInput.addEventListener('input', validatePassword);
    confirmPasswordInput.addEventListener('input', validatePassword);

    showPasswordCheckbox.addEventListener('change', function() {
        const type = this.checked ? 'text' : 'password';
        passwordInput.type = type;
        confirmPasswordInput.type = type;
    });

    document.getElementById('registerForm').addEventListener('submit', function(e) {
        if (!validateEmail() || !validatePhone() || !validatePassword()) {
            e.preventDefault();
        }
    });
});
</script>

<?php require 'footer.php'; ?>
