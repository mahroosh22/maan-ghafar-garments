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


/*
|--------------------------------------------------------------------------
| Product ID
|--------------------------------------------------------------------------
*/

$product_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$product_id || $product_id <= 0) {
    http_response_code(400);
    die("Invalid Product ID.");
}


/*
|--------------------------------------------------------------------------
| Get Product
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        product_id,
        product_name,
        category_id,
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
    error_log("Product query prepare failed: " . $conn->error);
    http_response_code(500);
    die("Unable to load product.");
}

$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();

$stmt->close();

if (!$product) {
    http_response_code(404);
    die("Product Not Found.");
}


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

        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $user_result = $stmt->get_result();

        if ($user_result->num_rows === 1) {

            $user = $user_result->fetch_assoc();

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

            $notification_result =
                $stmt->get_result();

            if (
                $notification_result->num_rows === 1
            ) {

                $notification =
                    $notification_result->fetch_assoc();

                $unread_replies =
                    (int) $notification['unread_replies'];
            }

            $stmt->close();
        }
    }
}


/*
|--------------------------------------------------------------------------
| Product Image
|--------------------------------------------------------------------------
| Admin uploads images into:
| uploads/products/
|--------------------------------------------------------------------------
*/

$product_image = trim(
    $product['image'] ?? ''
);

if ($product_image !== '') {

    $image_path =
        "uploads/products/" .
        basename($product_image);

} else {

    $image_path =
        "assets/images/no-image.png";
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
        <?php echo e($product['product_name']); ?>
        - QAMROSH
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="assets/css/product-details.css"
    >

    <style>

        /* =========================================
           PRODUCT DETAILS IMAGE FIX
        ========================================= */

        .product-details-image {
            position: relative;

            width: 100%;
            min-height: 500px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #f7f7f7;

            border-radius: 15px;

            overflow: hidden;
        }


        .product-details-image img {
            width: 100%;
            height: 500px;

            object-fit: contain;

            display: block;

            background: #f7f7f7;
        }


        /* Prevent image cropping/zoom */

        .product-details-image img:hover {
            transform: none !important;
        }


        /* =========================================
           MESSAGE NOTIFICATION
        ========================================= */

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


        /* =========================================
           RESPONSIVE PRODUCT IMAGE
        ========================================= */

        @media (max-width: 768px) {

            .product-details-image {
                min-height: 380px;
            }

            .product-details-image img {
                height: 380px;
            }

        }


        @media (max-width: 500px) {

            .product-details-image {
                min-height: 330px;
            }

            .product-details-image img {
                height: 330px;
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
        QAMROSH
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
     PRODUCT DETAILS
========================= -->

<section class="product-details-page">


    <div class="product-details">


        <!-- PRODUCT IMAGE -->

        <div class="product-details-image">


            <div class="image-badge">
                Ladies Collection
            </div>


            <img
                src="<?php echo e($image_path); ?>"
                alt="<?php echo e($product['product_name']); ?>"
                onerror="this.onerror=null;this.src='assets/images/no-image.png';"
            >


        </div>



        <!-- PRODUCT INFORMATION -->

        <div class="product-details-info">


            <span class="product-label">
                QAMROSH Collection
            </span>


            <h1>

                <?php
                echo e(
                    $product['product_name']
                );
                ?>

            </h1>


            <div class="product-rating">

                ★★★★★

                <span>
                    Premium Quality
                </span>

            </div>


            <div class="product-price">

                Rs.

                <?php
                echo number_format(
                    (float) $product['price']
                );
                ?>

            </div>


            <div class="product-divider"></div>


            <p class="product-description">

                <?php

                $description = trim(
                    $product['description'] ?? ''
                );

                if ($description !== '') {

                    echo e($description);

                } else {

                    echo "Beautiful ladies garment.";

                }

                ?>

            </p>



            <!-- PRODUCT INFO -->

            <div class="product-info-box">


                <div class="info-item">

                    <span>
                        Size
                    </span>


                    <strong>

                        <?php
                        echo e(
                            $product['size']
                        );
                        ?>

                    </strong>

                </div>



                <div class="info-item">

                    <span>
                        Availability
                    </span>


                    <strong
                        class="<?php

                        echo (
                            (int) $product['stock_quantity'] > 0
                        )
                            ? 'in-stock'
                            : 'out-stock';

                        ?>"
                    >

                        <?php

                        if (
                            (int) $product['stock_quantity'] > 0
                        ):

                        ?>

                            In Stock

                        <?php else: ?>

                            Out of Stock

                        <?php endif; ?>

                    </strong>

                </div>



                <div class="info-item">

                    <span>
                        Stock
                    </span>


                    <strong>

                        <?php

                        echo (int)
                            $product['stock_quantity'];

                        ?>

                    </strong>

                </div>


            </div>



            <!-- ADD TO CART -->

            <?php

            if (
                (int) $product['stock_quantity'] > 0
            ):

            ?>


                <a
                    href="add-to-cart.php?id=<?php echo (int) $product['product_id']; ?>"
                    class="add-to-cart-btn"
                >
                    🛒 Add to Cart
                </a>


            <?php else: ?>


                <button
                    class="add-to-cart-btn disabled-btn"
                    disabled
                >
                    Out of Stock
                </button>


            <?php endif; ?>



            <a
                href="products.php"
                class="back-products-btn"
            >
                ← Back to Products
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
                QAMROSH
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


                <?php
                if (isset($_SESSION['user_id'])):
                ?>

                    <li>
                        <a href="customer/profile.php">
                            My Profile
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
                    style="color:#d4af37;text-decoration:none;"
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



    <!-- FOOTER BOTTOM -->

    <div class="footer-bottom">

        © <?php echo date("Y"); ?>

        QAMROSH.

        All Rights Reserved.

    </div>


</footer>


</body>

</html>