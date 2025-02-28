<?php
session_start();
require_once 'db_connection.php';

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($category_id > 0) {
    // Get category details
    $category_sql = "SELECT * FROM categories WHERE category_id = ?";
    $stmt = mysqli_prepare($conn, $category_sql);
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);
    $category_result = mysqli_stmt_get_result($stmt);
    $category = mysqli_fetch_assoc($category_result);

    if (!$category) {
        header("Location: index.php");
        exit();
    }

    // Get products in this category
    $products_sql = "SELECT p.*, c.category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.category_id 
                    WHERE p.category_id = ?
                    ORDER BY p.product_id DESC";

    $stmt = mysqli_prepare($conn, $products_sql);
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);
    $products = mysqli_stmt_get_result($stmt);
} else {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['category_name']); ?> - Phone Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="category-header">
        <div class="container">
            <h1><?php echo htmlspecialchars($category['category_name']); ?></h1>
            <?php if (!empty($category['description'])): ?>
                <p><?php echo htmlspecialchars($category['description']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="products-section">
        <div class="container">
            <div class="products-grid">
                <?php if (mysqli_num_rows($products) > 0): ?>
                    <?php while ($product = mysqli_fetch_assoc($products)):
                        $image_path = 'uploads/products/' . $product['image_url'];
                        if (!file_exists($image_path) || empty($product['image_url'])) {
                            $image_path = 'images/default-product.jpg';
                        }
                    ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?php echo htmlspecialchars($image_path); ?>"
                                    alt="<?php echo htmlspecialchars($product['name']); ?>"
                                    loading="lazy"
                                    onerror="this.src='images/default-product.jpg';">
                            </div>
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="price">RM <?php echo number_format($product['price'], 2); ?></p>
                                <button onclick="checkLogin()" class="add-to-cart-btn">Add to Cart</button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-products">
                        <p>No products available in this category.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        function checkLogin() {
            <?php if (!isset($_SESSION['user_id'])): ?>
                alert('Please login first to access this feature.');
                location.href = 'login.php';
            <?php endif; ?>
        }
    </script>
</body>

</html>