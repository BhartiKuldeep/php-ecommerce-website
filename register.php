<?php
/**
 * User Registration
 */
require_once __DIR__ . '/includes/init.php';

// Redirect if already logged in
if (isLoggedIn()) { redirect('profile.php'); }

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('danger', 'Invalid request.');
        redirect('register.php');
    }

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    $old = compact('name', 'email');

    // Validate
    if (empty($name))                         $errors[] = 'Name is required.';
    if (empty($email))                        $errors[] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    if (strlen($password) < 6)                $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)               $errors[] = 'Passwords do not match.';

    // Check if email already exists
    if (empty($errors)) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (empty($errors)) {
        $db   = getDB();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :pass)");
        $stmt->execute([':name' => $name, ':email' => $email, ':pass' => $hash]);

        setFlash('success', 'Account created successfully! Please log in.');
        redirect('login.php');
    }
}

$pageTitle = 'Register';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="auth-card">
        <div class="card">
            <div class="card-body p-4">
                <h3 class="text-center fw-bold mb-1">Create Account</h3>
                <p class="text-center text-muted mb-4">Join <?= e(APP_NAME) ?> today</p>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?= e($old['name'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= e($old['email'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirm" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-dark w-100">
                        <i class="bi bi-person-plus me-1"></i> Register
                    </button>
                </form>

                <p class="text-center mt-3 mb-0 small">
                    Already have an account? <a href="<?= url('login.php') ?>">Log In</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
