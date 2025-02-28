<?php
session_start();

// Debug session
echo "<div style='font-size: 6px;'>Session contents ( for debugging):<pre>";
print_r($_SESSION);
echo "</pre></div>";

// Handle form submission to store selected items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    if (isset($_POST['selected_items']) && !empty($_POST['selected_items'])) {
        $_SESSION['selected_for_checkout'] = $_POST['selected_items'];
        header('Location: checkout.php');
        exit;
    } else {
        $error_message = "Please select at least one item to checkout.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Gigabyte</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f8f8f8;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-title {
            color: #000;
            font-size: 2em;
            margin: 20px 0;
            text-align: center;
            letter-spacing: 1px;
        }

        .cart-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 30px;
        }

        .select-all-container {
            background: #f8f8f8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            border: 1px solid #eee;
        }

        .select-all-container label {
            margin-left: 10px;
            color: #333;
            cursor: pointer;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .cart-table th {
            background-color: #000;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cart-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .cart-table tr:last-child td {
            border-bottom: none;
        }

        .cart-table input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            border: 2px solid #000;
        }

        .product-name {
            color: #000;
            font-weight: 500;
        }

        .price {
            color: #000;
            font-weight: 500;
        }

        .quantity {
            color: #666;
        }

        .remove-btn {
            color: #000;
            text-decoration: none;
            font-size: 0.9em;
            padding: 5px 10px;
            border-radius: 4px;
            transition: all 0.3s;
            border: 1px solid #000;
        }

        .remove-btn:hover {
            background-color: #000;
            color: white;
        }

        .cart-summary {
            background: #f8f8f8;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid #eee;
        }

        .total-amount {
            font-size: 1.2em;
            color: #000;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .selected-amount {
            color: #000;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .checkout-btn {
            background-color: #000;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .checkout-btn:hover {
            background-color: #333;
            transform: translateY(-2px);
        }

        .checkout-btn.disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .back-link {
            display: inline-block;
            color: #000;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 5px;
            transition: all 0.3s;
            border: 1px solid #000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .back-link:hover {
            background-color: #000;
            color: white;
        }

        .error-message {
            background-color: #fff;
            color: #000;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #000;
        }

        .login-message {
            background-color: #f8f8f8;
            color: #000;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #000;
        }

        .login-message a {
            color: #000;
            font-weight: 500;
            text-decoration: none;
            border-bottom: 1px solid #000;
        }

        .login-message a:hover {
            border-bottom: 2px solid #000;
        }

        .empty-cart {
            text-align: center;
            padding: 40px 20px;
            color: #000;
        }

        .empty-cart i {
            font-size: 3em;
            color: #000;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .cart-table {
                display: block;
                overflow-x: auto;
            }

            .action-buttons {
                flex-direction: column;
            }

            .checkout-btn,
            .back-link {
                width: 100%;
                text-align: center;
            }
        }

        .search-container {
            margin-bottom: 20px;
        }

        .search-container input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            transition: all 0.3s;
        }

        .search-container input:focus {
            border-color: #000;
            outline: none;
        }

        .hidden-row {
            display: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 class="page-title">Your Shopping Cart</h1>

        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="cart-container">
            <form method="POST" id="checkout-form">
                <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                    <div class="search-container">
                        <input type="text" id="cartSearch" placeholder="Search in cart..." onkeyup="searchCart(this.value)">
                    </div>

                    <div class="select-all-container">
                        <input type="checkbox" id="select-all">
                        <label for="select-all">Select All Items</label>
                    </div>

                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>Product</th>
                                <th>Price (RM)</th>
                                <th>Quantity</th>
                                <th>Total (RM)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total = 0;
                            $selected_total = 0;
                            foreach ($_SESSION['cart'] as $index => $item):
                                $item_total = $item['price'] * $item['quantity'];
                                $total += $item_total;
                            ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selected_items[]" value="<?php echo $index; ?>"
                                            class="item-checkbox" onchange="updateTotal()">
                                    </td>
                                    <td class="product-name"><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td class="price">RM <?php echo number_format($item['price'], 2); ?></td>
                                    <td class="quantity"><?php echo $item['quantity']; ?></td>
                                    <td class="price item-total" data-total="<?php echo $item_total; ?>">
                                        RM <?php echo number_format($item_total, 2); ?>
                                    </td>
                                    <td>
                                        <a href="remove_from_cart.php?index=<?php echo $index; ?>" class="remove-btn">
                                            <i class="fas fa-trash"></i> Remove
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="cart-summary">
                        <div class="total-amount">Cart Total: RM <?php echo number_format($total, 2); ?></div>
                        <div class="selected-amount">Selected Total: RM <span id="selected-total">0.00</span></div>

                        <div class="action-buttons">
                            <?php if (!isset($_SESSION["user_id"])): ?>
                                <div class="login-message">
                                    Please <a href="login.php">login</a> to proceed with checkout.
                                </div>
                            <?php else: ?>
                                <button type="submit" name="checkout" class="checkout-btn">
                                    <i class="fas fa-shopping-cart"></i> Proceed to Checkout
                                </button>
                            <?php endif; ?>
                            <a href="index.php" class="back-link">
                                <i class="fas fa-arrow-left"></i> Continue Shopping
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <p>Your cart is empty.</p>
                        <a href="index.php" class="back-link">Continue Shopping</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
        function searchCart(query) {
            query = query.toLowerCase();
            const rows = document.querySelectorAll('.cart-table tbody tr');
            let hasVisibleRows = false;

            rows.forEach(row => {
                const productName = row.querySelector('.product-name').textContent.toLowerCase();
                if (productName.includes(query)) {
                    row.classList.remove('hidden-row');
                    hasVisibleRows = true;
                } else {
                    row.classList.add('hidden-row');
                }
            });

            updateTotal();
        }

        function updateTotal() {
            let selectedTotal = 0;
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                if (!row.classList.contains('hidden-row') && checkbox.checked) {
                    const totalCell = row.querySelector('.item-total');
                    selectedTotal += parseFloat(totalCell.dataset.total);
                }
            });
            document.getElementById('selected-total').textContent = selectedTotal.toFixed(2);
        }

        document.getElementById('select-all')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateTotal();
        });
    </script>
</body>

</html>