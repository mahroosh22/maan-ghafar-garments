<?php
session_start();
require_once "config/database.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Maan Ghafar Garments</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

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

<section class="about">

    <div class="about-content">

        <h2>About Maan Ghafar Garments</h2>

        <p>
            Maan Ghafar Garments is committed to providing
            high-quality garments with modern style, comfort,
            and excellent customer satisfaction.
        </p>

        <p>
            We bring quality fashion and reliable service
            together under one trusted name.
        </p>

    </div>

</section>

<footer class="footer">

    <p>&copy; 2026 Maan Ghafar Garments. All Rights Reserved.</p>

</footer>

</body>
</html>

