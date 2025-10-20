<?php
require_once '../config/database.php';
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

// Initialize User object
$user = new User($db);

// Handle status update
if (isset($_POST['update_status']) && !empty($_POST['user_id']) && isset($_POST['status'])) {
    $user_id = $_POST['user_id'];
    $status = $_POST['status'];
    if ($user->updateStatus($user_id, $status)) {
        Session::setFlash('success', 'User status updated successfully.');
    } else {
        Session::setFlash('error', 'Failed to update user status.');
    }
    header("Location: users.php");
    exit;
}

// Handle role update
if (isset($_POST['update_role']) && !empty($_POST['user_id']) && isset($_POST['role'])) {
    $user_id = $_POST['user_id'];
    $role = $_POST['role'];
    if ($user->updateRole($user_id, $role)) {
        Session::setFlash('success', 'User role updated successfully.');
    } else {
        Session::setFlash('error', 'Failed to update user role.');
    }
    header("Location: users.php");
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$from_record_num = ($records_per_page * $page) - $records_per_page;

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : "";
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : "";

// Get users
if ($search !== "" || $role_filter !== "" || $status_filter !== "") {
    $stmt = $user->searchUsers($search, $role_filter, $status_filter, $from_record_num, $records_per_page);
    $total_rows = $user->countSearchResults($search, $role_filter, $status_filter);
} else {
    $stmt = $user->getUsers($from_record_num, $records_per_page);
    $total_rows = $user->getTotalUsers();
}

$page_title = "Manage Users";
include_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once __DIR__ . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Users</h1>
            </div>

            <!-- Search & Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-filter me-1"></i>
                    Search & Filter
                </div>
                <div class="card-body">
                    <form action="" method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="Username, Name, Email" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="role" class="form-label">Role</label>
                            <select name="role" id="role" class="form-select">
                                <option value="">All Roles</option>
                                <option value="admin" <?php echo ($role_filter === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="user" <?php echo ($role_filter === 'user') ? 'selected' : ''; ?>>User</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="active" <?php echo ($status_filter === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($status_filter === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                <option value="banned" <?php echo ($status_filter === 'banned') ? 'selected' : ''; ?>>Banned</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="users.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($stmt && $stmt->rowCount() > 0): ?>
                            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($row['role'] === 'admin') ? 'primary' : 'secondary'; ?>">
                                            <?php echo htmlspecialchars($row['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            switch($row['status']) {
                                                case 'active': echo 'success'; break;
                                                case 'inactive': echo 'warning'; break;
                                                case 'banned': echo 'danger'; break;
                                                default: echo 'secondary';
                                            }
                                        ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <form action="" method="POST" class="px-3 py-2">
                                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                        <div class="mb-2">
                                                            <label class="form-label">Change Role</label>
                                                            <select name="role" class="form-select form-select-sm">
                                                                <option value="user" <?php echo ($row['role'] === 'user') ? 'selected' : ''; ?>>User</option>
                                                                <option value="admin" <?php echo ($row['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                                            </select>
                                                        </div>
                                                        <button type="submit" name="update_role" value="1" class="btn btn-sm btn-primary w-100">Update Role</button>
                                                    </form>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="" method="POST" class="px-3 py-2">
                                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                        <div class="mb-2">
                                                            <label class="form-label">Change Status</label>
                                                            <select name="status" class="form-select form-select-sm">
                                                                <option value="active" <?php echo ($row['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                                                <option value="inactive" <?php echo ($row['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                                <option value="banned" <?php echo ($row['status'] === 'banned') ? 'selected' : ''; ?>>Banned</option>
                                                            </select>
                                                        </div>
                                                        <button type="submit" name="update_status" value="1" class="btn btn-sm btn-warning w-100">Update Status</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No users found.</td>
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
                                echo ($search !== "") ? '&search=' . urlencode($search) : ''; 
                                echo ($role_filter !== "") ? '&role=' . urlencode($role_filter) : '';
                                echo ($status_filter !== "") ? '&status=' . urlencode($status_filter) : '';
                            ?>">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page-1; 
                                echo ($search !== "") ? '&search=' . urlencode($search) : ''; 
                                echo ($role_filter !== "") ? '&role=' . urlencode($role_filter) : '';
                                echo ($status_filter !== "") ? '&status=' . urlencode($status_filter) : '';
                            ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $total_pages); $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; 
                                echo ($search !== "") ? '&search=' . urlencode($search) : ''; 
                                echo ($role_filter !== "") ? '&role=' . urlencode($role_filter) : '';
                                echo ($status_filter !== "") ? '&status=' . urlencode($status_filter) : '';
                            ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page+1; 
                                echo ($search !== "") ? '&search=' . urlencode($search) : ''; 
                                echo ($role_filter !== "") ? '&role=' . urlencode($role_filter) : '';
                                echo ($status_filter !== "") ? '&status=' . urlencode($status_filter) : '';
                            ?>">Next</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; 
                                echo ($search !== "") ? '&search=' . urlencode($search) : ''; 
                                echo ($role_filter !== "") ? '&role=' . urlencode($role_filter) : '';
                                echo ($status_filter !== "") ? '&status=' . urlencode($status_filter) : '';
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