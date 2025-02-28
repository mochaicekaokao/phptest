<?php
if (!isset($data)) {
    die('Database connection not available');
}

$sql = "SELECT * FROM login ORDER BY userId DESC";
$result = $data->query($sql);
?>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone Number</th>
            <?php if ($staffPosition == 'manager'): ?>
                <th>Actions</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['userId']); ?></td>
                    <td><?php echo htmlspecialchars($row['userName']); ?></td>
                    <td><?php echo htmlspecialchars($row['fullName']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['phoneNumber']); ?></td>
                    <?php if ($staffPosition == 'manager'): ?>
                        <td class="actions">
                            <button class="btn btn-edit" onclick="location.href='edit_login.php?id=<?php echo $row['userId']; ?>'">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-delete" onclick="deleteUser(<?php echo $row['userId']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" style="text-align: center;">No users found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<script>
    function deleteUser(id) {
        if (confirm('Are you sure you want to delete this user?')) {
            fetch('delete_login.php', {
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
                        alert(data.message || 'Error deleting user');
                    }
                })
                .catch(error => {
                    alert('Error: ' + error);
                });
        }
    }
</script>