<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";


/* =========================
   TOTAL PRODUCTS
========================= */

$total_products = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
");

if ($result) {
    $row = $result->fetch_assoc();
    $total_products = $row['total'];
}


/* =========================
   TOTAL ORDERS
========================= */

$total_orders = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
");

if ($result) {
    $row = $result->fetch_assoc();
    $total_orders = $row['total'];
}


/* =========================
   TOTAL CUSTOMERS
========================= */

$total_customers = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'customer'
");

if ($result) {
    $row = $result->fetch_assoc();
    $total_customers = $row['total'];
}


/* =========================
   PENDING ORDERS
========================= */

$pending_orders = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE status = 'pending'
");

if ($result) {
    $row = $result->fetch_assoc();
    $pending_orders = $row['total'];
}


/* =========================
   COMPLETED ORDERS
========================= */

$completed_orders = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE status = 'completed'
");

if ($result) {
    $row = $result->fetch_assoc();
    $completed_orders = $row['total'];
}


/* =========================
   RECENT ORDERS
========================= */

$recent_orders = $conn->query("
    SELECT
        o.order_id,
        o.total_amount,
        o.status,
        o.created_at,
        u.name AS customer_name
    FROM orders o
    LEFT JOIN users u
        ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 10
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

    <title>
        Admin Dashboard - Maan Ghafar Garments
    </title>


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


        /* =========================
           MENU
        ========================= */

        .menu {
            position: relative;

            z-index: 10000;
        }


        .menu a {
            display: block;

            width: 100%;

            text-decoration: none;

            color: #d1d5db;

            padding: 14px 16px;

            margin-bottom: 8px;

            border-radius: 8px;

            transition: 0.3s;

            cursor: pointer;
        }


        .menu a:hover,
        .menu a.active {
            background: #b8860b;

            color: #ffffff;
        }


        /* =========================
           LOGOUT
        ========================= */

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

            box-shadow:
                0 3px 12px rgba(0, 0, 0, 0.06);
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
           DASHBOARD CARDS
        ========================= */

        .cards {
            display: grid;

            grid-template-columns:
                repeat(5, 1fr);

            gap: 18px;

            margin-bottom: 25px;
        }


        .card {
            background: #ffffff;

            padding: 22px;

            border-radius: 12px;

            box-shadow:
                0 3px 12px rgba(0, 0, 0, 0.06);

            border-left:
                4px solid #b8860b;
        }


        .card h3 {
            font-size: 14px;

            color: #6b7280;

            margin-bottom: 12px;
        }


        .card .number {
            font-size: 30px;

            font-weight: bold;

            color: #111827;
        }


        /* =========================
           WELCOME BOX
        ========================= */

        .welcome-box {
            margin-top: 25px;

            background: #ffffff;

            padding: 30px;

            border-radius: 12px;

            box-shadow:
                0 3px 12px rgba(0, 0, 0, 0.06);
        }


        .welcome-box h2 {
            color: #111827;

            margin-bottom: 10px;
        }


        .welcome-box p {
            color: #6b7280;

            line-height: 1.6;
        }


        /* =========================
           ORDERS HEADER
        ========================= */

        .orders-header {
            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 20px;
        }


        .orders-header h2 {
            color: #111827;
        }


        .all-orders-btn {
            display: inline-block;

            text-decoration: none;

            background: #111827;

            color: #ffffff;

            padding: 9px 15px;

            border-radius: 7px;

            font-size: 13px;

            font-weight: 600;
        }


        .all-orders-btn:hover {
            background: #374151;
        }


        /* =========================
           ORDERS TABLE
        ========================= */

        .orders-table {
            width: 100%;

            overflow-x: auto;
        }


        .orders-table table {
            width: 100%;

            min-width: 750px;

            border-collapse: collapse;

            background: #ffffff;
        }


        .orders-table th,
        .orders-table td {
            padding: 14px 16px;

            text-align: left;

            border-bottom:
                1px solid #eeeeee;
        }


        .orders-table th {
            background: #f5f5f5;

            font-weight: 600;

            color: #374151;
        }


        .orders-table tr:hover {
            background: #fafafa;
        }


        .orders-table td {
            font-size: 14px;

            color: #4b5563;
        }


        /* =========================
           STATUS BADGES
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
        }


        .view-btn:hover {
            background: #967000;
        }


        /* =========================
           EMPTY MESSAGE
        ========================= */

        .empty-message {
            text-align: center;

            padding: 35px 20px;

            color: #6b7280;
        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 1100px) {

            .cards {
                grid-template-columns:
                    repeat(3, 1fr);
            }

        }


        @media (max-width: 900px) {

            .sidebar {
                width: 210px;
            }


            .main-content {
                margin-left: 210px;
            }


            .cards {
                grid-template-columns:
                    repeat(2, 1fr);
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


            .cards {
                grid-template-columns: 1fr;
            }


            .welcome-box {
                padding: 20px;
            }


            .orders-header {
                align-items: flex-start;

                gap: 10px;

                flex-direction: column;
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


            <a
                href="dashboard.php"
                class="active"
            >
                🏠 Dashboard
            </a>


            <a href="products.php">
                📦 Products
            </a>


            <a href="orders.php">
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


    <!-- =========================
         MAIN CONTENT
    ========================= -->

    <main class="main-content">


        <!-- TOPBAR -->

        <div class="topbar">

            <h1>
                Dashboard
            </h1>


            <p>

                Welcome back,

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $_SESSION['admin_name'] ?? 'Admin'
                    );
                    ?>
                </strong>

            </p>

        </div>


        <!-- =========================
             DASHBOARD CARDS
        ========================= -->

        <div class="cards">


            <div class="card">

                <h3>
                    Total Products
                </h3>

                <div class="number">
                    <?php echo $total_products; ?>
                </div>

            </div>


            <div class="card">

                <h3>
                    Total Orders
                </h3>

                <div class="number">
                    <?php echo $total_orders; ?>
                </div>

            </div>


            <div class="card">

                <h3>
                    Total Customers
                </h3>

                <div class="number">
                    <?php echo $total_customers; ?>
                </div>

            </div>


            <div class="card">

                <h3>
                    Pending Orders
                </h3>

                <div class="number">
                    <?php echo $pending_orders; ?>
                </div>

            </div>


            <div class="card">

                <h3>
                    Completed Orders
                </h3>

                <div class="number">
                    <?php echo $completed_orders; ?>
                </div>

            </div>


        </div>


        <!-- =========================
             WELCOME BOX
        ========================= -->

        <div class="welcome-box">

            <h2>
                Maan Ghafar Garments
            </h2>


            <p>

                This is your administration dashboard.

                From here you can manage products,
                orders and customers.

            </p>

        </div>


        <!-- =========================
             RECENT ORDERS
        ========================= -->

        <div class="welcome-box">


            <div class="orders-header">

                <h2>
                    Recent Orders
                </h2>


                <a
                    href="orders.php"
                    class="all-orders-btn"
                >
                    View All Orders
                </a>

            </div>


            <?php if (
                $recent_orders &&
                $recent_orders->num_rows > 0
            ): ?>


                <div class="orders-table">


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
                                    Total Amount
                                </th>


                                <th>
                                    Status
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


                            <?php
                            while (
                                $order =
                                $recent_orders->fetch_assoc()
                            ):
                            ?>


                                <tr>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $order['order_id']
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $order['customer_name']
                                            ?? 'Guest'
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        Rs.

                                        <?php
                                        echo htmlspecialchars(
                                            $order['total_amount']
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <span
                                            class="status-badge status-<?php
                                            echo strtolower(
                                                $order['status']
                                            );
                                            ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $order['status']
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $order['created_at']
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <a
                                            href="order_view.php?id=<?php
                                            echo urlencode(
                                                $order['order_id']
                                            );
                                            ?>"
                                            class="view-btn"
                                        >
                                            View
                                        </a>

                                    </td>


                                </tr>


                            <?php endwhile; ?>


                        </tbody>


                    </table>


                </div>


            <?php else: ?>


                <div class="empty-message">

                    No orders found.

                </div>


            <?php endif; ?>


        </div>


    </main>


</body>

</html>