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
   DELETE CUSTOMER
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $posted_token = $_POST['csrf_token'] ?? '';

    if (
        empty($posted_token) ||
        !hash_equals($csrf_token, $posted_token)
    ) {
        die("Invalid security token. Please go back and try again.");
    }


    $customer_id = filter_var(
        $_POST['customer_id'] ?? '',
        FILTER_VALIDATE_INT
    );


    if (
        $customer_id === false ||
        $customer_id <= 0
    ) {
        header("Location: customers.php");
        exit;
    }


    /* =========================
       VERIFY CUSTOMER
    ========================= */

    $stmt = $conn->prepare("
        SELECT id
        FROM users
        WHERE id = ?
        AND role = 'customer'
        LIMIT 1
    ");

    if (!$stmt) {
        header("Location: customers.php");
        exit;
    }


    $stmt->bind_param(
        "i",
        $customer_id
    );

    $stmt->execute();

    $customer_result = $stmt->get_result();

    $customer_exists = $customer_result->num_rows === 1;

    $stmt->close();


    if (!$customer_exists) {
        header("Location: customers.php");
        exit;
    }


    /* =========================
       DELETE CUSTOMER DATA
    ========================= */

    $conn->begin_transaction();


    try {

        /* DELETE ORDER ITEMS */

        $stmt = $conn->prepare("
            DELETE FROM order_items
            WHERE order_id IN (
                SELECT order_id
                FROM orders
                WHERE user_id = ?
            )
        ");

        if (!$stmt) {
            throw new Exception("Order items query failed.");
        }

        $stmt->bind_param(
            "i",
            $customer_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Order items deletion failed.");
        }

        $stmt->close();


        /* DELETE PAYMENTS */

        $stmt = $conn->prepare("
            DELETE FROM payments
            WHERE order_id IN (
                SELECT order_id
                FROM orders
                WHERE user_id = ?
            )
        ");

        if (!$stmt) {
            throw new Exception("Payments query failed.");
        }

        $stmt->bind_param(
            "i",
            $customer_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Payments deletion failed.");
        }

        $stmt->close();


        /* DELETE SHIPPING */

        $stmt = $conn->prepare("
            DELETE FROM shipping
            WHERE order_id IN (
                SELECT order_id
                FROM orders
                WHERE user_id = ?
            )
        ");

        if (!$stmt) {
            throw new Exception("Shipping query failed.");
        }

        $stmt->bind_param(
            "i",
            $customer_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Shipping deletion failed.");
        }

        $stmt->close();


        /* DELETE ORDERS */

        $stmt = $conn->prepare("
            DELETE FROM orders
            WHERE user_id = ?
        ");

        if (!$stmt) {
            throw new Exception("Orders query failed.");
        }

        $stmt->bind_param(
            "i",
            $customer_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Orders deletion failed.");
        }

        $stmt->close();


        /* DELETE ADDRESSES */

        $stmt = $conn->prepare("
            DELETE FROM addresses
            WHERE user_id = ?
        ");

        if (!$stmt) {
            throw new Exception("Addresses query failed.");
        }

        $stmt->bind_param(
            "i",
            $customer_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Addresses deletion failed.");
        }

        $stmt->close();


        /* DELETE CART */

        $stmt = $conn->prepare("
            DELETE FROM cart
            WHERE user_id = ?
        ");

        if (!$stmt) {
            throw new Exception("Cart query failed.");
        }

        $stmt->bind_param(
            "i",
            $customer_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Cart deletion failed.");
        }

        $stmt->close();


        /* DELETE WISHLIST */

        $stmt = $conn->prepare("
            DELETE FROM wishlist
            WHERE user_id = ?
        ");

        if (!$stmt) {
            throw new Exception("Wishlist query failed.");
        }

        $stmt->bind_param(
            "i",
            $customer_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Wishlist deletion failed.");
        }

        $stmt->close();


        /* DELETE REVIEWS */

        $stmt = $conn->prepare("
            DELETE FROM reviews
            WHERE user_id = ?
        ");

        if (!$stmt) {
            throw new Exception("Reviews query failed.");
        }

        $stmt->bind_param(
            "i",
            $customer_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Reviews deletion failed.");
        }

        $stmt->close();


        /* DELETE CUSTOMER */

        $stmt = $conn->prepare("
            DELETE FROM users
            WHERE id = ?
            AND role = 'customer'
        ");

        if (!$stmt) {
            throw new Exception("Customer query failed.");
        }

        $stmt->bind_param(
            "i",
            $customer_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Customer deletion failed.");
        }

        $stmt->close();


        /* SAVE ALL CHANGES */

        $conn->commit();


        echo "<script>
            alert('Customer and all related data deleted successfully.');
            window.location.href = 'customers.php';
        </script>";

        exit;


    } catch (Exception $e) {

        /* UNDO EVERYTHING */

        $conn->rollback();


        echo "<script>
            alert('Customer could not be deleted. Please try again.');
            window.location.href = 'customers.php';
        </script>";

        exit;
    }
}


/* =========================
   CUSTOMER STATISTICS
========================= */

$total_customers = 0;
$active_customers = 0;
$new_customers = 0;


/* Total Customers */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'customer'
");

if ($result) {

    $row = $result->fetch_assoc();

    $total_customers = (int) $row['total'];
}


/* Active Customers */

$active_customers = $total_customers;


/* New Customers */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'customer'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");

if ($result) {

    $row = $result->fetch_assoc();

    $new_customers = (int) $row['total'];
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
   CUSTOMER LIST
========================= */

$customers_result = $conn->query("
    SELECT
        id,
        name,
        email,
        phone,
        created_at
    FROM users
    WHERE role = 'customer'
    ORDER BY created_at DESC
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

    <title>Customers - Maan Ghafar Garments</title>

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
            position: relative;
            z-index: 1;
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
           CUSTOMER STATS
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
           CUSTOMERS BOX
        ========================= */

        .customers-box {
            background: #ffffff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .customers-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .customers-header h2 {
            color: #111827;
            font-size: 21px;
        }


        /* =========================
           CUSTOMER TABLE
        ========================= */

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .customers-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        .customers-table th {
            background: #f9fafb;
            color: #374151;
            text-align: left;
            padding: 14px;
            font-size: 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        .customers-table td {
            padding: 14px;
            border-bottom: 1px solid #e5e7eb;
            color: #4b5563;
            font-size: 14px;
        }

        .customers-table tr:hover {
            background: #fafafa;
        }

        .customer-name {
            font-weight: 600;
            color: #111827;
        }

        .customer-id {
            color: #b8860b;
            font-weight: bold;
        }


        /* =========================
           BUTTONS
        ========================= */

        .view-btn,
        .delete-btn {
            border: none;
            padding: 7px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            margin-right: 5px;
        }

        .view-btn {
            background: #b8860b;
            color: #ffffff;
            text-decoration: none;
            display: inline-block;
        }

        .delete-btn {
            background: #dc2626;
            color: #ffffff;
        }

        .view-btn:hover {
            background: #967000;
        }

        .delete-btn:hover {
            background: #b91c1c;
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

            .customers-box {
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
                    🏷️ Categories
                </span>

            </a>


            <a href="orders.php">

                <span class="menu-left">
                    🛒 Orders
                </span>

            </a>


            <a href="customers.php" class="active">

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
                Customers
            </h1>

            <p>
                Manage and monitor your customers from here.
            </p>

        </div>


        <!-- =========================
             STATISTICS
        ========================= -->

        <div class="stats">


            <div class="stat-card">

                <h3>
                    Total Customers
                </h3>

                <div class="stat-number">
                    <?php echo $total_customers; ?>
                </div>

            </div>


            <div class="stat-card">

                <h3>
                    Active Customers
                </h3>

                <div class="stat-number">
                    <?php echo $active_customers; ?>
                </div>

            </div>


            <div class="stat-card">

                <h3>
                    New Customers
                </h3>

                <div class="stat-number">
                    <?php echo $new_customers; ?>
                </div>

            </div>


        </div>


        <!-- =========================
             CUSTOMER LIST
        ========================= -->

        <div class="customers-box">


            <div class="customers-header">

                <h2>
                    Customer List
                </h2>

            </div>


            <?php if ($customers_result && $customers_result->num_rows > 0): ?>


                <div class="table-wrapper">

                    <table class="customers-table">


                        <thead>

                            <tr>

                                <th>ID</th>

                                <th>Name</th>

                                <th>Email</th>

                                <th>Phone</th>

                                <th>Registered</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php while ($customer = $customers_result->fetch_assoc()): ?>

                                <tr>

                                    <td>

                                        <span class="customer-id">

                                            #
                                            <?php echo htmlspecialchars(
                                                $customer['id'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span class="customer-name">

                                            <?php echo htmlspecialchars(
                                                $customer['name'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php echo htmlspecialchars(
                                            $customer['email'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo !empty($customer['phone'])

                                            ? htmlspecialchars(
                                                $customer['phone'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )

                                            : 'Not provided';

                                        ?>

                                    </td>


                                    <td>

                                        <?php echo htmlspecialchars(
                                            $customer['created_at'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <td>


                                        <a
                                            href="customer_view.php?id=<?php echo urlencode($customer['id']); ?>"
                                            class="view-btn"
                                        >
                                            View
                                        </a>


                                        <form
                                            method="POST"
                                            action="customers.php"
                                            style="display:inline;"
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
                                                name="customer_id"
                                                value="<?php echo htmlspecialchars(
                                                    $customer['id'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>"
                                            >


                                            <button
                                                type="submit"
                                                class="delete-btn"
                                                onclick="return confirm('WARNING: This will permanently delete the customer and all their orders and related data. Are you sure?');"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            <?php endwhile; ?>


                        </tbody>


                    </table>

                </div>


            <?php else: ?>


                <div class="empty-message">

                    <div class="empty-icon">
                        👥
                    </div>

                    <h3>
                        No Customers Yet
                    </h3>

                    <p>
                        Customer information will appear here
                        once customers register on the website.
                    </p>

                </div>


            <?php endif; ?>


        </div>


    </main>


</body>

</html>