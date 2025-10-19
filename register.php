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
$success = false;

// Process registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Initialize user object
    $user = new User($db);
    
    // Set user property values
    $user->username = isset($_POST["username"]) ? $_POST["username"] : "";
    $user->email = isset($_POST["email"]) ? $_POST["email"] : "";
    $user->password = isset($_POST["password"]) ? $_POST["password"] : "";
    $user->first_name = isset($_POST["first_name"]) ? $_POST["first_name"] : "";
    $user->last_name = isset($_POST["last_name"]) ? $_POST["last_name"] : "";
    $user->role = "customer";
    $user->status = "active";
    
    // Validate password
    if (strlen($user->password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } 
    // Check if passwords match
    else if (!isset($_POST["confirm_password"]) || $_POST["password"] !== $_POST["confirm_password"]) {
        $error = "Passwords do not match.";
    }
    // Check if username exists
    else if ($user->usernameExists()) {
        $error = "Username already exists.";
    }
    // Check if email exists
    else if ($user->emailExists()) {
        $error = "Email already exists.";
    }
    // Create user
    else if ($user->create()) {
        $success = true;
    } else {
        $error = "Unable to register. Please try again.";
    }
}

// Include header
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="form-container">
            <h2 class="text-center mb-4">Register</h2>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                Registration successful! You can now <a href="login.php">login</a>.
            </div>
            <?php else: ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">Last Name *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                </div>
                

                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control password-input" id="password" name="password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Password must be at least 6 characters long.</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Register</button>
                </div>
            </form>
            
            <?php endif; ?>
            
            <div class="mt-3 text-center">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>