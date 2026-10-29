<?php
require 'db.php';
require 'header.php';

// Fetch Games specifically for the Swipeable Featured Games Carousel with Discounts
$games_carousel_query = "SELECT g.*, c.name as cat_name, 
                         SUM(CASE WHEN h.status = 'Available' THEN 1 ELSE 0 END) as available_count
                         FROM gadgets g 
                         LEFT JOIN categories c ON g.category_id = c.id 
                         LEFT JOIN hardware_units h ON g.id = h.gadget_id
                         WHERE c.name LIKE '%game%' OR g.category_id = 1
                         GROUP BY g.id ORDER BY FIELD(g.id, 1, 11, 8, 15, 5, 6, 12, 7), g.id ASC";
$games_carousel_res = mysqli_query($conn, $games_carousel_query);
$featured_games = [];
if ($games_carousel_res) {
    while ($row = mysqli_fetch_assoc($games_carousel_res)) {
        $featured_games[] = $row;
    }
}

// Curated Discount Profiles with savings, badges, and features for each game
$game_discount_profiles = [
    1 => [
        'discount_pct' => 40,
        'badge' => '🔥 40% OFF FLASH DEAL',
        'sub_badge' => 'MOST ANTICIPATED',
        'rating' => '⭐ 5.0 RATED',
        'highlight' => 'Vice City Next-Gen 4K • Ray Tracing 60FPS • Free Day-1 Doorstep Delivery. Experience the most anticipated gaming release with zero deposit options.',
        'chips' => ['🎮 4K 60FPS', '⚡ Ray Tracing', '📦 Free Doorstep Delivery', '🛡️ Sanitized Disc']
    ],
    11 => [
        'discount_pct' => 35,
        'badge' => '⚡ 35% OFF WEEKEND PASS',
        'sub_badge' => 'PS5 EXCLUSIVE',
        'rating' => '⭐ 4.9 RATED',
        'highlight' => 'DualSense Haptic Feedback • Instant Fast Travel • Spectacular Web-Swinging across Marvel\'s New York with Peter Parker & Miles Morales.',
        'chips' => ['🎮 DualSense Haptics', '🕸️ Fast Travel', '⚡ 60 FPS Performance', '📦 Instant Delivery']
    ],
    8 => [
        'discount_pct' => 30,
        'badge' => '🛡️ 30% OFF VALHALLA SPECIAL',
        'sub_badge' => 'CRITICS CHOICE',
        'rating' => '⭐ 5.0 RATED',
        'highlight' => 'Explore the Nine Realms with Kratos & Atreus • Immersive 3D Tempest Audio • Visceral Leviathan Axe & Blades of Chaos combat.',
        'chips' => ['⚔️ 9 Realms Adventure', '🎧 3D Audio', '🎮 4K HDR Fidelity', '🛡️ Tested Genuine Disc']
    ],
    15 => [
        'discount_pct' => 45,
        'badge' => '💥 45% OFF MEGA LAUNCH',
        'sub_badge' => 'PS5 PRO READY',
        'rating' => '⭐ 4.8 RATED',
        'highlight' => 'Adamantium Claws Unleashed • Visceral Next-Gen Combat • High-octane Marvel superhero action crafted exclusively for PlayStation 5 hardware.',
        'chips' => ['🩸 Mature Action', '⚡ High Framerate', '🎮 Haptic Triggers', '📦 Express Handover']
    ],
    5 => [
        'discount_pct' => 35,
        'badge' => '⚽ 35% OFF MATCHDAY PASS',
        'sub_badge' => 'CO-OP TOURNAMENT HIT',
        'rating' => '⭐ 4.7 RATED',
        'highlight' => 'HyperMotionV Volumetric Realism • Ultimate Team Competitions • 19,000+ Licensed Players and 700+ Global Football Clubs.',
        'chips' => ['⚽ HyperMotionV', '🏆 Ultimate Team', '🎮 2-4 Player Co-Op', '📦 Quick Delivery']
    ],
    6 => [
        'discount_pct' => 30,
        'badge' => '🏎️ 30% OFF SPEED PASS',
        'sub_badge' => 'WHEEL COMPATIBLE',
        'rating' => '⭐ 4.9 RATED',
        'highlight' => '500+ Licensed Supercars across Vibrant Mexico • Dynamic Weather & Dust Storms • Full force-feedback support for Logitech G29 & G923 racing wheels.',
        'chips' => ['🏎️ 500+ Supercars', '🕹️ Wheel Compatible', '⚡ Ultra 4K 60FPS', '📦 Free Dropoff']
    ],
    12 => [
        'discount_pct' => 40,
        'badge' => '🧟 40% OFF REMAKE DEAL',
        'sub_badge' => 'AWARD WINNING',
        'rating' => '⭐ 4.9 RATED',
        'highlight' => 'Rebuilt from the Ground Up for PS5 • Photorealistic Character Models • Heart-wrenching cinematic journey across post-pandemic America.',
        'chips' => ['🧟 Ground-Up Remake', '🎮 DualSense Triggers', '🎧 3D Tempest Audio', '🛡️ Verified Copy']
    ],
    7 => [
        'discount_pct' => 30,
        'badge' => '🎯 30% OFF RECON DEAL',
        'sub_badge' => 'UNREAL ENGINE 5',
        'rating' => '⭐ 4.8 RATED',
        'highlight' => 'Emergence Day Campaign • Powered by Unreal Engine 5 with breathtaking destruction physics • Intense tactical co-op campaign.',
        'chips' => ['💥 Unreal Engine 5', '🎮 2-Player Co-Op', '⚡ 4K 120Hz Support', '📦 Fast Courier']
    ]
];
?>

