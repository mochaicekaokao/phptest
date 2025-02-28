<?php
session_start();
if (!isset($_SESSION['staff_name']) || $_SESSION['staffPosition'] !== 'manager') {
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $staffUserName = mysqli_real_escape_string($data, $_POST['staffUserName']);
    $staffEmail = mysqli_real_escape_string($data, $_POST['staffEmail']);
    $staffPassword = $_POST['staffPassword'];
    $staffPosition = mysqli_real_escape_string($data, $_POST['staffPosition']);

    // Validate email
    if (!filter_var($staffEmail, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format";
    } else {
        // Check if email already exists
        $check_sql = "SELECT * FROM stafflogin WHERE staffEmail = '$staffEmail'";
        $result = mysqli_query($data, $check_sql);

        if (mysqli_num_rows($result) > 0) {
            $error_message = "Email already exists";
        } else {
            // Hash the password
            $hashed_password = password_hash($staffPassword, PASSWORD_DEFAULT);

            $sql = "INSERT INTO stafflogin (staffUserName, staffEmail, staffPassword, staffPosition)
                    VALUES ('$staffUserName', '$staffEmail', '$hashed_password', '$staffPosition')";

            if (mysqli_query($data, $sql)) {
                $success_message = "Staff added successfully";
                $_POST = array(); // Clear form data after successful insertion
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
    <title>Add Staff</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../css/crud.css">
</head>

<body>
    <div class="form-container">
        <h2>Add New Staff</h2>

        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="staffUserName">Username</label>
                <input type="text" id="staffUserName" name="staffUserName" value="<?php echo isset($_POST['staffUserName']) ? htmlspecialchars($_POST['staffUserName']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="staffEmail">Email</label>
                <input type="email" id="staffEmail" name="staffEmail" value="<?php echo isset($_POST['staffEmail']) ? htmlspecialchars($_POST['staffEmail']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="staffPassword">Password</label>
                <input type="password" id="staffPassword" name="staffPassword" required>
            </div>

            <div class="form-group">
                <label for="staffPosition">Position</label>
                <select id="staffPosition" name="staffPosition" required>
                    <option value="manager">Manager</option>
                    <option value="staff">Staff</option>
                </select>
            </div>

            <div class="form-group">
                <button type="submit" class="btn">Add Staff</button>
                <button type="button" class="btn" onclick="location.href='staff_dashboard.php?table=stafflogin'">Back</button>
            </div>
        </form>
    </div>
</body>

</html>