<?php
if (!isset($data)) {
    die('Database connection not available');
}

$sql = "SELECT p.*, o.user_id, l.userName, l.fullName 
        FROM payments p 
        LEFT JOIN orders o ON p.order_id = o.order_id 
        LEFT JOIN login l ON o.user_id = l.userId 
        ORDER BY p.payment_date DESC";
$result = $data->query($sql);
?>

<table>
    <thead>
        <tr>
            <th>Payment ID</th>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['payment_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['order_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['fullName']); ?></td>
                    <td>RM <?php echo number_format($row['amount'], 2); ?></td>
                    <td><?php echo ucfirst(str_replace('_', ' ', $row['payment_method'])); ?></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($row['payment_date'])); ?></td>
                    <td class="actions">
                        <button class="btn btn-view" onclick="location.href='view_payment.php?id=<?php echo $row['payment_id']; ?>'">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align: center;">No payments found</td>
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

    .status-failed {
        background-color: #ffcccb;
    }
</style>