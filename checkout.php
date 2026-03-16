<?php
/**
 * Checkout Page
 * 
 * Collects shipping info, applies coupons, and places orders.
 */
require_once __DIR__ . '/includes/init.php';
requireLogin();

$db        = getDB();
$cartItems = getCartItems();

if (empty($cartItems)) {
    setFlash('warning', 'Your cart is empty.');
    redirect('cart.php');
}

$subtotal = cartSubtotal();
$shipping = cartShipping($subtotal);
$discount = 0.00;
$couponId = null;

// ── Handle coupon application ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    $code = strtoupper(trim($_POST['coupon_code'] ?? ''));
    if (!empty($code)) {
        $cStmt = $db->prepare("SELECT * FROM coupons WHERE code = :code AND is_active = 1 LIMIT 1");
        $cStmt->execute([':code' => $code]);
        $coupon = $cStmt->fetch();

        if (!$coupon) {
            setFlash('danger', 'Invalid coupon code.');
        } elseif ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
            setFlash('danger', 'This coupon has expired.');
        } elseif ($coupon['max_uses'] > 0 && $coupon['used_count'] >= $coupon['max_uses']) {
            setFlash('danger', 'This coupon has been fully redeemed.');
        } elseif ($subtotal < $coupon['min_order']) {
            setFlash('danger', 'Minimum order of ' . formatPrice($coupon['min_order']) . ' required for this coupon.');
        } else {
            $_SESSION['coupon'] = $coupon;
            setFlash('success', 'Coupon applied!');
        }
    }
    redirect('checkout.php');
}

// Remove coupon
if (isset($_GET['remove_coupon'])) {
    unset($_SESSION['coupon']);
    setFlash('info', 'Coupon removed.');
    redirect('checkout.php');
}

// Apply stored coupon
if (isset($_SESSION['coupon'])) {
    $coupon   = $_SESSION['coupon'];
    $couponId = $coupon['id'];
    if ($coupon['discount_type'] === 'percentage') {
        $discount = round($subtotal * ($coupon['discount_value'] / 100), 2);
    } else {
        $discount = min($coupon['discount_value'], $subtotal);
    }
}

$total = $subtotal - $discount + $shipping;
$user  = currentUser();

