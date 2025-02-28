<?php


session_start();

require_once 'db_connection.php'; // Use the common connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$username = $_POST["username"];
	$password = $_POST["password"];


	// Changed the login query to use prepared statements to prevent SQL injection
	//Separated the username check and password verification
	//Added password_verify() to check if the entered password matches the stored hash
	$sql = "SELECT * FROM login WHERE username = ?";
	$stmt = mysqli_prepare($data, $sql);
	mysqli_stmt_bind_param($stmt, "s", $username);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);

	if (mysqli_num_rows($result) > 0) {
		$row = mysqli_fetch_array($result);
		if (password_verify($password, $row["password"])) {
			// Store both username and user_id in session
			$_SESSION["username"] = $username;
			$_SESSION["user_id"] = $row["userId"];  // Make sure this matches your column name in the login table

			// Debug: Print session data
			echo "Session data set:<pre>";
			print_r($_SESSION);
			echo "</pre>";

			header("location:index.php");
			exit();
		} else {
			echo "username or password incorrect";
		}
	} else {
		echo "username or password incorrect";
	}
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Sign in</title>
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

		.login-container {
			background: white;
			border-radius: 10px;
			box-shadow: 0 14px 28px rgba(0, 0, 0, 0.1);
			padding: 30px;
			width: 100%;
			max-width: 400px;
			margin-top: 60px;
		}

		.login-header {
			text-align: center;
			margin-bottom: 30px;
		}

		.login-header h1 {
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

		.register-link {
			text-align: center;
			margin-top: 20px;
			padding-top: 20px;
			border-top: 1px solid #eee;
		}

		.register-link p {
			color: #666;
			margin-bottom: 10px;
		}

		.register-link a {
			color: #ff6b6b;
			text-decoration: none;
			font-weight: bold;
		}

		.register-link a:hover {
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
	</style>
</head>

<body>
	<a href="logout.php" class="back-link">
		<i class="fas fa-arrow-left"></i>
		Back to home page
	</a>

	<div class="login-container">
		<div class="login-header">
			<h1>Welcome Back</h1>
			<p>Please sign in to your account</p>
		</div>

		<?php if (isset($error_message)): ?>
			<div class="error-message">
				<?php echo $error_message; ?>
			</div>
		<?php endif; ?>

		<form action="#" method="POST">
			<div class="form-group">
				<label for="username">Username</label>
				<input type="text" id="username" name="username" placeholder="Enter your username" required>
			</div>

			<div class="form-group">
				<label for="password">Password</label>
				<input type="password" id="password" name="password" placeholder="Enter your password" required>
			</div>

			<button type="submit" name="login" class="submit-btn">Sign In</button>

			<div class="register-link">
				<p>Don't have an account?</p>
				<a href="register.php">Create an Account</a>
			</div>
		</form>
	</div>
</body>

</html>