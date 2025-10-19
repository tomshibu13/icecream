<?php
require_once 'config/database.php';
require_once 'models/Cart.php';
require_once 'models/Order.php';
require_once 'models/User.php';
require_once 'utils/Session.php';

// Razorpay API keys
$razorpay_key_id = "rzp_test_R6h0atxxQ4WsUU";
$razorpay_key_secret = "5CyNCDCaDKmrRqPWX2K6uLGV";

// Start session
Session::start();

// Check if user is logged in
if (!Session::isLoggedIn()) {
    Session::setFlash('error', 'Please login to checkout.');
    header("Location: login.php");
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize objects
$cart = new Cart($db);
$order = new Order($db);
$user = new User($db);

// Set user ID
$user_id = Session::getUserId();
$cart->user_id = $user_id;
$user->id = $user_id;

// Get user details
$user->readOne();

// Check if cart is empty
$stmt = $cart->getUserCart();
$cart_items = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cart_items[] = $row;
}

if (empty($cart_items)) {
    Session::setFlash('error', 'Your cart is empty. Please add products to your cart before checkout.');
    header("Location: cart.php");
    exit;
}

// Calculate cart total
$cart_total = $cart->getCartTotal();

// Process checkout
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    // Set order properties
    $order->user_id = $user_id;
    $order->total_amount = $cart_total;
    $order->shipping_address = $_POST['address'] . ", " . $_POST['city'] . ", " . $_POST['state'] . ", " . $_POST['zip_code'];
    $order->payment_method = $_POST['payment_method'];
    
    // For Razorpay payment method, we'll create the order but handle payment via JavaScript
    if ($order->payment_method === 'Razorpay') {
        // Create order in database first
        if ($order->createFromCart($cart_items, $cart_total)) {
            // We'll handle the redirect in JavaScript after payment
            $razorpay_order_id = 'order_' . $order->id . '_' . time(); // Generate a unique order ID for Razorpay
            // Store this in session for verification later
            $_SESSION['razorpay_order_id'] = $razorpay_order_id;
            $_SESSION['order_id'] = $order->id;
        } else {
            $message = "<div class='alert alert-danger'>Failed to place order. Please try again.</div>";
        }
    } else {
        // For other payment methods, proceed as usual
        if ($order->createFromCart($cart_items, $cart_total)) {
            // Redirect to order confirmation page
            header("Location: order_confirmation.php?id=" . $order->id);
            exit;
        } else {
            $message = "<div class='alert alert-danger'>Failed to place order. Please try again.</div>";
        }
    }
}

// Include header
require_once 'includes/header.php';
?>

<!-- Include Razorpay JavaScript SDK -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<h2 class="mb-4">Checkout</h2>

<?php echo $message; ?>

<div class="row">
    <!-- Checkout Form -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Shipping Information</h5>
            </div>
            <div class="card-body">
                <form action="" method="post">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $user->first_name; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $user->last_name; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $user->email; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address" required>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" required>
                        </div>
                        <div class="col-md-3">
                            <label for="state" class="form-label">State</label>
                            <input type="text" class="form-control" id="state" name="state" required>
                        </div>
                        <div class="col-md-3">
                            <label for="zip_code" class="form-label">Zip Code</label>
                            <input type="text" class="form-control" id="zip_code" name="zip_code" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="">Select Payment Method</option>
                            <option value="Razorpay">Razorpay</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="PayPal">PayPal</option>
                            <option value="Cash on Delivery">Cash on Delivery</option>
                        </select>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" name="place_order" id="place_order_btn" class="btn btn-success btn-lg">
                            <i class="fas fa-check-circle"></i> Place Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Order Summary -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Order Summary</h5>
            </div>
            <div class="card-body">
                <?php foreach ($cart_items as $item): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span><?php echo $item['name']; ?> (x<?php echo $item['quantity']; ?>)</span>
                    <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                </div>
                <?php endforeach; ?>
                
                <hr>
                
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($cart_total, 2); ?></span>
                </div>
                
                <div class="d-flex justify-content-between mb-2">
                    <span>Shipping:</span>
                    <span>Free</span>
                </div>
                
                <hr>
                
                <div class="d-flex justify-content-between fw-bold">
                    <span>Total:</span>
                    <span>$<?php echo number_format($cart_total, 2); ?></span>
                </div>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="cart.php" class="btn btn-outline-secondary w-100">
                <i class="fas fa-arrow-left"></i> Back to Cart
            </a>
        </div>
    </div>
</div>

<!-- Add JavaScript for Razorpay integration -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get payment method select element
    const paymentMethodSelect = document.getElementById('payment_method');
    const placeOrderBtn = document.getElementById('place_order_btn');
    
    // Function to handle Razorpay payment
    function handleRazorpayPayment(orderId, amount, name, email) {
        var options = {
            "key": "<?php echo $razorpay_key_id; ?>",
            "amount": amount * 100, // Razorpay amount is in paise
            "currency": "INR",
            "name": "Ice Cream Shop",
            "description": "Order Payment",
            "order_id": "", // Leave blank as we're not using Razorpay's order API
            "handler": function (response) {
                // On successful payment, redirect to confirmation page
                window.location.href = "order_confirmation.php?id=<?php echo isset($_SESSION['order_id']) ? $_SESSION['order_id'] : ''; ?>&payment_id=" + response.razorpay_payment_id;
            },
            "prefill": {
                "name": name,
                "email": email
            },
            "theme": {
                "color": "#3399cc"
            }
        };
        
        var rzp1 = new Razorpay(options);
        rzp1.on('payment.failed', function (response){
            alert("Payment failed. Please try again. Error: " + response.error.description);
        });
        rzp1.open();
    }
    
    // Check if we need to trigger Razorpay payment
    <?php if (isset($_SESSION['razorpay_order_id']) && isset($_SESSION['order_id'])): ?>
    // Get form values
    const firstName = document.getElementById('first_name').value;
    const lastName = document.getElementById('last_name').value;
    const email = document.getElementById('email').value;
    const fullName = firstName + ' ' + lastName;
    
    // Trigger Razorpay payment
    handleRazorpayPayment(
        '<?php echo isset($_SESSION['razorpay_order_id']) ? $_SESSION['razorpay_order_id'] : ''; ?>',
        <?php echo $cart_total; ?>,
        fullName,
        email
    );
    
    // Clear session variables after initiating payment
    <?php 
    // Keep the order_id for verification but clear the razorpay_order_id
    unset($_SESSION['razorpay_order_id']); 
    ?>
    <?php endif; ?>
    
    // Add event listener to payment method select
    paymentMethodSelect.addEventListener('change', function() {
        if (this.value === 'Razorpay') {
            placeOrderBtn.textContent = 'Pay with Razorpay';
        } else {
            placeOrderBtn.innerHTML = '<i class="fas fa-check-circle"></i> Place Order';
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>