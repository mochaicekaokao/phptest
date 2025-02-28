<?php
session_start();
require_once 'db_connection.php';

// Check if shipping ID is provided
if (!isset($_GET['id'])) {
    header('Location: shipping_table.php');
    exit();
}

$shipping_id = $_GET['id'];

// Fetch shipping details with order info
$stmt = $pdo->prepare("
    SELECT s.*, o.order_date, l.userName, l.email 
    FROM shipping s
    LEFT JOIN orders o ON s.order_id = o.order_id
    LEFT JOIN login l ON o.user_id = l.userId
    WHERE s.shipping_id = ?
");
$stmt->execute([$shipping_id]);
$shipping = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['shipping_status'];
    $tracking_number = $_POST['tracking_number'];
    
    $update_stmt = $pdo->prepare("
        UPDATE shipping 
        SET shipping_status = ?, tracking_number = ?
        WHERE shipping_id = ?
    ");
    $update_stmt->execute([$new_status, $tracking_number, $shipping_id]);
    
    // Refresh shipping details
    $stmt->execute([$shipping_id]);
    $shipping = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Show success message
    $success_message = "Shipping details updated successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Shipping #<?php echo $shipping_id; ?></title>
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

        .status-form {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        select, input {
            padding: 8px;
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        button {
            padding: 8px 15px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background: #45a049;
        }

        .success-message {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
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
            <h1>Edit Shipping #<?php echo $shipping_id; ?></h1>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <form class="status-form" method="POST">
            <div class="form-group">
                <label for="shipping_status">Shipping Status:</label>
                <select name="shipping_status" id="shipping_status" required>
                    <option value="pending" <?php echo $shipping['shipping_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="shipped" <?php echo $shipping['shipping_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo $shipping['shipping_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                </select>
            </div>

            <div class="form-group">
                <label for="tracking_number">Tracking Number:</label>
                <input type="text" name="tracking_number" id="tracking_number" 
                       value="<?php echo htmlspecialchars($shipping['tracking_number'] ?? ''); ?>" 
                       placeholder="Enter tracking number" required>
            </div>

            <button type="submit">Update Shipping</button>
        </form>

        <div class="shipping-details">
            <h2>Current Details</h2>
            <p><strong>Order Date:</strong> <?php echo date('F j, Y H:i', strtotime($shipping['order_date'])); ?></p>
            <p><strong>Current Status:</strong> 
                <span class="status-badge status-<?php echo strtolower($shipping['shipping_status']); ?>">
                    <?php echo ucfirst($shipping['shipping_status']); ?>
                </span>
            </p>
            <p><strong>Shipping Address:</strong><br>
                <?php echo nl2br(htmlspecialchars($shipping['shipping_address'])); ?>
            </p>
            <p><strong>Shipping Method:</strong> <?php echo htmlspecialchars($shipping['shipping_method']); ?></p>
            <p><strong>Last Updated:</strong> <?php echo date('F j, Y H:i', strtotime($shipping['shipping_date'])); ?></p>
        </div>
    </div>
</body>
</html> 