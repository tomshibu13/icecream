<?php
require_once '../config/database.php';
require_once '../models/Order.php';
require_once '../models/Product.php';
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
$order = new Order($db);
$product = new Product($db);
$user = new User($db);

// Get date range for reports
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get sales data for the selected period
$sales_data = $order->getSalesByDateRange($start_date, $end_date);
$total_sales = $order->getTotalSalesByDateRange($start_date, $end_date);
$order_count = $order->getOrderCountByDateRange($start_date, $end_date);
$avg_order_value = $order_count > 0 ? $total_sales / $order_count : 0;

// Get top selling products
$top_products = $product->getTopSellingProducts($start_date, $end_date, 5);

// Get sales by category
$sales_by_category = $product->getSalesByCategory($start_date, $end_date);

// Get new users in the period
$new_users = $user->getNewUsersByDateRange($start_date, $end_date);

// Get monthly sales for chart
$monthly_sales = $order->getMonthlySales(date('Y', strtotime($start_date)));

// Include header
$page_title = "Sales Reports";
include_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Sales Reports</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="exportCSV">
                            <i class="fas fa-download"></i> Export CSV
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Date Range Selector -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="" method="get" class="row g-3">
                                <div class="col-md-4">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-filter"></i> Apply Range
                                    </button>
                                    <a href="reports.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-sync-alt"></i> Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sales Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Total Sales</h5>
                            <h2 class="card-text">$<?php echo number_format($total_sales, 2); ?></h2>
                            <p class="card-text"><small>For selected period</small></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Orders</h5>
                            <h2 class="card-text"><?php echo $order_count; ?></h2>
                            <p class="card-text"><small>For selected period</small></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Avg. Order Value</h5>
                            <h2 class="card-text">$<?php echo number_format($avg_order_value, 2); ?></h2>
                            <p class="card-text"><small>For selected period</small></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">New Customers</h5>
                            <h2 class="card-text"><?php echo $new_users; ?></h2>
                            <p class="card-text"><small>For selected period</small></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sales Charts -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Monthly Sales</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="monthlySalesChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Sales by Category</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="categoryChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Products and Recent Sales -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Top Selling Products</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Category</th>
                                            <th>Units Sold</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                                            <td><?php echo $product['units_sold']; ?></td>
                                            <td>$<?php echo number_format($product['revenue'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (count($top_products) == 0): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No data available</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Recent Sales</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Date</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sales_data as $sale): ?>
                                        <tr>
                                            <td><a href="order_details.php?id=<?php echo $sale['id']; ?>">#<?php echo $sale['id']; ?></a></td>
                                            <td><?php echo date('M d, Y', strtotime($sale['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($sale['username']); ?></td>
                                            <td>$<?php echo number_format($sale['total_amount'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (count($sales_data) == 0): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No sales data available</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sales by Status -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Orders by Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <canvas id="orderStatusChart" height="200"></canvas>
                                </div>
                                <div class="col-md-4">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Status</th>
                                                    <th>Count</th>
                                                    <th>Percentage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
                                                $status_counts = [];
                                                $total_count = 0;
                                                
                                                foreach ($statuses as $status) {
                                                    $count = $order->countOrdersByStatusAndDateRange($status, $start_date, $end_date);
                                                    $status_counts[$status] = $count;
                                                    $total_count += $count;
                                                }
                                                
                                                foreach ($statuses as $status):
                                                    $percentage = $total_count > 0 ? ($status_counts[$status] / $total_count) * 100 : 0;
                                                ?>
                                                <tr>
                                                    <td>
                                                        <?php 
                                                        $badge_class = 'bg-secondary';
                                                        switch ($status) {
                                                            case 'Pending': $badge_class = 'bg-warning'; break;
                                                            case 'Processing': $badge_class = 'bg-info'; break;
                                                            case 'Shipped': $badge_class = 'bg-primary'; break;
                                                            case 'Delivered': $badge_class = 'bg-success'; break;
                                                            case 'Cancelled': $badge_class = 'bg-danger'; break;
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                                                    </td>
                                                    <td><?php echo $status_counts[$status]; ?></td>
                                                    <td><?php echo number_format($percentage, 1); ?>%</td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Monthly Sales Chart
        const monthlySalesCtx = document.getElementById('monthlySalesChart').getContext('2d');
        const monthlySalesChart = new Chart(monthlySalesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Monthly Sales ($)',
                    data: [
                        <?php 
                        $months = [];
                        for ($i = 1; $i <= 12; $i++) {
                            $month_sales = 0;
                            foreach ($monthly_sales as $sale) {
                                if ($sale['month'] == $i) {
                                    $month_sales = $sale['total'];
                                    break;
                                }
                            }
                            $months[] = $month_sales;
                        }
                        echo implode(', ', $months);
                        ?>
                    ],
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                }
            }
        });
        
        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php 
                    $categories = [];
                    $category_data = [];
                    $background_colors = [];
                    
                    foreach ($sales_by_category as $category) {
                        $categories[] = "'" . $category['category'] . "'";
                        $category_data[] = $category['total'];
                        
                        // Generate random colors
                        $r = rand(100, 200);
                        $g = rand(100, 200);
                        $b = rand(100, 200);
                        $background_colors[] = "'rgba($r, $g, $b, 0.8)'";
                    }
                    
                    echo implode(', ', $categories);
                    ?>
                ],
                datasets: [{
                    data: [<?php echo implode(', ', $category_data); ?>],
                    backgroundColor: [<?php echo implode(', ', $background_colors); ?>],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
        
        // Order Status Chart
        const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
        const orderStatusChart = new Chart(orderStatusCtx, {
            type: 'bar',
            data: {
                labels: ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'],
                datasets: [{
                    label: 'Orders by Status',
                    data: [
                        <?php 
                        $status_data = [];
                        foreach ($statuses as $status) {
                            $status_data[] = $status_counts[$status];
                        }
                        echo implode(', ', $status_data);
                        ?>
                    ],
                    backgroundColor: [
                        'rgba(255, 193, 7, 0.8)',  // Warning - Pending
                        'rgba(23, 162, 184, 0.8)', // Info - Processing
                        'rgba(0, 123, 255, 0.8)',  // Primary - Shipped
                        'rgba(40, 167, 69, 0.8)',  // Success - Delivered
                        'rgba(220, 53, 69, 0.8)'   // Danger - Cancelled
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        
        // Export to CSV functionality
        document.getElementById('exportCSV').addEventListener('click', function() {
            // Create CSV content
            let csvContent = 'data:text/csv;charset=utf-8,';
            csvContent += 'Report Period: ' + document.getElementById('start_date').value + ' to ' + document.getElementById('end_date').value + '\n\n';
            csvContent += 'Sales Summary\n';
            csvContent += 'Total Sales,$' + <?php echo json_encode(number_format($total_sales, 2)); ?> + '\n';
            csvContent += 'Total Orders,' + <?php echo json_encode($order_count); ?> + '\n';
            csvContent += 'Average Order Value,$' + <?php echo json_encode(number_format($avg_order_value, 2)); ?> + '\n';
            csvContent += 'New Customers,' + <?php echo json_encode($new_users); ?> + '\n\n';
            
            // Top Products
            csvContent += 'Top Selling Products\n';
            csvContent += 'Product,Category,Units Sold,Revenue\n';
            <?php foreach ($top_products as $product): ?>
            csvContent += <?php echo json_encode(htmlspecialchars($product['name'])); ?> + ',' + 
                         <?php echo json_encode(htmlspecialchars($product['category'])); ?> + ',' + 
                         <?php echo json_encode($product['units_sold']); ?> + ',$' + 
                         <?php echo json_encode(number_format($product['revenue'], 2)); ?> + '\n';
            <?php endforeach; ?>
            csvContent += '\n';
            
            // Sales by Category
            csvContent += 'Sales by Category\n';
            csvContent += 'Category,Total\n';
            <?php foreach ($sales_by_category as $category): ?>
            csvContent += <?php echo json_encode($category['category']); ?> + ',$' + 
                         <?php echo json_encode(number_format($category['total'], 2)); ?> + '\n';
            <?php endforeach; ?>
            csvContent += '\n';
            
            // Orders by Status
            csvContent += 'Orders by Status\n';
            csvContent += 'Status,Count,Percentage\n';
            <?php foreach ($statuses as $status): 
                $percentage = $total_count > 0 ? ($status_counts[$status] / $total_count) * 100 : 0;
            ?>
            csvContent += <?php echo json_encode($status); ?> + ',' + 
                         <?php echo json_encode($status_counts[$status]); ?> + ',' + 
                         <?php echo json_encode(number_format($percentage, 1)); ?> + '%\n';
            <?php endforeach; ?>
            
            // Create download link
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement('a');
            link.setAttribute('href', encodedUri);
            link.setAttribute('download', 'sales_report_' + document.getElementById('start_date').value + '_to_' + document.getElementById('end_date').value + '.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    });
</script>

<style>
/* Print Styles */
@media print {
    .sidebar, .navbar, .btn-toolbar, form, .btn, .no-print {
        display: none !important;
    }
    
    main {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .card {
        break-inside: avoid;
        margin-bottom: 20px;
    }
    
    .container-fluid {
        width: 100%;
        padding: 0;
    }
    
    body {
        padding: 20px;
    }
}
</style>

<?php include_once 'includes/footer.php'; ?>