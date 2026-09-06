<?php

session_start();

require_once "config/database.php";


/* =========================================
   HELPER
========================================= */

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================
   CSRF TOKEN
========================================= */

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));
}


/* =========================================
   VARIABLES
========================================= */

$success = "";
$error = "";

$user_email = "";
$user_name = "";

$unread_replies = 0;

$customer_messages = [];


/* =========================================
   GET LOGGED-IN CUSTOMER INFORMATION
========================================= */

if (isset($_SESSION['user_id'])) {

    $user_id = (int) $_SESSION['user_id'];

    if ($user_id > 0) {

        $stmt = $conn->prepare("
            SELECT
                name,
                email
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        if ($stmt) {

            $stmt->bind_param(
                "i",
                $user_id
            );

            if ($stmt->execute()) {

                $result = $stmt->get_result();

                if ($result->num_rows === 1) {

                    $user = $result->fetch_assoc();

                    $user_name =
                        trim($user['name'] ?? '');

                    $user_email =
                        trim($user['email'] ?? '');
                }
            }

            $stmt->close();
        }
    }
}


/* =========================================
   MESSAGE NOTIFICATION
========================================= */

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


/* =========================================
   HANDLE CONTACT FORM
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $csrf_token =
        $_POST['csrf_token'] ?? '';

    if (
        $csrf_token === '' ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals(
            $_SESSION['csrf_token'],
            $csrf_token
        )
    ) {

        $error =
            "Invalid request. Please refresh the page and try again.";

    } else {

        $name =
            trim($_POST['name'] ?? '');

        $email =
            trim($_POST['email'] ?? '');

        $subject =
            trim($_POST['subject'] ?? '');

        $message =
            trim($_POST['message'] ?? '');


        /* =========================================
           VALIDATION
        ========================================= */

        if (
            $name === '' ||
            $email === '' ||
            $subject === '' ||
            $message === ''
        ) {

            $error =
                "Please fill in all fields.";

        } elseif (mb_strlen($name) > 100) {

            $error =
                "Name must not exceed 100 characters.";

        } elseif (mb_strlen($email) > 150) {

            $error =
                "Email address is too long.";

        } elseif (mb_strlen($subject) > 200) {

            $error =
                "Subject must not exceed 200 characters.";

        } elseif (mb_strlen($message) > 5000) {

            $error =
                "Message must not exceed 5000 characters.";

        } elseif (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $error =
                "Please enter a valid email address.";

        } else {

            /* =========================================
               INSERT MESSAGE
            ========================================= */

            $stmt = $conn->prepare("
                INSERT INTO contact_messages
                (
                    name,
                    email,
                    subject,
                    message
                )
                VALUES (?, ?, ?, ?)
            ");

            if ($stmt) {

                $stmt->bind_param(
                    "ssss",
                    $name,
                    $email,
                    $subject,
                    $message
                );

                if ($stmt->execute()) {

                    $success =
                        "Thank you! Your message has been sent successfully.";

                    $_POST = [];

                    /*
                    |--------------------------------------
                    | Refresh CSRF token after submission
                    |--------------------------------------
                    */

                    $_SESSION['csrf_token'] =
                        bin2hex(random_bytes(32));

                } else {

                    error_log(
                        "Contact message insert failed: " .
                        $stmt->error
                    );

                    $error =
                        "Something went wrong. Please try again.";
                }

                $stmt->close();

            } else {

                error_log(
                    "Contact message prepare failed: " .
                    $conn->error
                );

                $error =
                    "Unable to process your message right now.";
            }
        }
    }
}


/* =========================================
   GET CUSTOMER MESSAGES + ADMIN REPLIES
========================================= */

