<?php
session_start();
if (!isset($_SESSION['staff_name'])) {
    header("Location: index.php");
    exit();
}

// Define arrays of tables for each section first
$user_management_tables = ['login', 'stafflogin'];
$product_tables = ['products', 'product_attributes', 'categories'];

require_once 'db_connection.php';

$staffPosition = isset($_SESSION['staffPosition']) ? $_SESSION['staffPosition'] : '';
$current_table = isset($_GET['table']) ? $_GET['table'] : 'login';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            display: flex;
            background-color: #f5f5f5;
            height: 100vh;
            overflow: hidden;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: #222;
            padding: 20px;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
        }

        .sidebar h2 {
            color: white;
            margin-bottom: 30px;
            font-size: 1.5em;
        }

        .sidebar a {
            display: block;
            color: #fff;
            padding: 10px;
            text-decoration: none;
            margin-bottom: 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .sidebar a:hover {
            background-color: #444;
        }

        .sidebar a.active {
            background-color: #444;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
            height: 100vh;
            overflow-y: auto;
            background-color: #fff;
        }

        .table-container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #222;
            color: white;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            color: white;
        }

        .btn-edit {
            background-color: #444;
        }

        .btn-delete {
            background-color: #666;
        }

        .btn-add {
            background-color: #222;
            padding: 10px 20px;
            margin-bottom: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-bar {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 300px;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <h2>Dashboard</h2>

        <!-- User Management -->
        <div class="sidebar-section">
            <h3>User Management</h3>
            <a href="?table=login" <?php if ($current_table == 'login') echo 'class="active"'; ?>>
                <i class="fas fa-users"></i> Members
            </a>
            <?php if ($staffPosition == 'manager'): ?>
                <a href="?table=stafflogin" <?php if ($current_table == 'stafflogin') echo 'class="active"'; ?>>
                    <i class="fas fa-user-tie"></i> Staff Management
                </a>
            <?php endif; ?>
        </div>

        <!-- Product Management -->
        <div class="sidebar-section">
            <h3>Products</h3>
            <a href="?table=products" <?php if ($current_table == 'products') echo 'class="active"'; ?>>
                <i class="fas fa-box"></i> Products
            </a>
            <a href="?table=lowInStockAlert" <?php if ($current_table == 'lowInStockAlert') echo 'class="active"'; ?>>
                <i class="fas fa-box"></i> Low In Stock Alert
            </a>
            <a href="?table=categories" <?php if ($current_table == 'categories') echo 'class="active"'; ?>>
                <i class="fas fa-tags"></i> Categories
            </a>
        </div>

        <!-- Order Management -->
        <div class="sidebar-section">
            <h3>Orders</h3>
            <a href="?table=orders" <?php if ($current_table == 'orders') echo 'class="active"'; ?>>
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
            <a href="?table=order_items" <?php if ($current_table == 'order_items') echo 'class="active"'; ?>>
                <i class="fas fa-list"></i> Order Items
            </a>
            <a href="?table=shipping" <?php if ($current_table == 'shipping') echo 'class="active"'; ?>>
                <i class="fas fa-truck"></i> Shipping
            </a>
        </div>

        <!-- Marketing -->
        <div class="sidebar-section">
            <h3>Marketing</h3>
            <a href="?table=coupons" <?php if ($current_table == 'coupons') echo 'class="active"'; ?>>
                <i class="fas fa-ticket-alt"></i> Coupons
            </a>
            <a href="?table=reviews" <?php if ($current_table == 'reviews') echo 'class="active"'; ?>>
                <i class="fas fa-star"></i> Reviews
            </a>
        </div>

        <!-- Payment Management -->
        <div class="sidebar-section">
            <h3>Payments</h3>
            <a href="?table=payments" <?php if ($current_table == 'payments') echo 'class="active"'; ?>>
                <i class="fas fa-"></i> Payments
            </a>
        </div>

        <div class="sidebar-section">
            <h3>Reports</h3>
            <a href="?table=topSellingProduct" <?php if ($current_table == 'topSellingProduct') echo 'class="active"'; ?>>
                <i class="fas fa-award"></i> Top Selling Products
            </a>
            <a href="?table=dataChart" <?php if ($current_table == 'dataChart') echo 'class="active"'; ?>>
                <i class="	fas fa-chart-area"></i> Data Chart
            </a>
        </div>

        <a href="logout.php" style="margin-top: 50px;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <div class="main-content">
        <div class="header">
            <h1><?php echo ucfirst(str_replace('_', ' ', $current_table)); ?> Management</h1>
            <div class="header-actions">
                <input type="text" class="search-bar" placeholder="Search...">
                <?php
                // Show Add New button only for user management and product sections
                $show_add_button = (
                    // User Management section
                    (in_array($current_table, $user_management_tables) &&
                        ($staffPosition == 'manager' || $current_table != 'stafflogin')) ||
                    // Product Management section
                    in_array($current_table, $product_tables)
                );

                if ($show_add_button):
                ?>
                    <button class="btn btn-add" onclick="location.href='add_<?php echo $current_table; ?>.php'">
                        <i class="fas fa-plus"></i> Add New
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-container">
            <?php include "tables/{$current_table}_table.php"; ?>
        </div>
    </div>

    <script>
        // Search functionality
        document.querySelector('.search-bar').addEventListener('keyup', function() {
            let searchValue = this.value.toLowerCase();
            let tableRows = document.querySelectorAll('table tbody tr');

            tableRows.forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });
    </script>
</body>

</html>