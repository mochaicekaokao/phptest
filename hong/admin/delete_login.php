<?php
session_start();
if (!isset($_SESSION['staff_name']) || $_SESSION['staffPosition'] !== 'manager') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

require_once 'db_connection.php';

try {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    // Check only for orders table since reviews and wishlist don't exist
    $check_sql = "SELECT COUNT(*) as count FROM orders WHERE user_id = ?";
    $stmt = $data->prepare($check_sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        throw new Exception("Cannot delete user: This user has existing orders");
    }

    // If no related records exist, proceed with deletion
    $stmt = $data->prepare("DELETE FROM login WHERE userId = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Error deleting user");
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
