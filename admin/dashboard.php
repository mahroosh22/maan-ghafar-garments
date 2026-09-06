<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";


/* =========================
   DASHBOARD STATISTICS
========================= */

$total_products = 0;
$total_orders = 0;
$total_customers = 0;
$pending_orders = 0;
$completed_orders = 0;
$unread_messages = 0;
$total_revenue = 0;


/* =========================
   TOTAL PRODUCTS
========================= */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
");

if ($result) {
    $row = $result->fetch_assoc();
    $total_products = (int) ($row['total'] ?? 0);
}


/* =========================
   TOTAL ORDERS
========================= */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
");

if ($result) {
    $row = $result->fetch_assoc();
    $total_orders = (int) ($row['total'] ?? 0);
}


/* =========================
   TOTAL CUSTOMERS
========================= */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'customer'
");

if ($result) {
    $row = $result->fetch_assoc();
    $total_customers = (int) ($row['total'] ?? 0);
}


/* =========================
   PENDING ORDERS
========================= */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE status = 'pending'
");

if ($result) {
    $row = $result->fetch_assoc();
    $pending_orders = (int) ($row['total'] ?? 0);
}


/* =========================
   COMPLETED ORDERS
========================= */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE status = 'completed'
");

if ($result) {
    $row = $result->fetch_assoc();
    $completed_orders = (int) ($row['total'] ?? 0);
}


/* =========================
   UNREAD MESSAGES
========================= */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM contact_messages
    WHERE status = 'unread'
");

if ($result) {
    $row = $result->fetch_assoc();
    $unread_messages = (int) ($row['total'] ?? 0);
}


/* =========================
   TOTAL REVENUE
========================= */

