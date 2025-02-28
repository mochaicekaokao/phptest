<?php
if (!isset($data)) {
    die('Database connection not available');
}

$sql = "SELECT * FROM stafflogin ORDER BY staffID DESC";
$result = $data->query($sql);
?>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Position</th>
            <?php if ($staffPosition == 'manager'): ?>
                <th>Actions</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['staffID']); ?></td>
                    <td><?php echo htmlspecialchars($row['staffUserName']); ?></td>
                    <td><?php echo htmlspecialchars($row['staffEmail']); ?></td>
                    <td><?php echo htmlspecialchars($row['staffPosition']); ?></td>
                    <?php if ($staffPosition == 'manager'): ?>
                        <td class="actions">
                            <button class="btn btn-edit" onclick="location.href='edit_stafflogin.php?id=<?php echo $row['staffID']; ?>'">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-delete" onclick="deleteStaff(<?php echo $row['staffID']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" style="text-align: center;">No staff members found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<script>
    function deleteStaff(id) {
        if (confirm('Are you sure you want to delete this staff member?')) {
            fetch('delete_stafflogin.php', {
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
                        alert('Error deleting staff member');
                    }
                });
        }
    }
</script>