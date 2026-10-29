<?php
require 'db.php';
require 'header.php';

$cart = $_SESSION['cart'] ?? [];
$total_cost = 0;

?>
<h2>Your Shopping Cart</h2>

<?php if (empty($cart)): ?>
    <div style="text-align: center; padding: 40px 20px; background: var(--container-bg); border-radius: 12px; margin-top: 20px;">
        <p style="font-size: 1.1rem; color: var(--text-muted); margin-bottom: 16px;">Your cart is currently empty.</p>
        <a href="catalog.php" class="btn" style="display: inline-block;">🎮 Browse Rental Catalog</a>
    </div>
<?php else: ?>
    <table>
        <tr>
            <th>Item</th>
            <th>Delivery Date</th>
            <th>Return Date</th>
            <th>Duration (Days)</th>
            <th>Daily Rate</th>
            <th>Total</th>
            <th>Action</th>
        </tr>
        <?php foreach ($cart as $index => $item): 
            $gadget_id = (int)$item['gadget_id'];
            $res = mysqli_query($conn, "SELECT title, daily_price FROM gadgets WHERE id = $gadget_id");
            $gadget = mysqli_fetch_assoc($res);
            
            $d1 = new DateTime($item['delivery_date']);
            $d2 = new DateTime($item['return_date']);
            $days = $d2->diff($d1)->days;
            $item_total = $days * $gadget['daily_price'];
            $total_cost += $item_total;
        ?>
        <tr>
            <td>
                <a href="gadget_details.php?id=<?php echo $gadget_id; ?>" style="color: inherit; text-decoration: none; font-weight: 700; transition: color 0.2s ease;" onmouseover="this.style.color='#00d2ff'" onmouseout="this.style.color='inherit'" title="View product details and images">
                    <?php echo htmlspecialchars($gadget['title']); ?>
                </a>
            </td>
            <td><?php echo htmlspecialchars($item['delivery_date']); ?></td>
            <td><?php echo htmlspecialchars($item['return_date']); ?></td>
            <td><?php echo $days; ?></td>
            <td>₹<?php echo htmlspecialchars($gadget['daily_price']); ?></td>
            <td>₹<?php echo number_format($item_total, 2); ?></td>
            <td><a href="cart_action.php?remove=<?php echo $index; ?>" class="btn btn-danger">Remove</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <h3>Total Cost: ₹<?php echo number_format($total_cost, 2); ?></h3>
    <br>
    
    <?php if (isLoggedIn() && !isAdmin()): 
        $user_id = (int)$_SESSION['user_id'];
        $u_q = mysqli_query($conn, "SELECT name, phone FROM users WHERE id = $user_id");
        $u_info = mysqli_fetch_assoc($u_q);
        $user_phone = $u_info['phone'] ?? '';
    ?>
        <div style="max-width: 760px; background: var(--container-bg); padding: 30px; border-radius: 16px; border: 1.5px solid var(--border-color); box-shadow: var(--card-shadow); margin-top: 25px; position: relative; overflow: hidden;">
            <div style="position: absolute; top: 0; left: 10%; right: 10%; height: 3px; background: linear-gradient(90deg, transparent, #00f0ff, #8b5cf6, transparent);"></div>
            
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                <span style="font-size: 1.8rem;">📦</span>
                <div>
                    <h3 style="margin: 0; font-family: 'Rajdhani', sans-serif; font-size: 1.6rem; letter-spacing: 0.02em;">Shipping & Delivery Address</h3>
                    <p style="margin: 2px 0 0; font-size: 0.88rem; color: var(--text-muted);">Please provide your accurate delivery details for safe doorstep dispatch of your rental gear.</p>
                </div>
            </div>

            <hr style="border: none; border-top: 1px solid var(--border-color); margin: 18px 0 22px;">

            <form action="payment.php" method="POST">
                <input type="hidden" name="total_amount" value="<?php echo $total_cost; ?>">

                <div style="margin-bottom: 16px;">
                    <label style="display: flex; align-items: center; gap: 6px; font-weight: 700;">
                        <span>🏠</span> Flat / House No., Apartment & Building Name <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" name="house_no" placeholder="e.g. Flat 402, B-Wing, Skyline Heights, 4th Floor" required style="margin-top: 6px;">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: flex; align-items: center; gap: 6px; font-weight: 700;">
                        <span>🛣️</span> Street Address, Locality / Colony & Area <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" name="street_address" placeholder="e.g. 100 Feet Ring Road, Satellite Area" required style="margin-top: 6px;">
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="display: flex; align-items: center; gap: 6px; font-weight: 700;">
                            <span>🚩</span> Landmark <span style="font-size: 0.8rem; font-weight: 500; color: var(--text-muted);">(Optional)</span>
                        </label>
                        <input type="text" name="landmark" placeholder="e.g. Near Shivranjani Cross Roads / Opp. Mall" style="margin-top: 6px;">
                    </div>
                    <div>
                        <label style="display: flex; align-items: center; gap: 6px; font-weight: 700;">
                            <span>🏙️</span> City <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" name="city" placeholder="e.g. Ahmedabad, Mumbai, Bengaluru" required style="margin-top: 6px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="display: flex; align-items: center; gap: 6px; font-weight: 700;">
                            <span>🗺️</span> State / Region <span style="color: #ef4444;">*</span>
                        </label>
                        <select name="state" required style="margin-top: 6px;">
                            <option value="">-- Select State / Union Territory --</option>
                            <option value="Gujarat" selected>Gujarat</option>
                            <option value="Maharashtra">Maharashtra</option>
                            <option value="Delhi">Delhi (NCT)</option>
                            <option value="Karnataka">Karnataka</option>
                            <option value="Tamil Nadu">Tamil Nadu</option>
                            <option value="Telangana">Telangana</option>
                            <option value="Rajasthan">Rajasthan</option>
                            <option value="Uttar Pradesh">Uttar Pradesh</option>
                            <option value="Madhya Pradesh">Madhya Pradesh</option>
                            <option value="West Bengal">West Bengal</option>
                            <option value="Punjab">Punjab</option>
                            <option value="Haryana">Haryana</option>
                            <option value="Kerala">Kerala</option>
                            <option value="Andhra Pradesh">Andhra Pradesh</option>
                            <option value="Bihar">Bihar</option>
                            <option value="Chandigarh">Chandigarh</option>
                            <option value="Goa">Goa</option>
                            <option value="Himachal Pradesh">Himachal Pradesh</option>
                            <option value="Jammu and Kashmir">Jammu and Kashmir</option>
                            <option value="Jharkhand">Jharkhand</option>
                            <option value="Odisha">Odisha</option>
                            <option value="Uttarakhand">Uttarakhand</option>
                            <option value="Other">Other / International</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: flex; align-items: center; gap: 6px; font-weight: 700;">
                            <span>📮</span> PIN / Postal Code <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" name="pincode" placeholder="e.g. 380015" pattern="[0-9]{6}" maxlength="6" title="Please enter a valid 6-digit postal code" required style="margin-top: 6px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="display: flex; align-items: center; gap: 6px; font-weight: 700;">
                            <span>📞</span> Delivery Contact Phone <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="tel" name="delivery_phone" value="<?php echo htmlspecialchars($user_phone); ?>" pattern="[6-9][0-9]{9}" maxlength="10" placeholder="10-digit mobile number" title="10-digit mobile number starting with 6-9" required style="margin-top: 6px;">
                    </div>
                    <div>
                        <label style="display: flex; align-items: center; gap: 6px; font-weight: 700;">
                            <span>📝</span> Delivery Instructions <span style="font-size: 0.8rem; font-weight: 500; color: var(--text-muted);">(Optional)</span>
                        </label>
                        <input type="text" name="delivery_notes" placeholder="e.g. Ring bell, deliver after 4 PM, call on arrival" style="margin-top: 6px;">
                    </div>
                </div>

                <div style="background: rgba(0, 240, 255, 0.05); border: 1px dashed rgba(0, 240, 255, 0.3); border-radius: 10px; padding: 12px 16px; margin: 20px 0 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <span style="font-size: 0.88rem; color: var(--text-color);">
                        🔒 <strong>Secure Razorpay & UPI Checkout:</strong> Safe online payments via UPI, Cards, or Netbanking.
                    </span>
                    <span style="font-family: 'Rajdhani', sans-serif; font-size: 1.3rem; font-weight: 800; color: #00f0ff;">
                        Total: ₹<?php echo number_format($total_cost, 2); ?>
                    </span>
                </div>

                <button type="submit" class="btn" style="width: 100%; padding: 14px; font-size: 1.05rem; font-weight: 700; letter-spacing: 0.04em;">
                    Proceed to Payment &rarr;
                </button>
            </form>
        </div>
    <?php elseif (isAdmin()): ?>
        <p>Admins cannot place orders.</p>
    <?php else: ?>
        <p>You must <a href="login.php">login</a> to checkout.</p>
    <?php endif; ?>
    
<?php endif; ?>

<?php require 'footer.php'; ?>
