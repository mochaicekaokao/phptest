<?php
if (!isset($data)) {
    die('Database connection not available');
}

$sql = "SELECT r.*, l.userName, l.fullName, p.name as product_name, p.image_url 
        FROM rating r 
        LEFT JOIN login l ON r.user_id = l.userId 
        LEFT JOIN products p ON r.product_id = p.product_id 
        ORDER BY r.created_at DESC";
$result = $data->query($sql);
?>

<table>
    <thead>
        <tr>
            <th>Username</th>
            <th>Full Name</th>
            <th>Product</th>
            <th>Rating</th>
            <th>Comment</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['userName']); ?></td>
                    <td><?php echo htmlspecialchars($row['fullName']); ?></td>
                    <td class="product-cell">
                        <img src="../<?php echo htmlspecialchars($row['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($row['product_name']); ?>"
                             class="product-thumbnail"
                             onerror="this.src='../uploads/products/image-icon-600nw-211642900.webp'">
                        <span><?php echo htmlspecialchars($row['product_name']); ?></span>
                    </td>
                    <td>
                        <?php 
                        for($i = 1; $i <= 5; $i++) {
                            echo $i <= $row['rating'] ? '★' : '☆';
                        }
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['comment'] ?: 'No comment'); ?></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align: center;">No reviews found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<style>
.product-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.product-thumbnail {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 4px;
}

td:nth-child(4) {
    color: #FFD700;
    letter-spacing: 2px;
}

td:nth-child(5) {
    max-width: 300px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

@media screen and (max-width: 768px) {
    td:nth-child(5) {
        max-width: 150px;
    }
}
</style>

<script>
function deleteReview(id) {
    if (confirm('Are you sure you want to delete this review?')) {
        fetch('delete_review.php', {
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
                alert('Error deleting review');
            }
        });
    }
}
</script> 