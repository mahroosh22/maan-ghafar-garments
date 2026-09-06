<?php

session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$grand_total = 0;

/*
|--------------------------------------------------------------------------
| Message Notification
|--------------------------------------------------------------------------
*/

$unread_replies = 0;

if (isset($_SESSION['user_id'])) {

    $user_id = (int) $_SESSION['user_id'];
    $user_email = "";

    $stmt = $conn->prepare("
        SELECT email
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $user_id
        );

        $stmt->execute();

        $result_user = $stmt->get_result();

        if ($result_user->num_rows === 1) {

            $user = $result_user->fetch_assoc();

            $user_email = trim(
                $user['email'] ?? ''
            );
        }

        $stmt->close();
    }

    if ($user_email !== "") {

        $stmt = $conn->prepare("
            SELECT COUNT(*) AS unread_replies
            FROM contact_messages
            WHERE LOWER(TRIM(email)) = LOWER(TRIM(?))
            AND admin_reply IS NOT NULL
            AND TRIM(admin_reply) <> ''
            AND reply_seen = 0
        ");

        if ($stmt) {

            $stmt->bind_param(
                "s",
                $user_email
            );

            $stmt->execute();

            $result_notification =
                $stmt->get_result();

            if (
                $result_notification->num_rows === 1
            ) {

                $row =
                    $result_notification->fetch_assoc();

                $unread_replies =
                    (int) $row['unread_replies'];
            }

            $stmt->close();
        }
    }
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
    Shopping Cart - Maan Ghafar Garments
</title>

<link
    rel="stylesheet"
    href="assets/css/style.css"
>

<style>

.cart-page {
    background: #f8f8f8;
    min-height: 600px;
    padding: 55px 20px 70px;
}

.cart-header {
    max-width: 1200px;
    margin: 0 auto 35px;
    text-align: center;
}

.cart-header h1 {
    margin: 0 0 10px;
    font-size: 36px;
    color: #111;
}

.cart-header p {
    margin: 0;
    color: #777;
}

.cart-container {
    max-width: 1200px;
    margin: auto;
}

.cart-item {
    display: grid;
    grid-template-columns: 130px 1fr auto;
    gap: 25px;
    align-items: center;
    background: #fff;
    padding: 20px;
    margin-bottom: 18px;
    border-radius: 14px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.07);
}

.cart-item-image {
    width: 130px;
    height: 145px;
    background: #f5f5f5;
    border-radius: 10px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cart-item-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
    background: #f5f5f5;
}

.cart-item-info h3 {
    margin: 0 0 10px;
    font-size: 21px;
    color: #111;
}

.cart-price {
    color: #777;
    margin: 5px 0;
}

.cart-price strong {
    color: #b28a13;
}

.quantity-title {
    font-size: 14px;
    color: #777;
    margin: 12px 0 7px;
}

.quantity-controls {
    display: inline-flex;
    align-items: center;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
}

.quantity-controls a {
    width: 38px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    background: #111;
    color: #fff;
    font-size: 20px;
    transition: 0.3s;
}

.quantity-controls a:hover {
    background: #d4af37;
    color: #111;
}

.quantity-number {
    width: 42px;
    text-align: center;
    font-weight: bold;
}

.cart-item-total {
    text-align: right;
    min-width: 150px;
}

.cart-item-total small {
    display: block;
    color: #888;
    margin-bottom: 6px;
}

.cart-item-total strong {
    display: block;
    color: #b28a13;
    font-size: 21px;
    margin-bottom: 12px;
}

.remove-btn {
    display: inline-block;
    padding: 8px 14px;
    background: #f4e7e5;
    color: #b52b20;
    text-decoration: none;
    border-radius: 7px;
    font-size: 13px;
    transition: 0.3s;
}

.remove-btn:hover {
    background: #b52b20;
    color: #fff;
}

.cart-summary {
    max-width: 500px;
    margin: 30px 0 0 auto;
    background: #fff;
    padding: 25px;
    border-radius: 14px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.07);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 18px;
    border-bottom: 1px solid #eee;
}

.summary-row span {
    color: #555;
}

.summary-row strong {
    color: #111;
    font-size: 24px;
}

.checkout-btn {
    display: block;
    width: 100%;
    margin-top: 18px;
    padding: 14px;
    background: #111;
    color: #fff;
    text-align: center;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
    transition: 0.3s;
    box-sizing: border-box;
}

.checkout-btn:hover {
    background: #d4af37;
    color: #111;
}

.continue-btn {
    display: inline-block;
    margin-top: 15px;
    padding: 12px 20px;
    background: #eee;
    color: #222;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: 0.3s;
}

.continue-btn:hover {
    background: #ddd;
}

