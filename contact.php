
<?php
session_start();
require_once "config/database.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Maan Ghafar Garments</title>

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


<section class="contact">

    <h2>Contact Us</h2>

    <div class="contact-info">

        <p>
            <strong>Phone:</strong> 0300-0000000
        </p>

        <p>
            <strong>Email:</strong> info@maanghafargarments.com
        </p>

        <p>
            <strong>Address:</strong> Pakistan
        </p>

    </div>

</section>


<footer class="footer">

    <p>&copy; 2026 Maan Ghafar Garments. All Rights Reserved.</p>

</footer>

</body>
</html>

