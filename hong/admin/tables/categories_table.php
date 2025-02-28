<?php
if (!isset($data)) {
    die('Database connection not available');
}

$sql = "SELECT * FROM categories ORDER BY category_id DESC";
$result = $data->query($sql);
?>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Category Name</th>
            <th>Created At</th>
            <th>Updated At</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['category_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($row['updated_at'])); ?></td>
                    <td class="actions">
                        <button class="btn btn-edit" onclick="location.href='edit_category.php?id=<?php echo $row['category_id']; ?>'">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-delete" onclick="deleteCategory(<?php echo $row['category_id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" style="text-align: center;">No categories found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<script>
function deleteCategory(id) {
    if (confirm('Are you sure you want to delete this category?')) {
        fetch('delete_category.php', {
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
                alert('Error deleting category');
            }
        });
    }
}
</script> 