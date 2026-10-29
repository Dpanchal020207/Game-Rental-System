<?php
require 'db.php';
if(isLoggedIn()) redirect('index.php');

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $res = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
    if (mysqli_num_rows($res) > 0) {
        $msg = "A password reset link has been sent to your email (simulated).";
    } else {
        $msg = "Email not found.";
    }
}

require 'header.php';
?>

<div style="max-width: 400px; margin: 40px auto; background: var(--container-bg); padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border-color);">
    <h2 style="text-align:center; margin-top:0;">Reset Password</h2>
    <?php if($msg): ?><p style="text-align:center; color: #007bff; background: #e6f2ff; padding: 10px; border-radius: 5px;"><?php echo $msg; ?></p><?php endif; ?>
    
    <form method="POST">
        <label>Enter your registered email address:</label>
        <input type="email" name="email" required placeholder="Enter the Registered Email Id" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        <button type="submit" class="btn" style="width: 100%;">Send Reset Link</button>
    </form>
    <p style="text-align: center; margin-top: 15px;"><a href="login.php" style="color: #666; text-decoration: none;">&larr; Back to Login</a></p>
</div>

<?php require 'footer.php'; ?>
