<?php
require 'db.php';
if (!isAdmin()) redirect('index.php');

// Handle Manual Verification POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_verify_user'])) {
    $verify_id = (int)$_POST['verify_user_id'];
    mysqli_query($conn, "UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE id = $verify_id");
    $_SESSION['admin_flash_success'] = "User #$verify_id has been manually verified.";
    redirect('admin_users.php');
}

// Handle User Deletion POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $del_id = (int)$_POST['delete_user_id'];
    
    // Check if the user has active bookings
    $active_check = mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE user_id = $del_id AND status IN ('Pending', 'Confirmed', 'Dispatched')");
    $active_row = mysqli_fetch_assoc($active_check);
    
    if (!empty($active_row['count']) && $active_row['count'] > 0) {
        $_SESSION['admin_flash_error'] = "Cannot delete user #$del_id: This user currently has {$active_row['count']} active booking(s). Please complete or cancel them before deleting.";
    } else {
        try {
            // Delete user - cascading foreign key removes associated bookings & items cleanly
            $del_res = mysqli_query($conn, "DELETE FROM users WHERE id = $del_id");
            if ($del_res) {
                $_SESSION['admin_flash_success'] = "User #$del_id deleted successfully.";
            } else {
                $_SESSION['admin_flash_error'] = "Failed to delete user: " . mysqli_error($conn);
            }
        } catch (Exception $e) {
            $_SESSION['admin_flash_error'] = "Database error deleting user: " . $e->getMessage();
        }
    }
    redirect('admin_users.php');
}

// Fetch users
$res = mysqli_query($conn, "SELECT * FROM users WHERE role = 'customer' ORDER BY id DESC");
$users = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        // Count active bookings
        $user_id = $row['id'];
        $b_res = mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE user_id = $user_id AND status IN ('Pending', 'Confirmed', 'Dispatched')");
        $b_row = mysqli_fetch_assoc($b_res);
        $row['active_bookings'] = $b_row['count'];
        $users[] = $row;
    }
}

// Retrieve flash messages
$flash_success = $_SESSION['admin_flash_success'] ?? '';
$flash_error = $_SESSION['admin_flash_error'] ?? '';
unset($_SESSION['admin_flash_success'], $_SESSION['admin_flash_error']);

require 'header.php';
?>

<h2>Registered Users (Customer Profiles)</h2>

<?php if ($flash_success): ?>
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #10b981; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;">
        ✓ <?php echo htmlspecialchars($flash_success); ?>
    </div>
<?php endif; ?>

<?php if ($flash_error): ?>
    <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #ef4444; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;">
        ⚠️ <?php echo htmlspecialchars($flash_error); ?>
    </div>
<?php endif; ?>

<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Verification Status</th>
        <th>Active Bookings</th>
        <th>Status</th>
        <th>Action</th>
    </tr>
    <?php foreach ($users as $u): 
        $is_online = false;
        if ($u['last_activity']) {
            $last = strtotime($u['last_activity']);
            if (time() - $last < 300) { // Active in last 5 mins
                $is_online = true;
            }
        }
    ?>
    <tr>
        <td><?php echo $u['id']; ?></td>
        <td><?php echo htmlspecialchars($u['name']); ?></td>
        <td><?php echo htmlspecialchars($u['email']); ?></td>
        <td><?php echo htmlspecialchars($u['phone']); ?></td>
        <td>
            <?php if ($u['is_verified']): ?>
                <span style="color: #10b981; font-weight: 600;">Verified</span>
            <?php else: ?>
                <span style="color: #f59e0b; font-weight: 600;">Pending OTP</span>
            <?php endif; ?>
        </td>
        <td><?php echo $u['active_bookings']; ?></td>
        <td>
            <?php if($is_online): ?>
                <span style="color:green; font-weight:bold;">Online</span>
            <?php else: ?>
                <span style="color:gray;">Offline</span><br>
                <small><?php echo $u['last_activity'] ? $u['last_activity'] : 'Never'; ?></small>
            <?php endif; ?>
        </td>
        <td>
            <?php if(!$u['is_verified']): ?>
                <form action="admin_users.php" method="POST" style="display:inline-block; margin-right: 4px;">
                    <input type="hidden" name="verify_user_id" value="<?php echo $u['id']; ?>">
                    <button type="submit" name="manual_verify_user" class="btn" style="background: #10b981; padding: 5px 9px; font-size: 11px; font-weight: 600;" title="Manually activate this user without OTP">Verify</button>
                </form>
            <?php endif; ?>
            <form action="admin_users.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');" style="display:inline-block;">
                <input type="hidden" name="delete_user_id" value="<?php echo $u['id']; ?>">
                <button type="submit" name="delete_user" class="btn btn-danger" style="padding: 5px 9px; font-size: 11px;">Delete</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php require 'footer.php'; ?>
