<?php
session_start();
if (!isset($_SESSION['staff_name'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userName = mysqli_real_escape_string($data, $_POST['userName']);
    $email = mysqli_real_escape_string($data, $_POST['email']);
    $password = $_POST['password'];
    $phoneNumber = mysqli_real_escape_string($data, $_POST['phoneNumber']);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format";
    } else {
        // Check if email already exists
        $check_sql = "SELECT * FROM login WHERE email = '$email'";
        $result = mysqli_query($data, $check_sql);

        if (mysqli_num_rows($result) > 0) {
            $error_message = "Email already exists";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO login (userName, email, password, phoneNumber) 
                    VALUES ('$userName', '$email', '$hashed_password', '$phoneNumber')";

            if (mysqli_query($data, $sql)) {
                $success_message = "User added successfully";
                // Clear form data after successful insertion
                $_POST = array();
            } else {
                $error_message = "Error: " . mysqli_error($data);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../css/crud.css">
</head>

<body>
    <div class="form-container">
        <h2>Add New User</h2>

        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="userName">Username</label>
                <input type="text" id="userName" name="userName" value="<?php echo isset($_POST['userName']) ? htmlspecialchars($_POST['userName']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="phoneNumber">Phone Number</label>
                <input type="text" id="phoneNumber" name="phoneNumber" value="<?php echo isset($_POST['phoneNumber']) ? htmlspecialchars($_POST['phoneNumber']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <button type="submit" class="btn">Add User</button>
                <button type="button" class="btn" onclick="location.href='staff_dashboard.php?table=login'">Back</button>
            </div>
        </form>
    </div>
</body>

</html>