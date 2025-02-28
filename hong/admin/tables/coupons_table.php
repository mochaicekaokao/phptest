<?php
if (!isset($data)) {
    die('Database connection not available');
}

$sql = "SELECT * FROM coupons ORDER BY valid_from DESC";
$result = $data->query($sql);
?>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Code</th>
            <th>Discount</th>
            <th>Valid From</th>
            <th>Valid To</th>
            <th>Usage</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <?php 
                $isExpired = strtotime($row['valid_to']) < time();
                $isMaxedOut = $row['usage_count'] >= $row['max_usage_limit'];
                ?>
                <tr class="<?php echo ($isExpired || $isMaxedOut) ? 'expired' : ''; ?>">
                    <td><?php echo htmlspecialchars($row['coupon_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['code']); ?></td>
                    <td>RM <?php echo number_format($row['discount_amount'], 2); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($row['valid_from'])); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($row['valid_to'])); ?></td>
                    <td><?php echo $row['usage_count'] . '/' . $row['max_usage_limit']; ?></td>
                    <td class="actions">
                        <button class="btn btn-edit" onclick="location.href='edit_coupon.php?id=<?php echo $row['coupon_id']; ?>'">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-delete" onclick="deleteCoupon(<?php echo $row['coupon_id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align: center;">No coupons found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<style>
.expired {
    opacity: 0.6;
    background-color: #f5f5f5;
}
</style>

<script>
function deleteCoupon(id) {
    if (confirm('Are you sure you want to delete this coupon?')) {
        fetch('delete_coupon.php', {
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
                alert('Error deleting coupon');
            }
        });
    }
}
</script> 