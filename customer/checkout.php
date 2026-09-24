<?php

session_start();

require_once "../config/database.php";


/* =========================================================
   CUSTOMER LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'customer'
) {
    header("Location: ../login.php");
    exit;
}


/* =========================================================
   CART CHECK
========================================================= */

if (
    empty($_SESSION['cart']) ||
    !is_array($_SESSION['cart'])
) {
    header("Location: ../cart.php");
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


/* =========================================================
   CSRF TOKEN
========================================================= */

if (
    !isset($_SESSION['csrf_token']) ||
    !is_string($_SESSION['csrf_token']) ||
    $_SESSION['csrf_token'] === ''
) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


/* =========================================================
   GET CUSTOMER INFORMATION
========================================================= */

$stmt = $conn->prepare("
    SELECT
        name,
        email,
        phone
    FROM users
    WHERE id = ?
    AND role = 'customer'
    LIMIT 1
");


if (!$stmt) {

    error_log(
        "Checkout customer prepare failed: " .
        $conn->error
    );

    die("Unable to load customer information.");
}


$stmt->bind_param("i", $user_id);


if (!$stmt->execute()) {

    error_log(
        "Checkout customer execute failed: " .
        $stmt->error
    );

    $stmt->close();

    die("Unable to load customer information.");
}


$result = $stmt->get_result();

$user = $result->fetch_assoc();

$stmt->close();


if (!$user) {

    session_unset();
    session_destroy();

    header("Location: ../login.php");
    exit;
}


/* =========================================================
   CALCULATE CART TOTAL
========================================================= */

$grand_total = 0;

$total_items = 0;

$cart_products = [];


foreach ($_SESSION['cart'] as $product_id => $quantity) {

    $product_id = (int)$product_id;
    $quantity = (int)$quantity;


    if (
        $product_id <= 0 ||
        $quantity <= 0
    ) {
        continue;
    }


    $stmt = $conn->prepare("
        SELECT
            product_id,
            product_name,
            price,
            stock_quantity,
            size,
            image
        FROM products
        WHERE product_id = ?
        LIMIT 1
    ");


    if (!$stmt) {

        error_log(
            "Checkout product prepare failed: " .
            $conn->error
        );

        continue;
    }


    $stmt->bind_param("i", $product_id);


    if (!$stmt->execute()) {

        error_log(
            "Checkout product execute failed: " .
            $stmt->error
        );

        $stmt->close();

        continue;
    }


    $result = $stmt->get_result();

    $product = $result->fetch_assoc();

    $stmt->close();


    if (!$product) {
        continue;
    }


    /* =====================================================
       STOCK CHECK
    ===================================================== */

    $available_stock = (int)$product['stock_quantity'];


    if ($available_stock <= 0) {
        continue;
    }


    if ($quantity > $available_stock) {
        $quantity = $available_stock;
    }


    if ($quantity <= 0) {
        continue;
    }


    /* =====================================================
       CALCULATE SUBTOTAL
    ===================================================== */

    $price = (float)$product['price'];

    $subtotal = $price * $quantity;

    $grand_total += $subtotal;

    $total_items += $quantity;


    $product['quantity'] = $quantity;

    $product['subtotal'] = $subtotal;


    $cart_products[] = $product;
}


/* =========================================================
   IF NO VALID PRODUCTS REMAIN
========================================================= */

if (empty($cart_products)) {

    unset($_SESSION['cart']);

    header("Location: ../cart.php");
    exit;
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
        Checkout - QAMROSH
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        .checkout-page {
            background: #f8f8f8;
            min-height: 650px;
            padding: 55px 20px 70px;
        }


        .checkout-header {
            max-width: 1200px;
            margin: 0 auto 35px;
            text-align: center;
        }


        .checkout-header h1 {
            margin: 0 0 10px;
            color: #111;
            font-size: 36px;
        }


        .checkout-header p {
            margin: 0;
            color: #777;
        }


        .checkout-layout {
            max-width: 1100px;
            margin: auto;
            display: grid;
            grid-template-columns: 0.9fr 1.1fr;
            gap: 25px;
            align-items: start;
        }


        /* =====================================================
           ORDER SUMMARY
        ===================================================== */

        .order-summary {
            background: #fff;
            padding: 28px;
            border-radius: 15px;
            box-shadow: 0 6px 25px rgba(0,0,0,0.07);
        }


        .order-summary h2 {
            margin: 0 0 22px;
            color: #111;
            font-size: 23px;
        }


        .customer-info {
            background: #f8f6ef;
            padding: 18px;
            border-radius: 10px;
            margin-bottom: 22px;
        }


        .customer-info p {
            margin: 8px 0;
            color: #555;
            font-size: 14px;
        }


        .customer-info strong {
            color: #222;
        }


        /* =====================================================
           PRODUCTS
        ===================================================== */

        .checkout-products {
            margin-bottom: 20px;
        }


        .checkout-product {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 0;
            border-bottom: 1px solid #eee;
        }


        .checkout-product-image {
            width: 70px;
            height: 80px;
            flex-shrink: 0;
            border-radius: 8px;
            overflow: hidden;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
        }


        .checkout-product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            background: #f5f5f5;
        }


        .product-details {
            flex: 1;
            min-width: 0;
        }


        .product-details h3 {
            margin: 2px 0 6px;
            font-size: 15px;
            color: #222;
        }


        .product-details p {
            margin: 4px 0;
            font-size: 13px;
            color: #777;
        }


        .product-price {
            font-weight: bold;
            color: #b28a13;
            white-space: nowrap;
            font-size: 14px;
        }


        .summary-line {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 14px 0;
            border-bottom: 1px solid #eee;
            color: #555;
        }


        .summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
        }


        .summary-total span {
            font-weight: bold;
            color: #222;
        }


        .summary-total strong {
            color: #b28a13;
            font-size: 25px;
        }


        .secure-note {
            margin-top: 22px;
            padding: 13px;
            background: #f5f5f5;
            border-radius: 8px;
            text-align: center;
            color: #666;
            font-size: 13px;
        }


        /* =====================================================
           CHECKOUT FORM
        ===================================================== */

        .checkout-form {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 6px 25px rgba(0,0,0,0.07);
        }


        .checkout-form h2 {
            margin: 0 0 25px;
            color: #111;
            font-size: 23px;
        }


        .form-group {
            margin-bottom: 18px;
        }


        .form-group label {
            display: block;
            margin-bottom: 7px;
            color: #333;
            font-size: 14px;
            font-weight: bold;
        }


        .checkout-form input,
        .checkout-form textarea,
        .checkout-form select {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            background: #fff;
            color: #222;
            font-size: 15px;
            font-family: Arial, sans-serif;
            transition: 0.3s;
            box-sizing: border-box;
        }


        .checkout-form input:focus,
        .checkout-form textarea:focus,
        .checkout-form select:focus {
            border-color: #d4af37;
            box-shadow: 0 0 0 3px rgba(212,175,55,0.12);
        }


        .checkout-form textarea {
            resize: vertical;
            min-height: 110px;
        }


        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }


        .payment-note {
            margin-top: -7px;
            margin-bottom: 20px;
            color: #888;
            font-size: 12px;
        }


        .place-order-btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            background: #111;
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }


        .place-order-btn:hover {
            background: #d4af37;
            color: #111;
            transform: translateY(-1px);
        }


        .back-cart-btn {
            display: block;
            margin-top: 15px;
            padding: 12px;
            text-align: center;
            background: #eee;
            color: #222;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: 0.3s;
        }


        .back-cart-btn:hover {
            background: #ddd;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 850px) {

            .checkout-layout {
                grid-template-columns: 1fr;
                max-width: 650px;
            }

        }


        @media (max-width: 600px) {

            .checkout-page {
                padding: 35px 12px 50px;
            }


            .checkout-header h1 {
                font-size: 29px;
            }


            .order-summary,
            .checkout-form {
                padding: 22px;
            }


            .checkout-product-image {
                width: 60px;
                height: 70px;
            }


            .product-details h3 {
                font-size: 14px;
            }


            .product-price {
                font-size: 13px;
            }


            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

        }

    </style>

