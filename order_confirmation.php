<?php
require_once 'config/database.php';
require_once 'models/Order.php';
require_once 'utils/Session.php';

// Start session
Session::start();

// Check if user is logged in
if (!Session::isLoggedIn()) {
    Session::setFlash('error', 'Please login to view order details.');
    header("Location: login.php");
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize order object
$order = new Order($db);

// Get order ID from URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $order->id = $_GET['id'];
    
    // Get order details
    $stmt = $order->getOrderDetails();
    $order_details = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if order exists and belongs to the current user
    if (empty($order_details) || $order_details['user_id'] != Session::getUserId()) {
        Session::setFlash('error', 'Order not found or you do not have permission to view it.');
        header("Location: orders.php");
        exit;
    }
} else {
    // Redirect to orders page if no ID provided
    header("Location: orders.php");
    exit;
}

// Include header
require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0"><i class="fas fa-check-circle"></i> Order Confirmed!</h3>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                        <h4 class="mt-3">Thank you for your order!</h4>
                        <p>Your order has been placed successfully and is being processed.</p>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Order Information</h5>
                            <p><strong>Order ID:</strong> #<?php echo $order_details[0]['order_id']; ?></p>
                            <p><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order_details[0]['created_at'])); ?></p>
                            <p><strong>Status:</strong> <span class="badge bg-info"><?php echo $order_details[0]['status']; ?></span></p>
                            <p><strong>Payment Method:</strong> <?php echo $order_details[0]['payment_method']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Shipping Address</h5>
                            <p><?php echo $order_details[0]['shipping_address']; ?></p>
                        </div>
                    </div>
                    
                    <h5>Order Details</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_details as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="assets/images/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="img-thumbnail me-2" style="width: 50px;">
                                            <?php echo $item['name']; ?>
                                        </div>
                                    </td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td class="text-end">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end">$<?php echo number_format($order_details[0]['total_amount'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Shipping:</strong></td>
                                    <td class="text-end">Free</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong>$<?php echo number_format($order_details[0]['total_amount'], 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="orders.php" class="btn btn-primary">
                            <i class="fas fa-list"></i> View All Orders
                        </a>
                        <a href="products.php" class="btn btn-outline-primary ms-2">
                            <i class="fas fa-shopping-basket"></i> Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>