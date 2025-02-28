<?php
session_start();
if (!isset($_SESSION['staff_name'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

$error_message = "";
$success_message = "";
$product_data = null;

// Get categories for dropdown
$categories_sql = "SELECT * FROM categories ORDER BY category_name";
$categories_result = mysqli_query($data, $categories_sql);

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($data, $_GET['id']);
    $sql = "SELECT * FROM products WHERE product_id = '$id'";
    $result = mysqli_query($data, $sql);
    $product_data = mysqli_fetch_assoc($result);

    if (!$product_data) {
        die("Product not found");
    }

    // Get product attributes
    $attributes_sql = "SELECT * FROM product_attributes WHERE product_id = '$id' ORDER BY attribute_name";
    $attributes_result = mysqli_query($data, $attributes_sql);
    $product_attributes = array();
    while ($attr = mysqli_fetch_assoc($attributes_result)) {
        $product_attributes[] = $attr;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = mysqli_real_escape_string($data, $_POST['product_id']);
    $name = mysqli_real_escape_string($data, $_POST['name']);
    $description = mysqli_real_escape_string($data, $_POST['description']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $category_id = intval($_POST['category_id']);

    $image_url = $product_data['image_url']; // Keep existing image by default

    // Handle new image upload if provided
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
            // Delete old image if exists
            if (!empty($product_data['image_url']) && file_exists("../" . $product_data['image_url'])) {
                unlink("../" . $product_data['image_url']);
            }
            $image_url = 'uploads/products/' . $new_filename;
        } else {
            $error_message = "Error uploading file";
        }
    }

    if (empty($error_message)) {
        // Start transaction
        mysqli_begin_transaction($data);

        try {
            // Update product
            $sql = "UPDATE products SET 
                    name = '$name',
                    description = '$description',
                    price = $price,
                    stock_quantity = $stock_quantity,
                    category_id = $category_id,
                    image_url = '$image_url'
                    WHERE product_id = '$id'";

            if (!mysqli_query($data, $sql)) {
                throw new Exception("Error updating product: " . mysqli_error($data));
            }

            // Handle attributes
            // First, delete existing attributes
            $delete_attr_sql = "DELETE FROM product_attributes WHERE product_id = '$id'";
            if (!mysqli_query($data, $delete_attr_sql)) {
                throw new Exception("Error deleting old attributes: " . mysqli_error($data));
            }

            // Insert new/updated attributes
            if (isset($_POST['attribute_names']) && isset($_POST['attribute_values'])) {
                $attr_names = $_POST['attribute_names'];
                $attr_values = $_POST['attribute_values'];

                for ($i = 0; $i < count($attr_names); $i++) {
                    if (!empty($attr_names[$i]) && !empty($attr_values[$i])) {
                        $attr_name = mysqli_real_escape_string($data, $attr_names[$i]);
                        $attr_value = mysqli_real_escape_string($data, $attr_values[$i]);

                        $insert_attr_sql = "INSERT INTO product_attributes (product_id, attribute_name, attribute_value) 
                                          VALUES ('$id', '$attr_name', '$attr_value')";

                        if (!mysqli_query($data, $insert_attr_sql)) {
                            throw new Exception("Error inserting attribute: " . mysqli_error($data));
                        }
                    }
                }
            }

            // Commit transaction
            mysqli_commit($data);
            $success_message = "Product and attributes updated successfully";

            // Refresh product data
            $result = mysqli_query($data, "SELECT * FROM products WHERE product_id = '$id'");
            $product_data = mysqli_fetch_assoc($result);

            // Refresh attributes
            $attributes_result = mysqli_query($data, "SELECT * FROM product_attributes WHERE product_id = '$id' ORDER BY attribute_name");
            $product_attributes = array();
            while ($attr = mysqli_fetch_assoc($attributes_result)) {
                $product_attributes[] = $attr;
            }
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
    <title>Edit Product</title>
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

        .current-image {
            max-width: 200px;
            margin: 10px 0;
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
        <h2>Edit Product</h2>

        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="product_id" value="<?php echo $product_data['product_id']; ?>">

            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product_data['name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php
                    mysqli_data_seek($categories_result, 0);
                    while ($category = mysqli_fetch_assoc($categories_result)):
                    ?>
                        <option value="<?php echo $category['category_id']; ?>"
                            <?php echo ($category['category_id'] == $product_data['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?php echo htmlspecialchars($product_data['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="price">Price (RM)</label>
                <input type="number" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($product_data['price']); ?>" required>
            </div>

            <div class="form-group">
                <label for="stock_quantity">Stock Quantity</label>
                <input type="number" id="stock_quantity" name="stock_quantity" value="<?php echo htmlspecialchars($product_data['stock_quantity']); ?>" required>
            </div>

            <div class="form-group">
                <label>Current Image</label>
                <?php if (!empty($product_data['image_url'])): ?>
                    <img src="../<?php echo htmlspecialchars($product_data['image_url']); ?>" class="current-image" alt="Current Product Image">
                <?php else: ?>
                    <p>No image uploaded</p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="image">New Image (leave blank to keep current)</label>
                <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                <img id="imagePreview" style="display: none;">
            </div>

            <div class="attributes-section">
                <h3>Product Attributes</h3>
                <div id="attributes-container">
                    <?php if (!empty($product_attributes)): ?>
                        <?php foreach ($product_attributes as $attr): ?>
                            <div class="attribute-row">
                                <input type="text" name="attribute_names[]" placeholder="Attribute Name" value="<?php echo htmlspecialchars($attr['attribute_name']); ?>" required>
                                <input type="text" name="attribute_values[]" placeholder="Attribute Value" value="<?php echo htmlspecialchars($attr['attribute_value']); ?>" required>
                                <button type="button" class="btn-remove" onclick="removeAttribute(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn-add" onclick="addAttribute()">
                    <i class="fas fa-plus"></i> Add Attribute
                </button>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <button type="submit" class="btn">Update Product</button>
                <button type="button" class="btn" onclick="location.href='staff_dashboard.php?table=products'">Back</button>
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

        // Add at least one empty attribute row if none exist
        window.onload = function() {
            const container = document.getElementById('attributes-container');
            if (container.children.length === 0) {
                addAttribute();
            }
        };
    </script>
</body>

</html>