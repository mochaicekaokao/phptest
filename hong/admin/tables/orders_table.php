<?php
if (!isset($data)) {
    die('Database connection not available');
}

$sql = "SELECT o.*, l.userName, l.fullName 
        FROM orders o 
        LEFT JOIN login l ON o.user_id = l.userId 
        ORDER BY o.order_date DESC";
$result = $data->query($sql);
?>

<table>
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Username</th>
            <th>Customer Name</th>
            <th>Total Amount</th>
            <th>Status</th>
            <th>Order Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['order_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['userName']); ?></td>
                    <td><?php echo htmlspecialchars($row['fullName']); ?></td>
                    <td>RM <?php echo number_format($row['total_amount'], 2); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('Y-m-d H:i', strtotime($row['order_date'])); ?></td>
                    <td class="actions">
                        <button class="btn btn-view" onclick="location.href='view_order.php?id=<?php echo $row['order_id']; ?>'">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-edit" onclick="location.href='edit_order.php?id=<?php echo $row['order_id']; ?>'">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align: center;">No orders found</td>
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

    .status-completed {
        background-color: #90EE90;
    }

    .status-cancelled {
        background-color: #ffcccb;
    }
</style>