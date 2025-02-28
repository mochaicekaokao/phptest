<?php
if (!isset($data)) {
    die('Database connection not available');
}

$sql = "SELECT s.*, o.order_date, o.user_id, l.userName, l.fullName 
        FROM shipping s 
        LEFT JOIN orders o ON s.order_id = o.order_id 
        LEFT JOIN login l ON o.user_id = l.userId
        ORDER BY s.shipping_date DESC";
$result = $data->query($sql);
?>

<table>
    <thead>
        <tr>
            <th>Shipping ID</th>
            <th>Order ID</th>
            <th>Customer Name</th>
            <th>Status</th>
            <th>Tracking Number</th>
            <th>Shipping Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['shipping_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['order_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['fullName']); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo strtolower($row['shipping_status']); ?>">
                            <?php echo ucfirst($row['shipping_status']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($row['tracking_number']); ?></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($row['shipping_date'])); ?></td>
                    <td class="actions">
                        <button class="btn btn-view" onclick="location.href='view_shipping.php?id=<?php echo $row['shipping_id']; ?>'">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-edit" onclick="location.href='edit_shipping.php?id=<?php echo $row['shipping_id']; ?>'">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align: center;">No shipping records found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<style>
    .status-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.9em;
    }

    .status-pending {
        background-color: #ffd700;
    }

    .status-shipped {
        background-color: #87CEEB;
    }

    .status-delivered {
        background-color: #90EE90;
    }
</style>