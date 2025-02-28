<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user's orders with their items
$stmt = $pdo->prepare("
    SELECT 
        o.order_id,
        o.order_date,
        o.total_amount,
        o.status,
        o.shipping_address,
        o.payment_method,
        o.shipping_date,
        o.delivery_date,
        GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR ', ') as items
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE o.user_id = ?
    GROUP BY o.order_id
    ORDER BY o.order_date DESC
");

$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .orders-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .order-card {
            border-bottom: 1px solid #eee;
            padding: 20px;
            transition: background-color 0.3s;
        }

        .order-card:last-child {
            border-bottom: none;
        }

        .order-card:hover {
            background-color: #f9f9f9;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .order-id {
            font-weight: 600;
            color: #333;
        }

        .order-date {
            color: #666;
            font-size: 0.9em;
        }

        .order-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .order-details {
            margin-top: 10px;
            color: #666;
        }

        .order-items {
            margin: 10px 0;
            line-height: 1.6;
        }

        .order-address {
            font-size: 0.9em;
            color: #666;
            margin-top: 10px;
        }

        .payment-method {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }

        .total-amount {
            font-weight: 600;
            color: #2c3e50;
            margin-top: 10px;
        }

        .dates-info {
            font-size: 0.85em;
            color: #666;
            margin-top: 10px;
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

        .no-orders {
            text-align: center;
            padding: 40px;
            color: #666;
        }


        .cancel-btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: #d9534f;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            margin-top: 10px;
        }

        .cancel-btn:hover {
            background-color: #c9302c;
        }

        .download-btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            margin-top: 10px;
        }

        .download-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <h1 class="page-title">My Orders</h1>

        <div class="orders-container">
            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <i class="fas fa-shopping-bag" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                    <p>You haven't placed any orders yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-id">Order #<?php echo $order['order_id']; ?></span>
                            <span class="order-date">
                                <i class="far fa-calendar"></i>
                                <?php echo date('F j, Y', strtotime($order['order_date'])); ?>
                            </span>
                        </div>

                        <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>

                        <div class="order-details">
                            <div class="order-items">
                                <strong>Items:</strong> <?php echo htmlspecialchars($order['items']); ?>
                            </div>

                            <div class="payment-method">
                                <strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?>
                            </div>

                            <div class="order-address">
                                <strong>Shipping Address:</strong><br>
                                <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                            </div>

                            <div class="total-amount">
                                Total Amount: RM <?php echo number_format($order['total_amount'], 2); ?>
                            </div>

                            <div class="dates-info">
                                <?php if ($order['shipping_date']): ?>
                                    <div>Shipped: <?php echo date('F j, Y', strtotime($order['shipping_date'])); ?></div>
                                <?php endif; ?>

                                <?php if ($order['delivery_date']): ?>
                                    <div>Delivered: <?php echo date('F j, Y', strtotime($order['delivery_date'])); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Show cancel button only if order is pending -->
                            <?php if (strtolower($order['status']) === 'pending'): ?>
                                <form action="cancelOrder.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <button type="submit" class="cancel-btn">Cancel Order</button>
                                </form>
                            <?php endif; ?>

                            <?php if (strtolower($order['status']) === 'completed'): ?>
                                <a href="view_receipt.php?order_id=<?php echo $order['order_id']; ?>" class="download-btn" target="_blank">
                                    <i class="fas fa-print"></i> View Receipt
                                </a>
                            <?php endif; ?>

                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>