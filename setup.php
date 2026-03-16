<?php
/**
 * First-Run Setup Script
 * 
 * Run this once after importing database.sql to fix password hashes.
 * This ensures the default credentials work correctly on your PHP version.
 * 
 * Usage:
 *   1. Import sql/database.sql into your MySQL database
 *   2. Visit: http://localhost/php-ecommerce-website/setup.php
 *   3. Delete this file after setup is complete
 */
require_once __DIR__ . '/includes/init.php';

echo '<!DOCTYPE html><html><head><title>Setup</title>';
echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">';
echo '</head><body class="bg-light"><div class="container py-5" style="max-width:600px;">';

$db = getDB();

try {
    // Hash passwords properly
    $adminHash = password_hash('Admin@123', PASSWORD_DEFAULT);
    $userHash  = password_hash('User@123', PASSWORD_DEFAULT);

    // Update admin password
    $stmt = $db->prepare("UPDATE admins SET password = :pass WHERE email = 'admin@example.com'");
    $stmt->execute([':pass' => $adminHash]);
    $adminUpdated = $stmt->rowCount();

    // Update user password
    $stmt = $db->prepare("UPDATE users SET password = :pass WHERE email = 'user@example.com'");
    $stmt->execute([':pass' => $userHash]);
    $userUpdated = $stmt->rowCount();

    echo '<div class="card border-success">';
    echo '<div class="card-body text-center">';
    echo '<h2 class="text-success mb-3">✅ Setup Complete!</h2>';
    echo '<p>Password hashes have been updated for your PHP version.</p>';
    echo '<table class="table table-bordered mt-3 text-start">';
    echo '<thead><tr><th>Role</th><th>Email</th><th>Password</th><th>Status</th></tr></thead>';
    echo '<tbody>';
    echo '<tr><td>Admin</td><td><code>admin@example.com</code></td><td><code>Admin@123</code></td>';
    echo '<td class="text-success">' . ($adminUpdated ? '✓ Updated' : '✓ OK') . '</td></tr>';
    echo '<tr><td>User</td><td><code>user@example.com</code></td><td><code>User@123</code></td>';
    echo '<td class="text-success">' . ($userUpdated ? '✓ Updated' : '✓ OK') . '</td></tr>';
    echo '</tbody></table>';
    echo '<div class="alert alert-warning mt-3"><strong>Important:</strong> Delete this <code>setup.php</code> file now for security.</div>';
    echo '<div class="mt-3">';
    echo '<a href="index.php" class="btn btn-primary me-2">Visit Store →</a>';
    echo '<a href="admin/login.php" class="btn btn-dark">Admin Panel →</a>';
    echo '</div>';
    echo '</div></div>';

} catch (PDOException $e) {
    echo '<div class="alert alert-danger">';
    echo '<h4>Database Error</h4>';
    echo '<p>Make sure you have:</p>';
    echo '<ol><li>Created the <code>ecommerce_db</code> database</li>';
    echo '<li>Imported <code>sql/database.sql</code></li>';
    echo '<li>Configured <code>.env</code> with correct credentials</li></ol>';
    echo '<p class="text-muted small">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}

echo '</div></body></html>';
