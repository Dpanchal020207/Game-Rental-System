<?php
require 'db.php';
require 'header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('index.php');
}

// Fetch gadget info with availability counts
$query = "SELECT g.*, c.name as cat_name, 
          SUM(CASE WHEN h.status = 'Available' THEN 1 ELSE 0 END) as available_count,
          SUM(CASE WHEN h.status = 'Rented' THEN 1 ELSE 0 END) as rented_count,
          SUM(CASE WHEN h.status = 'Under Maintenance' THEN 1 ELSE 0 END) as maintenance_count
          FROM gadgets g 
          LEFT JOIN categories c ON g.category_id = c.id 
          LEFT JOIN hardware_units h ON g.id = h.gadget_id
          WHERE g.id = $id
          GROUP BY g.id";

$result = mysqli_query($conn, $query);
$gadget = mysqli_fetch_assoc($result);

if (!$gadget) {
    $return_category = $_GET['category'] ?? $_SESSION['last_category'] ?? '';
    $back_url = 'catalog.php' . (!empty($return_category) ? '?category=' . urlencode($return_category) : '');
    echo "<h2>Gadget Not Found</h2><p>The requested gadget could not be found. <a href='" . htmlspecialchars($back_url) . "'>Return to Catalog</a></p>";
    require 'footer.php';
    exit();
}

// Fetch all gallery images
$gallery_res = mysqli_query($conn, "SELECT image FROM gadget_images WHERE gadget_id = $id ORDER BY id ASC");
$all_images = [];

if (!empty($gadget['image'])) {
    $all_images[] = $gadget['image'];
}
if ($gallery_res) {
    while ($row = mysqli_fetch_assoc($gallery_res)) {
        if (!empty($row['image']) && !in_array($row['image'], $all_images)) {
            $all_images[] = $row['image'];
        }
    }
}
$available_count = (int)($gadget['available_count'] ?? 0);
$daily_price = (float)$gadget['daily_price'];
$total_images = count($all_images);

// Category retention logic: return to the specific category the user was browsing
$return_category = '';
if (isset($_GET['category']) && $_GET['category'] !== '' && $_GET['category'] !== 'all') {
    $return_category = $_GET['category'];
} elseif (!empty($_SESSION['last_category'])) {
    $return_category = $_SESSION['last_category'];
} elseif (!empty($gadget['category_id'])) {
    $return_category = $gadget['category_id'];
}

$back_catalog_url = 'catalog.php' . (!empty($return_category) ? '?category=' . urlencode($return_category) : '');
?>

<style>
/* Embedded slider & layout styles to ensure 100% reliable styling */
.details-page-container {
    max-width: 1180px;
    margin: 0 auto;
}

.details-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.92rem;
    color: var(--text-muted);
    margin-bottom: 25px;
}

.details-breadcrumb a {
    color: #00d2ff;
    text-decoration: none;
    font-weight: 600;
}

.details-breadcrumb a:hover {
    text-decoration: underline;
}

.details-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr);
    gap: 40px;
    align-items: start;
}

.gadget-gallery {
    min-width: 0;
    width: 100%;
}

.swipe-slider-wrapper {
    position: relative;
    width: 100%;
    margin-bottom: 14px;
}

/* Strict slider frame: ONLY 1 photo visible at a time */
.slider-viewport {
    position: relative !important;
    overflow: hidden !important;
    border-radius: 16px !important;
    background: var(--input-bg) !important;
    border: 1px solid var(--border-color) !important;
    width: 100% !important;
    height: 440px !important;
    max-height: 440px !important;
    cursor: grab;
    user-select: none;
    -webkit-user-select: none;
    touch-action: pan-y;
    box-shadow: var(--card-shadow);
}

.slider-viewport.is-dragging {
    cursor: grabbing;
}

.slider-track {
    display: flex !important;
    flex-direction: row !important;
    width: 100% !important;
    height: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    transition: transform 0.35s cubic-bezier(0.25, 1, 0.5, 1);
    will-change: transform;
}