.cart-actions {
    text-align: center;
    margin-top: 25px;
}

.empty-cart {
    max-width: 650px;
    margin: 20px auto;
    padding: 55px 30px;
    background: #fff;
    border-radius: 16px;
    text-align: center;
    box-shadow: 0 5px 20px rgba(0,0,0,0.07);
}

.empty-icon {
    font-size: 55px;
    margin-bottom: 15px;
}

.empty-cart h2 {
    margin: 0 0 10px;
    color: #111;
}

.empty-cart p {
    color: #777;
    margin-bottom: 25px;
}

.browse-btn {
    display: inline-block;
    padding: 13px 25px;
    background: #111;
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
    transition: 0.3s;
}

.browse-btn:hover {
    background: #d4af37;
    color: #111;
}

/* MESSAGE BADGE */

.message-nav-link {
    position: relative;
    display: inline-block;
}

.message-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    margin-left: 4px;
    background: #d4af37;
    color: #111;
    border-radius: 50px;
    font-size: 11px;
    font-weight: bold;
    vertical-align: middle;
}

/* FOOTER */

.modern-footer {
    background: #111;
    color: #fff;
    padding: 55px 30px 20px;
}

.footer-container {
    max-width: 1200px;
    margin: auto;
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1.2fr;
    gap: 45px;
}

.footer-column h2 {
    color: #fff;
    margin-top: 0;
}

.footer-column h3 {
    margin-bottom: 18px;
    font-size: 19px;
    color: #d4af37;
}

.footer-column p {
    color: #bbb;
    line-height: 1.7;
    font-size: 14px;
    margin: 10px 0;
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 10px;
}

.footer-links a,
.footer-contact a {
    color: #bbb;
    text-decoration: none;
    font-size: 14px;
    transition: 0.3s;
}

.footer-links a:hover,
.footer-contact a:hover {
    color: #d4af37;
}

.footer-bottom {
    max-width: 1200px;
    margin: 40px auto 0;
    padding-top: 20px;
    border-top: 1px solid #333;
    text-align: center;
    color: #888;
    font-size: 13px;
}

/* MOBILE */

@media (max-width: 768px) {

    .cart-page {
        padding: 35px 12px 50px;
    }

    .cart-header h1 {
        font-size: 29px;
    }

    .cart-item {
        grid-template-columns: 95px 1fr;
        gap: 15px;
        padding: 15px;
    }

    .cart-item-image {
        width: 95px;
        height: 115px;
    }

    .cart-item-info h3 {
        font-size: 17px;
    }

    .cart-item-total {
        grid-column: 1 / -1;
        text-align: left;
        border-top: 1px solid #eee;
        padding-top: 15px;
    }

    .cart-item-total strong {
        display: inline-block;
        margin-right: 12px;
    }

    .cart-summary {
        max-width: 100%;
    }

    .modern-footer {
        padding: 45px 25px 20px;
    }

    .footer-container {
        grid-template-columns: 1fr;
        gap: 30px;
    }
}

</style>

</head>

<body>

<!-- =========================
     HEADER
========================= -->

<header class="header">

    <div class="logo">
        Maan Ghafar Garments
    </div>

    <nav class="navbar">

        <a href="index.php">
            Home
        </a>

        <a href="products.php">
            Products
        </a>

        <a href="about.php">
            About
        </a>

        <a href="contact.php">
            Contact
        </a>

        <a href="cart.php">
            Cart
        </a>

        <a href="customer/orders.php">
            My Orders
        </a>

        <?php if (isset($_SESSION['user_id'])): ?>

            <a
                href="contact.php"
                class="message-nav-link"
            >

                💬 Messages

                <?php if ($unread_replies > 0): ?>

                    <span class="message-badge">
                        <?php echo $unread_replies; ?>
                    </span>

                <?php endif; ?>

            </a>

            <span>

                Hi,

                <?php
                echo e(
                    $_SESSION['user_name']
                    ?? 'Customer'
                );
                ?>

            </span>

            <a href="customer/profile.php">
                Profile
            </a>

            <a href="logout.php">
                Logout
            </a>

        <?php else: ?>

            <a href="login.php">
                Login
            </a>

            <a href="register.php">
                Register
            </a>

        <?php endif; ?>

    </nav>

</header>

<!-- =========================
     CART
========================= -->

