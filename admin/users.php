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

// Initialize user object
$user = new User($db);

// Process user status update
if (isset($_POST['update_status']) && isset($_POST['user_id']) && isset($_POST['status'])) {
    $user_id = $_POST['user_id'];
    $status = $_POST['status'];
    
    if ($user->updateStatus($user_id, $status)) {
        Session::setFlash('success', 'User status updated successfully.');
    } else {
        Session::setFlash('error', 'Failed to update user status.');
    }
    
    // Redirect to avoid form resubmission
    header("Location: users.php");
    exit;
}

// Process user role update
if (isset($_POST['update_role']) && isset($_POST['user_id']) && isset($_POST['role'])) {
    $user_id = $_POST['user_id'];
    $role = $_POST['role'];
    
    // Prevent admin from changing their own role
    if ($user_id == Session::get('user_id')) {
        Session::setFlash('error', 'You cannot change your own role.');
        header("Location: users.php");
        exit;
    }
    
    if ($user->updateRole($user_id, $role)) {
        Session::setFlash('success', 'User role updated successfully.');
    } else {
        Session::setFlash('error', 'Failed to update user role.');
    }
    
    // Redirect to avoid form resubmission
    header("Location: users.php");
    exit;
}

// Process user deletion
if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    
    // Prevent admin from deleting themselves
    if ($user_id == Session::get('user_id')) {
        Session::setFlash('error', 'You cannot delete your own account.');
        header("Location: users.php");
        exit;
    }
    
    if ($user->delete($user_id)) {
        Session::setFlash('success', 'User deleted successfully.');
    } else {
        Session::setFlash('error', 'Failed to delete user.');
    }
    
    // Redirect to avoid form resubmission
    header("Location: users.php");
    exit;
}

// Handle search and filtering
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$from_record_num = ($records_per_page * $page) - $records_per_page;

// Get users based on filters
if (!empty($search) || !empty($role_filter) || !empty($status_filter)) {
    $stmt = $user->searchUsers($search, $role_filter, $status_filter, $from_record_num, $records_per_page);
    $total_rows = $user->countSearchResults($search, $role_filter, $status_filter);
} else {
    $stmt = $user->getAllUsers($from_record_num, $records_per_page);
    $total_rows = $user->countAll();
}

// Calculate total pages
$total_pages = ceil($total_rows / $records_per_page);

// Include header
$page_title = "Manage Users";
include_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Users</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add_user.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-user-plus"></i> Add New User
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
                                    <label for="search" class="form-label">Search</label>
                                    <input type="text" class="form-control" id="search" name="search" placeholder="Search by name, email or username" value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="role" class="form-label">Filter by Role</label>
                                    <select name="role" id="role" class="form-select">
                                        <option value="">All Roles</option>
                                        <option value="admin" <?php echo ($role_filter == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                        <option value="user" <?php echo ($role_filter == 'user') ? 'selected' : ''; ?>>User</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Filter by Status</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="">All Statuses</option>
                                        <option value="active" <?php echo ($status_filter == 'active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($status_filter == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                    <a href="users.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-sync-alt"></i> Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                            <td>
                                <?php if ($row['id'] != Session::get('user_id')): ?>
                                <form action="" method="post" class="role-form">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                    <select name="role" class="form-select form-select-sm role-select" data-original="<?php echo $row['role']; ?>">
                                        <option value="admin" <?php echo ($row['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                        <option value="user" <?php echo ($row['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                                    </select>
                                    <button type="submit" name="update_role" class="btn btn-sm btn-primary update-role-btn" style="display: none;">
                                        Update
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="badge bg-primary"><?php echo ucfirst($row['role']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['id'] != Session::get('user_id')): ?>
                                <form action="" method="post" class="status-form">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                    <select name="status" class="form-select form-select-sm status-select" data-original="<?php echo $row['status']; ?>">
                                        <option value="active" <?php echo ($row['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($row['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary update-status-btn" style="display: none;">
                                        Update
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="badge bg-success">Active</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($row['id'] != Session::get('user_id')): ?>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $row['id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                                
                                <!-- Delete Modal -->
                                <div class="modal fade" id="deleteModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteModalLabel<?php echo $row['id']; ?>">Confirm Deletion</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to delete user <strong><?php echo htmlspecialchars($row['username']); ?></strong>? This action cannot be undone.
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <form action="" method="post">
                                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" name="delete_user" class="btn btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if ($stmt->rowCount() == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center">No users found.</td>
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
                        <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
                            First
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
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
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
                            &raquo;
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
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
        // Show update button when role changes
        const roleSelects = document.querySelectorAll('.role-select');
        
        roleSelects.forEach(select => {
            select.addEventListener('change', function() {
                const originalValue = this.getAttribute('data-original');
                const updateBtn = this.parentElement.querySelector('.update-role-btn');
                
                if (this.value !== originalValue) {
                    updateBtn.style.display = 'inline-block';
                } else {
                    updateBtn.style.display = 'none';
                }
            });
        });
        
        // Show update button when status changes
        const statusSelects = document.querySelectorAll('.status-select');
        
        statusSelects.forEach(select => {
            select.addEventListener('change', function() {
                const originalValue = this.getAttribute('data-original');
                const updateBtn = this.parentElement.querySelector('.update-status-btn');
                
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