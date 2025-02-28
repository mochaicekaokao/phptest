<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch wishlist items with product details
$stmt = $pdo->prepare("
    SELECT 
        w.wishlist_id,
        w.created_at,
        p.product_id,
        p.name,
        p.price,
        p.image_url
    FROM wishlist w
    JOIN products p ON w.product_id = p.product_id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-title {
            color: #333;
            margin-bottom: 20px;
        }

        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .wishlist-item {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .wishlist-item:hover {
            transform: translateY(-5px);
        }

        .item-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .item-info {
            padding: 15px;
        }

        .item-name {
            margin: 0 0 10px 0;
            font-size: 1.1em;
        }

        .item-price {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .item-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }

        .remove-btn,
        .add-to-cart-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .remove-btn {
            background-color: #ff4444;
            color: white;
        }

        .remove-btn:hover {
            background-color: #cc0000;
        }

        .add-to-cart-btn {
            background-color: #4CAF50;
            color: white;
        }

        .add-to-cart-btn:hover {
            background-color: #45a049;
        }

        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            background-color: #45a049;
        }

        .empty-wishlist {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <h1 class="page-title">My Wishlist</h1>

        <?php if (empty($wishlist_items)): ?>
            <div class="empty-wishlist">
                <i class="fas fa-heart" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                <p>Your wishlist is empty.</p>
            </div>
        <?php else: ?>
            <div class="wishlist-grid">
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="wishlist-item" id="wishlist-item-<?php echo $item['wishlist_id']; ?>">
                        <img src="<?php
                                    $image_path = '../' . $item['image_url'];
                                    if (!file_exists($image_path) || empty($item['image_url'])) {
                                        $image_path = '../uploads/products/image-icon-600nw-211642900.webp';
                                    }
                                    echo htmlspecialchars($image_path);
                                    ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image"
                            onerror="this.src='../uploads/products/image-icon-600nw-211642900.webp';">
                        <div class="item-info">
                            <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <div class="item-price">RM <?php echo number_format($item['price'], 2); ?></div>
                            <div class="item-actions">
                                <button onclick="removeFromWishlist(<?php echo $item['wishlist_id']; ?>)" class="remove-btn">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                                <button
                                    onclick="addToCart(<?php echo $item['product_id']; ?>, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>)"
                                    class="add-to-cart-btn">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function removeFromWishlist(wishlistId) {
            if (confirm('Are you sure you want to remove this item from your wishlist?')) {
                fetch('remove_from_wishlist.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            wishlist_id: wishlistId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const item = document.getElementById(`wishlist-item-${wishlistId}`);
                            item.style.animation = 'fadeOut 0.3s ease';
                            setTimeout(() => {
                                item.remove();
                                // Check if wishlist is empty
                                if (document.querySelector('.wishlist-grid').children.length === 0) {
                                    location.reload(); // Reload to show empty state
                                }
                            }, 300);
                        } else {
                            alert(data.message || 'Error removing item from wishlist');
                        }
                    });
            }
        }

        function addToCart(productId, name, price) {
            fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        name: name,
                        price: price,
                        quantity: 1
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Added to cart successfully!');
                    } else {
                        alert('Failed to add to cart.');
                    }
                });
        }
    </script>
</body>

</html>