
<?php
session_start();
require_once "config/database.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maan Ghafar Garments</title>

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
        <a href="my-orders.php">My Orders</a>

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


<section class="hero" id="home">

    <div class="hero-content">

        <h1>Welcome to Maan Ghafar Garments</h1>

        <p>Quality Garments for Every Style</p>

        <a href="products.php" class="hero-btn">
            Shop Now
        </a>

    </div>

</section>


<section class="categories">

    <h2>Shop By Category</h2>

    <a href="products.php" class="all-products-btn">
        All Products
    </a>

    <div class="category-container">

        <a href="products.php?category=1" class="category-card">

            <img src="assets/image/suite 1.jpg" alt="Lawn Dresses">

            <h3>Lawn Dresses</h3>

        </a>


        <a href="products.php?category=2" class="category-card">

            <img src="assets/image/suite 02.jpg" alt="Western Dresses">

            <h3>Western Dresses</h3>

        </a>


        <a href="products.php?category=3" class="category-card">

            <img src="assets/image/suite 03.jpg" alt="Maxi Dresses">

            <h3>Maxi Dresses</h3>

        </a>

    </div>

</section>


<footer class="footer">

    <p>&copy; 2026 Maan Ghafar Garments. All Rights Reserved.</p>

</footer>


</body>
</html>

