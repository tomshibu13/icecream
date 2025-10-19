<?php
require_once 'utils/Session.php';

// Start session
Session::start();

// Set page title
$page_title = "Page Not Found";

// Include header
include_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="error-template">
                <h1 class="display-1">404</h1>
                <h2>Page Not Found</h2>
                <div class="error-details mb-4">
                    Sorry, the page you requested could not be found.
                </div>
                <div class="error-actions">
                    <a href="index.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-home me-2"></i>Back to Home
                    </a>
                    <a href="products.php" class="btn btn-outline-primary btn-lg ms-2">
                        <i class="fas fa-ice-cream me-2"></i>Browse Products
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>