<?php
require_once 'db.php';
if (!isAdmin()) redirect('index.php');

// 1. Overall Status Breakdown & Net Revenue
$status_sql = "SELECT 
    SUM(CASE WHEN status IN ('Delivered', 'Returned') THEN total_amount ELSE 0 END) as realized_revenue,
    COUNT(CASE WHEN status IN ('Delivered', 'Returned') THEN 1 END) as realized_count,
    SUM(CASE WHEN status IN ('Confirmed', 'Dispatched') THEN total_amount ELSE 0 END) as inprogress_revenue,
    COUNT(CASE WHEN status IN ('Confirmed', 'Dispatched') THEN 1 END) as inprogress_count,
    SUM(CASE WHEN status = 'Pending' THEN total_amount ELSE 0 END) as pending_revenue,
    COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_count,
    SUM(CASE WHEN status = 'Cancelled' THEN total_amount ELSE 0 END) as cancelled_revenue,
    COUNT(CASE WHEN status = 'Cancelled' THEN 1 END) as cancelled_count,
    SUM(CASE WHEN status != 'Cancelled' THEN total_amount ELSE 0 END) as total_net_revenue,
    COUNT(CASE WHEN status != 'Cancelled' THEN 1 END) as total_valid_orders,
    SUM(total_amount) as gross_volume,
    COUNT(*) as total_all_orders
FROM bookings";
$status_res = mysqli_query($conn, $status_sql);
$sb = mysqli_fetch_assoc($status_res);

$realized_rev = (float)($sb['realized_revenue'] ?? 0);
$realized_cnt = (int)($sb['realized_count'] ?? 0);
$inprogress_rev = (float)($sb['inprogress_revenue'] ?? 0);
$inprogress_cnt = (int)($sb['inprogress_count'] ?? 0);
$pending_rev = (float)($sb['pending_revenue'] ?? 0);
$pending_cnt = (int)($sb['pending_count'] ?? 0);
$cancelled_rev = (float)($sb['cancelled_revenue'] ?? 0);
$cancelled_cnt = (int)($sb['cancelled_count'] ?? 0);
$total_net_rev = (float)($sb['total_net_revenue'] ?? 0);
$total_valid_orders = (int)($sb['total_valid_orders'] ?? 0);
$gross_volume = (float)($sb['gross_volume'] ?? 0);
$total_all_orders = (int)($sb['total_all_orders'] ?? 0);

$aov = $total_valid_orders > 0 ? ($total_net_rev / $total_valid_orders) : 0;

// Percentage calculations for distribution bar
$pct_realized = $gross_volume > 0 ? round(($realized_rev / $gross_volume) * 100, 1) : 0;
$pct_inprogress = $gross_volume > 0 ? round(($inprogress_rev / $gross_volume) * 100, 1) : 0;
$pct_pending = $gross_volume > 0 ? round(($pending_rev / $gross_volume) * 100, 1) : 0;
$pct_cancelled = $gross_volume > 0 ? round(($cancelled_rev / $gross_volume) * 100, 1) : 0;

// 2. Time Periods Revenue
$time_sql = "SELECT
    SUM(CASE WHEN DATE(created_at) = CURDATE() AND status != 'Cancelled' THEN total_amount ELSE 0 END) as today_revenue,
    COUNT(CASE WHEN DATE(created_at) = CURDATE() AND status != 'Cancelled' THEN 1 END) as today_orders,
    SUM(CASE WHEN YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) AND status != 'Cancelled' THEN total_amount ELSE 0 END) as week_revenue,
    COUNT(CASE WHEN YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) AND status != 'Cancelled' THEN 1 END) as week_orders,
    SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) AND status != 'Cancelled' THEN total_amount ELSE 0 END) as month_revenue,
    COUNT(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) AND status != 'Cancelled' THEN 1 END) as month_orders
FROM bookings";
$time_res = mysqli_query($conn, $time_sql);
$tb = mysqli_fetch_assoc($time_res);

