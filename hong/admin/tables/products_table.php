<?php
if (!isset($data)) {
    die('Database connection not available');
}

$sql = "SELECT p.*, c.category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        ORDER BY p.product_id DESC";
$result = $data->query($sql);

// Get all product attributes
$attributes_sql = "SELECT * FROM product_attributes ORDER BY product_id, attribute_name";
$attributes_result = $data->query($attributes_sql);

// Create a map of product_id to attributes
$product_attributes = array();
while ($attr = $attributes_result->fetch_assoc()) {
    if (!isset($product_attributes[$attr['product_id']])) {
        $product_attributes[$attr['product_id']] = array();
    }
    $product_attributes[$attr['product_id']][] = $attr;
}
?>

<table>
    <thead>
        <tr>
            <th></th>
            <th>ID</th>
            <th>Image</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="product-row">
                    <td>
                        <button class="btn btn-expand" onclick="toggleAttributes(<?php echo $row['product_id']; ?>)">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </td>
                    <td><?php echo htmlspecialchars($row['product_id']); ?></td>
                    <td>
                        <?php if (!empty($row['image_url'])): ?>
                            <img src="../<?php echo htmlspecialchars($row['image_url']); ?>"
                                alt="<?php echo htmlspecialchars($row['name']); ?>"
                                style="max-width: 50px; max-height: 50px;"
                                onclick="showFullImage(this.src)"
                                class="product-thumbnail">
                        <?php else: ?>
                            <span class="no-image">No Image</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                    <td>RM <?php echo number_format($row['price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['stock_quantity']); ?></td>
                    <td class="actions">
                        <button class="btn btn-edit" onclick="location.href='edit_product.php?id=<?php echo $row['product_id']; ?>'">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-delete" onclick="deleteProduct(<?php echo $row['product_id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <tr id="attributes-<?php echo $row['product_id']; ?>" class="attributes-row" style="display: none;">
                    <td colspan="8">
                        <div class="attributes-container">
                            <h4>Product Attributes</h4>
                            <?php if (isset($product_attributes[$row['product_id']])): ?>
                                <table class="attributes-table">
                                    <thead>
                                        <tr>
                                            <th>Attribute Name</th>
                                            <th>Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($product_attributes[$row['product_id']] as $attribute): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($attribute['attribute_name']); ?></td>
                                                <td><?php echo htmlspecialchars($attribute['attribute_value']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>No attributes found for this product.</p>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" style="text-align: center;">No products found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<style>
    .product-thumbnail {
        cursor: pointer;
        transition: transform 0.2s;
    }

    .product-thumbnail:hover {
        transform: scale(1.1);
    }

    .no-image {
        color: #999;
        font-style: italic;
    }

    .btn-expand {
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        color: #666;
    }

    .btn-expand:hover {
        color: #333;
    }

    .attributes-container {
        padding: 15px;
        background: #f9f9f9;
        border-radius: 4px;
    }

    .attributes-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .attributes-table th,
    .attributes-table td {
        padding: 8px;
        border: 1px solid #ddd;
        text-align: left;
    }

    .attributes-table th {
        background: #f5f5f5;
    }

    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.9);
        z-index: 1000;
    }

    .modal-content {
        margin: auto;
        display: block;
        max-width: 90%;
        max-height: 90%;
        position: relative;
        top: 50%;
        transform: translateY(-50%);
    }

    .close {
        position: absolute;
        right: 25px;
        top: 25px;
        color: #f1f1f1;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
    }
</style>

<!-- Modal for full-size image -->
<div id="imageModal" class="modal" onclick="this.style.display='none'">
    <span class="close">&times;</span>
    <img class="modal-content" id="fullImage">
</div>

<script>
    function toggleAttributes(productId) {
        const row = document.getElementById('attributes-' + productId);
        const button = event.target.closest('.btn-expand');
        const icon = button.querySelector('i');

        if (row.style.display === 'none') {
            row.style.display = 'table-row';
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        } else {
            row.style.display = 'none';
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        }
    }

    function showFullImage(src) {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('fullImage');
        modal.style.display = "block";
        modalImg.src = src;
    }

    function deleteProduct(id) {
        if (confirm('Are you sure you want to delete this product?')) {
            fetch('delete_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error deleting product');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting product: ' + error);
                });
        }
    }
</script>