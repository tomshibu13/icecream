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

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    Session::setFlash('error', 'User ID is required.');
    header("Location: users.php");
    exit;
}

$user_id = $_GET['id'];

// Get user data
if (!$user->readOne($user_id)) {
    Session::setFlash('error', 'User not found.');
    header("Location: users.php");
    exit;
}

// Process form submission
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $role = trim($_POST['role']);
    $status = trim($_POST['status']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    if (empty($first_name)) {
        $errors[] = "First name is required.";
    }
    
    if (empty($last_name)) {
        $errors[] = "Last name is required.";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif ($email !== $user->email && $user->emailExists($email)) {
        $errors[] = "Email already exists.";
    }
    
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif ($username !== $user->username && $user->usernameExists($username)) {
        $errors[] = "Username already exists.";
    }
    
    // Password is optional during edit
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        }
        
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
    }
    
    if (empty($role)) {
        $errors[] = "Role is required.";
    }
    
    if (empty($status)) {
        $errors[] = "Status is required.";
    }
    
    // Prevent admin from demoting themselves
    if (Session::get('user_id') == $user_id && $role != 'admin') {
        $errors[] = "You cannot change your own admin role.";
    }
    
    // If no errors, update user
    if (empty($errors)) {
        $user->id = $user_id;
        $user->first_name = $first_name;
        $user->last_name = $last_name;
        $user->email = $email;
        $user->username = $username;
        $user->role = $role;
        $user->status = $status;
        
        // Only update password if provided
        if (!empty($password)) {
            $user->password = password_hash($password, PASSWORD_DEFAULT);
        }
        
        if ($user->update()) {
            Session::setFlash('success', 'User updated successfully.');
            header("Location: users.php");
            exit;
        } else {
            $errors[] = "Failed to update user.";
        }
    }
}

// Include header
$page_title = "Edit User";
include_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Edit User</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="users.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Users
                    </a>
                </div>
            </div>
            
            <!-- Display Errors -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Edit User Form -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">User Information</h5>
                </div>
                <div class="card-body">
                    <form action="" method="post">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user->first_name); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user->last_name); ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user->email); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user->username); ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Leave blank to keep current password. New password must be at least 6 characters long.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required <?php echo (Session::get('user_id') == $user_id) ? 'disabled' : ''; ?>>
                                    <option value="">Select Role</option>
                                    <option value="admin" <?php echo ($user->role === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    <option value="user" <?php echo ($user->role === 'user') ? 'selected' : ''; ?>>User</option>
                                </select>
                                <?php if (Session::get('user_id') == $user_id): ?>
                                    <input type="hidden" name="role" value="admin">
                                    <div class="form-text text-warning">You cannot change your own role.</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required <?php echo (Session::get('user_id') == $user_id) ? 'disabled' : ''; ?>>
                                    <option value="">Select Status</option>
                                    <option value="active" <?php echo ($user->status === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($user->status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                                <?php if (Session::get('user_id') == $user_id): ?>
                                    <input type="hidden" name="status" value="active">
                                    <div class="form-text text-warning">You cannot deactivate your own account.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="users.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle password visibility
        const toggleButtons = document.querySelectorAll('.toggle-password');
        
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    });
</script>

<?php include_once 'includes/footer.php'; ?>