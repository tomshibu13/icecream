<?php
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'utils/Session.php';

// Start session
Session::start();

// Check if user is already logged in
if (Session::isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = "";

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Initialize user object
    $user = new User($db);
    
    // Set user property values
    $user->username = $_POST["username"];
    $user->password = $_POST["password"];
    
    // Attempt login
    if ($user->login()) {
        // Set session variables
        $is_admin = ($user->role === 'admin');
        Session::setUser($user->id, $user->username, $is_admin);
        
        // Redirect based on user role
        if ($is_admin) {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}

// Include header
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="form-container">
            <h2 class="text-center mb-4">Login</h2>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control password-input" id="password" name="password" required>
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
            
            <div class="mt-3 text-center">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>