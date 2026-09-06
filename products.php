<?php

session_start();

require_once "config/database.php";


/* =========================================================
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   CUSTOMER MESSAGE NOTIFICATION
========================================================= */

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

        if ($stmt->execute()) {

            $result_user = $stmt->get_result();

            if ($result_user->num_rows === 1) {

                $user = $result_user->fetch_assoc();

                $user_email = trim(
                    $user['email'] ?? ''
                );
            }
        }

        $stmt->close();
    }


    if ($user_email !== "") {

        $stmt = $conn->prepare("
            SELECT COUNT(*) AS unread_replies
            FROM contact_messages
            WHERE LOWER(TRIM(email)) =
                  LOWER(TRIM(?))
            AND admin_reply IS NOT NULL
            AND TRIM(admin_reply) <> ''
            AND reply_seen = 0
        ");

        if ($stmt) {

            $stmt->bind_param("s", $user_email);

            if ($stmt->execute()) {

                $result_notification =
                    $stmt->get_result();

                if ($result_notification->num_rows === 1) {

                    $row =
                        $result_notification->fetch_assoc();

                    $unread_replies =
                        (int) $row['unread_replies'];
                }
            }

            $stmt->close();
        }
    }
}


/* =========================================================
   GET CATEGORIES
========================================================= */

$categories = [];

$stmt = $conn->prepare("
    SELECT
        category_id,
        category_name,
        description,
        image
    FROM category
    ORDER BY category_id ASC
");

if ($stmt) {

    if ($stmt->execute()) {

        $category_result = $stmt->get_result();

        while ($row = $category_result->fetch_assoc()) {

            $categories[] = $row;
        }
    }

    $stmt->close();
}


/* =========================================================
   CATEGORY FILTER
========================================================= */

$category = filter_input(
    INPUT_GET,
    'category',
    FILTER_VALIDATE_INT
);

if (!$category || $category < 1) {
    $category = 0;
}


/* =========================================================
   GET SELECTED CATEGORY NAME
========================================================= */

$selected_category_name = "";

if ($category > 0) {

    $stmt = $conn->prepare("
        SELECT category_name
        FROM category
        WHERE category_id = ?
        LIMIT 1
    ");

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $category
        );

        if ($stmt->execute()) {

            $selected_category_result =
                $stmt->get_result();

            if (
                $selected_category_result->num_rows === 1
            ) {

                $selected_category =
                    $selected_category_result->fetch_assoc();

                $selected_category_name =
                    trim(
                        $selected_category['category_name']
                        ?? ''
                    );
            }
        }

        $stmt->close();
    }
}


/* =========================================================
   GET PRODUCTS
========================================================= */

$result = false;

