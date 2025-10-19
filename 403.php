<?php
require_once 'utils/Session.php';

// Start session
Session::start();

// Set page title
$page_title = "Access Forbidden";

// Include header
include_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="error-template">
                <h1 class="display-1">403</h1>
                <h2>Access Forbidden</h2>
                <div class="error-details mb-4">
                    Sorry, you don't have permission to access this page.
                </div>
                <div class="error-actions">
                    <a href="index.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-home me-2"></i>Back to Home
                    </a>
                    <?php if (!Session::isLoggedIn()): ?>
                        <a href="login.php" class="btn btn-outline-primary btn-lg ms-2">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>