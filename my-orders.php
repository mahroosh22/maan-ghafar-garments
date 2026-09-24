<?php

session_start();

require_once "../config/database.php";


/* =========================
   CUSTOMER LOGIN CHECK
========================= */

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'customer'
) {
    header("Location: ../login.php");
    exit;
}


$user_id = (int) $_SESSION['user_id'];


/* =========================
   HELPER
========================= */

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================
   GET ORDER ID
========================= */

$order_id = trim($_GET['id'] ?? '');

if ($order_id === '' || strlen($order_id) > 50) {
    header("Location: orders.php");
    exit;
}


/* =========================
   GET ORDER
   CUSTOMER CAN ONLY SEE
   THEIR OWN ORDER
========================= */

$stmt = $conn->prepare("
    SELECT
        order_id,
        user_id,
        customer_name,
        phone,
        total_amount,
        status,
        shipping_address,
        payment_method,
        created_at
    FROM orders
    WHERE order_id = ?
    AND user_id = ?
    LIMIT 1
");


if (!$stmt) {

    error_log(
        "Order view prepare failed: " .
        $conn->error
    );

    die("Unable to load order.");
}


$stmt->bind_param(
    "si",
    $order_id,
    $user_id
);


if (!$stmt->execute()) {

    error_log(
        "Order view execute failed: " .
        $stmt->error
    );

    $stmt->close();

    die("Unable to load order.");
}


$result = $stmt->get_result();

$order = $result->fetch_assoc();

$stmt->close();


/* =========================
   ORDER NOT FOUND
========================= */

if (!$order) {
    header("Location: orders.php");
    exit;
}


/* =========================
   SAFE STATUS
========================= */

$order_status = strtolower(
    trim($order['status'] ?? '')
);


$allowed_statuses = [
    'pending',
    'processing',
    'completed',
    'cancelled'
];


if (!in_array($order_status, $allowed_statuses, true)) {
    $order_status = 'pending';
}


/* =========================
   GET ORDER ITEMS
========================= */

$item_stmt = $conn->prepare("
    SELECT
        oi.quantity,
        oi.price,
        p.product_name,
        p.image
    FROM order_items oi
    LEFT JOIN products p
        ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
    ORDER BY oi.order_item_id ASC
");


if (!$item_stmt) {

    error_log(
        "Order items prepare failed: " .
        $conn->error
    );

    die("Unable to load order items.");
}


$item_stmt->bind_param(
    "s",
    $order_id
);


if (!$item_stmt->execute()) {

    error_log(
        "Order items execute failed: " .
        $item_stmt->error
    );

    $item_stmt->close();

    die("Unable to load order items.");
}


$items_result = $item_stmt->get_result();


$total_quantity = 0;

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
        Order #<?php echo e($order_id); ?> -
        QAMROSH
    </title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {
            font-family: Arial, sans-serif;
            background: #f7f5f2;
            color: #222;
            line-height: 1.6;
        }


        /* =========================
           HEADER
        ========================= */

        header {
            background: #111;
            color: #fff;
            padding: 18px 6%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 18px rgba(0,0,0,0.12);
        }


        .logo {
            color: #fff;
            text-decoration: none;
            font-size: 24px;
            font-weight: 800;
            white-space: nowrap;
        }


        .logo span {
            color: #d4a017;
        }


        nav {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 18px;
            flex-wrap: wrap;
        }


        nav a {
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: 0.3s;
        }


        nav a:hover {
            color: #d4a017;
        }


        .cart-link {
            border: 1px solid #d4a017;
            padding: 8px 15px;
            border-radius: 22px;
        }


        .cart-link:hover {
            background: #d4a017;
            color: #111;
        }


        .logout-link {
            background: #b42318;
            padding: 8px 15px;
            border-radius: 22px;
        }


        .logout-link:hover {
            background: #d32f2f;
            color: #fff;
        }


        /* =========================
           PAGE
        ========================= */

        .page-wrapper {
            max-width: 1100px;
            margin: auto;
            padding: 50px 20px 70px;
        }


        .back-link {
            display: inline-block;
            margin-bottom: 25px;
            color: #555;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
        }


        .back-link:hover {
            color: #b8860b;
        }


        .page-heading {
            margin-bottom: 30px;
        }


        .page-label {
            color: #b8860b;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
        }


        .page-heading h1 {
            font-size: 34px;
            color: #111;
            margin-top: 7px;
        }


        .page-heading p {
            color: #777;
            margin-top: 6px;
            font-size: 14px;
        }


        /* =========================
           ORDER STATUS
        ========================= */

        .status-banner {
            background: #111;
            color: #fff;
            border-radius: 18px;
            padding: 25px 28px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }


        .status-banner h2 {
            font-size: 20px;
            margin-bottom: 4px;
        }


        .status-banner p {
            color: #aaa;
            font-size: 13px;
        }


        .status {
            display: inline-block;
            padding: 8px 18px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }


        .status.pending {
            background: #fff3cd;
            color: #856404;
        }


        .status.processing {
            background: #cfe2ff;
            color: #084298;
        }


        .status.completed {
            background: #d1e7dd;
            color: #0f5132;
        }


        .status.cancelled {
            background: #f8d7da;
            color: #842029;
        }


        /* =========================
           INFO CARD
        ========================= */

        .card {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 18px;
            padding: 28px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
        }


        .card-title {
            font-size: 20px;
            color: #111;
            margin-bottom: 20px;
        }


        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }


        .info-box {
            background: #faf9f6;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 16px;
        }


        .info-box.full {
            grid-column: span 2;
        }


        .info-label {
            display: block;
            color: #888;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 5px;
        }


        .info-value {
            color: #222;
            font-size: 14px;
            font-weight: 600;
            word-break: break-word;
        }


        .order-id {
            color: #b8860b;
            font-size: 16px;
        }


        /* =========================
           ITEMS
        ========================= */

        .items-list {
            border-top: 1px solid #eee;
        }


        .item {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 18px 0;
            border-bottom: 1px solid #eee;
        }


        .item:last-child {
            border-bottom: none;
        }


        .item-image {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid #eee;
            background: #f3f3f3;
            flex-shrink: 0;
        }


        .item-details {
            flex: 1;
            min-width: 0;
        }


        .item-details h3 {
            font-size: 17px;
            color: #222;
            margin-bottom: 5px;
        }


        .item-details p {
            color: #777;
            font-size: 13px;
            margin: 2px 0;
        }


        .item-total {
            font-size: 17px;
            font-weight: 800;
            color: #111;
            white-space: nowrap;
        }


        /* =========================
           TOTAL
        ========================= */

        .total-section {
            border-top: 2px solid #222;
            margin-top: 10px;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }


        .total-label {
            color: #777;
            font-size: 14px;
        }


        .total-quantity {
            color: #777;
            font-size: 13px;
            margin-top: 3px;
        }


        .grand-total {
            color: #b8860b;
            font-size: 25px;
            font-weight: 800;
        }


        /* =========================
           MESSAGE
        ========================= */

        .success-message {
            background: #f0f9f4;
            border: 1px solid #cdebd9;
            color: #26734d;
            padding: 15px 18px;
            border-radius: 10px;
            font-size: 14px;
            margin-top: 20px;
        }


        /* =========================
           BUTTONS
        ========================= */

        .buttons {
            text-align: center;
            margin-top: 30px;
        }


        .buttons a {
            display: inline-block;
            margin: 5px;
            padding: 12px 22px;
            border-radius: 9px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            transition: 0.3s;
        }


        .primary-btn {
            background: #111;
            color: #fff;
        }


        .primary-btn:hover {
            background: #d4a017;
            color: #111;
            transform: translateY(-2px);
        }


        .secondary-btn {
            background: #fff;
            color: #222;
            border: 1px solid #ddd;
        }


        .secondary-btn:hover {
            border-color: #d4a017;
            color: #a87900;
        }


        /* =========================
           FOOTER
        ========================= */

        footer {
            background: #111;
            color: #ddd;
            padding: 55px 6% 25px;
        }


        .footer-grid {
            max-width: 1200px;
            margin: auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.4fr;
            gap: 45px;
        }


        .footer-column h3 {
            color: #fff;
            font-size: 17px;
            margin-bottom: 18px;
        }


        .footer-brand {
            font-size: 23px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 15px;
        }


        .footer-brand span {
            color: #d4a017;
        }


        .footer-column p {
            color: #aaa;
            line-height: 1.8;
            font-size: 14px;
        }


        .footer-links {
            list-style: none;
        }


        .footer-links li {
            margin-bottom: 11px;
        }


        .footer-links a {
            color: #aaa;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
        }


        .footer-links a:hover {
            color: #d4a017;
            padding-left: 4px;
        }


        .contact-item {
            color: #aaa;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 12px;
        }


        .contact-item strong {
            color: #fff;
        }


        .footer-bottom {
            max-width: 1200px;
            margin: 40px auto 0;
            padding-top: 20px;
            border-top: 1px solid #2b2b2b;
            text-align: center;
            color: #888;
            font-size: 13px;
        }


        .footer-bottom span {
            color: #d4a017;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 950px) {

            .footer-grid {
                grid-template-columns: 1fr 1fr;
            }

        }


        @media (max-width: 700px) {

            header {
                padding: 16px 20px;
                flex-direction: column;
            }


            .logo {
                font-size: 21px;
            }


            nav {
                width: 100%;
                justify-content: center;
                gap: 12px;
            }


            nav a {
                font-size: 13px;
            }


            .page-wrapper {
                padding: 40px 15px 55px;
            }


            .page-heading h1 {
                font-size: 27px;
            }


            .status-banner {
                align-items: flex-start;
                flex-direction: column;
            }


            .card {
                padding: 20px;
            }


            .info-grid {
                grid-template-columns: 1fr;
            }


            .info-box.full {
                grid-column: span 1;
            }


            .item {
                align-items: flex-start;
            }


            .item-image {
                width: 70px;
                height: 70px;
            }


            .item-total {
                font-size: 14px;
            }


            .total-section {
                align-items: flex-start;
                flex-direction: column;
            }


            .grand-total {
                font-size: 22px;
            }


            .footer-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }

        }


        @media (max-width: 480px) {

            .item {
                flex-wrap: wrap;
            }


            .item-total {
                width: 100%;
                margin-left: 88px;
            }


            .order-id {
                font-size: 14px;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     HEADER
========================= -->

<header>

    <a href="../index.php" class="logo">
        QAMROSH
    </a>


    <nav>

        <a href="../index.php">
            Home
        </a>

        <a href="../products.php">
            Products
        </a>

        <a href="../about.php">
            About
        </a>

        <a href="../contact.php">
            Contact
        </a>

        <a href="../cart.php" class="cart-link">
            🛒 Cart
        </a>

        <a href="orders.php">
            My Orders
        </a>

        <a href="profile.php">
            Profile
        </a>

        <a
            href="../logout.php"
            class="logout-link"
        >
            Logout
        </a>

    </nav>

</header>


<!-- =========================
     MAIN
========================= -->

<main class="page-wrapper">


    <a
        href="orders.php"
        class="back-link"
    >
        ← Back to My Orders
    </a>


    <div class="page-heading">

        <span class="page-label">
            Order Details
        </span>

        <h1>
            Order #<?php echo e($order_id); ?>
        </h1>

        <p>
            View your order information, items and delivery details.
        </p>

    </div>


    <!-- =========================
         STATUS
    ========================= -->

    <div class="status-banner">

        <div>

            <h2>
                Order Status
            </h2>

            <p>
                Your order is currently
                <?php echo e(ucfirst($order_status)); ?>.
            </p>

        </div>


        <span
            class="status <?php echo e($order_status); ?>"
        >
            <?php echo e($order['status']); ?>
        </span>

    </div>


    <!-- =========================
         ORDER INFORMATION
    ========================= -->

    <div class="card">

        <h2 class="card-title">
            📋 Order Information
        </h2>


        <div class="info-grid">


            <div class="info-box">

                <span class="info-label">
                    Order ID
                </span>

                <div class="info-value order-id">
                    #<?php echo e($order_id); ?>
                </div>

            </div>


            <div class="info-box">

                <span class="info-label">
                    Order Date
                </span>

                <div class="info-value">
                    <?php echo e($order['created_at']); ?>
                </div>

            </div>


            <div class="info-box">

                <span class="info-label">
                    Customer Name
                </span>

                <div class="info-value">
                    <?php echo e($order['customer_name']); ?>
                </div>

            </div>


            <div class="info-box">

                <span class="info-label">
                    Phone
                </span>

                <div class="info-value">
                    <?php echo e($order['phone']); ?>
                </div>

            </div>


            <div class="info-box">

                <span class="info-label">
                    Payment Method
                </span>

                <div class="info-value">
                    <?php echo e($order['payment_method']); ?>
                </div>

            </div>


            <div class="info-box">

                <span class="info-label">
                    Total Quantity
                </span>

                <div class="info-value">
                    <?php echo $total_quantity; ?>
                </div>

            </div>


            <div class="info-box full">

                <span class="info-label">
                    Delivery Address
                </span>

                <div class="info-value">
                    <?php
                    echo nl2br(
                        e($order['shipping_address'])
                    );
                    ?>
                </div>

            </div>


        </div>

    </div>


    <!-- =========================
         ORDER ITEMS
    ========================= -->

    <div class="card">

        <h2 class="card-title">
            🛍️ Ordered Items
        </h2>


        <div class="items-list">


            <?php if ($items_result->num_rows > 0): ?>


                <?php while ($item = $items_result->fetch_assoc()): ?>

                    <?php

                    $quantity = (int) $item['quantity'];

                    $price = (float) $item['price'];

                    $item_total =
                        $price * $quantity;

                    $total_quantity += $quantity;


                    $image = trim(
                        $item['image'] ?? ''
                    );


                    $image_path =
                        $image !== ''
                        ? "../uploads/" . $image
                        : "../assets/images/no-image.png";

                    ?>


                    <div class="item">


                        <img
                            src="<?php echo e($image_path); ?>"
                            alt="<?php echo e($item['product_name'] ?? 'Product'); ?>"
                            class="item-image"
                            loading="lazy"
                        >


                        <div class="item-details">

                            <h3>
                                <?php
                                echo e(
                                    $item['product_name']
                                    ?? 'Product unavailable'
                                );
                                ?>
                            </h3>


                            <p>
                                Price:
                                Rs.
                                <?php
                                echo number_format(
                                    $price,
                                    2
                                );
                                ?>
                            </p>


                            <p>
                                Quantity:
                                <?php echo $quantity; ?>
                            </p>

                        </div>


                        <div class="item-total">

                            Rs.
                            <?php
                            echo number_format(
                                $item_total,
                                2
                            );
                            ?>

                        </div>


                    </div>


                <?php endwhile; ?>


            <?php else: ?>

                <p style="padding: 20px 0; color: #777;">
                    No items were found for this order.
                </p>

            <?php endif; ?>


        </div>


        <!-- TOTAL -->

        <div class="total-section">

            <div>

                <div class="total-label">
                    Order Total
                </div>

                <div class="total-quantity">
                    <?php echo $total_quantity; ?>
                    item(s)
                </div>

            </div>


            <div class="grand-total">

                Rs.
                <?php
                echo number_format(
                    (float) $order['total_amount'],
                    2
                );
                ?>

            </div>

        </div>


        <div class="success-message">

            ✓ Your order details have been saved successfully.
            We will process your order according to its current status.

        </div>


    </div>


    <!-- =========================
         BUTTONS
    ========================= -->

    <div class="buttons">

        <a
            href="../products.php"
            class="primary-btn"
        >
            Continue Shopping
        </a>


        <a
            href="orders.php"
            class="secondary-btn"
        >
            View All Orders
        </a>

    </div>


</main>


<!-- =========================
     FOOTER
========================= -->

<footer>

    <div class="footer-grid">


        <!-- BRAND -->

        <div class="footer-column">

            <div class="footer-brand">
                QAMROSH
            </div>

            <p>
                Discover elegant and stylish ladies'
                garments designed to bring comfort,
                confidence and beauty to every occasion.
            </p>

        </div>


        <!-- QUICK LINKS -->

        <div class="footer-column">

            <h3>
                Quick Links
            </h3>

            <ul class="footer-links">

                <li>
                    <a href="../index.php">
                        Home
                    </a>
                </li>

                <li>
                    <a href="../products.php">
                        Products
                    </a>
                </li>

                <li>
                    <a href="../about.php">
                        About Us
                    </a>
                </li>

                <li>
                    <a href="../contact.php">
                        Contact
                    </a>
                </li>

            </ul>

        </div>


        <!-- CUSTOMER AREA -->

        <div class="footer-column">

            <h3>
                Customer Area
            </h3>

            <ul class="footer-links">

                <li>
                    <a href="../cart.php">
                        Shopping Cart
                    </a>
                </li>

                <li>
                    <a href="orders.php">
                        My Orders
                    </a>
                </li>

                <li>
                    <a href="profile.php">
                        My Profile
                    </a>
                </li>

                <li>
                    <a href="../logout.php">
                        Logout
                    </a>
                </li>

            </ul>

        </div>


        <!-- CONTACT -->

        <div class="footer-column">

            <h3>
                Contact & Support
            </h3>


            <div class="contact-item">

                <strong>
                    Email:
                </strong><br>

                qamrosh@gmail.com

            </div>


            <div class="contact-item">

                <strong>
                    Support:
                </strong><br>

                We're here to help with your orders.

            </div>


            <div class="contact-item">

                <strong>
                    Shopping:
                </strong><br>

                Quality ladies' garments for every style.

            </div>

        </div>


    </div>


    <div class="footer-bottom">

        © <?php echo date("Y"); ?>

        <span>
            QAMROSH
        </span>

        — All Rights Reserved.

    </div>

</footer>


<?php

$item_stmt->close();

?>

</body>

</html>