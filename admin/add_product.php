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

// Process form submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
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
                $product->image = $new_filename;
            } else {
                $message = "<div class='alert alert-danger'>Failed to upload image.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Invalid file type. Allowed types: jpg, jpeg, png, gif.</div>";
        }
    } else {
        // Set default image if no image uploaded
        $product->image = 'default.jpg';
    }
    
    // Create product
    if (empty($message)) {
        if ($product->create()) {
            Session::setFlash('success', 'Product added successfully.');
            header("Location: products.php");
            exit;
        } else {
            $message = "<div class='alert alert-danger'>Failed to add product.</div>";
        }
    }
}

// Get all categories for dropdown
$categories = $product->getCategories();

// Include header
$page_title = "Add Product";
include_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Add New Product</h1>
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
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                                        <?php endforeach; ?>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3" id="newCategoryDiv" style="display: none;">
                                    <label for="new_category" class="form-label">New Category</label>
                                    <input type="text" class="form-control" id="new_category" name="new_category">
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="price" class="form-label">Price ($)</label>
                                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="stock" class="form-label">Stock</label>
                                        <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="image" class="form-label">Product Image</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                    <div class="form-text">Recommended size: 800x600 pixels. Max file size: 2MB.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <div id="imagePreview" class="mt-2" style="display: none;">
                                        <img src="" alt="Image Preview" class="img-thumbnail" style="max-width: 200px;">
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="add_product" class="btn btn-primary">
                                        <i class="fas fa-plus-circle"></i> Add Product
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Product Guidelines</h5>
                        </div>
                        <div class="card-body">
                            <h6>Product Name</h6>
                            <p>Choose a descriptive name that clearly identifies the ice cream flavor or product.</p>
                            
                            <h6>Category</h6>
                            <p>Select an appropriate category or create a new one if needed.</p>
                            
                            <h6>Price</h6>
                            <p>Set a competitive price based on ingredients, production costs, and market rates.</p>
                            
                            <h6>Stock</h6>
                            <p>Enter the initial inventory quantity available for sale.</p>
                            
                            <h6>Description</h6>
                            <p>Write a detailed description highlighting flavor profile, ingredients, and unique selling points.</p>
                            
                            <h6>Image</h6>
                            <p>Upload a high-quality image that showcases the product attractively.</p>
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