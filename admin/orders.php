
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";


/* =========================
   CSRF TOKEN
========================= */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];


/* =========================
   UPDATE ORDER STATUS
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $posted_token = $_POST['csrf_token'] ?? '';

    if (
        empty($posted_token) ||
        !hash_equals($csrf_token, $posted_token)
    ) {
        die("Invalid security token. Please go back and try again.");
    }

    $order_id = trim($_POST['order_id'] ?? '');
    $status = trim($_POST['status'] ?? '');

    $allowed_statuses = [
        'pending',
        'processing',
        'completed',
        'cancelled'
    ];

    if (
        $order_id !== '' &&
        in_array($status, $allowed_statuses, true)
    ) {

        $stmt = $conn->prepare("
            UPDATE orders
            SET status = ?
            WHERE order_id = ?
        ");

        if ($stmt) {

            $stmt->bind_param(
                "ss",
                $status,
                $order_id
            );

            $stmt->execute();
            $stmt->close();
        }
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

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
");

if ($result) {

    $row = $result->fetch_assoc();

    $total_orders = (int) $row['total'];
}


/* Pending Orders */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE status = 'pending'
");

if ($result) {

    $row = $result->fetch_assoc();

    $pending_orders = (int) $row['total'];
}


/* Completed Orders */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE status = 'completed'
");

if ($result) {

    $row = $result->fetch_assoc();

    $completed_orders = (int) $row['total'];
}


/* =========================
   UNREAD MESSAGES
========================= */

$unread_messages = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM contact_messages
    WHERE status = 'unread'
");

if ($result) {

    $row = $result->fetch_assoc();

    $unread_messages = (int) $row['total'];
}


/* =========================
   GET ORDERS
========================= */

