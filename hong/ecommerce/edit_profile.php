<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$success_message = '';
$error_message = '';

// Get current user data
$stmt = $pdo->prepare("SELECT userName, profilePic FROM login WHERE userId = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();
$profilePic = $current_user['profilePic'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_username = trim($_POST['new_username']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        $stmt = $pdo->prepare("SELECT password FROM login WHERE userId = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!password_verify($current_password, $user['password'])) {
            throw new Exception("Current password is incorrect");
        }

        $pdo->beginTransaction();

        if (!empty($new_username)) {
            // Check if username already exists (excluding current user)
            $stmt = $pdo->prepare("SELECT userId FROM login WHERE userName = ? AND userId != ?");
            $stmt->execute([$new_username, $_SESSION['user_id']]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Username already exists");
            }
            
            // Update username
            $stmt = $pdo->prepare("UPDATE login SET userName = ? WHERE userId = ?");
            $stmt->execute([$new_username, $_SESSION['user_id']]);
            
            // Update the session with new username
            $_SESSION['username'] = $new_username;
        }

        if (!empty($new_password)) {
            if (strlen($new_password) < 6) {
                throw new Exception("Password must be at least 6 characters long");
            }
            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match");
            }
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE login SET password = ? WHERE userId = ?");
            $stmt->execute([$hashed_password, $_SESSION['user_id']]);
        }

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
                if (!empty($profilePic) && file_exists("../" . $profilePic)) {
                    unlink("../" . $profilePic);
                }
                $profilePic = 'uploads/profile/' . $new_filename;
                $stmt = $pdo->prepare("UPDATE login SET profilePic = ? WHERE userId = ?");
                $stmt->execute([$profilePic, $_SESSION['user_id']]);
            } else {
                throw new Exception("Error uploading file");
            }
        }

        $pdo->commit();
        $success_message = "Profile updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
</head>
<style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }

        button:hover {
            background-color: #45a049;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .password-toggle {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }
        .profilePicture {
            width: 150px; 
            height: 150px;
            display: block;
            margin: 10px 0; 
            object-fit: cover; 
        }
        #imagePreview {
            width: 150px; 
            height: 150px;
            margin-top: 10px;
        }
    </style>
<body>
    <div class="container">
        <h1>Edit Profile</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Profile Picture</label>
                <?php if (!empty($profilePic)): ?>
                    <img src="<?php echo htmlspecialchars($profilePic); ?>" class="profilePicture" alt="Profile Picture" onerror="this.style.display='none'">
                <?php else: ?>
                    <p>No image uploaded</p>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="image">Profile Picture</label>
                <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">

                <img id="imagePreview" style="display: none;">
            </div>
            <div class="form-group">
                <label for="new_username">New Username</label>
                <input type="text" id="new_username" name="new_username" value="<?php echo htmlspecialchars($current_user['userName']); ?>">
            </div>
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password">
            </div>
            <button type="submit">Update Profile</button>
            <button type="button" onclick="window.location.href='index.php'" style="background-color: #ccc; color: #333; margin-top: 10px;">Back</button>
        </form>
    </div>
    <script>
    function previewImage(input) {
        const preview = document.getElementById("imagePreview");
        if (input.files && input.files[0]) {
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = "block";
            };

            reader.readAsDataURL(input.files[0]);
        } else {
            preview.style.display = "none";
        }
    }
    </script>
</body>
</html>