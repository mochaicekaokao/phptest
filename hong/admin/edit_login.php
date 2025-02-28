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
    $profilePic = $user_data['profilePic'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/profile/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        if (in_array($file_extension, $allowed_types) && move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            if (!empty($user_data['profilePic']) && file_exists("../" . $user_data['profilePic'])) {
                unlink("../" . $user_data['profilePic']);
            }
            $profilePic = 'uploads/profile/' . $new_filename;
        } else {
            $error_message = "Error uploading file";
        }
    }
    
    $check_sql = "SELECT * FROM login WHERE email = '$email' AND userId != '$id'";
    $result = mysqli_query($data, $check_sql);

    if (mysqli_num_rows($result) > 0) {
        $error_message = "Email already exists";
    } else {
        $update_sql = "UPDATE login SET userName='$userName', email='$email', phoneNumber='$phoneNumber', profilePic='$profilePic' WHERE userId='$id'";
        if (mysqli_query($data, $update_sql)) {
            $success_message = "User updated successfully";
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
<style>
    .profilePicture {
        width: 100px;
        height: 100px; 
        object-fit: cover;
    }
</style>
<body>
    <div class="form-container">
        <h2>Edit User</h2>

        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="userId" value="<?php echo $user_data['userId']; ?>">

            <div class="form-group">
                <label>Profile Picture</label>
                <?php if (!empty($user_data['profilePic'])): ?>
                    <img src="../<?php echo htmlspecialchars($user_data['profilePic']); ?>" class="profilePicture" alt="Profile Picture">
                <?php else: ?>
                    <p>No image uploaded</p>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="image">New Image (leave blank to keep current)</label>
                <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                <img id="imagePreview" style="display: none;">
            </div>

            <div class="form-group">
                <label for="userName">Username</label>
                <input type="text" id="userName" name="userName" value="<?php echo htmlspecialchars($user_data['userName']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
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