$today_rev = (float)($tb['today_revenue'] ?? 0);
$today_orders = (int)($tb['today_orders'] ?? 0);
$week_rev = (float)($tb['week_revenue'] ?? 0);
$week_orders = (int)($tb['week_orders'] ?? 0);
$month_rev = (float)($tb['month_revenue'] ?? 0);
$month_orders = (int)($tb['month_orders'] ?? 0);

// 3. Total Items Rented (active / dispatched units)
$items_res = mysqli_query($conn, "SELECT COUNT(*) as total_items FROM booking_items bi JOIN bookings b ON bi.booking_id = b.id WHERE b.status != 'Cancelled'");
$items_row = mysqli_fetch_assoc($items_res);
$total_items = $items_row['total_items'] ? (int)$items_row['total_items'] : 0;

// 4. Category Revenue Breakdown
$cat_sql = "SELECT 
    c.id as category_id,
    c.name as category_name,
    COUNT(DISTINCT bi.id) as units_rented,
    COALESCE(SUM(bi.price_per_day * b.total_days), 0) as category_revenue
FROM categories c
LEFT JOIN gadgets g ON g.category_id = c.id
LEFT JOIN hardware_units hu ON hu.gadget_id = g.id
LEFT JOIN booking_items bi ON bi.hardware_unit_id = hu.id
LEFT JOIN bookings b ON bi.booking_id = b.id AND b.status != 'Cancelled'
GROUP BY c.id, c.name
ORDER BY category_revenue DESC, units_rented DESC";
$cat_res = mysqli_query($conn, $cat_sql);
$categories_rev = [];
if ($cat_res) {
    while ($crow = mysqli_fetch_assoc($cat_res)) {
        $categories_rev[] = $crow;
    }
}

// 5. Monthly Revenue History Breakdown
$history_res = mysqli_query($conn, "SELECT 
                                        DATE_FORMAT(created_at, '%M %Y') as month_name,
                                        YEAR(created_at) as yr,
                                        MONTH(created_at) as mo,
                                        COUNT(*) as total_orders,
                                        SUM(CASE WHEN status != 'Cancelled' THEN total_amount ELSE 0 END) as net_revenue,
                                        SUM(CASE WHEN status IN ('Delivered', 'Returned', 'Confirmed') THEN 1 ELSE 0 END) as completed_orders,
                                        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_count
                                    FROM bookings 
                                    GROUP BY YEAR(created_at), MONTH(created_at) 
                                    ORDER BY YEAR(created_at) DESC, MONTH(created_at) DESC");
$revenue_history = [];
if ($history_res) {
    while ($row = mysqli_fetch_assoc($history_res)) {
        $revenue_history[] = $row;
    }
}

// 6. Recent & All Customer Bookings
$res = mysqli_query($conn, "SELECT b.*, u.name as user_name, u.email as user_email FROM bookings b JOIN users u ON b.user_id = u.id ORDER BY b.created_at DESC");
$bookings = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $bookings[] = $row;
    }
}

require 'header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 25px; gap: 15px;">
    <div>
        <h2 style="margin: 0;">Admin Dashboard & Revenue Analytics</h2>
        <p style="margin: 5px 0 0; color: var(--text-muted); font-size: 0.95rem;">Comprehensive financial breakdown, status metrics, and gear rentals</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="#status-revenue" class="btn" style="padding: 8px 14px; font-size: 0.85rem; background: var(--input-bg); color: var(--text-color); border: 1px solid var(--border-color); box-shadow: none;">🏷️ Revenue by Status</a>
        <a href="#time-revenue" class="btn" style="padding: 8px 14px; font-size: 0.85rem; background: var(--input-bg); color: var(--text-color); border: 1px solid var(--border-color); box-shadow: none;">⏱️ Time Periods</a>
        <a href="#category-revenue" class="btn" style="padding: 8px 14px; font-size: 0.85rem; background: var(--input-bg); color: var(--text-color); border: 1px solid var(--border-color); box-shadow: none;">🎮 By Category</a>
        <a href="#monthly-history" class="btn" style="padding: 8px 14px; font-size: 0.85rem; background: var(--input-bg); color: var(--text-color); border: 1px solid var(--border-color); box-shadow: none;">📈 Monthly History</a>
    </div>
