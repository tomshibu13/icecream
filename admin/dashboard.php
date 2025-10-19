<?php
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Product.php';
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
$db = new Database();
$conn = $db->getConnection();

// Get counts for dashboard stats
$userModel = new User($conn);
$productModel = new Product($conn);
$orderModel = new Order($conn);

$totalUsers = $userModel->getTotalUsers();
$totalProducts = $productModel->getTotalProducts();
$totalOrders = $orderModel->getTotalOrders();
$totalRevenue = $orderModel->getTotalRevenue();

// Get recent orders
$recentOrders = $orderModel->getRecentOrders(5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Ice Cream Shop</title>
    
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<style>
    /* admin.css */

/* General Reset and Base Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Arial', sans-serif;
    background-color: #f4f4f9;
    color: #333;
    line-height: 1.6;
}

/* Admin Container */
.admin-container {
    display: flex;
    min-height: 100vh;
}

/* Sidebar */
.sidebar {
    width: 250px;
    background-color: #2c3e50;
    color: #fff;
    padding: 20px;
    position: fixed;
    height: 100%;
    overflow-y: auto;
}

.sidebar .logo {
    text-align: center;
    margin-bottom: 30px;
}

.sidebar .logo h2 {
    font-size: 24px;
    color: #fff;
}

.sidebar .logo p {
    font-size: 14px;
    color: #bdc3c7;
}

.sidebar nav ul {
    list-style: none;
}

.sidebar nav ul li {
    margin-bottom: 10px;
}

.sidebar nav ul li a {
    display: flex;
    align-items: center;
    color: #bdc3c7;
    text-decoration: none;
    padding: 10px;
    border-radius: 5px;
    transition: background 0.3s;
}

.sidebar nav ul li a i {
    margin-right: 10px;
}

.sidebar nav ul li.active a,
.sidebar nav ul li a:hover {
    background-color: #3498db;
    color: #fff;
}

/* Main Content */
.main-content {
    margin-left: 250px;
    flex-grow: 1;
    padding: 20px;
}

/* Header */
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #fff;
    padding: 15px 20px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

header h1 {
    font-size: 24px;
    color: #2c3e50;
}

.user-info span {
    font-size: 16px;
    color: #2c3e50;
}

/* Dashboard Stats */
.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    padding: 20px;
    transition: transform 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    font-size: 24px;
    color: #fff;
}

.stat-icon.users { background-color: #3498db; }
.stat-icon.products { background-color: #e74c3c; }
.stat-icon.orders { background-color: #2ecc71; }
.stat-icon.revenue { background-color: #f1c40f; }

.stat-info h3 {
    font-size: 16px;
    color: #7f8c8d;
    margin-bottom: 5px;
}

.stat-info p {
    font-size: 24px;
    font-weight: bold;
    color: #2c3e50;
}

/* Dashboard Sections */
.dashboard-sections {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

/* Recent Orders Table */
.recent-orders {
    background-color: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.recent-orders h2 {
    font-size: 20px;
    margin-bottom: 15px;
    color: #2c3e50;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

table th,
table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ecf0f1;
}

table th {
    background-color: #f4f4f9;
    color: #2c3e50;
    font-weight: 600;
}

table td {
    color: #34495e;
}

.status {
    padding: 5px 10px;
    border-radius: 12px;
    font-size: 14px;
    color: #fff;
}

.status.pending { background-color: #f39c12; }
.status.completed { background-color: #2ecc71; }
.status.cancelled { background-color: #e74c3c; }

.btn-view {
    display: inline-block;
    padding: 5px 10px;
    background-color: #3498db;
    color: #fff;
    text-decoration: none;
    border-radius: 5px;
    transition: background 0.3s;
}

.btn-view:hover {
    background-color: #2980b9;
}

.view-all {
    display: inline-block;
    padding: 10px 20px;
    background Ascending
    background-color: #3498db;
    color: #fff;
    text-decoration: none;
    border-radius: 5px;
    transition: background 0.3s;
}

.view-all:hover {
    background-color: #2980b9;
}

/* Quick Actions */
.quick-actions {
    background-color: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.quick-actions h2 {
    font-size: 20px;
    margin-bottom: 15px;
    color: #2c3e50;
}

.action-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
}

.action-btn {
    display: flex;
    align-items: center;
    padding: 10px;
    background-color: #3498db;
    color: #fff;
    text-decoration: none;
    border-radius: 5px;
    transition: background 0.3s;
}

.action-btn i {
    margin-right: 10px;
    font-size: 20px;
}

.action-btn:hover {
    background-color: #2980b9;
}

/* Responsive Design */
@media (max-width: 768px) {
    .admin-container {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        position: static;
        height: auto;
    }

    .main-content {
        margin-left: 0;
        padding: 10px;
    }

    .dashboard-sections {
        grid-template-columns: 1fr;
    }

    .stat-card {
        padding: 15px;
    }

    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 20px;
    }

    .stat-info p {
        font-size: 20px;
    }

    table th,
    table td {
        padding: 8px;
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .dashboard-stats {
        grid-template-columns: 1fr;
    }

    .action-buttons {
        grid-template-columns: 1fr;
    }

    .action-btn {
        padding: 8px;
        font-size: 14px;
    }
}
</style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="logo">
                <h2>Ice Cream Shop</h2>
                <p>Admin Panel</p>
            </div>
            <nav>
                <ul>
                    <li class="active"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="products.php"><i class="fas fa-ice-cream"></i> Products</a></li>
                    <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="categories.php"><i class="fas fa-list"></i> Categories</a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <header>
                <h1>Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars(Session::get('user_name')); ?></span>
                </div>
            </header>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Users</h3>
                        <p><?php echo $totalUsers; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon products">
                        <i class="fas fa-ice-cream"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Products</h3>
                        <p><?php echo $totalProducts; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Orders</h3>
                        <p><?php echo $totalOrders; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon revenue">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Revenue</h3>
                        <p>$<?php echo number_format($totalRevenue, 2); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-sections">
                <div class="section recent-orders">
                    <h2>Recent Orders</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recentOrders): ?>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td><span class="status <?php echo strtolower($order['status']); ?>"><?php echo $order['status']; ?></span></td>
                                    <td>
                                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn-view">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">No recent orders found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <a href="orders.php" class="view-all">View All Orders</a>
                </div>
                
                <div class="section quick-actions">
                    <h2>Quick Actions</h2>
                    <div class="action-buttons">
                        <a href="add_product.php" class="action-btn">
                            <i class="fas fa-plus-circle"></i>
                            <span>Add New Product</span>
                        </a>
                        <a href="add_category.php" class="action-btn">
                            <i class="fas fa-folder-plus"></i>
                            <span>Add New Category</span>
                        </a>
                        <a href="manage_inventory.php" class="action-btn">
                            <i class="fas fa-boxes"></i>
                            <span>Manage Inventory</span>
                        </a>
                        <a href="reports.php" class="action-btn">
                            <i class="fas fa-chart-bar"></i>
                            <span>View Reports</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>