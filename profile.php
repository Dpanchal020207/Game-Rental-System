<?php
require 'db.php';
if (!isLoggedIn() || isAdmin()) redirect('index.php');

$user_id = (int)$_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    
    if (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
        $error_msg = "Invalid name format. Only letters and spaces are allowed.";
    } elseif (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
        $error_msg = "Invalid phone format. Must be 10 digits starting with 6-9.";
    } else {
        $query = "UPDATE users SET name = '$name', phone = '$phone'";
        
        if (!empty($_POST['new_password'])) {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (strlen($new_password) < 6) {
                $error_msg = "Password must be at least 6 characters.";
            } elseif ($new_password !== $confirm_password) {
                $error_msg = "Passwords do not match. Please reconfirm your new password.";
            } else {
                $password = mysqli_real_escape_string($conn, $new_password);
                $query .= ", password = '$password'";
            }
        }
        
        if (empty($error_msg)) {
            $query .= " WHERE id = $user_id";
            if (mysqli_query($conn, $query)) {
                $_SESSION['name'] = $name;
                $success_msg = "Profile updated successfully!";
            } else {
                $error_msg = "Failed to update profile.";
            }
        }
    }
}

// Fetch user data for form
$user_res = mysqli_query($conn, "SELECT name, email, phone FROM users WHERE id = $user_id");
$user_data = mysqli_fetch_assoc($user_res);

require 'header.php';
?>

<div style="max-width: 500px; margin: 40px auto; background: var(--container-bg); padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border-color);">
    <h2 style="text-align: center; margin-top: 0;">My Profile</h2>
    
    <?php if($success_msg): ?>
        <div style="background:#d4edda; color:#155724; padding:15px; border-radius:5px; margin-bottom: 20px;">
            <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>
    <?php if($error_msg): ?>
        <div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:5px; margin-bottom: 20px;">
            <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="update_profile" value="1">
        
        <label>Name:</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($user_data['name']); ?>" required>
        
        <label>Email (Cannot be changed):</label>
        <input type="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" disabled style="opacity: 0.7; cursor: not-allowed;">
        
        <label>Phone Number:</label>
        <input type="text" name="phone" value="<?php echo htmlspecialchars($user_data['phone']); ?>" required>
        
        <label>New Password (leave blank to keep current):</label>
        <input type="password" name="new_password" id="new_password" minlength="6">
        
        <label>Reconfirm New Password:</label>
        <input type="password" name="confirm_password" id="confirm_password" minlength="6">
        
        <button type="submit" class="btn" style="width: 100%; margin-top: 10px;">Save Changes</button>
    </form>
    
    <script>
        // Simple client-side validation to ensure passwords match before submitting
        document.querySelector('form').addEventListener('submit', function(e) {
            const pwd = document.getElementById('new_password').value;
            const confirm = document.getElementById('confirm_password').value;
            if (pwd !== '' && pwd !== confirm) {
                e.preventDefault();
                alert('Passwords do not match! Please reconfirm your new password.');
            }
        });
    </script>
</div>

<?php require 'footer.php'; ?>
