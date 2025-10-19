<?php
require_once 'config/database.php';
require_once 'models/Product.php';
require_once 'models/Cart.php';
require_once 'utils/Session.php';
require_once 'includes/header.php';

// Start session
Session::start();

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize product object
$product = new Product($db);

// Get product ID from URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $product->id = $_GET['id'];
    $product->readOne();
} else {
    // Redirect to products page if no ID provided
    header("Location: products.php");
    exit;
}

// Process add to cart
$message = "";
if (isset($_POST['add_to_cart']) && Session::isLoggedIn()) {
    // Initialize cart object
    $cart = new Cart($db);
    
    // Set cart properties
    $cart->user_id = Session::getUserId();
    $cart->product_id = $product->id;
    $cart->quantity = isset($_POST['quantity']) ? $_POST['quantity'] : 1;
    
    // Check if product is in stock
    if ($product->stock < $cart->quantity) {
        $message = "<div class='alert alert-danger'>Sorry, only {$product->stock} items available in stock.</div>";
    } else {
        // Add to cart
        if ($cart->addToCart()) {
            $message = "<div class='alert alert-success'>Product added to cart successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Unable to add product to cart.</div>";
        }
    }
} else if (isset($_POST['add_to_cart']) && !Session::isLoggedIn()) {
    // Redirect to login page if not logged in
    Session::setFlash('error', 'Please login to add products to cart.');
    header("Location: login.php");
    exit;
}
?>

<div class="row">
    <!-- Product Image -->
    <div class="col-md-5 mb-4">
        <img src="assets/images/<?php echo $product->image; ?>" class="img-fluid rounded" alt="<?php echo $product->name; ?>">
    </div>
    
    <!-- Product Details -->
    <div class="col-md-7">
        <?php echo $message; ?>
        
        <h2><?php echo $product->name; ?></h2>
        <p class="text-muted">Category: <?php echo $product->category; ?></p>
        
        <div class="mb-3">
            <h3 class="text-danger">$<?php echo number_format($product->price, 2); ?></h3>
        </div>
        
        <div class="mb-4">
            <h5>Description:</h5>
            <p><?php echo $product->description; ?></p>
        </div>
        
        <div class="mb-3">
            <p>
                <strong>Availability:</strong> 
                <?php if ($product->stock > 0): ?>
                <span class="text-success">In Stock (<?php echo $product->stock; ?> available)</span>
                <?php else: ?>
                <span class="text-danger">Out of Stock</span>
                <?php endif; ?>
            </p>
        </div>
        
        <?php if ($product->stock > 0): ?>
        <form action="" method="post">
            <div class="row align-items-center mb-4">
                <div class="col-md-3">
                    <label for="quantity" class="form-label">Quantity:</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product->stock; ?>">
                </div>
                <div class="col-md-9">
                    <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg add-to-cart-btn">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="products.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>