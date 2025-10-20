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

// Initialize Order object
$order = new Order($db);

// Handle order status update
if (isset($_POST['update_status']) && !empty($_POST['order_id']) && !empty($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    if ($order->updateStatus($order_id, $status)) {
        Session::setFlash('success', 'Order status updated successfully.');
    } else {
        Session::setFlash('error', 'Failed to update order status.');
    }
    header("Location: orders.php");
    exit;
}

// Set up pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$from_record_num = ($records_per_page * $page) - $records_per_page;

// Handle search and filtering
$search = isset($_GET['search']) ? $_GET['search'] : "";
$status_filter = isset($_GET['status']) ? $_GET['status'] : "";
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : "";
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : "";

// Get orders based on search/filter
if (!empty($search) || !empty($status_filter) || !empty($date_from) || !empty($date_to)) {
    $stmt = $order->searchOrders($search, $status_filter, $date_from, $date_to, $from_record_num, $records_per_page);
    $total_rows = $order->countSearchResults($search, $status_filter, $date_from, $date_to);
} else {
    $stmt = $order->getAllOrders($from_record_num, $records_per_page);
    $total_rows = $order->getTotalOrders();
}

// Include header
$page_title = "Manage Orders";
include_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once __DIR__ . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Orders</h1>
            </div>

            <!-- Search and Filter -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-filter me-1"></i>
                    Search & Filter
                </div>
                <div class="card-body">
                    <form action="" method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="Order ID or Customer" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="Pending" <?php echo ($status_filter == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Processing" <?php echo ($status_filter == 'Processing') ? 'selected' : ''; ?>>Processing</option>
                                <option value="Shipped" <?php echo ($status_filter == 'Shipped') ? 'selected' : ''; ?>>Shipped</option>
                                <option value="Delivered" <?php echo ($status_filter == 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                                <option value="Cancelled" <?php echo ($status_filter == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="orders.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($stmt->rowCount() > 0): ?>
                            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td><?php echo $row['customer_name']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            switch($row['status']) {
                                                case 'Pending': echo 'warning'; break;
                                                case 'Processing': echo 'info'; break;
                                                case 'Shipped': echo 'primary'; break;
                                                case 'Delivered': echo 'success'; break;
                                                case 'Cancelled': echo 'danger'; break;
                                                default: echo 'secondary';
                                            }
                                        ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="order_details.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <form action="" method="POST">
                                                        <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                                        <input type="hidden" name="update_status" value="1">
                                                        <button type="submit" name="status" value="Pending" class="dropdown-item">Mark as Pending</button>
                                                        <button type="submit" name="status" value="Processing" class="dropdown-item">Mark as Processing</button>
                                                        <button type="submit" name="status" value="Shipped" class="dropdown-item">Mark as Shipped</button>
                                                        <button type="submit" name="status" value="Delivered" class="dropdown-item">Mark as Delivered</button>
                                                        <button type="submit" name="status" value="Cancelled" class="dropdown-item">Mark as Cancelled</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No orders found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php
            $total_pages = ceil($total_rows / $records_per_page);
            if ($total_pages > 1):
            ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1<?php 
                                echo (!empty($search)) ? '&search='.$search : ''; 
                                echo (!empty($status_filter)) ? '&status='.$status_filter : '';
                                echo (!empty($date_from)) ? '&date_from='.$date_from : '';
                                echo (!empty($date_to)) ? '&date_to='.$date_to : '';
                            ?>">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page-1; 
                                echo (!empty($search)) ? '&search='.$search : ''; 
                                echo (!empty($status_filter)) ? '&status='.$status_filter : '';
                                echo (!empty($date_from)) ? '&date_from='.$date_from : '';
                                echo (!empty($date_to)) ? '&date_to='.$date_to : '';
                            ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $total_pages); $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; 
                                echo (!empty($search)) ? '&search='.$search : ''; 
                                echo (!empty($status_filter)) ? '&status='.$status_filter : '';
                                echo (!empty($date_from)) ? '&date_from='.$date_from : '';
                                echo (!empty($date_to)) ? '&date_to='.$date_to : '';
                            ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page+1; 
                                echo (!empty($search)) ? '&search='.$search : ''; 
                                echo (!empty($status_filter)) ? '&status='.$status_filter : '';
                                echo (!empty($date_from)) ? '&date_from='.$date_from : '';
                                echo (!empty($date_to)) ? '&date_to='.$date_to : '';
                            ?>">Next</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; 
                                echo (!empty($search)) ? '&search='.$search : ''; 
                                echo (!empty($status_filter)) ? '&status='.$status_filter : '';
                                echo (!empty($date_from)) ? '&date_from='.$date_from : '';
                                echo (!empty($date_to)) ? '&date_to='.$date_to : '';
                            ?>">Last</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>