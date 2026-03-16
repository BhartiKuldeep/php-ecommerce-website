<?php
/**
 * Product Detail Page
 * 
 * Shows full product information, reviews, and related products.
 */
require_once __DIR__ . '/includes/init.php';

$slug = $_GET['slug'] ?? '';
if (empty($slug)) { redirect('shop.php'); }

$db = getDB();

// Fetch product
$stmt = $db->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.slug = :slug AND p.is_active = 1 
    LIMIT 1
");
$stmt->execute([':slug' => $slug]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('warning', 'Product not found.');
    redirect('shop.php');
}

$effectivePrice = $product['sale_price'] ?? $product['price'];
$hasDiscount    = !empty($product['sale_price']) && $product['sale_price'] < $product['price'];

// Fetch approved reviews
$reviewStmt = $db->prepare("
    SELECT r.*, u.name AS user_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = :pid AND r.is_approved = 1 
    ORDER BY r.created_at DESC
");
$reviewStmt->execute([':pid' => $product['id']]);
$reviews = $reviewStmt->fetchAll();

// Average rating
$avgStmt = $db->prepare("SELECT AVG(rating) FROM reviews WHERE product_id = :pid AND is_approved = 1");
$avgStmt->execute([':pid' => $product['id']]);
$avgRating = round((float)$avgStmt->fetchColumn(), 1);

// Related products (same category, exclude current)
$relatedStmt = $db->prepare("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.category_id = :cid AND p.id != :pid AND p.is_active = 1 
    ORDER BY RAND() 
    LIMIT 4
");
$relatedStmt->execute([':cid' => $product['category_id'], ':pid' => $product['id']]);
$relatedProducts = $relatedStmt->fetchAll();

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'review') {
    requireLogin();
    if (!verifyCsrf()) {
        setFlash('danger', 'Invalid request.');
    } else {
        $rating  = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $comment = trim($_POST['comment'] ?? '');

        if (empty($comment)) {
            setFlash('warning', 'Please write a review comment.');
        } else {
            $ins = $db->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (:uid, :pid, :r, :c)");
            $ins->execute([
                ':uid' => currentUserId(),
                ':pid' => $product['id'],
                ':r'   => $rating,
                ':c'   => $comment,
            ]);
            setFlash('success', 'Your review has been submitted and is pending approval.');
            redirect('product.php?slug=' . $product['slug']);
        }
    }
}

// Check if in wishlist
$inWishlist = false;
if (isLoggedIn()) {
    $wStmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = :uid AND product_id = :pid");
    $wStmt->execute([':uid' => currentUserId(), ':pid' => $product['id']]);
    $inWishlist = (bool)$wStmt->fetch();
}

$pageTitle = $product['name'];
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url() ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= url('category.php?slug=' . e($product['category_slug'])) ?>"><?= e($product['category_name']) ?></a></li>
            <li class="breadcrumb-item active"><?= e(truncate($product['name'], 40)) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Product Image -->
        <div class="col-md-5">
            <div class="bg-white rounded shadow-sm p-3 text-center">
                <img src="<?= productImage($product['image']) ?>" 
                     alt="<?= e($product['name']) ?>" 
                     class="img-fluid rounded" style="max-height: 400px; object-fit: contain;">
            </div>
        </div>

        <!-- Product Info -->
        <div class="col-md-7">
            <span class="badge bg-primary mb-2"><?= e($product['category_name']) ?></span>
            <h2 class="fw-bold"><?= e($product['name']) ?></h2>

            <!-- Rating -->
            <?php if ($avgRating > 0): ?>
            <div class="mb-2">
                <?= renderStars((int)round($avgRating)) ?>
                <span class="text-muted ms-1">(<?= $avgRating ?> / 5 — <?= count($reviews) ?> review<?= count($reviews) !== 1 ? 's' : '' ?>)</span>
            </div>
            <?php endif; ?>

            <!-- Price -->
            <div class="mb-3">
                <span class="fs-3 fw-bold text-primary"><?= formatPrice($effectivePrice) ?></span>
                <?php if ($hasDiscount): ?>
                    <span class="text-muted text-decoration-line-through ms-2 fs-5"><?= formatPrice($product['price']) ?></span>
                    <span class="badge bg-danger ms-2">Save <?= round((1 - $product['sale_price'] / $product['price']) * 100) ?>%</span>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <p class="text-muted"><?= nl2br(e($product['description'])) ?></p>

            <!-- Stock -->
            <p class="mb-3">
                <?php if ($product['stock'] > 0): ?>
                    <span class="text-success"><i class="bi bi-check-circle"></i> In Stock (<?= $product['stock'] ?> available)</span>
                <?php else: ?>
                    <span class="text-danger"><i class="bi bi-x-circle"></i> Out of Stock</span>
                <?php endif; ?>
            </p>

            <!-- Add to Cart -->
            <?php if ($product['stock'] > 0): ?>
            <form action="<?= url('cart.php') ?>" method="POST" class="d-flex align-items-center gap-3 mb-3">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                <?= csrfField() ?>
                <div class="input-group" style="width: 140px;">
                    <button type="button" class="btn btn-outline-secondary" id="qty-minus-detail">−</button>
                    <input type="number" name="quantity" id="qty-input-detail" class="form-control text-center" value="1" min="1" max="<?= $product['stock'] ?>">
                    <button type="button" class="btn btn-outline-secondary" id="qty-plus-detail">+</button>
                </div>
                <button type="submit" class="btn btn-dark px-4">
                    <i class="bi bi-cart-plus"></i> Add to Cart
                </button>
            </form>
            <?php endif; ?>

            <!-- Wishlist -->
            <?php if (isLoggedIn()): ?>
            <form action="<?= url('wishlist.php') ?>" method="POST" class="d-inline">
                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                <input type="hidden" name="action" value="<?= $inWishlist ? 'remove' : 'add' ?>">
                <?= csrfField() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi <?= $inWishlist ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                    <?= $inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist' ?>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reviews Section -->
    <div class="mt-5">
        <h4 class="section-title">Customer Reviews (<?= count($reviews) ?>)</h4>

        <?php if (empty($reviews)): ?>
            <p class="text-muted">No reviews yet. Be the first to review this product!</p>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong><?= e($review['user_name']) ?></strong>
                            <span class="ms-2"><?= renderStars($review['rating']) ?></span>
                        </div>
                        <small class="text-muted"><?= date('M d, Y', strtotime($review['created_at'])) ?></small>
                    </div>
                    <p class="mb-0"><?= nl2br(e($review['comment'])) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Review Form -->
        <?php if (isLoggedIn()): ?>
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body">
                <h5 class="card-title">Write a Review</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="review">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label">Rating</label>
                        <select name="rating" class="form-select" style="width: auto;">
                            <option value="5">★★★★★ (5)</option>
                            <option value="4">★★★★☆ (4)</option>
                            <option value="3">★★★☆☆ (3)</option>
                            <option value="2">★★☆☆☆ (2)</option>
                            <option value="1">★☆☆☆☆ (1)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Your Review</label>
                        <textarea name="comment" class="form-control" rows="3" required placeholder="Share your experience..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </form>
            </div>
        </div>
        <?php else: ?>
            <p class="mt-3"><a href="<?= url('login.php') ?>">Log in</a> to write a review.</p>
        <?php endif; ?>
    </div>

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
    <div class="mt-5">
        <h4 class="section-title">Related Products</h4>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
            <?php foreach ($relatedProducts as $product): ?>
                <?php include __DIR__ . '/includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
