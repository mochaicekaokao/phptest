<?php
session_start();
require_once 'db_connection.php';

// Check if payment ID is provided
if (!isset($_GET['id'])) {
    header('Location: staff_dashboard.php?table=payments');
    exit();
}

$payment_id = $_GET['id'];

// Fetch payment details with customer info and order details
$stmt = $pdo->prepare("
    SELECT p.*, o.*, l.userName, l.fullName, l.email, l.phoneNumber,
           GROUP_CONCAT(CONCAT(oi.quantity, 'x ', pr.name) SEPARATOR '\n') as order_items
    FROM payments p
    LEFT JOIN orders o ON p.order_id = o.order_id
    LEFT JOIN login l ON o.user_id = l.userId
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN products pr ON oi.product_id = pr.product_id
    WHERE p.payment_id = ?
    GROUP BY p.payment_id
");
$stmt->execute([$payment_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    header('Location: staff_dashboard.php?table=payments');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Payment #<?php echo $payment_id; ?></title>
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
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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
            <a href="staff_dashboard.php?table=payments" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Payments
            </a>
            <h1>Payment #<?php echo $payment_id; ?></h1>
        </div>

        <div class="section">
            <h2 class="section-title">Payment Information</h2>
            <div class="detail-row">
                <div class="detail-label">Payment Date:</div>
                <div><?php echo date('F j, Y H:i', strtotime($payment['payment_date'])); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Amount:</div>
                <div>RM <?php echo number_format($payment['amount'], 2); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Method:</div>
                <div><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Customer Information</h2>
            <div class="detail-row">
                <div class="detail-label">Name:</div>
                <div><?php echo htmlspecialchars($payment['fullName']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Email:</div>
                <div><?php echo htmlspecialchars($payment['email']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Phone:</div>
                <div><?php echo htmlspecialchars($payment['phoneNumber']); ?></div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Order Information</h2>
            <div class="detail-row">
                <div class="detail-label">Order ID:</div>
                <div>#<?php echo htmlspecialchars($payment['order_id']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Order Date:</div>
                <div><?php echo date('F j, Y H:i', strtotime($payment['order_date'])); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Order Status:</div>
                <div><?php echo ucfirst($payment['status']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Items:</div>
                <div class="order-items"><?php echo nl2br(htmlspecialchars($payment['order_items'])); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Shipping Address:</div>
                <div><?php echo nl2br(htmlspecialchars($payment['shipping_address'])); ?></div>
            </div>
        </div>
    </div>
</body>

</html>