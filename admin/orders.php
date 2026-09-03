
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";


/* =========================
   UPDATE ORDER STATUS
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $order_id = $_POST['order_id'] ?? '';
    $status = $_POST['status'] ?? '';

    $allowed_statuses = [
        'pending',
        'processing',
        'completed',
        'cancelled'
    ];

    if (
        !empty($order_id) &&
        in_array($status, $allowed_statuses)
    ) {

        $stmt = $conn->prepare(
            "UPDATE orders SET status = ? WHERE order_id = ?"
        );

        $stmt->bind_param("ss", $status, $order_id);

        $stmt->execute();

        $stmt->close();
    }

    header("Location: orders.php");
    exit;
}


/* =========================
   ORDER COUNTS
========================= */

$total_orders = 0;
$pending_orders = 0;
$completed_orders = 0;


/* Total Orders */

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM orders"
);

if ($result) {

    $row = $result->fetch_assoc();

    $total_orders = $row['total'];
}


/* Pending Orders */

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM orders WHERE status = 'pending'"
);

if ($result) {

    $row = $result->fetch_assoc();

    $pending_orders = $row['total'];
}


/* Completed Orders */

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM orders WHERE status = 'completed'"
);

if ($result) {

    $row = $result->fetch_assoc();

    $completed_orders = $row['total'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Orders - Maan Ghafar Garments</title>

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
            line-height: 1.4;
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

        /* Main */
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

        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
            border-left: 4px solid #b8860b;
        }

        .stat-card h3 {
            font-size: 15px;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 30px;
            font-weight: bold;
            color: #111827;
        }

        /* Orders Box */
        .orders-box {
            background: #ffffff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .orders-header h2 {
            color: #111827;
            font-size: 21px;
        }

        .empty-message {
            text-align: center;
            padding: 55px 20px;
            color: #6b7280;
        }

        .empty-icon {
            font-size: 45px;
            margin-bottom: 15px;
        }

        .empty-message h3 {
            color: #111827;
            margin-bottom: 8px;
        }

        .empty-message p {
            line-height: 1.6;
        }
        .orders-table {
    width: 100%;
    overflow-x: auto;
}

.orders-table table {
    width: 100%;
    min-width: 900px;
    border-collapse: collapse;
    margin-top: 5px;
}

.orders-table th,
.orders-table td {
    padding: 14px 12px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.orders-table th {
    background: #f9fafb;
    color: #374151;
    font-size: 14px;
}

.orders-table td {
    color: #4b5563;
    font-size: 14px;
}

.orders-table tr:hover {
    background: #fafafa;
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

        /* Mobile */
        @media (max-width: 900px) {

            .sidebar {
                width: 210px;
            }

            .main-content {
                margin-left: 210px;
            }

            .stats {
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

            .orders-box {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <aside class="sidebar">

        <div class="logo">
            MAAN GHAFAR<br>
            GARMENTS
        </div>

        <div class="admin-title">
            ADMIN PANEL
        </div>

        <nav class="menu">

            <a href="dashboard.php">
                🏠 Dashboard
            </a>

            <a href="products.php">
                📦 Products
            </a>

            <a href="orders.php" class="active">
                🛒 Orders
            </a>

            <a href="customers.php">
                👥 Customers
            </a>

        </nav>

        <div class="logout">
            <a href="logout.php">
                Logout
            </a>
        </div>

    </aside>


    <!-- Main Content -->
    <main class="main-content">

        <div class="topbar">

            <h1>Orders</h1>

            <p>
                Manage and monitor customer orders from here.
            </p>

        </div>


        <!-- Statistics -->
        <div class="stats">

            <div class="stat-card">

                <h3>Total Orders</h3>

                <div class="stat-number">
                    <?php echo $total_orders; ?>
                </div>

            </div>


            <div class="stat-card">

                <h3>Pending Orders</h3>

                <div class="stat-number">
                    <?php echo $pending_orders; ?>
                </div>

            </div>


            <div class="stat-card">

                <h3>Completed Orders</h3>

                <div class="stat-number">
                    <?php echo $completed_orders; ?>
                </div>

            </div>

        </div>


        <!-- Orders -->
        <div class="orders-box">

            <div class="orders-header">

                <h2>Recent Orders</h2>

            </div>


           <div class="orders-table">

    <?php
    $orders_result = $conn->query("
        SELECT order_id, customer_name, phone, total_amount, status, shipping_address, payment_method, created_at
        FROM orders
        ORDER BY created_at DESC
    ");
    ?>

    <?php if ($orders_result && $orders_result->num_rows > 0): ?>

        <table>

            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>

    <?php while ($order = $orders_result->fetch_assoc()): ?>

        <tr>

            <td>
                <?php echo htmlspecialchars($order['order_id']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($order['customer_name']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($order['phone']); ?>
            </td>

            <td>
                Rs. <?php echo htmlspecialchars($order['total_amount']); ?>
            </td>

            <td>
                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                    <?php echo htmlspecialchars($order['status']); ?>
                </span>
            </td>

            <td>
                <?php echo htmlspecialchars($order['payment_method']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($order['created_at']); ?>
            </td>

            <td>
                <form method="POST" action="orders.php">

    <input type="hidden"
           name="order_id"
           value="<?php echo htmlspecialchars($order['order_id']); ?>">

    <select name="status" onchange="this.form.submit()">

        <option value="pending"
            <?php echo ($order['status'] === 'pending') ? 'selected' : ''; ?>>
            Pending
        </option>

        <option value="processing"
            <?php echo ($order['status'] === 'processing') ? 'selected' : ''; ?>>
            Processing
        </option>

        <option value="completed"
            <?php echo ($order['status'] === 'completed') ? 'selected' : ''; ?>>
            Completed
        </option>

        <option value="cancelled"
            <?php echo ($order['status'] === 'cancelled') ? 'selected' : ''; ?>>
            Cancelled
        </option>

    </select>

</form>
            </td>

        </tr>

    <?php endwhile; ?>

</tbody>

        </table>

    <?php else: ?>

        <div class="empty-message">
            <div class="empty-icon">🛒</div>

            <h3>No Orders Yet</h3>

            <p>
                Customer orders will appear here once
                customers start placing orders.
            </p>
        </div>

    <?php endif; ?>

</div>

        </div>

    </main>

</body>
</html>