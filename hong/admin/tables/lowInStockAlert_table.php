<?php
if (!isset($data)) {
    die('Database connection not available');
}

// Get products with stock quantity less than 20
$sql = "SELECT p.*, c.category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        WHERE p.stock_quantity < 20 
        ORDER BY p.stock_quantity ASC";
$result = $data->query($sql);

// Count total low stock items
$low_stock_count = $result->num_rows;
?>

<div class="alert-summary">
    <div class="alert-count <?php echo $low_stock_count > 0 ? 'warning' : 'good'; ?>">
        <i class="fas <?php echo $low_stock_count > 0 ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>"></i>
        <div class="alert-text">
            <h3><?php echo $low_stock_count; ?> Products Low in Stock</h3>
            <p><?php echo $low_stock_count > 0 ? 'Attention needed' : 'Stock levels are healthy'; ?></p>
        </div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Image</th>
            <th>Name</th>
            <th>Category</th>
            <th>Current Stock</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['product_id']); ?></td>
                    <td>
                        <?php if (!empty($row['image_url'])): ?>
                            <img src="../<?php echo htmlspecialchars($row['image_url']); ?>"
                                alt="<?php echo htmlspecialchars($row['name']); ?>"
                                style="max-width: 50px; max-height: 50px;"
                                class="product-thumbnail">
                        <?php else: ?>
                            <span class="no-image">No Image</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                    <td>
                        <span class="stock-number <?php echo $row['stock_quantity'] <= 5 ? 'critical' : 'warning'; ?>">
                            <?php echo htmlspecialchars($row['stock_quantity']); ?>
                        </span>
                    </td>
                    <td>
                        <?php
                        if ($row['stock_quantity'] <= 5) {
                            echo '<span class="status-badge critical">Critical</span>';
                        } else if ($row['stock_quantity'] <= 10) {
                            echo '<span class="status-badge warning">Low</span>';
                        } else {
                            echo '<span class="status-badge attention">Attention</span>';
                        }
                        ?>
                    </td>
                    <td class="actions">
                        <button class="btn btn-edit" onclick="location.href='edit_product.php?id=<?php echo $row['product_id']; ?>'">
                            <i class="fas fa-edit"></i> Update Stock
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align: center;">All products are well stocked</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<style>
    .alert-summary {
        margin-bottom: 20px;
    }

    .alert-count {
        display: flex;
        align-items: center;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .alert-count.warning {
        background-color: #fff3cd;
        border: 1px solid #ffeeba;
    }

    .alert-count.good {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
    }

    .alert-count i {
        font-size: 2em;
        margin-right: 15px;
    }

    .alert-count.warning i {
        color: #ff9800;
    }

    .alert-count.good i {
        color: #28a745;
    }

    .alert-text h3 {
        margin: 0;
        color: #333;
        font-size: 1.2em;
    }

    .alert-text p {
        margin: 5px 0 0;
        color: #666;
    }

    .stock-number {
        font-weight: bold;
        padding: 5px 10px;
        border-radius: 15px;
    }

    .stock-number.critical {
        color: #dc3545;
        background-color: #f8d7da;
    }

    .stock-number.warning {
        color: #856404;
        background-color: #fff3cd;
    }

    .status-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.9em;
        font-weight: bold;
    }

    .status-badge.critical {
        background-color: #f8d7da;
        color: #dc3545;
    }

    .status-badge.warning {
        background-color: #fff3cd;
        color: #856404;
    }

    .status-badge.attention {
        background-color: #fff3cd;
        color: #856404;
    }

    .product-thumbnail {
        border-radius: 4px;
        object-fit: cover;
    }

    .btn-edit {
        background-color: #007bff;
        transition: background-color 0.3s;
    }

    .btn-edit:hover {
        background-color: #0056b3;
    }

    .no-image {
        color: #999;
        font-style: italic;
    }
</style>