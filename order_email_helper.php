<?php
// Centralized Order & Status Email Helper for ONYXPLAY
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/smtp_config.php';

/**
 * Send full Order Confirmation Email ONLY to the customer/user upon booking placement
 * 
 * @param string|int $booking_identifier Either the string booking_id (e.g. 'BKG-67...') or numeric id
 * @param mysqli $conn
 * @return array ['success' => bool, 'message' => string, 'email' => string]
 */
function sendOrderConfirmationEmail($booking_identifier, $conn) {
    if (empty($booking_identifier)) {
        return ['success' => false, 'message' => 'Missing booking identifier'];
    }

    // Fetch booking details along with user details
    if (is_numeric($booking_identifier)) {
        $stmt = mysqli_prepare($conn, "SELECT b.*, u.name as user_name, u.email as user_email, u.phone as user_phone 
                                       FROM bookings b 
                                       JOIN users u ON b.user_id = u.id 
                                       WHERE b.id = ?");
        $id_val = (int)$booking_identifier;
        mysqli_stmt_bind_param($stmt, "i", $id_val);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT b.*, u.name as user_name, u.email as user_email, u.phone as user_phone 
                                       FROM bookings b 
                                       JOIN users u ON b.user_id = u.id 
                                       WHERE b.booking_id = ?");
        $str_val = (string)$booking_identifier;
        mysqli_stmt_bind_param($stmt, "s", $str_val);
    }

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $booking = mysqli_fetch_assoc($res);

    if (!$booking) {
        return ['success' => false, 'message' => 'Booking not found in database'];
    }

    $b_id = (int)$booking['id'];
    $booking_code = $booking['booking_id'];
    $user_name = $booking['user_name'];
    $user_email = $booking['user_email'];
    $user_phone = $booking['user_phone'];
    $delivery_date = date('d-m-Y', strtotime($booking['delivery_date']));
    $return_date = date('d-m-Y', strtotime($booking['return_date']));
    $total_days = (int)$booking['total_days'];
    $total_amount = number_format((float)$booking['total_amount'], 2);
    $delivery_address = $booking['delivery_address'];
    $city = $booking['city'];
    $order_date = date('d-m-Y h:i A', strtotime($booking['created_at']));
    $payment_method_raw = $booking['payment_method'] ?? 'razorpay';
    $payment_method_label = ($payment_method_raw === 'razorpay') ? 'Razorpay Online Gateway' : 'Direct UPI Transfer';
    $payment_id_display = !empty($booking['payment_id']) ? htmlspecialchars($booking['payment_id']) : ('pay_' . strtoupper(substr(md5($b_id), 0, 10)));

    // Fetch booked items
    $items_query = "SELECT bi.*, g.title as gadget_title, g.daily_price, c.name as category_name, h.serial_number 
                    FROM booking_items bi 
                    JOIN hardware_units h ON bi.hardware_unit_id = h.id 
                    JOIN gadgets g ON h.gadget_id = g.id 
                    LEFT JOIN categories c ON g.category_id = c.id 
                    WHERE bi.booking_id = $b_id";
    $items_res = mysqli_query($conn, $items_query);
    $items = [];
    if ($items_res) {
        while ($item = mysqli_fetch_assoc($items_res)) {
            $items[] = $item;
        }
    }

    $items_rows = '';
    $items_text = '';
    foreach ($items as $it) {
        $title = htmlspecialchars($it['gadget_title']);
        $cat = htmlspecialchars(ucfirst($it['category_name'] ?? 'Gear'));
        $rate = number_format((float)$it['price_per_day'], 2);
        $subtotal = number_format((float)$it['price_per_day'] * $total_days, 2);

        $items_rows .= "
        <tr style='border-bottom: 1px solid #1e293b;'>
            <td style='padding: 10px 8px; color: #f1f5f9; font-weight: 600;'>$title <span style='font-size: 11px; color: #00d2ff;'>($cat)</span></td>
            <td style='padding: 10px 8px; text-align: center; color: #94a3b8;'>&#8377;$rate / day</td>
            <td style='padding: 10px 8px; text-align: center; color: #94a3b8;'>$total_days Days</td>
            <td style='padding: 10px 8px; text-align: right; font-weight: 700; color: #00f0ff;'>&#8377;$subtotal</td>
        </tr>
        ";

        $items_text .= "- $title ($cat) | Rs. $rate/day x $total_days days = Rs. $subtotal\n";
    }

    $html_body = "
    <div style='font-family: Arial, sans-serif; max-width: 580px; margin: 0 auto; background: #0b0e17; border: 1px solid #1e293b; border-radius: 14px; overflow: hidden; box-shadow: 0 12px 35px rgba(0,0,0,0.6); color: #f1f5f9;'>
        <!-- Top Dual-Tone Highlight Bar -->
        <div style='height: 4px; background: linear-gradient(90deg, #00f0ff 0%, #b026ff 100%);'></div>

        <div style='padding: 30px 25px;'>
            <div style='text-align: center; margin-bottom: 25px;'>
                <div style='font-size: 28px; font-weight: 800; letter-spacing: 2px; line-height: 1.1;'>
                    <span style='color: #cbd5e1;'>ONYX</span><span style='color: #00f0ff;'>PLAY</span>
                </div>
                <div style='color: #a855f7; font-size: 13px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-top: 4px;'>Next-Gen Game Rentals</div>
                <h2 style='color: #00d2ff; font-size: 20px; margin: 15px 0 6px 0; font-weight: 700;'>Booking Confirmation</h2>
                <p style='color: #94a3b8; font-size: 13px; margin: 0;'>Your gaming gear is officially reserved and prepped for delivery.</p>
            </div>

            <p style='color: #e2e8f0; font-size: 15px;'>Hello <strong style='color: #00f0ff;'>" . htmlspecialchars($user_name) . "</strong>,</p>
            <p style='color: #cbd5e1; font-size: 14px; line-height: 1.5;'>We have successfully confirmed your ONYXPLAY rental order. Below are your booking specifications and rental timeline:</p>

            <!-- Booking details summary -->
            <div style='background: #121726; border: 1px solid #1e293b; border-radius: 10px; padding: 16px; margin: 18px 0; font-size: 14px;'>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 6px 0; color: #94a3b8; width: 40%;'>Booking ID:</td>
                        <td style='padding: 6px 0; font-weight: 700; color: #00f0ff; font-family: monospace; font-size: 15px;'>$booking_code</td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #94a3b8;'>Order Date:</td>
                        <td style='padding: 6px 0; color: #e2e8f0;'>$order_date</td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #94a3b8;'>Delivery Date:</td>
                        <td style='padding: 6px 0; font-weight: 700; color: #10b981;'>$delivery_date</td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #94a3b8;'>Return Date:</td>
                        <td style='padding: 6px 0; font-weight: 700; color: #f59e0b;'>$return_date</td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #94a3b8;'>Rental Duration:</td>
                        <td style='padding: 6px 0; color: #e2e8f0;'>$total_days Day(s)</td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #94a3b8;'>Delivery Destination:</td>
                        <td style='padding: 6px 0; color: #e2e8f0;'>" . htmlspecialchars($delivery_address) . ", " . htmlspecialchars($city) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #94a3b8;'>Payment Method:</td>
                        <td style='padding: 6px 0; font-weight: 700; color: #00f0ff;'>$payment_method_label</td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #94a3b8;'>Payment / Transaction ID:</td>
                        <td style='padding: 6px 0; font-family: monospace; color: #10b981; font-weight: 600;'>$payment_id_display</td>
                    </tr>
                </table>
            </div>

            <!-- Reserved Items -->
            <div style='margin: 22px 0;'>
                <div style='font-size: 14px; font-weight: 700; color: #cbd5e1; margin-bottom: 8px;'>Reserved Hardware & Gear:</div>
                <table style='width: 100%; border-collapse: collapse; font-size: 13px; border: 1px solid #1e293b;'>
                    <thead>
                        <tr style='background: #161d30;'>
                            <th style='padding: 8px; text-align: left; color: #94a3b8;'>Item</th>
                            <th style='padding: 8px; text-align: center; color: #94a3b8;'>Rate</th>
                            <th style='padding: 8px; text-align: center; color: #94a3b8;'>Days</th>
                            <th style='padding: 8px; text-align: right; color: #94a3b8;'>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        $items_rows
                    </tbody>
                    <tfoot>
                        <tr style='background: #141a2c; border-top: 2px solid #1e293b;'>
                            <td colspan='3' style='padding: 10px 8px; text-align: right; font-weight: 700; color: #e2e8f0;'>Total Amount Paid:</td>
                            <td style='padding: 10px 8px; text-align: right; font-weight: 800; font-size: 17px; color: #00f0ff;'>&#8377;$total_amount</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Instructions -->
            <div style='background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.35); border-radius: 8px; padding: 14px; margin-bottom: 20px; font-size: 13px; color: #a7f3d0;'>
                <strong style='color: #34d399;'>Fulfillment & Handover Steps:</strong>
                <ul style='margin: 6px 0 0 0; padding-left: 20px; line-height: 1.5;'>
                    <li>Our fulfillment team will contact you at <strong>" . htmlspecialchars($user_phone) . "</strong> prior to delivery dispatch.</li>
                    <li>Please keep a valid ID ready for contactless verification upon delivery.</li>
                    <li>Pickup is automatically scheduled for your return date ($return_date).</li>
                </ul>
            </div>

            <p style='color: #94a3b8; font-size: 12px; text-align: center; margin: 15px 0 0 0;'>
                You can inspect real-time progress anytime under <strong style='color: #f1f5f9;'>My Bookings</strong> on your account.
            </p>

            <hr style='border: none; border-top: 1px solid #1e293b; margin: 20px 0;'>
            <p style='color: #64748b; font-size: 12px; text-align: center; margin: 0;'>
                ONYXPLAY &bull; Premium Consoles, VR & Gaming Gear Rentals
            </p>
        </div>
    </div>
    ";

    $alt_body = "ONYXPLAY - Booking Confirmation\n\n"
              . "Hello $user_name,\n\n"
              . "Thank you for your order! Your ONYXPLAY booking #$booking_code is confirmed.\n\n"
              . "Booking ID: $booking_code\n"
              . "Order Date: $order_date\n"
              . "Delivery Date: $delivery_date\n"
              . "Return Date: $return_date\n"
              . "Duration: $total_days days\n"
              . "Delivery Address: $delivery_address, $city\n\n"
              . "Items Booked:\n"
              . $items_text . "\n"
              . "Total Amount: Rs. $total_amount\n\n"
              . "Our delivery team will contact you at $user_phone prior to delivery.\n"
              . "ONYXPLAY Platform\n";

    // Send email ONLY to the customer/user
    try {
        $mail = getMailer();
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Add ONLY the user/customer address
        $mail->addAddress($user_email, $user_name);

        $mail->isHTML(true);
        $mail->Subject = "Your ONYXPLAY Booking Confirmation - #" . $booking_code;
        $mail->Body = $html_body;
        $mail->AltBody = $alt_body;

        $sent = $mail->send();

        // Audit log
        $log_entry = date('Y-m-d H:i:s') . " | SUCCESS | Order Confirmation sent ONLY to user: $user_email for Booking #$booking_code (Total: Rs. $total_amount)\n";
        @file_put_contents(__DIR__ . '/order_email_log.txt', $log_entry, FILE_APPEND);

        return [
            'success' => true,
            'message' => 'Order confirmation email sent successfully to customer.',
            'email' => $user_email
        ];
    } catch (Exception $e) {
        $error_msg = $e->getMessage();
        $log_entry = date('Y-m-d H:i:s') . " | ERROR | Failed sending to user $user_email for Booking #$booking_code: $error_msg\n";
        @file_put_contents(__DIR__ . '/order_email_log.txt', $log_entry, FILE_APPEND);

        return [
            'success' => false,
            'message' => 'Failed to send confirmation email: ' . $error_msg,
            'email' => $user_email
        ];
    }
}

/**
 * Send Order Status Update Email ONLY to the customer
 * 
 * @param int $booking_id Numeric ID in bookings table
 * @param string $status New status
 * @param mysqli $conn
 * @return array
 */
function sendOrderStatusUpdateEmail($booking_id, $status, $conn) {
    $booking_id = (int)$booking_id;
    if ($booking_id <= 0) return ['success' => false, 'message' => 'Invalid booking ID'];

    $stmt = mysqli_prepare($conn, "SELECT b.*, u.name as user_name, u.email as user_email 
                                   FROM bookings b 
                                   JOIN users u ON b.user_id = u.id 
                                   WHERE b.id = ?");
    mysqli_stmt_bind_param($stmt, "i", $booking_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $booking = mysqli_fetch_assoc($res);

    if (!$booking) return ['success' => false, 'message' => 'Booking not found'];

    $booking_code = $booking['booking_id'];
    $user_name = $booking['user_name'];
    $user_email = $booking['user_email'];

    $status_colors = [
        'Confirmed' => '#3b82f6',
        'Dispatched' => '#8b5cf6',
        'Delivered' => '#10b981',
        'Returned' => '#06b6d4',
        'Cancelled' => '#ef4444'
    ];
    $badge_color = $status_colors[$status] ?? '#6366f1';

    $html = "
    <div style='max-width: 580px; margin: 0 auto; font-family: Arial, sans-serif; background: #0b0e17; border: 1px solid #1e293b; border-radius: 14px; overflow: hidden; color: #f1f5f9; box-shadow: 0 12px 35px rgba(0,0,0,0.6);'>
        <!-- Top Dual-Tone Highlight Bar -->
        <div style='height: 4px; background: linear-gradient(90deg, #00f0ff 0%, #b026ff 100%);'></div>

        <div style='padding: 28px 25px;'>
            <div style='text-align: center; margin-bottom: 20px;'>
                <div style='font-size: 26px; font-weight: 800; letter-spacing: 2px; line-height: 1.1;'>
                    <span style='color: #cbd5e1;'>ONYX</span><span style='color: #00f0ff;'>PLAY</span>
                </div>
                <div style='color: #a855f7; font-size: 13px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-top: 4px;'>Next-Gen Game Rentals</div>
            </div>

            <h2 style='color: #00d2ff; margin-top: 0; font-size: 20px; font-weight: 700;'>Order Status Update</h2>
            <p style='color: #cbd5e1; font-size: 14px;'>Hello <strong style='color: #f1f5f9;'>" . htmlspecialchars($user_name) . "</strong>,</p>
            <p style='color: #94a3b8; font-size: 14px;'>The status of your ONYXPLAY booking <strong style='color: #00f0ff; font-family: monospace;'>#$booking_code</strong> has been updated to:</p>
            
            <div style='display: inline-block; background: $badge_color; color: #ffffff; font-weight: 800; font-size: 15px; padding: 7px 20px; border-radius: 20px; margin: 12px 0;'>
                " . htmlspecialchars($status) . "
            </div>

            <p style='color: #94a3b8; font-size: 14px; margin-top: 15px; line-height: 1.5;'>
                You can review real-time tracking and delivery updates anytime directly in your customer dashboard under <strong style='color: #f1f5f9;'>My Bookings</strong>.
            </p>
            <hr style='border: none; border-top: 1px solid #1e293b; margin: 20px 0;'>
            <p style='color: #64748b; font-size: 12px; margin: 0; text-align: center;'>ONYXPLAY &bull; Need assistance? Contact support</p>
        </div>
    </div>
    ";

    try {
        $mail = getMailer();
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->addAddress($user_email, $user_name);
        $mail->isHTML(true);
        $mail->Subject = "Order Status Update: #" . $booking_code . " is now " . $status . " - ONYXPLAY";
        $mail->Body = $html;
        $mail->AltBody = "Hello $user_name,\n\nYour ONYXPLAY order #$booking_code is now: $status.\n\nTrack order in your account.\n";
        $mail->send();

        @file_put_contents(__DIR__ . '/order_email_log.txt', date('Y-m-d H:i:s') . " | STATUS | Sent status '$status' ONLY to user $user_email for #$booking_code\n", FILE_APPEND);
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
