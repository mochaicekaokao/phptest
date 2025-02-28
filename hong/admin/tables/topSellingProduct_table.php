<?php
if (!isset($data)) {
    die('Database connection not available');
}

// Get top 5 selling products with their total quantity sold and revenue
$sql = "SELECT 
            p.product_id,
            p.name,
            p.image_url,
            p.price,
            c.category_name,
            COUNT(DISTINCT o.order_id) as total_orders,
            SUM(oi.quantity) as total_quantity_sold,
            SUM(oi.quantity * oi.price_at_purchase) as total_revenue
        FROM products p
        LEFT JOIN order_items oi ON p.product_id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.order_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE o.status = 'completed'
        GROUP BY p.product_id
        ORDER BY total_quantity_sold DESC
        LIMIT 5";

$result = $data->query($sql);
?>

<div class="top-selling-container">
    <div class="stats-summary">
        <?php
        $total_sales = 0;
        $total_revenue = 0;
        if ($result && $result->num_rows > 0) {
            $temp_result = $result;
            while ($row = $temp_result->fetch_assoc()) {
                $total_sales += $row['total_quantity_sold'];
                $total_revenue += $row['total_revenue'];
            }
            mysqli_data_seek($result, 0); // Reset result pointer
        }
        ?>
        <div class="stat-card">
            <i class="fas fa-chart-line"></i>
            <div class="stat-info">
                <h3>Total Sales</h3>
                <p><?php echo number_format($total_sales); ?> units</p>
            </div>
        </div>
        <div class="stat-card">
            <i class="fas fa-money-bill-wave"></i>
            <div class="stat-info">
                <h3>Total Revenue</h3>
                <p>RM <?php echo number_format($total_revenue, 2); ?></p>
            </div>
        </div>
    </div>

    <table class="top-selling-table">
        <thead>
            <tr>
                <th>Rank</th>
                <th>Product</th>
                <th>Category</th>
                <th>Total Orders</th>
                <th>Quantity Sold</th>
                <th>Unit Price</th>
                <th>Total Revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php
                $rank = 1;
                while ($row = $result->fetch_assoc()):
                ?>
                    <tr class="product-row rank-<?php echo $rank; ?>">
                        <td class="rank">
                            <div class="rank-badge">
                                <?php
                                if ($rank <= 3) {
                                    echo '<i class="fas fa-trophy rank-' . $rank . '"></i>';
                                } else {
                                    echo $rank;
                                }
                                ?>
                            </div>
                        </td>
                        <td class="product-info">
                            <?php if (!empty($row['image_url'])): ?>
                                <img src="../<?php echo htmlspecialchars($row['image_url']); ?>"
                                    alt="<?php echo htmlspecialchars($row['name']); ?>"
                                    class="product-thumbnail">
                            <?php endif; ?>
                            <span class="product-name"><?php echo htmlspecialchars($row['name']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo number_format($row['total_orders']); ?></td>
                        <td><?php echo number_format($row['total_quantity_sold']); ?></td>
                        <td>RM <?php echo number_format($row['price'], 2); ?></td>
                        <td>RM <?php echo number_format($row['total_revenue'], 2); ?></td>
                    </tr>
                <?php
                    $rank++;
                endwhile;
                ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center;">No sales data available</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
    .top-selling-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .stats-summary {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        flex: 1;
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .stat-card i {
        font-size: 2em;
        color: #333;
    }

    .stat-info h3 {
        margin: 0;
        font-size: 1em;
        color: #666;
    }

    .stat-info p {
        margin: 5px 0 0;
        font-size: 1.5em;
        font-weight: bold;
        color: #333;
    }

    .top-selling-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .top-selling-table th,
    .top-selling-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .top-selling-table th {
        background-color: black;
        font-weight: bold;
    }

    .product-row {
        transition: background-color 0.2s;
    }

    .product-row:hover {
        background-color: #f8f9fa;
    }

    .rank {
        text-align: center;
        width: 50px;
    }

    .rank-badge {
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        font-weight: bold;
        font-size: 1.2em;
    }

    .rank-badge i {
        font-size: 1.5em;
    }

    .rank-1 i {
        color: #ffd700;
        /* Gold */
    }

    .rank-2 i {
        color: #c0c0c0;
        /* Silver */
    }

    .rank-3 i {
        color: #cd7f32;
        /* Bronze */
    }

    .product-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .product-thumbnail {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
    }

    .product-name {
        font-weight: bold;
    }

    /* Highlight top 3 rows */
    .rank-1 {
        background-color: rgba(255, 215, 0, 0.1);
    }

    .rank-2 {
        background-color: rgba(192, 192, 192, 0.1);
    }

    .rank-3 {
        background-color: rgba(205, 127, 50, 0.1);
    }
</style>