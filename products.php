<?php
require_once 'config/database.php';
require_once 'models/Product.php';
require_once 'includes/header.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize product object
$product = new Product($db);

// Get products based on search or category filter
$products = [];
$search_term = "";
$category = "";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $_GET['search'];
    $stmt = $product->search($search_term);
} else if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category = $_GET['category'];
    $product->category = $category;
    $stmt = $product->readByCategory();
} else {
    $stmt = $product->readAll();
}

// Get all products
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $products[] = $row;
}

// Get all categories for filter
$categories = [];
$stmt = $db->query("SELECT DISTINCT category FROM products ORDER BY category");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $categories[] = $row['category'];
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Our Ice Cream Products</h2>
    </div>
    <div class="col-md-6">
        <form action="products.php" method="get" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Search products..." value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>
</div>

<div class="row">
    <!-- Sidebar with filters -->
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Categories</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="products.php" class="list-group-item list-group-item-action <?php echo empty($category) ? 'active' : ''; ?>">All Categories</a>
                    <?php foreach ($categories as $cat): ?>
                    <a href="products.php?category=<?php echo urlencode($cat); ?>" class="list-group-item list-group-item-action <?php echo ($category == $cat) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Products grid -->
    <div class="col-md-9">
        <?php if (!empty($search_term)): ?>
        <div class="alert alert-info">
            Search results for: <strong><?php echo htmlspecialchars($search_term); ?></strong>
            <a href="products.php" class="float-end">Clear search</a>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($category)): ?>
        <div class="alert alert-info">
            Category: <strong><?php echo htmlspecialchars($category); ?></strong>
            <a href="products.php" class="float-end">Show all categories</a>
        </div>
        <?php endif; ?>
        
        <?php if (empty($products)): ?>
        <div class="alert alert-warning">No products found.</div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($products as $product): ?>
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="card product-card">
                    <img src="assets/images/<?php echo $product['image']; ?>" class="card-img-top" alt="<?php echo $product['name']; ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $product['name']; ?></h5>
                        <p class="card-text"><?php echo substr($product['description'], 0, 60); ?>...</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="price">$<?php echo number_format($product['price'], 2); ?></span>
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>