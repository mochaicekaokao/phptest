<?php
session_start();

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['product_id']) || !isset($input['name']) || !isset($input['price']) || !isset($input['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if product already exists in cart
$found = false;
foreach ($_SESSION['cart'] as &$item) {
    if ($item['product_id'] == $input['product_id']) {
        $item['quantity'] += $input['quantity']; // Add the selected quantity
        $found = true;
        break;
    }
}

// If product is new, add to cart
if (!$found) {
    $_SESSION['cart'][] = [
        'product_id' => $input['product_id'],
        'name' => $input['name'],
        'price' => $input['price'],
        'quantity' => $input['quantity'] // Use the selected quantity
    ];
}

// Return updated cart count
echo json_encode(['success' => true, 'cart_count' => count($_SESSION['cart'])]);
?>
