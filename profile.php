<?php
/**
 * User Profile
 * 
 * View and update profile, change password.
 */
require_once __DIR__ . '/includes/init.php';
requireLogin();

$db   = getDB();
$user = currentUser();

// ── Update Profile ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verifyCsrf()) {
        setFlash('danger', 'Invalid request.');
        redirect('profile.php');
    }

    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city    = trim($_POST['city'] ?? '');
    $state   = trim($_POST['state'] ?? '');
    $zip     = trim($_POST['zip'] ?? '');

    if (empty($name)) {
        setFlash('danger', 'Name is required.');
        redirect('profile.php');
    }

    $stmt = $db->prepare("UPDATE users SET name=:name, phone=:phone, address=:addr, city=:city, state=:state, zip=:zip WHERE id=:id");
    $stmt->execute([
        ':name'  => $name,
        ':phone' => $phone,
        ':addr'  => $address,
        ':city'  => $city,
        ':state' => $state,
        ':zip'   => $zip,
        ':id'    => currentUserId(),
    ]);

    setFlash('success', 'Profile updated successfully.');
    redirect('profile.php');
}

// ── Change Password ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verifyCsrf()) {
        setFlash('danger', 'Invalid request.');
        redirect('profile.php');
    }

    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password'])) {
        setFlash('danger', 'Current password is incorrect.');
    } elseif (strlen($new) < 6) {
        setFlash('danger', 'New password must be at least 6 characters.');
    } elseif ($new !== $confirm) {
        setFlash('danger', 'New passwords do not match.');
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $db->prepare("UPDATE users SET password = :pass WHERE id = :id")->execute([':pass' => $hash, ':id' => currentUserId()]);
        setFlash('success', 'Password changed successfully.');
    }
    redirect('profile.php');
}

// Refresh user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => currentUserId()]);
$user = $stmt->fetch();

$pageTitle = 'My Profile';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url() ?>">Home</a></li>
            <li class="breadcrumb-item active">My Profile</li>
        </ol>
    </nav>

    <h2 class="section-title">My Profile</h2>

    <div class="row g-4">
        <!-- Update Profile -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title fw-bold"><i class="bi bi-person me-2"></i>Profile Information</h5>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="update_profile" value="1">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
                                <small class="text-muted">Email cannot be changed.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ZIP Code</label>
                                <input type="text" name="zip" class="form-control" value="<?= e($user['zip'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"><?= e($user['address'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" value="<?= e($user['city'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">State</label>
                                <input type="text" name="state" class="form-control" value="<?= e($user['state'] ?? '') ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary mt-3">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title fw-bold"><i class="bi bi-lock me-2"></i>Change Password</h5>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="change_password" value="1">

                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" minlength="6" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </form>
                </div>
            </div>

            <!-- Account Info Card -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Account Info</h6>
                    <p class="mb-1 small text-muted">Member since</p>
                    <p class="fw-semibold"><?= date('F d, Y', strtotime($user['created_at'])) ?></p>
                    <a href="<?= url('orders.php') ?>" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-box-seam me-1"></i> View Order History
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
