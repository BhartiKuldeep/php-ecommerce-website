<?php
/**
 * Search Results
 */
require_once __DIR__ . '/includes/init.php';

$query = trim($_GET['q'] ?? '');
$products = [];

if (!empty($query)) {
    $db    = getDB();
    $like  = '%' . $query . '%';
    $stmt  = $db->prepare("
        SELECT p.*, c.name AS category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.is_active = 1 AND (p.name LIKE :q1 OR p.description LIKE :q2 OR c.name LIKE :q3)
        ORDER BY p.name ASC 
        LIMIT 50
    ");
    $stmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like]);
    $products = $stmt->fetchAll();
}

$pageTitle = 'Search: ' . $query;
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url() ?>">Home</a></li>
            <li class="breadcrumb-item active">Search</li>
        </ol>
    </nav>

    <h2 class="section-title">Search Results for "<?= e($query) ?>"</h2>
    <p class="text-muted"><?= count($products) ?> result<?= count($products) !== 1 ? 's' : '' ?> found</p>

    <?php if (empty($query)): ?>
        <div class="text-center py-5">
            <i class="bi bi-search display-1 text-muted"></i>
            <p class="mt-3 text-muted">Enter a search term to find products.</p>
        </div>
    <?php elseif (empty($products)): ?>
        <div class="text-center py-5">
            <i class="bi bi-emoji-frown display-1 text-muted"></i>
            <h4 class="mt-3">No products found</h4>
            <p class="text-muted">Try a different search term.</p>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($products as $product): ?>
                <?php include __DIR__ . '/includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
