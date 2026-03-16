<?php
/**
 * Order Details
 * 
 * Detailed view of a single order with status timeline.
 */
require_once __DIR__ . '/includes/init.php';
requireLogin();

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) { redirect('orders.php'); }

$db = getDB();

// Fetch order (ensure it belongs to current user)
$stmt = $db->prepare("SELECT * FROM orders WHERE id = :id AND user_id = :uid LIMIT 1");
$stmt->execute([':id' => $orderId, ':uid' => currentUserId()]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('danger', 'Order not found.');
    redirect('orders.php');
}

// Fetch order items
$itemsStmt = $db->prepare("
    SELECT oi.*, p.slug, p.image 
    FROM order_items oi 
    LEFT JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = :oid
");
$itemsStmt->execute([':oid' => $orderId]);
$items = $itemsStmt->fetchAll();

// Fetch payment info
$payStmt = $db->prepare("SELECT * FROM payments WHERE order_id = :oid LIMIT 1");
$payStmt->execute([':oid' => $orderId]);
$payment = $payStmt->fetch();

// Status timeline steps
$statuses   = ['pending', 'confirmed', 'packed', 'shipped', 'delivered'];
$currentIdx = array_search($order['status'], $statuses);
$isCancelled = ($order['status'] === 'cancelled');

$pageTitle = 'Order #' . $order['order_number'];
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url() ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= url('orders.php') ?>">My Orders</a></li>
            <li class="breadcrumb-item active"><?= e($order['order_number']) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h2 class="section-title mb-0">Order <?= e($order['order_number']) ?></h2>
        <span class="fs-5"><?= statusBadge($order['status']) ?></span>
    </div>

    <!-- Status Timeline -->
    <?php if (!$isCancelled): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="status-timeline">
                <?php foreach ($statuses as $idx => $s): ?>
                <div class="status-step <?= ($idx < $currentIdx) ? 'completed' : '' ?> <?= ($idx === $currentIdx) ? 'active' : '' ?>">
                    <div class="dot">
                        <?php if ($idx < $currentIdx): ?>
                            <i class="bi bi-check"></i>
                        <?php elseif ($idx === $currentIdx): ?>
                            <i class="bi bi-circle-fill" style="font-size:.4rem;"></i>
                        <?php endif; ?>
                    </div>
                    <small><?= ucfirst($s) ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-danger mb-4">
        <i class="bi bi-x-circle me-2"></i> This order has been cancelled.
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Order Items -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-3">Order Items</h5>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?= productImage($item['image'] ?? null) ?>" alt="" class="cart-img">
                                            <a href="<?= url('product.php?slug=' . e($item['slug'] ?? '')) ?>" class="text-decoration-none">
                                                <?= e($item['name']) ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td><?= formatPrice($item['price']) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td class="fw-semibold"><?= formatPrice($item['total']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Payment Summary</h6>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Subtotal</span>
                        <span><?= formatPrice($order['subtotal']) ?></span>
                    </div>
                    <?php if ($order['discount'] > 0): ?>
                    <div class="d-flex justify-content-between mb-1 text-success">
                        <span>Discount</span>
                        <span>-<?= formatPrice($order['discount']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Shipping</span>
                        <span><?= $order['shipping_cost'] > 0 ? formatPrice($order['shipping_cost']) : 'Free' ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total</span>
                        <span class="text-primary"><?= formatPrice($order['total']) ?></span>
                    </div>
                    <?php if ($payment): ?>
                    <div class="mt-2 small text-muted">
                        Payment: <?= ucfirst(e($payment['payment_method'])) ?> — 
                        <span class="badge bg-<?= $payment['payment_status'] === 'completed' ? 'success' : 'warning' ?>">
                            <?= ucfirst($payment['payment_status']) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Shipping Address</h6>
                    <p class="mb-1"><?= e($order['shipping_name']) ?></p>
                    <p class="mb-1 text-muted small"><?= e($order['shipping_address']) ?></p>
                    <p class="mb-1 text-muted small"><?= e($order['shipping_city']) ?>, <?= e($order['shipping_state']) ?> <?= e($order['shipping_zip']) ?></p>
                    <p class="mb-1 text-muted small"><i class="bi bi-telephone me-1"></i><?= e($order['shipping_phone']) ?></p>
                    <p class="mb-0 text-muted small"><i class="bi bi-envelope me-1"></i><?= e($order['shipping_email']) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