<!-- ========================================================
     1. HERO SECTION (SharePal-Style Adapted for ONYXPLAY)
     ======================================================== -->
<section class="sp-hero-section">
    <!-- Concentric Ripple Rings -->
    <div class="sp-ripple-wrapper">
        <div class="sp-ripple-ring sp-ring-1"></div>
        <div class="sp-ripple-ring sp-ring-2"></div>
        <div class="sp-ripple-ring sp-ring-3"></div>
        <div class="sp-ripple-ring sp-ring-4"></div>
    </div>

    <!-- Flanking Floating Gaming Gadgets (Clean, No Tags, Safe Corners) -->
    <!-- Top-Left: Meta Quest 3 VR Headset -->
    <a href="gadget_details.php?id=31" class="sp-floating-gadget sp-fg-top-left" title="Click to view Meta Quest 3 VR Headset">
        <div class="sp-fg-frame">
            <img src="uploads/metaquest3_meta.jpg" alt="Meta Quest 3">
        </div>
    </a>

    <!-- Top-Right: Logitech G29 Force Feedback Wheel -->
    <a href="gadget_details.php?id=36" class="sp-floating-gadget sp-fg-top-right" title="Click to view Logitech G29 Racing Wheel">
        <div class="sp-fg-frame">
            <img src="uploads/logitecho29_g29.jpg" alt="Logitech G29">
        </div>
    </a>

    <!-- Mid-Left: PlayStation 5 Console -->
    <a href="gadget_details.php?id=2" class="sp-floating-gadget sp-fg-mid-left" title="Click to view PlayStation 5 Disc Console">
        <div class="sp-fg-frame">
            <img src="uploads/ps5console_ps5.jpg" alt="PlayStation 5 Console">
        </div>
    </a>

    <!-- Mid-Right: Xbox Series X Console -->
    <a href="gadget_details.php?id=17" class="sp-floating-gadget sp-fg-mid-right" title="Click to view Xbox Series X 4K Console">
        <div class="sp-fg-frame">
            <img src="uploads/xboxseriesx_xbox_series_x.jpg" alt="Xbox Series X">
        </div>
    </a>

    <!-- Center Hero Typography & Search -->
    <div class="sp-hero-center">
        <!-- ONYXPLAY Esports Brand Pill -->
        <div class="hero-brand-pill">
            <span class="pulse-dot"></span>
            <span class="hero-brand-text">ONYXPLAY &bull; NEXT-GEN GAMING GEAR ON DEMAND</span>
        </div>

        <!-- Main Headline with Hand-Drawn Highlights -->
        <h1 class="sp-main-title">
            "Own the 
            <span class="sp-sketch-circle-wrap">
                Experience
                <svg class="sp-sketch-circle-svg" viewBox="0 0 250 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M 25,40 C 20,18 100,10 190,16 C 240,20 248,58 160,68 C 60,78 10,55 20,32 C 26,18 90,8 180,12" stroke="#00f0ff" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                </svg>
            </span>
            <br>
            <span class="sp-sketch-underline-wrap">
                Rent
                <svg class="sp-sketch-underline-svg" viewBox="0 0 120 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M 6,14 Q 30,22 65,14 T 115,16 M 15,20 Q 45,26 85,20" stroke="#b026ff" stroke-width="3.2" stroke-linecap="round" fill="none"/>
                </svg>
            </span>
            the Gear"
        </h1>

        <!-- Sub Planet & Pocket Badges -->
        <div class="sp-planet-pocket-pill">
            <span style="display: inline-flex; align-items: center; gap: 6px;">
                <span>🌍</span> Good for our <strong style="color: #00d2ff;">Planet</strong>
            </span>
            <span class="sp-rental-divider">&bull;</span>
            <span style="display: inline-flex; align-items: center; gap: 6px;">
                <span>💳</span> Good for your <strong style="color: #a855f7;">Pocket</strong>
            </span>
        </div>

        <!-- Quick Hero Search directly to Catalog -->
        <form method="GET" action="catalog.php" style="display: flex; max-width: 540px; margin: 18px auto 0; gap: 8px; width: 100%;">
            <input 
                type="text" 
                name="search" 
                placeholder="Search 39+ consoles, games, racing rigs & VR..." 
                style="flex: 1; border-radius: 25px; padding: 11px 20px; font-size: 0.95rem; margin: 0; background: rgba(18, 22, 36, 0.85); border: 1.5px solid rgba(0, 240, 255, 0.35); color: #fff;"
            >
            <button type="submit" class="btn" style="border-radius: 25px; padding: 11px 22px; font-weight: 700; font-size: 0.92rem; white-space: nowrap;">Search Gear</button>
        </form>
    </div>

    <!-- 4 Bottom Category Showcase Cards (SharePal Style with Color Bars) -->
    <div class="sp-cat-cards-grid">
        <!-- Card 1: Consoles -->
        <a href="catalog.php?category=2" class="sp-category-card">
            <div>
                <h3 class="sp-card-title">Gaming Consoles</h3>
                <p class="sp-card-sub">Rent PS5, PS5 Pro, Xbox Series X, Switch OLED & more</p>
            </div>
            <div class="sp-card-gear-preview">
                <img src="uploads/ps5console_ps5.jpg" alt="PS5 Console">
                <img src="uploads/xboxseriesx_xbox_series_x.jpg" alt="Xbox Series X">
            </div>
            <div class="sp-card-bottom-bar" style="background: linear-gradient(90deg, #00f0ff, #8b5cf6);"></div>
        </a>

        <!-- Card 2: VR & Sim Racing -->
        <a href="catalog.php?category=6" class="sp-category-card">
            <div>
                <h3 class="sp-card-title">VR & Sim Racing</h3>
                <p class="sp-card-sub">Rent Meta Quest 3, Logitech G29, Thrustmaster & more</p>
            </div>
            <div class="sp-card-gear-preview">
                <img src="uploads/metaquest3_meta.jpg" alt="Meta Quest 3">
                <img src="uploads/logitecho29_g29.jpg" alt="Logitech G29">
            </div>
            <div class="sp-card-bottom-bar" style="background: linear-gradient(90deg, #06b6d4, #0284c7);"></div>
        </a>

        <!-- Card 3: Controllers & Audio -->
        <a href="catalog.php?category=7" class="sp-category-card">
            <div>
                <h3 class="sp-card-title">Controllers & Audio</h3>
                <p class="sp-card-sub">Rent DualSense Edge, Xbox Elite 2, Pulse 3D Headset</p>
            </div>
            <div class="sp-card-gear-preview">
                <img src="uploads/dualsense_ps5dual.jpg" alt="DualSense Controller">
                <img src="uploads/pulseelite_elite.avif" alt="Pulse Elite" onerror="this.src='uploads/xboxcontroller_xbox.jpg'">
            </div>
            <div class="sp-card-bottom-bar" style="background: linear-gradient(90deg, #10b981, #059669);"></div>
        </a>

        <!-- Card 4: Games & Passes -->
        <a href="#game-carousel-section" class="sp-category-card">
            <div>
                <h3 class="sp-card-title">Blockbuster Games</h3>
                <p class="sp-card-sub">Rent GTA VI, Spider-Man 2, FC 25, Wolverine & more with discounts</p>
            </div>
            <div class="sp-card-gear-preview">
                <img src="uploads/gtavi_Gta_6.jpg" alt="GTA VI">
                <img src="uploads/spiderman2_spiderman2.jpg" alt="Spider-Man 2" onerror="this.src='uploads/forzahorizon_forza_6.webp'">
            </div>
            <div class="sp-card-bottom-bar" style="background: linear-gradient(90deg, #00f0ff, #b026ff);"></div>
        </a>
    </div>

    <!-- Mouse Scroll Down Indicator -->
    <a href="#game-carousel-section" class="sp-scroll-prompt">
        <span>Scroll down</span>
        <div class="mouse-pill">
            <div class="mouse-wheel"></div>
        </div>
        <span>to explore discounted blockbuster games</span>
    </a>