</div>

<!-- TOP EXECUTIVE STAT CARDS -->
<div class="stat-grid">
    <div class="stat-card" style="border-left: 4px solid #10b981;">
        <div class="stat-title">💰 Lifetime Net Revenue</div>
        <div class="stat-val" style="color: #10b981;">₹<?php echo number_format($total_net_rev, 2); ?></div>
        <div class="stat-desc">From <?php echo $total_valid_orders; ?> active/completed orders</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #00d2ff;">
        <div class="stat-title">💎 Average Order Value (AOV)</div>
        <div class="stat-val" style="color: #00d2ff;">₹<?php echo number_format($aov, 2); ?></div>
        <div class="stat-desc">Average earnings per booking</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
        <div class="stat-title">🎮 Units Rented Out</div>
        <div class="stat-val" style="color: #8b5cf6;"><?php echo $total_items; ?></div>
        <div class="stat-desc">Hardware units dispatched</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #3b82f6;">
        <div class="stat-title">📦 Total Bookings Volume</div>
        <div class="stat-val" style="color: #3b82f6;"><?php echo $total_all_orders; ?></div>
        <div class="stat-desc">Gross volume: ₹<?php echo number_format($gross_volume, 2); ?></div>
    </div>
</div>

<!-- 1. REVENUE BY ORDER STATUS -->
<h3 id="status-revenue" style="display: flex; align-items: center; gap: 8px; margin-top: 40px;">
    <span>🏷️</span> Revenue by Order Status
</h3>
<p style="color: var(--text-muted); margin-top: -8px; font-size: 0.9rem;">Categorization of earnings across Realized, In-Progress, Pending, and Lost Revenue</p>

<div class="stat-grid">
    <!-- Realized / Completed Revenue -->
    <div class="stat-card" style="border-top: 3px solid #10b981;">
        <div class="stat-title" style="color: #10b981;">✅ Realized / Earned Revenue</div>
        <div class="stat-val" style="color: #10b981;">₹<?php echo number_format($realized_rev, 2); ?></div>
        <div class="stat-desc">
            <span class="badge badge-success"><?php echo $realized_cnt; ?> Orders</span>
            <span style="margin-left: 6px;">Delivered & Returned</span>
        </div>
    </div>

    <!-- Active In-Progress Revenue -->
    <div class="stat-card" style="border-top: 3px solid #3b82f6;">
        <div class="stat-title" style="color: #3b82f6;">🚚 Active / In-Progress Revenue</div>
        <div class="stat-val" style="color: #3b82f6;">₹<?php echo number_format($inprogress_rev, 2); ?></div>
        <div class="stat-desc">
            <span class="badge badge-info"><?php echo $inprogress_cnt; ?> Orders</span>
            <span style="margin-left: 6px;">Confirmed & Dispatched</span>
        </div>
    </div>

    <!-- Pending Revenue -->
    <div class="stat-card" style="border-top: 3px solid #f59e0b;">
        <div class="stat-title" style="color: #f59e0b;">⏳ Pending / Awaiting Revenue</div>
        <div class="stat-val" style="color: #f59e0b;">₹<?php echo number_format($pending_rev, 2); ?></div>
        <div class="stat-desc">
            <span class="badge badge-warning"><?php echo $pending_cnt; ?> Orders</span>
            <span style="margin-left: 6px;">Awaiting verification</span>
        </div>
    </div>

    <!-- Cancelled / Lost Revenue -->
    <div class="stat-card" style="border-top: 3px solid #ef4444;">
        <div class="stat-title" style="color: #ef4444;">❌ Cancelled / Lost Revenue</div>
        <div class="stat-val" style="color: #ef4444;">₹<?php echo number_format($cancelled_rev, 2); ?></div>
        <div class="stat-desc">
            <span class="badge badge-danger"><?php echo $cancelled_cnt; ?> Orders</span>
            <span style="margin-left: 6px;">Lost booking value</span>
        </div>
    </div>
