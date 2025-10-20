<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../utils/Session.php';

Session::start();
if (!Session::isLoggedIn() || !Session::isAdmin()) {
    Session::setFlash('error', 'You do not have permission to access this page.');
    header('Location: ../login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

// Determine mode: create or edit
$isEdit = isset($_GET['id']) && is_numeric($_GET['id']);
if ($isEdit) {
    $product->id = (int)$_GET['id'];
    $product->readOne();
}

// Fetch categories for suggestions
$categories = $product->getCategories();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $price = isset($_POST['price']) ? trim($_POST['price']) : '';
    $categoryVal = isset($_POST['category']) ? trim($_POST['category']) : '';
    $stockVal = isset($_POST['stock']) ? trim($_POST['stock']) : '';

    if ($name === '' || $price === '' || $categoryVal === '' || $stockVal === '') {
        Session::setFlash('error', 'Please fill in all required fields.');
        header('Location: product_form.php' . ($isEdit ? ('?id=' . $product->id) : ''));
        exit;
    }

    $product->name = $name;
    $product->description = $description;
    $product->price = is_numeric($price) ? (float)$price : 0;
    $product->category = $categoryVal;
    $product->stock = is_numeric($stockVal) ? (int)$stockVal : 0;

    // Handle image upload if present
    $uploadedFileName = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $_FILES['image']['tmp_name'];
        $origName = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];
        if (!in_array($ext, $allowed)) {
            Session::setFlash('error', 'Invalid image type. Allowed: jpg, jpeg, png, gif');
            header('Location: product_form.php' . ($isEdit ? ('?id=' . $product->id) : ''));
            exit;
        }

        $uploadDir = dirname(__DIR__) . '/assets/images/products/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
        $uploadedFileName = uniqid('prod_') . '_' . $safeBase . '.' . $ext;
        $targetPath = $uploadDir . $uploadedFileName;

        if (!move_uploaded_file($tmpPath, $targetPath)) {
            Session::setFlash('error', 'Failed to upload image.');
            header('Location: product_form.php' . ($isEdit ? ('?id=' . $product->id) : ''));
            exit;
        }
        $product->image = $uploadedFileName;
    } else {
        // Preserve existing image on edit if no new file uploaded
        if ($isEdit) {
            $product->image = $product->image; // keep current
        } else {
            // leave empty for new product if none uploaded
            $product->image = $product->image ?? '';
        }
    }

    $ok = $isEdit ? $product->update() : $product->create();

    if ($ok) {
        Session::setFlash('success', $isEdit ? 'Product updated successfully.' : 'Product created successfully.');
        header('Location: products.php');
        exit;
    } else {
        Session::setFlash('error', $isEdit ? 'Failed to update product.' : 'Failed to create product.');
        header('Location: product_form.php' . ($isEdit ? ('?id=' . $product->id) : ''));
        exit;
    }
}

$page_title = $isEdit ? 'Edit Product' : 'Add Product';
include_once __DIR__ . '/includes/header.php';
include_once __DIR__ . '/includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="row">
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="products.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Products</a>
            </div>

            <?php if (Session::hasFlash('success')): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars(Session::getFlash('success')); ?></div>
            <?php endif; ?>
            <?php if (Session::hasFlash('error')): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars(Session::getFlash('error')); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">Product Details</div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product->name ?? ''); ?>" required />
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter product description..."><?php echo htmlspecialchars($product->description ?? ''); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="price" class="form-label">Price</label>
                                        <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($product->price ?? ''); ?>" required />
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="stock" class="form-label">Stock</label>
                                        <input type="number" min="0" class="form-control" id="stock" name="stock" value="<?php echo htmlspecialchars($product->stock ?? ''); ?>" required />
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="category" class="form-label">Category</label>
                                        <input list="categoriesList" type="text" class="form-control" id="category" name="category" value="<?php echo htmlspecialchars($product->category ?? ''); ?>" required />
                                        <datalist id="categoriesList">
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo htmlspecialchars($cat); ?>"></option>
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label for="image" class="form-label">Product Image</label>
                                    <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png,.gif" />
                                    <div class="form-text">Recommended: JPG/PNG/GIF</div>
                                </div>
                                <?php if (!empty($product->image)): ?>
                                    <div class="mb-3">
                                        <label class="form-label">Current Image</label>
                                        <div>
                                            <img src="../assets/images/products/<?php echo htmlspecialchars($product->image); ?>" alt="Current Image" style="max-width: 100%; height: auto;" />
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Update Product' : 'Create Product'; ?></button>
                        <a href="products.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>