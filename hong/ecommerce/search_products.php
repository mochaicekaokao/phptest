<?php
require_once 'db_connection.php';

if (isset($_GET['query'])) {
    $search = '%' . $_GET['query'] . '%';

    try {
        $stmt = $pdo->prepare("
            SELECT p.*, c.category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.name LIKE ? 
            OR p.description LIKE ? 
            OR c.category_name LIKE ?
            ORDER BY p.name
        ");

        $stmt->execute([$search, $search, $search]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response = [];
        foreach ($products as $product) {
            $image_path = '../' . $product['image_url'];
            if (!file_exists($image_path) || empty($product['image_url'])) {
                $image_path = '../uploads/products/image-icon-600nw-211642900.webp';
            }

            $response[] = [
                'id' => $product['product_id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $image_path,
                'description' => $product['description'],
                'stock' => $product['stock_quantity'],
                'category' => $product['category_name']
            ];
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'products' => $response]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
