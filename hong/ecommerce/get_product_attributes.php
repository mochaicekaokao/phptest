<?php
require_once 'db_connection.php';

if (!isset($_GET['product_id'])) {
    echo json_encode([]);
    exit;
}

$product_id = $_GET['product_id'];

try {
    $stmt = $pdo->prepare("
        SELECT attribute_name, attribute_value
        FROM product_attributes
        WHERE product_id = ?
    ");
    $stmt->execute([$product_id]);
    $attributes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($attributes);
} catch (Exception $e) {
    echo json_encode([]);
} 