// ── Handle order placement ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!verifyCsrf()) {
        setFlash('danger', 'Invalid request. Please try again.');
        redirect('checkout.php');
    }

    // Validate shipping fields
    $fields = ['shipping_name', 'shipping_email', 'shipping_phone', 'shipping_address', 'shipping_city', 'shipping_state', 'shipping_zip'];
    $errors = [];
    $data   = [];
    foreach ($fields as $f) {
        $val = trim($_POST[$f] ?? '');
        if (empty($val)) {
            $errors[] = ucwords(str_replace('_', ' ', str_replace('shipping_', '', $f))) . ' is required.';
        }
        $data[$f] = $val;
    }

    if (!filter_var($data['shipping_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!empty($errors)) {
        setFlash('danger', implode('<br>', $errors));
        redirect('checkout.php');
    }

    $paymentMethod = $_POST['payment_method'] ?? 'cod';
    $notes         = trim($_POST['notes'] ?? '');
    $orderNumber   = generateOrderNumber();

    try {
        $db->beginTransaction();

        // Insert order
        $orderStmt = $db->prepare("
            INSERT INTO orders (user_id, coupon_id, order_number, subtotal, discount, shipping_cost, total, status,
                shipping_name, shipping_email, shipping_phone, shipping_address, shipping_city, shipping_state, shipping_zip, notes)
            VALUES (:uid, :cid, :onum, :sub, :disc, :ship, :total, 'pending',
                :sname, :semail, :sphone, :saddr, :scity, :sstate, :szip, :notes)
        ");
        $orderStmt->execute([
            ':uid'    => currentUserId(),
            ':cid'    => $couponId,
            ':onum'   => $orderNumber,
            ':sub'    => $subtotal,
            ':disc'   => $discount,
            ':ship'   => $shipping,
            ':total'  => $total,
            ':sname'  => $data['shipping_name'],
            ':semail' => $data['shipping_email'],
            ':sphone' => $data['shipping_phone'],
            ':saddr'  => $data['shipping_address'],
            ':scity'  => $data['shipping_city'],
            ':sstate' => $data['shipping_state'],
            ':szip'   => $data['shipping_zip'],
            ':notes'  => $notes,
        ]);
        $orderId = $db->lastInsertId();

        // Insert order items & reduce stock
        $itemStmt  = $db->prepare("INSERT INTO order_items (order_id, product_id, name, price, quantity, total) VALUES (:oid, :pid, :name, :price, :qty, :total)");
        $stockStmt = $db->prepare("UPDATE products SET stock = stock - :qty WHERE id = :pid AND stock >= :qty");

        foreach ($cartItems as $item) {
            $price    = $item['sale_price'] ?? $item['price'];
            $lineTotal = $price * $item['cart_qty'];

            $itemStmt->execute([
                ':oid'   => $orderId,
                ':pid'   => $item['id'],
                ':name'  => $item['name'],
                ':price' => $price,
                ':qty'   => $item['cart_qty'],
                ':total' => $lineTotal,
            ]);

            $stockStmt->execute([':qty' => $item['cart_qty'], ':pid' => $item['id']]);
        }

        // Insert payment record
        $payStmt = $db->prepare("INSERT INTO payments (order_id, payment_method, payment_status, amount) VALUES (:oid, :method, :status, :amount)");
        $payStmt->execute([
            ':oid'    => $orderId,
            ':method' => $paymentMethod,
            ':status' => ($paymentMethod === 'cod') ? 'pending' : 'pending',
            ':amount' => $total,
        ]);

        // Update coupon usage
        if ($couponId) {
            $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = :id")->execute([':id' => $couponId]);
        }

        $db->commit();

        // Clear cart and coupon session
        clearCart();
        unset($_SESSION['coupon']);

        // Redirect to success page
        $_SESSION['last_order_number'] = $orderNumber;
        redirect('order_success.php');

    } catch (Exception $e) {
        $db->rollBack();
        setFlash('danger', 'Something went wrong. Please try again.');
        redirect('checkout.php');
    }
}

$pageTitle = 'Checkout';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url() ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= url('cart.php') ?>">Cart</a></li>
            <li class="breadcrumb-item active">Checkout</li>
        </ol>
    </nav>

    <h2 class="section-title">Checkout</h2>

    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="place_order" value="1">

        <div class="row g-4">
            <!-- Shipping Info -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-3"><i class="bi bi-truck me-2"></i>Shipping Information</h5>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="shipping_name" class="form-control" value="<?= e($user['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="shipping_email" class="form-control" value="<?= e($user['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="text" name="shipping_phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ZIP Code <span class="text-danger">*</span></label>
                                <input type="text" name="shipping_zip" class="form-control" value="<?= e($user['zip'] ?? '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address <span class="text-danger">*</span></label>
                                <textarea name="shipping_address" class="form-control" rows="2" required><?= e($user['address'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">City <span class="text-danger">*</span></label>
                                <input type="text" name="shipping_city" class="form-control" value="<?= e($user['city'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">State <span class="text-danger">*</span></label>
                                <input type="text" name="shipping_state" class="form-control" value="<?= e($user['state'] ?? '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Order Notes (optional)</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Special delivery instructions..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-3"><i class="bi bi-credit-card me-2"></i>Payment Method</h5>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" value="cod" id="pm-cod" checked>
                            <label class="form-check-label" for="pm-cod">
                                <i class="bi bi-cash-stack me-1"></i> Cash on Delivery
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" value="card" id="pm-card">
                            <label class="form-check-label" for="pm-card">
                                <i class="bi bi-credit-card-2-front me-1"></i> Credit/Debit Card
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" value="upi" id="pm-upi">
                            <label class="form-check-label" for="pm-upi">
                                <i class="bi bi-phone me-1"></i> UPI
                            </label>
                        </div>
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-info-circle"></i> Online payment integration is for demo purposes. Orders are saved with selected method.
                        </small>
                    </div>
                </div>
            </div>

            <!-- Order Summary Sidebar -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-3">Order Summary</h5>

                        <?php foreach ($cartItems as $item): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= productImage($item['image']) ?>" alt="" class="rounded" style="width:40px; height:40px; object-fit:cover;">
                                <div>
                                    <small class="d-block"><?= e(truncate($item['name'], 30)) ?></small>
                                    <small class="text-muted">x<?= $item['cart_qty'] ?></small>
                                </div>
                            </div>
                            <span class="fw-semibold small"><?= formatPrice($item['line_total']) ?></span>
                        </div>
                        <?php endforeach; ?>

                        <hr>

                        <!-- Coupon -->
                        <?php if (isset($_SESSION['coupon'])): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-success small">
                                    <i class="bi bi-tag"></i> <?= e($_SESSION['coupon']['code']) ?>
                                </span>
                                <a href="<?= url('checkout.php?remove_coupon=1') ?>" class="text-danger small">Remove</a>
                            </div>
                        <?php else: ?>
                            <form method="POST" class="mb-3" id="coupon-form">
                                <div class="input-group input-group-sm">
                                    <input type="text" name="coupon_code" class="form-control" placeholder="Coupon code">
                                    <button type="submit" name="apply_coupon" value="1" class="btn btn-outline-primary">Apply</button>
                                </div>
                            </form>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Subtotal</span>
                            <span><?= formatPrice($subtotal) ?></span>
                        </div>
                        <?php if ($discount > 0): ?>
                        <div class="d-flex justify-content-between mb-1 text-success">
                            <span>Discount</span>
                            <span>-<?= formatPrice($discount) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Shipping</span>
                            <span><?= $shipping > 0 ? formatPrice($shipping) : '<span class="text-success">Free</span>' ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                            <span>Total</span>
                            <span class="text-primary"><?= formatPrice($total) ?></span>
                        </div>

                        <button type="submit" class="btn btn-dark btn-lg w-100">
                            <i class="bi bi-lock"></i> Place Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
