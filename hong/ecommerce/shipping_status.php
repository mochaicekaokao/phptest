<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Add this at the top of the file, after session_start()
$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    $success_message = "Thank you for your rating!";
}
if (isset($_GET['error'])) {
    $error_message = "Error submitting rating: " . htmlspecialchars($_GET['error']);
}

// Handle delivery confirmation and rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shipping_id'])) {
    $shipping_id = $_POST['shipping_id'];
    $product_id = $_POST['product_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];

    try {
        $pdo->beginTransaction();

        // Debug information
        error_log("Submitting rating - Shipping ID: $shipping_id, Product ID: $product_id, Rating: $rating");

        // Update shipping status to delivered
        $update_stmt = $pdo->prepare("
            UPDATE shipping 
            SET shipping_status = 'delivered' 
            WHERE shipping_id = ? AND order_id IN (
                SELECT order_id FROM orders WHERE user_id = ?
            )
        ");
        $update_stmt->execute([$shipping_id, $_SESSION['user_id']]);

        // Add rating with product_id
        $rating_stmt = $pdo->prepare("
            INSERT INTO rating (
                shipping_id, 
                user_id, 
                product_id, 
                rating, 
                comment, 
                created_at
            ) VALUES (
                :shipping_id,
                :user_id,
                :product_id,
                :rating,
                :comment,
                CURRENT_TIMESTAMP
            )
        ");

        $rating_stmt->execute([
            ':shipping_id' => $shipping_id,
            ':user_id' => $_SESSION['user_id'],
            ':product_id' => $product_id,
            ':rating' => $rating,
            ':comment' => $comment
        ]);

        $pdo->commit();

        // Instead of using alert and reload, just redirect
        header('Location: shipping_status.php?success=1');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Rating submission error: " . $e->getMessage());
        header('Location: shipping_status.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

// Fetch shipping details with ratings and products
$stmt = $pdo->prepare("
    SELECT s.*, o.order_date, o.total_amount, 
           r.rating, r.comment, r.created_at as rating_date,
           oi.product_id, p.name as product_name, p.image_url,
           r.rating_id
    FROM shipping s
    JOIN orders o ON s.order_id = o.order_id
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    LEFT JOIN rating r ON (s.shipping_id = r.shipping_id AND oi.product_id = r.product_id)
    WHERE o.user_id = ?
    ORDER BY s.shipping_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Status</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            color: #333;
            margin-bottom: 30px;
        }

        .shipping-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .shipping-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s ease;
        }

        .shipping-card:hover {
            transform: translateY(-5px);
        }

        .product-info {
            display: flex;
            align-items: center;
            margin: 15px 0;
            gap: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }

        .product-info h4 {
            margin: 0;
            font-size: 1.1em;
            color: #333;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            display: inline-block;
            margin: 10px 0;
        }

        .status-pending {
            background-color: #ffd700;
            color: #000;
        }

        .status-shipped {
            background-color: #87CEEB;
            color: #000;
        }

        .status-delivered {
            background-color: #90EE90;
            color: #000;
        }

        .shipping-details {
            margin: 15px 0;
        }

        .shipping-details p {
            margin: 8px 0;
            color: #666;
        }

        .shipping-details strong {
            color: #333;
        }

        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-top: 15px;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #45a049;
        }

        .rating-section {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .rating-section h4 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .rating-stars {
            color: #ffd700;
            font-size: 1.2em;
            margin-bottom: 10px;
        }

        .rating-stars i {
            margin-right: 2px;
        }

        .rating-section p {
            color: #666;
            font-style: italic;
            margin: 10px 0;
        }

        .rating-section small {
            color: #999;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            margin: 15% auto;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            border-radius: 8px;
            position: relative;
        }

        .star-rating {
            text-align: center;
            font-size: 2em;
            margin: 20px 0;
        }

        .star-rating i {
            cursor: pointer;
            margin: 0 5px;
            transition: color 0.3s ease;
        }

        .star-rating i:hover {
            color: #ffd700;
        }

        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 10px 0;
            font-family: inherit;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-buttons button {
            flex: 1;
        }

        .modal-buttons button[type="button"] {
            background-color: #999;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .shipping-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 10px;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
                padding: 20px;
            }
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
        <h1>My Shipments</h1>
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        <div class="shipping-grid">
            <?php foreach ($shipments as $shipment): ?>
                <div class="shipping-card">
                    <h3>Order #<?php echo $shipment['order_id']; ?></h3>
                    <div class="product-info">
                        <img src="../<?php echo htmlspecialchars($shipment['image_url']); ?>"
                            alt="<?php echo htmlspecialchars($shipment['product_name']); ?>"
                            class="product-image"
                            onerror="this.src='../uploads/products/image-icon-600nw-211642900.webp'">
                        <div>
                            <h4><?php echo htmlspecialchars($shipment['product_name']); ?></h4>
                        </div>
                    </div>
                    <div class="shipping-details">
                        <p>
                            <strong>Status:</strong>
                            <span class="status-badge status-<?php echo strtolower($shipment['shipping_status']); ?>">
                                <?php echo ucfirst($shipment['shipping_status']); ?>
                            </span>
                        </p>
                        <p><strong>Delivery Method:</strong> <?php echo $shipment['shipping_method']; ?></p>
                        <p><strong>Tracking Number:</strong> <?php echo $shipment['tracking_number'] ?: 'Not available yet'; ?></p>
                    </div>

                    <?php if (($shipment['shipping_status'] === 'shipped' || $shipment['shipping_status'] === 'delivered') && !isset($shipment['rating_id'])): ?>
                        <button onclick="showRatingModal(
                            <?php echo $shipment['shipping_id']; ?>, 
                            <?php echo $shipment['product_id']; ?>, 
                            '<?php echo htmlspecialchars($shipment['product_name']); ?>'
                        )">
                            <?php echo $shipment['shipping_status'] === 'delivered' ? 'Rate Product' : 'Confirm Delivery & Rate Product'; ?>
                        </button>
                    <?php endif; ?>

                    <?php if (isset($shipment['rating_id'])): ?>
                        <div class="rating-section">
                            <h4>Your Rating for <?php echo htmlspecialchars($shipment['product_name']); ?></h4>
                            <div class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $shipment['rating'] ? 'active' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <?php if ($shipment['comment']): ?>
                                <p><em>"<?php echo htmlspecialchars($shipment['comment']); ?>"</em></p>
                            <?php endif; ?>
                            <small>Rated on <?php echo date('F j, Y', strtotime($shipment['rating_date'])); ?></small>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Rating Modal -->
    <div id="ratingModal" class="modal">
        <div class="modal-content">
            <h2>Rate Your Product</h2>
            <p id="productName" style="margin-bottom: 15px;"></p>
            <form id="ratingForm" method="POST" onsubmit="return validateRating(event)">
                <input type="hidden" name="shipping_id" id="shipping_id_input">
                <input type="hidden" name="product_id" id="product_id_input">
                <div class="star-rating" id="starRating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="far fa-star" data-rating="<?php echo $i; ?>"></i>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="ratingInput">
                <textarea name="comment" placeholder="Share your experience (optional)" rows="4"></textarea>
                <div class="modal-buttons">
                    <button type="submit">Submit Rating</button>
                    <button type="button" onclick="closeRatingModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add this success modal to your HTML -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <h2>Thank You!</h2>
            <p>Your rating has been submitted successfully.</p>
            <button onclick="closeSuccessModal()">OK</button>
        </div>
    </div>

    <script>
        function showRatingModal(shippingId, productId, productName) {
            document.getElementById('ratingModal').style.display = 'block';
            document.getElementById('shipping_id_input').value = shippingId;
            document.getElementById('product_id_input').value = productId;
            document.getElementById('productName').textContent = 'Rating for: ' + productName;
            // Reset form
            document.getElementById('ratingInput').value = '';
            document.getElementById('ratingForm').reset();
            resetStars();
        }

        function closeRatingModal() {
            document.getElementById('ratingModal').style.display = 'none';
            resetStars();
        }

        function resetStars() {
            const stars = document.querySelectorAll('.star-rating .fa-star');
            stars.forEach(star => {
                star.classList.remove('fas', 'far');
                star.classList.add('far');
            });
        }

        function validateRating(event) {
            const rating = document.getElementById('ratingInput').value;
            const shippingId = document.getElementById('shipping_id_input').value;
            const productId = document.getElementById('product_id_input').value;

            console.log('Validating form:', {
                rating: rating,
                shippingId: shippingId,
                productId: productId
            });

            if (!rating) {
                alert('Please select a rating before submitting.');
                event.preventDefault();
                return false;
            }

            if (!shippingId || !productId) {
                alert('Missing required information. Please try again.');
                event.preventDefault();
                return false;
            }

            return true;
        }

        // Star rating functionality
        const stars = document.querySelectorAll('.star-rating .fa-star');
        stars.forEach(star => {
            star.addEventListener('mouseover', function() {
                const rating = this.dataset.rating;
                highlightStars(rating);
            });

            star.addEventListener('mouseout', function() {
                const selectedRating = document.getElementById('ratingInput').value;
                if (selectedRating) {
                    highlightStars(selectedRating);
                } else {
                    resetStars();
                }
            });

            star.addEventListener('click', function() {
                const rating = this.dataset.rating;
                document.getElementById('ratingInput').value = rating;
                highlightStars(rating);
                console.log('Rating selected:', rating); // Debug log
            });
        });

        function highlightStars(rating) {
            stars.forEach(star => {
                const starRating = star.dataset.rating;
                star.classList.remove('fas', 'far');
                if (starRating <= rating) {
                    star.classList.add('fas');
                } else {
                    star.classList.add('far');
                }
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('ratingModal')) {
                closeRatingModal();
            }
        }
    </script>
</body>

</html>