</head>


<body>


<!-- HEADER -->

<header class="header">

    <div class="logo">
        QAMROSH
    </div>


    <nav class="navbar">

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


        <span>

            Hi,
            <?php

            echo htmlspecialchars(
                $_SESSION['user_name'] ?? $user['name'],
                ENT_QUOTES,
                'UTF-8'
            );

            ?>

        </span>


        <a href="../logout.php">
            Logout
        </a>

    </nav>

</header>



<!-- CHECKOUT -->

<section class="checkout-page">


    <div class="checkout-header">

        <h1>
            Secure Checkout
        </h1>

        <p>
            Complete your details to place your order.
        </p>

    </div>


    <div class="checkout-layout">


        <!-- ORDER SUMMARY -->

        <div class="order-summary">

            <h2>
                Order Summary
            </h2>


            <!-- CUSTOMER INFO -->

            <div class="customer-info">

                <p>

                    <strong>
                        Customer:
                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $user['name'],
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    ?>

                </p>


                <p>

                    <strong>
                        Email:
                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $user['email'],
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    ?>

                </p>


                <p>

                    <strong>
                        Phone:
                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $user['phone'] ?? 'Not provided',
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    ?>

                </p>

            </div>


            <!-- PRODUCTS -->

            <div class="checkout-products">

                <?php foreach ($cart_products as $product): ?>

                    <?php

                    $product_image =
                        trim(
                            $product['image'] ?? ''
                        );


                    /*
                     * IMPORTANT:
                     * Admin uploads product images into:
                     * uploads/products/
                     */

                    if ($product_image !== '') {

                        $image_path =
                            "../uploads/products/" .
                            basename($product_image);

                    } else {

                        $image_path =
                            "../assets/images/no-image.png";

                    }

                    ?>


                    <div class="checkout-product">


                        <div class="checkout-product-image">

                            <img
                                src="<?php echo htmlspecialchars($image_path, ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                loading="lazy"
                                onerror="this.onerror=null;this.src='../assets/images/no-image.png';"
                            >

                        </div>


                        <div class="product-details">

                            <h3>

                                <?php

                                echo htmlspecialchars(
                                    $product['product_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                            </h3>


                            <p>

                                Quantity:

                                <?php

                                echo (int)$product['quantity'];

                                ?>

                            </p>


                            <?php if (!empty($product['size'])): ?>

                                <p>

                                    Size:

                                    <?php

                                    echo htmlspecialchars(
                                        $product['size'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </p>

                            <?php endif; ?>


                            <p>

                                Unit Price:

                                Rs.

                                <?php

                                echo number_format(
                                    (float)$product['price']
                                );

                                ?>

                            </p>

                        </div>


                        <div class="product-price">

                            Rs.

                            <?php

                            echo number_format(
                                (float)$product['subtotal']
                            );

                            ?>

                        </div>


                    </div>

                <?php endforeach; ?>

            </div>


            <!-- TOTAL ITEMS -->

            <div class="summary-line">

                <span>
                    Total Items
                </span>

                <span>

                    <?php

                    echo $total_items;

                    ?>

                </span>

            </div>


            <!-- DELIVERY -->

            <div class="summary-line">

                <span>
                    Delivery
                </span>

                <span>
                    Free
                </span>

            </div>


            <!-- GRAND TOTAL -->

            <div class="summary-total">

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


            <div class="secure-note">

                🔒 Your order information is securely submitted.

            </div>

        </div>



        <!-- CHECKOUT FORM -->

        <div class="checkout-form">

            <h2>
                Delivery Information
            </h2>


            <form
                action="place-order.php"
                method="POST"
            >


                <!-- CSRF -->

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>"
                >


                <!-- FULL NAME -->

                <div class="form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>"
                        maxlength="100"
                        autocomplete="name"
                        required
                    >

                </div>


                <!-- PHONE -->

                <div class="form-group">

                    <label for="phone">
                        Phone Number
                    </label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="<?php echo htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="03XXXXXXXXX"
                        maxlength="30"
                        autocomplete="tel"
                        required
                    >

                </div>


                <!-- ADDRESS -->

                <div class="form-group">

                    <label for="address">
                        Delivery Address
                    </label>

                    <textarea
                        id="address"
                        name="address"
                        maxlength="1000"
                        placeholder="Enter your complete delivery address"
                        autocomplete="street-address"
                        required
                    ></textarea>

                </div>


                <!-- CITY + POSTAL CODE -->

                <div class="form-row">

                    <div class="form-group">

                        <label for="city">
                            City
                        </label>

                        <input
                            type="text"
                            id="city"
                            name="city"
                            maxlength="100"
                            placeholder="e.g. Lahore"
                            autocomplete="address-level2"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="postal_code">
                            Postal Code
                        </label>

                        <input
                            type="text"
                            id="postal_code"
                            name="postal_code"
                            maxlength="20"
                            placeholder="e.g. 54000"
                            autocomplete="postal-code"
                            required
                        >

                    </div>

                </div>


                <!-- PAYMENT -->

                <div class="form-group">

                    <label for="payment_method">
                        Payment Method
                    </label>


                    <select
                        id="payment_method"
                        name="payment_method"
                        required
                    >

                        <option value="">
                            Select Payment Method
                        </option>

                        <option value="Cash on Delivery">
                            Cash on Delivery
                        </option>

                        <option value="JazzCash">
                            JazzCash
                        </option>

                        <option value="EasyPaisa">
                            EasyPaisa
                        </option>

                        <option value="Bank Transfer">
                            Bank Transfer
                        </option>

                    </select>

                </div>


                <p class="payment-note">

                    Select your preferred payment method
                    before placing your order.

                </p>


                <!-- PLACE ORDER -->

                <button
                    type="submit"
                    class="place-order-btn"
                >

                    🛍️ Place Order

                </button>

            </form>


            <a
                href="../cart.php"
                class="back-cart-btn"
            >

                ← Back to Cart

            </a>

        </div>

    </div>

</section>



<!-- FOOTER -->

<footer class="modern-footer">

    <div class="footer-container">


        <div class="footer-column footer-brand">

            <h2>
                QAMROSH
            </h2>

            <p>

                Your online destination for
                stylish and elegant ladies garments.
                Discover beautiful designs and
                shop your favorite outfits with ease.

            </p>

        </div>


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
                        Contact Us
                    </a>
                </li>

            </ul>

        </div>


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
                    href="../contact.php"
                    style="
                        color:#d4af37;
                        text-decoration:none;
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

        QAMROSH.

        All Rights Reserved.

    </div>

</footer>


</body>

</html>