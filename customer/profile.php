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

$error = "";
$success = "";


/* =========================================
   CSRF TOKEN
========================================= */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(
        random_bytes(32)
    );
}

$csrf_token = $_SESSION['csrf_token'];


/* =========================================
   SAFE OUTPUT HELPER
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
   GET CUSTOMER INFORMATION
========================================= */

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        email
    FROM users
    WHERE id = ?
    AND role = 'customer'
    LIMIT 1
");

if (!$stmt) {

    die("Database error.");

}

$stmt->bind_param("i", $user_id);

if (!$stmt->execute()) {

    $stmt->close();

    die("Unable to load profile.");

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


/* =========================================
   UPDATE PROFILE
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $posted_token = $_POST['csrf_token'] ?? '';

    if (
        empty($posted_token) ||
        !hash_equals(
            $csrf_token,
            $posted_token
        )
    ) {

        $error =
            "Invalid security token. Please try again.";

    } else {

        $name = trim(
            $_POST['name'] ?? ''
        );

        $email = trim(
            $_POST['email'] ?? ''
        );


        /* =========================
           VALIDATION
        ========================= */

        if (
            $name === '' ||
            $email === ''
        ) {

            $error =
                "Please fill all fields.";

        } elseif (
            mb_strlen($name) < 2
        ) {

            $error =
                "Name must contain at least 2 characters.";

        } elseif (
            mb_strlen($name) > 100
        ) {

            $error =
                "Name is too long.";

        } elseif (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $error =
                "Please enter a valid email address.";

        } elseif (
            mb_strlen($email) > 150
        ) {

            $error =
                "Email address is too long.";

        } else {

            /* =========================
               CHECK DUPLICATE EMAIL
            ========================= */

            $check_stmt = $conn->prepare("
                SELECT id
                FROM users
                WHERE LOWER(TRIM(email))
                    = LOWER(TRIM(?))
                AND id != ?
                LIMIT 1
            ");


            if (!$check_stmt) {

                $error =
                    "Database error.";

            } else {

                $check_stmt->bind_param(
                    "si",
                    $email,
                    $user_id
                );


                if (!$check_stmt->execute()) {

                    $error =
                        "Unable to check email address.";

                    $check_stmt->close();

                } else {

                    $check_result =
                        $check_stmt->get_result();


                    if (
                        $check_result->num_rows > 0
                    ) {

                        $error =
                            "This email is already registered.";

                        $check_stmt->close();

                    } else {

                        $check_stmt->close();


                        /* =========================
                           UPDATE USER
                        ========================= */

                        $update_stmt = $conn->prepare("
                            UPDATE users
                            SET
                                name = ?,
                                email = ?
                            WHERE id = ?
                            AND role = 'customer'
                        ");


                        if (!$update_stmt) {

                            $error =
                                "Database error.";

                        } else {

                            $update_stmt->bind_param(
                                "ssi",
                                $name,
                                $email,
                                $user_id
                            );


                            if (
                                $update_stmt->execute()
                            ) {

                                $success =
                                    "Your profile has been updated successfully.";

                                /*
                                    Keep session information
                                    synchronized with database.
                                */

                                $_SESSION['user_name'] =
                                    $name;

                                $_SESSION['user_email'] =
                                    $email;


                                $user['name'] =
                                    $name;

                                $user['email'] =
                                    $email;

                            } else {

                                $error =
                                    "Profile could not be updated.";

                            }


                            $update_stmt->close();
                        }
                    }
                }
            }
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
        My Profile - QAMROSH
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f7f6f3;
            color: #222;
        }


        /* =========================
           NAVBAR
        ========================== */

        .navbar {
            background: #111;
            color: #fff;
            padding: 17px 6%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .brand {
            color: #d4af37;
            text-decoration: none;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: .3px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 7px;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: #fff;
            text-decoration: none;
            padding: 9px 13px;
            border-radius: 7px;
            font-size: 14px;
            transition: .2s ease;
        }

        .nav-links a:hover {
            background: #2b2b2b;
            color: #d4af37;
        }

        .nav-links .active {
            color: #d4af37;
        }

        .logout {
            background: #9d2f27 !important;
        }

        .logout:hover {
            background: #c0392b !important;
            color: #fff !important;
        }


        /* =========================
           PAGE
        ========================== */

        .page {
            min-height: calc(100vh - 80px);
            padding: 55px 20px 70px;
        }

        .profile-wrapper {
            max-width: 850px;
            margin: 0 auto;
        }

        .page-heading {
            text-align: center;
            margin-bottom: 30px;
        }

        .page-heading .small-title {
            color: #b28b19;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .page-heading h1 {
            margin: 0;
            font-size: 34px;
            color: #111;
        }

        .page-heading p {
            margin: 10px 0 0;
            color: #777;
            font-size: 15px;
        }


        /* =========================
           PROFILE CARD
        ========================== */

        .profile-card {
            background: #fff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow:
                0 12px 35px
                rgba(0, 0, 0, .09);
            border: 1px solid #eee;
        }

        .profile-top {
            background: linear-gradient(
                135deg,
                #111 0%,
                #1d1d1d 60%,
                #292929 100%
            );
            padding: 32px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #d4af37;
            color: #111;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            font-weight: bold;
            flex-shrink: 0;
        }

        .profile-top h2 {
            color: #fff;
            margin: 0 0 6px;
            font-size: 23px;
        }

        .profile-top p {
            color: #ccc;
            margin: 0;
            font-size: 14px;
        }

        .profile-content {
            padding: 35px;
        }


        /* =========================
           ALERTS
        ========================== */

        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 22px;
            font-size: 14px;
            font-weight: 600;
        }

        .error {
            background: #fff0f0;
            color: #a12626;
            border: 1px solid #f0caca;
        }

        .success {
            background: #effaf1;
            color: #24713a;
            border: 1px solid #c9e8cf;
        }


        /* =========================
           FORM
        ========================== */

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }

        .form-group {
            margin-bottom: 4px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 700;
            color: #333;
        }

        input {
            width: 100%;
            padding: 14px 15px;
            border: 1px solid #ddd;
            border-radius: 9px;
            background: #fafafa;
            color: #222;
            font-size: 15px;
            transition: .2s ease;
        }

        input:focus {
            outline: none;
            border-color: #c7a22c;
            background: #fff;
            box-shadow:
                0 0 0 3px
                rgba(212, 175, 55, .12);
        }

        .update-btn {
            width: 100%;
            border: none;
            margin-top: 25px;
            padding: 14px 20px;
            border-radius: 9px;
            background: #111;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: .2s ease;
        }

        .update-btn:hover {
            background: #d4af37;
            color: #111;
        }


        /* =========================
           QUICK ACTIONS
        ========================== */

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            margin-top: 28px;
        }

        .quick-action {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            color: #222;
            text-decoration: none;
            background: #fafafa;
            font-size: 14px;
            font-weight: 700;
            transition: .2s ease;
        }

        .quick-action:hover {
            border-color: #d4af37;
            background: #fffdf5;
            transform: translateY(-2px);
        }

        .quick-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: #111;
            color: #d4af37;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }


        /* =========================
           FOOTER
        ========================== */

        .footer {
            background: #111;
            color: #ddd;
            margin-top: 0;
            padding: 45px 6% 20px;
        }

        .footer-container {
            max-width: 1200px;
            margin: auto;
            display: grid;
            grid-template-columns:
                2fr 1fr 1fr 1.4fr;
            gap: 35px;
        }

        .footer h3,
        .footer h4 {
            color: #fff;
            margin-top: 0;
            margin-bottom: 15px;
        }

        .footer h3 {
            color: #d4af37;
            font-size: 21px;
        }

        .footer p {
            color: #aaa;
            line-height: 1.7;
            font-size: 14px;
            margin: 0;
        }

        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .footer-links a {
            color: #aaa;
            text-decoration: none;
            font-size: 14px;
            transition: .2s ease;
        }

        .footer-links a:hover {
            color: #d4af37;
            padding-left: 3px;
        }

        .footer-contact p {
            margin-bottom: 8px;
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 35px auto 0;
            padding-top: 20px;
            border-top: 1px solid #2d2d2d;
            text-align: center;
            color: #888;
            font-size: 13px;
        }


        /* =========================
           RESPONSIVE
        ========================== */

        @media (max-width: 850px) {

            .footer-container {
                grid-template-columns: 1fr 1fr;
            }

        }


        @media (max-width: 650px) {

            .navbar {
                padding: 16px 20px;
            }

            .brand {
                width: 100%;
                text-align: center;
            }

            .nav-links {
                width: 100%;
                justify-content: center;
            }

            .nav-links a {
                font-size: 13px;
                padding: 8px 10px;
            }

            .page {
                padding: 35px 15px 50px;
            }

            .page-heading h1 {
                font-size: 28px;
            }

            .profile-top {
                padding: 25px 20px;
            }

            .profile-content {
                padding: 25px 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 17px;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .footer {
                padding: 40px 20px 20px;
            }

            .footer-container {
                grid-template-columns: 1fr;
                gap: 25px;
            }

        }

    </style>

</head>

<body>


<!-- =========================
     NAVBAR
========================== -->

<header class="navbar">

    <a
        href="../index.php"
        class="brand"
    >
        QAMROSH
    </a>


    <nav class="nav-links">

        <a href="../index.php">
            Home
        </a>

        <a href="../products.php">
            Products
        </a>

        <a href="../cart.php">
            Cart
        </a>

        <a href="orders.php">
            My Orders
        </a>

        <a
            href="profile.php"
            class="active"
        >
            My Account
        </a>

        <a
            href="../logout.php"
            class="logout"
        >
            Logout
        </a>

    </nav>

</header>


<!-- =========================
     PROFILE PAGE
========================== -->

<main class="page">

    <div class="profile-wrapper">


        <div class="page-heading">

            <div class="small-title">
                Customer Area
            </div>

            <h1>
                My Profile
            </h1>

            <p>
                Manage your personal information
                and account details.
            </p>

        </div>



        <section class="profile-card">


            <div class="profile-top">

                <div class="profile-icon">
                    👤
                </div>


                <div>

                    <h2>
                        <?php
                        echo e($user['name']);
                        ?>
                    </h2>

                    <p>
                        <?php
                        echo e($user['email']);
                        ?>
                    </p>

                </div>

            </div>



            <div class="profile-content">


                <?php if ($error !== ''): ?>

                    <div class="alert error">

                        <?php
                        echo e($error);
                        ?>

                    </div>

                <?php endif; ?>



                <?php if ($success !== ''): ?>

                    <div class="alert success">

                        <?php
                        echo e($success);
                        ?>

                    </div>

                <?php endif; ?>



                <form
                    method="POST"
                    autocomplete="off"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php
                            echo e($csrf_token);
                        ?>"
                    >


                    <div class="form-grid">


                        <div class="form-group">

                            <label for="name">
                                Full Name
                            </label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                maxlength="100"
                                value="<?php
                                    echo e($user['name']);
                                ?>"
                                required
                            >

                        </div>



                        <div class="form-group">

                            <label for="email">
                                Email Address
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                maxlength="150"
                                value="<?php
                                    echo e($user['email']);
                                ?>"
                                required
                            >

                        </div>

                    </div>



                    <button
                        type="submit"
                        class="update-btn"
                    >
                        Update Profile
                    </button>

                </form>



                <div class="quick-actions">


                    <a
                        href="profile.php"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            👤
                        </span>

                        <span>
                            My Profile
                        </span>

                    </a>



                    <a
                        href="orders.php"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            📦
                        </span>

                        <span>
                            My Orders
                        </span>

                    </a>



                    <a
                        href="../products.php"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            🛍
                        </span>

                        <span>
                            Continue Shopping
                        </span>

                    </a>



                    <a
                        href="../contact.php"
                        class="quick-action"
                    >

                        <span class="quick-icon">
                            ✉
                        </span>

                        <span>
                            Contact Support
                        </span>

                    </a>


                </div>


            </div>

        </section>


    </div>

</main>


<!-- =========================
     FOOTER
========================== -->

<footer class="footer">


    <div class="footer-container">


        <div>

            <h3>
                QAMROSH
            </h3>

            <p>
                Discover stylish and elegant ladies
                garments designed to make every
                occasion special.
            </p>

        </div>



        <div>

            <h4>
                Quick Links
            </h4>

            <div class="footer-links">

                <a href="../index.php">
                    Home
                </a>

                <a href="../products.php">
                    Products
                </a>

                <a href="../about.php">
                    About Us
                </a>

                <a href="../contact.php">
                    Contact Us
                </a>

            </div>

        </div>



        <div>

            <h4>
                Customer Area
            </h4>

            <div class="footer-links">

                <a href="profile.php">
                    My Profile
                </a>

                <a href="orders.php">
                    My Orders
                </a>

                <a href="../cart.php">
                    Shopping Cart
                </a>

                <a href="../logout.php">
                    Logout
                </a>

            </div>

        </div>



        <div class="footer-contact">

            <h4>
                Contact & Support
            </h4>

            <p>
                📧 qamrosh@gmail.com
            </p>

            <p>
                🛍 Ladies Fashion Collection
            </p>

            <p>
                💬 We're here to help
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