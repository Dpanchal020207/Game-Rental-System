<?php
require 'db.php';
if (!isLoggedIn() || isAdmin()) redirect('index.php');

$user_id = (int)$_SESSION['user_id'];

$res = mysqli_query($conn, "SELECT * FROM bookings WHERE user_id = $user_id ORDER BY created_at DESC");
$bookings = [];
while ($row = mysqli_fetch_assoc($res)) {
    $bookings[] = $row;
}

require 'header.php';
?>

<h2>My Bookings Dashboard</h2>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div style="background: #d4edda; color: #155724; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;">
        <?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
    </div>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;">
        <?php echo $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
    </div>
<?php endif; ?>

<?php if (empty($bookings)): ?>
    <p>You have no bookings yet.</p>
<?php else: ?>
    <table>
        <tr>
            <th>Booking ID</th>
            <th>Delivery Date</th>
            <th>Return Date</th>
            <th>Total Amount</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php foreach ($bookings as $b): ?>
        <tr>
            <td>
                <span style="font-family: monospace; font-weight: 700; color: #00f0ff;"><?php echo htmlspecialchars($b['booking_id']); ?></span>
            </td>
            <td><?php echo htmlspecialchars($b['delivery_date']); ?></td>
            <td><?php echo htmlspecialchars($b['return_date']); ?></td>
            <td><strong>₹<?php echo htmlspecialchars($b['total_amount']); ?></strong></td>
            <td>
                <span style="font-size: 0.82rem; font-weight: 600; color: #10b981;">
                    💳 <?php echo ($b['payment_method'] ?? 'razorpay') === 'razorpay' ? 'Razorpay' : 'UPI'; ?>
                </span>
                <?php if (!empty($b['payment_id'])): ?>
                    <br><span style="font-size: 0.75rem; font-family: monospace; color: var(--text-muted);"><?php echo htmlspecialchars(substr($b['payment_id'], 0, 14)) . (strlen($b['payment_id']) > 14 ? '...' : ''); ?></span>
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($b['status']); ?></td>
            <td>
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                    <?php if ($b['status'] === 'Pending' || $b['status'] === 'Confirmed'): ?>
                        <form action="cancel_order.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?');" style="margin: 0;">
                            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                            <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.82rem;">Cancel</button>
                        </form>
                    <?php endif; ?>
                    <form action="resend_order_email.php" method="POST" style="margin: 0;">
                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                        <button type="submit" class="btn" style="padding: 6px 12px; font-size: 0.82rem; background: var(--btn-accent, #00d2ff); color: #fff;">✉️ Email Receipt</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require 'footer.php'; ?>
