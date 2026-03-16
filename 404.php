<?php
/**
 * 404 — Page Not Found
 */
require_once __DIR__ . '/includes/init.php';
http_response_code(404);

$pageTitle = 'Page Not Found';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5 text-center">
    <div style="font-size: 8rem; line-height: 1; color: #e5e7eb;">404</div>
    <h2 class="fw-bold mt-3">Page Not Found</h2>
    <p class="text-muted">The page you're looking for doesn't exist or has been moved.</p>
    <a href="<?= url() ?>" class="btn btn-primary mt-2">
        <i class="bi bi-house me-1"></i> Back to Home
    </a>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
