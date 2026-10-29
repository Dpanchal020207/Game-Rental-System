<?php
require 'db.php';
require 'header.php';

$search = $_GET['search'] ?? '';

// Category state management: remember chosen category across page views
if (isset($_GET['category'])) {
    if ($_GET['category'] === 'all' || $_GET['category'] === '') {
        $category = '';
        $_SESSION['last_category'] = '';
    } else {
        $category = $_GET['category'];
        $_SESSION['last_category'] = $category;
    }
} elseif (isset($_GET['reset'])) {
    $category = '';
    $_SESSION['last_category'] = '';
} elseif (!empty($_SESSION['last_category'])) {
    $category = $_SESSION['last_category'];
} else {
    $category = '';
}

// Category metadata for icons & badges
$cat_meta = [
    'game' => ['icon' => '💿', 'label' => 'Games'],
    'console' => ['icon' => '🎮', 'label' => 'Consoles'],
    'accessories' => ['icon' => '🎧', 'label' => 'Accessories'],
    'digital account' => ['icon' => '🔑', 'label' => 'Digital Accounts'],
    'subscription' => ['icon' => '⚡', 'label' => 'Subscriptions'],
    'VR Headsets' => ['icon' => '🥽', 'label' => 'VR Headsets'],
    'Controllers' => ['icon' => '🕹️', 'label' => 'Controllers'],
    'Racing Wheels' => ['icon' => '🏎️', 'label' => 'Racing Wheels']
];

// Fetch categories for dropdown & tabs
$cat_result = mysqli_query($conn, "SELECT c.id, c.name, COUNT(g.id) as product_count FROM categories c LEFT JOIN gadgets g ON c.id = g.category_id GROUP BY c.id ORDER BY c.id ASC");
$categories = [];
$total_products_count = 0;
if ($cat_result) {
    while ($row = mysqli_fetch_assoc($cat_result)) {
        $categories[] = $row;
        $total_products_count += (int)$row['product_count'];
    }
}

// Fetch gadgets with hardware unit status counts
$query = "SELECT g.*, c.name as cat_name, 
          SUM(CASE WHEN h.status = 'Available' THEN 1 ELSE 0 END) as available_count,
          SUM(CASE WHEN h.status = 'Rented' THEN 1 ELSE 0 END) as rented_count,
          SUM(CASE WHEN h.status = 'Under Maintenance' THEN 1 ELSE 0 END) as maintenance_count
          FROM gadgets g 
          LEFT JOIN categories c ON g.category_id = c.id 
          LEFT JOIN hardware_units h ON g.id = h.gadget_id
          WHERE 1=1";

if ($search) {
    $search_safe = mysqli_real_escape_string($conn, $search);
    $query .= " AND (g.title LIKE '%$search_safe%' OR g.description LIKE '%$search_safe%')";
}
if ($category) {
    $category_safe = mysqli_real_escape_string($conn, $category);
    $query .= " AND g.category_id = '$category_safe'";
}

$query .= " GROUP BY g.id ORDER BY g.id ASC";

$gadget_result = mysqli_query($conn, $query);
$gadgets = [];
if ($gadget_result) {
    while ($row = mysqli_fetch_assoc($gadget_result)) {
        $gadgets[] = $row;
    }
}

// Fetch gallery images
$gallery_map = [];
$gimg_res = mysqli_query($conn, "SELECT gadget_id, image FROM gadget_images ORDER BY id ASC");
if ($gimg_res) {
    while ($row = mysqli_fetch_assoc($gimg_res)) {
        $gallery_map[$row['gadget_id']][] = $row['image'];
    }
}

// Build image list for cards
foreach ($gadgets as &$g) {
    $imgs = [];
    if (!empty($g['image'])) {
        $imgs[] = $g['image'];
    }
    if (!empty($gallery_map[$g['id']])) {
        foreach ($gallery_map[$g['id']] as $ext_img) {
            if (!in_array($ext_img, $imgs)) {
                $imgs[] = $ext_img;
            }
        }
    }
    $g['all_images'] = $imgs;
}
unset($g);
?>