$result = $conn->query("
    SELECT
        COALESCE(SUM(total_amount), 0) AS revenue
    FROM orders
    WHERE status != 'cancelled'
");

if ($result) {
    $row = $result->fetch_assoc();
    $total_revenue = (float) ($row['revenue'] ?? 0);
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
    LIMIT 8
");


/* =========================
   RECENT MESSAGES
========================= */

$recent_messages = $conn->query("
    SELECT
        message_id,
        name,
        email,
        subject,
        message,
        status,
        created_at
    FROM contact_messages
    ORDER BY
        CASE
            WHEN status = 'unread' THEN 0
            ELSE 1
        END,
        created_at DESC
    LIMIT 5
");

?>

<!DOCTYPE html>

<html lang="en">

<head>

```
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

        background:
            linear-gradient(
                180deg,
                #111827 0%,
                #0b1120 100%
            );

        padding: 25px 18px;

        z-index: 9999;

        box-shadow:
            4px 0 20px rgba(0, 0, 0, 0.08);
    }


    .logo {
        text-align: center;

        color: #d4af37;

        font-size: 21px;
        font-weight: bold;

        letter-spacing: 1.5px;

        margin-bottom: 12px;

        line-height: 1.4;
    }


    .admin-title {
        text-align: center;

        color: #9ca3af;

        font-size: 11px;

        letter-spacing: 2px;

        margin-bottom: 35px;
    }


    /* =========================
       MENU
    ========================= */

    .menu {
        position: relative;

        z-index: 10000;
    }


    .menu a {
        display: flex;

        align-items: center;

        justify-content: space-between;

        width: 100%;

        text-decoration: none;

        color: #d1d5db;

        padding: 13px 15px;

        margin-bottom: 8px;

        border-radius: 9px;

        transition: 0.25s;

        cursor: pointer;

        font-size: 14px;
    }


    .menu-left {
        display: flex;

        align-items: center;

        gap: 10px;
    }


    .menu a:hover,
    .menu a.active {
        background: #b8860b;

        color: #ffffff;

        transform: translateX(2px);
    }


    .menu-badge {
        min-width: 22px;

        height: 22px;

        padding: 0 6px;

        display: flex;

        align-items: center;

        justify-content: center;

        background: #dc2626;

        color: #ffffff;

        border-radius: 20px;

        font-size: 11px;

        font-weight: bold;
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

        font-weight: 600;

        transition: 0.25s;
    }


    .logout a:hover {
        background: #b91c1c;

        transform: translateY(-2px);
    }


    /* =========================
       MAIN CONTENT
    ========================= */

    .main-content {
        margin-left: 250px;

        padding: 30px;

        min-height: 100vh;
    }


    /* =========================
       TOPBAR
    ========================= */

    .topbar {
        background:
            linear-gradient(
                135deg,
                #ffffff,
                #fafafa
            );

        padding: 25px 28px;

        border-radius: 15px;

        margin-bottom: 25px;

        box-shadow:
            0 5px 20px rgba(0, 0, 0, 0.06);

        border: 1px solid #eeeeee;

        display: flex;

        justify-content: space-between;

        align-items: center;

        gap: 20px;
    }


    .topbar h1 {
        font-size: 29px;

        color: #111827;

        margin-bottom: 7px;
    }


    .topbar p {
        color: #6b7280;

        font-size: 14px;
    }


    .topbar-name {
        color: #b8860b;
    }


    .dashboard-date {
        background: #f8f6ed;

        color: #7c5d08;

        padding: 10px 15px;

        border-radius: 8px;

        font-size: 13px;

        font-weight: 600;

        white-space: nowrap;
    }


    /* =========================
       STAT CARDS
    ========================= */

    .cards {
        display: grid;

        grid-template-columns:
            repeat(3, 1fr);

        gap: 18px;

        margin-bottom: 25px;
    }


    .card {
        position: relative;

        background: #ffffff;

        padding: 22px;

        min-height: 145px;

        border-radius: 14px;

        box-shadow:
            0 5px 20px rgba(0, 0, 0, 0.06);

        border: 1px solid #eeeeee;

        overflow: hidden;

        transition: 0.25s;
    }


    .card:hover {
        transform: translateY(-4px);

        box-shadow:
            0 10px 25px rgba(0, 0, 0, 0.09);
    }


    .card::after {
        content: "";

        position: absolute;

        right: -25px;
        top: -25px;

        width: 90px;
        height: 90px;

        background: #b8860b;

        opacity: 0.07;

        border-radius: 50%;
    }


    .card-top {
        display: flex;

        justify-content: space-between;

        align-items: center;

        margin-bottom: 15px;
    }


    .card h3 {
        font-size: 14px;

        color: #6b7280;

        font-weight: 600;
    }


    .card-icon {
        width: 38px;
        height: 38px;

        display: flex;

        align-items: center;
        justify-content: center;

        background: #f8f6ed;

        color: #9a7307;

        border-radius: 10px;

        font-size: 18px;
    }


    .card .number {
        font-size: 30px;

        font-weight: bold;

        color: #111827;
    }


    .card-link {
        display: inline-block;

        margin-top: 10px;

        color: #b8860b;

        text-decoration: none;

        font-size: 12px;

        font-weight: 600;
    }


    .card-link:hover {
        text-decoration: underline;
    }


    /* =========================
       REVENUE CARD
    ========================= */

    .revenue-card {
        background:
            linear-gradient(
                135deg,
                #111827,
                #1f2937
            );

        border: none;

        color: white;
    }


    .revenue-card h3 {
        color: #d1d5db;
    }


    .revenue-card .number {
        color: #ffffff;
    }


    .revenue-card .card-icon {
        background: rgba(212, 175, 55, 0.15);

        color: #d4af37;
    }


    .revenue-card .card-link {
        color: #d4af37;
    }


    /* =========================
       CONTENT GRID
    ========================= */

    .content-grid {
        display: grid;

        grid-template-columns:
            1.6fr 1fr;

        gap: 22px;

        margin-bottom: 25px;
    }


    /* =========================
       PANEL
    ========================= */

    .panel {
        background: #ffffff;

        border-radius: 14px;

        box-shadow:
            0 5px 20px rgba(0, 0, 0, 0.06);

        border: 1px solid #eeeeee;

        overflow: hidden;
    }


    .panel-header {
        padding: 20px 22px;

        border-bottom:
            1px solid #eeeeee;

        display: flex;

        justify-content: space-between;

        align-items: center;

        gap: 15px;
    }


    .panel-header h2 {
        color: #111827;

        font-size: 19px;
    }


    .panel-header p {
        color: #9ca3af;

        font-size: 12px;

        margin-top: 4px;
    }


    .panel-link {
        text-decoration: none;

        background: #111827;

        color: #ffffff;

        padding: 8px 13px;

        border-radius: 7px;

        font-size: 12px;

        font-weight: 600;

        white-space: nowrap;
    }


    .panel-link:hover {
        background: #b8860b;
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

        min-width: 650px;

        border-collapse: collapse;
    }


    .orders-table th,
    .orders-table td {
        padding: 13px 15px;

        text-align: left;

        border-bottom:
            1px solid #eeeeee;
    }


    .orders-table th {
        background: #fafafa;

        font-size: 12px;

        font-weight: 700;

        color: #6b7280;

        text-transform: uppercase;
    }


    .orders-table td {
        font-size: 13px;

        color: #4b5563;
    }


    .orders-table tr:last-child td {
        border-bottom: none;
    }


    .orders-table tr:hover {
        background: #fcfcfc;
    }


    .order-id {
        font-weight: 700;

        color: #111827;
    }


    .amount {
        font-weight: 700;

        color: #111827;
    }


    /* =========================
       STATUS BADGES
    ========================= */

    .status-badge {
        display: inline-block;

        padding: 5px 10px;

        border-radius: 20px;

        font-size: 11px;

        font-weight: 700;

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


    .view-btn {
        display: inline-block;

        text-decoration: none;

        background: #b8860b;

        color: #ffffff;

        padding: 6px 11px;

        border-radius: 6px;

        font-size: 11px;

        font-weight: 700;
    }


    .view-btn:hover {
        background: #967000;
    }


    /* =========================
       MESSAGES
    ========================= */

    .messages-list {
        padding: 5px 0;
    }


    .message-item {
        display: flex;

        gap: 13px;

        padding: 17px 20px;

        border-bottom:
            1px solid #eeeeee;

        text-decoration: none;

        color: inherit;

        transition: 0.2s;
    }


    .message-item:last-child {
        border-bottom: none;
    }


    .message-item:hover {
        background: #fafafa;
    }


    .message-item.unread {
        background: #fffdf4;
    }


    .message-avatar {
        width: 40px;
        height: 40px;

        min-width: 40px;

        display: flex;

        align-items: center;
        justify-content: center;

        background: #111827;

        color: #d4af37;

        border-radius: 50%;

        font-size: 14px;

        font-weight: bold;
    }


    .message-content {
        min-width: 0;

        flex: 1;
    }


    .message-name-row {
        display: flex;

        justify-content: space-between;

        gap: 10px;
    }


    .message-name {
        color: #111827;

        font-size: 14px;

        font-weight: 700;
    }


    .message-date {
        color: #9ca3af;

        font-size: 10px;

        white-space: nowrap;
    }


    .message-subject {
        color: #4b5563;

        font-size: 12px;

        font-weight: 600;

        margin-top: 4px;

        white-space: nowrap;

        overflow: hidden;

        text-overflow: ellipsis;
    }


    .message-preview {
        color: #9ca3af;

        font-size: 11px;

        margin-top: 5px;

        white-space: nowrap;

        overflow: hidden;

        text-overflow: ellipsis;
    }


    .new-badge {
        display: inline-block;

        background: #dc2626;

        color: #ffffff;

        font-size: 9px;

        padding: 3px 6px;

        border-radius: 10px;

        margin-left: 5px;
    }


    /* =========================
       QUICK ACTIONS
    ========================= */

    .quick-actions {
        padding: 20px;

        display: grid;

        grid-template-columns:
            repeat(2, 1fr);

        gap: 12px;
    }


    .quick-action {
        display: flex;

        align-items: center;

        gap: 10px;

        padding: 14px;

        background: #f8f9fb;

        border: 1px solid #eeeeee;

        border-radius: 9px;

        text-decoration: none;

        color: #374151;

        font-size: 12px;

        font-weight: 600;

        transition: 0.2s;
    }


    .quick-action:hover {
        background: #f8f6ed;

        border-color: #d4af37;

        color: #8a6500;

        transform: translateY(-2px);
    }


    .quick-icon {
        width: 32px;
        height: 32px;

        display: flex;

        align-items: center;
        justify-content: center;

        background: #ffffff;

        border-radius: 7px;

        font-size: 15px;
    }


    /* =========================
       WELCOME BOX
    ========================= */

    .welcome-box {
        background:
            linear-gradient(
                135deg,
                #111827,
                #1f2937
            );

        padding: 28px;

        border-radius: 14px;

        margin-bottom: 25px;

        color: white;

        box-shadow:
            0 7px 22px rgba(17, 24, 39, 0.15);

        position: relative;

        overflow: hidden;
    }


    .welcome-box::after {
        content: "✦";

        position: absolute;

        right: 35px;
        top: 10px;

        font-size: 90px;

        color: #d4af37;

        opacity: 0.08;
    }


    .welcome-box h2 {
        color: #d4af37;

        margin-bottom: 9px;

        font-size: 22px;
    }


    .welcome-box p {
        color: #d1d5db;

        line-height: 1.6;

        font-size: 14px;

        max-width: 650px;
    }


    /* =========================
       EMPTY MESSAGE
    ========================= */

    .empty-message {
        text-align: center;

        padding: 35px 20px;

        color: #9ca3af;

        font-size: 13px;
    }


    /* =========================
       MOBILE
    ========================= */

    @media (max-width: 1100px) {

        .cards {
            grid-template-columns:
                repeat(2, 1fr);
        }


        .content-grid {
            grid-template-columns: 1fr;
        }

    }


    @media (max-width: 900px) {

        .sidebar {
            width: 210px;
        }


        .main-content {
            margin-left: 210px;

            padding: 22px;
        }


        .cards {
            grid-template-columns:
                repeat(2, 1fr);
        }


        .topbar {
            align-items: flex-start;

            flex-direction: column;
        }

    }


    @media (max-width: 650px) {

        .sidebar {
            position: relative;

            width: 100%;

            height: auto;

            padding: 20px;

            box-shadow: none;
        }


        .logo {
            margin-bottom: 10px;
        }


        .admin-title {
            margin-bottom: 18px;
        }


        .menu {
            display: flex;

            gap: 7px;

            overflow-x: auto;

            padding-bottom: 5px;
        }


        .menu a {
            white-space: nowrap;

            width: auto;

            min-width: max-content;

            margin-bottom: 0;
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

            padding: 18px;
        }


        .topbar {
            padding: 20px;

            margin-bottom: 18px;
        }


        .topbar h1 {
            font-size: 24px;
        }


        .dashboard-date {
            display: none;
        }


        .cards {
            grid-template-columns: 1fr;

            gap: 13px;
        }


        .card {
            min-height: 125px;
        }


        .welcome-box {
            padding: 22px;

            margin-bottom: 18px;
        }


        .welcome-box h2 {
            font-size: 19px;
        }


        .panel-header {
            padding: 17px;

            align-items: flex-start;

            flex-direction: column;
        }


        .quick-actions {
            grid-template-columns: 1fr;
        }


        .message-name-row {
            flex-direction: column;

            gap: 3px;
        }


        .message-date {
            white-space: normal;
        }

    }

</style>
```

</head>

<body>

<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

```
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

        <span class="menu-left">
            🏠 Dashboard
        </span>

    </a>


    <a href="products.php">

        <span class="menu-left">
            📦 Products
        </span>

    </a>


    <!-- CATEGORIES ADDED -->

    <a href="categories.php">

        <span class="menu-left">
            📂 Categories
        </span>

    </a>


    <a href="orders.php">

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
                <?= $unread_messages ?>
            </span>

        <?php endif; ?>

    </a>


</nav>


<div class="logout">

    <a href="logout.php">
        🚪 Logout
    </a>

</div>
```

</aside>

<!-- =========================
     MAIN CONTENT
========================= -->

<main class="main-content">

```
<div class="topbar">

    <div>

        <h1>
            Dashboard
        </h1>


        <p>

            Welcome back,

            <strong class="topbar-name">

                <?= htmlspecialchars(
                    $_SESSION['admin_name'] ?? 'Admin',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </strong>

            — here's what's happening today.

        </p>

    </div>


    <div class="dashboard-date">

        📅
        <?= date("d M Y") ?>

    </div>


</div>



<div class="cards">


    <div class="card">

        <div class="card-top">

            <h3>
                Total Products
            </h3>

            <div class="card-icon">
                📦
            </div>

        </div>


        <div class="number">
            <?= $total_products ?>
        </div>


        <a
            href="products.php"
            class="card-link"
        >
            Manage Products →
        </a>

    </div>



    <div class="card">

        <div class="card-top">

            <h3>
                Total Orders
            </h3>

            <div class="card-icon">
                🛒
            </div>

        </div>


        <div class="number">
            <?= $total_orders ?>
        </div>


        <a
            href="orders.php"
            class="card-link"
        >
            View Orders →
        </a>

    </div>



    <div class="card">

        <div class="card-top">

            <h3>
                Total Customers
            </h3>

            <div class="card-icon">
                👥
            </div>

        </div>


        <div class="number">
            <?= $total_customers ?>
        </div>


        <a
            href="customers.php"
            class="card-link"
        >
            View Customers →
        </a>

    </div>



    <div class="card">

        <div class="card-top">

            <h3>
                Pending Orders
            </h3>

            <div class="card-icon">
                ⏳
            </div>

        </div>


        <div class="number">
            <?= $pending_orders ?>
        </div>


        <a
            href="orders.php"
            class="card-link"
        >
            Manage Pending →
        </a>

    </div>



    <div class="card">

        <div class="card-top">

            <h3>
                Completed Orders
            </h3>

            <div class="card-icon">
                ✓
            </div>

        </div>


        <div class="number">
            <?= $completed_orders ?>
        </div>


        <a
            href="orders.php"
            class="card-link"
        >
            View Completed →
        </a>

    </div>



    <div class="card">

        <div class="card-top">

            <h3>
                Unread Messages
            </h3>

            <div class="card-icon">
                💬
            </div>

        </div>


        <div class="number">
            <?= $unread_messages ?>
        </div>


        <a
            href="messages.php"
            class="card-link"
        >
            Open Messages →
        </a>

    </div>



    <div class="card revenue-card">

        <div class="card-top">

            <h3>
                Total Revenue
            </h3>

            <div class="card-icon">
                💰
            </div>

        </div>


        <div class="number">

            Rs.
            <?= number_format($total_revenue, 0) ?>

        </div>


        <span
            style="
                display:block;
                margin-top:10px;
                color:#9ca3af;
                font-size:11px;
            "
        >
            Excluding cancelled orders
        </span>

    </div>


</div>



<div class="welcome-box">


    <h2>
        Maan Ghafar Garments
    </h2>


    <p>

        Manage your ladies garments store from one place.
        Keep track of products, customers, orders and
        customer messages with ease.

    </p>


</div>



<div class="content-grid">


    <div class="panel">


        <div class="panel-header">


            <div>

                <h2>
                    Recent Orders
                </h2>

                <p>
                    Latest customer orders
                </p>

            </div>


            <a
                href="orders.php"
                class="panel-link"
            >
                View All
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
                                Order
                            </th>

                            <th>
                                Customer
                            </th>

                            <th>
                                Amount
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php while (
                            $order =
                            $recent_orders->fetch_assoc()
                        ): ?>


                            <?php

                            $order_status = strtolower(
                                trim(
                                    $order['status'] ?? 'pending'
                                )
                            );

                            $allowed_statuses = [
                                'pending',
                                'processing',
                                'completed',
                                'cancelled'
                            ];

                            if (
                                !in_array(
                                    $order_status,
                                    $allowed_statuses,
                                    true
                                )
                            ) {
                                $order_status = 'pending';
                            }

                            ?>


                            <tr>


                                <td>

                                    <span class="order-id">

                                        <?= htmlspecialchars(
                                            (string) $order['order_id'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $order['customer_name']
                                        ?? 'Guest',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>


                                <td>

                                    <span class="amount">

                                        Rs.
                                        <?= number_format(
                                            (float) $order['total_amount'],
                                            0
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <span
                                        class="status-badge status-<?=
                                        $order_status
                                        ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $order_status,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <a
                                        href="order_view.php?id=<?= urlencode(
                                            (string) $order['order_id']
                                        ) ?>"
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

                🛒 No orders found yet.

            </div>


        <?php endif; ?>


    </div>



    <div class="panel">


        <div class="panel-header">


            <div>

                <h2>
                    Recent Messages
                </h2>

                <p>
                    Customer enquiries
                </p>

            </div>


            <a
                href="messages.php"
                class="panel-link"
            >
                View All
            </a>

        </div>


        <?php if (
            $recent_messages &&
            $recent_messages->num_rows > 0
        ): ?>


            <div class="messages-list">


                <?php while (
                    $msg =
                    $recent_messages->fetch_assoc()
                ): ?>


                    <?php

                    $message_name =
                        trim(
                            (string) (
                                $msg['name'] ?? ''
                            )
                        );

                    $avatar_letter =
                        $message_name !== ''
                            ? strtoupper(
                                substr(
                                    $message_name,
                                    0,
                                    1
                                )
                            )
                            : '?';

                    $message_status =
                        strtolower(
                            trim(
                                $msg['status'] ?? 'read'
                            )
                        );

                    ?>


                    <a
                        href="messages.php"
                        class="message-item <?= $message_status === 'unread'
                            ? 'unread'
                            : '' ?>"
                    >


                        <div class="message-avatar">

                            <?= htmlspecialchars(
                                $avatar_letter,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </div>


                        <div class="message-content">


                            <div class="message-name-row">


                                <div class="message-name">

                                    <?= htmlspecialchars(
                                        $msg['name'] ?? 'Customer',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>


                                    <?php if (
                                        $message_status === 'unread'
                                    ): ?>

                                        <span class="new-badge">
                                            NEW
                                        </span>

                                    <?php endif; ?>

                                </div>


                                <div class="message-date">

                                    <?= htmlspecialchars(
                                        date(
                                            "d M",
                                            strtotime(
                                                $msg['created_at']
                                            )
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </div>


                            </div>


                            <div class="message-subject">

                                <?= htmlspecialchars(
                                    $msg['subject'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </div>


                            <div class="message-preview">

                                <?= htmlspecialchars(
                                    $msg['message'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </div>


                        </div>


                    </a>


                <?php endwhile; ?>


            </div>


        <?php else: ?>


            <div class="empty-message">

                💬 No customer messages yet.

            </div>


        <?php endif; ?>


    </div>


</div>



<div class="panel">


    <div class="panel-header">

        <div>

            <h2>
                Quick Actions
            </h2>

            <p>
                Frequently used admin options
            </p>

        </div>

    </div>


    <div class="quick-actions">


        <a
            href="products.php"
            class="quick-action"
        >

            <span class="quick-icon">
                ➕
            </span>

            Add / Manage Products

        </a>


        <a
            href="categories.php"
            class="quick-action"
        >

            <span class="quick-icon">
                📂
            </span>

            Manage Categories

        </a>


        <a
            href="orders.php"
            class="quick-action"
        >

            <span class="quick-icon">
                📦
            </span>

            Manage Orders

        </a>


        <a
            href="customers.php"
            class="quick-action"
        >

            <span class="quick-icon">
                👥
            </span>

            View Customers

        </a>


        <a
            href="messages.php"
            class="quick-action"
        >

            <span class="quick-icon">
                💬
            </span>

            Check Customer Messages

        </a>


    </div>


</div>
```

</main>

</body>

</html>