</div>

<!-- Revenue Distribution Visual Bar -->
<div class="revenue-bar-container">
    <div class="revenue-bar-header">
        <span>Overall Revenue Status Distribution (% of Gross ₹<?php echo number_format($gross_volume, 2); ?>)</span>
        <span style="color: var(--text-muted); font-size: 0.85rem;">Status Ratio</span>
    </div>
    <div class="revenue-bar-track">
        <?php if ($gross_volume > 0): ?>
            <?php if ($pct_realized > 0): ?>
                <div class="revenue-bar-segment" style="width: <?php echo $pct_realized; ?>%; background: #10b981;" title="Realized: <?php echo $pct_realized; ?>%"></div>
            <?php endif; ?>
            <?php if ($pct_inprogress > 0): ?>
                <div class="revenue-bar-segment" style="width: <?php echo $pct_inprogress; ?>%; background: #3b82f6;" title="In-Progress: <?php echo $pct_inprogress; ?>%"></div>
            <?php endif; ?>
            <?php if ($pct_pending > 0): ?>
                <div class="revenue-bar-segment" style="width: <?php echo $pct_pending; ?>%; background: #f59e0b;" title="Pending: <?php echo $pct_pending; ?>%"></div>
            <?php endif; ?>
            <?php if ($pct_cancelled > 0): ?>
                <div class="revenue-bar-segment" style="width: <?php echo $pct_cancelled; ?>%; background: #ef4444;" title="Cancelled: <?php echo $pct_cancelled; ?>%"></div>
            <?php endif; ?>
        <?php else: ?>
            <div class="revenue-bar-segment" style="width: 100%; background: rgba(148, 163, 184, 0.3);" title="No bookings recorded yet"></div>
        <?php endif; ?>
    </div>
    <div class="revenue-bar-legend">
        <span class="legend-item"><span class="legend-dot" style="background: #10b981;"></span> Realized (<?php echo $pct_realized; ?>%)</span>
        <span class="legend-item"><span class="legend-dot" style="background: #3b82f6;"></span> In-Progress (<?php echo $pct_inprogress; ?>%)</span>
        <span class="legend-item"><span class="legend-dot" style="background: #f59e0b;"></span> Pending (<?php echo $pct_pending; ?>%)</span>
        <span class="legend-item"><span class="legend-dot" style="background: #ef4444;"></span> Cancelled (<?php echo $pct_cancelled; ?>%)</span>
    </div>
</div>

<!-- 2. REVENUE BY TIME PERIOD -->
<h3 id="time-revenue" style="display: flex; align-items: center; gap: 8px; margin-top: 40px;">
    <span>⏱️</span> Revenue by Time Periods
</h3>
<p style="color: var(--text-muted); margin-top: -8px; font-size: 0.9rem;">Track performance across daily, weekly, monthly, and lifetime periods</p>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-title">🌅 Today's Revenue</div>
        <div class="stat-val" style="color: var(--text-color);">₹<?php echo number_format($today_rev, 2); ?></div>
        <div class="stat-desc"><?php echo $today_orders; ?> orders placed today (<?php echo date('d M Y'); ?>)</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">📆 This Week's Revenue</div>
        <div class="stat-val" style="color: var(--text-color);">₹<?php echo number_format($week_rev, 2); ?></div>
        <div class="stat-desc"><?php echo $week_orders; ?> orders this calendar week</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">🗓️ This Month's Revenue</div>
        <div class="stat-val" style="color: #3b82f6;">₹<?php echo number_format($month_rev, 2); ?></div>
        <div class="stat-desc"><?php echo $month_orders; ?> orders in <?php echo date('F Y'); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-title">🏆 All-Time Net Earnings</div>
        <div class="stat-val" style="color: #10b981;">₹<?php echo number_format($total_net_rev, 2); ?></div>
        <div class="stat-desc">Cumulative revenue since launch</div>
    </div>
