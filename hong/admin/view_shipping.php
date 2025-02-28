<?php
session_start();
require_once 'db_connection.php';

if (!isset($_GET['id'])) {
    header('Location: index.php?page=shipping');
    exit();
}

$shipping_id = $_GET['id'];

// Fetch shipping details with order and customer info
$stmt = $pdo->prepare("
    SELECT s.*, o.order_date, o.total_amount, 
           l.userName, l.fullName, l.email, l.phoneNumber
    FROM shipping s
    LEFT JOIN orders o ON s.order_id = o.order_id
    LEFT JOIN login l ON o.user_id = l.userId
    WHERE s.shipping_id = ?
");
$stmt->execute([$shipping_id]);
$shipping = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Shipping #<?php echo $shipping_id; ?></title>
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
        .status-shipped { background-color: #87CEEB; }
        .status-delivered { background-color: #90EE90; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="staff_dashboard.php?table=shipping" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Shipping
            </a>
            <h1>Shipping #<?php echo $shipping_id; ?></h1>
        </div>

        <div class="section">
            <h2 class="section-title">Shipping Status</h2>
            <span class="status-badge status-<?php echo strtolower($shipping['shipping_status']); ?>">
                <?php echo ucfirst($shipping['shipping_status']); ?>
            </span>
            <?php if ($shipping['tracking_number']): ?>
                <p><strong>Tracking Number:</strong> <?php echo htmlspecialchars($shipping['tracking_number']); ?></p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2 class="section-title">Customer Information</h2>
            <div class="customer-details">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($shipping['userName']); ?></p>
                <p><strong>Full Name:</strong> <?php echo htmlspecialchars($shipping['fullName']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($shipping['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($shipping['phoneNumber']); ?></p>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Shipping Information</h2>
            <div class="detail-row">
                <div class="detail-label">Address:</div>
                <div><?php echo nl2br(htmlspecialchars($shipping['shipping_address'])); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Method:</div>
                <div><?php echo htmlspecialchars($shipping['shipping_method']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Order Date:</div>
                <div><?php echo date('F j, Y H:i', strtotime($shipping['order_date'])); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Last Updated:</div>
                <div><?php echo date('F j, Y H:i', strtotime($shipping['shipping_date'])); ?></div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Order Details</h2>
            <div class="detail-row">
                <div class="detail-label">Order ID:</div>
                <div>#<?php echo htmlspecialchars($shipping['order_id']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Total Amount:</div>
                <div>RM <?php echo number_format($shipping['total_amount'], 2); ?></div>
            </div>
        </div>

        <a href="edit_shipping.php?id=<?php echo $shipping_id; ?>" class="btn btn-edit" style="display: inline-block; margin-top: 20px;">
            <i class="fas fa-edit"></i> Edit Shipping
        </a>
    </div>
</body>
</html> 