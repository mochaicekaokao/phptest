<?php
session_start();
require_once 'db_connection.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Ensure the order_id is provided via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $user_id = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        // Fetch the order items to restore stock
        $stmt = $pdo->prepare("
            SELECT oi.product_id, oi.quantity 
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.order_id
            WHERE oi.order_id = ? AND o.user_id = ? AND o.status = 'Pending'
        ");
        $stmt->execute([$order_id, $user_id]);
        $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($order_items)) {
            throw new Exception("Order not found or cannot be canceled.");
        }

        // Restore stock quantity for each item
        $update_stock_stmt = $pdo->prepare("
            UPDATE products 
            SET stock_quantity = stock_quantity + ? 
            WHERE product_id = ?
        ");

        foreach ($order_items as $item) {
            $update_stock_stmt->execute([$item['quantity'], $item['product_id']]);
        }

        // Update order status to "Cancelled"
        $update_order_stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'Cancelled' 
            WHERE order_id = ? AND user_id = ? AND status = 'Pending'
        ");
        $update_order_stmt->execute([$order_id, $user_id]);

        $pdo->commit();

        // Redirect with a success message
        header('Location: order_status.php?msg=Order cancelled successfully and stock restored.');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Order cancellation error: " . $e->getMessage());
        header('Location: order_status.php?msg=' . urlencode("Error: " . $e->getMessage()));
        exit();
    }
} else {
    header('Location: order_status.php?msg=Invalid request');
    exit();
}
