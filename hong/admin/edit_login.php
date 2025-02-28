<?php
session_start();
if (!isset($_SESSION['staff_name'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

$error_message = "";
$success_message = "";
$user_data = null;

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($data, $_GET['id']);
    $sql = "SELECT * FROM login WHERE userId = '$id'";
    $result = mysqli_query($data, $sql);
    $user_data = mysqli_fetch_assoc($result);

    if (!$user_data) {
        die("User not found");
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = mysqli_real_escape_string($data, $_POST['userId']);
    $userName = mysqli_real_escape_string($data, $_POST['userName']);
    $email = mysqli_real_escape_string($data, $_POST['email']);
    $phoneNumber = mysqli_real_escape_string($data, $_POST['phoneNumber']);
    $new_password = $_POST['new_password'];

    // Check if email exists for other users
    $check_sql = "SELECT * FROM login WHERE email = '$email' AND userId != '$id'";
    $result = mysqli_query($data, $check_sql);

    if (mysqli_num_rows($result) > 0) {
        $error_message = "Email already exists";
    } else {
        if (!empty($new_password)) {
            // Update with new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE login SET 
                    userName = '$userName', 
                    email = '$email', 
                    password = '$hashed_password', 
                    phoneNumber = '$phoneNumber' 
                    WHERE userId = '$id'";
        } else {
            // Update without changing password
            $sql = "UPDATE login SET 
                    userName = '$userName', 
                    email = '$email', 
                    phoneNumber = '$phoneNumber' 
                    WHERE userId = '$id'";
        }

        if (mysqli_query($data, $sql)) {
            $success_message = "User updated successfully";
            // Refresh user data
            $result = mysqli_query($data, "SELECT * FROM login WHERE userId = '$id'");
            $user_data = mysqli_fetch_assoc($result);
        } else {
            $error_message = "Error: " . mysqli_error($data);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../css/crud.css">
</head>

<body>
    <div class="form-container">
        <h2>Edit User</h2>

        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="userId" value="<?php echo $user_data['userId']; ?>">

            <div class="form-group">
                <label for="userName">Username</label>
                <input type="text" id="userName" name="userName" value="<?php echo htmlspecialchars($user_data['userName']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="new_password">New Password (leave blank to keep current)</label>
                <input type="password" id="new_password" name="new_password">
            </div>

            <div class="form-group">
                <label for="phoneNumber">Phone Number</label>
                <input type="text" id="phoneNumber" name="phoneNumber" value="<?php echo htmlspecialchars($user_data['phoneNumber']); ?>" required>
            </div>

            <div class="form-group">
                <button type="submit" class="btn">Update User</button>
                <button type="button" class="btn" onclick="location.href='staff_dashboard.php?table=login'">Back</button>
            </div>
        </form>
    </div>
</body>

</html>