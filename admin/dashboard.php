
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";
$total_products = 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM products");

if ($result) {
    $row = $result->fetch_assoc();
    $total_products = $row['total'];
}
$total_orders = 0; 
 
$result = $conn->query("SELECT COUNT(*) AS total FROM orders"); 
 
if ($result) { 
    $row = $result->fetch_assoc(); 
    $total_orders = $row['total']; 
}
$total_customers = 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'customer'");

if ($result) {
    $row = $result->fetch_assoc();
    $total_customers = $row['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - Maan Ghafar Garments</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6f8;
            color: #1f2937;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #111827;
            padding: 25px 18px;
        }

        .logo {
            text-align: center;
            color: #b8860b;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 40px;
        }

        .admin-title {
            text-align: center;
            color: #ffffff;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .menu a {
            display: block;
            text-decoration: none;
            color: #d1d5db;
            padding: 14px 16px;
            margin-bottom: 8px;
            border-radius: 8px;
            transition: 0.3s;
        }

        .menu a:hover,
        .menu a.active {
            background: #b8860b;
            color: #ffffff;
        }

        .logout {
            position: absolute;
            bottom: 25px;
            left: 18px;
            right: 18px;
        }

        .logout a {
            display: block;
            text-align: center;
            text-decoration: none;
            color: #ffffff;
            background: #dc2626;
            padding: 12px;
            border-radius: 8px;
        }

        .logout a:hover {
            background: #b91c1c;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 30px;
        }

        .topbar {
            background: #ffffff;
            padding: 22px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .topbar h1 {
            font-size: 28px;
            color: #111827;
            margin-bottom: 6px;
        }

        .topbar p {
            color: #6b7280;
        }

        /* Dashboard Cards */
        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .card {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
            border-left: 4px solid #b8860b;
        }

        .card h3 {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 12px;
        }

        .card .number {
            font-size: 32px;
            font-weight: bold;
            color: #111827;
        }

        /* Welcome Box */
        .welcome-box {
            margin-top: 25px;
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .welcome-box h2 {
            color: #111827;
            margin-bottom: 10px;
        }

        .welcome-box p {
            color: #6b7280;
            line-height: 1.6;
        }

        /* Mobile */
        @media (max-width: 900px) {
            .sidebar {
                width: 210px;
            }

            .main-content {
                margin-left: 210px;
            }

            .cards {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 650px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 20px;
            }

            .logo {
                margin-bottom: 15px;
            }

            .admin-title {
                margin-bottom: 15px;
            }

            .menu {
                display: flex;
                gap: 8px;
                overflow-x: auto;
            }

            .menu a {
                white-space: nowrap;
            }

            .logout {
                position: relative;
                left: auto;
                right: auto;
                bottom: auto;
                margin-top: 15px;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .topbar h1 {
                font-size: 23px;
            }
        }
        .orders-table {
    width: 100%;
    overflow-x: auto;
    margin-top: 20px;
}

.orders-table table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
}

.orders-table th,
.orders-table td {
    padding: 14px 16px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.orders-table th {
    background: #f5f5f5;
    font-weight: 600;
}

.orders-table tr:hover {
    background: #fafafa;
}

.orders-table td {
    font-size: 14px;
}
.orders-table td:nth-child(4) {
    font-weight: 600;
    text-transform: capitalize;
}
.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    text-transform: capitalize;
}
.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-processing {
    background: #cfe2ff;
    color: #084298;
}

.status-completed {
    background: #d1e7dd;
    color: #0f5132;
}

.status-cancelled {
    background: #f8d7da;
    color: #842029;
}
    </style>
</head>

<body>

    <!-- Sidebar -->
    <aside class="sidebar">

        <div class="logo">
            MAAN GHAFAR<br>GARMENTS
        </div>

        <div class="admin-title">
            ADMIN PANEL
        </div>

        <nav class="menu">
            <a href="dashboard.php" class="active">🏠 Dashboard</a>
            <a href="products.php">📦 Products</a>
            <a href="#">🛒 Orders</a>
            <a href="#">👥 Customers</a>
        </nav>

        <div class="logout">
            <a href="logout.php">Logout</a>
        </div>

    </aside>


    <!-- Main Content -->
    <main class="main-content">

        <div class="topbar">

            <h1>Dashboard</h1>

            <p>
                Welcome back,
                <strong><?php echo htmlspecialchars($_SESSION['admin_name']); ?></strong>
            </p>

        </div>


        <!-- Cards --> 
<div class="cards"> 

    <div class="card"> 
        <h3>Total Products</h3> 
        <div class="number"><?php echo $total_products; ?></div> 
        
    </div> 
    
    <div class="card">  
    <h3>Total Orders</h3>  
    <div class="number"><?php echo $total_orders; ?></div>  
</div> 

    <div class="card"> 
        <h3>Total Customers</h3> 
        <div class="number"><?php echo $total_customers; ?></div>

</div>

        <!-- Welcome -->
        <div class="welcome-box">

            <h2>Maan Ghafar Garments</h2>

            <p>
                This is your administration dashboard.
                From here you will be able to manage products,
                orders and customers.
            </p>

        </div>
<!-- Recent Orders -->
<div class="welcome-box">

    <h2>Recent Orders</h2>

    <?php
    $recent_orders = $conn->query("
        SELECT order_id, customer_name, total_amount, status, created_at
        FROM orders
        ORDER BY created_at DESC
    
    ");
    ?>

    <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>

        <div class="orders-table">

            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while ($order = $recent_orders->fetch_assoc()): ?>

                        <tr>
                            <td><?php echo htmlspecialchars($order['order_id']); ?></td>

                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>

                            <td>Rs. <?php echo htmlspecialchars($order['total_amount']); ?></td>

                            <td>
    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
        <?php echo htmlspecialchars($order['status']); ?>
    </span>
</td>

                            <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                        </tr>

                    <?php endwhile; ?>

                </tbody>
            </table>

        </div>

    <?php else: ?>

        <p>No orders found.</p>

    <?php endif; ?>

</div>
    </main>

</body>
</html>

