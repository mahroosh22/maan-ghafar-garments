
<?php
session_start();

require_once "config/database.php";

/* Customer login check */
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'customer') {
    header("Location: login.php");
    exit;
}

/* Cart check */
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* Get customer information */
$stmt = $conn->prepare("
    SELECT name, email
    FROM users
    WHERE id = ? AND role = 'customer'
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

if (!$user) {
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}


/* =========================
   GET CART PRODUCTS
========================= */

$cart_products = [];
$grand_total = 0;

foreach ($_SESSION['cart'] as $product_id => $quantity) {

    $product_id = (int)$product_id;
    $quantity = (int)$quantity;

    if ($quantity <= 0) {
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

    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    $stmt->close();

    if ($product) {

        $product['quantity'] = $quantity;

        $item_total = (float)$product['price'] * $quantity;

        $product['item_total'] = $item_total;

        $grand_total += $item_total;

        $cart_products[] = $product;
    }
}


/* If no valid products remain */
if (empty($cart_products)) {
    header("Location: cart.php");
    exit;
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Checkout - Maan Ghafar Garments</title>

<link rel="stylesheet" href="assets/css/style.css">

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
        grid-template-columns: 0.95fr 1.05fr;
        gap: 25px;
        align-items: start;
    }


    /* =========================
       ORDER SUMMARY
    ========================= */

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


    /* PRODUCT ITEM */

    .checkout-product {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 0;
        border-bottom: 1px solid #eee;
    }

    .checkout-product-image {
        width: 75px;
        height: 85px;
        flex-shrink: 0;
        background: #f5f5f5;
        border-radius: 9px;
        overflow: hidden;
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


    .checkout-product-info {
        flex: 1;
        min-width: 0;
    }

    .checkout-product-name {
        margin: 0 0 6px;
        color: #222;
        font-size: 15px;
        font-weight: bold;
    }

    .checkout-product-details {
        margin: 3px 0;
        color: #777;
        font-size: 12px;
    }

    .checkout-product-price {
        margin-top: 6px;
        color: #b28a13;
        font-size: 14px;
        font-weight: bold;
    }

    .checkout-product-total {
        color: #222;
        font-size: 14px;
        font-weight: bold;
        white-space: nowrap;
    }


    .summary-line {
        display: flex;
        justify-content: space-between;
        padding: 14px 0;
        border-bottom: 1px solid #eee;
        color: #555;
        gap: 15px;
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


    /* =========================
       CHECKOUT FORM
    ========================= */

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


    /* =========================
       RESPONSIVE
    ========================= */

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
            width: 65px;
            height: 75px;
        }

        .checkout-product-name {
            font-size: 14px;
        }

        .checkout-product-total {
            font-size: 13px;
        }

    }

</style>

</head>


<body>


<!-- HEADER -->

<header class="header">

    <div class="logo">
        Maan Ghafar Garments
    </div>

    <nav class="navbar">

        <a href="index.php">Home</a>

        <a href="products.php">Products</a>

        <a href="about.php">About</a>

        <a href="contact.php">Contact</a>

        <?php if (isset($_SESSION['user_id'])): ?>

            <span>
                Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </span>

            <a href="logout.php">Logout</a>

        <?php else: ?>

            <a href="login.php">Login</a>

            <a href="register.php">Register</a>

        <?php endif; ?>

    </nav>

</header>


<!-- CHECKOUT -->

<section class="checkout-page">


    <div class="checkout-header">

        <h1>Secure Checkout</h1>

        <p>
            Complete your details to place your order.
        </p>

    </div>


    <div class="checkout-layout">


        <!-- ORDER SUMMARY -->

        <div class="order-summary">

            <h2>Order Summary</h2>


            <!-- CUSTOMER INFORMATION -->

            <div class="customer-info">

                <p>
                    <strong>Customer:</strong>
                    <?php echo htmlspecialchars($user['name']); ?>
                </p>

                <p>
                    <strong>Email:</strong>
                    <?php echo htmlspecialchars($user['email']); ?>
                </p>

            </div>


            <!-- PRODUCTS -->

            <?php foreach ($cart_products as $product): ?>

                <?php

                $product_image = trim($product['image'] ?? '');

                if ($product_image !== '') {

                    $image_path = "uploads/products/" . basename($product_image);

                } else {

                    $image_path = "assets/images/no-image.png";

                }

                ?>

                <div class="checkout-product">


                    <div class="checkout-product-image">

                        <img
                            src="<?php echo htmlspecialchars($image_path); ?>"
                            alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                            onerror="this.onerror=null;this.src='assets/images/no-image.png';"
                        >

                    </div>


                    <div class="checkout-product-info">

                        <p class="checkout-product-name">

                            <?php
                            echo htmlspecialchars($product['product_name']);
                            ?>

                        </p>


                        <p class="checkout-product-details">

                            Quantity:
                            <?php echo (int)$product['quantity']; ?>

                            <?php if (!empty($product['size'])): ?>

                                &nbsp; | &nbsp;

                                Size:
                                <?php echo htmlspecialchars($product['size']); ?>

                            <?php endif; ?>

                        </p>


                        <p class="checkout-product-price">

                            Rs.
                            <?php echo number_format((float)$product['price']); ?>

                        </p>

                    </div>


                    <div class="checkout-product-total">

                        Rs.
                        <?php echo number_format((float)$product['item_total']); ?>

                    </div>


                </div>

            <?php endforeach; ?>


            <!-- PRODUCT COUNT -->

            <div class="summary-line">

                <span>Products</span>

                <span>
                    <?php echo count($cart_products); ?> item(s)
                </span>

            </div>


            <!-- DELIVERY -->

            <div class="summary-line">

                <span>Delivery</span>

                <span>Calculated at checkout</span>

            </div>


            <!-- GRAND TOTAL -->

            <div class="summary-total">

                <span>Grand Total</span>

                <strong>
                    Rs. <?php echo number_format($grand_total); ?>
                </strong>

            </div>


            <div class="secure-note">

                🔒 Your order information is securely submitted.

            </div>


        </div>


        <!-- CHECKOUT FORM -->

        <div class="checkout-form">

            <h2>Delivery Information</h2>


            <form
                action="place-order.php"
                method="POST"
            >


                <div class="form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?php echo htmlspecialchars($user['name']); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="phone">
                        Phone Number
                    </label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        placeholder="03XXXXXXXXX"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="address">
                        Delivery Address
                    </label>

                    <textarea
                        id="address"
                        name="address"
                        placeholder="Enter your complete delivery address"
                        required
                    ></textarea>

                </div>


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

                    Select your preferred payment method before placing the order.

                </p>


                <button
                    type="submit"
                    class="place-order-btn"
                >
                    🛍️ Place Order
                </button>


            </form>


            <a
                href="cart.php"
                class="back-cart-btn"
            >
                ← Back to Cart
            </a>

        </div>


    </div>


</section>


<!-- FOOTER -->

<footer class="site-footer">


    <div class="footer-container">


        <div class="footer-column">

            <h3>Maan Ghafar Garments</h3>

            <p>
                Discover elegant and stylish ladies garments
                made for every occasion.
            </p>

        </div>


        <div class="footer-column">

            <h3>Quick Links</h3>

            <a href="index.php">Home</a>

            <a href="products.php">Products</a>

            <a href="about.php">About Us</a>

            <a href="contact.php">Contact</a>

        </div>


        <div class="footer-column">

            <h3>Customer Area</h3>

            <a href="login.php">Login</a>

            <a href="register.php">Register</a>

            <a href="products.php">Shop Now</a>

        </div>


        <div class="footer-column">

            <h3>Contact & Support</h3>

            <p>📧 support@maanghafar.com</p>

            <p>📞 Customer Support</p>

            <p>🕐 Mon - Sat: 10 AM - 8 PM</p>

        </div>


    </div>


    <div class="footer-bottom">

        <p>

            © <?php echo date("Y"); ?>

            Maan Ghafar Garments.

            All Rights Reserved.

        </p>

    </div>


</footer>


</body>

</html>