if ($user_email !== "") {

    $stmt = $conn->prepare("
        SELECT
            message_id,
            name,
            email,
            subject,
            message,
            admin_reply,
            reply_seen,
            status,
            created_at
        FROM contact_messages
        WHERE LOWER(TRIM(email)) = LOWER(TRIM(?))
        ORDER BY created_at DESC
    ");

    if ($stmt) {

        $stmt->bind_param(
            "s",
            $user_email
        );

        if ($stmt->execute()) {

            $result =
                $stmt->get_result();

            while (
                $row =
                $result->fetch_assoc()
            ) {

                $customer_messages[] =
                    $row;
            }
        }

        $stmt->close();
    }


    /* =========================================
       MARK ADMIN REPLIES AS SEEN
    ========================================= */

    $stmt = $conn->prepare("
        UPDATE contact_messages
        SET reply_seen = 1
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
        Contact Us - Maan Ghafar Garments
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <style>

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
           CONTACT HERO
        ========================================= */

        .contact-hero {

            min-height: 330px;

            display: flex;

            align-items: center;

            justify-content: center;

            text-align: center;

            padding: 60px 20px;

            background:
                linear-gradient(
                    rgba(17, 24, 39, 0.82),
                    rgba(17, 24, 39, 0.82)
                ),
                url("https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=1600&q=80")
                center/cover no-repeat;

            color: white;
        }


        .contact-hero h1 {

            font-size: 48px;

            margin-bottom: 15px;
        }


        .contact-hero p {

            font-size: 18px;

            color: #ddd;

            max-width: 650px;

            margin: auto;

            line-height: 1.7;
        }


        /* =========================================
           CONTACT SECTION
        ========================================= */

        .contact-section {

            max-width: 1150px;

            margin: 70px auto;

            padding: 0 25px;

            display: grid;

            grid-template-columns:
                0.9fr 1.1fr;

            gap: 45px;
        }


        .contact-info,
        .contact-form-box {

            background: white;

            padding: 35px;

            border-radius: 15px;

            box-shadow:
                0 8px 30px
                rgba(0,0,0,0.08);
        }


        .contact-info h2,
        .contact-form-box h2 {

            margin-top: 0;

            margin-bottom: 20px;

            color: #111827;
        }


        .contact-info p {

            color: #666;

            line-height: 1.7;

            margin-bottom: 22px;
        }


        .contact-item {

            margin-bottom: 22px;
        }


        .contact-item strong {

            display: block;

            color: #111827;

            margin-bottom: 5px;
        }


        .contact-item span {

            color: #666;
        }


        /* =========================================
           CONTACT FORM
        ========================================= */

        .contact-form {

            display: flex;

            flex-direction: column;

            gap: 16px;
        }


        .contact-form label {

            font-weight: 600;

            color: #333;
        }


        .contact-form input,
        .contact-form textarea {

            width: 100%;

            box-sizing: border-box;

            padding: 13px 15px;

            border: 1px solid #ddd;

            border-radius: 8px;

            font-size: 15px;

            font-family: Arial, sans-serif;

            outline: none;
        }


        .contact-form input:focus,
        .contact-form textarea:focus {

            border-color: #b8860b;
        }


        .contact-form textarea {

            min-height: 150px;

            resize: vertical;
        }


        .contact-btn {

            border: none;

            padding: 14px 25px;

            background: #b8860b;

            color: white;

            border-radius: 8px;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;

            transition: 0.3s;
        }


        .contact-btn:hover {

            background: #8f6808;

            transform: translateY(-2px);
        }


        /* =========================================
           ALERTS
        ========================================= */

        .alert-success {

            background: #e8f7ee;

            color: #187a3d;

            padding: 13px 15px;

            border-radius: 8px;

            margin-bottom: 20px;
        }


        .alert-error {

            background: #fdecec;

            color: #b42318;

            padding: 13px 15px;

            border-radius: 8px;

            margin-bottom: 20px;
        }


        /* =========================================
           SUPPORT BOX
        ========================================= */

        .support-box {

            margin-top: 25px;

            padding: 18px;

            background: #f8f6ef;

            border-left: 4px solid #b8860b;

            border-radius: 6px;

            color: #555;

            line-height: 1.6;
        }


        /* =========================================
           CUSTOMER MESSAGES
        ========================================= */

        .customer-messages {

            max-width: 1150px;

            margin: 0 auto 70px;

            padding: 0 25px;
        }


        .customer-messages-box {

            background: white;

            padding: 35px;

            border-radius: 15px;

            box-shadow:
                0 8px 30px
                rgba(0,0,0,0.08);
        }


        .customer-messages-box h2 {

            margin: 0 0 8px;

            color: #111827;
        }


        .messages-intro {

            color: #777;

            margin-bottom: 25px;

            line-height: 1.6;
        }


        .customer-message-card {

            border: 1px solid #e5e7eb;

            border-radius: 12px;

            padding: 22px;

            margin-bottom: 18px;

            background: #fafafa;
        }


        .customer-message-card:last-child {

            margin-bottom: 0;
        }


        .customer-message-top {

            display: flex;

            justify-content: space-between;

            gap: 20px;

            align-items: flex-start;
        }


        .customer-message-subject {

            font-size: 17px;

            font-weight: bold;

            color: #111827;

            margin-bottom: 8px;
        }


        .customer-message-date {

            color: #888;

            font-size: 13px;

            white-space: nowrap;
        }


        .customer-message-text {

            color: #555;

            line-height: 1.7;

            white-space: pre-wrap;

            word-break: break-word;

            margin-top: 12px;
        }


        .admin-reply {

            margin-top: 18px;

            padding: 17px;

            background: #f1f8f3;

            border-left: 4px solid #187a3d;

            border-radius: 8px;
        }


        .admin-reply-title {

            color: #187a3d;

            font-weight: bold;

            margin-bottom: 7px;
        }


        .admin-reply-text {

            color: #444;

            line-height: 1.7;

            white-space: pre-wrap;

            word-break: break-word;
        }


        .waiting-reply {

            margin-top: 18px;

            padding: 13px 15px;

            background: #fff8e6;

            color: #8a6500;

            border-radius: 8px;

            font-size: 14px;
        }


        .message-status {

            display: inline-block;

            margin-top: 15px;

            padding: 5px 11px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;
        }


        .message-status.unread {

            background: #fff1c7;

            color: #8a6500;
        }


        .message-status.read {

            background: #e8f7ee;

            color: #187a3d;
        }


        .no-messages {

            padding: 25px;

            text-align: center;

            color: #777;

            background: #fafafa;

            border-radius: 10px;
        }


        /* =========================================
           FOOTER
        ========================================= */

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


        /* =========================================
           MOBILE
        ========================================= */

        @media (max-width: 768px) {

            .contact-hero h1 {

                font-size: 36px;
            }


            .contact-section {

                grid-template-columns: 1fr;

                margin: 45px auto;
            }


            .customer-messages {

                margin-bottom: 45px;
            }


            .customer-message-top {

                flex-direction: column;

                gap: 8px;
            }


            .customer-message-date {

                white-space: normal;
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


<!-- =========================================
     NAVBAR
========================================= -->

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



<!-- =========================================
     HERO
========================================= -->

<section class="contact-hero">

    <div>

        <h1>
            Get In Touch
        </h1>


        <p>

            Have a question about our products
            or your order?

            Send us a message and our team
            will be happy to help.

        </p>

    </div>

</section>



<!-- =========================================
     CONTACT SECTION
========================================= -->

<section class="contact-section">


    <div class="contact-info">

        <h2>
            Contact Information
        </h2>


        <p>

            We're here to help you with your
            shopping experience.

            Feel free to contact
            Maan Ghafar Garments anytime.

        </p>


        <div class="contact-item">

            <strong>
                📧 Email
            </strong>

            <span>
                info@maanghafargarments.com
            </span>

        </div>


        <div class="contact-item">

            <strong>
                📞 Phone
            </strong>

            <span>
                0300-0000000
            </span>

        </div>


        <div class="contact-item">

            <strong>
                📍 Address
            </strong>

            <span>
                Pakistan
            </span>

        </div>


        <div class="support-box">

            <strong>
                Customer Support
            </strong>

            <br>

            Need help with an order,
            product, size or anything else?

            Send us a message and we'll
            get back to you.

        </div>

    </div>



    <div class="contact-form-box">

        <h2>
            Send Us a Message
        </h2>


        <?php if ($success !== ""): ?>

            <div class="alert-success">
                <?= e($success) ?>
            </div>

        <?php endif; ?>


        <?php if ($error !== ""): ?>

            <div class="alert-error">
                <?= e($error) ?>
            </div>

        <?php endif; ?>


        <form
            class="contact-form"
            action="contact.php"
            method="POST"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION['csrf_token']) ?>"
            >


            <label for="name">
                Your Name
            </label>


            <input
                type="text"
                id="name"
                name="name"
                placeholder="Enter your name"
                maxlength="100"
                value="<?= e(
                    $_POST['name']
                    ?? $user_name
                ) ?>"
                autocomplete="name"
                required
            >


            <label for="email">
                Email Address
            </label>


            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your email"
                maxlength="150"
                value="<?= e(
                    $_POST['email']
                    ?? $user_email
                ) ?>"
                autocomplete="email"
                required
            >


            <label for="subject">
                Subject
            </label>


            <input
                type="text"
                id="subject"
                name="subject"
                placeholder="Enter message subject"
                maxlength="200"
                value="<?= e(
                    $_POST['subject']
                    ?? ''
                ) ?>"
                required
            >


            <label for="message">
                Message
            </label>


            <textarea
                id="message"
                name="message"
                maxlength="5000"
                placeholder="Write your message here..."
                required
            ><?= e(
                $_POST['message']
                ?? ''
            ) ?></textarea>


            <button
                type="submit"
                class="contact-btn"
            >
                Send Message
            </button>

        </form>

    </div>

</section>



<!-- =========================================
     CUSTOMER MESSAGES
========================================= -->

<?php if (isset($_SESSION['user_id'])): ?>

<section class="customer-messages">

    <div class="customer-messages-box">

        <h2>
            💬 My Messages
        </h2>


        <p class="messages-intro">

            Here you can see the messages you have sent
            and any replies from Maan Ghafar Garments.

        </p>


        <?php if (empty($customer_messages)): ?>

            <div class="no-messages">

                You haven't sent any messages yet.

            </div>

        <?php else: ?>


            <?php foreach (
                $customer_messages
                as $customer_message
            ): ?>

                <div class="customer-message-card">


                    <div class="customer-message-top">

                        <div>

                            <div
                                class="customer-message-subject"
                            >

                                <?= e(
                                    $customer_message['subject']
                                ) ?>

                            </div>

                        </div>


                        <div
                            class="customer-message-date"
                        >

                            <?=
                                e(
                                    date(
                                        "d M Y, h:i A",
                                        strtotime(
                                            $customer_message[
                                                'created_at'
                                            ]
                                        )
                                    )
                                )
                            ?>

                        </div>

                    </div>


                    <div class="customer-message-text">

                        <?= e(
                            $customer_message['message']
                        ) ?>

                    </div>


                    <?php if (
                        isset(
                            $customer_message[
                                'admin_reply'
                            ]
                        )
                        &&
                        trim(
                            $customer_message[
                                'admin_reply'
                            ]
                        ) !== ''
                    ): ?>


                        <div class="admin-reply">

                            <div
                                class="admin-reply-title"
                            >

                                💬 Admin Reply

                            </div>


                            <div
                                class="admin-reply-text"
                            >

                                <?= e(
                                    $customer_message[
                                        'admin_reply'
                                    ]
                                ) ?>

                            </div>

                        </div>


                    <?php else: ?>


                        <div class="waiting-reply">

                            ⏳ Your message has been received.
                            Our team will reply soon.

                        </div>


                    <?php endif; ?>


                    <?php

                    $message_status =
                        strtolower(
                            trim(
                                $customer_message[
                                    'status'
                                ] ?? ''
                            )
                        );

                    $allowed_statuses = [
                        'unread',
                        'read'
                    ];

                    if (
                        !in_array(
                            $message_status,
                            $allowed_statuses,
                            true
                        )
                    ) {

                        $message_status = 'unread';
                    }

                    ?>


                    <span
                        class="message-status
                        <?= e($message_status) ?>"
                    >

                        <?= e(
                            ucfirst(
                                $message_status
                            )
                        ) ?>

                    </span>


                </div>

            <?php endforeach; ?>


        <?php endif; ?>


    </div>

</section>

<?php endif; ?>



<!-- =========================================
     FOOTER
========================================= -->

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


                <?php if (
                    isset($_SESSION['user_id'])
                ): ?>


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

        © <?= date("Y") ?>

        Maan Ghafar Garments.

        All Rights Reserved.

    </div>

</footer>


</body>

</html>