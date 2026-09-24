<?php

session_start();

require_once "config/database.php";


/* =========================
   MESSAGE NOTIFICATION
========================= */

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

        $result_user = $stmt->get_result();

        if ($result_user->num_rows === 1) {

            $user = $result_user->fetch_assoc();

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

            $result_notification = $stmt->get_result();

            if ($result_notification->num_rows === 1) {

                $row = $result_notification->fetch_assoc();

                $unread_replies = (int) $row['unread_replies'];
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
        About Us - QAMROSH
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
           ABOUT PAGE
        ========================= */

        .about-page {
            background: #f8f8f8;
        }


        /* =========================
           ABOUT HERO
        ========================= */

        .about-hero {

            background:
                linear-gradient(
                    135deg,
                    #111827,
                    #242424
                );

            color: white;

            text-align: center;

            padding: 80px 20px;
        }


        .about-hero h1 {

            font-size: 46px;

            margin: 0 0 15px;
        }


        .about-hero h1 span {

            color: #d4af37;
        }


        .about-hero p {

            max-width: 700px;

            margin: auto;

            font-size: 18px;

            line-height: 1.8;

            color: #e5e7eb;
        }


        /* =========================
           ABOUT CONTAINER
        ========================= */

        .about-container {

            max-width: 1100px;

            margin: auto;

            padding: 70px 20px;
        }


        /* =========================
           INTRO SECTION
        ========================= */

        .about-intro {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 50px;

            align-items: center;

            margin-bottom: 70px;
        }


        .about-intro h2 {

            font-size: 34px;

            color: #111827;

            margin-bottom: 20px;
        }


        .about-intro h2 span {

            color: #b8860b;
        }


        .about-intro p {

            color: #555;

            line-height: 1.8;

            font-size: 16px;

            margin-bottom: 15px;
        }


        /* =========================
           MISSION BOX
        ========================= */

        .about-box {

            background: #111827;

            color: white;

            padding: 45px 35px;

            border-radius: 18px;

            box-shadow:
                0 12px 30px
                rgba(0,0,0,0.12);
        }


        .about-box h3 {

            color: #d4af37;

            font-size: 25px;

            margin-top: 0;
        }


        .about-box p {

            color: #e5e7eb;

            line-height: 1.8;
        }


        /* =========================
           FEATURES
        ========================= */

        .features-title {

            text-align: center;

            margin-bottom: 35px;
        }


        .features-title h2 {

            font-size: 32px;

            color: #111827;

            margin-bottom: 10px;
        }


        .features-title p {

            color: #666;
        }


        .features {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 25px;
        }


        .feature-card {

            background: white;

            padding: 35px 25px;

            text-align: center;

            border-radius: 15px;

            box-shadow:
                0 8px 25px
                rgba(0,0,0,0.08);

            transition: 0.3s;
        }


        .feature-card:hover {

            transform:
                translateY(-6px);
        }


        .feature-icon {

            width: 65px;

            height: 65px;

            margin:
                0 auto 18px;

            border-radius: 50%;

            background: #111827;

            color: #d4af37;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 28px;
        }


        .feature-card h3 {

            color: #111827;

            margin-bottom: 10px;
        }


        .feature-card p {

            color: #666;

            line-height: 1.6;
        }


        /* =========================
           CTA
        ========================= */

        .about-cta {

            margin-top: 70px;

            padding: 55px 25px;

            text-align: center;

            background:
                linear-gradient(
                    135deg,
                    #111827,
                    #242424
                );

            border-radius: 20px;

            color: white;
        }


        .about-cta h2 {

            font-size: 32px;

            margin-top: 0;
        }


        .about-cta p {

            color: #ddd;

            margin-bottom: 25px;
        }


        .about-btn {

            display: inline-block;

            background: #d4af37;

            color: #111827;

            padding: 13px 28px;

            border-radius: 8px;

            text-decoration: none;

            font-weight: bold;

            transition: 0.3s;
        }


        .about-btn:hover {

            background: #b8860b;

            color: white;
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

        @media (max-width: 768px) {

            .about-hero h1 {

                font-size: 34px;
            }


            .about-intro {

                grid-template-columns: 1fr;

                gap: 30px;
            }


            .features {

                grid-template-columns: 1fr;
            }


            .about-intro h2 {

                font-size: 28px;
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


<body class="about-page">


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
     ABOUT HERO
========================= -->

<section class="about-hero">

    <h1>

        About

        <span>
            QAMROSH
        </span>

    </h1>


    <p>

        Discover stylish, comfortable and quality
        ladies garments designed to bring elegance
        and confidence to every occasion.

    </p>

</section>



<!-- =========================
     ABOUT CONTENT
========================= -->

<main class="about-container">


    <section class="about-intro">


        <div>

            <h2>

                Fashion With

                <span>
                    Quality & Style
                </span>

            </h2>


            <p>

                Welcome to QAMROSH,
                your trusted destination for beautiful
                ladies stitched garments.

            </p>


            <p>

                Our goal is to provide customers with
                stylish designs, comfortable fabrics
                and quality products at reasonable prices.

            </p>


            <p>

                We believe that fashion should be elegant,
                comfortable and accessible. That's why
                we carefully focus on quality and
                customer satisfaction.

            </p>

        </div>


        <div class="about-box">

            <h3>
                Our Mission
            </h3>


            <p>

                Our mission is to make quality ladies
                fashion easily accessible through a
                simple and reliable online shopping
                experience.

            </p>


            <p>

                From selecting your favorite design
                to receiving your order, we aim to make
                every step convenient for our customers.

            </p>

        </div>


    </section>



    <!-- =========================
         WHY CHOOSE US
    ========================= -->

    <section>


        <div class="features-title">

            <h2>
                Why Choose Us?
            </h2>


            <p>

                We focus on the things that matter
                most to our customers.

            </p>

        </div>



        <div class="features">


            <div class="feature-card">

                <div class="feature-icon">
                    ✦
                </div>


                <h3>
                    Quality Products
                </h3>


                <p>

                    We focus on providing well-designed
                    and quality ladies stitched garments.

                </p>

            </div>



            <div class="feature-card">

                <div class="feature-icon">
                    ♡
                </div>


                <h3>
                    Customer Satisfaction
                </h3>


                <p>

                    Your satisfaction matters to us.
                    We aim to provide a smooth and
                    reliable shopping experience.

                </p>

            </div>



            <div class="feature-card">

                <div class="feature-icon">
                    ✓
                </div>


                <h3>
                    Easy Shopping
                </h3>


                <p>

                    Browse products, add your favorite
                    items to cart and place your order
                    with ease.

                </p>

            </div>


        </div>

    </section>



    <!-- =========================
         CTA
    ========================= -->

    <section class="about-cta">

        <h2>
            Find Your Perfect Style
        </h2>


        <p>

            Explore our ladies collection and discover
            something special for yourself.

        </p>


        <a
            href="products.php"
            class="about-btn"
        >

            Explore Products

        </a>

    </section>


</main>



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

                Visit our Contact page

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
