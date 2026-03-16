<?php
/**
 * Shop Page
 * 
 * Lists all active products with pagination and optional sorting.
 */
require_once __DIR__ . '/includes/init.php';

$db   = getDB();
$page = max(1, (int)($_GET['page'] ?? 1));
$sort = $_GET['sort'] ?? 'newest';

// Sorting options
$orderBy = match($sort) {
    'price_low'  => 'COALESCE(p.sale_price, p.price) ASC',
    'price_high' => 'COALESCE(p.sale_price, p.price) DESC',
    'name_az'    => 'p.name ASC',
    default      => 'p.created_at DESC',
};

// Count total
$countStmt  = $db->query("SELECT COUNT(*) FROM products WHERE is_active = 1");
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalItems / PRODUCTS_PER_PAGE);
$offset     = paginationOffset($page, PRODUCTS_PER_PAGE);

// Fetch products
$stmt = $db->prepare("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.is_active = 1 
    ORDER BY {$orderBy} 
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit',  PRODUCTS_PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$pageTitle = 'Shop';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url() ?>">Home</a></li>
            <li class="breadcrumb-item active">Shop</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="section-title mb-0">All Products</h2>
        <div class="d-flex align-items-center gap-2">
            <label class="form-label mb-0 small text-muted">Sort by:</label>
            <select class="form-select form-select-sm" style="width: auto;" onchange="window.location.href='shop.php?sort='+this.value">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                <option value="name_az" <?= $sort === 'name_az' ? 'selected' : '' ?>>Name: A–Z</option>
            </select>
        </div>
    </div>

    <p class="text-muted small">Showing <?= count($products) ?> of <?= $totalItems ?> products</p>

    <?php if (empty($products)): ?>
        <div class="text-center py-5">
            <i class="bi bi-box-seam display-1 text-muted"></i>
            <p class="mt-3 text-muted">No products found.</p>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($products as $product): ?>
                <?php include __DIR__ . '/includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>

        <div class="mt-4">
            <?= renderPagination($page, $totalPages, 'shop.php') ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
