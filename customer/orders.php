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
   CSRF TOKEN
========================= */

if (
    !isset($_SESSION['csrf_token']) ||
    !is_string($_SESSION['csrf_token']) ||
    $_SESSION['csrf_token'] === ''
) {

    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));
}


$csrf_token =
    $_SESSION['csrf_token'];


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
   STATUS LIST
========================= */

$allowed_statuses = [
    'pending',
    'processing',
    'completed',
    'cancelled'
];


/* =========================
   GET CUSTOMER ORDERS
========================= */

$stmt = $conn->prepare("
    SELECT
        order_id,
        customer_name,
        phone,
        total_amount,
        status,
        shipping_address,
        payment_method,
        created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");


if (!$stmt) {

    error_log(
        "Customer orders prepare failed: " .
        $conn->error
    );

    die("Unable to load your orders.");
}


$stmt->bind_param(
    "i",
    $user_id
);


if (!$stmt->execute()) {

    error_log(
        "Customer orders execute failed: " .
        $stmt->error
    );

    $stmt->close();

    die("Unable to load your orders.");
}


$result = $stmt->get_result();

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
        My Orders - QAMROSH
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
           MAIN
        ========================= */

        .page-wrapper {
            max-width: 1150px;
            margin: auto;
            padding: 55px 20px 70px;
        }


        .page-heading {
            text-align: center;
            margin-bottom: 45px;
        }


        .page-label {
            display: inline-block;
            color: #b8860b;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }


        .page-heading h1 {
            font-size: 36px;
            color: #111;
            margin-bottom: 8px;
        }


        .page-heading p {
            color: #777;
            font-size: 15px;
        }


        /* =========================
           ORDER CARD
        ========================= */

        .order-card {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 18px;
            margin-bottom: 28px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.07);
        }


        .order-top {
            padding: 22px 25px;
            background: #111;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
        }


        .order-number {
            font-size: 18px;
            font-weight: 800;
            word-break: break-word;
        }


        .order-number span {
            color: #d4a017;
        }


        .status {
            padding: 7px 16px;
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
           ORDER BODY
        ========================= */

        .order-body {
            padding: 25px;
        }


        .order-info {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 25px;
        }


        .info-box {
            background: #faf9f6;
            border: 1px solid #eee;
            padding: 15px;
            border-radius: 10px;
        }


        .info-box.full {
            grid-column: span 2;
        }


        .info-label {
            display: block;
            color: #888;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 700;
            margin-bottom: 5px;
        }


        .info-value {
            color: #222;
            font-size: 14px;
            font-weight: 600;
            word-break: break-word;
        }


        /* =========================
           ORDER SUMMARY
        ========================= */

        .order-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding-top: 20px;
            border-top: 2px solid #222;
        }


        .summary-label {
            color: #777;
            font-size: 14px;
        }


        .summary-date {
            color: #888;
            font-size: 13px;
            margin-top: 3px;
        }


        .summary-right {
            text-align: right;
        }


        .grand-total {
            color: #b8860b;
            font-size: 23px;
            font-weight: 800;
            margin-bottom: 10px;
        }


        /* =========================
           ACTION BUTTONS
        ========================= */

        .order-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }


        .view-order-btn {
            display: inline-block;
            padding: 10px 18px;
            background: #111;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            transition: 0.3s;
        }


        .view-order-btn:hover {
            background: #d4a017;
            color: #111;
            transform: translateY(-2px);
        }


        .cancel-order-btn {
            display: inline-block;
            padding: 10px 18px;
            background: #fff;
            color: #b42318;
            border: 1px solid #dc2626;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }


        .cancel-order-btn:hover {
            background: #dc2626;
            color: #fff;
            transform: translateY(-2px);
        }


        .cancel-form {
            display: inline-block;
            margin: 0;
        }


        /* =========================
           EMPTY ORDERS
        ========================= */

        .empty-orders {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 18px;
            padding: 65px 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
        }


        .empty-icon {
            width: 75px;
            height: 75px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #f7f1df;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
        }


        .empty-orders h2 {
            font-size: 25px;
            margin-bottom: 8px;
        }


        .empty-orders p {
            color: #777;
            margin-bottom: 20px;
        }


        .shop-btn {
            display: inline-block;
            padding: 12px 25px;
            background: #111;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            transition: 0.3s;
        }


        .shop-btn:hover {
            background: #d4a017;
            color: #111;
            transform: translateY(-2px);
        }


        /* =========================
           BOTTOM BUTTONS
        ========================= */

        .bottom-buttons {
            text-align: center;
            margin-top: 40px;
        }


        .bottom-buttons a {
            display: inline-block;
            margin: 5px;
            padding: 11px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            transition: 0.3s;
        }


        .continue-shopping {
            background: #111;
            color: #fff;
        }


        .continue-shopping:hover {
            background: #d4a017;
            color: #111;
        }


        .account-btn {
            background: #fff;
            color: #222;
            border: 1px solid #ddd;
        }


        .account-btn:hover {
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

            .order-info {
                grid-template-columns: repeat(2, 1fr);
            }


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
                font-size: 29px;
            }


            .order-top {
                padding: 18px;
            }


            .order-body {
                padding: 18px;
            }


            .order-info {
                grid-template-columns: 1fr;
            }


            .info-box.full {
                grid-column: span 1;
            }


            .order-summary {
                align-items: flex-start;
                flex-direction: column;
            }


            .summary-right {
                text-align: left;
                width: 100%;
            }


            .grand-total {
                font-size: 21px;
            }


            .order-actions {
                justify-content: flex-start;
                width: 100%;
            }


            .cancel-form {
                width: 100%;
            }


            .cancel-order-btn {
                width: 100%;
            }


            .view-order-btn {
                width: 100%;
                text-align: center;
            }


            .footer-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }

        }


        @media (max-width: 480px) {

            .order-number {
                font-size: 15px;
            }


            .status {
                font-size: 11px;
                padding: 6px 12px;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     HEADER
========================= -->

<header>

    <a
        href="../index.php"
        class="logo"
    >
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


        <a
            href="../cart.php"
            class="cart-link"
        >
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


    <div class="page-heading">

        <span class="page-label">
            Customer Area
        </span>


        <h1>
            📦 My Orders
        </h1>


        <p>
            View your orders, payment details and delivery information.
        </p>

    </div>


    <?php if ($result->num_rows > 0): ?>


        <?php while ($order = $result->fetch_assoc()): ?>

            <?php

            $current_order_id =
                (string) $order['order_id'];


            $order_status = strtolower(
                trim(
                    $order['status'] ?? ''
                )
            );


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


            <div class="order-card">


                <!-- =========================
                     ORDER HEADER
                ========================= -->

                <div class="order-top">

                    <div class="order-number">

                        Order

                        <span>

                            #<?php

                            echo e(
                                $current_order_id
                            );

                            ?>

                        </span>

                    </div>


                    <span
                        class="status <?php echo e($order_status); ?>"
                    >

                        <?php

                        echo e(
                            ucfirst(
                                $order_status
                            )
                        );

                        ?>

                    </span>

                </div>


                <!-- =========================
                     ORDER BODY
                ========================= -->

                <div class="order-body">


                    <!-- ORDER INFORMATION -->

                    <div class="order-info">


                        <div class="info-box">

                            <span class="info-label">
                                Order Date
                            </span>


                            <div class="info-value">

                                <?php

                                $order_date =
                                    strtotime(
                                        $order['created_at']
                                        ?? ''
                                    );


                                if (
                                    $order_date !== false
                                ) {

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

                            </div>

                        </div>


                        <div class="info-box">

                            <span class="info-label">
                                Payment
                            </span>


                            <div class="info-value">

                                <?php

                                echo e(
                                    $order[
                                        'payment_method'
                                    ]
                                    ?? 'Not provided'
                                );

                                ?>

                            </div>

                        </div>


                        <div class="info-box">

                            <span class="info-label">
                                Customer
                            </span>


                            <div class="info-value">

                                <?php

                                echo e(
                                    $order[
                                        'customer_name'
                                    ]
                                );

                                ?>

                            </div>

                        </div>


                        <div class="info-box">

                            <span class="info-label">
                                Phone
                            </span>


                            <div class="info-value">

                                <?php

                                echo e(
                                    $order[
                                        'phone'
                                    ]
                                );

                                ?>

                            </div>

                        </div>


                        <div class="info-box full">

                            <span class="info-label">
                                Delivery Address
                            </span>


                            <div class="info-value">

                                <?php

                                echo nl2br(
                                    e(
                                        $order[
                                            'shipping_address'
                                        ]
                                    )
                                );

                                ?>

                            </div>

                        </div>


                    </div>


                    <!-- =========================
                         ORDER SUMMARY
                    ========================= -->

                    <div class="order-summary">


                        <div>

                            <div class="summary-label">
                                Order Total
                            </div>


                            <div class="summary-date">

                                Order ID:

                                <?php

                                echo e(
                                    $current_order_id
                                );

                                ?>

                            </div>

                        </div>


                        <div class="summary-right">


                            <div class="grand-total">

                                Rs.

                                <?php

                                echo number_format(
                                    (float)
                                    $order[
                                        'total_amount'
                                    ],
                                    2
                                );

                                ?>

                            </div>


                            <div class="order-actions">


                                <!-- VIEW ORDER -->

                                <a
                                    href="order_view.php?id=<?php echo urlencode($current_order_id); ?>"
                                    class="view-order-btn"
                                >
                                    View Order Details →
                                </a>


                                <!-- =========================
                                     CANCEL ORDER
                                ========================= -->

                                <?php if ($order_status === 'pending'): ?>

                                    <form
                                        action="../cancel-order.php"
                                        method="POST"
                                        class="cancel-form"
                                        onsubmit="return confirm('Are you sure you want to cancel this order?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="order_id"
                                            value="<?php echo e($current_order_id); ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo e($csrf_token); ?>"
                                        >


                                        <button
                                            type="submit"
                                            class="cancel-order-btn"
                                        >
                                            Cancel Order
                                        </button>

                                    </form>

                                <?php endif; ?>


                            </div>


                        </div>


                    </div>


                </div>

            </div>


        <?php endwhile; ?>


    <?php else: ?>


        <!-- =========================
             EMPTY ORDERS
        ========================= -->

        <div class="empty-orders">

            <div class="empty-icon">
                📦
            </div>


            <h2>
                No Orders Yet
            </h2>


            <p>
                You haven't placed any orders yet.
                Explore our ladies' collection and start shopping.
            </p>


            <a
                href="../products.php"
                class="shop-btn"
            >
                Start Shopping
            </a>

        </div>


    <?php endif; ?>


    <!-- =========================
         BOTTOM BUTTONS
    ========================= -->

    <div class="bottom-buttons">


        <a
            href="../products.php"
            class="continue-shopping"
        >
            ← Continue Shopping
        </a>


        <a
            href="profile.php"
            class="account-btn"
        >
            My Profile
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
                </strong>

                <br>

                qamrosh@gmail.com

            </div>


            <div class="contact-item">

                <strong>
                    Support:
                </strong>

                <br>

                We're here to help with your orders.

            </div>


            <div class="contact-item">

                <strong>
                    Shopping:
                </strong>

                <br>

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

$stmt->close();

?>

</body>

</html>