<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";


/* =========================
   GET ORDER ID
========================= */

$order_id = $_GET['id'] ?? '';

if (empty($order_id)) {
    header("Location: orders.php");
    exit;
}


/* =========================
   ORDER DETAILS
========================= */

$stmt = $conn->prepare("
    SELECT
        o.order_id,
        o.user_id,
        o.total_amount,
        o.status,
        o.shipping_address,
        o.payment_method,
        o.created_at,
        u.name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.order_id = ?
    LIMIT 1
");

$stmt->bind_param("s", $order_id);
$stmt->execute();

$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();

$stmt->close();


if (!$order) {
    echo "<script>
        alert('Order not found.');
        window.location.href = 'orders.php';
    </script>";
    exit;
}


/* =========================
   ORDER ITEMS
========================= */

$item_stmt = $conn->prepare("
    SELECT
        oi.order_item_id,
        oi.product_id,
        oi.quantity,
        oi.price,
        p.product_name,
        p.image
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");

$item_stmt->bind_param("s", $order_id);
$item_stmt->execute();

$items_result = $item_stmt->get_result();


/* =========================
   PAYMENT DETAILS
========================= */

$payment = null;

$payment_stmt = $conn->prepare("
    SELECT
        payment_method,
        amount,
        transaction_id,
        payment_status,
        paid_at
    FROM payments
    WHERE order_id = ?
    ORDER BY payment_id DESC
    LIMIT 1
");

$payment_stmt->bind_param("s", $order_id);
$payment_stmt->execute();

$payment_result = $payment_stmt->get_result();
$payment = $payment_result->fetch_assoc();

$payment_stmt->close();


/* =========================
   SHIPPING DETAILS
========================= */

$shipping = null;

$shipping_stmt = $conn->prepare("
    SELECT
        shipping_address,
        city,
        shipping_status,
        shipped_at,
        postal_code,
        country
    FROM shipping
    WHERE order_id = ?
    LIMIT 1
");

$shipping_stmt->bind_param("s", $order_id);
$shipping_stmt->execute();

$shipping_result = $shipping_stmt->get_result();
$shipping = $shipping_result->fetch_assoc();

$shipping_stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Order Details - Maan Ghafar Garments
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
            z-index: 999;
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


        /* =========================
           MAIN
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
           BACK BUTTON
        ========================= */

        .back-btn {
            display: inline-block;
            background: #111827;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .back-btn:hover {
            background: #374151;
        }


        /* =========================
           ORDER GRID
        ========================= */

        .order-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }


        .info-box {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .info-box h2 {
            font-size: 19px;
            color: #111827;
            margin-bottom: 18px;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 12px;
        }


        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #6b7280;
            font-size: 14px;
        }

        .info-value {
            color: #111827;
            font-weight: 600;
            text-align: right;
            font-size: 14px;
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
           PRODUCTS
        ========================= */

        .products-box {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
            margin-bottom: 20px;
        }

        .products-box h2 {
            font-size: 19px;
            color: #111827;
            margin-bottom: 20px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        .products-table th {
            background: #f9fafb;
            color: #374151;
            text-align: left;
            padding: 13px;
            font-size: 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        .products-table td {
            padding: 13px;
            border-bottom: 1px solid #e5e7eb;
            color: #4b5563;
            font-size: 14px;
        }

        .product-image {
            width: 55px;
            height: 65px;
            object-fit: cover;
            border-radius: 6px;
            background: #f3f4f6;
        }

        .product-name {
            font-weight: 600;
            color: #111827;
        }


        /* =========================
           TOTAL
        ========================= */

        .total-box {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }

        .total {
            font-size: 22px;
            font-weight: bold;
            color: #b8860b;
        }


        /* =========================
           EMPTY
        ========================= */

        .empty-message {
            text-align: center;
            padding: 30px;
            color: #6b7280;
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

            .order-grid {
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

            .info-box,
            .products-box {
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


    <!-- =========================
         MAIN CONTENT
    ========================= -->

    <main class="main-content">


        <div class="topbar">

            <h1>
                Order Details
            </h1>

            <p>
                View complete information about this order.
            </p>

        </div>


        <a href="orders.php" class="back-btn">
            ← Back to Orders
        </a>


        <!-- =========================
             ORDER + CUSTOMER INFO
        ========================= -->

        <div class="order-grid">


            <!-- ORDER INFORMATION -->

            <div class="info-box">

                <h2>
                    Order Information
                </h2>

                <div class="info-row">

                    <span class="info-label">
                        Order ID
                    </span>

                    <span class="info-value">
                        <?php echo htmlspecialchars($order['order_id']); ?>
                    </span>

                </div>


                <div class="info-row">

                    <span class="info-label">
                        Date
                    </span>

                    <span class="info-value">
                        <?php echo htmlspecialchars($order['created_at']); ?>
                    </span>

                </div>


                <div class="info-row">

                    <span class="info-label">
                        Status
                    </span>

                    <span class="info-value">

                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">

                            <?php echo htmlspecialchars($order['status']); ?>

                        </span>

                    </span>

                </div>


                <div class="info-row">

                    <span class="info-label">
                        Payment Method
                    </span>

                    <span class="info-value">
                        <?php echo htmlspecialchars($order['payment_method']); ?>
                    </span>

                </div>


                <div class="info-row">

                    <span class="info-label">
                        Total Amount
                    </span>

                    <span class="info-value">
                        Rs. <?php echo htmlspecialchars($order['total_amount']); ?>
                    </span>

                </div>

            </div>


            <!-- CUSTOMER INFORMATION -->

            <div class="info-box">

                <h2>
                    Customer Information
                </h2>


                <div class="info-row">

                    <span class="info-label">
                        Name
                    </span>

                    <span class="info-value">
                        <?php echo htmlspecialchars($order['customer_name'] ?? 'Not available'); ?>
                    </span>

                </div>


                <div class="info-row">

                    <span class="info-label">
                        Email
                    </span>

                    <span class="info-value">
                        <?php echo htmlspecialchars($order['customer_email'] ?? 'Not available'); ?>
                    </span>

                </div>


                <div class="info-row">

                    <span class="info-label">
                        Phone
                    </span>

                    <span class="info-value">
                        <?php echo htmlspecialchars($order['customer_phone'] ?? 'Not provided'); ?>
                    </span>

                </div>


                <div class="info-row">

                    <span class="info-label">
                        Shipping Address
                    </span>

                    <span class="info-value">
                        <?php echo htmlspecialchars($order['shipping_address'] ?? 'Not provided'); ?>
                    </span>

                </div>

            </div>

        </div>


        <!-- =========================
             ORDER ITEMS
        ========================= -->

        <div class="products-box">

            <h2>
                Ordered Products
            </h2>


            <?php if ($items_result && $items_result->num_rows > 0): ?>

                <div class="table-wrapper">

                    <table class="products-table">

                        <thead>

                            <tr>

                                <th>
                                    Product
                                </th>

                                <th>
                                    Product ID
                                </th>

                                <th>
                                    Price
                                </th>

                                <th>
                                    Quantity
                                </th>

                                <th>
                                    Subtotal
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php while ($item = $items_result->fetch_assoc()): ?>

                                <tr>

                                    <td>

                                        <?php if (!empty($item['image'])): ?>

                                            <img
                                                src="../uploads/<?php echo htmlspecialchars($item['image']); ?>"
                                                class="product-image"
                                                alt="Product"
                                            >

                                        <?php endif; ?>

                                        <div class="product-name">

                                            <?php echo htmlspecialchars(
                                                $item['product_name'] ?? 'Product unavailable'
                                            ); ?>

                                        </div>

                                    </td>


                                    <td>
                                        #<?php echo htmlspecialchars($item['product_id']); ?>
                                    </td>


                                    <td>
                                        Rs. <?php echo htmlspecialchars($item['price']); ?>
                                    </td>


                                    <td>
                                        <?php echo htmlspecialchars($item['quantity']); ?>
                                    </td>


                                    <td>

                                        Rs.
                                        <?php

                                        echo htmlspecialchars(
                                            $item['price'] * $item['quantity']
                                        );

                                        ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>


                        </tbody>

                    </table>

                </div>


                <div class="total-box">

                    <div class="total">

                        Total:
                        Rs. <?php echo htmlspecialchars($order['total_amount']); ?>

                    </div>

                </div>


            <?php else: ?>

                <div class="empty-message">
                    No products found for this order.
                </div>

            <?php endif; ?>

        </div>


        <!-- =========================
             PAYMENT DETAILS
        ========================= -->

        <div class="order-grid">


            <div class="info-box">

                <h2>
                    Payment Details
                </h2>


                <?php if ($payment): ?>

                    <div class="info-row">

                        <span class="info-label">
                            Payment Method
                        </span>

                        <span class="info-value">
                            <?php echo htmlspecialchars($payment['payment_method']); ?>
                        </span>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            Amount
                        </span>

                        <span class="info-value">
                            Rs. <?php echo htmlspecialchars($payment['amount']); ?>
                        </span>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            Transaction ID
                        </span>

                        <span class="info-value">
                            <?php echo htmlspecialchars($payment['transaction_id'] ?? 'N/A'); ?>
                        </span>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            Payment Status
                        </span>

                        <span class="info-value">
                            <?php echo htmlspecialchars($payment['payment_status']); ?>
                        </span>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            Paid At
                        </span>

                        <span class="info-value">
                            <?php echo htmlspecialchars($payment['paid_at'] ?? 'N/A'); ?>
                        </span>

                    </div>


                <?php else: ?>

                    <div class="empty-message">
                        No payment record found.
                    </div>

                <?php endif; ?>

            </div>


            <!-- SHIPPING DETAILS -->

            <div class="info-box">

                <h2>
                    Shipping Details
                </h2>


                <?php if ($shipping): ?>

                    <div class="info-row">

                        <span class="info-label">
                            Address
                        </span>

                        <span class="info-value">
                            <?php echo htmlspecialchars($shipping['shipping_address']); ?>
                        </span>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            City
                        </span>

                        <span class="info-value">
                            <?php echo htmlspecialchars($shipping['city']); ?>
                        </span>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            Postal Code
                        </span>

                        <span class="info-value">
                            <?php echo htmlspecialchars($shipping['postal_code'] ?? 'N/A'); ?>
                        </span>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            Country
                        </span>

                        <span class="info-value">
                            <?php echo htmlspecialchars($shipping['country'] ?? 'N/A'); ?>
                        </span>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            Shipping Status
                        </span>

                        <span class="info-value">
                            <?php echo htmlspecialchars($shipping['shipping_status']); ?>
                        </span>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            Shipped At
                        </span>

                        <span class="info-value">
                            <?php echo htmlspecialchars($shipping['shipped_at'] ?? 'Not shipped'); ?>
                        </span>

                    </div>


                <?php else: ?>

                    <div class="empty-message">
                        No shipping record found.
                    </div>

                <?php endif; ?>

            </div>

        </div>


    </main>


</body>

</html>