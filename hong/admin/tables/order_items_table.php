<?php
if (!isset($data)) {
    die('Database connection not available');
}

$sql = "SELECT oi.*, p.name as product_name, o.order_date 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.product_id 
        LEFT JOIN orders o ON oi.order_id = o.order_id 
        ORDER BY o.order_date DESC";
$result = $data->query($sql);
?>

<table>
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Price</th>
            <th>Total</th>
            <th>Order Date</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['order_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                    <td>RM <?php echo number_format($row['price_at_purchase'], 2); ?></td>
                    <td>RM <?php echo number_format($row['price_at_purchase'] * $row['quantity'], 2); ?></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($row['order_date'])); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align: center;">No order items found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table> 