<div id="catalog" style="padding-top: 10px;">
    <!-- Catalog Header -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 12px; margin-bottom: 24px;">
        <div>
            <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(0,240,255,0.08); border: 1px solid rgba(0,240,255,0.25); border-radius: 20px; padding: 4px 14px; font-size: 0.8rem; font-weight: 700; color: #00f0ff; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 8px;">
                <span>⚡</span> Certified Gaming Hardware & Discs
            </div>
            <h1 style="font-family: 'Rajdhani', sans-serif; font-size: 2.3rem; margin: 0 0 6px 0; color: #fff; letter-spacing: 0.02em;">
                Rental Gear Catalog
            </h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem;">
                Explore verified next-gen consoles, AAA games, VR rigs, and pro controllers available for doorstep delivery.
            </p>
        </div>
        <?php if (!empty($category) || !empty($search)): ?>
            <a href="catalog.php?category=all" class="btn" style="background: transparent; color: #dc3545; border: 1.5px solid #dc3545; padding: 8px 18px; border-radius: 20px; font-size: 0.85rem; text-decoration: none; font-weight: 700; transition: all 0.2s ease;">
                ✕ Clear All Filters
            </a>
        <?php endif; ?>
    </div>

    <!-- Horizontal Category Quick Tabs -->
    <div class="sp-quick-tabs">
        <a href="catalog.php?category=all" class="sp-tab-pill <?php echo (empty($category) || $category === 'all') ? 'active' : ''; ?>">
            <span>🎮</span> All Gear (<?php echo $total_products_count; ?>)
        </a>
        <?php foreach ($categories as $cat): 
            $meta = $cat_meta[$cat['name']] ?? ['icon' => '📦'];
            $is_active = ($category == $cat['id']);
        ?>
            <a href="catalog.php?category=<?php echo $cat['id']; ?>" class="sp-tab-pill <?php echo $is_active ? 'active' : ''; ?>">
                <span><?php echo $meta['icon']; ?></span> 
                <?php echo htmlspecialchars(ucfirst($cat['name'])); ?> (<?php echo $cat['product_count']; ?>)
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Search & Category Filter Form -->
    <form method="GET" action="catalog.php" style="margin-bottom: 24px; display: flex; gap: 10px; flex-wrap: wrap;">
        <input 
            type="text" 
            name="search" 
            placeholder="Search gadgets by name or model (e.g. PS5, Quest, GTA VI, Logitech)..." 
            value="<?php echo htmlspecialchars($search); ?>" 
            style="flex: 2; min-width: 250px; margin: 0;"
        >
        <select name="category" style="flex: 1; min-width: 180px; margin: 0;" onchange="this.form.submit()">
            <option value="">All Categories (<?php echo $total_products_count; ?> Products)</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(ucfirst($cat['name'])); ?> (<?php echo $cat['product_count']; ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn" style="flex: 0.4; min-width: 110px;">Search Gear</button>
    </form>

    <!-- Active Category Banner -->
    <?php if (!empty($category)): 
        $active_cat_name = '';
        foreach ($categories as $cat) {
            if ($cat['id'] == $category) {
                $active_cat_name = ucfirst($cat['name']);
                break;
            }
        }
    ?>
        <div style="margin-bottom: 22px; padding: 12px 18px; background: var(--table-header-bg); border-radius: 10px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <span style="font-size: 0.95rem; color: var(--text-color);">
                📁 Filtered by Category: <strong style="color: #00d2ff;"><?php echo htmlspecialchars($active_cat_name); ?></strong>
                <span style="color: var(--text-muted); font-size: 0.85rem;">(<?php echo count($gadgets); ?> items)</span>
            </span>
            <a href="catalog.php?category=all" style="font-size: 0.85rem; color: #dc3545; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                ✕ Clear Category Filter (View All)
            </a>
        </div>
    <?php endif; ?>

    <!-- Product Grid -->
    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
        <?php if (count($gadgets) > 0): ?>
            <?php foreach ($gadgets as $g): 
                $card_imgs = $g['all_images'];
                $details_url = "gadget_details.php?id=" . $g['id'] . (!empty($category) ? "&category=" . urlencode($category) : "");
            ?>
                <div class="card" id="gadget-card-<?php echo $g['id']; ?>">
                    <!-- Clickable Image Thumbnail -> Directly navigates to gadget_details.php preserving category -->
                    <a href="<?php echo htmlspecialchars($details_url); ?>" class="card-img-wrapper" style="text-decoration: none; display: block;" title="Click to view <?php echo htmlspecialchars($g['title']); ?> details & images">
                        <?php if (!empty($card_imgs)): ?>
                            <img src="uploads/<?php echo htmlspecialchars($card_imgs[0]); ?>" alt="<?php echo htmlspecialchars($g['title']); ?>">
                            <?php if (count($card_imgs) > 1): ?>
                                <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.75); color: #fff; font-size: 0.75rem; padding: 2px 8px; border-radius: 6px; font-weight: 700; pointer-events: none;">
                                    📷 <?php echo count($card_imgs); ?> Photos
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="background:var(--input-bg); height: 180px; display:flex; align-items:center; justify-content:center; color: var(--text-muted); font-size: 0.9rem;">No Image</div>
                        <?php endif; ?>
                    </a>

                    <!-- Clickable Title -> Directly navigates to gadget_details.php preserving category -->
                    <a href="<?php echo htmlspecialchars($details_url); ?>" class="gadget-clickable" title="Click to view details & images">
                        <h3><?php echo htmlspecialchars($g['title']); ?></h3>
                    </a>

                    <p>Category: <strong><?php echo htmlspecialchars(ucfirst($g['cat_name'])); ?></strong></p>
                    <p>Price: <span class="price-tag">₹<?php echo htmlspecialchars(number_format($g['daily_price'], 2)); ?></span> / day</p>

                    <!-- Description preview snippet -->
                    <?php if (!empty($g['description'])): ?>
                        <a href="<?php echo htmlspecialchars($details_url); ?>" style="text-decoration: none; color: inherit;" title="Click to view full description">
                            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 6px 0 10px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.45;">
                                <?php echo htmlspecialchars($g['description']); ?>
                            </p>
                        </a>
                    <?php endif; ?>

                    <!-- Prominent button directly to product details page preserving category -->
                    <a href="<?php echo htmlspecialchars($details_url); ?>" class="btn-view-details">
                        View Details & Images &rarr;
                    </a>

                    <!-- Real-Time Hardware Status Counters -->
                    <div style="font-size: 0.85rem; margin-bottom: 12px; padding: 10px; background: var(--table-header-bg); border-radius: 8px; border: 1px solid var(--border-color);">
                        <strong style="color: var(--text-color);">Hardware Status:</strong><br>
                        <span style="color: #28a745;">&#9679; Available: <?php echo $g['available_count'] ? $g['available_count'] : 0; ?></span><br>
                        <span style="color: #007bff;">&#9679; Rented: <?php echo $g['rented_count'] ? $g['rented_count'] : 0; ?></span><br>
                        <span style="color: #dc3545;">&#9679; Under Maintenance: <?php echo $g['maintenance_count'] ? $g['maintenance_count'] : 0; ?></span>
                    </div>

                    <!-- Direct Add to Cart Form (AJAX Enabled via footer.php) -->
                    <?php if ($g['available_count'] > 0): ?>
                        <form action="cart_action.php" method="POST" style="margin-top: 10px;">
                            <input type="hidden" name="gadget_id" value="<?php echo $g['id']; ?>">
                            <label style="font-size: 0.85rem;">Delivery Date:</label>
                            <input type="date" name="delivery_date" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" style="margin-bottom: 8px;">
                            <label style="font-size: 0.85rem;">Return Date:</label>
                            <input type="date" name="return_date" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" style="margin-bottom: 8px;">
                            <button type="submit" name="add_to_cart" class="btn" style="width: 100%;">Add to Cart</button>
                        </form>
                    <?php else: ?>
                        <button class="btn" disabled style="width: 100%; margin-top: 10px; background: #94a3b8; cursor: not-allowed;">Out of Stock</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; width: 100%; padding: 50px 20px; background: var(--input-bg); border-radius: 12px; border: 1px dashed var(--border-color); margin: 20px 0;">
                <h3 style="margin-bottom: 10px;">No gadgets found matching your criteria.</h3>
                <p style="color: var(--text-muted); margin-bottom: 18px;">Try clearing your search query or choosing another category.</p>
                <a href="catalog.php?category=all" class="btn" style="display: inline-block;">View All <?php echo $total_products_count; ?> Products</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require 'footer.php'; ?>
