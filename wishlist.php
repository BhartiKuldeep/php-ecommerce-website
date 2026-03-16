<?php
/**
 * Wishlist
 * 
 * Manage user's product wishlist.
 */
require_once __DIR__ . '/includes/init.php';
requireLogin();

$db = getDB();

// ── Handle POST actions ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('danger', 'Invalid request.');
        redirect('wishlist.php');
    }

    $action    = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);

    if ($action === 'add' && $productId) {
        $stmt = $db->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (:uid, :pid)");
        $stmt->execute([':uid' => currentUserId(), ':pid' => $productId]);
        setFlash('success', 'Added to wishlist!');
    } elseif ($action === 'remove' && $productId) {
        $stmt = $db->prepare("DELETE FROM wishlist WHERE user_id = :uid AND product_id = :pid");
        $stmt->execute([':uid' => currentUserId(), ':pid' => $productId]);
        setFlash('info', 'Removed from wishlist.');
    }

    // Redirect back to referring page or wishlist
    $referer = $_SERVER['HTTP_REFERER'] ?? url('wishlist.php');
    header('Location: ' . $referer);
    exit;
}

// ── Fetch wishlist items ────────────────────────────────
$stmt = $db->prepare("
    SELECT p.*, c.name AS category_name, w.created_at AS wishlisted_at 
    FROM wishlist w 
    JOIN products p ON w.product_id = p.id 
    JOIN categories c ON p.category_id = c.id 
    WHERE w.user_id = :uid AND p.is_active = 1 
    ORDER BY w.created_at DESC
");
$stmt->execute([':uid' => currentUserId()]);
$wishlistItems = $stmt->fetchAll();

$pageTitle = 'My Wishlist';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url() ?>">Home</a></li>
            <li class="breadcrumb-item active">Wishlist</li>
        </ol>
    </nav>

    <h2 class="section-title">My Wishlist (<?= count($wishlistItems) ?>)</h2>

    <?php if (empty($wishlistItems)): ?>
        <div class="text-center py-5">
            <i class="bi bi-heart display-1 text-muted"></i>
            <h4 class="mt-3">Your wishlist is empty</h4>
            <p class="text-muted">Save items you love to your wishlist.</p>
            <a href="<?= url('shop.php') ?>" class="btn btn-primary">Browse Products</a>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($wishlistItems as $product): ?>
                <?php include __DIR__ . '/includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
