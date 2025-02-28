<?php
session_start();
require_once 'db_connection.php';

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header('Location: orders_table.php');
    exit();
}

$order_id = $_GET['id'];

// Fetch order details with customer info
$stmt = $pdo->prepare("
    SELECT o.*, l.userName, l.fullName, l.email, l.phoneNumber,
           GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name, ' (RM', p.price, ')') SEPARATOR '\n') as order_items
    FROM orders o
    LEFT JOIN login l ON o.user_id = l.userId
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE o.order_id = ?
    GROUP BY o.order_id
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order #<?php echo $order_id; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .back-btn {
            text-decoration: none;
            color: #333;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .order-details {
            margin-top: 20px;
        }

        .section {
            margin-bottom: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .section-title {
            font-size: 1.2em;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #ddd;
        }

        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }

        .detail-label {
            width: 150px;
            font-weight: bold;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            display: inline-block;
        }

        .status-pending { background-color: #ffd700; }
        .status-processing { background-color: #87CEEB; }
        .status-completed { background-color: #90EE90; }
        .status-cancelled { background-color: #ffcccb; }

        .order-items {
            white-space: pre-line;
            padding: 10px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="staff_dashboard.php?table=orders" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
            <h1>Order #<?php echo $order_id; ?></h1>
        </div>

        <div class="order-details">
            <div class="section">
                <h2 class="section-title">Order Status</h2>
                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>

            <div class="section">
                <h2 class="section-title">Customer Information</h2>
                <div class="customer-details">
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($order['userName']); ?></p>
                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($order['fullName']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phoneNumber']); ?></p>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">Order Information</h2>
                <div class="detail-row">
                    <div class="detail-label">Order Date:</div>
                    <div><?php echo date('F j, Y H:i', strtotime($order['order_date'])); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Total Amount:</div>
                    <div>RM <?php echo number_format($order['total_amount'], 2); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Payment Method:</div>
                    <div><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></div>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">Shipping Information</h2>
                <div class="detail-row">
                    <div class="detail-label">Address:</div>
                    <div><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></div>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">Order Items</h2>
                <div class="order-items">
                    <?php echo nl2br(htmlspecialchars($order['order_items'])); ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
