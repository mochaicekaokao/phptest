<?php
session_start();
require_once 'db_connection.php';

if (!isset($_GET['order_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = $_GET['order_id'];

try {
    $pdo->beginTransaction();

    // Fetch order details with correct column name
    $stmt = $pdo->prepare("
        SELECT 
            o.*, 
            oi.*, 
            p.name as product_name,
            p.stock_quantity as current_stock
        FROM orders o 
        JOIN order_items oi ON o.order_id = oi.order_id 
        JOIN products p ON oi.product_id = p.product_id 
        WHERE o.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();

    if (empty($order_items)) {
        throw new Exception("Order not found");
    }

    // Update product quantities with correct column name
    $update_stmt = $pdo->prepare("
        UPDATE products 
        SET stock_quantity = stock_quantity - ? 
        WHERE product_id = ? AND stock_quantity >= ?
    ");

    foreach ($order_items as $item) {
        // Check if enough stock is available
        if ($item['current_stock'] < $item['quantity']) {
            throw new Exception("Not enough stock for product: " . $item['product_name']);
        }

        // Update product quantity
        $update_stmt->execute([
            $item['quantity'],
            $item['product_id'],
            $item['quantity']
        ]);

        // Verify the update was successful
        if ($update_stmt->rowCount() === 0) {
            throw new Exception("Failed to update quantity for product: " . $item['product_name']);
        }
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Order confirmation error: " . $e->getMessage());
    // You might want to handle this error appropriately
    header('Location: error.php?message=' . urlencode($e->getMessage()));
    exit;
}

// Get order details from first row
$order = [
    'order_id' => $order_items[0]['order_id'],
    'order_date' => $order_items[0]['order_date'],
    'shipping_address' => $order_items[0]['shipping_address'],
    'payment_method' => $order_items[0]['payment_method'],
    'status' => $order_items[0]['status']
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
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

        .confirmation-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .success-message {
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
            color: #3c763d;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .order-details {
            margin-top: 20px;
        }

        .order-info {
            margin: 20px 0;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            border: 1px solid #000;
        }

        .order-info h3,
        .order-details h3 {
            color: #000;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #fff;
        }

        th {
            background-color: #000;
            color: #fff;
            padding: 15px;
            text-align: left;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #ddd;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 25px;
            background-color: #000;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .back-btn:hover {
            background-color: #333;
            transform: translateY(-2px);
        }

        /* Success Card Popup Styles */
        .card {
            overflow: hidden;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: left;
            border-radius: 0.5rem;
            max-width: 590px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            background-color: #fff;
            z-index: 1000;
        }

        /* Add overlay background */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .div_image_v {
            background: #47c9a2;
            border-bottom: none;
            position: relative;
            text-align: center;
            margin: -20px -20px 0;
            border-radius: 5px 5px 0 0;
            padding: 35px;
        }

        .dismiss {
            position: absolute;
            right: 10px;
            top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            background-color: #fff;
            color: #333;
            border: 2px solid #D1D5DB;
            font-size: 1rem;
            font-weight: 600;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            transition: .3s ease;
            cursor: pointer;
        }

        .dismiss:hover {
            background-color: #ff4444;
            border: 2px solid #ff4444;
            color: #fff;
        }

        .header {
            padding: 1.25rem 1rem 1rem 1rem;
        }

        .image {
            display: flex;
            margin-left: auto;
            margin-right: auto;
            background-color: #e2feee;
            flex-shrink: 0;
            justify-content: center;
            align-items: center;
            width: 3rem;
            height: 3rem;
            border-radius: 9999px;
            animation: animate .6s linear alternate-reverse infinite;
            transition: .6s ease;
        }

        .image svg {
            color: #0afa2a;
            width: 2rem;
            height: 2rem;
        }

        .content {
            margin-top: 0.75rem;
            text-align: center;
        }

        .title {
            color: #066e29;
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.5rem;
        }

        .message {
            margin-top: 0.5rem;
            color: #595b5f;
            font-size: 0.875rem;
            line-height: 1.25rem;
        }

        @keyframes animate {
            from {
                transform: scale(1);
            }

            to {
                transform: scale(1.09);
            }
        }
    </style>
</head>

<body>
    <!-- Add overlay div -->
    <div class="overlay"></div>

    <!-- Add success card -->
    <div class="card" id="successCard">
        <div class="header">
            <div class="div_image_v">
                <div class="image">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                        <g id="SVGRepo_iconCarrier">
                            <path d="M20 7L9.00004 18L3.99994 13" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        </g>
                    </svg>
                </div>
            </div>
            <div class="content">
                <span class="title">Order confirmed</span>
                <p class="message">Thank you for your purchase. Your package will be delivered within 3 days of your purchase</p>
                <button class="dismiss" type="button" onclick="hideCard()">x</button>
            </div>
        </div>
    </div>

    <div class="confirmation-container">
        <div class="success-message">
            <h2>Order Confirmed!</h2>
            <p>Your order #<?php echo $order_id; ?> has been successfully placed.</p>
        </div>

        <div class="order-info">
            <h3>Order Information</h3>
            <p><strong>Order Date:</strong> <?php echo date('Y-m-d H:i:s', strtotime($order['order_date'])); ?></p>
            <p><strong>Status:</strong> <?php echo ucfirst($order['status']); ?></p>
            <p><strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
            <p><strong>Shipping Address:</strong><br><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
        </div>

        <div class="order-details">
            <h3>Order Details</h3>
            <table width="100%" border="1">
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price (RM)</th>
                    <th>Total (RM)</th>
                </tr>
                <?php
                $total = 0;
                foreach ($order_items as $item):
                    $item_total = $item['price_at_purchase'] * $item['quantity'];
                    $total += $item_total;
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>RM <?php echo number_format($item['price_at_purchase'], 2); ?></td>
                        <td>RM <?php echo number_format($item_total, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3" align="right"><strong>Total:</strong></td>
                    <td><strong>RM <?php echo number_format($total, 2); ?></strong></td>
                </tr>
            </table>
        </div>

        <a href="index.php" class="back-btn">Continue Shopping</a>
    </div>

    <script>
        // Show card and overlay on page load
        window.onload = function() {
            document.querySelector('.overlay').style.display = 'block';
            document.getElementById('successCard').style.display = 'block';
        }

        // Hide card and overlay when dismiss button is clicked
        function hideCard() {
            document.querySelector('.overlay').style.display = 'none';
            document.getElementById('successCard').style.display = 'none';
        }
    </script>
</body>

</html>