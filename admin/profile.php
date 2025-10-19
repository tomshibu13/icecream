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

// Get user ID from session
$user_id = Session::get('user_id');

// Get user data
$user->id = $user_id;
$user->readOne();

// No need to check for $user_data as readOne() doesn't return anything

// Process form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update profile information
    if (isset($_POST['update_profile'])) {
        // Validate input
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        
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
        
        // If no errors, update user
        if (empty($errors)) {
            $user->first_name = $first_name;
            $user->last_name = $last_name;
            $user->email = $email;
            $user->username = $username;
            
            if ($user->update()) {
                Session::setFlash('success', 'Profile updated successfully.');
                $success = true;
                
                // Update session data
                Session::set('username', $username);
                
                // Refresh user data
                $user_data = $user->readOne();
            } else {
                $errors[] = "Failed to update profile.";
            }
        }
    }
    
    // Change password
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password)) {
            $errors[] = "Current password is required.";
        } elseif (!password_verify($current_password, $user_data['password'])) {
            $errors[] = "Current password is incorrect.";
        }
        
        if (empty($new_password)) {
            $errors[] = "New password is required.";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
        
        // If no errors, update password
        if (empty($errors)) {
            $user->password = password_hash($new_password, PASSWORD_DEFAULT);
            
            if ($user->updatePassword()) {
                Session::setFlash('success', 'Password changed successfully.');
                $success = true;
            } else {
                $errors[] = "Failed to change password.";
            }
        }
    }
}

// Include header
$page_title = "My Profile";
include_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Profile</h1>
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
            
            <!-- Profile Information -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Account Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user->first_name . '+' . $user->last_name); ?>&background=random&size=128" class="rounded-circle img-thumbnail" alt="Profile Picture">
                                <h4 class="mt-3"><?php echo htmlspecialchars($user->first_name . ' ' . $user->last_name); ?></h4>
                                <span class="badge bg-primary"><?php echo ucfirst($user->role); ?></span>
                            </div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-user me-2"></i> Username</span>
                                    <span class="text-muted"><?php echo htmlspecialchars($user->username); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-envelope me-2"></i> Email</span>
                                    <span class="text-muted"><?php echo htmlspecialchars($user->email); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-calendar me-2"></i> Joined</span>
                                    <span class="text-muted"><?php echo date('M d, Y', strtotime($user->created_at)); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-key me-2"></i> Last Password Change</span>
                                    <span class="text-muted">N/A</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <!-- Edit Profile Form -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">Edit Profile</h5>
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
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user->email); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user->username); ?>" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Change Password Form -->
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="card-title mb-0">Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form action="" method="post">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Password must be at least 6 characters long.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" name="change_password" class="btn btn-warning">
                                        <i class="fas fa-key me-2"></i> Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
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