if ($category > 0) {

    $stmt = $conn->prepare("
        SELECT
            product_id,
            product_name,
            category_id,
            description,
            price,
            stock_quantity,
            size,
            image,
            created_at
        FROM products
        WHERE category_id = ?
        ORDER BY product_id DESC
    ");

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $category
        );

        if ($stmt->execute()) {

            $result = $stmt->get_result();
        }

        $stmt->close();
    }

} else {

    $stmt = $conn->prepare("
        SELECT
            product_id,
            product_name,
            category_id,
            description,
            price,
            stock_quantity,
            size,
            image,
            created_at
        FROM products
        ORDER BY product_id DESC
    ");

    if ($stmt) {

        if ($stmt->execute()) {

            $result = $stmt->get_result();
        }

        $stmt->close();
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
        Products | Maan Ghafar Garments
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <style>

        /* =========================
           MESSAGE NOTIFICATION
        ========================= */

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


        /* =========================
           PRODUCTS PAGE
        ========================= */

        .products-page {
            padding: 55px 40px 70px;
            background: #f8f8f8;
            min-height: 500px;
        }


        .products-header {
            max-width: 1200px;
            margin: 0 auto 35px;
            text-align: center;
        }


        .products-header h1 {
            margin: 0 0 12px;
            font-size: 38px;
            color: #222;
        }


        .products-header p {
            margin: 0 auto;
            max-width: 650px;
            color: #777;
            line-height: 1.7;
        }


        /* =========================
           SELECTED CATEGORY
        ========================= */

        .selected-category {
            max-width: 1200px;
            margin: 0 auto 25px;
            text-align: center;
        }


        .selected-category h2 {
            margin: 0;
            font-size: 25px;
            color: #b28a13;
        }


        /* =========================
           CATEGORY FILTER
        ========================= */

        .product-categories {
            max-width: 1200px;
            margin: 0 auto 40px;

            display: flex;
            justify-content: center;
            align-items: center;

            gap: 12px;
            flex-wrap: wrap;
        }


        .category-filter-btn {
            display: inline-block;

            padding: 11px 22px;

            border: 1px solid #ddd;
            border-radius: 30px;

            background: white;
            color: #333;

            text-decoration: none;

            font-size: 14px;
            font-weight: 600;

            transition: 0.3s ease;
        }


        .category-filter-btn:hover {
            background: #d4af37;
            border-color: #d4af37;
            color: #111;

            transform: translateY(-2px);
        }


        .category-filter-btn.active {
            background: #d4af37;
            border-color: #d4af37;
            color: #111;
        }


        /* =========================
           PRODUCTS GRID
        ========================= */

        .product-container {
            max-width: 1200px;
            margin: auto;

            display: grid;
            grid-template-columns: repeat(4, 1fr);

            gap: 25px;
        }


        /* =========================
           PRODUCT CARD
        ========================= */

        .product-card {
            background: white;

            border-radius: 12px;
            overflow: hidden;

            box-shadow:
                0 5px 20px
                rgba(0, 0, 0, 0.07);

            transition: 0.3s ease;

            padding-bottom: 20px;
        }


        .product-card:hover {
            transform: translateY(-7px);

            box-shadow:
                0 12px 30px
                rgba(0, 0, 0, 0.13);
        }


        /* =========================
           PRODUCT IMAGE
        ========================= */

        .product-image {
            width: 100%;
            height: 300px;

            overflow: hidden;

            background: #f5f5f5;

            display: flex;
            align-items: center;
            justify-content: center;
        }


        .product-image img {
            width: 100%;
            height: 100%;

            object-fit: contain;

            display: block;

            background: #f5f5f5;
        }


        .product-card:hover
        .product-image img {
            transform: none;
        }


        /* =========================
           PRODUCT DETAILS
        ========================= */

        .product-card h3 {
            margin: 18px 18px 8px;

            font-size: 18px;
            color: #222;
        }


        .product-card p {
            margin: 0 18px 12px;

            color: #777;

            font-size: 14px;
            line-height: 1.6;

            min-height: 44px;
        }


        .product-price {
            display: block;

            margin: 0 18px 15px;

            font-size: 20px;
            font-weight: bold;

            color: #b28a13;
        }


        .view-details-btn {
            display: block;

            margin: 0 18px;

            padding: 11px 15px;

            text-align: center;

            border-radius: 7px;

            background: #111;
            color: white;

            text-decoration: none;

            font-size: 14px;
            font-weight: bold;

            transition: 0.3s ease;
        }


        .view-details-btn:hover {
            background: #d4af37;
            color: #111;
        }


        /* =========================
           EMPTY PRODUCTS
        ========================= */

        .no-products {
            max-width: 600px;

            margin: 30px auto;
            padding: 35px;

            text-align: center;

            background: white;

            border-radius: 12px;

            color: #777;

            box-shadow:
                0 5px 20px
                rgba(0, 0, 0, 0.06);
        }


        .no-products h3 {
            color: #333;
            margin-bottom: 8px;
        }


        /* =========================
           FOOTER
        ========================= */

        .modern-footer {
            background: #111;
            color: white;

            padding: 55px 50px 20px;
        }


        .footer-container {
            max-width: 1200px;

            margin: auto;

            display: grid;

            grid-template-columns:
                1.5fr
                1fr
                1fr
                1.2fr;

            gap: 45px;
        }


        .footer-column h3 {
            margin-bottom: 18px;

            font-size: 19px;

            color: #d4af37;
        }


        .footer-brand h2 {
            margin-bottom: 15px;

            font-size: 24px;

            color: white;
        }


        .footer-brand p {
            max-width: 330px;

            color: #bbb;

            line-height: 1.7;

            font-size: 14px;
        }


        .footer-links {
            list-style: none;
            padding: 0;
        }


        .footer-links li {
            margin-bottom: 10px;
        }


        .footer-links a {
            color: #bbb;

            text-decoration: none;

            font-size: 14px;

            transition: 0.3s ease;
        }


        .footer-links a:hover {
            color: #d4af37;
            padding-left: 4px;
        }


        .footer-contact p {
            margin: 10px 0;

            color: #bbb;

            font-size: 14px;
            line-height: 1.6;
        }


        .footer-contact strong {
            color: white;
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


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1000px) {

            .product-container {
                grid-template-columns:
                    repeat(3, 1fr);
            }
        }


        @media (max-width: 768px) {

            .products-page {
                padding: 45px 20px 55px;
            }


            .products-header h1 {
                font-size: 32px;
            }


            .product-container {
                grid-template-columns:
                    repeat(2, 1fr);

                gap: 18px;
            }


            .product-image {
                height: 260px;
            }


            .modern-footer {
                padding: 45px 25px 20px;
            }


            .footer-container {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }


        @media (max-width: 500px) {

            .product-container {
                grid-template-columns: 1fr;
            }


            .product-image {
                height: 330px;
            }


            .products-header h1 {
                font-size: 28px;
            }


            .category-filter-btn {
                width: 100%;
                text-align: center;
                box-sizing: border-box;
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
                        <?= $unread_replies ?>
                    </span>

                <?php endif; ?>

            </a>


            <span>

                Hi,

                <?php

                echo e(
                    $_SESSION['user_name'] ??
                    'Customer'
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
     PRODUCTS
========================= -->

<section class="products-page">


    <div class="products-header">

        <h1>
            Our Collection
        </h1>


        <p>

            Explore our beautiful collection of
            ladies garments, designed to bring
            elegance and style to every occasion.

        </p>

    </div>


    <!-- =========================
         SELECTED CATEGORY TITLE
    ========================= -->

    <?php if ($category > 0 && $selected_category_name !== ''): ?>

        <div class="selected-category">

            <h2>
                <?= e($selected_category_name) ?>
            </h2>

        </div>

    <?php endif; ?>


    <!-- =========================
         CATEGORY FILTER
    ========================= -->

    <div class="product-categories">

        <a
            href="products.php"
            class="category-filter-btn
            <?= ($category === 0) ? 'active' : ''; ?>"
        >
            All Products
        </a>


        <?php foreach ($categories as $cat): ?>

            <?php

            $cat_id =
                (int) $cat['category_id'];

            $cat_name =
                trim(
                    $cat['category_name'] ?? ''
                );

            ?>

            <a
                href="products.php?category=<?= $cat_id ?>"
                class="category-filter-btn
                <?= ($category === $cat_id)
                    ? 'active'
                    : ''; ?>"
            >

                <?= e($cat_name) ?>

            </a>

        <?php endforeach; ?>

    </div>



    <!-- =========================
         PRODUCT CARDS
    ========================= -->

    <div class="product-container">


        <?php if ($result && $result->num_rows > 0): ?>


            <?php while (
                $product = $result->fetch_assoc()
            ): ?>


                <?php

                $image =
                    trim(
                        $product['image'] ?? ''
                    );


                if ($image !== '') {

                    $image_path =
                        'uploads/products/' .
                        basename($image);

                } else {

                    $image_path =
                        'assets/images/no-image.png';
                }

                ?>


                <div class="product-card">


                    <div class="product-image">

                        <img
                            src="<?= e($image_path) ?>"
                            alt="<?= e(
                                $product['product_name']
                            ) ?>"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='assets/images/no-image.png';"
                        >

                    </div>


                    <h3>

                        <?= e(
                            $product['product_name']
                        ) ?>

                    </h3>


                    <p>

                        <?php

                        $description =
                            trim(
                                $product['description'] ?? ''
                            );

                        if ($description === '') {

                            echo "Beautiful ladies garment.";

                        } else {

                            echo e(
                                mb_strimwidth(
                                    $description,
                                    0,
                                    110,
                                    '...'
                                )
                            );
                        }

                        ?>

                    </p>


                    <span class="product-price">

                        Rs.

                        <?= number_format(
                            (float) $product['price']
                        ) ?>

                    </span>


                    <a
                        href="product-details.php?id=<?= urlencode(
                            $product['product_id']
                        ) ?>"
                        class="view-details-btn"
                    >

                        View Details

                    </a>


                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <div class="no-products">

                <h3>
                    No Products Found
                </h3>


                <p>

                    There are currently no products
                    available in this category.

                </p>


                <a
                    href="products.php"
                    class="view-details-btn"
                >

                    View All Products

                </a>

            </div>


        <?php endif; ?>


    </div>


</section>



<!-- =========================
     FOOTER
========================= -->

<footer class="modern-footer">


    <div class="footer-container">


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


                <?php if (isset($_SESSION['user_id'])): ?>

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

        © <?= date("Y"); ?>

        Maan Ghafar Garments.

        All Rights Reserved.

    </div>


</footer>


</body>

</html>