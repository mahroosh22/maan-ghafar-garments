
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

$customer_id = intval($_GET['id'] ?? 0);

if ($customer_id <= 0) {
    header("Location: customers.php");
    exit;
}


/* =========================
   CUSTOMER INFORMATION
========================= */

$stmt = $conn->prepare("
    SELECT id, name, email, phone, created_at
    FROM users
    WHERE id = ? AND role = 'customer'
    LIMIT 1
");

$stmt->bind_param("i", $customer_id);
$stmt->execute();

$customer_result = $stmt->get_result();
$customer = $customer_result->fetch_assoc();

$stmt->close();


if (!$customer) {
    echo "<script>
        alert('Customer not found.');
        window.location.href = 'customers.php';
    </script>";
    exit;
}


/* =========================
   CUSTOMER ORDERS
========================= */

$stmt = $conn->prepare("
    SELECT
        order_id,
        total_amount,
        status,
        shipping_address,
        payment_method,
        created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->bind_param("i", $customer_id);
$stmt->execute();

$orders_result = $stmt->get_result();

$stmt->close();


/* =========================
   ORDER STATISTICS
========================= */

$total_orders = 0;
$total_spent = 0;

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_orders,
        COALESCE(SUM(total_amount), 0) AS total_spent
    FROM orders
    WHERE user_id = ?
");

$stmt->bind_param("i", $customer_id);
$stmt->execute();

$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();

if ($stats) {
    $total_orders = $stats['total_orders'];
    $total_spent = $stats['total_spent'];
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Customer View - Maan Ghafar Garments
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

        .menu a {
            display: block;
            text-decoration: none;
            color: #d1d5db;
            padding: 14px 16px;
            margin-bottom: 8px;
            border-radius: 8px;
            transition: 0.3s;
        }

        .menu a:hover {
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


        /* =========================
           MAIN CONTENT
        ========================= */

        .main-content {
            margin-left: 250px;
            padding: 30px;
        }


        /* =========================
           TOPBAR
        ========================= */

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

        .back-btn {
            display: inline-block;
            margin-top: 15px;
            background: #b8860b;
            color: #ffffff;
            text-decoration: none;
            padding: 9px 15px;
            border-radius: 7px;
            font-size: 14px;
        }

        .back-btn:hover {
            background: #967000;
        }


        /* =========================
           CUSTOMER INFORMATION
        ========================= */

        .customer-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .customer-card h2 {
            color: #111827;
            font-size: 21px;
            margin-bottom: 20px;
        }

        .customer-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .info-box {
            background: #f9fafb;
            padding: 16px;
            border-radius: 8px;
        }

        .info-label {
            display: block;
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .info-value {
            color: #111827;
            font-size: 15px;
            font-weight: 600;
            word-break: break-word;
        }


        /* =========================
           STATISTICS
        ========================= */

        .stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #ffffff;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
            border-left: 4px solid #b8860b;
        }

        .stat-card h3 {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 9px;
        }

        .stat-number {
            color: #111827;
            font-size: 28px;
            font-weight: bold;
        }


        /* =========================
           ORDERS
        ========================= */

        .orders-box {
            background: #ffffff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .orders-box h2 {
            color: #111827;
            font-size: 21px;
            margin-bottom: 20px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        .orders-table th {
            background: #f9fafb;
            color: #374151;
            text-align: left;
            padding: 14px;
            font-size: 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        .orders-table td {
            padding: 14px;
            border-bottom: 1px solid #e5e7eb;
            color: #4b5563;
            font-size: 14px;
        }

        .orders-table tr:hover {
            background: #fafafa;
        }

        .order-id {
            color: #b8860b;
            font-weight: bold;
        }

        .amount {
            color: #111827;
            font-weight: bold;
        }


        /* =========================
           STATUS
        ========================= */

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .view-order {
            display: inline-block;
            background: #b8860b;
            color: #ffffff;
            text-decoration: none;
            padding: 7px 12px;
            border-radius: 6px;
            font-size: 13px;
        }

        .view-order:hover {
            background: #967000;
        }


        /* =========================
           EMPTY
        ========================= */

        .empty-message {
            text-align: center;
            padding: 45px 20px;
            color: #6b7280;
        }

        .empty-message h3 {
            color: #111827;
            margin-bottom: 8px;
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

            .customer-info {
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

            .stats {
                grid-template-columns: 1fr;
            }

            .customer-card,
            .orders-box {
                padding: 18px;
            }

        }

    </style>

</head>


<body>


    <!-- SIDEBAR -->

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


    <!-- MAIN -->

    <main class="main-content">


        <!-- TOPBAR -->

        <div class="topbar">

            <h1>
                Customer Details
            </h1>

            <p>
                View customer information and order history.
            </p>

            <a href="customers.php" class="back-btn">
                ← Back to Customers
            </a>

        </div>


        <!-- CUSTOMER INFORMATION -->

        <div class="customer-card">

            <h2>
                Customer Information
            </h2>

            <div class="customer-info">


                <div class="info-box">

                    <span class="info-label">
                        Customer ID
                    </span>

                    <span class="info-value">
                        #<?php echo htmlspecialchars($customer['id']); ?>
                    </span>

                </div>


                <div class="info-box">

                    <span class="info-label">
                        Name
                    </span>

                    <span class="info-value">
                        <?php echo htmlspecialchars($customer['name']); ?>
                    </span>

                </div>


                <div class="info-box">

                    <span class="info-label">
                        Email
                    </span>

                    <span class="info-value">
                        <?php echo htmlspecialchars($customer['email']); ?>
                    </span>

                </div>


                <div class="info-box">

                    <span class="info-label">
                        Phone
                    </span>

                    <span class="info-value">

                        <?php
                        echo !empty($customer['phone'])
                            ? htmlspecialchars($customer['phone'])
                            : 'Not provided';
                        ?>

                    </span>

                </div>


                <div class="info-box">

                    <span class="info-label">
                        Registered
                    </span>

                    <span class="info-value">
                        <?php echo htmlspecialchars($customer['created_at']); ?>
                    </span>

                </div>


            </div>

        </div>


        <!-- STATISTICS -->

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
                    Total Spent
                </h3>

                <div class="stat-number">
                    Rs. <?php echo number_format((float)$total_spent, 2); ?>
                </div>

            </div>


        </div>


        <!-- ORDERS -->

        <div class="orders-box">

            <h2>
                Order History
            </h2>


            <?php if ($orders_result && $orders_result->num_rows > 0): ?>


                <div class="table-wrapper">

                    <table class="orders-table">

                        <thead>

                            <tr>

                                <th>
                                    Order ID
                                </th>

                                <th>
                                    Amount
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

                                <?php

                                $status = strtolower($order['status'] ?? 'pending');

                                $status_class = 'status-pending';

                                if ($status === 'processing') {
                                    $status_class = 'status-processing';
                                } elseif ($status === 'completed') {
                                    $status_class = 'status-completed';
                                } elseif ($status === 'cancelled') {
                                    $status_class = 'status-cancelled';
                                }

                                ?>


                                <tr>


                                    <td>

                                        <span class="order-id">
                                            #<?php echo htmlspecialchars($order['order_id']); ?>
                                        </span>

                                    </td>


                                    <td>

                                        <span class="amount">
                                            Rs. <?php echo number_format((float)$order['total_amount'], 2); ?>
                                        </span>

                                    </td>


                                    <td>

                                        <span class="status <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>

                                    </td>


                                    <td>

                                        <?php
                                        echo !empty($order['payment_method'])
                                            ? htmlspecialchars($order['payment_method'])
                                            : 'Not specified';
                                        ?>

                                    </td>


                                    <td>

                                        <?php echo htmlspecialchars($order['created_at']); ?>

                                    </td>


                                    <td>

                                        <a
                                            href="order_view.php?id=<?php echo $order['order_id']; ?>"
                                            class="view-order"
                                        >
                                            View Order
                                        </a>

                                    </td>


                                </tr>


                            <?php endwhile; ?>


                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <div class="empty-message">

                    <h3>
                        No Orders Yet
                    </h3>

                    <p>
                        This customer has not placed any orders.
                    </p>

                </div>


            <?php endif; ?>


        </div>


    </main>


</body>

</html>