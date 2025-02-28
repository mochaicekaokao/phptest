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
            <th>Image</th>
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
                    <td>
                        <?php if (!empty($row['profilePic'])): ?>
                            <img src="../<?php echo htmlspecialchars($row['profilePic']); ?>"
                                style="max-width: 30px; max-height: 30px;"
                                onclick="showFullImage(this.src)"
                                class="product-thumbnail">
                        <?php else: ?>
                            <span class="no-image">No Image</span>
                        <?php endif; ?>
                    </td>                    
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
                <td colspan="7" style="text-align: center;">No users found</td>
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
<div id="imageModal" class="modal" onclick="this.style.display='none'">
    <span class="close">&times;</span>
    <img class="modal-content" id="fullImage">
</div>
<script>
        function showFullImage(src) {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('fullImage');
        modal.style.display = "block";
        modalImg.src = src;
    }
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