.slider-slide {
    flex: 0 0 100% !important;
    min-width: 100% !important;
    max-width: 100% !important;
    width: 100% !important;
    height: 100% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 16px !important;
    box-sizing: border-box !important;
    overflow: hidden !important;
}

.slider-slide img {
    max-width: 100% !important;
    max-height: 100% !important;
    width: auto !important;
    height: auto !important;
    object-fit: contain !important;
    border-radius: 12px !important;
    pointer-events: none;
    -webkit-user-drag: none;
    display: block;
    margin: auto;
}

.slider-nav-btn {
    position: absolute !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    width: 44px !important;
    height: 44px !important;
    border-radius: 50% !important;
    background: rgba(15, 23, 42, 0.75) !important;
    color: #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.25) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 2rem !important;
    line-height: 1 !important;
    cursor: pointer !important;
    transition: all 0.25s ease !important;
    z-index: 10 !important;
    backdrop-filter: blur(6px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.slider-nav-btn:hover {
    background: #00d2ff !important;
    transform: translateY(-50%) scale(1.1) !important;
}

.slider-nav-btn.prev-btn {
    left: 14px !important;
}

.slider-nav-btn.next-btn {
    right: 14px !important;
}

.slider-badge-counter {
    position: absolute !important;
    top: 14px !important;
    right: 14px !important;
    background: rgba(15, 23, 42, 0.8) !important;
    color: #ffffff !important;
    padding: 5px 14px !important;
    border-radius: 20px !important;
    font-size: 0.85rem !important;
    font-weight: 700 !important;
    z-index: 10 !important;
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    letter-spacing: 0.5px;
}

.slider-swipe-hint {
    position: absolute !important;
    bottom: 12px !important;
    left: 50% !important;
    transform: translateX(-50%) !important;
    background: rgba(15, 23, 42, 0.8) !important;
    color: #cbd5e1 !important;
    padding: 5px 16px !important;
    border-radius: 20px !important;
    font-size: 0.8rem !important;
    font-weight: 600 !important;
    z-index: 10 !important;
    pointer-events: none;
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    gap: 6px;
    transition: opacity 0.4s ease;
}

.slider-pagination-dots {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 12px;
}

.slider-pagination-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--border-color);
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.slider-pagination-dot:hover {
    background: #00d2ff;
}

.slider-pagination-dot.active {
    background: linear-gradient(90deg, #00d2ff, #8b5cf6);
    width: 28px;
    border-radius: 10px;
}

.gadget-info-box {
    display: flex;
    flex-direction: column;
}

.gadget-desc-container {
    margin: 16px 0 20px;
}

.gadget-desc-title {
    font-size: 1rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-muted);
    margin-bottom: 10px;
}

.gadget-desc-text {
    background: var(--input-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 18px 20px;
    font-size: 1rem;
    line-height: 1.7;
    color: var(--text-color);
}

.details-full-description-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 30px;
    margin-top: 40px;
    box-shadow: var(--card-shadow);
}

.details-full-desc-header {
    font-family: 'Rajdhani', sans-serif;
    font-size: 1.55rem;
    font-weight: 700;
    margin-bottom: 18px;
    color: var(--text-color);
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 14px;
}

.details-specs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 20px;
}

