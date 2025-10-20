<?php
require_once 'config/database.php';
require_once 'models/Order.php';
require_once 'utils/Session.php';

// Start session
Session::start();

// Check if user is logged in
if (!Session::isLoggedIn()) {
    Session::setFlash('error', 'Please login to view your orders.');
    header("Location: login.php");
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize order object
$order = new Order($db);
$order->user_id = Session::getUserId();

// Get user's orders
$stmt = $order->getUserOrders();
$orders = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $orders[] = $row;
}

// Include header
require_once 'includes/header.php';
?>

<h2 class="mb-4">My Orders</h2>

<?php if (empty($orders)): ?>
<div class="alert alert-info">
    You have no orders yet. <a href="products.php">Start shopping</a>.
</div>
<?php else: ?>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td>#<?php echo $order['id']; ?></td>
                <td><?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></td>
                <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                <td>
                    <?php if ($order['status'] == 'Pending'): ?>
                    <span class="badge bg-warning text-dark">Pending</span>
                    <?php elseif ($order['status'] == 'Processing'): ?>
                    <span class="badge bg-info">Processing</span>
                    <?php elseif ($order['status'] == 'Shipped'): ?>
                    <span class="badge bg-primary">Shipped</span>
                    <?php elseif ($order['status'] == 'Delivered'): ?>
                    <span class="badge bg-success">Delivered</span>
                    <?php elseif ($order['status'] == 'Cancelled'): ?>
                    <span class="badge bg-danger">Cancelled</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="order_confirmation.php?id=<?php echo htmlspecialchars($order['id']); ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> View
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>