<?php
session_start();
require_once "config/database.php";

$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

if ($category > 0) {
    $result = $conn->query("SELECT * FROM products WHERE category_id = $category");
} else {
    $result = $conn->query("SELECT * FROM products");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Maan Ghafar Garments</title>

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

<section class="products">

    <h2>Our Products</h2>

    <div class="category-container">

        <a href="products.php" class="all-products-btn">
            All Products
        </a>

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

    <div class="product-container">

        <?php while ($product = $result->fetch_assoc()): ?>

            <div class="product-card">

                <div class="product-image">
                    <img
                        src="assets/image/<?php echo htmlspecialchars($product['image']); ?>"
                        alt=""
                    >
                </div>

                <h3>
                    <?php echo htmlspecialchars($product['product_name']); ?>
                </h3>

                <p>
                    <?php echo htmlspecialchars($product['description']); ?>
                </p>

                <span>
                    Rs. <?php echo number_format($product['price']); ?>
                </span>

                <a
                    href="product-details.php?id=<?php echo $product['product_id']; ?>"
                    class="view-details-btn"
                >
                    View Details
                </a>

            </div>

        <?php endwhile; ?>

    </div>

</section>

</body>
</html>