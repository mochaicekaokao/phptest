<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    die('Order ID not provided');
}

// Fetch order details
$stmt = $pdo->prepare("
    SELECT 
        o.*,
        u.fullName as customer_name,
        u.email
    FROM orders o
    JOIN login u ON o.user_id = u.userId
    WHERE o.order_id = ? AND o.user_id = ? AND o.status = 'completed'
");

$stmt->execute([$_GET['order_id'], $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('Order not found or not completed');
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT 
        p.name,
        p.price,
        oi.quantity
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");

$stmt->execute([$_GET['order_id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>E-Receipt - Order #<?php echo $order['order_id']; ?></title>
    <style>
        @media print {
            .no-print {
                display: none;
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f9f9f9;
        }

        .receipt {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            border: 1px solid #ddd;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            background-color: white;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .logo {
            max-width: 200px;
            margin-bottom: 15px;
        }

        .store-name {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .receipt-title {
            font-size: 22px;
            margin: 30px 0;
            text-align: center;
            color: #333;
            font-weight: bold;
            padding: 10px;
            border-top: 1px dashed #ddd;
            border-bottom: 1px dashed #ddd;
        }

        .info-section {
            margin-bottom: 30px;
        }

        .info-section h3 {
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 8px;
            margin-bottom: 15px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #f5f5f5;
            font-weight: bold;
            color: #333;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .total {
            text-align: right;
            font-weight: bold;
            font-size: 20px;
            margin-top: 20px;
            color: #333;
            padding: 10px 0;
        }

        .print-btn {
            background-color: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 20px;
            transition: background-color 0.3s;
        }

        .print-btn:hover {
            background-color: #45a049;
        }

        .back-btn {
            background-color: #6c757d;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
            transition: background-color 0.3s;
        }

        .back-btn:hover {
            background-color: #5a6268;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        
        .receipt-id {
            font-weight: bold;
            color: #4CAF50;
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" class="print-btn">Print Receipt</button>
        <button onclick="window.location.href='order_status.php'" class="back-btn">Back</button>
    </div>

    <div class="receipt">
        <div class="header">
            <img src="../uploads/logo/logo.png" alt="GIGABYTE Logo" class="logo">
            <div class="store-name">GIGABYTE</div>
            <div>123 Store Street, City, Country</div>
            <div>Phone: +123456789</div>
            <div>Email: store@example.com</div>
        </div>

        <div class="receipt-title">
            E-RECEIPT <span class="receipt-id">#<?php echo htmlspecialchars($order['order_id']); ?></span>
        </div>

        <div class="info-section">
            <h3>Receipt Details</h3>
            <div><strong>Receipt No:</strong> <?php echo htmlspecialchars($order['order_id']); ?></div>
            <div><strong>Date:</strong> <?php echo date('F j, Y', strtotime($order['order_date'])); ?></div>
            <div><strong>Time:</strong> <?php echo date('h:i A', strtotime($order['order_date'])); ?></div>
        </div>

        <div class="info-section">
            <h3>Customer Details</h3>
            <div><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></div>
            <div><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></div>
            <div><strong>Shipping Address:</strong><br><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></div>
        </div>

        <div class="info-section">
            <h3>Order Items</h3>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>RM <?php echo number_format($item['price'], 2); ?></td>
                            <td>RM <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="info-section">
            <h3>Payment Information</h3>
            <div><strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></div>
            <div class="total">Total Amount: RM <?php echo number_format($order['total_amount'], 2); ?></div>
        </div>

        <div class="footer">
            <p>Thank you for shopping with us!</p>
            <p>For any inquiries, please contact our customer service at support@gigabyte.com</p>
            <p>&copy; <?php echo date('Y'); ?> GIGABYTE. All rights reserved.</p>
        </div>
    </div>
</body>
</html> 