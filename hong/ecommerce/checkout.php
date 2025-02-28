<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header('Location: login.php');
    exit();
}

// Check if items are selected for checkout
if (!isset($_SESSION['selected_for_checkout']) || empty($_SESSION['selected_for_checkout'])) {
    header('Location: cart.php');
    exit;
}

// Debug session
echo "<div style='font-size: 6px;'>Session contents (for debugging):<pre>";
print_r($_SESSION);
echo "</pre></div>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_started = false;
    try {
        // Calculate total order amount for selected items only
        $total_amount = 0;
        $selected_items = [];
        foreach ($_SESSION['selected_for_checkout'] as $index) {
            if (isset($_SESSION['cart'][$index])) {
                $item = $_SESSION['cart'][$index];
                $total_amount += $item['price'] * $item['quantity'];
                $selected_items[] = $item;
            }
        }

        // Start transaction
        $pdo->beginTransaction();
        $transaction_started = true;

        // Store user_id in a variable for clarity
        $user_id = $_SESSION['user_id'];

        // Insert into orders table
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                user_id, 
                total_amount, 
                status, 
                shipping_address, 
                payment_method,
                order_date
            ) VALUES (?, ?, 'pending', ?, ?, CURRENT_TIMESTAMP)
        ");

        $stmt->execute([
            $user_id,
            $total_amount,
            $_POST['shipping_address'],
            $_POST['payment_method']
        ]);
        $order_id = $pdo->lastInsertId();

        // Insert into payments table
        $stmt = $pdo->prepare("
            INSERT INTO payments (
                order_id,
                payment_method,
                amount,
                payment_date
            ) VALUES (?, ?, ?, CURRENT_TIMESTAMP)
        ");

        $stmt->execute([
            $order_id,
            $_POST['payment_method'],
            $total_amount
        ]);

        // Insert items into order_items table
        $stmt = $pdo->prepare("
            INSERT INTO order_items (
                order_id, 
                product_id, 
                quantity, 
                price_at_purchase
            ) VALUES (?, ?, ?, ?)
        ");

        foreach ($selected_items as $item) {
            $stmt->execute([
                $order_id,
                $item['product_id'],
                $item['quantity'],
                $item['price']
            ]);
        }

        $pdo->commit();

        // Remove checked out items from cart
        foreach ($_SESSION['selected_for_checkout'] as $index) {
            unset($_SESSION['cart'][$index]);
        }
        // Reindex the cart array
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        // Clear the selected items
        unset($_SESSION['selected_for_checkout']);

        // Redirect to order confirmation
        header('Location: order_confirmation.php?order_id=' . $order_id);
        exit;
    } catch (Exception $e) {
        if ($transaction_started) {
            $pdo->rollBack();
        }
        $error = "An error occurred while processing your order: " . $e->getMessage();
    }
}

// Calculate total for selected items
$total = 0;
$selected_items = [];
foreach ($_SESSION['selected_for_checkout'] as $index) {
    if (isset($_SESSION['cart'][$index])) {
        $item = $_SESSION['cart'][$index];
        $total += $item['price'] * $item['quantity'];
        $selected_items[] = $item;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Gigabyte</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f8f8f8;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-title {
            color: #000;
            font-size: 2em;
            margin: 20px 0;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .checkout-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }

        .checkout-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .form-section {
            margin-bottom: 30px;
        }

        .section-title {
            color: #000;
            font-size: 1.2em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #000;
            font-weight: 500;
        }

        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            transition: all 0.3s;
        }

        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #000;
            outline: none;
        }

        .order-summary {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            height: fit-content;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .summary-table th,
        .summary-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .summary-table th {
            color: #000;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .product-row td {
            padding: 15px 12px;
        }

        .product-name {
            color: #000;
            font-weight: 500;
        }

        .product-price {
            color: #000;
            font-weight: 500;
        }

        .total-row {
            font-weight: 600;
            color: #000;
            border-top: 2px solid #eee;
        }

        .total-row td {
            padding-top: 20px;
        }

        .submit-btn {
            background-color: #000;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            width: 100%;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .submit-btn:hover {
            background-color: #333;
            transform: translateY(-2px);
        }

        .back-link {
            display: inline-block;
            color: #000;
            text-decoration: none;
            margin-top: 20px;
            padding: 10px 0;
            transition: all 0.3s;
            border-bottom: 1px solid transparent;
        }

        .back-link:hover {
            border-bottom-color: #000;
        }

        .error {
            background-color: #fff;
            color: #000;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #000;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .payment-method {
            position: relative;
            padding: 15px;
            border: 2px solid #eee;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-method:hover {
            border-color: #000;
        }

        .payment-method input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .payment-method label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .payment-method i {
            font-size: 1.2em;
            color: #000;
        }

        .payment-method input[type="radio"]:checked+label {
            color: #000;
            font-weight: 600;
        }

        .payment-method input[type="radio"]:checked~.payment-method {
            border-color: #000;
            background-color: #f8f8f8;
        }

        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }

            .payment-methods {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 class="page-title">Checkout</h1>

        <?php if (isset($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="checkout-container">
            <div class="checkout-form">
                <form method="POST">
                    <div class="form-section">
                        <h2 class="section-title">Shipping Information</h2>
                        <div class="form-group">
                            <label for="shipping_address">Delivery Address</label>
                            <textarea id="shipping_address" name="shipping_address" required rows="4"
                                placeholder="Enter your complete delivery address"></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2 class="section-title">Payment Method</h2>
                        <div class="payment-methods">
                            <div class="payment-method">
                                <input type="radio" id="cash" name="payment_method" value="cash" required>
                                <label for="cash">
                                    <i class="fas fa-money-bill-wave"></i>
                                    Cash on Delivery
                                </label>
                            </div>
                            <div class="payment-method">
                                <input type="radio" id="bank_transfer" name="payment_method" value="bank_transfer">
                                <label for="bank_transfer">
                                    <i class="fas fa-university"></i>
                                    Bank Transfer
                                </label>
                            </div>
                            <div class="payment-method">
                                <input type="radio" id="credit_card" name="payment_method" value="credit_card">
                                <label for="credit_card">
                                    <i class="fas fa-credit-card"></i>
                                    Credit Card
                                </label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-lock"></i> Place Order
                    </button>

                    <a href="cart.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Cart
                    </a>
                </form>
            </div>

            <div class="order-summary">
                <h2 class="section-title">Order Summary</h2>
                <table class="summary-table">
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                    </tr>
                    <?php foreach ($selected_items as $item):
                        $item_total = $item['price'] * $item['quantity'];
                    ?>
                        <tr class="product-row">
                            <td class="product-name"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td class="product-price">RM <?php echo number_format($item['price'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="2">Total Amount:</td>
                        <td class="product-price">RM <?php echo number_format($total, 2); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Add active class to selected payment method
        document.querySelectorAll('.payment-method input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.payment-method').forEach(method => {
                    method.style.borderColor = '#ddd';
                    method.style.backgroundColor = 'white';
                });
                this.closest('.payment-method').style.borderColor = '#4CAF50';
                this.closest('.payment-method').style.backgroundColor = '#f1f8e9';
            });
        });
    </script>
</body>

</html>