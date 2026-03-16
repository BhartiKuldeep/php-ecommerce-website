<?php
/**
 * Category Page
 * 
 * Displays products belonging to a specific category.
 */
require_once __DIR__ . '/includes/init.php';

$slug = $_GET['slug'] ?? '';
if (empty($slug)) { redirect('shop.php'); }

$db = getDB();

// Fetch category
$catStmt = $db->prepare("SELECT * FROM categories WHERE slug = :slug AND is_active = 1 LIMIT 1");
$catStmt->execute([':slug' => $slug]);
$category = $catStmt->fetch();

if (!$category) {
    setFlash('warning', 'Category not found.');
    redirect('shop.php');
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));

$countStmt = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id = :cid AND is_active = 1");
$countStmt->execute([':cid' => $category['id']]);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalItems / PRODUCTS_PER_PAGE);
$offset     = paginationOffset($page, PRODUCTS_PER_PAGE);

$stmt = $db->prepare("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.category_id = :cid AND p.is_active = 1 
    ORDER BY p.created_at DESC 
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':cid',    $category['id'], PDO::PARAM_INT);
$stmt->bindValue(':limit',  PRODUCTS_PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$pageTitle = $category['name'];
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url() ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= url('shop.php') ?>">Shop</a></li>
            <li class="breadcrumb-item active"><?= e($category['name']) ?></li>
        </ol>
    </nav>

    <h2 class="section-title"><?= e($category['name']) ?></h2>
    <?php if ($category['description']): ?>
        <p class="text-muted"><?= e($category['description']) ?></p>
    <?php endif; ?>

    <p class="text-muted small"><?= $totalItems ?> product<?= $totalItems !== 1 ? 's' : '' ?> found</p>

    <?php if (empty($products)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <p class="mt-3 text-muted">No products in this category yet.</p>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($products as $product): ?>
                <?php include __DIR__ . '/includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
        <div class="mt-4">
            <?= renderPagination($page, $totalPages, 'category.php?slug=' . e($slug)) ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
