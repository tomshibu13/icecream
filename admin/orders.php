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

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize order object
$order = new Order($db);

// Process status update
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order->id = $_POST['order_id'];
    $order->status = $_POST['status'];
    
    if ($order->updateStatus()) {
        Session::setFlash('success', 'Order status updated successfully.');
    } else {
        Session::setFlash('error', 'Failed to update order status.');
    }
    
    // Redirect to avoid form resubmission
    header("Location: orders.php");
    exit;
}

// Handle filtering
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$from_record_num = ($records_per_page * $page) - $records_per_page;

// Get orders
if (!empty($status_filter) && !empty($date_filter)) {
    $stmt = $order->getOrdersByStatusAndDate($status_filter, $date_filter, $from_record_num, $records_per_page);
    $total_rows = $order->countOrdersByStatusAndDate($status_filter, $date_filter);
} elseif (!empty($status_filter)) {
    $stmt = $order->getOrdersByStatus($status_filter, $from_record_num, $records_per_page);
    $total_rows = $order->countOrdersByStatus($status_filter);
} elseif (!empty($date_filter)) {
    $stmt = $order->getOrdersByDate($date_filter, $from_record_num, $records_per_page);
    $total_rows = $order->countOrdersByDate($date_filter);
} else {
    $stmt = $order->getAllOrders($from_record_num, $records_per_page);
    $total_rows = $order->countAllOrders();
}

// Calculate total pages
$total_pages = ceil($total_rows / $records_per_page);

// Include header
$page_title = "Manage Orders";
include_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Orders</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="reports.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-chart-bar"></i> Sales Reports
                    </a>
                </div>
            </div>
            
            <!-- Filter Options -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="" method="get" class="row g-3">
                                <div class="col-md-4">
                                    <label for="status" class="form-label">Filter by Status</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="">All Statuses</option>
                                        <option value="Pending" <?php echo ($status_filter == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Processing" <?php echo ($status_filter == 'Processing') ? 'selected' : ''; ?>>Processing</option>
                                        <option value="Shipped" <?php echo ($status_filter == 'Shipped') ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="Delivered" <?php echo ($status_filter == 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="Cancelled" <?php echo ($status_filter == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="date" class="form-label">Filter by Date</label>
                                    <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-filter"></i> Apply Filters
                                    </button>
                                    <a href="orders.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-sync-alt"></i> Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Orders Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment Method</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td><?php echo $row['username']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                            <td>
                                <form action="" method="post" class="status-form">
                                    <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                    <select name="status" class="form-select form-select-sm status-select" data-original="<?php echo $row['status']; ?>">
                                        <option value="Pending" <?php echo ($row['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Processing" <?php echo ($row['status'] == 'Processing') ? 'selected' : ''; ?>>Processing</option>
                                        <option value="Shipped" <?php echo ($row['status'] == 'Shipped') ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="Delivered" <?php echo ($row['status'] == 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="Cancelled" <?php echo ($row['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary update-btn" style="display: none;">
                                        Update
                                    </button>
                                </form>
                            </td>
                            <td><?php echo $row['payment_method']; ?></td>
                            <td>
                                <a href="order_details.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if ($stmt->rowCount() == 0): ?>
                        <tr>
                            <td colspan="7" class="text-center">No orders found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1<?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($date_filter) ? '&date=' . urlencode($date_filter) : ''; ?>">
                            First
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($date_filter) ? '&date=' . urlencode($date_filter) : ''; ?>">
                            &laquo;
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php 
                    // Determine the range of page numbers to display
                    $range = 2; // Display 2 pages before and after the current page
                    $start_page = max(1, $page - $range);
                    $end_page = min($total_pages, $page + $range);
                    
                    for ($i = $start_page; $i <= $end_page; $i++): 
                    ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($date_filter) ? '&date=' . urlencode($date_filter) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($date_filter) ? '&date=' . urlencode($date_filter) : ''; ?>">
                            &raquo;
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($date_filter) ? '&date=' . urlencode($date_filter) : ''; ?>">
                            Last
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show update button when status changes
        const statusSelects = document.querySelectorAll('.status-select');
        
        statusSelects.forEach(select => {
            select.addEventListener('change', function() {
                const originalValue = this.getAttribute('data-original');
                const updateBtn = this.parentElement.querySelector('.update-btn');
                
                if (this.value !== originalValue) {
                    updateBtn.style.display = 'inline-block';
                } else {
                    updateBtn.style.display = 'none';
                }
            });
        });
    });
</script>

<?php include_once 'includes/footer.php'; ?>