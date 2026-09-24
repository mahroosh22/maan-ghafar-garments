<?php

session_start();

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'customer'
) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";


/* =========================
   CUSTOMER ID
========================= */

$user_id = filter_var(
    $_SESSION['user_id'],
    FILTER_VALIDATE_INT
);

if (!$user_id || $user_id <= 0) {

    session_unset();
    session_destroy();

    header("Location: ../login.php");
    exit;
}


/* =========================
   ORDER ID
========================= */

$order_id = trim($_GET['id'] ?? '');

$error = "";
$order = null;
$items = [];


/* =========================
   SAFE OUTPUT
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
   CHECK ORDER ID
========================= */

if ($order_id === '') {

    $error = "Invalid order.";

}


/* =========================
   GET ORDER
========================= */

if ($error === '') {

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
        LEFT JOIN users u
            ON o.user_id = u.id
        WHERE o.order_id = ?
        AND o.user_id = ?
        LIMIT 1
    ");

    if (!$stmt) {

        error_log(
            "Order view prepare failed: " .
            $conn->error
        );

        $error = "Unable to load order.";

    } else {

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

            $error = "Unable to load order.";

        } else {

            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                $order = $result->fetch_assoc();

            } else {

                $error =
                    "Order not found or you do not have permission to view it.";

            }
        }

        $stmt->close();
    }
}


/* =========================
   GET ORDER ITEMS
========================= */

