<?php
session_start();
if (!isset($_SESSION['staff_name']) || $_SESSION['staffPosition'] !== 'manager') {
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

$staffID = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$staff = null;

if ($staffID) {
    $sql = "SELECT * FROM stafflogin WHERE staffID = $staffID";
    $result = mysqli_query($data, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $staff = mysqli_fetch_assoc($result);
    } else {
        die("Staff not found");
    }
}

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $staffUserName = mysqli_real_escape_string($data, $_POST['staffUserName']);
    $staffEmail = mysqli_real_escape_string($data, $_POST['staffEmail']);
    $staffPosition = mysqli_real_escape_string($data, $_POST['staffPosition']);

    $update_sql = "UPDATE stafflogin SET staffUserName = '$staffUserName', staffEmail = '$staffEmail', staffPosition = '$staffPosition' WHERE staffID = $staffID";

    if (mysqli_query($data, $update_sql)) {
        $success_message = "Staff updated successfully";
    } else {
        $error_message = "Error: " . mysqli_error($data);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../css/crud.css">
</head>

<body>
    <div class="form-container">
        <h2>Edit Staff</h2>

        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="staffUserName">Username</label>
                <input type="text" id="staffUserName" name="staffUserName" value="<?php echo htmlspecialchars($staff['staffUserName']); ?>" required>
            </div>

            <div class="form-group">
                <label for="staffEmail">Email</label>
                <input type="email" id="staffEmail" name="staffEmail" value="<?php echo htmlspecialchars($staff['staffEmail']); ?>" required>
            </div>

            <div class="form-group">
                <label for="staffPosition">Position</label>
                <select id="staffPosition" name="staffPosition" required>
                    <option value="manager" <?php echo ($staff['staffPosition'] == 'manager') ? 'selected' : ''; ?>>Manager</option>
                    <option value="staff" <?php echo ($staff['staffPosition'] == 'staff') ? 'selected' : ''; ?>>Staff</option>
                </select>
            </div>

            <div class="form-group">
                <button type="submit" class="btn">Update Staff</button>
                <button type="button" class="btn" onclick="location.href='staff_dashboard.php?table=stafflogin'">Back</button>
            </div>
        </form>
    </div>
</body>

</html>