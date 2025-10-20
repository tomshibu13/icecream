<?php
require_once __DIR__ . '/../utils/Session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

Session::start();
if (!Session::isLoggedIn() || !Session::isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Profile';

$db = (new Database())->getConnection();
$user = new User($db);
$user->id = Session::getUserId();
$user->readOne();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username   = isset($_POST['username']) ? trim($_POST['username']) : $user->username;
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : $user->first_name;
    $last_name  = isset($_POST['last_name']) ? trim($_POST['last_name']) : $user->last_name;
    $email      = isset($_POST['email']) ? trim($_POST['email']) : $user->email;

    $user->username   = $username;
    $user->first_name = $first_name;
    $user->last_name  = $last_name;
    $user->email      = $email;
    // Preserve current status so update() doesn't null it
    if (!isset($user->status)) { $user->status = 'active'; }

    if ($user->update()) {
        Session::setFlash('success', 'Profile updated successfully.');
    } else {
        Session::setFlash('error', 'Failed to update profile.');
    }
    header('Location: profile.php');
    exit;
}

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $new_password     = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if (strlen($new_password) < 6) {
        Session::setFlash('error', 'Password must be at least 6 characters.');
    } elseif ($new_password !== $confirm_password) {
        Session::setFlash('error', 'Passwords do not match.');
    } else {
        $user->password = $new_password;
        if ($user->updatePassword()) {
            Session::setFlash('success', 'Password updated successfully.');
        } else {
            Session::setFlash('error', 'Failed to update password.');
        }
    }
    header('Location: profile.php');
    exit;
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="row">
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Admin Profile</h1>
            </div>

            <?php if (Session::hasFlash('success')): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars(Session::getFlash('success')); ?></div>
            <?php endif; ?>
            <?php if (Session::hasFlash('error')): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars(Session::getFlash('error')); ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-7">
                    <div class="card mb-4">
                        <div class="card-header">Profile Details</div>
                        <div class="card-body">
                            <form method="post" action="profile.php">
                                <input type="hidden" name="update_profile" value="1" />

                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user->username); ?>" required />
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user->first_name); ?>" required />
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user->last_name); ?>" required />
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user->email); ?>" required />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <input type="text" class="form-control" value="admin" readonly />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user->status ?? 'active'); ?>" readonly />
                                </div>

                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card mb-4">
                        <div class="card-header">Change Password</div>
                        <div class="card-body">
                            <form method="post" action="profile.php">
                                <input type="hidden" name="update_password" value="1" />

                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required />
                                </div>

                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required />
                                </div>

                                <button type="submit" class="btn btn-warning">Update Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>