if ($order !== null && $error === '') {

    $stmt = $conn->prepare("
        SELECT
            oi.order_item_id,
            oi.order_id,
            oi.product_id,
            oi.quantity,
            oi.price,
            p.product_name,
            p.description,
            p.size,
            p.image
        FROM order_items oi
        LEFT JOIN products p
            ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
        ORDER BY oi.order_item_id ASC
    ");

    if (!$stmt) {

        error_log(
            "Order items prepare failed: " .
            $conn->error
        );

        $error = "Unable to load order products.";

    } else {

        $stmt->bind_param(
            "s",
            $order_id
        );

        if (!$stmt->execute()) {

            error_log(
                "Order items execute failed: " .
                $stmt->error
            );

            $error = "Unable to load order products.";

        } else {

            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {

                $items[] = $row;
            }
        }

        $stmt->close();
    }
}


/* =========================
   TOTAL QUANTITY
========================= */

$total_quantity = 0;

foreach ($items as $item) {

    $total_quantity += max(
        0,
        (int) ($item['quantity'] ?? 0)
    );
}

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
        Order Details - QAMROSH
    </title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f6f7f9;
            color: #222;
            line-height: 1.5;
        }


        /* =========================
           HEADER
        ========================= */

        .header {
            background: #111827;
            color: #fff;
            padding: 17px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.12);
        }


        .logo {
            color: #d4af37;
            text-decoration: none;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 1px;
            white-space: nowrap;
        }


        .nav {
            display: flex;
            align-items: center;
            gap: 7px;
            flex-wrap: wrap;
        }


        .nav a {
            color: #fff;
            text-decoration: none;
            padding: 9px 13px;
            border-radius: 7px;
            font-size: 14px;
            transition: 0.3s;
        }


        .nav a:hover,
        .nav a.active {
            background: #b8860b;
        }


        .logout-btn {
            background: #dc2626;
        }


        .logout-btn:hover {
            background: #b91c1c !important;
        }


        /* =========================
           MAIN
        ========================= */

        .main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 42px 20px 65px;
        }


        .back-link {
            display: inline-block;
            color: #b8860b;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }


        .back-link:hover {
            text-decoration: underline;
        }


        .page-title {
            margin-bottom: 25px;
        }


        .page-title h1 {
            color: #111827;
            font-size: 31px;
            margin-bottom: 6px;
        }


        .page-title p {
            color: #6b7280;
            font-size: 14px;
        }


        /* =========================
           ERROR
        ========================= */

        .error-box {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 18px;
            border-radius: 10px;
            margin-bottom: 20px;
        }


        .error-back {
            display: inline-block;
            margin-top: 10px;
            color: #991b1b;
            font-weight: 700;
            text-decoration: none;
        }


        /* =========================
           CARDS
        ========================= */

        .summary-card,
        .info-card,
        .products-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.06);
        }


        .summary-card {
            padding: 25px;
            margin-bottom: 25px;
        }


        /* =========================
           ORDER SUMMARY
        ========================= */

        .summary-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ececec;
        }


        .summary-header h2 {
            color: #111827;
            font-size: 21px;
        }


        .order-number {
            color: #b8860b;
        }


        /* =========================
           STATUS
        ========================= */

        .status {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 30px;
            font-size: 13px;
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


        /* =========================
           SUMMARY GRID
        ========================= */

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            padding-top: 23px;
        }


        .summary-item span {
            display: block;
            color: #9ca3af;
            font-size: 12px;
            margin-bottom: 6px;
        }


        .summary-item strong {
            color: #374151;
            font-size: 14px;
        }


        .total-amount {
            color: #b8860b !important;
            font-size: 18px !important;
        }


        /* =========================
           CUSTOMER INFO
        ========================= */

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }


        .info-card {
            padding: 23px;
        }


        .info-card h3 {
            color: #111827;
            font-size: 18px;
            margin-bottom: 14px;
        }


        .info-card p {
            color: #4b5563;
            font-size: 14px;
            line-height: 1.8;
        }


        .info-card strong {
            color: #374151;
        }


        /* =========================
           PRODUCTS
        ========================= */

        .products-card {
            padding: 25px;
        }


        .products-card h2 {
            color: #111827;
            font-size: 21px;
            margin-bottom: 20px;
        }


        .product-item {
            display: grid;
            grid-template-columns: 90px 1fr auto;
            align-items: center;
            gap: 18px;
            padding: 18px 0;
            border-bottom: 1px solid #eeeeee;
        }


        .product-item:first-of-type {
            padding-top: 5px;
        }


        .product-item:last-child {
            border-bottom: none;
            padding-bottom: 5px;
        }


        .product-image {
            width: 90px;
            height: 90px;
            border-radius: 10px;
            overflow: hidden;
            background: #f3f4f6;
            display: flex;
            justify-content: center;
            align-items: center;
        }


        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }


        .no-image {
            color: #9ca3af;
            font-size: 12px;
            text-align: center;
            padding: 5px;
        }


        .product-info h3 {
            color: #111827;
            font-size: 16px;
            margin-bottom: 7px;
        }


        .product-info p {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 4px;
        }


        .product-info strong {
            color: #374151;
        }


        .product-price {
            text-align: right;
        }


        .product-price .price {
            color: #b8860b;
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 5px;
        }


        .product-price .quantity {
            color: #6b7280;
            font-size: 13px;
        }


        .no-items {
            text-align: center;
            padding: 35px 20px;
            color: #6b7280;
        }


        /* =========================
           FOOTER
        ========================= */

        .footer {
            background: #111827;
            color: #d1d5db;
            padding: 45px 7% 20px;
            margin-top: 15px;
        }


        .footer-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1fr;
            gap: 35px;
        }


        .footer h3 {
            color: #d4af37;
            margin-bottom: 15px;
            font-size: 18px;
        }


        .footer p {
            color: #d1d5db;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 8px;
        }


        .footer-links {
            list-style: none;
        }


        .footer-links li {
            margin-bottom: 9px;
        }


        .footer-links a {
            color: #d1d5db;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
        }


        .footer-links a:hover {
            color: #d4af37;
            padding-left: 4px;
        }


        .footer-bottom {
            max-width: 1200px;
            margin: 35px auto 0;
            padding-top: 18px;
            border-top: 1px solid #374151;
            text-align: center;
            color: #9ca3af;
            font-size: 13px;
        }


        /* =========================
           TABLET
        ========================= */

        @media (max-width: 900px) {

            .header {
                padding: 17px 20px;
            }


            .summary-grid {
                grid-template-columns: 1fr 1fr;
            }


            .footer-grid {
                grid-template-columns: 1fr 1fr;
            }

        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 650px) {

            .header {
                flex-direction: column;
                align-items: flex-start;
            }


            .nav {
                width: 100%;
                overflow-x: auto;
                flex-wrap: nowrap;
                padding-bottom: 3px;
            }


            .nav a {
                white-space: nowrap;
            }


            .main {
                padding: 30px 15px 45px;
            }


            .page-title h1 {
                font-size: 26px;
            }


            .summary-card,
            .products-card,
            .info-card {
                padding: 18px;
            }


            .summary-header {
                flex-direction: column;
                align-items: flex-start;
            }


            .summary-grid {
                grid-template-columns: 1fr;
            }


            .info-grid {
                grid-template-columns: 1fr;
            }


            .product-item {
                grid-template-columns: 70px 1fr;
                gap: 13px;
            }


            .product-image {
                width: 70px;
                height: 70px;
            }


            .product-price {
                grid-column: 2;
                text-align: left;
            }


            .footer {
                padding: 35px 20px 18px;
            }


            .footer-grid {
                grid-template-columns: 1fr;
                gap: 25px;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     HEADER
========================= -->

<header class="header">

    <a
        href="../index.php"
        class="logo"
    >
        QAMROSH
    </a>


    <nav class="nav">

        <a href="../index.php">
            Home
        </a>


        <a href="../products.php">
            Products
        </a>


        <a
            href="orders.php"
            class="active"
        >
            My Orders
        </a>


        <a href="profile.php">
            Profile
        </a>


        <a href="../cart.php">
            Cart
        </a>


        <a
            href="../logout.php"
            class="logout-btn"
        >
            Logout
        </a>

    </nav>

</header>


<!-- =========================
     MAIN
========================= -->

<main class="main">


    <a
        href="orders.php"
        class="back-link"
    >
        ← Back to My Orders
    </a>


    <?php if ($error !== ""): ?>


        <div class="error-box">

            <?php
            echo e($error);
            ?>

            <br>

            <a
                href="orders.php"
                class="error-back"
            >
                Go Back to Orders
            </a>

        </div>


    <?php else: ?>


        <?php

        $status = strtolower(
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
                $status,
                $allowed_statuses,
                true
            )
        ) {
            $status = 'pending';
        }


        $status_class =
            'status-' . $status;

        ?>


        <div class="page-title">

            <h1>
                Order Details
            </h1>

            <p>
                Complete information about your order.
            </p>

        </div>


        <!-- =========================
             ORDER SUMMARY
        ========================= -->

        <div class="summary-card">

            <div class="summary-header">

                <h2>

                    Order

                    <span class="order-number">

                        #

                        <?php
                        echo e(
                            $order['order_id']
                        );
                        ?>

                    </span>

                </h2>


                <span
                    class="status <?php echo e($status_class); ?>"
                >

                    <?php
                    echo e(
                        ucfirst($status)
                    );
                    ?>

                </span>

            </div>


            <div class="summary-grid">


                <div class="summary-item">

                    <span>
                        Order Date
                    </span>

                    <strong>

                        <?php

                        $order_date = strtotime(
                            $order['created_at'] ?? ''
                        );

                        if ($order_date !== false) {

                            echo e(
                                date(
                                    "d M Y, h:i A",
                                    $order_date
                                )
                            );

                        } else {

                            echo "Not available";

                        }

                        ?>

                    </strong>

                </div>


                <div class="summary-item">

                    <span>
                        Payment Method
                    </span>

                    <strong>

                        <?php
                        echo e(
                            $order['payment_method']
                            ?? 'Not provided'
                        );
                        ?>

                    </strong>

                </div>


                <div class="summary-item">

                    <span>
                        Total Items
                    </span>

                    <strong>

                        <?php
                        echo (int) $total_quantity;
                        ?>

                    </strong>

                </div>


                <div class="summary-item">

                    <span>
                        Total Amount
                    </span>

                    <strong class="total-amount">

                        Rs.

                        <?php
                        echo number_format(
                            (float)
                            $order['total_amount'],
                            2
                        );
                        ?>

                    </strong>

                </div>


            </div>

        </div>


        <!-- =========================
             CUSTOMER + SHIPPING
        ========================= -->

        <div class="info-grid">


            <div class="info-card">

                <h3>
                    Customer Information
                </h3>


                <p>

                    <strong>
                        Name:
                    </strong>

                    <?php
                    echo e(
                        $order['customer_name']
                        ?? 'Not provided'
                    );
                    ?>

                    <br>


                    <strong>
                        Email:
                    </strong>

                    <?php
                    echo e(
                        $order['customer_email']
                        ?? 'Not provided'
                    );
                    ?>

                    <br>


                    <strong>
                        Phone:
                    </strong>

                    <?php
                    echo e(
                        $order['customer_phone']
                        ?? 'Not provided'
                    );
                    ?>

                </p>

            </div>


            <div class="info-card">

                <h3>
                    Shipping Address
                </h3>


                <p>

                    <?php

                    if (
                        !empty(
                            $order['shipping_address']
                        )
                    ) {

                        echo nl2br(
                            e(
                                $order[
                                    'shipping_address'
                                ]
                            )
                        );

                    } else {

                        echo "Address not provided.";

                    }

                    ?>

                </p>

            </div>


        </div>


        <!-- =========================
             ORDERED PRODUCTS
        ========================= -->

        <div class="products-card">

            <h2>
                Ordered Products
            </h2>


            <?php if (count($items) > 0): ?>


                <?php foreach ($items as $item): ?>

                    <?php

                    $product_name =
                        trim(
                            $item['product_name']
                            ?? ''
                        );


                    if ($product_name === '') {

                        $product_name =
                            'Product unavailable';

                    }


                    $quantity = max(
                        0,
                        (int) (
                            $item['quantity']
                            ?? 0
                        )
                    );


                    $price = max(
                        0,
                        (float) (
                            $item['price']
                            ?? 0
                        )
                    );


                    $subtotal =
                        $price * $quantity;


                    $image =
                        trim(
                            $item['image']
                            ?? ''
                        );


                    /*
                     * Product images are stored
                     * inside uploads/products/
                     */

                    $image_path = "";

                    if ($image !== "") {

                        $image_path =
                            "../uploads/products/" .
                            basename($image);
                    }

                    ?>


                    <div class="product-item">


                        <!-- PRODUCT IMAGE -->

                        <div class="product-image">


                            <?php if ($image_path !== ""): ?>


                                <img
                                    src="<?php echo e($image_path); ?>"
                                    alt="<?php echo e($product_name); ?>"
                                    loading="lazy"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                >


                                <div
                                    class="no-image"
                                    style="display:none;"
                                >
                                    No Image
                                </div>


                            <?php else: ?>


                                <div class="no-image">
                                    No Image
                                </div>


                            <?php endif; ?>


                        </div>


                        <!-- PRODUCT INFO -->

                        <div class="product-info">


                            <h3>

                                <?php
                                echo e(
                                    $product_name
                                );
                                ?>

                            </h3>


                            <?php
                            if (
                                !empty(
                                    $item['size']
                                )
                            ):
                            ?>

                                <p>

                                    <strong>
                                        Size:
                                    </strong>

                                    <?php
                                    echo e(
                                        $item['size']
                                    );
                                    ?>

                                </p>

                            <?php endif; ?>


                            <p>

                                <strong>
                                    Price:
                                </strong>

                                Rs.

                                <?php
                                echo number_format(
                                    $price,
                                    2
                                );
                                ?>

                            </p>


                        </div>


                        <!-- PRICE -->

                        <div class="product-price">


                            <div class="price">

                                Rs.

                                <?php
                                echo number_format(
                                    $subtotal,
                                    2
                                );
                                ?>

                            </div>


                            <div class="quantity">

                                Quantity:

                                <?php
                                echo (int) $quantity;
                                ?>

                            </div>


                        </div>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="no-items">

                    No products were found for this order.

                </div>


            <?php endif; ?>


        </div>


    <?php endif; ?>


</main>


<!-- =========================
     FOOTER
========================= -->

<footer class="footer">

    <div class="footer-grid">


        <!-- BRAND -->

        <div>

            <h3>
                QAMROSH
            </h3>


            <p>
                Discover elegant and stylish ladies
                garments designed with style, comfort
                and elegance. Shop your favorite
                outfits with ease.
            </p>

        </div>


        <!-- QUICK LINKS -->

        <div>

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

        <div>

            <h3>
                Customer Area
            </h3>


            <ul class="footer-links">

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
                    <a href="../cart.php">
                        Shopping Cart
                    </a>
                </li>


                <li>
                    <a href="../products.php">
                        Shop Now
                    </a>
                </li>

            </ul>

        </div>


        <!-- CONTACT -->

        <div>

            <h3>
                Contact & Support
            </h3>


            <p>
                📧 qamrosh@gmail.com
            </p>


            <p>
                📞 Customer Support
            </p>


            <p>
                🕐 Mon - Sat: 10 AM - 8 PM
            </p>

        </div>


    </div>


    <div class="footer-bottom">

        © <?php echo date("Y"); ?>

        QAMROSH.

        All Rights Reserved.

    </div>

</footer>


</body>

</html>