.details-spec-item {
    background: var(--input-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 14px 18px;
}

.details-spec-label {
    font-size: 0.82rem;
    text-transform: uppercase;
    font-weight: 700;
    color: var(--text-muted);
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.details-spec-value {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--text-color);
}

.details-rent-calculator {
    background: var(--input-bg);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 22px;
    margin-top: 20px;
}

.details-price-preview {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: var(--card-bg);
    border-radius: 10px;
    border: 1px dashed var(--border-hover);
    margin: 15px 0;
    font-weight: 700;
}

@media (max-width: 768px) {
    .details-grid {
        grid-template-columns: 1fr;
        gap: 25px;
    }
    .slider-viewport {
        height: 290px !important;
        max-height: 290px !important;
    }
    .details-full-description-card {
        padding: 20px;
        margin-top: 25px;
    }
}
</style>

<div class="details-page-container">
    <!-- Breadcrumb Navigation -->
    <div class="details-breadcrumb">
        <a href="<?php echo htmlspecialchars($back_catalog_url); ?>">&larr; Back to Catalog<?php echo !empty($gadget['cat_name']) ? ' (' . htmlspecialchars(ucfirst($gadget['cat_name'])) . ')' : ''; ?></a>
        <span>/</span>
        <a href="catalog.php?category=<?php echo urlencode($gadget['category_id']); ?>" style="text-decoration: none; color: inherit; font-weight: 500;">
            <?php echo htmlspecialchars(ucfirst($gadget['cat_name'] ?? 'Gadgets')); ?>
        </a>
        <span>/</span>
        <strong><?php echo htmlspecialchars($gadget['title']); ?></strong>
    </div>

    <!-- Main Product Layout Grid -->
    <div class="details-grid">
        
        <!-- Left Column: Single Photo Swipeable Gallery (ONLY 1 Photo Visible at a time) -->
        <div class="gadget-gallery">
            <div class="swipe-slider-wrapper">
                <div class="slider-viewport" id="sliderViewport" title="Swipe or drag left/right to view other images">
                    <?php if ($total_images > 0): ?>
                        <div class="slider-track" id="sliderTrack">
                            <?php foreach ($all_images as $index => $img): ?>
                                <div class="slider-slide" data-index="<?php echo $index; ?>">
                                    <img src="uploads/<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($gadget['title']) . ' - Photo ' . ($index + 1); ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($total_images > 1): ?>
                            <!-- Navigation Arrows -->
                            <button type="button" class="slider-nav-btn prev-btn" id="prevSlideBtn" onclick="moveSlide(-1)" aria-label="Previous image">&#8249;</button>
                            <button type="button" class="slider-nav-btn next-btn" id="nextSlideBtn" onclick="moveSlide(1)" aria-label="Next image">&#8250;</button>

                            <!-- Single Slide Counter Badge (e.g. 1 / 3) -->
                            <div class="slider-badge-counter" id="sliderBadgeCounter">
                                📷 1 / <?php echo $total_images; ?>
                            </div>

                            <!-- Swipe Hint (fades out on first swipe) -->
                            <div class="slider-swipe-hint" id="swipeHint">
                                <span>&#128072; Swipe to view next photo &#128073;</span>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="slider-slide" style="display:flex; align-items:center; justify-content:center; color: var(--text-muted);">
                            No Image Available
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($total_images > 1): ?>
                    <!-- Clean Pagination Dots (Click to jump to photo) -->
                    <div class="slider-pagination-dots" id="sliderPaginationDots">
                        <?php for ($i = 0; $i < $total_images; $i++): ?>
                            <span class="slider-pagination-dot <?php echo $i === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $i; ?>)" title="Photo <?php echo $i + 1; ?>"></span>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Product Description, Pricing, and Booking Form -->
        <div class="gadget-info-box">
            <div style="margin-bottom: 8px;">
                <span class="badge badge-info"><?php echo htmlspecialchars(ucfirst($gadget['cat_name'] ?? 'Gadget')); ?></span>
            </div>

            <h1 style="font-family: 'Rajdhani', sans-serif; font-size: 2.3rem; margin: 0 0 12px 0; color: var(--text-color); line-height: 1.2;">
                <?php echo htmlspecialchars($gadget['title']); ?>
            </h1>

            <?php if (!empty($_GET['deal'])): 
                $deal_pct = (int)$_GET['deal'];
                $discounted_rate = max(1, round($daily_price * (1 - ($deal_pct / 100))));
            ?>
                <div style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 14px; border-radius: 8px; background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; font-size: 0.82rem; font-weight: 800; margin-bottom: 12px; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.45); letter-spacing: 0.5px;">
                    🔥 SPECIAL HOMEPAGE DEAL: <?php echo $deal_pct; ?>% DISCOUNT APPLIED
                </div>
                <div style="margin-bottom: 20px; display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap;">
                    <span style="font-size: 2.2rem; font-weight: 800; font-family: 'Rajdhani', sans-serif; color: #00f0ff; text-shadow: 0 0 12px rgba(0, 240, 255, 0.4);">
                        ₹<?php echo htmlspecialchars(number_format($discounted_rate, 2)); ?>
                    </span>
                    <span style="text-decoration: line-through; color: #94a3b8; font-size: 1.15rem; font-weight: 600;">
                        ₹<?php echo htmlspecialchars(number_format($daily_price, 2)); ?>
                    </span>
                    <span style="color: #10b981; font-weight: 800; font-size: 0.95rem;">
                        (Save <?php echo $deal_pct; ?>% / ₹<?php echo number_format($daily_price - $discounted_rate, 2); ?>/day)
                    </span>
                    <span style="color: var(--text-muted); font-size: 0.95rem; font-weight: 600;"> / day</span>
                </div>
            <?php else: ?>
                <div style="margin-bottom: 20px;">
                    <span style="font-size: 2rem; font-weight: 800; font-family: 'Rajdhani', sans-serif; color: #00f0ff;">
                        ₹<?php echo htmlspecialchars(number_format($daily_price, 2)); ?>
                    </span>
                    <span style="color: var(--text-muted); font-size: 0.95rem; font-weight: 600;"> / day</span>
                </div>
            <?php endif; ?>

            <!-- Hardware Stock Status -->
            <div style="font-size: 0.9rem; margin-bottom: 20px; padding: 14px 18px; background: var(--input-bg); border-radius: 12px; border: 1px solid var(--border-color); display: flex; gap: 20px; flex-wrap: wrap;">
                <div>
                    <span style="color: #28a745; font-weight: 700;">&#9679; Available:</span> 
                    <strong><?php echo $available_count; ?></strong>
                </div>
                <div>
                    <span style="color: #007bff; font-weight: 700;">&#9679; Rented:</span> 
                    <strong><?php echo (int)($gadget['rented_count'] ?? 0); ?></strong>
                </div>
                <div>
                    <span style="color: #dc3545; font-weight: 700;">&#9679; Maintenance:</span> 
                    <strong><?php echo (int)($gadget['maintenance_count'] ?? 0); ?></strong>
                </div>
            </div>

            <!-- Product Description to the Right of the Product -->
            <div class="gadget-desc-container">
                <div class="gadget-desc-title">Product Description</div>
                <div class="gadget-desc-text">
                    <?php 
                    if (!empty($gadget['description'])) {
                        echo nl2br(htmlspecialchars($gadget['description']));
                    } else {
                        echo "<em>No detailed description currently available for this gadget.</em>";
                    }
                    ?>
                </div>
            </div>

            <!-- Rental Booking Form -->
            <div class="details-rent-calculator">
                <h3 style="margin-top: 0; margin-bottom: 12px; font-size: 1.2rem;">Book Rental</h3>
                <form action="cart_action.php" method="POST" id="detailRentalForm">
                    <input type="hidden" name="gadget_id" value="<?php echo $gadget['id']; ?>">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label>Delivery Date:</label>
                            <input type="date" name="delivery_date" id="detailDeliveryDate" required min="<?php echo date('Y-m-d'); ?>" onchange="calculateRentalPrice()">
                        </div>
                        <div>
                            <label>Return Date:</label>
                            <input type="date" name="return_date" id="detailReturnDate" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" onchange="calculateRentalPrice()">
                        </div>
                    </div>

                    <!-- Price Preview -->
                    <div class="details-price-preview" id="pricePreviewBox" style="display: none;">
                        <span>Rental Duration: <strong id="rentalDays">0</strong> days</span>
                        <span>Estimated Total: <strong id="rentalTotal" style="color: #00f0ff; font-size: 1.15rem;">₹0.00</strong></span>
                    </div>

                    <?php if ($available_count > 0): ?>
                        <button type="submit" name="add_to_cart" class="btn" style="width: 100%; margin-top: 10px;">
                            Add to Cart
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn" style="width: 100%; margin-top: 10px; background: #94a3b8; cursor: not-allowed;" disabled>
                            Currently Out of Stock
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Product Description & Specifications Section Below the Product -->
    <div class="details-full-description-card">
        <div class="details-full-desc-header">
            <span>&#128196; Full Product Description & Specifications</span>
        </div>
        
        <p style="font-size: 1.05rem; line-height: 1.8; color: var(--text-color); margin-bottom: 25px;">
            <?php 
            if (!empty($gadget['description'])) {
                echo nl2br(htmlspecialchars($gadget['description']));
            } else {
                echo "Detailed product specifications will be updated soon.";
            }
            ?>
        </p>

        <div class="details-specs-grid">
            <div class="details-spec-item">
                <div class="details-spec-label">Product Name</div>
                <div class="details-spec-value"><?php echo htmlspecialchars($gadget['title']); ?></div>
            </div>
            <div class="details-spec-item">
                <div class="details-spec-label">Category</div>
                <div class="details-spec-value"><?php echo htmlspecialchars(ucfirst($gadget['cat_name'] ?? 'General')); ?></div>
            </div>
            <div class="details-spec-item">
                <div class="details-spec-label">Daily Rental Rate</div>
                <div class="details-spec-value" style="color: #00f0ff;">₹<?php echo htmlspecialchars(number_format($daily_price, 2)); ?></div>
            </div>
            <div class="details-spec-item">
                <div class="details-spec-label">Availability Status</div>
                <div class="details-spec-value" style="color: <?php echo $available_count > 0 ? '#10b981' : '#ef4444'; ?>;">
                    <?php echo $available_count > 0 ? "In Stock ($available_count Available)" : 'Out of Stock'; ?>
                </div>
            </div>
            <div class="details-spec-item">
                <div class="details-spec-label">Delivery Policy</div>
                <div class="details-spec-value" style="font-size: 0.95rem;">Same Day / Scheduled Delivery</div>
            </div>
            <div class="details-spec-item">
                <div class="details-spec-label">Hardware Guarantee</div>
                <div class="details-spec-value" style="font-size: 0.95rem;">Tested & Sanitized Units</div>
            </div>
        </div>
    </div>
