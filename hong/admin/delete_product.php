<?php
session_start();
if (!isset($_SESSION['staff_name']) || $_SESSION['staffPosition'] !== 'manager') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

require_once 'db_connection.php';

try {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    // Start transaction
    $data->begin_transaction();

    try {
        // Get current image URL before deletion
        $stmt = $data->prepare("SELECT image_url FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        // Check if product exists in orders
        $check_sql = "SELECT COUNT(*) as count FROM order_items WHERE product_id = ?";
        $stmt = $data->prepare($check_sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            throw new Exception("Cannot delete product: It has existing orders");
        }

        // Delete the product
        $stmt = $data->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            // Delete the physical image file if it exists
            if (!empty($product['image_url'])) {
                $image_path = "../" . $product['image_url'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }

            $data->commit();
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Error deleting product");
        }
    } catch (Exception $e) {
        $data->rollback();
        throw $e;
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
