<?php
require_once '../config/database.php';
require_once '../models/Product.php';
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

// Initialize product object
$product = new Product($db);

// Get product ID from URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $product->id = $_GET['id'];
    $product->readOne();
    
    // Check if product exists
    if (!$product->name) {
        Session::setFlash('error', 'Product not found.');
        header("Location: products.php");
        exit;
    }
} else {
    // Redirect to products page if no ID provided
    header("Location: products.php");
    exit;
}

// Process form submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    // Set product properties
    $product->name = $_POST['name'];
    $product->description = $_POST['description'];
    $product->price = $_POST['price'];
    $product->category = $_POST['category'];
    $product->stock = $_POST['stock'];
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        // Check if file extension is allowed
        if (in_array(strtolower($file_ext), $allowed)) {
            // Generate unique filename
            $new_filename = uniqid() . '.' . $file_ext;
            $upload_path = '../assets/images/' . $new_filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Delete old image if it's not the default
                if ($product->image != 'default.jpg' && file_exists('../assets/images/' . $product->image)) {
                    unlink('../assets/images/' . $product->image);
                }
                
                $product->image = $new_filename;
            } else {
                $message = "<div class='alert alert-danger'>Failed to upload image.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Invalid file type. Allowed types: jpg, jpeg, png, gif.</div>";
        }
    }
    
    // Update product
    if (empty($message)) {
        if ($product->update()) {
            Session::setFlash('success', 'Product updated successfully.');
            header("Location: products.php");
            exit;
        } else {
            $message = "<div class='alert alert-danger'>Failed to update product.</div>";
        }
    }
}

// Get all categories for dropdown
$categories = $product->getCategories();

// Include header
$page_title = "Edit Product";
include_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Edit Product</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="products.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Products
                    </a>
                </div>
            </div>
            
            <?php echo $message; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <form action="" method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Product Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product->name); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category; ?>" <?php echo ($product->category == $category) ? 'selected' : ''; ?>>
                                            <?php echo $category; ?>
                                        </option>
                                        <?php endforeach; ?>
                                        <option value="other" <?php echo (!in_array($product->category, $categories)) ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3" id="newCategoryDiv" style="display: <?php echo (!in_array($product->category, $categories)) ? 'block' : 'none'; ?>">
                                    <label for="new_category" class="form-label">New Category</label>
                                    <input type="text" class="form-control" id="new_category" name="new_category" value="<?php echo (!in_array($product->category, $categories)) ? htmlspecialchars($product->category) : ''; ?>">
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="price" class="form-label">Price ($)</label>
                                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="<?php echo $product->price; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="stock" class="form-label">Stock</label>
                                        <input type="number" class="form-control" id="stock" name="stock" min="0" value="<?php echo $product->stock; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($product->description); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="image" class="form-label">Product Image</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                    <div class="form-text">Leave empty to keep current image. Recommended size: 800x600 pixels. Max file size: 2MB.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Current Image</label>
                                    <div>
                                        <img src="../assets/images/<?php echo $product->image; ?>" alt="<?php echo $product->name; ?>" class="img-thumbnail" style="max-width: 200px;">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div id="imagePreview" class="mt-2" style="display: none;">
                                        <label class="form-label">New Image Preview</label>
                                        <div>
                                            <img src="" alt="Image Preview" class="img-thumbnail" style="max-width: 200px;">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="update_product" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Product
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Product Information</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Product ID:</strong> <?php echo $product->id; ?></p>
                            <p><strong>Created:</strong> <?php echo date('F j, Y, g:i a', strtotime($product->created)); ?></p>
                            <p><strong>Last Updated:</strong> <?php echo date('F j, Y, g:i a', strtotime($product->modified)); ?></p>
                            
                            <hr>
                            
                            <h6>Editing Tips</h6>
                            <ul>
                                <li>Update product details to reflect current information</li>
                                <li>Adjust stock levels based on inventory</li>
                                <li>Upload a new image only if you want to replace the current one</li>
                                <li>Ensure description is accurate and compelling</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Show/hide new category input based on selection
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('category');
        const newCategoryDiv = document.getElementById('newCategoryDiv');
        const newCategoryInput = document.getElementById('new_category');
        
        categorySelect.addEventListener('change', function() {
            if (this.value === 'other') {
                newCategoryDiv.style.display = 'block';
                newCategoryInput.setAttribute('required', 'required');
            } else {
                newCategoryDiv.style.display = 'none';
                newCategoryInput.removeAttribute('required');
            }
        });
        
        // Image preview
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = imagePreview.querySelector('img');
        
        imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.setAttribute('src', e.target.result);
                    imagePreview.style.display = 'block';
                }
                
                reader.readAsDataURL(this.files[0]);
            } else {
                imagePreview.style.display = 'none';
            }
        });
    });
</script>

<?php include_once 'includes/footer.php'; ?>