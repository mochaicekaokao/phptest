<?php


session_start();

$error_message = ""; // Initialize the error message variable

require_once 'db_connection.php';

if ($data === false) {
    die("connection error");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $fullName = $_POST["fullName"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $phone = $_POST["phone"]; // Add phone number input

    if ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } else {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        }
        // Validate phone number
        else if (!preg_match('/^[0-9]{9,10}$/', $phone)) {
            $error_message = 'Phone number must be 9 to 10 digits long.';
        } else {
            // Check if the username already exists in the database
            $sql_check_username = "SELECT * FROM login WHERE userName = '$name'";
            $result_username = mysqli_query($data, $sql_check_username);

            // Check if the email already exists in the database
            $sql_check_email = "SELECT * FROM login WHERE email = '$email'";
            $result_email = mysqli_query($data, $sql_check_email);

            if (mysqli_num_rows($result_username) > 0) {
                $error_message = 'Username already exists.';
            } elseif (mysqli_num_rows($result_email) > 0) {
                $error_message = 'Email already exists.';
            } else {
                // Hash the password before storing
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Add the new user to the database with hashed password, phone number, and fullName
                $sql = "INSERT INTO login (userName, fullName, email, password, phoneNumber) 
                        VALUES ('$name', '$fullName', '$email', '$hashed_password', '$phone')";
                if (mysqli_query($data, $sql)) {
                    // Redirect to the login page after successful registration
                    header("location: login.php");
                    exit();
                } else {
                    $error_message = "Error: " . mysqli_error($data);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background: #f6f5f7;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }

        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 16px;
        }

        .back-link:hover {
            color: #666;
        }

        .register-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 100%;
            max-width: 500px;
            margin-top: 60px;
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            border-color: #ff6b6b;
            outline: none;
        }

        .phone-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .phone-prefix {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .phone-prefix img {
            width: 20px;
            height: 15px;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background: #ff6b6b;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .submit-btn:hover {
            background: #ff5252;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .login-link p {
            color: #666;
            margin-bottom: 10px;
        }

        .login-link a {
            color: #ff6b6b;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: #ffe6e6;
            color: #ff0000;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .password-group {
            position: relative;
        }

        .password-toggle {
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
    <a href="logout.php" class="back-link">
        <i class="fas fa-arrow-left"></i>
        Back to home page
    </a>

    <div class="register-container">
        <div class="register-header">
            <h1>Create Account</h1>
            <p>Please fill in your details to register</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form action="#" method="POST">
            <div class="form-group">
                <label for="name">Username</label>
                <input type="text" id="name" name="name" placeholder="Choose a username" required
                    value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="fullName">Full Name</label>
                <input type="text" id="fullName" name="fullName" placeholder="Enter your full name" required
                    value="<?php echo isset($_POST['fullName']) ? htmlspecialchars($_POST['fullName']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-group">
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                    <i class="fa fa-eye password-toggle" id="togglePassword"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-group">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                    <i class="fa fa-eye password-toggle" id="toggleConfirmPassword"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <div class="phone-group">
                    <div class="phone-prefix">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/6/66/Flag_of_Malaysia.svg" alt="MY">
                        <span>+60</span>
                    </div>
                    <input type="text" id="phone" name="phone" placeholder="Enter phone number" required pattern="[0-9]{9,10}"
                        title="Enter 9-10 digits" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
            </div>

            <button type="submit" class="submit-btn">Create Account</button>

            <div class="login-link">
                <p>Already have an account?</p>
                <a href="login.php">Sign In</a>
            </div>
        </form>
    </div>

    <script>
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            icon.addEventListener("click", function() {
                if (input.type === "password") {
                    input.type = "text";
                    icon.classList.remove("fa-eye");
                    icon.classList.add("fa-eye-slash");
                } else {
                    input.type = "password";
                    icon.classList.remove("fa-eye-slash");
                    icon.classList.add("fa-eye");
                }
            });
        }

        togglePasswordVisibility("password", "togglePassword");
        togglePasswordVisibility("confirm_password", "toggleConfirmPassword");
    </script>
</body>

</html>