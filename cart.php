<?php
require_once 'config/database.php';
require_once 'models/Cart.php';
require_once 'models/Product.php';
require_once 'utils/Session.php';

// Start session
Session::start();

// Check if user is logged in
if (!Session::isLoggedIn()) {
    Session::setFlash('error', 'Please login to view your cart.');
    header("Location: login.php");
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize cart object
$cart = new Cart($db);
$cart->user_id = Session::getUserId();

// Process cart actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update quantity
    if (isset($_POST['update_quantity'])) {
        $cart->id = $_POST['cart_id'];
        $cart->quantity = $_POST['quantity'];
        
        if ($cart->updateQuantity()) {
            Session::setFlash('success', 'Cart updated successfully.');
        } else {
            Session::setFlash('error', 'Failed to update cart.');
        }
        
        // Redirect to avoid form resubmission
        header("Location: cart.php");
        exit;
    }
    
    // Remove item from cart
    if (isset($_POST['remove_item'])) {
        $cart->id = $_POST['cart_id'];
        
        if ($cart->removeFromCart()) {
            Session::setFlash('success', 'Item removed from cart.');
        } else {
            Session::setFlash('error', 'Failed to remove item from cart.');
        }
        
        // Redirect to avoid form resubmission
        header("Location: cart.php");
        exit;
    }
    
    // Clear cart
    if (isset($_POST['clear_cart'])) {
        if ($cart->clearCart()) {
            Session::setFlash('success', 'Cart cleared successfully.');
        } else {
            Session::setFlash('error', 'Failed to clear cart.');
        }
        
        // Redirect to avoid form resubmission
        header("Location: cart.php");
        exit;
    }
}

// Get user's cart items
$stmt = $cart->getUserCart();
$cart_items = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cart_items[] = $row;
}

// Calculate cart total
$cart_total = $cart->getCartTotal();

// Include header
require_once 'includes/header.php';
?>

<h2 class="mb-4">Shopping Cart</h2>

<?php if (empty($cart_items)): ?>
<div class="alert alert-info">
    Your cart is empty. <a href="products.php">Continue shopping</a>.
</div>
<?php else: ?>

<div class="row">
    <!-- Cart Items -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Cart Items</h5>
            </div>
            <div class="card-body">
                <?php foreach ($cart_items as $item): ?>
                <div class="row cart-item mb-3 pb-3 border-bottom">
                    <div class="col-md-2">
                        <img src="assets/images/products/<?php echo $item['image']; ?>" class="img-fluid rounded" alt="<?php echo $item['name']; ?>">
                    </div>
                    <div class="col-md-4">
                        <h5><a href="product.php?id=<?php echo $item['product_id']; ?>"><?php echo $item['name']; ?></a></h5>
                        <p class="text-muted">₹<?php echo number_format($item['price'], 2); ?> each</p>
                    </div>
                    <div class="col-md-3">
                        <form action="" method="post">
                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                            <div class="input-group">
                                <input type="number" class="form-control quantity-input" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>">
                                <button type="submit" name="update_quantity" class="btn btn-outline-secondary">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-2">
                        <p class="fw-bold">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                    </div>
                    <div class="col-md-1">
                        <form action="" method="post">
                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" name="remove_item" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Cart Summary -->
    <div class="col-md-4">
        <div class="card cart-summary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Order Summary</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <span>Subtotal:</span>
                    <span>₹<?php echo number_format($cart_total, 2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span>Shipping:</span>
                    <span>Free</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3 fw-bold">
                    <span>Total:</span>
                    <span>₹<?php echo number_format($cart_total, 2); ?></span>
                </div>
                
                <div class="d-grid gap-2">
                    <a href="checkout.php" class="btn btn-success">
                        <i class="fas fa-credit-card"></i> Proceed to Checkout
                    </a>
                    <form action="" method="post">
                        <button type="submit" name="clear_cart" class="btn btn-outline-danger w-100">
                            <i class="fas fa-trash"></i> Clear Cart
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="products.php" class="btn btn-outline-primary w-100">
                <i class="fas fa-shopping-basket"></i> Continue Shopping
            </a>
        </div>
    </div>
</div>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>