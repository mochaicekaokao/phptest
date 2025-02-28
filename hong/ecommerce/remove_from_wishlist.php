<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$wishlist_id = $input['wishlist_id'] ?? null;

if (!$wishlist_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid wishlist item']);
    exit;
}

try {
    // Verify the wishlist item belongs to the user
    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE wishlist_id = ? AND user_id = ?");
    $stmt->execute([$wishlist_id, $_SESSION['user_id']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error removing item']);
} 