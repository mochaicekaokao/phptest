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

// Fetch categories for the navigation
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - GIGABYTE</title>
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

        /* About Us Page Specific Styles */
        .about-section {
            padding: 60px 0;
        }

        .about-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .about-header h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 20px;
        }

        .about-header p {
            font-size: 1.1rem;
            color: #666;
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .about-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-bottom: 60px;
        }

        .about-text {
            flex: 1;
            padding: 0 100px;
            min-width: 300px;
        }

        .about-image {
            flex: 1;
            padding: 0 20px;
            min-width: 300px;
            text-align: center;
        }

        .about-image img {
            max-width: 100%;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .mission-values {
            background-color: #f9f9f9;
            padding: 60px 0;
            margin: 40px 0;
        }

        .mission-values h2 {
            text-align: center;
            margin-bottom: 40px;
            color: #333;
        }

        .values-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
        }

        .value-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            flex: 1;
            min-width: 250px;
            max-width: 350px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .value-card:hover {
            transform: translateY(-10px);
        }

        .value-card i {
            font-size: 2.5rem;
            color: #e44d26;
            margin-bottom: 20px;
        }

        .value-card h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .team-section {
            padding: 60px 0;
        }

        .team-section h2 {
            text-align: center;
            margin-bottom: 40px;
            color: #333;
        }

        .team-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
        }

        .team-member {
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            flex: 1;
            min-width: 250px;
            max-width: 300px;
            transition: transform 0.3s ease;
        }

        .team-member:hover {
            transform: translateY(-10px);
        }

        .team-member img {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }

        .team-info {
            padding: 20px;
            text-align: center;
        }

        .team-info h3 {
            margin-bottom: 5px;
            color: #333;
        }

        .team-info p {
            color: #666;
            margin-bottom: 15px;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .social-links a {
            color: #333;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .social-links a:hover {
            color: #e44d26;
        }

        .timeline {
            position: relative;
            max-width: 1200px;
            margin: 50px auto;
        }

        .timeline::after {
            content: '';
            position: absolute;
            width: 6px;
            background-color: #e44d26;
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -3px;
        }

        .timeline-item {
            padding: 10px 40px;
            position: relative;
            width: 50%;
            box-sizing: border-box;
        }

        .timeline-item::after {
            content: '';
            position: absolute;
            width: 25px;
            height: 25px;
            background-color: white;
            border: 4px solid #e44d26;
            border-radius: 50%;
            top: 15px;
            z-index: 1;
        }

        .left {
            left: 0;
        }

        .right {
            left: 50%;
        }

        .left::after {
            right: -17px;
        }

        .right::after {
            left: -17px;
        }

        .timeline-content {
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .timeline-content h3 {
            margin-top: 0;
            color: #333;
        }

        @media screen and (max-width: 768px) {
            .timeline::after {
                left: 31px;
            }

            .timeline-item {
                width: 100%;
                padding-left: 70px;
                padding-right: 25px;
            }

            .timeline-item::after {
                left: 15px;
            }

            .left::after, .right::after {
                left: 15px;
            }

            .right {
                left: 0;
            }
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
                    <a href="index.php#category-<?php echo $category['category_id']; ?>"
                        class="category-item">
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <main>
        <section class="about-section">
            <div class="container">
                <div class="about-header">
                    <h1>About GIGABYTE</h1>
                    <p>Your trusted source for the latest smartphones and accessories since 2010.</p>
                </div>

                <div class="about-content">
                    <div class="about-text">
                        <h2>Our Story</h2>
                        <p>GIGABYTE was founded in 2010 with a simple mission: to provide high-quality smartphones and accessories at affordable prices. What started as a small shop in Kuala Lumpur has grown into one of Malaysia's leading electronics retailers.</p>
                        <p>Over the years, we've expanded our product range to include the latest smartphones, tablets, and accessories from top brands around the world. Our commitment to quality, customer service, and technological innovation has made us a trusted name in the industry.</p>
                        <p>Today, GIGABYTE serves thousands of customers across Malaysia through our online store and physical locations. We continue to grow and evolve, always keeping our customers' needs at the heart of everything we do.</p>
                    </div>
                    <div class="about-image">
                        <img src="../uploads/about/store.jpg" alt="GIGABYTE Store" onerror="this.src='../uploads/products/image-icon-600nw-211642900.webp';">
                    </div>
                </div>
            </div>
        </section>

        <section class="mission-values">
            <div class="container">
                <h2>Our Mission & Values</h2>
                <div class="values-container">
                    <div class="value-card">
                        <i class="fas fa-star"></i>
                        <h3>Quality</h3>
                        <p>We are committed to offering only the highest quality products that meet our strict standards for performance and reliability.</p>
                    </div>
                    <div class="value-card">
                        <i class="fas fa-handshake"></i>
                        <h3>Customer Service</h3>
                        <p>We believe in building lasting relationships with our customers through exceptional service and support.</p>
                    </div>
                    <div class="value-card">
                        <i class="fas fa-lightbulb"></i>
                        <h3>Innovation</h3>
                        <p>We continuously seek out the latest technologies and products to keep our customers at the cutting edge.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="about-section">
            <div class="container">
                <h2 style="text-align: center; margin-bottom: 40px;">Our Journey</h2>
                <div class="timeline">
                    <div class="timeline-item left">
                        <div class="timeline-content">
                            <h3>2010</h3>
                            <p>GIGABYTE was founded in Kuala Lumpur, Malaysia, starting as a small mobile phone shop.</p>
                        </div>
                    </div>
                    <div class="timeline-item right">
                        <div class="timeline-content">
                            <h3>2013</h3>
                            <p>Expanded our business to include online sales, reaching customers across Malaysia.</p>
                        </div>
                    </div>
                    <div class="timeline-item left">
                        <div class="timeline-content">
                            <h3>2015</h3>
                            <p>Opened our second physical store in Penang and established partnerships with major global brands.</p>
                        </div>
                    </div>
                    <div class="timeline-item right">
                        <div class="timeline-content">
                            <h3>2018</h3>
                            <p>Launched our customer loyalty program and expanded our product range to include tablets and accessories.</p>
                        </div>
                    </div>
                    <div class="timeline-item left">
                        <div class="timeline-content">
                            <h3>2020</h3>
                            <p>Celebrated our 10th anniversary and revamped our online store with enhanced features.</p>
                        </div>
                    </div>
                    <div class="timeline-item right">
                        <div class="timeline-content">
                            <h3>2023</h3>
                            <p>Expanded to 5 physical stores across Malaysia and introduced our premium product line.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="team-section">
            <div class="container">
                <h2>Meet Our Team</h2>
                <div class="team-container">
                    <div class="team-member">
                        <img src="../uploads/about/ceo.jpg" alt="CEO" onerror="this.src='../uploads/products/image-icon-600nw-211642900.webp';">
                        <div class="team-info">
                            <h3>Ahmad Razif</h3>
                            <p>Founder & CEO</p>
                            <div class="social-links">
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                                <a href="#"><i class="fab fa-facebook"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="team-member">
                        <img src="../uploads/about/cto.jpg" alt="CTO" onerror="this.src='../uploads/products/image-icon-600nw-211642900.webp';">
                        <div class="team-info">
                            <h3>Mei Ling</h3>
                            <p>Chief Technology Officer</p>
                            <div class="social-links">
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                                <a href="#"><i class="fab fa-github"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="team-member">
                        <img src="../uploads/about/marketing.jpg" alt="Marketing Director" onerror="this.src='../uploads/products/image-icon-600nw-211642900.webp';">
                        <div class="team-info">
                            <h3>Raj Kumar</h3>
                            <p>Marketing Director</p>
                            <div class="social-links">
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="#"><i class="fab fa-instagram"></i></a>
                                <a href="#"><i class="fab fa-facebook"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

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

        function searchProducts(query) {
            if (query.length < 2) {
                return;
            }

            fetch(`search_products.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Redirect to index page with search query
                        window.location.href = `index.php?search=${encodeURIComponent(query)}`;
                    } else {
                        console.error('Search error:', data.error);
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    </script>

    <?php include 'chat.php'; ?>
</body>

</html> 