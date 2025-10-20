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

// Initialize Product object
$product = new Product($db);

// Handle product deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = $_GET['delete'];
    if ($product->delete($id)) {
        Session::setFlash('success', 'Product deleted successfully.');
    } else {
        Session::setFlash('error', 'Failed to delete product.');
    }
    header("Location: products.php");
    exit;
}

// Handle CSV upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_products'])) {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        Session::setFlash('error', 'Please select a valid CSV file.');
        header('Location: products.php');
        exit;
    }

    $tmpPath = $_FILES['csv_file']['tmp_name'];
    $filename = $_FILES['csv_file']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        Session::setFlash('error', 'Only CSV files are allowed.');
        header('Location: products.php');
        exit;
    }

    $handle = fopen($tmpPath, 'r');
    if ($handle === false) {
        Session::setFlash('error', 'Failed to read CSV file.');
        header('Location: products.php');
        exit;
    }

    $header = fgetcsv($handle);
    if ($header === false) {
        Session::setFlash('error', 'CSV file appears to be empty.');
        header('Location: products.php');
        exit;
    }

    $map = array_flip(array_map('strtolower', $header));
    $required = ['name','price','category','stock'];
    foreach ($required as $col) {
        if (!isset($map[$col])) {
            fclose($handle);
            Session::setFlash('error', 'Missing required column: ' . $col);
            header('Location: products.php');
            exit;
        }
    }

    $imported = 0;
    $failed = 0;

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) === 0) { continue; }
        if (count($row) === 1 && trim($row[0]) === '') { continue; }

        $name = $row[$map['name']] ?? '';
        $description = isset($map['description']) ? ($row[$map['description']] ?? '') : '';
        $price = $row[$map['price']] ?? '';
        $image = isset($map['image']) ? ($row[$map['image']] ?? '') : '';
        $categoryVal = $row[$map['category']] ?? '';
        $stockVal = $row[$map['stock']] ?? '';

        if ($name === '' || $price === '' || $categoryVal === '' || $stockVal === '') { $failed++; continue; }

        $product->name = $name;
        $product->description = $description;
        $product->price = is_numeric($price) ? (float)$price : 0;
        $product->image = $image;
        $product->category = $categoryVal;
        $product->stock = is_numeric($stockVal) ? (int)$stockVal : 0;

        if ($product->create()) { $imported++; } else { $failed++; }
    }
    fclose($handle);

    if ($imported > 0) {
        Session::setFlash('success', 'Imported ' . $imported . ' products' . ($failed ? ' (' . $failed . ' failed)' : ''));
    } else {
        Session::setFlash('error', 'Import failed. ' . $failed . ' rows could not be imported.');
    }

    header('Location: products.php');
    exit;
}

// Set up pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$from_record_num = ($records_per_page * $page) - $records_per_page;

// Handle search and filtering
$search = isset($_GET['search']) ? $_GET['search'] : "";
$category = isset($_GET['category']) ? $_GET['category'] : "";

// Get products based on search/filter
if (!empty($search)) {
    $stmt = $product->search($search, $from_record_num, $records_per_page);
    $total_rows = $product->countSearchResults($search);
} else if (!empty($category)) {
    $stmt = $product->getProductsByCategory($category, $from_record_num, $records_per_page);
    $total_rows = $product->getTotalProductsByCategory($category);
} else {
    $stmt = $product->getProducts($from_record_num, $records_per_page);
    $total_rows = $product->getTotalProducts();
}

// Get categories for filter dropdown
$categories = $product->getCategories();

// Include header
$page_title = "Manage Products";
include_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once __DIR__ . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Products</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="product_form.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus"></i> Add New Product
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#uploadCsvModal">
                            <i class="fas fa-upload"></i> Upload CSV
                        </button>
                    </div>
                </div>
            </div>

            <!-- Upload CSV Modal -->
            <div class="modal fade" id="uploadCsvModal" tabindex="-1" aria-labelledby="uploadCsvLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="upload_products" value="1" />
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="uploadCsvLabel">Upload Products (CSV)</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="csv_file" class="form-label">CSV File</label>
                                    <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required />
                                </div>
                                <div class="small text-muted">
                                    Columns: <code>name, description, price, category, stock, image</code>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Upload</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form action="" method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                </div>
                <div class="col-md-6">
                    <form action="" method="GET" class="d-flex">
                        <select name="category" class="form-select me-2">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo ($category == $cat) ? 'selected' : ''; ?>>
                                    <?php echo $cat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-secondary">Filter</button>
                    </form>
                </div>
            </div>

            <!-- Products Table -->
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($stmt->rowCount() > 0): ?>
                            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td>
                                        <?php if (!empty($row['image'])): ?>
                                            <img src="../assets/images/products/<?php echo $row['image']; ?>" alt="<?php echo $row['name']; ?>" width="50">
                                        <?php else: ?>
                                            <img src="../assets/images/no-image.jpg" alt="No Image" width="50">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['category']; ?></td>
                                    <td>$<?php echo number_format($row['price'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($row['stock'] <= 10) ? 'danger' : (($row['stock'] <= 20) ? 'warning' : 'success'); ?>">
                                            <?php echo $row['stock']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="product_form.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="products.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No products found.</td>
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
                            <a class="page-link" href="?page=1<?php echo (!empty($search)) ? '&search='.$search : ''; echo (!empty($category)) ? '&category='.$category : ''; ?>">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page-1; echo (!empty($search)) ? '&search='.$search : ''; echo (!empty($category)) ? '&category='.$category : ''; ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $total_pages); $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; echo (!empty($search)) ? '&search='.$search : ''; echo (!empty($category)) ? '&category='.$category : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page+1; echo (!empty($search)) ? '&search='.$search : ''; echo (!empty($category)) ? '&category='.$category : ''; ?>">Next</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; echo (!empty($search)) ? '&search='.$search : ''; echo (!empty($category)) ? '&category='.$category : ''; ?>">Last</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>