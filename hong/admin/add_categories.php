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
    $category_name = mysqli_real_escape_string($data, trim($_POST['category_name']));

    // Validate category name
    if (empty($category_name)) {
        $error_message = "Category name is required";
    } else {
        // Check if category already exists
        $check_sql = "SELECT category_id FROM categories WHERE category_name = '$category_name'";
        $check_result = mysqli_query($data, $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            $error_message = "Category already exists";
        } else {
            // Insert new category
            $sql = "INSERT INTO categories (category_name, created_at, updated_at) 
                    VALUES ('$category_name', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

            if (mysqli_query($data, $sql)) {
                $success_message = "Category added successfully";
                // Clear the form
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
    <title>Add Category</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .btn {
            background: #333;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }

        .btn:hover {
            background: #444;
        }

        .error {
            color: red;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #fee;
            border-radius: 4px;
        }

        .success {
            color: green;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #efe;
            border-radius: 4px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .back-btn {
            text-decoration: none;
            color: #333;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <div class="header">
            <h2>Add New Category</h2>
            <a href="staff_dashboard.php?table=categories" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Categories
            </a>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="category_name">Category Name</label>
                <input type="text" id="category_name" name="category_name"
                    value="<?php echo isset($_POST['category_name']) ? htmlspecialchars($_POST['category_name']) : ''; ?>"
                    required
                    placeholder="Enter category name">
            </div>

            <div class="form-group">
                <button type="submit" class="btn">Add Category</button>
                <button type="button" class="btn" onclick="location.href='staff_dashboard.php?table=categories'">Cancel</button>
            </div>
        </form>
    </div>

    <script>
        // Auto-capitalize first letter of each word
        document.getElementById('category_name').addEventListener('input', function(e) {
            let words = e.target.value.split(' ');
            let capitalizedWords = words.map(word => {
                if (word.length > 0) {
                    return word.charAt(0).toUpperCase() + word.slice(1);
                }
                return word;
            });
            e.target.value = capitalizedWords.join(' ');
        });
    </script>
</body>

</html>