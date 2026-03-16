<?php
/**
 * Homepage
 * 
 * Displays hero banner, featured products, category grid,
 * and latest products.
 */
require_once __DIR__ . '/includes/init.php';

$db = getDB();

// Fetch featured products (limit 8)
$featuredStmt = $db->query("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.is_featured = 1 AND p.is_active = 1 
    ORDER BY p.created_at DESC 
    LIMIT 8
");
$featuredProducts = $featuredStmt->fetchAll();

// Fetch all active categories
$categories = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

// Fetch latest products (limit 8)
$latestStmt = $db->query("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.is_active = 1 
    ORDER BY p.created_at DESC 
    LIMIT 8
");
$latestProducts = $latestStmt->fetchAll();

$pageTitle = 'Home';
include __DIR__ . '/includes/header.php';
?>

<!-- ── Hero Section ────────────────────────────────────── -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <p class="text-uppercase fw-semibold mb-2" style="color: var(--se-accent); letter-spacing:.1em; font-size:.85rem;">
                    Welcome to <?= e(APP_NAME) ?>
                </p>
                <h1>Discover Quality Products at Unbeatable Prices</h1>
                <p class="lead mt-3 mb-4">
                    Shop from our wide collection of electronics, fashion, books, shoes, and accessories — all in one place.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="<?= url('shop.php') ?>" class="btn btn-primary btn-lg px-4">
                        <i class="bi bi-bag me-1"></i> Shop Now
                    </a>
                    <a href="#categories" class="btn btn-outline-light btn-lg px-4">
                        Browse Categories
                    </a>
                </div>
            </div>
            <div class="col-lg-5 text-center mt-4 mt-lg-0">
                <i class="bi bi-bag-heart" style="font-size: 12rem; opacity: .08; position: absolute; right: 5%; top: 10%;"></i>
            </div>
        </div>
    </div>
</section>

<!-- ── Featured Products ───────────────────────────────── -->
<?php if (!empty($featuredProducts)): ?>
<section class="py-5">
    <div class="container">
        <h2 class="section-title">Featured Products</h2>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($featuredProducts as $product): ?>
                <?php include __DIR__ . '/includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ── Categories ───────────────────────────────────────── -->
<section class="py-5 bg-white" id="categories">
    <div class="container">
        <h2 class="section-title text-center">Shop by Category</h2>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3 justify-content-center">
            <?php
            $catIcons = [
                'electronics' => 'bi-cpu',
                'fashion'     => 'bi-handbag',
                'books'       => 'bi-book',
                'shoes'       => 'bi-shoe',   // fallback icon below
                'accessories' => 'bi-watch',
            ];
            foreach ($categories as $cat):
                $icon = $catIcons[$cat['slug']] ?? 'bi-grid';
            ?>
            <div class="col">
                <a href="<?= url('category.php?slug=' . e($cat['slug'])) ?>" class="category-card">
                    <i class="bi <?= $icon ?>"></i>
                    <strong><?= e($cat['name']) ?></strong>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── Latest Products ─────────────────────────────────── -->
<?php if (!empty($latestProducts)): ?>
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title mb-0">Latest Arrivals</h2>
            <a href="<?= url('shop.php') ?>" class="btn btn-outline-primary btn-sm">View All <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($latestProducts as $product): ?>
                <?php include __DIR__ . '/includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ── Features Strip ──────────────────────────────────── -->
<section class="py-4 bg-white border-top">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-6 col-md-3">
                <i class="bi bi-truck fs-3 text-primary"></i>
                <h6 class="mt-2 mb-1">Free Shipping</h6>
                <small class="text-muted">On orders over <?= formatPrice(FREE_SHIPPING_THRESHOLD) ?></small>
            </div>
            <div class="col-6 col-md-3">
                <i class="bi bi-shield-check fs-3 text-primary"></i>
                <h6 class="mt-2 mb-1">Secure Payment</h6>
                <small class="text-muted">100% protected</small>
            </div>
            <div class="col-6 col-md-3">
                <i class="bi bi-arrow-counterclockwise fs-3 text-primary"></i>
                <h6 class="mt-2 mb-1">Easy Returns</h6>
                <small class="text-muted">30-day return policy</small>
            </div>
            <div class="col-6 col-md-3">
                <i class="bi bi-headset fs-3 text-primary"></i>
                <h6 class="mt-2 mb-1">24/7 Support</h6>
                <small class="text-muted">We're here to help</small>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
