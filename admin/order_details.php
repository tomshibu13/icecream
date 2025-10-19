<?php
require_once '../config/database.php';
require_once '../models/Order.php';
require_once '../utils/Session.php';

// Start session
Session::start();

// Check if user is logged in and is admin
if (!Session::isLoggedIn() || !Session::isAdmin()) {
    Session::setFlash('error', 'You do not have permission to access this page.');
    header("Location: ../login.php");
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    Session::setFlash('error', 'Order ID is required.');
    header("Location: orders.php");
    exit;
}

$order_id = $_GET['id'];

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize order object
$order = new Order($db);

// Get order details
$order_details = $order->getOrderById($order_id);

if (!$order_details) {
    Session::setFlash('error', 'Order not found.');
    header("Location: orders.php");
    exit;
}

// Get order items
$order_items = $order->getOrderItems($order_id);

// Process status update
if (isset($_POST['update_status']) && isset($_POST['status'])) {
    $order->id = $order_id;
    $order->status = $_POST['status'];
    
    if ($order->updateStatus()) {
        Session::setFlash('success', 'Order status updated successfully.');
        // Refresh the page to show updated status
        header("Location: order_details.php?id={$order_id}");
        exit;
    } else {
        Session::setFlash('error', 'Failed to update order status.');
    }
}

