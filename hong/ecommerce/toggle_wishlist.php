<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

// Create wishlist table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS wishlist (
        wishlist_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_wishlist (user_id, product_id),
        FOREIGN KEY (user_id) REFERENCES login(userId),
        FOREIGN KEY (product_id) REFERENCES products(product_id)
    )");
} catch (Exception $e) {
    error_log("Error creating wishlist table: " . $e->getMessage());
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$product_id = $input['product_id'] ?? null;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

try {
    // Check if item is already in wishlist
    $stmt = $pdo->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $product_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Remove from wishlist
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE wishlist_id = ?");
        $stmt->execute([$existing['wishlist_id']]);
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        // Add to wishlist
        $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        echo json_encode(['success' => true, 'action' => 'added']);
    }
} catch (Exception $e) {
    error_log("Error updating wishlist: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error updating wishlist: ' . $e->getMessage()]);
}
