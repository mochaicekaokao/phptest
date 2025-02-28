<?php
session_start();
require_once 'db_connection.php';

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header('Location: orders_table.php');
    exit();
}

$order_id = $_GET['id'];

// Fetch order details with correct column names
$stmt = $pdo->prepare("
    SELECT o.*, l.userName, l.email
    FROM orders o
    LEFT JOIN login l ON o.user_id = l.userId
    WHERE o.order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_status'])) {
    $new_status = $_POST['new_status'];

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Update order status
        $update_stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $update_stmt->execute([$new_status, $order_id]);

        // If status is changed to completed, create shipping record
        if ($new_status === 'completed') {
            // Check if shipping record already exists
            $check_stmt = $pdo->prepare("SELECT shipping_id FROM shipping WHERE order_id = ?");
            $check_stmt->execute([$order_id]);

            if (!$check_stmt->fetch()) {
                // Create new shipping record
                $shipping_stmt = $pdo->prepare("
                    INSERT INTO shipping (
                        order_id, 
                        shipping_address, 
                        shipping_method,
                        shipping_status,
                        shipping_date
                    ) VALUES (
                        ?, 
                        ?, 
                        ?,
                        'pending',
                        CURRENT_TIMESTAMP
                    )
                ");

                $shipping_stmt->execute([
                    $order_id,
                    $order['shipping_address'],
                    $_POST['shipping_method']
                ]);
            }
        }

        // Commit transaction
        $pdo->commit();

        // Refresh order details
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        // Show success message
        $success_message = "Order status updated successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $error_message = "Error updating order status: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Order #<?php echo $order_id; ?></title>
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

        .status-form {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        select {
            padding: 8px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
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

        .order-details {
            margin-top: 20px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
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

        .status-pending {
            background-color: #ffd700;
        }

        .status-processing {
            background-color: #87CEEB;
        }

        .status-completed {
            background-color: #90EE90;
        }

        .status-cancelled {
            background-color: #ffcccb;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <a href="staff_dashboard.php?table=orders" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
            <h1>Edit Order #<?php echo $order_id; ?></h1>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="success-message" style="background-color: #dff0d8; color: #3c763d; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error-message" style="background-color: #f2dede; color: #a94442; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form class="status-form" method="POST">
            <label for="new_status">Update Order Status:</label>
            <select name="new_status" id="new_status" onchange="toggleDeliveryMethod(this.value)">
                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>

            <div id="deliveryMethodSection" style="display: none; margin-top: 10px;">
                <label for="shipping_method">Delivery Method:</label>
                <select name="shipping_method" id="shipping_method" required>
                    <option value="">Select Delivery Method</option>
                    <option value="Standard Delivery">Standard Delivery (3-5 days)</option>
                    <option value="Express Delivery">Express Delivery (1-2 days)</option>
                    <option value="Economy Shipping">Economy Shipping (5-7 days)</option>
                </select>
            </div>

            <button type="submit" style="margin-top: 10px;">Update Status</button>
        </form>

        <script>
            function toggleDeliveryMethod(status) {
                const deliverySection = document.getElementById('deliveryMethodSection');
                const shippingMethodSelect = document.getElementById('shipping_method');

                if (status === 'completed') {
                    deliverySection.style.display = 'block';
                    shippingMethodSelect.required = true;
                } else {
                    deliverySection.style.display = 'none';
                    shippingMethodSelect.required = false;
                }
            }

            // Initialize on page load
            document.addEventListener('DOMContentLoaded', function() {
                toggleDeliveryMethod(document.getElementById('new_status').value);
            });
        </script>

        <div class="order-details">
            <div class="detail-row">
                <div class="detail-label">Current Status:</div>
                <div>
                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Customer Name:</div>
                <div><?php echo htmlspecialchars($order['userName']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Email:</div>
                <div><?php echo htmlspecialchars($order['email']); ?></div>
            </div>
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
            <div class="detail-row">
                <div class="detail-label">Shipping Address:</div>
                <div><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></div>
            </div>
        </div>
    </div>
</body>

</html>