</div>

<!-- 3. REVENUE BY CATEGORY BREAKDOWN -->
<h3 id="category-revenue" style="display: flex; align-items: center; gap: 8px; margin-top: 40px;">
    <span>🎮</span> Revenue by Category & Gear Type
</h3>
<p style="color: var(--text-muted); margin-top: -8px; font-size: 0.9rem;">Analyze which gaming equipment categories produce the highest revenue</p>

<div class="table-responsive" style="margin-bottom: 40px;">
    <table>
        <tr>
            <th>Category</th>
            <th>Units Rented</th>
            <th>Revenue Generated</th>
            <th>Revenue Share (%)</th>
        </tr>
        <?php 
        $has_cat_sales = false;
        foreach ($categories_rev as $cat): 
            $cat_amount = (float)$cat['category_revenue'];
            if ($cat_amount > 0) $has_cat_sales = true;
            $cat_share = $total_net_rev > 0 ? min(100, round(($cat_amount / $total_net_rev) * 100, 1)) : 0;
        ?>
        <tr>
            <td>
                <strong><?php echo htmlspecialchars(ucwords($cat['category_name'])); ?></strong>
            </td>
            <td>
                <span class="badge badge-secondary"><?php echo (int)$cat['units_rented']; ?> Units</span>
            </td>
            <td>
                <strong style="color: #10b981; font-size: 1.05rem;">₹<?php echo number_format($cat_amount, 2); ?></strong>
            </td>
            <td>
                <div class="cat-progress-track">
                    <div class="cat-progress-fill" style="width: <?php echo $cat_share; ?>%;"></div>
                </div>
                <span style="font-size: 0.88rem; font-weight: 600;"><?php echo $cat_share; ?>%</span>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- 4. MONTHLY REVENUE HISTORY -->
<h3 id="monthly-history" style="display: flex; align-items: center; gap: 8px; margin-top: 40px;">
    <span>📈</span> Monthly Revenue History
</h3>
<p style="color: var(--text-muted); margin-top: -8px; font-size: 0.9rem;">Chronological month-over-month rental performance and financial growth</p>

<?php if (empty($revenue_history)): ?>
    <p style="color: var(--text-muted); margin-bottom: 30px;">No revenue history recorded yet. As customers book gear, monthly revenue will appear here.</p>
<?php else: ?>
    <div class="table-responsive" style="margin-bottom: 40px;">
        <table>
            <tr>
                <th>Period (Month / Year)</th>
                <th>Total Orders</th>
                <th>Successful / Active Orders</th>
                <th>Cancelled Orders</th>
                <th>Net Revenue Generated</th>
            </tr>
            <?php foreach ($revenue_history as $rh): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($rh['month_name']); ?></strong></td>
                <td><?php echo (int)$rh['total_orders']; ?> Orders</td>
                <td><span class="badge badge-success"><?php echo (int)$rh['completed_orders']; ?> Active</span></td>
                <td>
                    <?php if ($rh['cancelled_count'] > 0): ?>
                        <span class="badge badge-danger"><?php echo (int)$rh['cancelled_count']; ?> Cancelled</span>
                    <?php else: ?>
                        <span style="color: var(--text-muted); font-size: 0.85rem;">0</span>
                    <?php endif; ?>
                </td>
                <td><strong style="color: #10b981; font-size: 1.05rem;">₹<?php echo number_format($rh['net_revenue'], 2); ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>