</section>

<!-- ========================================================
     2. FEATURED GAMES SWIPEABLE & AUTO-CYCLING CAROUSEL WITH DISCOUNTS
     ======================================================== -->
<section class="game-carousel-section" id="game-carousel-section" aria-label="Featured Discounted Games">
    <!-- Header with Title, Live Badge, and Carousel Controls -->
    <div class="carousel-section-header">
        <div>
            <div class="carousel-top-badge">
                <span class="pulse-beacon"></span>
                <span>LIMITED TIME EXCLUSIVE DEALS</span>
            </div>
            <h2 class="carousel-main-title">🔥 Featured Games & Discounted Rentals</h2>
            <p class="carousel-subtitle">Swipe or browse top blockbuster titles available with up to 45% discount. Doorstep delivery included.</p>
        </div>
        <div class="carousel-nav-controls">
            <!-- Autoplay status indicator / toggle -->
            <button type="button" class="carousel-autoplay-toggle" id="carouselAutoplayToggle" title="Click to pause or resume automatic cycling">
                <span class="autoplay-dot" id="autoplayDot"></span>
                <span id="autoplayStatusText">Auto-Cycle: ON</span>
            </button>
            <!-- Navigation arrows -->
            <button type="button" class="carousel-arrow-btn prev-arrow" id="carouselPrevBtn" aria-label="Previous Game">
                &#10094;
            </button>
            <button type="button" class="carousel-arrow-btn next-arrow" id="carouselNextBtn" aria-label="Next Game">
                &#10095;
            </button>
        </div>
    </div>

    <!-- Main Carousel Stage -->
    <div class="game-carousel-wrapper" id="gameCarouselWrapper">
        <!-- Laser Progress Bar for Autoplay Countdown -->
        <div class="carousel-laser-timer" id="carouselLaserTimer">
            <div class="laser-timer-fill" id="laserTimerFill"></div>
        </div>

        <!-- Viewport & Slider Track (Touch & Mouse Drag Enabled) -->
        <div class="game-carousel-viewport" id="gameCarouselViewport">
            <div class="game-carousel-track" id="gameCarouselTrack">
                <?php foreach ($featured_games as $index => $fg): 
                    $game_id = (int)$fg['id'];
                    $profile = $game_discount_profiles[$game_id] ?? [
                        'discount_pct' => 25,
                        'badge' => '🔥 25% OFF SPECIAL DEAL',
                        'sub_badge' => 'POPULAR RENTAL',
                        'rating' => '⭐ 4.8 RATED',
                        'highlight' => htmlspecialchars($fg['description'] ?? 'Verified genuine gaming copy available for instant doorstep delivery.'),
                        'chips' => ['🎮 Console Ready', '📦 Free Dropoff', '🛡️ Sanitized Disc', '⚡ Fast Handover']
                    ];
                    
                    $orig_price = (float)$fg['daily_price'];
                    $discount_pct = (int)$profile['discount_pct'];
                    $deal_price = max(1, round($orig_price * (1 - ($discount_pct / 100))));
                    $savings = $orig_price - $deal_price;
                    $details_url = "gadget_details.php?id=" . $game_id . "&deal=" . $discount_pct;
                    $avail = (int)$fg['available_count'];
                ?>
                <div class="game-slide" data-slide-index="<?php echo $index; ?>" data-game-id="<?php echo $game_id; ?>">
                    <div class="game-slide-inner">
                        <!-- Left Column: Content, Discount Badges & Actions -->
                        <div class="slide-content-col">
                            <div class="slide-badges-row">
                                <span class="slide-discount-badge"><?php echo $profile['badge']; ?></span>
                                <span class="slide-sub-badge"><?php echo $profile['sub_badge']; ?></span>
                                <span class="slide-rating-badge"><?php echo $profile['rating']; ?></span>
                            </div>

                            <h3 class="slide-game-title">
                                <a href="<?php echo htmlspecialchars($details_url); ?>"><?php echo htmlspecialchars($fg['title']); ?></a>
                            </h3>

                            <p class="slide-game-desc">
                                <?php echo htmlspecialchars($profile['highlight']); ?>
                            </p>

                            <div class="slide-chips-row">
                                <?php foreach ($profile['chips'] as $chip): ?>
                                    <span class="slide-chip"><?php echo htmlspecialchars($chip); ?></span>
                                <?php endforeach; ?>
                            </div>

                            <!-- Price & Discount Box -->
                            <div class="slide-pricing-box">
                                <div class="pricing-rates">
                                    <div class="rate-current">
                                        <span class="rate-currency">₹</span>
                                        <span class="rate-number"><?php echo number_format($deal_price); ?></span>
                                        <span class="rate-period">/ day</span>
                                    </div>
                                    <div class="rate-original">
                                        <span class="rate-strike">₹<?php echo number_format($orig_price, 2); ?> / day</span>
                                        <span class="rate-savings">SAVE ₹<?php echo number_format($savings, 2); ?>/day (<?php echo $discount_pct; ?>% OFF)</span>
                                    </div>
                                </div>
                                <div class="rate-deal-timer">
                                    <span>⚡ Limited Deal Applied</span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="slide-actions-row">
                                <a href="<?php echo htmlspecialchars($details_url); ?>" class="btn slide-primary-btn">
                                    Rent This Deal &rarr;
                                </a>

                                <?php if ($avail > 0): ?>
                                    <!-- Quick 3-Day Add to Cart Form (intercepted by footer.php AJAX) -->
                                    <form action="cart_action.php" method="POST" style="margin: 0; display: inline-block;">
                                        <input type="hidden" name="gadget_id" value="<?php echo $game_id; ?>">
                                        <input type="hidden" name="delivery_date" value="<?php echo date('Y-m-d'); ?>">
                                        <input type="hidden" name="return_date" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>">
                                        <button type="submit" name="add_to_cart" class="btn slide-cart-btn" title="Quick Add 3-Day Rental to Cart">
                                            🛒 Quick Book (3 Days)
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn slide-cart-btn" disabled style="opacity: 0.6; cursor: not-allowed;">
                                        Out of Stock
                                    </button>
                                <?php endif; ?>

                                <a href="<?php echo htmlspecialchars($details_url); ?>" class="btn slide-secondary-btn" title="View details, specifications and images">
                                    Specs & Images
                                </a>
                            </div>
                        </div>

                        <!-- Right Column: High-Res Game Poster Art & Visual Highlights -->
                        <div class="slide-visual-col">
                            <a href="<?php echo htmlspecialchars($details_url); ?>" class="slide-poster-link" title="Click to view <?php echo htmlspecialchars($fg['title']); ?> details">
                                <div class="poster-glow-backdrop"></div>
                                <div class="poster-frame">
                                    <img src="uploads/<?php echo htmlspecialchars($fg['image']); ?>" alt="<?php echo htmlspecialchars($fg['title']); ?>" class="poster-img" loading="lazy">
                                    <div class="poster-deal-tag">
                                        <span><?php echo $discount_pct; ?>% OFF</span>
                                    </div>
                                    <div class="poster-status-pill">
                                        <span class="status-indicator-dot" style="background: <?php echo $avail > 0 ? '#10b981' : '#ef4444'; ?>;"></span>
                                        <span><?php echo $avail > 0 ? "In Stock ($avail Available)" : 'Out of Stock'; ?></span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Dot Navigation Indicators -->
        <div class="carousel-dots-container" id="carouselDotsContainer">
            <?php foreach ($featured_games as $index => $fg): ?>
                <button type="button" class="carousel-dot <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>" aria-label="Go to slide <?php echo $index + 1; ?>: <?php echo htmlspecialchars($fg['title']); ?>"></button>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