</div>

<script>
// ========================================================
// Strict Single-Photo Swipeable Slider
// ONLY 1 Photo Visible in Frame. User Swipes to View Others.
// ========================================================
(function() {
    const totalSlides = <?php echo (int)$total_images; ?>;
    if (totalSlides <= 1) return;

    const viewport = document.getElementById('sliderViewport');
    const track = document.getElementById('sliderTrack');
    const counterBadge = document.getElementById('sliderBadgeCounter');
    const swipeHint = document.getElementById('swipeHint');
    const dots = document.querySelectorAll('.slider-pagination-dot');

    let currentIndex = 0;
    let startX = 0;
    let currentX = 0;
    let isDragging = false;
    let hasSwiped = false;

    window.goToSlide = function(index) {
        if (index < 0) index = 0;
        if (index >= totalSlides) index = totalSlides - 1;
        currentIndex = index;
        updateSlider(true);
        hideHint();
    };

    window.moveSlide = function(direction) {
        let nextIndex = currentIndex + direction;
        if (nextIndex < 0) nextIndex = totalSlides - 1;
        if (nextIndex >= totalSlides) nextIndex = 0;
        goToSlide(nextIndex);
    };

    function updateSlider(animate) {
        if (animate) {
            track.style.transition = 'transform 0.35s cubic-bezier(0.25, 1, 0.5, 1)';
        } else {
            track.style.transition = 'none';
        }
        // Move by exact percentage - completely immune to container resize / delay!
        track.style.transform = 'translateX(-' + (currentIndex * 100) + '%)';

        // Update 1-Photo Counter
        if (counterBadge) {
            counterBadge.textContent = '📷 ' + (currentIndex + 1) + ' / ' + totalSlides;
        }

        // Update Dots
        dots.forEach((dot, idx) => {
            dot.classList.toggle('active', idx === currentIndex);
        });
    }

    function hideHint() {
        if (!hasSwiped && swipeHint) {
            hasSwiped = true;
            swipeHint.style.opacity = '0';
            setTimeout(() => {
                if (swipeHint) swipeHint.style.display = 'none';
            }, 400);
        }
    }

    // Touch Event Handlers for Mobile Swiping
    viewport.addEventListener('touchstart', function(e) {
        isDragging = true;
        startX = e.touches[0].clientX;
        currentX = startX;
    }, { passive: true });

    viewport.addEventListener('touchmove', function(e) {
        if (!isDragging) return;
        currentX = e.touches[0].clientX;
    }, { passive: true });

    viewport.addEventListener('touchend', function() {
        if (!isDragging) return;
        isDragging = false;
        const diffX = currentX - startX;
        const threshold = 40; // 40px swipe threshold

        if (diffX < -threshold && currentIndex < totalSlides - 1) {
            currentIndex++;
            updateSlider(true);
            hideHint();
        } else if (diffX > threshold && currentIndex > 0) {
            currentIndex--;
            updateSlider(true);
            hideHint();
        }
    });

    // Mouse Drag Event Handlers for Desktop Swiping
    viewport.addEventListener('mousedown', function(e) {
        e.preventDefault();
        isDragging = true;
        startX = e.clientX;
        currentX = startX;
        viewport.classList.add('is-dragging');
    });

    window.addEventListener('mousemove', function(e) {
        if (!isDragging) return;
        currentX = e.clientX;
    });

    window.addEventListener('mouseup', function() {
        if (!isDragging) return;
        isDragging = false;
        viewport.classList.remove('is-dragging');
        const diffX = currentX - startX;
        const threshold = 40;

        if (diffX < -threshold && currentIndex < totalSlides - 1) {
            currentIndex++;
            updateSlider(true);
            hideHint();
        } else if (diffX > threshold && currentIndex > 0) {
            currentIndex--;
            updateSlider(true);
            hideHint();
        }
    });

    // Keyboard Arrow Keys (Left / Right)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft') moveSlide(-1);
        if (e.key === 'ArrowRight') moveSlide(1);
    });

    // Initialize to show strictly photo 1
    updateSlider(false);
})();

// Real-time Rental Calculator
function calculateRentalPrice() {
    const d1Val = document.getElementById('detailDeliveryDate').value;
    const d2Val = document.getElementById('detailReturnDate').value;
    const previewBox = document.getElementById('pricePreviewBox');
    const daysSpan = document.getElementById('rentalDays');
    const totalSpan = document.getElementById('rentalTotal');
    const dailyPrice = <?php echo json_encode($daily_price); ?>;

    if (d1Val && d2Val) {
        const d1 = new Date(d1Val);
        const d2 = new Date(d2Val);
        const diffTime = d2 - d1;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays > 0) {
            const total = (diffDays * dailyPrice).toFixed(2);
            daysSpan.textContent = diffDays;
            totalSpan.textContent = '₹' + total;
            previewBox.style.display = 'flex';
        } else {
            previewBox.style.display = 'none';
        }
    } else {
        previewBox.style.display = 'none';
    }
}
</script>

<?php require 'footer.php'; ?>
