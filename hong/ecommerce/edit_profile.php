<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_username = trim($_POST['new_username']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        // Get current user data
        $stmt = $pdo->prepare("SELECT password FROM login WHERE userId = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            throw new Exception("Current password is incorrect");
        }

        // Start transaction
        $pdo->beginTransaction();

        // Update username if provided
        if (!empty($new_username)) {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT userId FROM login WHERE userName = ? AND userId != ?");
            $stmt->execute([$new_username, $_SESSION['user_id']]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Username already exists");
            }

            $stmt = $pdo->prepare("UPDATE login SET userName = ? WHERE userId = ?");
            $stmt->execute([$new_username, $_SESSION['user_id']]);
        }

        // Update password if provided
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

        $pdo->commit();
        $success_message = "Profile updated successfully!";

    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = $e->getMessage();
    }
}

// Get current username
$stmt = $pdo->prepare("SELECT userName FROM login WHERE userId = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Profile</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="new_username">New Username</label>
                <input type="text" id="new_username" name="new_username" 
                       value="<?php echo htmlspecialchars($current_user['userName']); ?>">
            </div>

            <div class="form-group">
                <label for="current_password">Current Password</label>
                <div class="password-toggle">
                    <input type="password" id="current_password" name="current_password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('current_password')"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <div class="password-toggle">
                    <input type="password" id="new_password" name="new_password">
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('new_password')"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="password-toggle">
                    <input type="password" id="confirm_password" name="confirm_password">
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password')"></i>
                </div>
            </div>

            <button type="submit">Update Profile</button>
        </form>
    </div>

    <script>
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.nextElementSibling;
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function validateForm() {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (newPassword && newPassword.length < 6) {
            alert('Password must be at least 6 characters long');
            return false;
        }

        if (newPassword !== confirmPassword) {
            alert('New passwords do not match');
            return false;
        }

        return true;
    }
    </script>
</body>
</html>