$orders_result = $conn->query("
    SELECT
        o.order_id,
        o.user_id,
        o.total_amount,
        o.status,
        o.shipping_address,
        o.payment_method,
        o.created_at,
        u.name AS customer_name,
        u.phone AS customer_phone
    FROM orders o
    LEFT JOIN users u
        ON o.user_id = u.id
    ORDER BY o.created_at DESC
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

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


        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #111827;
            padding: 25px 18px;
            z-index: 9999;
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


        .menu {
            position: relative;
            z-index: 10000;
        }


        .menu a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none;
            color: #d1d5db;
            padding: 14px 16px;
            margin-bottom: 8px;
            border-radius: 8px;
            transition: 0.3s;
            position: relative;
            z-index: 10001;
        }


        .menu a:hover,
        .menu a.active {
            background: #b8860b;
            color: #ffffff;
        }


        .menu-left {
            display: flex;
            align-items: center;
        }


        .menu-badge {
            background: #dc2626;
            color: #ffffff;
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
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


        /* =========================
           MAIN CONTENT
        ========================= */

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


        /* =========================
           STATS
        ========================= */

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


        /* =========================
           ORDERS BOX
        ========================= */

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


        /* =========================
           TABLE
        ========================= */

        .orders-table {
            width: 100%;
            overflow-x: auto;
        }


        .orders-table table {
            width: 100%;
            min-width: 1000px;
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


        /* =========================
           STATUS
        ========================= */

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


        /* =========================
           VIEW BUTTON
        ========================= */

        .view-btn {
            display: inline-block;
            text-decoration: none;
            background: #b8860b;
            color: #ffffff;
            padding: 7px 13px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            margin-right: 7px;
        }


        .view-btn:hover {
            background: #967000;
        }


        /* =========================
           STATUS SELECT
        ========================= */

        .status-form {
            display: inline-block;
        }


        .status-form select {
            padding: 7px 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #ffffff;
            color: #374151;
            cursor: pointer;
            font-size: 13px;
        }


        .status-form select:focus {
            outline: none;
            border-color: #b8860b;
        }


        /* =========================
           EMPTY MESSAGE
        ========================= */

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


        /* =========================
           MOBILE
        ========================= */

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


    <!-- =========================
         SIDEBAR
    ========================= -->

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

                <span class="menu-left">
                    🏠 Dashboard
                </span>

            </a>


            <a href="products.php">

                <span class="menu-left">
                    📦 Products
                </span>

            </a>


            <!-- CATEGORIES -->

            <a href="categories.php">

                <span class="menu-left">
                    📂 Categories
                </span>

            </a>


            <a href="orders.php" class="active">

                <span class="menu-left">
                    🛒 Orders
                </span>

            </a>


            <a href="customers.php">

                <span class="menu-left">
                    👥 Customers
                </span>

            </a>


            <a href="messages.php">

                <span class="menu-left">
                    💬 Messages
                </span>


                <?php if ($unread_messages > 0): ?>

                    <span class="menu-badge">
                        <?php echo $unread_messages; ?>
                    </span>

                <?php endif; ?>

            </a>

        </nav>


        <div class="logout">

            <a href="logout.php">
                Logout
            </a>

        </div>

    </aside>


    <!-- =========================
         MAIN CONTENT
    ========================= -->

    <main class="main-content">


        <!-- TOP BAR -->

        <div class="topbar">

            <h1>
                Orders
            </h1>

            <p>
                Manage and monitor customer orders from here.
            </p>

        </div>


        <!-- =========================
             STATISTICS
        ========================= -->

        <div class="stats">


            <div class="stat-card">

                <h3>
                    Total Orders
                </h3>

                <div class="stat-number">
                    <?php echo $total_orders; ?>
                </div>

            </div>


            <div class="stat-card">

                <h3>
                    Pending Orders
                </h3>

                <div class="stat-number">
                    <?php echo $pending_orders; ?>
                </div>

            </div>


            <div class="stat-card">

                <h3>
                    Completed Orders
                </h3>

                <div class="stat-number">
                    <?php echo $completed_orders; ?>
                </div>

            </div>


        </div>


        <!-- =========================
             ORDERS
        ========================= -->

        <div class="orders-box">


            <div class="orders-header">

                <h2>
                    Recent Orders
                </h2>

            </div>


            <div class="orders-table">


                <?php if ($orders_result && $orders_result->num_rows > 0): ?>


                    <table>


                        <thead>

                            <tr>

                                <th>
                                    Order ID
                                </th>

                                <th>
                                    Customer
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Total Amount
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Payment
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php while ($order = $orders_result->fetch_assoc()): ?>


                                <tr>


                                    <!-- ORDER ID -->

                                    <td>

                                        <?php echo htmlspecialchars(
                                            $order['order_id'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- CUSTOMER -->

                                    <td>

                                        <?php echo htmlspecialchars(
                                            $order['customer_name'] ?? 'Guest',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- PHONE -->

                                    <td>

                                        <?php echo htmlspecialchars(
                                            $order['customer_phone'] ?? 'Not provided',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- TOTAL -->

                                    <td>

                                        Rs.
                                        <?php echo htmlspecialchars(
                                            $order['total_amount'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?php

                                        $display_status = strtolower(
                                            trim($order['status'] ?? '')
                                        );

                                        $safe_status_class = in_array(
                                            $display_status,
                                            [
                                                'pending',
                                                'processing',
                                                'completed',
                                                'cancelled'
                                            ],
                                            true
                                        )
                                            ? $display_status
                                            : 'pending';

                                        ?>

                                        <span class="status-badge status-<?php
                                            echo $safe_status_class;
                                        ?>">

                                            <?php echo htmlspecialchars(
                                                $order['status'] ?? 'Unknown',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </span>

                                    </td>


                                    <!-- PAYMENT -->

                                    <td>

                                        <?php echo htmlspecialchars(
                                            $order['payment_method'] ?? 'Not specified',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- DATE -->

                                    <td>

                                        <?php echo htmlspecialchars(
                                            $order['created_at'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- ACTION -->

                                    <td>


                                        <a
                                            href="order_view.php?id=<?php echo urlencode($order['order_id']); ?>"
                                            class="view-btn"
                                        >
                                            View
                                        </a>


                                        <form
                                            method="POST"
                                            action="orders.php"
                                            class="status-form"
                                        >


                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?php echo htmlspecialchars(
                                                    $csrf_token,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="order_id"
                                                value="<?php echo htmlspecialchars(
                                                    $order['order_id'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>"
                                            >


                                            <select
                                                name="status"
                                                onchange="this.form.submit()"
                                            >

                                                <option
                                                    value="pending"
                                                    <?php echo (
                                                        $order['status'] === 'pending'
                                                    ) ? 'selected' : ''; ?>
                                                >
                                                    Pending
                                                </option>


                                                <option
                                                    value="processing"
                                                    <?php echo (
                                                        $order['status'] === 'processing'
                                                    ) ? 'selected' : ''; ?>
                                                >
                                                    Processing
                                                </option>


                                                <option
                                                    value="completed"
                                                    <?php echo (
                                                        $order['status'] === 'completed'
                                                    ) ? 'selected' : ''; ?>
                                                >
                                                    Completed
                                                </option>


                                                <option
                                                    value="cancelled"
                                                    <?php echo (
                                                        $order['status'] === 'cancelled'
                                                    ) ? 'selected' : ''; ?>
                                                >
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

                        <div class="empty-icon">
                            🛒
                        </div>


                        <h3>
                            No Orders Yet
                        </h3>


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