(function() {
    const track = document.getElementById('gameCarouselTrack');
    const viewport = document.getElementById('gameCarouselViewport');
    const wrapper = document.getElementById('gameCarouselWrapper');
    const prevBtn = document.getElementById('carouselPrevBtn');
    const nextBtn = document.getElementById('carouselNextBtn');
    const dots = document.querySelectorAll('.carousel-dot');
    const laserFill = document.getElementById('laserTimerFill');
    const toggleBtn = document.getElementById('carouselAutoplayToggle');
    const statusText = document.getElementById('autoplayStatusText');
    const slides = document.querySelectorAll('.game-slide');
    
    if (!track || slides.length === 0) return;

    let currentIndex = 0;
    const totalSlides = slides.length;
    const autoCycleDuration = 4500; // 4.5 seconds per slide
    let isAutoplayActive = true;
    let isHovered = false;
    let countdownStartTime = Date.now();
    let elapsedBeforePause = 0;
    let animationFrameId = null;

    // Drag / Touch state
    let isDragging = false;
    let startX = 0;
    let currentX = 0;
    let deltaX = 0;
    let startTimestamp = 0;

    function updateSlidePosition(smooth = true) {
        if (!smooth) {
            track.classList.add('no-transition');
        } else {
            track.classList.remove('no-transition');
        }
        track.style.transform = `translateX(-${currentIndex * 100}%)`;

        // Update dots
        dots.forEach((dot, idx) => {
            if (idx === currentIndex) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });
    }

    function goToSlide(index) {
        currentIndex = (index + totalSlides) % totalSlides;
        updateSlidePosition(true);
        resetCountdown();
    }

    function nextSlide() {
        goToSlide(currentIndex + 1);
    }

    function prevSlide() {
        goToSlide(currentIndex - 1);
    }

    // Autoplay Timer Loop with Smooth Progress Bar
    function startCountdown() {
        countdownStartTime = Date.now() - elapsedBeforePause;
        runCountdownFrame();
    }

    function runCountdownFrame() {
        if (!isAutoplayActive || isHovered || isDragging) return;

        const now = Date.now();
        const elapsed = now - countdownStartTime;
        const progress = Math.min(100, (elapsed / autoCycleDuration) * 100);

        if (laserFill) {
            laserFill.style.width = `${progress}%`;
        }

        if (elapsed >= autoCycleDuration) {
            elapsedBeforePause = 0;
            nextSlide();
        } else {
            animationFrameId = requestAnimationFrame(runCountdownFrame);
        }
    }

    function pauseCountdown() {
        if (animationFrameId) {
            cancelAnimationFrame(animationFrameId);
            animationFrameId = null;
        }
        elapsedBeforePause = Date.now() - countdownStartTime;
    }

    function resetCountdown() {
        elapsedBeforePause = 0;
        countdownStartTime = Date.now();
        if (laserFill) {
            laserFill.style.width = '0%';
        }
        if (isAutoplayActive && !isHovered && !isDragging) {
            if (animationFrameId) cancelAnimationFrame(animationFrameId);
            animationFrameId = requestAnimationFrame(runCountdownFrame);
        }
    }

    // Button controls
    if (prevBtn) {
        prevBtn.addEventListener('click', (e) => {
            e.preventDefault();
            prevSlide();
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', (e) => {
            e.preventDefault();
            nextSlide();
        });
    }

    // Dot indicators
    dots.forEach((dot, idx) => {
        dot.addEventListener('click', (e) => {
            e.preventDefault();
            goToSlide(idx);
        });
    });

    // Autoplay toggle button
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            isAutoplayActive = !isAutoplayActive;
            if (isAutoplayActive) {
                toggleBtn.classList.remove('is-paused');
                statusText.textContent = 'Auto-Cycle: ON';
                resetCountdown();
            } else {
                toggleBtn.classList.add('is-paused');
                statusText.textContent = 'Auto-Cycle: PAUSED';
                pauseCountdown();
                if (laserFill) laserFill.style.width = '0%';
            }
        });
    }

    // Pause on hover
    wrapper.addEventListener('mouseenter', () => {
        isHovered = true;
        pauseCountdown();
    });

    wrapper.addEventListener('mouseleave', () => {
        isHovered = false;
        if (isAutoplayActive && !isDragging) {
            startCountdown();
        }
    });

    // Touch & Swipe support
    viewport.addEventListener('touchstart', (e) => {
        isDragging = true;
        pauseCountdown();
        startX = e.touches[0].clientX;
        currentX = startX;
        deltaX = 0;
        startTimestamp = Date.now();
        track.classList.add('no-transition');
    }, { passive: true });

    viewport.addEventListener('touchmove', (e) => {
        if (!isDragging) return;
        currentX = e.touches[0].clientX;
        deltaX = currentX - startX;
        
        // Real-time track follow
        const viewportWidth = viewport.offsetWidth || 1;
        const offsetPercent = (deltaX / viewportWidth) * 100;
        const currentPercent = -(currentIndex * 100) + offsetPercent;
        track.style.transform = `translateX(${currentPercent}%)`;
    }, { passive: true });

    viewport.addEventListener('touchend', (e) => {
        if (!isDragging) return;
        isDragging = false;
        track.classList.remove('no-transition');
        
        const duration = Date.now() - startTimestamp;
        const threshold = 40; // min 40px swipe
        
        if (deltaX < -threshold || (deltaX < -20 && duration < 250)) {
            nextSlide();
        } else if (deltaX > threshold || (deltaX > 20 && duration < 250)) {
            prevSlide();
        } else {
            // Snap back
            updateSlidePosition(true);
            resetCountdown();
        }
        deltaX = 0;
    });

    // Mouse Drag support on desktop
    viewport.addEventListener('mousedown', (e) => {
        // Prevent default drag behaviour of images
        if (e.button !== 0) return; // Only left click
        isDragging = true;
        pauseCountdown();
        viewport.classList.add('is-dragging');
        track.classList.add('no-transition');
        startX = e.clientX;
        currentX = startX;
        deltaX = 0;
        startTimestamp = Date.now();
    });

    window.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        currentX = e.clientX;
        deltaX = currentX - startX;

        const viewportWidth = viewport.offsetWidth || 1;
        const offsetPercent = (deltaX / viewportWidth) * 100;
        const currentPercent = -(currentIndex * 100) + offsetPercent;
        track.style.transform = `translateX(${currentPercent}%)`;
    });

    window.addEventListener('mouseup', (e) => {
        if (!isDragging) return;
        isDragging = false;
        viewport.classList.remove('is-dragging');
        track.classList.remove('no-transition');

        const duration = Date.now() - startTimestamp;
        const threshold = 50;

        if (deltaX < -threshold || (deltaX < -25 && duration < 200)) {
            nextSlide();
        } else if (deltaX > threshold || (deltaX > 25 && duration < 200)) {
            prevSlide();
        } else {
            updateSlidePosition(true);
            resetCountdown();
        }

        // Prevent click if dragged more than 10px
        if (Math.abs(deltaX) > 10) {
            const clickStopper = function(ev) {
                ev.preventDefault();
                ev.stopPropagation();
                window.removeEventListener('click', clickStopper, true);
            };
            window.addEventListener('click', clickStopper, true);
        }
        deltaX = 0;
    });

    // Keyboard navigation (Arrow keys)
    window.addEventListener('keydown', (e) => {
        // Only if carousel is within viewport
        const rect = wrapper.getBoundingClientRect();
        if (rect.top < window.innerHeight && rect.bottom > 0) {
            if (e.key === 'ArrowLeft') {
                prevSlide();
            } else if (e.key === 'ArrowRight') {
                nextSlide();
            }
        }
    });

    // Initialize position and start countdown
    updateSlidePosition(false);
    startCountdown();
})();
</script>