<!-- 5. ALL CUSTOMER BOOKINGS & ORDERS -->
<h3 style="display: flex; align-items: center; gap: 8px; margin-top: 40px;">
    <span>📦</span> All Customer Bookings & Orders
</h3>
<p style="color: var(--text-muted); margin-top: -8px; font-size: 0.9rem;">Manage live order lifecycles and dispatch statuses</p>

<?php if (empty($bookings)): ?>
    <p style="color: var(--text-muted);">No bookings found.</p>
<?php else: ?>
    <div class="table-responsive">
        <table>
            <tr>
                <th>Booking ID</th>
                <th>Order Date</th>
                <th>Customer</th>
                <th>Delivery & City</th>
                <th>Rental Dates</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php foreach ($bookings as $b): 
                $st = $b['status'];
                $badge_class = 'badge-pending';
                if ($st === 'Confirmed' || $st === 'Delivered') $badge_class = 'badge-confirmed';
                elseif ($st === 'Cancelled') $badge_class = 'badge-cancelled';
                elseif ($st === 'Dispatched') $badge_class = 'badge-dispatched';
                elseif ($st === 'Returned') $badge_class = 'badge-returned';
            ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($b['booking_id']); ?></strong></td>
                <td>
                    <small style="color: var(--text-muted);">
                        <?php echo date('d M Y, h:i A', strtotime($b['created_at'])); ?>
                    </small>
                </td>
                <td>
                    <strong><?php echo htmlspecialchars($b['user_name']); ?></strong><br>
                    <small style="color: var(--text-muted);"><?php echo htmlspecialchars($b['user_email']); ?></small>
                </td>
                <td>
                    <div style="max-width: 260px; font-size: 0.85rem; line-height: 1.45;">
                        <?php echo htmlspecialchars($b['delivery_address']); ?><br>
                        <span style="display: inline-block; margin-top: 5px; padding: 2px 8px; background: rgba(0, 240, 255, 0.1); border: 1px solid rgba(0, 240, 255, 0.3); border-radius: 4px; font-size: 0.78rem; font-weight: 700; color: #00f0ff;">
                            📍 <?php echo htmlspecialchars($b['city']); ?>
                        </span>
                    </div>
                </td>
                <td>
                    <?php echo htmlspecialchars($b['delivery_date']); ?> to <br>
                    <?php echo htmlspecialchars($b['return_date']); ?>
                    <?php if(!empty($b['total_days'])): ?>
                        <br><small style="color: var(--text-muted);">(<?php echo $b['total_days']; ?> days)</small>
                    <?php endif; ?>
                </td>
                <td>
                    <strong style="color: var(--text-color);">₹<?php echo number_format($b['total_amount'], 2); ?></strong>
                    <br><small style="color: #10b981; font-weight: 600;">💳 <?php echo ($b['payment_method'] ?? 'razorpay') === 'razorpay' ? 'Razorpay' : 'UPI'; ?></small>
                    <?php if(!empty($b['payment_id'])): ?>
                        <br><span style="font-size: 0.72rem; font-family: monospace; color: var(--text-muted);"><?php echo htmlspecialchars($b['payment_id']); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge <?php echo $badge_class; ?>">
                        <?php echo htmlspecialchars($st); ?>
                    </span>
                </td>
                <td>
                    <form action="admin_update_order.php" method="POST" style="margin: 0;">
                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                        <select name="status" onchange="this.form.submit()" style="padding: 6px 10px; margin: 0; min-width: 130px; font-size: 0.85rem;">
                            <option value="">Update Status</option>
                            <option value="Pending" <?php echo $st === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Confirmed" <?php echo $st === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="Dispatched" <?php echo $st === 'Dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                            <option value="Delivered" <?php echo $st === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="Returned" <?php echo $st === 'Returned' ? 'selected' : ''; ?>>Returned</option>
                            <option value="Cancelled" <?php echo $st === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>

<?php require 'footer.php'; ?>
