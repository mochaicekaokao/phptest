<?php
session_start();
if (!isset($_SESSION['staff_name'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

$error_message = "";
$success_message = "";

// Get categories for dropdown
$categories_sql = "SELECT * FROM categories ORDER BY category_name";
$categories_result = mysqli_query($data, $categories_sql);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($data, $_POST['name']);
    $description = mysqli_real_escape_string($data, $_POST['description']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $category_id = intval($_POST['category_id']);

    // Handle image upload
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/products/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        if (in_array($file_extension, $allowed_types) && move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = 'uploads/products/' . $new_filename;
        } else {
            $error_message = "Error uploading file";
        }
    }

    if (empty($error_message)) {
        // Start transaction
        mysqli_begin_transaction($data);

        try {
            // Insert product
            $sql = "INSERT INTO products (name, description, price, stock_quantity, category_id, image_url) 
                    VALUES ('$name', '$description', $price, $stock_quantity, $category_id, '$image_url')";

            if (!mysqli_query($data, $sql)) {
                throw new Exception("Error adding product: " . mysqli_error($data));
            }

            $product_id = mysqli_insert_id($data);

            // Insert attributes if provided
            if (isset($_POST['attribute_names']) && isset($_POST['attribute_values'])) {
                $attr_names = $_POST['attribute_names'];
                $attr_values = $_POST['attribute_values'];

                for ($i = 0; $i < count($attr_names); $i++) {
                    if (!empty($attr_names[$i]) && !empty($attr_values[$i])) {
                        $attr_name = mysqli_real_escape_string($data, $attr_names[$i]);
                        $attr_value = mysqli_real_escape_string($data, $attr_values[$i]);

                        $insert_attr_sql = "INSERT INTO product_attributes (product_id, attribute_name, attribute_value) 
                                          VALUES ($product_id, '$attr_name', '$attr_value')";

                        if (!mysqli_query($data, $insert_attr_sql)) {
                            throw new Exception("Error inserting attribute: " . mysqli_error($data));
                        }
                    }
                }
            }

            // Commit transaction
            mysqli_commit($data);
            $success_message = "Product and attributes added successfully";

            // Clear form data after successful submission
            $_POST = array();
        } catch (Exception $e) {
            mysqli_rollback($data);
            $error_message = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        .form-container {
            max-width: 800px;
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
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        textarea {
            height: 100px;
            resize: vertical;
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
        }

        .success {
            color: green;
            margin-bottom: 10px;
        }

        #imagePreview {
            max-width: 200px;
            margin-top: 10px;
        }

        .attributes-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }

        .attribute-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .attribute-row input {
            flex: 1;
        }

        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            cursor: pointer;
        }

        .btn-remove:hover {
            background: #c82333;
        }

        .btn-add {
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn-add:hover {
            background: #218838;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h2>Add New Product</h2>

        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                        <option value="<?php echo $category['category_id']; ?>"
                            <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="price">Price (RM)</label>
                <input type="number" id="price" name="price" step="0.01" required value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="stock_quantity">Stock Quantity</label>
                <input type="number" id="stock_quantity" name="stock_quantity" required value="<?php echo isset($_POST['stock_quantity']) ? htmlspecialchars($_POST['stock_quantity']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="image">Product Image</label>
                <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)" required>
                <img id="imagePreview" style="display: none;">
            </div>

            <div class="attributes-section">
                <h3>Product Attributes</h3>
                <div id="attributes-container">
                    <!-- Attribute rows will be added here -->
                </div>
                <button type="button" class="btn-add" onclick="addAttribute()">
                    <i class="fas fa-plus"></i> Add Attribute
                </button>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <button type="submit" class="btn">Add Product</button>
                <button type="button" class="btn" onclick="location.href='staff_dashboard.php?table=products'">Cancel</button>
            </div>
        </form>
    </div>

    <script>
        function previewImage(input) {
            var preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function addAttribute() {
            const container = document.getElementById('attributes-container');
            const newRow = document.createElement('div');
            newRow.className = 'attribute-row';
            newRow.innerHTML = `
                <input type="text" name="attribute_names[]" placeholder="Attribute Name" required>
                <input type="text" name="attribute_values[]" placeholder="Attribute Value" required>
                <button type="button" class="btn-remove" onclick="removeAttribute(this)">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(newRow);
        }

        function removeAttribute(button) {
            button.closest('.attribute-row').remove();
        }

        // Add at least one empty attribute row when the page loads
        window.onload = function() {
            addAttribute();
        };
    </script>
</body>

</html>