<!-- ========================================================
     3. ONYXPLAY FULL ARSENAL & VALUE PROPOSITION SECTION
     ======================================================== -->
<section style="margin-top: 50px; margin-bottom: 30px;">
    <!-- Dual-Tone Arsenal CTA Banner -->
    <div style="background: linear-gradient(135deg, rgba(8, 10, 18, 0.95) 0%, rgba(18, 22, 38, 0.95) 100%); border: 1.5px solid rgba(0, 240, 255, 0.25); border-radius: 20px; padding: 46px 28px; text-align: center; position: relative; overflow: hidden; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6);">
        <!-- Neon Top Accent Line -->
        <div style="position: absolute; top: 0; left: 10%; right: 10%; height: 3px; background: linear-gradient(90deg, transparent, #00f0ff, #b026ff, transparent);"></div>
        
        <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(0, 240, 255, 0.1); border: 1px solid rgba(0, 240, 255, 0.3); border-radius: 20px; padding: 5px 16px; font-size: 0.8rem; font-weight: 700; color: #00f0ff; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 14px;">
            <span>⚡</span> READY FOR THE FULL GAMING ARSENAL?
        </div>
        
        <h2 style="font-family: 'Rajdhani', sans-serif; font-size: 2.4rem; margin: 0 0 12px; color: #fff; text-transform: uppercase; letter-spacing: 0.04em;">
            Explore Our <span style="background: linear-gradient(90deg, #00f0ff, #b026ff); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Complete Rental Catalog</span>
        </h2>
        
        <p style="max-width: 680px; margin: 0 auto 26px; color: var(--text-muted); font-size: 1.02rem; line-height: 1.6;">
            Looking for PlayStation 5, Xbox Series X, Meta Quest 3 VR headsets, Logitech G29 racing rigs, or pro controllers? Access all 39+ verified gear items with instant doorstep delivery and zero deposit options.
        </p>

        <!-- Big CTA Button to Dedicated Catalog Page -->
        <div style="display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; margin-bottom: 26px;">
            <a href="catalog.php" class="btn" style="background: linear-gradient(135deg, #00f0ff 0%, #8b5cf6 100%); color: #050811; font-weight: 800; font-size: 1.05rem; padding: 14px 34px; border-radius: 30px; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 0 25px rgba(0, 240, 255, 0.4); text-transform: uppercase; letter-spacing: 0.05em; border: none; transition: transform 0.2s ease, box-shadow 0.2s ease;">
                🎮 Browse Complete Catalog (39 Products) &rarr;
            </a>
        </div>

        <!-- Quick Category Shortcuts -->
        <div style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
            <a href="catalog.php?category=2" class="sp-tab-pill" style="font-size: 0.85rem; padding: 6px 14px;">
                <span>🎮</span> Gaming Consoles (5)
            </a>
            <a href="catalog.php?category=1" class="sp-tab-pill" style="font-size: 0.85rem; padding: 6px 14px;">
                <span>💿</span> AAA Game Discs (8)
            </a>
            <a href="catalog.php?category=6" class="sp-tab-pill" style="font-size: 0.85rem; padding: 6px 14px;">
                <span>🥽</span> VR Headsets (3)
            </a>
            <a href="catalog.php?category=8" class="sp-tab-pill" style="font-size: 0.85rem; padding: 6px 14px;">
                <span>🏎️</span> Sim Racing Wheels (3)
            </a>
            <a href="catalog.php?category=7" class="sp-tab-pill" style="font-size: 0.85rem; padding: 6px 14px;">
                <span>🕹️</span> Pro Controllers (6)
            </a>
        </div>
    </div>

    <!-- 4 High-Octane Trust / Value Pillars -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; margin-top: 30px;">
        <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; padding: 22px 20px; position: relative; overflow: hidden;">
            <div style="position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, #00f0ff, transparent);"></div>
            <div style="font-size: 2rem; margin-bottom: 10px;">⚡</div>
            <h4 style="font-family: 'Rajdhani', sans-serif; font-size: 1.25rem; margin: 0 0 6px; color: var(--text-color);">2-Hour Rapid Delivery</h4>
            <p style="margin: 0; font-size: 0.88rem; color: var(--text-muted); line-height: 1.5;">Hand-delivered directly to your doorstep in heavy-duty shockproof protective cases.</p>
        </div>

        <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; padding: 22px 20px; position: relative; overflow: hidden;">
            <div style="position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, #8b5cf6, transparent);"></div>
            <div style="font-size: 2rem; margin-bottom: 10px;">🛡️</div>
            <h4 style="font-family: 'Rajdhani', sans-serif; font-size: 1.25rem; margin: 0 0 6px; color: var(--text-color);">100% Sanitized & Tested</h4>
            <p style="margin: 0; font-size: 0.88rem; color: var(--text-muted); line-height: 1.5;">Every console, controller, and disc undergoes UV sterilization and thorough testing before dispatch.</p>
        </div>

        <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; padding: 22px 20px; position: relative; overflow: hidden;">
            <div style="position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, #00f0ff, transparent);"></div>
            <div style="font-size: 2rem; margin-bottom: 10px;">💎</div>
            <h4 style="font-family: 'Rajdhani', sans-serif; font-size: 1.25rem; margin: 0 0 6px; color: var(--text-color);">Zero Deposit Available</h4>
            <p style="margin: 0; font-size: 0.88rem; color: var(--text-muted); line-height: 1.5;">Verified gamer profiles rent with zero deposit and transparent per-day flat pricing.</p>
        </div>

        <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; padding: 22px 20px; position: relative; overflow: hidden;">
            <div style="position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, #b026ff, transparent);"></div>
            <div style="font-size: 2rem; margin-bottom: 10px;">🔄</div>
            <h4 style="font-family: 'Rajdhani', sans-serif; font-size: 1.25rem; margin: 0 0 6px; color: var(--text-color);">Hassle-Free Return Pickup</h4>
            <p style="margin: 0; font-size: 0.88rem; color: var(--text-muted); line-height: 1.5;">When your booking ends, our courier collects the equipment right from your location on schedule.</p>
        </div>
    </div>
</section>

<?php require 'footer.php'; ?>