// Include header
$page_title = "Order Details #" . $order_id;
include_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Order #<?php echo $order_id; ?> Details</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="orders.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Orders
                    </a>
                </div>
            </div>
            
            <!-- Order Information -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Order Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Order ID:</th>
                                    <td>#<?php echo $order_details['id']; ?></td>
                                </tr>
                                <tr>
                                    <th>Date:</th>
                                    <td><?php echo date('F d, Y h:i A', strtotime($order_details['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Customer:</th>
                                    <td><?php echo $order_details['username']; ?> (ID: <?php echo $order_details['user_id']; ?>)</td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td><?php echo $order_details['email']; ?></td>
                                </tr>
                                <tr>
                                    <th>Payment Method:</th>
                                    <td><?php echo $order_details['payment_method']; ?></td>
                                </tr>
                                <tr>
                                    <th>Total Amount:</th>
                                    <td><strong>$<?php echo number_format($order_details['total_amount'], 2); ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <form action="" method="post" class="d-flex align-items-center">
                                            <select name="status" class="form-select form-select-sm me-2" style="width: 150px;">
                                                <option value="Pending" <?php echo ($order_details['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Processing" <?php echo ($order_details['status'] == 'Processing') ? 'selected' : ''; ?>>Processing</option>
                                                <option value="Shipped" <?php echo ($order_details['status'] == 'Shipped') ? 'selected' : ''; ?>>Shipped</option>
                                                <option value="Delivered" <?php echo ($order_details['status'] == 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="Cancelled" <?php echo ($order_details['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-sm btn-primary">
                                                Update Status
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">Shipping Information</h5>
                        </div>
                        <div class="card-body">
                            <address>
                                <strong><?php echo $order_details['shipping_name']; ?></strong><br>
                                <?php echo $order_details['shipping_address']; ?><br>
                                <?php echo $order_details['shipping_city']; ?>, <?php echo $order_details['shipping_state']; ?> <?php echo $order_details['shipping_zip']; ?><br>
                                <?php echo $order_details['shipping_country']; ?><br>
                                <abbr title="Phone">P:</abbr> <?php echo $order_details['shipping_phone']; ?>
                            </address>
                        </div>
                    </div>
                    
                    <!-- Order Notes -->
                    <div class="card mt-3">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="card-title mb-0">Order Notes</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($order_details['notes'])): ?>
                                <p><?php echo nl2br($order_details['notes']); ?></p>
                            <?php else: ?>
                                <p class="text-muted">No notes for this order.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="card-title mb-0">Order Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $subtotal = 0; ?>
                                <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="../uploads/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="img-thumbnail me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php else: ?>
                                                <img src="../assets/img/no-image.jpg" alt="No Image" class="img-thumbnail me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-0"><?php echo $item['name']; ?></h6>
                                                <small class="text-muted"><?php echo $item['category']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td class="text-end">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                                <?php $subtotal += ($item['price'] * $item['quantity']); ?>
                                <?php endforeach; ?>
                                
                                <!-- Subtotal, Tax, Shipping, Total -->
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end">$<?php echo number_format($subtotal, 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Tax (8%):</strong></td>
                                    <td class="text-end">$<?php echo number_format($subtotal * 0.08, 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Shipping:</strong></td>
                                    <td class="text-end">$<?php echo number_format($order_details['total_amount'] - ($subtotal + ($subtotal * 0.08)), 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong>$<?php echo number_format($order_details['total_amount'], 2); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Order Timeline -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">Order Timeline</h5>
                </div>
                <div class="card-body">
                    <ul class="timeline">
                        <li class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h3 class="timeline-title">Order Placed</h3>
                                <p class="timeline-date"><?php echo date('F d, Y h:i A', strtotime($order_details['created_at'])); ?></p>
                                <p>Order #<?php echo $order_details['id']; ?> was placed by <?php echo $order_details['username']; ?>.</p>
                            </div>
                        </li>
                        <?php if ($order_details['status'] != 'Pending'): ?>
                        <li class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h3 class="timeline-title">Order Processing</h3>
                                <p class="timeline-date"><?php echo date('F d, Y', strtotime($order_details['created_at'] . ' +1 day')); ?></p>
                                <p>Order is being processed and prepared for shipping.</p>
                            </div>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (in_array($order_details['status'], ['Shipped', 'Delivered'])): ?>
                        <li class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h3 class="timeline-title">Order Shipped</h3>
                                <p class="timeline-date"><?php echo date('F d, Y', strtotime($order_details['created_at'] . ' +2 days')); ?></p>
                                <p>Order has been shipped to the customer.</p>
                            </div>
                        </li>
                        <?php endif; ?>
                        
                        <?php if ($order_details['status'] == 'Delivered'): ?>
                        <li class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h3 class="timeline-title">Order Delivered</h3>
                                <p class="timeline-date"><?php echo date('F d, Y', strtotime($order_details['created_at'] . ' +4 days')); ?></p>
                                <p>Order has been delivered to the customer.</p>
                            </div>
                        </li>
                        <?php endif; ?>
                        
                        <?php if ($order_details['status'] == 'Cancelled'): ?>
                        <li class="timeline-item">
                            <div class="timeline-marker bg-danger"></div>
                            <div class="timeline-content">
                                <h3 class="timeline-title text-danger">Order Cancelled</h3>
                                <p class="timeline-date"><?php echo date('F d, Y', strtotime($order_details['updated_at'])); ?></p>
                                <p>Order has been cancelled.</p>
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="d-flex justify-content-between mb-4">
                <a href="orders.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
                <div>
                    <a href="javascript:window.print()" class="btn btn-info me-2">
                        <i class="fas fa-print"></i> Print Order
                    </a>
                    <a href="mailto:<?php echo $order_details['email']; ?>?subject=Order #<?php echo $order_id; ?> Update&body=Dear <?php echo $order_details['username']; ?>, %0D%0A%0D%0AYour order #<?php echo $order_id; ?> status has been updated to: <?php echo $order_details['status']; ?>.%0D%0A%0D%0AThank you for your business!%0D%0A%0D%0ARegards,%0D%0AIce Cream Shop Team" class="btn btn-primary">
                        <i class="fas fa-envelope"></i> Email Customer
                    </a>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
/* Timeline Styles */
.timeline {
    position: relative;
    padding-left: 30px;
    list-style: none;
    margin-bottom: 0;
}

.timeline-item {
    position: relative;
    padding-bottom: 30px;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    background-color: #28a745;
    left: -30px;
    top: 6px;
}

.timeline-item:not(:last-child) .timeline-marker::after {
    content: '';
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    width: 2px;
    background-color: #e9ecef;
    top: 15px;
    bottom: -30px;
}

.timeline-content {
    padding-bottom: 10px;
}

.timeline-title {
    font-size: 1.1rem;
    margin-bottom: 0;
}

.timeline-date {
    color: #6c757d;
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
}

/* Print Styles */
@media print {
    .sidebar, .navbar, .btn-toolbar, form, .btn {
        display: none !important;
    }
    
    main {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .card {
        break-inside: avoid;
    }
}
</style>

<?php include_once 'includes/footer.php'; ?>