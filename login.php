<?php
/**
 * User Login
 */
require_once __DIR__ . '/includes/init.php';

if (isLoggedIn()) { redirect('profile.php'); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('danger', 'Invalid request.');
        redirect('login.php');
    }

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = 'Please fill in all fields.';
    }

    if (empty($errors)) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            session_regenerate_id(true);

            setFlash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect('index.php');
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="auth-card">
        <div class="card">
            <div class="card-body p-4">
                <h3 class="text-center fw-bold mb-1">Welcome Back</h3>
                <p class="text-center text-muted mb-4">Log in to your account</p>

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
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-dark w-100">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Log In
                    </button>
                </form>

                <p class="text-center mt-3 mb-0 small">
                    Don't have an account? <a href="<?= url('register.php') ?>">Register</a>
                </p>

                <hr>
                <div class="text-center small text-muted">
                    <strong>Demo Credentials:</strong><br>
                    Email: <code>user@example.com</code> &nbsp;|&nbsp; Password: <code>User@123</code>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
