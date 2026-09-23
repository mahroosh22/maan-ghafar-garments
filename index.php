<?php

session_start();

require_once "config/database.php";


/* =========================================
   CUSTOMER MESSAGE NOTIFICATION
========================================= */

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

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();
            $user_email = trim($user['email']);
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

            $stmt->bind_param("s", $user_email);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                $row = $result->fetch_assoc();

                $unread_replies = (int) $row['unread_replies'];
            }

            $stmt->close();
        }
    }
}


/* =========================================
   GET CATEGORIES
========================================= */

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

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $categories[] = $row;
    }

    $stmt->close();
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

<title>QAMROSH | Men's & Women's Fashion</title>

<link rel="stylesheet" href="assets/css/style.css">


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
       HERO SECTION
    ========================= */

    .home-hero {
        min-height: 620px;
        position: relative;
        display: flex;
        align-items: center;
        overflow: hidden;

        background-image:
            linear-gradient(
                90deg,
                rgba(0, 0, 0, 0.78),
                rgba(0, 0, 0, 0.40),
                rgba(0, 0, 0, 0.08)
            ),
            url("https://brandedcutpieces.com.pk/cdn/shop/files/KHAADI-PRINTED-D-7-Khaadi-Printed-Unstitched-Lawn-3pc-Khaadi-37356731236633.jpg?v=1764146613");

        background-size: cover;
        background-repeat: no-repeat;
        background-position: center;

        transition: background-image 0.8s ease-in-out;
    }


    .home-hero-content {
        max-width: 1200px;
        width: 100%;
        margin: auto;
        padding: 70px 50px;
        color: white;
        position: relative;
        z-index: 2;
    }


    .hero-badge {
        display: inline-block;

        padding: 8px 16px;

        margin-bottom: 20px;

        background: rgba(212, 175, 55, 0.95);

        color: #111;

        border-radius: 30px;

        font-size: 13px;

        font-weight: bold;

        letter-spacing: 1px;

        text-transform: uppercase;
    }


    .home-hero h1 {

        max-width: 700px;

        margin: 0 0 20px;

        font-size: 58px;

        line-height: 1.08;

        color: white;
    }


    .home-hero p {

        max-width: 600px;

        margin: 0 0 30px;

        font-size: 20px;

        line-height: 1.7;

        color: #f5f5f5;
    }


    .hero-buttons {

        display: flex;

        gap: 15px;

        flex-wrap: wrap;
    }


    .hero-shop-btn,
    .hero-outline-btn {

        display: inline-block;

        padding: 14px 28px;

        border-radius: 7px;

        text-decoration: none;

        font-weight: bold;

        transition: 0.3s ease;
    }


    .hero-shop-btn {

        background: #d4af37;

        color: #111;
    }


    .hero-shop-btn:hover {

        background: white;

        transform: translateY(-2px);
    }


    .hero-outline-btn {

        border: 1px solid white;

        color: white;

        background: rgba(255, 255, 255, 0.08);
    }


    .hero-outline-btn:hover {

        background: white;

        color: #111;
    }


    /* =========================
       FEATURES
    ========================= */

    .home-features {

        padding: 35px 50px;

        background: white;

        display: grid;

        grid-template-columns: repeat(3, 1fr);

        gap: 20px;

        max-width: 1200px;

        margin: auto;
    }


    .feature-box {

        text-align: center;

        padding: 20px;

        transition: 0.3s ease;
    }


    .feature-box:hover {

        transform: translateY(-4px);
    }


    .feature-icon {

        font-size: 32px;

        margin-bottom: 8px;
    }


    .feature-box h3 {

        margin-bottom: 5px;

        font-size: 18px;
    }


    .feature-box p {

        color: #777;

        font-size: 14px;
    }


    /* =========================
       CATEGORY SECTION
    ========================= */

    .category-subtitle {

        max-width: 650px;

        margin: -10px auto 30px;

        color: #666;

        font-size: 16px;
    }


    .category-container {

        max-width: 1200px;

        margin: 35px auto 60px;

        padding: 0 30px;

        display: grid;

        grid-template-columns:
            repeat(3, 1fr);

        gap: 25px;
    }


    .category-card {

        display: block;

        overflow: hidden;

        background: white;

        border-radius: 14px;

        text-decoration: none;

        color: #111;

        box-shadow:
            0 8px 25px rgba(0, 0, 0, 0.08);

        transition:
            transform 0.3s ease,
            box-shadow 0.3s ease;
    }


    .category-card:hover {

        transform: translateY(-8px);

        box-shadow:
            0 15px 35px rgba(0, 0, 0, 0.15);
    }


    .category-card-image {

        width: 100%;

        height: 300px;

        overflow: hidden;

        background: #f5f5f5;

        display: flex;

        align-items: center;

        justify-content: center;
    }


    /* =========================
       CATEGORY IMAGE - NO CROP
    ========================= */

    .category-card-image img {

        width: 100%;

        height: 100%;

        object-fit: contain;

        display: block;

        background: #f5f5f5;

        transition:
            transform 0.4s ease;
    }


    .category-card:hover
    .category-card-image img {

        transform: scale(1.03);
    }


    .category-card h3 {

        margin: 0;

        padding: 18px 15px;

        text-align: center;

        font-size: 19px;

        color: #222;
    }


    .category-card p {

        margin: -8px 15px 18px;

        text-align: center;

        color: #777;

        font-size: 13px;

        line-height: 1.5;
    }


    .category-empty {

        grid-column: 1 / -1;

        text-align: center;

        padding: 40px;

        color: #777;

        background: #f8f8f8;

        border-radius: 12px;
    }


    .all-products-btn {

        display: inline-block;

        padding: 12px 24px;

        background: #111;

        color: white;

        text-decoration: none;

        border-radius: 7px;

        font-weight: bold;

        transition: 0.3s ease;
    }


    .all-products-btn:hover {

        background: #d4af37;

        color: #111;
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

        transition: 0.3s;
    }


    .footer-links a:hover {

        color: #d4af37;

        padding-left: 4px;
    }


    .footer-contact p {

        margin: 10px 0;

        color: #bbb;

        font-size: 14px;
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
       ADMIN LOGIN
    ========================= */

    .admin-login-link {

        color: #d4af37 !important;

        font-weight: bold;
    }


    .admin-login-link:hover {

        color: white !important;
    }


    /* =========================
       RESPONSIVE
    ========================= */

    @media (max-width: 900px) {

        .category-container {

            grid-template-columns:
                repeat(2, 1fr);
        }

    }


    @media (max-width: 768px) {

        .home-hero {

            min-height: 560px;

            background-size: cover;

            background-position: center;
        }


        .home-hero-content {

            padding: 60px 25px;
        }


        .home-hero h1 {

            font-size: 40px;
        }


        .home-hero p {

            font-size: 17px;
        }


        .home-features {

            grid-template-columns: 1fr;

            padding: 25px 20px;

            gap: 5px;
        }


        .category-container {

            grid-template-columns: 1fr;

            padding: 0 20px;
        }


        .category-card-image {

            height: 280px;
        }


        .modern-footer {

            padding: 45px 25px 20px;
        }


        .footer-container {

            grid-template-columns: 1fr;

            gap: 30px;
        }

    }


    @media (max-width: 480px) {

        .home-hero h1 {

            font-size: 34px;
        }


        .hero-buttons {

            flex-direction: column;

            align-items: flex-start;
        }


        .hero-shop-btn,
        .hero-outline-btn {

            width: 100%;

            text-align: center;
        }


        .category-card-image {

            height: 250px;
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

                    <?= $unread_replies ?>

                </span>

            <?php endif; ?>

        </a>


        <span>

            Hi,

            <?php

            echo htmlspecialchars(
                $_SESSION['user_name'] ?? 'Customer'
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
     HERO
========================= -->

<section class="home-hero">


<div class="home-hero-content">


    <span class="hero-badge">

        Men's & Women's Fashion Collection

    </span>


    <h1>

        Style That Makes
        You Stand Out

    </h1>


    <p>

        Discover elegant men's and women's fashion,
        beautiful designs and quality clothing
        made for every occasion.

    </p>


    <div class="hero-buttons">


        <a
            href="products.php"
            class="hero-shop-btn"
        >

            Shop Collection →

        </a>


        <a
            href="#categories"
            class="hero-outline-btn"
        >

            Explore Categories

        </a>


    </div>


</div>

</section>


<!-- =========================
     FEATURES
========================= -->

<section class="home-features">


<div class="feature-box">


    <div class="feature-icon">

        ✨

    </div>


    <h3>

        Elegant Designs

    </h3>


    <p>

        Stylish men's and women's fashion for every occasion.

    </p>


</div>


<div class="feature-box">


    <div class="feature-icon">

        🛍️

    </div>


    <h3>

        Easy Shopping

    </h3>


    <p>

        Browse products and order easily from anywhere.

    </p>


</div>


<div class="feature-box">


    <div class="feature-icon">

        🚚

    </div>


    <h3>

        Easy Delivery

    </h3>


    <p>

        Convenient order and delivery process.

    </p>


</div>


</section>


<!-- =========================
     CATEGORIES
========================= -->

<section
    class="categories"
    id="categories"
>


<h2>

    Shop By Category

</h2>


<p class="category-subtitle">

    Explore our collection of stylish men's and women's
    fashion and find a look that matches your style.

</p>


<a
    href="products.php"
    class="all-products-btn"
>

    View All Products

</a>


<div class="category-container">


<?php if (!empty($categories)): ?>


    <?php foreach ($categories as $category): ?>


        <?php

        $category_id =
            (int) $category['category_id'];

        $category_name =
            trim($category['category_name']);

        $category_image =
            trim($category['image'] ?? '');

        if ($category_image !== '') {

            $image_path =
                "uploads/categories/" .
                basename($category_image);

        } else {

            $image_path =
                "assets/images/no-image.png";

        }

        ?>


        <a
            href="products.php?category=<?= $category_id ?>"
            class="category-card"
        >


            <div class="category-card-image">


                <img
                    src="<?= htmlspecialchars($image_path) ?>"
                    alt="<?= htmlspecialchars($category_name) ?>"
                    loading="lazy"
                    onerror="this.onerror=null;this.src='assets/images/no-image.png';"
                >


            </div>


            <h3>

                <?= htmlspecialchars($category_name) ?>

            </h3>


            <?php if (!empty($category['description'])): ?>


                <p>

                    <?= htmlspecialchars(
                        $category['description']
                    ) ?>

                </p>


            <?php endif; ?>


        </a>


    <?php endforeach; ?>


<?php else: ?>


    <div class="category-empty">

        No categories available yet.

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

            QAMROSH

        </h2>


        <p>

            Your online destination for stylish and elegant
            men's and women's fashion. Discover beautiful
            designs and shop your favorite outfits with ease.

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


            <li>

                <a
                    href="admin/login.php"
                    class="admin-login-link"
                >

                    🔐 Admin Login

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


<div class="footer-bottom">

    © <?php echo date("Y"); ?>

    QAMROSH.

    All Rights Reserved.

</div>


</footer>


<!-- =========================
     HERO IMAGE SLIDER
========================= -->

<script>

const heroImages = [

    "https://brandedcutpieces.com.pk/cdn/shop/files/KHAADI-PRINTED-D-7-Khaadi-Printed-Unstitched-Lawn-3pc-Khaadi-37356731236633.jpg?v=1764146613",

    "https://binsaeedfabric.com/cdn/shop/files/DP-0013-_3_Piece_Lawn_Printed_UnStitched_Suit_-_pictures_-_3181d5488465bd2be7668f414f26c4c4.jpg?v=1760803644",

    "https://images.unsplash.com/photo-1551488831-00ddcb6c6bd3?auto=format&fit=crop&fm=jpg&q=85&w=2000"

];


let heroIndex = 0;


setInterval(function () {

    heroIndex =
        (heroIndex + 1) %
        heroImages.length;


    const hero =
        document.querySelector(".home-hero");


    if (hero) {

        hero.style.backgroundImage =

            `
            linear-gradient(
                90deg,
                rgba(0, 0, 0, 0.78),
                rgba(0, 0, 0, 0.40),
                rgba(0, 0, 0, 0.08)
            ),
            url("${heroImages[heroIndex]}")
            `;

    }


}, 2500);

</script>


</body>

</html>