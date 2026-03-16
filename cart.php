<?php
/**
 * Shopping Cart
 * 
 * Handles add, update, remove actions and displays cart contents.
 */
require_once __DIR__ . '/includes/init.php';

// ── Handle POST actions ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);

    // CSRF check for add action (cart updates use simpler flow)
    if ($action === 'add' && !verifyCsrf()) {
        setFlash('danger', 'Invalid request.');
        redirect('cart.php');
    }

    switch ($action) {
        case 'add':
            $qty = max(1, (int)($_POST['quantity'] ?? 1));
            // Verify product exists and is in stock
            $db   = getDB();
            $stmt = $db->prepare("SELECT id, stock FROM products WHERE id = :id AND is_active = 1");
            $stmt->execute([':id' => $productId]);
            $prod = $stmt->fetch();

            if ($prod && $prod['stock'] > 0) {
                addToCart($productId, $qty);
                setFlash('success', 'Product added to cart!');
            } else {
                setFlash('danger', 'Product is unavailable.');
            }
            redirect('cart.php');
            break;

        case 'update':
            $qty = max(0, (int)($_POST['quantity'] ?? 0));
            updateCartItem($productId, $qty);
            setFlash('info', 'Cart updated.');
            redirect('cart.php');
            break;

        case 'remove':
            removeFromCart($productId);
            setFlash('info', 'Item removed from cart.');
            redirect('cart.php');
            break;

        case 'clear':
            clearCart();
            setFlash('info', 'Cart cleared.');
            redirect('cart.php');
            break;
    }
}

// ── Display Cart ────────────────────────────────────────
$cartItems = getCartItems();
$subtotal  = cartSubtotal();
$shipping  = cartShipping($subtotal);
$total     = $subtotal + $shipping;

$pageTitle = 'Shopping Cart';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url() ?>">Home</a></li>
            <li class="breadcrumb-item active">Cart</li>
        </ol>
    </nav>

    <h2 class="section-title">Shopping Cart</h2>

    <?php if (empty($cartItems)): ?>
        <div class="text-center py-5">
            <i class="bi bi-cart-x display-1 text-muted"></i>
            <h4 class="mt-3">Your cart is empty</h4>
            <p class="text-muted">Looks like you haven't added anything to your cart yet.</p>
            <a href="<?= url('shop.php') ?>" class="btn btn-primary mt-2">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $item):
                                        $price = $item['sale_price'] ?? $item['price'];
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?= productImage($item['image']) ?>" alt="" class="cart-img">
                                                <div>
                                                    <a href="<?= url('product.php?slug=' . e($item['slug'])) ?>" class="text-decoration-none fw-semibold text-dark">
                                                        <?= e(truncate($item['name'], 40)) ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= formatPrice($price) ?></td>
                                        <td>
                                            <form method="POST" class="cart-update-form">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                                <div class="input-group input-group-sm" style="width: 120px;">
                                                    <button type="button" class="btn btn-outline-secondary qty-btn qty-minus">−</button>
                                                    <input type="number" name="quantity" class="form-control qty-input" value="<?= $item['cart_qty'] ?>" min="1" max="<?= $item['stock'] ?>">
                                                    <button type="button" class="btn btn-outline-secondary qty-btn qty-plus">+</button>
                                                </div>
                                            </form>
                                        </td>
                                        <td class="fw-semibold"><?= formatPrice($item['line_total']) ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <a href="<?= url('shop.php') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Continue Shopping
                    </a>
                    <form method="POST">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn-outline-danger" data-confirm="Clear all items from cart?">
                            <i class="bi bi-trash"></i> Clear Cart
                        </button>
                    </form>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-3">Order Summary</h5>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span><?= formatPrice($subtotal) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Shipping</span>
                            <span><?= $shipping > 0 ? formatPrice($shipping) : '<span class="text-success">Free</span>' ?></span>
                        </div>
                        <?php if ($shipping > 0): ?>
                        <small class="text-muted d-block mb-2">
                            Free shipping on orders over <?= formatPrice(FREE_SHIPPING_THRESHOLD) ?>
                        </small>
                        <?php endif; ?>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Total</span>
                            <span class="text-primary"><?= formatPrice($total) ?></span>
                        </div>

                        <a href="<?= url('checkout.php') ?>" class="btn btn-dark w-100 mt-3">
                            Proceed to Checkout <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
