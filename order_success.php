<?php
/**
 * Order Success Page
 * 
 * Shown after a successful order placement.
 */
require_once __DIR__ . '/includes/init.php';
requireLogin();

$orderNumber = $_SESSION['last_order_number'] ?? null;
if (!$orderNumber) {
    redirect('orders.php');
}

// Clear the session variable (only show once)
unset($_SESSION['last_order_number']);

// Fetch order details
$db   = getDB();
$stmt = $db->prepare("SELECT * FROM orders WHERE order_number = :onum AND user_id = :uid LIMIT 1");
$stmt->execute([':onum' => $orderNumber, ':uid' => currentUserId()]);
$order = $stmt->fetch();

$pageTitle = 'Order Confirmed';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="text-center" style="max-width: 600px; margin: 0 auto;">
        <div class="mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
        </div>
        <h2 class="fw-bold">Order Confirmed!</h2>
        <p class="text-muted fs-5 mt-2">Thank you for your purchase. Your order has been placed successfully.</p>

        <?php if ($order): ?>
        <div class="card border-0 shadow-sm mt-4 text-start">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted">Order Number</small>
                        <p class="fw-bold mb-0"><?= e($order['order_number']) ?></p>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Status</small>
                        <p class="mb-0"><?= statusBadge($order['status']) ?></p>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Total Amount</small>
                        <p class="fw-bold text-primary mb-0"><?= formatPrice($order['total']) ?></p>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Date</small>
                        <p class="mb-0"><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="mt-4 d-flex justify-content-center gap-3">
            <a href="<?= url('orders.php') ?>" class="btn btn-primary">
                <i class="bi bi-box-seam me-1"></i> View My Orders
            </a>
            <a href="<?= url('shop.php') ?>" class="btn btn-outline-secondary">
                Continue Shopping
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
