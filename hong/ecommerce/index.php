<?php
session_start();
$host = "localhost";
$user = "root";
$password = "";
$db = "ecommerce";

$conn = mysqli_connect($host, $user, $password, $db);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch categories with their products
$categories_sql = "SELECT c.*, 
                         GROUP_CONCAT(p.product_id) as product_ids
                  FROM categories c
                  LEFT JOIN products p ON c.category_id = p.category_id
                  GROUP BY c.category_id
                  ORDER BY c.category_name";
$categories = mysqli_query($conn, $categories_sql);

// Store categories data for reuse
$categories_data = [];
while ($category = mysqli_fetch_assoc($categories)) {
    $categories_data[] = $category;
}

// Add this function for wishlist functionality
function isProductInWishlist($conn, $user_id, $product_id)
{
    $sql = "SELECT 1 FROM wishlist WHERE user_id = ? AND product_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_num_rows($result) > 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phone Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        html {
            scroll-behavior: smooth;
        }

        .category-section {
            padding: 2rem 0;
        }

        .category-title {
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #000;
        }

        .user-dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
            z-index: 1;
            border-radius: 4px;
        }

        .user-dropdown:hover .dropdown-content {
            display: block;
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
            border-radius: 4px;
        }

        .welcome-message {
            margin-right: 15px;
        }

        /* Add wishlist button styles */
        .wishlist-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            position: relative;
            z-index: 2;
        }

        .wishlist-btn i {
            color: #ccc;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .wishlist-btn i.active {
            color: #ff4444;
        }

        .wishlist-btn:hover i {
            color: #ff4444;
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
            width: 100%;
        }

        .product-header h3 {
            margin: 0;
            flex: 1;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <a href="index.php" style="display: flex; align-items: center; text-decoration: none;">
                    GIGABYTE
                    <img src="/uploads/logo/logo.png" alt="GIGABYTE" style="width: 50px; height: 50px; margin-left: 10px;">
                </a>
            </div>

            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search products..." onkeyup="searchProducts(this.value)">
                <button><i class="fas fa-search"></i></button>
            </div>

            <div class="nav-buttons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="cart.php">
                        <button class="nav-btn">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count"><?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?></span>
                        </button>
                    </a>
                    <span style="color:black" class="welcome-message">
                        Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                    </span>
                    <div class="user-dropdown">
                        <button class="nav-btn">
                            <i class="fas fa-user"></i>
                        </button>
                        <div class="dropdown-content">
                            <a href="order_status.php"><i class="fas fa-shopping-bag"></i> Order Status</a>
                            <a href="shipping_status.php"><i class="fas fa-truck"></i> Shipping Status</a>
                            <a href="edit_profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
                            <a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a>
                        </div>
                    </div>
                    <button class="login-btn" onclick="location.href='logout.php'">
                        Logout
                    </button>
                <?php else: ?>
                    <button class="nav-btn" onclick="checkLogin()">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count">0</span>
                    </button>
                    <button class="nav-btn" onclick="checkLogin()">
                        <i class="fas fa-user"></i>
                    </button>
                    <button class="login-btn" onclick="location.href='login.php'">
                        Login
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="categories">
        <div class="container">
            <?php foreach ($categories_data as $category): ?>
                <?php if (!empty($category['product_ids'])): ?>
                    <a href="#category-<?php echo $category['category_id']; ?>"
                        class="category-item">
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="banner-slider">
        <div class="banner active" style="background-image: linear-gradient(45deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.2) 100%), url('../uploads/banners/banner1.jpg');">
            <div class="banner-content">
                <h1>Latest Smartphones</h1>
                <p>Discover the newest technology with amazing features</p>
                <button onclick="location.href='#products'">Shop Now</button>
            </div>
        </div>
        <div class="banner" style="background-image: linear-gradient(45deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.2) 100%), url('../uploads/banners/banner2.jpg');">
            <div class="banner-content">
                <h1>Special Offers</h1>
                <p>Get amazing deals on selected phones</p>
                <button onclick="location.href='#products'">View Offers</button>
            </div>
        </div>
        <div class="banner" style="background-image: linear-gradient(45deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.2) 100%), url('../uploads/banners/banner3.jpg');">
            <div class="banner-content">
                <h1>Premium Phones</h1>
                <p>Experience the best in mobile technology</p>
                <button onclick="location.href='#products'">Explore Now</button>
            </div>
        </div>
        <div class="banner-controls">
            <button class="prev-btn"><i class="fas fa-chevron-left"></i></button>
            <div class="banner-dots"></div>
            <button class="next-btn"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>

    <section id="products" class="products">
        <div id="searchResults" class="products-grid" style="display: none;"></div>
        <div id="regularProducts">
            <h2>Our Products</h2>

            <?php foreach ($categories_data as $category):
                // Skip categories with no products
                if (empty($category['product_ids'])) continue;

                // Fetch products for this category
                $product_ids = $category['product_ids'];
                $products_sql = "SELECT p.*, c.category_name 
                               FROM products p 
                               LEFT JOIN categories c ON p.category_id = c.category_id 
                               WHERE p.product_id IN ($product_ids)
                               ORDER BY p.name";
                $products = mysqli_query($conn, $products_sql);
            ?>
                <div id="category-<?php echo $category['category_id']; ?>" class="category-section">
                    <h3 class="category-title"><?php echo htmlspecialchars($category['category_name']); ?></h3>
                    <div class="products-grid">
                        <?php while ($product = mysqli_fetch_assoc($products)):
                            $image_path = 'uploads/products' . $product['image_url'];
                            if (!file_exists($image_path) || empty($product['image_url'])) {
                                $image_path = '../uploads/products/image-icon-600nw-211642900.webp';
                            }
                        ?>

                            <div class="product-card">
                                <div class="product-image" onclick="showProductDetails(<?php echo htmlspecialchars(json_encode([
                                                                                            'id' => $product['product_id'],
                                                                                            'name' => $product['name'],
                                                                                            'price' => $product['price'],
                                                                                            'image' => '../' . $product['image_url'],
                                                                                            'stock' => $product['stock_quantity'],
                                                                                            'description' => $product['description']
                                                                                        ])); ?>)">
                                    <img src="<?php
                                                $image_path = '../' . $product['image_url'];
                                                if (!file_exists($image_path) || empty($product['image_url'])) {
                                                    $image_path = '../uploads/products/image-icon-600nw-211642900.webp';
                                                }
                                                echo htmlspecialchars($image_path);
                                                ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy"
                                        onerror="this.src='../uploads/products/image-icon-600nw-211642900.webp';">
                                </div>
                                <div class="product-info">
                                    <div class="product-header">
                                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                            <div class="wishlist-btn" onclick="event.stopPropagation(); toggleWishlist(<?php echo $product['product_id']; ?>, this)">
                                                <i class="fas fa-heart <?php echo isProductInWishlist($conn, $_SESSION['user_id'], $product['product_id']) ? 'active' : ''; ?>"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <p class="price">RM <?php echo number_format($product['price'], 2); ?></p>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <div class="cart-controls">
                                            <input type="number" min="1" value="1" class="quantity-input"
                                                id="quantity-<?php echo $product['product_id']; ?>"
                                                onclick="event.stopPropagation();"
                                                onchange="event.stopPropagation();">
                                            <button onclick="event.stopPropagation(); addToCart(<?php echo $product['product_id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['price']; ?>)"
                                                class="add-to-cart-btn">
                                                Add to Cart
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <button onclick="checkLogin()" class="add-to-cart-btn">Add to Cart</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About Us</h3>
                    <p>Your trusted source for the latest smartphones and accessories.</p>
                    <p>Stay Connected, Stay <strong>GIGABYTE</strong>!</p>
                    <p><a href="aboutus.php" style="color: #e44d26; text-decoration: none;">Learn more about us &rarr;</a></p>
                </div>
                <div class="footer-section">
                    
                    <h3>Contact</h3>
                    <p>Email: info@gigabyte.com</p>
                    <p>Phone: (123) 456-7890</p>
                </div>
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3983.537791254664!2d101.72398217475026!3d3.2152605527431537!2m3!1f0!2f0!3f0
                !3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31cc3843bfb6a031%3A0x2dc5e067aae3ab84!2sTunku%20Abdul%20Rahman%20University%20of%20Management%20and%
                20Technology%20(TAR%20UMT)!5e0!3m2!1sen!2smy!4v1739954364352!5m2!1sen!2smy"
                    width="400" height="250" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>

            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Gigabyte. All rights reserved.</p>
            </div>

        </div>
    </footer>

    <script>
        function checkLogin() {
            <?php if (!isset($_SESSION['user_id'])): ?>
                alert('Please login first to access this feature.');
                location.href = 'login.php';
            <?php endif; ?>
        }

        function addToCart(productId, productName, productPrice) {
            <?php if (!isset($_SESSION['user_id'])): ?>
                alert('Please login first to access this feature.');
                location.href = 'login.php';
                return;
            <?php endif; ?>

            event.stopPropagation(); // Prevent event bubbling
            const quantity = parseInt(document.getElementById(`quantity-${productId}`).value) || 1;

            fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        name: productName,
                        price: productPrice,
                        quantity: quantity
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('.cart-count').textContent = data.cart_count;
                        alert('Added to cart!');
                    } else {
                        alert('Failed to add to cart.');
                    }
                });
        }

        function toggleWishlist(productId, button) {
            <?php if (!isset($_SESSION['user_id'])): ?>
                alert('Please login first to access this feature.');
                location.href = 'login.php';
                return;
            <?php endif; ?>

            event.stopPropagation();

            fetch('toggle_wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const heartIcon = button.querySelector('i');
                        heartIcon.classList.toggle('active');
                    } else {
                        alert(data.message || 'Error updating wishlist');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating wishlist. Please try again.');
                });
        }

        // Smooth scroll for category links
        document.querySelectorAll('.category-item').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);

                // Calculate offset for fixed navbar
                const navbarHeight = document.querySelector('.navbar').offsetHeight;
                const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - navbarHeight;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });

                // Update active state
                document.querySelectorAll('.category-item').forEach(item => {
                    item.classList.remove('active');
                });
                this.classList.add('active');
            });
        });

        // Highlight active category on scroll
        window.addEventListener('scroll', function() {
            const categories = document.querySelectorAll('.category-section');
            const categoryLinks = document.querySelectorAll('.category-item');
            const navbarHeight = document.querySelector('.navbar').offsetHeight;

            categories.forEach((category, index) => {
                const rect = category.getBoundingClientRect();
                if (rect.top <= navbarHeight + 50 && rect.bottom >= navbarHeight) {
                    categoryLinks.forEach(link => link.classList.remove('active'));
                    categoryLinks[index].classList.add('active');
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const banners = document.querySelectorAll('.banner');
            const dotsContainer = document.querySelector('.banner-dots');
            const prevBtn = document.querySelector('.prev-btn');
            const nextBtn = document.querySelector('.next-btn');
            let currentSlide = 0;

            // Create dots
            banners.forEach((_, index) => {
                const dot = document.createElement('button');
                dot.classList.add('dot');
                if (index === 0) dot.classList.add('active');
                dot.addEventListener('click', () => goToSlide(index));
                dotsContainer.appendChild(dot);
            });

            const dots = document.querySelectorAll('.dot');

            function updateSlide() {
                banners.forEach(banner => banner.classList.remove('active'));
                dots.forEach(dot => dot.classList.remove('active'));
                banners[currentSlide].classList.add('active');
                dots[currentSlide].classList.add('active');
            }

            function goToSlide(index) {
                currentSlide = index;
                updateSlide();
            }

            function nextSlide() {
                currentSlide = (currentSlide + 1) % banners.length;
                updateSlide();
            }

            function prevSlide() {
                currentSlide = (currentSlide - 1 + banners.length) % banners.length;
                updateSlide();
            }

            prevBtn.addEventListener('click', prevSlide);
            nextBtn.addEventListener('click', nextSlide);

            // Auto-advance slides
            setInterval(nextSlide, 5000);
        });

        let searchTimeout;

        function searchProducts(query) {
            clearTimeout(searchTimeout);
            const searchResults = document.getElementById('searchResults');
            const regularProducts = document.getElementById('regularProducts');

            if (query.length < 2) {
                searchResults.style.display = 'none';
                regularProducts.style.display = 'block';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`search_products.php?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displaySearchResults(data.products);
                        } else {
                            console.error('Search error:', data.error);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }, 300);
        }

        function displaySearchResults(products) {
            const searchResults = document.getElementById('searchResults');
            const regularProducts = document.getElementById('regularProducts');

            if (products.length === 0) {
                searchResults.innerHTML = '<p class="no-results">No products found</p>';
                searchResults.style.display = 'block';
                regularProducts.style.display = 'none';
                return;
            }

            let html = '';
            products.forEach(product => {
                html += `
                    <div class="product-card">
                        <div class="product-image" onclick="showProductDetails(${JSON.stringify(product)})">
                            <img src="${product.image}" alt="${product.name}" loading="lazy"
                                onerror="this.src='../uploads/products/image-icon-600nw-211642900.webp';">
                        </div>
                        <div class="product-info">
                            <div class="product-header">
                                <h3>${product.name}</h3>
                                ${isLoggedIn ? `
                                    <div class="wishlist-btn" onclick="event.stopPropagation(); toggleWishlist(${product.id}, this)">
                                        <i class="fas fa-heart"></i>
                                    </div>
                                ` : ''}
                            </div>
                            <p class="price">RM ${parseFloat(product.price).toFixed(2)}</p>
                            ${isLoggedIn ? `
                                <div class="cart-controls">
                                    <input type="number" min="1" value="1" class="quantity-input"
                                        id="quantity-${product.id}"
                                        onclick="event.stopPropagation();"
                                        onchange="event.stopPropagation();">
                                    <button onclick="event.stopPropagation(); addToCart(${product.id}, '${product.name}', ${product.price})"
                                        class="add-to-cart-btn">
                                        Add to Cart
                                    </button>
                                </div>
                            ` : `
                                <button onclick="checkLogin()" class="add-to-cart-btn">Add to Cart</button>
                            `}
                        </div>
                    </div>
                `;
            });

            searchResults.innerHTML = html;
            searchResults.style.display = 'grid';
            regularProducts.style.display = 'none';
        }

        // Add this at the start of your script section
        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    </script>

    <!-- Add this modal HTML before the closing </body> tag -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="product-details">
                <div class="product-image-section">
                    <img id="modalProductImage" src="" alt="Product Image">
                </div>
                <div class="product-info-section">
                    <h2 id="modalProductName"></h2>
                    <p class="price">RM <span id="modalProductPrice"></span></p>
                    <div class="rating-info" id="modalRatingInfo"></div>
                    <div class="stock-info">Stock: <span id="modalProductStock"></span></div>
                    <div class="attributes-section"></div>
                    <p class="description" id="modalProductDescription"></p>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 900px;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .close {
            position: absolute;
        }

        .product-image-section img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            object-fit: cover;
        }

        .product-info-section {
            flex: 1;
        }

        .product-info-section h2 {
            margin: 0 0 15px 0;
            color: #333;
        }

        .price {
            font-size: 1.5em;
            color: #e44d26;
            margin: 10px 0;
        }

        .stock-info {
            margin: 10px 0;
            color: #666;
        }

        .attributes-section {
            margin: 20px 0;
        }

        .attribute-item {
            margin: 10px 0;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 4px;
        }

        .description {
            margin-top: 20px;
            line-height: 1.6;
            color: #666;
        }

        @media (max-width: 768px) {
            .product-details {
                flex-direction: column;
            }

            .product-image-section {
                flex: none;
            }
        }
    </style>

    <!-- Add this script before the closing </body> tag -->
    <script>
        function showProductDetails(productData) {
            event.preventDefault(); // Prevent any default click behavior

            // Update modal content
            document.getElementById('modalProductImage').src = productData.image;
            document.getElementById('modalProductName').textContent = productData.name;
            document.getElementById('modalProductPrice').textContent = parseFloat(productData.price).toFixed(2);
            document.getElementById('modalProductStock').textContent = productData.stock;
            document.getElementById('modalProductDescription').textContent = productData.description;

            // Fetch and display product attributes
            fetch('get_product_attributes.php?product_id=' + productData.id)
                .then(response => response.json())
                .then(attributes => {
                    const attributesSection = document.querySelector('.attributes-section');
                    attributesSection.innerHTML = '';

                    attributes.forEach(attr => {
                        const attrDiv = document.createElement('div');
                        attrDiv.className = 'attribute-item';
                        attrDiv.innerHTML = `
                        <strong>${attr.attribute_name}:</strong> ${attr.attribute_value}
                    `;
                        attributesSection.appendChild(attrDiv);
                    });
                });

            // Show modal
            document.getElementById('productModal').style.display = 'block';
        }

        // Close modal when clicking the X or outside the modal
        document.querySelector('.close').onclick = function() {
            document.getElementById('productModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>

    <?php include 'chat.php'; ?>
</body>

</html>