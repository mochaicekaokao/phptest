<?php
session_start();

$host = "localhost";
$user = "root";
$password = "";
$db = "ecommerce";

$conn = mysqli_connect($host, $user, $password, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // SQL query to fetch user by username
    $sql = "SELECT * FROM stafflogin WHERE staffUserName = '$username'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    // Check if the user exists and if the passwords match
    if ($row && $row['staffPassword'] === $password) {
        $_SESSION['staff_name'] = $row['staffUserName'];  // store the staff username in session
        $_SESSION['staffPosition'] = $row['staffPosition']; // store the staff position in session
        header("Location: staff_dashboard.php"); // Redirect to dashboard
        exit();
    } else {
        $error_message = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login</title>
    <link rel="stylesheet" href="../css/stafflogin.css">
</head>
<body>

<div class="login-container">
    <h2>Staff Login</h2>
    <?php if (!empty($error_message)) { echo "<p class='error'>$error_message</p>"; } ?>
    
    <form action="" method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
</div>

</body>
</html>
