<?php
require_once '../config/database.php';
require_once '../models/Product.php';
require_once '../models/Order.php';
require_once '../models/User.php';
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

// Initialize objects
$product = new Product($db);
$order = new Order($db);
$user = new User($db);

// Get statistics
$total_products = $product->getTotalProducts();
$total_orders = $order->getTotalOrders();
$total_users = $user->getTotalUsers();
$total_revenue = $order->getTotalRevenue();

// Get recent orders
$recent_orders = $order->getRecentOrders(5);

// Get low stock products
$low_stock_products = $product->getLowStockProducts(5);

// Include header
$page_title = "Admin Dashboard";
include_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once __DIR__ . '/includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0">Welcome, <?php echo htmlspecialchars(Session::getUsername()); ?></h1>
                    <p class="text-muted small mb-0">Here’s an overview of your store today</p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="orders.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-file-export"></i> Export</a>
                    </div>
                    <a href="#" class="btn btn-sm btn-outline-secondary"><i class="fas fa-calendar"></i> This week</a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row g-2 mb-4 quick-actions">
                <div class="col-auto">
                    <a href="products.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Product</a>
                </div>
                <div class="col-auto">
                    <a href="orders.php" class="btn btn-info btn-sm text-white"><i class="fas fa-tasks"></i> Manage Orders</a>
                </div>
                <div class="col-auto">
                    <a href="users.php" class="btn btn-warning btn-sm text-dark"><i class="fas fa-user-cog"></i> Manage Users</a>
                </div>
                <div class="col-auto">
                    <a href="profile.php" class="btn btn-secondary btn-sm"><i class="fas fa-user-shield"></i> Admin Profile</a>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Products</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_products; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-ice-cream fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Revenue</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($total_revenue, 2); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total Orders</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_orders; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Total Users</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_users; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Row -->
            <div class="row">
                <!-- Recent Orders -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Recent Orders</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($recent_orders && $recent_orders->rowCount() > 0): ?>
                                            <?php while ($row = $recent_orders->fetch(PDO::FETCH_ASSOC)): ?>
                                                <tr>
                                                    <td><a href="order_details.php?id=<?php echo $row['id']; ?>">#<?php echo $row['id']; ?></a></td>
                                                    <td><?php echo $row['customer_name']; ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($row['order_date'])); ?></td>
                                                    <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            switch($row['status']) {
                                                                case 'Pending': echo 'warning'; break;
                                                                case 'Processing': echo 'info'; break;
                                                                case 'Completed': echo 'success'; break;
                                                                case 'Cancelled': echo 'danger'; break;
                                                                default: echo 'secondary';
                                                            }
                                                        ?>">
                                                            <?php echo $row['status']; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No recent orders found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <a href="orders.php" class="btn btn-primary btn-sm">View All Orders</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Products -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Low Stock Products</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($low_stock_products && $low_stock_products->rowCount() > 0): ?>
                                            <?php while ($row = $low_stock_products->fetch(PDO::FETCH_ASSOC)): ?>
                                                <tr>
                                                    <td><a href="edit_product.php?id=<?php echo $row['id']; ?>"><?php echo $row['name']; ?></a></td>
                                                    <td><?php echo $row['category']; ?></td>
                                                    <td>$<?php echo number_format($row['price'], 2); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo ($row['stock'] <= 5) ? 'danger' : 'warning'; ?>">
                                                            <?php echo $row['stock']; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No low stock products found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <a href="products.php" class="btn btn-primary btn-sm">View All Products</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Charts -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Monthly Sales</h6>
                            <span class="badge bg-primary">Demo Data</span>
                        </div>
                        <div class="card-body">
                            <div class="chart-area" style="height:320px;">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Orders by Status</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="statusChart"></canvas>
                            <div class="mt-3 small text-muted">Based on sample data</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Demo data - replace with actual data from database
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Monthly Sales ($)',
                data: [1200, 1900, 3000, 2500, 2800, 3500, 4000, 3800, 4200, 3900, 4500, 5000],
                backgroundColor: 'rgba(78,115,223,0.1)',
                borderColor: 'rgba(78,115,223,1)',
                pointRadius: 3,
                pointBackgroundColor: 'rgba(78,115,223,1)',
                pointBorderColor: 'rgba(78,115,223,1)',
                pointHoverRadius: 5,
                pointHoverBackgroundColor: 'rgba(78,115,223,1)',
                pointHoverBorderColor: 'rgba(78,115,223,1)',
                pointHitRadius: 10,
                pointBorderWidth: 2,
                tension: 0.35,
                fill: true
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: { display: false, drawBorder: false }
                },
                y: {
                    ticks: {
                        beginAtZero: true,
                        callback: function(value) { return '$' + value; }
                    }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '$' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Orders by Status (demo)
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Processing', 'Completed', 'Cancelled'],
            datasets: [{
                data: [12, 9, 28, 3],
                backgroundColor: ['#f6c23e', '#36b9cc', '#1cc88a', '#e74a3b'],
                hoverBackgroundColor: ['#f4b619', '#2fa7b4', '#17a673', '#c8392e']
            }]
        },
        options: {
            cutout: '60%',
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>