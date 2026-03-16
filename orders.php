<?php
/**
 * User Order History
 */
require_once __DIR__ . '/includes/init.php';
requireLogin();

$db   = getDB();
$page = max(1, (int)($_GET['page'] ?? 1));

$countStmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :uid");
$countStmt->execute([':uid' => currentUserId()]);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalItems / ORDERS_PER_PAGE);
$offset     = paginationOffset($page, ORDERS_PER_PAGE);

$stmt = $db->prepare("
    SELECT * FROM orders 
    WHERE user_id = :uid 
    ORDER BY created_at DESC 
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':uid',    currentUserId(), PDO::PARAM_INT);
$stmt->bindValue(':limit',  ORDERS_PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();

$pageTitle = 'My Orders';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url() ?>">Home</a></li>
            <li class="breadcrumb-item active">My Orders</li>
        </ol>
    </nav>

    <h2 class="section-title">My Orders</h2>

    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <i class="bi bi-box-seam display-1 text-muted"></i>
            <h4 class="mt-3">No orders yet</h4>
            <p class="text-muted">Start shopping and your orders will appear here.</p>
            <a href="<?= url('shop.php') ?>" class="btn btn-primary">Shop Now</a>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong><?= e($order['order_number']) ?></strong></td>
                            <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                            <td class="fw-semibold"><?= formatPrice($order['total']) ?></td>
                            <td><?= statusBadge($order['status']) ?></td>
                            <td>
                                <a href="<?= url('order_details.php?id=' . $order['id']) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <?= renderPagination($page, $totalPages, 'orders.php') ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
