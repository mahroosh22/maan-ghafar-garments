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

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT id, name, email
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

    header("Location: ../login.php");
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
        My Account - QAMROSH
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            color: #222;
        }

        .navbar {
            background: #111;
            color: white;

            padding: 18px 30px;

            display: flex;
            justify-content: space-between;
            align-items: center;

            flex-wrap: wrap;
            gap: 15px;
        }

        .navbar h2 {
            margin: 0;
        }

        .nav-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: white;
            text-decoration: none;

            padding: 9px 14px;

            border-radius: 6px;
        }

        .nav-links a:hover {
            background: #333;
        }

        .logout {
            background: #c0392b !important;
        }

        .container {
            max-width: 1000px;

            margin: 40px auto;

            padding: 20px;
        }

        .welcome {
            background: white;

            padding: 30px;

            border-radius: 12px;

            box-shadow:
                0 5px 20px rgba(0,0,0,0.08);

            margin-bottom: 25px;
        }

        .welcome h1 {
            margin-top: 0;
        }

        .welcome p {
            color: #666;
        }

        .dashboard-grid {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 20px;

            margin-bottom: 25px;
        }

        .dashboard-card {
            background: white;

            padding: 25px;

            border-radius: 12px;

            text-align: center;

            box-shadow:
                0 5px 20px rgba(0,0,0,0.08);

            text-decoration: none;

            color: #222;

            transition: 0.3s;
        }

        .dashboard-card:hover {
            transform: translateY(-4px);

            box-shadow:
                0 8px 25px rgba(0,0,0,0.12);
        }

        .dashboard-card .icon {
            font-size: 35px;

            margin-bottom: 10px;
        }

        .dashboard-card h3 {
            margin: 10px 0;
        }

        .dashboard-card p {
            color: #666;

            margin-bottom: 0;

            line-height: 1.4;
        }

        .account-card {
            background: white;

            padding: 30px;

            border-radius: 12px;

            box-shadow:
                0 5px 20px rgba(0,0,0,0.08);
        }

        .account-card h2 {
            margin-top: 0;
        }

        .info {
            padding: 14px 0;

            border-bottom: 1px solid #eee;
        }

        .info:last-child {
            border-bottom: none;
        }

        .label {
            font-weight: bold;

            display: inline-block;

            width: 100px;
        }

        @media (max-width: 850px) {

            .dashboard-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

        }

        @media (max-width: 600px) {

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .navbar {
                padding: 15px;
            }

            .nav-links {
                width: 100%;
            }

            .nav-links a {
                padding: 8px 10px;
            }

        }

    </style>

</head>

<body>


<div class="navbar">

    <h2>
        QAMROSH
    </h2>

    <div class="nav-links">

        <a href="../index.php">
            Home
        </a>

        <a href="../products.php">
            Products
        </a>

        <a href="../cart.php">
            Cart
        </a>

        <a href="../my-orders.php">
            My Orders
        </a>

        <a href="profile.php">
            My Profile
        </a>

        <a
            href="../logout.php"
            class="logout"
        >
            Logout
        </a>

    </div>

</div>


<div class="container">


    <div class="welcome">

        <h1>
            Welcome,
            <?php echo htmlspecialchars($user['name']); ?>! 👋
        </h1>

        <p>
            Welcome to your QAMROSH customer account.
        </p>

    </div>


    <div class="dashboard-grid">


        <!-- Products -->

        <a
            href="../products.php"
            class="dashboard-card"
        >

            <div class="icon">
                🛍️
            </div>

            <h3>
                Products
            </h3>

            <p>
                Browse our latest garments.
            </p>

        </a>


        <!-- Cart -->

        <a
            href="../cart.php"
            class="dashboard-card"
        >

            <div class="icon">
                🛒
            </div>

            <h3>
                My Cart
            </h3>

            <p>
                View your shopping cart.
            </p>

        </a>


        <!-- Orders -->

        <a
            href="../my-orders.php"
            class="dashboard-card"
        >

            <div class="icon">
                📦
            </div>

            <h3>
                My Orders
            </h3>

            <p>
                View your placed orders.
            </p>

        </a>


        <!-- Profile -->

        <a
            href="profile.php"
            class="dashboard-card"
        >

            <div class="icon">
                👤
            </div>

            <h3>
                My Profile
            </h3>

            <p>
                Update your name and email.
            </p>

        </a>


    </div>


    <div class="account-card">

        <h2>
            Account Information
        </h2>


        <div class="info">

            <span class="label">
                Name:
            </span>

            <?php
            echo htmlspecialchars($user['name']);
            ?>

        </div>


        <div class="info">

            <span class="label">
                Email:
            </span>

            <?php
            echo htmlspecialchars($user['email']);
            ?>

        </div>


    </div>


</div>


</body>

</html>