<section class="cart-page">

    <div class="cart-header">

        <h1>
            🛒 Shopping Cart
        </h1>

        <p>
            Review your selected ladies collection
            before checkout.
        </p>

    </div>

    <div class="cart-container">

        <?php if (empty($_SESSION['cart'])): ?>

            <div class="empty-cart">

                <div class="empty-icon">
                    🛍️
                </div>

                <h2>
                    Your Cart is Empty
                </h2>

                <p>
                    You haven't added any products yet.
                </p>

                <a
                    href="products.php"
                    class="browse-btn"
                >
                    Browse Products
                </a>

            </div>

        <?php else: ?>

            <?php foreach (
                $_SESSION['cart']
                as $cart_product_id => $cart_quantity
            ): ?>

                <?php

                $cart_product_id =
                    filter_var(
                        $cart_product_id,
                        FILTER_VALIDATE_INT
                    );

                $quantity =
                    filter_var(
                        $cart_quantity,
                        FILTER_VALIDATE_INT
                    );

                if (
                    !$cart_product_id ||
                    $cart_product_id <= 0
                ) {

                    unset(
                        $_SESSION['cart'][
                            $cart_product_id
                        ]
                    );

                    continue;
                }

                if (!$quantity || $quantity < 1) {
                    $quantity = 1;
                }

                /*
                |----------------------------------------------------------
                | Get Latest Product Data
                |----------------------------------------------------------
                */

                $stmt = $conn->prepare("
                    SELECT
                        product_id,
                        product_name,
                        description,
                        price,
                        stock_quantity,
                        size,
                        image
                    FROM products
                    WHERE product_id = ?
                    LIMIT 1
                ");

                if (!$stmt) {
                    continue;
                }

                $stmt->bind_param(
                    "i",
                    $cart_product_id
                );

                if (!$stmt->execute()) {
                    $stmt->close();
                    continue;
                }

                $result =
                    $stmt->get_result();

                $product =
                    $result->fetch_assoc();

                $stmt->close();

                /*
                |----------------------------------------------------------
                | Product No Longer Exists
                |----------------------------------------------------------
                */

                if (!$product) {

                    unset(
                        $_SESSION['cart'][
                            $cart_product_id
                        ]
                    );

                    continue;
                }

                $stock =
                    (int) $product['stock_quantity'];

                /*
                |----------------------------------------------------------
                | Adjust Cart Quantity To Current Stock
                |----------------------------------------------------------
                */

                if ($stock <= 0) {

                    unset(
                        $_SESSION['cart'][
                            $cart_product_id
                        ]
                    );

                    continue;
                }

                if ($quantity > $stock) {

                    $quantity = $stock;

                    $_SESSION['cart'][
                        $cart_product_id
                    ] = $stock;
                }

                /*
                |----------------------------------------------------------
                | Calculate Total
                |----------------------------------------------------------
                */

                $price =
                    (float) $product['price'];

                $item_total =
                    $price * $quantity;

                $grand_total += $item_total;

                /*
                |----------------------------------------------------------
                | Product Image
                |----------------------------------------------------------
                */

                $product_image =
                    trim(
                        $product['image'] ?? ''
                    );

                if ($product_image !== '') {

                    /*
                    |------------------------------------------------------
                    | IMPORTANT:
                    | Product images are stored in:
                    | uploads/products/
                    |------------------------------------------------------
                    */

                    $image_path =
                        "uploads/products/" .
                        basename($product_image);

                } else {

                    $image_path =
                        "assets/images/no-image.png";
                }

                ?>

                <div class="cart-item">

                    <!-- IMAGE -->

                    <div class="cart-item-image">

                        <img
                            src="<?php echo e($image_path); ?>"
                            alt="<?php
                            echo e(
                                $product['product_name']
                            );
                            ?>"
                            onerror="this.onerror=null;this.src='assets/images/no-image.png';"
                        >

                    </div>

                    <!-- PRODUCT INFO -->

                    <div class="cart-item-info">

                        <h3>

                            <?php
                            echo e(
                                $product['product_name']
                            );
                            ?>

                        </h3>

                        <p class="cart-price">

                            Price:

                            <strong>

                                Rs.

                                <?php
                                echo number_format(
                                    $price
                                );
                                ?>

                            </strong>

                        </p>

                        <?php if (
                            !empty($product['size'])
                        ): ?>

                            <p class="cart-price">

                                Size:

                                <strong>

                                    <?php
                                    echo e(
                                        $product['size']
                                    );
                                    ?>

                                </strong>

                            </p>

                        <?php endif; ?>

                        <p class="quantity-title">
                            Quantity
                        </p>

                        <div class="quantity-controls">

                            <a
                                href="update-cart.php?id=<?php echo (int) $cart_product_id; ?>&action=decrease"
                                aria-label="Decrease quantity"
                            >
                                −
                            </a>

                            <span class="quantity-number">
                                <?php echo $quantity; ?>
                            </span>

                            <?php if ($quantity < $stock): ?>

                                <a
                                    href="update-cart.php?id=<?php echo (int) $cart_product_id; ?>&action=increase"
                                    aria-label="Increase quantity"
                                >
                                    +
                                </a>

                            <?php else: ?>

                                <span
                                    style="
                                        width:38px;
                                        height:36px;
                                        display:flex;
                                        align-items:center;
                                        justify-content:center;
                                        background:#ddd;
                                        color:#888;
                                        font-size:20px;
                                    "
                                    title="Maximum available stock reached"
                                >
                                    +
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                    <!-- TOTAL -->

                    <div class="cart-item-total">

                        <small>
                            Item Total
                        </small>

                        <strong>

                            Rs.

                            <?php
                            echo number_format(
                                $item_total
                            );
                            ?>

                        </strong>

                        <a
                            href="remove-from-cart.php?id=<?php echo (int) $cart_product_id; ?>"
                            class="remove-btn"
                        >
                            Remove
                        </a>

                    </div>

                </div>

            <?php endforeach; ?>

            <!-- SUMMARY -->

            <?php if ($grand_total > 0): ?>

                <div class="cart-summary">

                    <div class="summary-row">

                        <span>
                            Grand Total
                        </span>

                        <strong>

                            Rs.

                            <?php
                            echo number_format(
                                $grand_total
                            );
                            ?>

                        </strong>

                    </div>

                    <?php if (
                        isset($_SESSION['user_id'])
                    ): ?>

                        <a
                            href="customer/checkout.php"
                            class="checkout-btn"
                        >
                            Proceed to Checkout →
                        </a>

                    <?php else: ?>

                        <a
                            href="login.php"
                            class="checkout-btn"
                        >
                            Login to Checkout →
                        </a>

                    <?php endif; ?>

                </div>

            <?php else: ?>

                <div class="empty-cart">

                    <div class="empty-icon">
                        🛍️
                    </div>

                    <h2>
                        Your Cart is Empty
                    </h2>

                    <p>
                        The selected products are no longer
                        available.
                    </p>

                    <a
                        href="products.php"
                        class="browse-btn"
                    >
                        Browse Products
                    </a>

                </div>

            <?php endif; ?>

        <?php endif; ?>

        <div class="cart-actions">

            <a
                href="products.php"
                class="continue-btn"
            >
                ← Continue Shopping
            </a>

        </div>

    </div>

</section>

<!-- =========================
     FOOTER
========================= -->

<footer class="modern-footer">

    <div class="footer-container">

        <!-- BRAND -->

        <div class="footer-column footer-brand">

            <h2>
                Maan Ghafar Garments
            </h2>

            <p>
                Your online destination for
                stylish and elegant ladies garments.
                Discover beautiful designs and
                shop your favorite outfits with ease.
            </p>

        </div>

        <!-- QUICK LINKS -->

        <div class="footer-column">

            <h3>
                Quick Links
            </h3>

            <ul class="footer-links">

                <li>
                    <a href="index.php">
                        Home
                    </a>
                </li>

                <li>
                    <a href="products.php">
                        Products
                    </a>
                </li>

                <li>
                    <a href="about.php">
                        About Us
                    </a>
                </li>

                <li>
                    <a href="contact.php">
                        Contact Us
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
                    <a href="cart.php">
                        Shopping Cart
                    </a>
                </li>

                <li>
                    <a href="customer/orders.php">
                        My Orders
                    </a>
                </li>

                <?php if (
                    isset($_SESSION['user_id'])
                ): ?>

                    <li>
                        <a href="customer/profile.php">
                            My Profile
                        </a>
                    </li>

                    <li>
                        <a href="contact.php">
                            Messages
                        </a>
                    </li>

                    <li>
                        <a href="logout.php">
                            Logout
                        </a>
                    </li>

                <?php else: ?>

                    <li>
                        <a href="login.php">
                            Customer Login
                        </a>
                    </li>

                    <li>
                        <a href="register.php">
                            Create Account
                        </a>
                    </li>

                <?php endif; ?>

            </ul>

        </div>

        <!-- CONTACT -->

        <div class="footer-column footer-contact">

            <h3>
                Contact
            </h3>

            <p>

                <strong>
                    📧 Email:
                </strong>

                <br>

                Visit our

                <a
                    href="contact.php"
                    style="
                        color:#d4af37;
                        text-decoration:none;
                        display:inline;
                    "
                >
                    Contact page
                </a>

            </p>

            <p>

                <strong>
                    💬 Support:
                </strong>

                <br>

                We're here to help with
                your orders and questions.

            </p>

            <p>

                <strong>
                    🛍️ Shopping:
                </strong>

                <br>

                Browse our latest collection
                anytime.

            </p>

        </div>

    </div>

    <div class="footer-bottom">

        © <?php echo date("Y"); ?>

        Maan Ghafar Garments.

        All Rights Reserved.

    </div>

</footer>

</body>

</html>