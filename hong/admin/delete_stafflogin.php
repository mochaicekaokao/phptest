<?php
session_start();
if (!isset($_SESSION['staff_name']) || $_SESSION['staffPosition'] !== 'manager') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

require_once 'db_connection.php';

try {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    // Prevent deleting your own account
    if ($id == $_SESSION['staff_id']) {
        throw new Exception("Cannot delete your own account");
    }

    // Check if this is the last manager account
    $check_managers_sql = "SELECT COUNT(*) as count FROM stafflogin WHERE staffPosition = 'manager' AND staffID != ?";
    $stmt = $data->prepare($check_managers_sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] == 0) {
        throw new Exception("Cannot delete the last manager account");
    }

    // If all checks pass, proceed with deletion
    $stmt = $data->prepare("DELETE FROM stafflogin WHERE staffID = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Error deleting staff account");
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
