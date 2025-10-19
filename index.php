<?php
require_once 'config/database.php';
require_once 'models/Product.php';
require_once 'includes/header.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize product object
$product = new Product($db);

// Read featured products (latest 4 products)
$stmt = $product->readAll();
$products = [];
$count = 0;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($count < 4) {
        $products[] = $row;
    }
    $count++;
}
?>

<!-- Hero Section -->
<section class="hero text-center">
    <div class="container">
        <h1>Delicious Ice Cream</h1>
        <p class="lead">Handcrafted with premium ingredients for the perfect treat</p>
        <a href="products.php" class="btn btn-primary btn-lg">Shop Now</a>
    </div>
</section>

<!-- Featured Products -->
<section>
    <div class="container">
        <h2 class="text-center mb-4">Featured Flavors</h2>
        <div class="row">
            <?php foreach ($products as $product): ?>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card product-card">
                    <img src="assets/images/<?php echo $product['image']; ?>" class="card-img-top" alt="<?php echo $product['name']; ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $product['name']; ?></h5>
                        <p class="card-text"><?php echo substr($product['description'], 0, 60); ?>...</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="price">$<?php echo number_format($product['price'], 2); ?></span>
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="products.php" class="btn btn-primary">View All Products</a>
        </div>
    </div>
</section>

<!-- Features -->
<section class="py-5 bg-light mt-5">
    <div class="container">
        <h2 class="text-center mb-4">Why Choose Us</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="text-center">
                    <i class="fas fa-ice-cream fa-3x text-primary mb-3"></i>
                    <h4>Premium Ingredients</h4>
                    <p>We use only the finest ingredients to create our delicious ice cream flavors.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="text-center">
                    <i class="fas fa-shipping-fast fa-3x text-primary mb-3"></i>
                    <h4>Fast Delivery</h4>
                    <p>Quick delivery to ensure your ice cream arrives in perfect condition.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="text-center">
                    <i class="fas fa-medal fa-3x text-primary mb-3"></i>
                    <h4>Award Winning</h4>
                    <p>Our ice cream has won multiple awards for taste and quality.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>