<?php

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
        <a href="#">Home</a>
        <a href="#">Products</a>
        <a href="#">About</a>
        <a href="#">Contact</a>
    </nav>
</header>
<section class="hero">
    <div class="hero-content">
        <h1>Welcome to Maan Ghafar Garments</h1>
        <p>Quality Garments for Every Style</p>
        <a href="#" class="hero-btn">Shop Now</a>
    </div>
</section>
<section class="categories">
    <h2>Shop By Category</h2>

    <a href="index.php" class="all-products-btn">All Products</a>

    <div class="category-container">
        <a href="?category=1" class="category-card">
    <img src="assets/image/suite 1.jpg" alt="Lawn Dresses">
    <h3>Lawn Dresses</h3>
</a>
        <a href="?category=2" class="category-card">
    <img src="assets/image/suite 02.jpg" alt="Western Dresses">
    <h3>Western Dresses</h3>
</a>

        <a href="?category=3" class="category-card">
    <img src="assets/image/suite 03.jpg" alt="Maxi Dresses">
    <h3>Maxi Dresses</h3>
</a>

    </div>
</section>
<section class="products">
    <h2>Our Products</h2>

    <div class="product-container">

        <?php
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

if ($category > 0) {
    $result = $conn->query("SELECT * FROM products WHERE category_id = $category");
} else {
    $result = $conn->query("SELECT * FROM products");
}

while ($product = $result->fetch_assoc()) {
?>

            <div class="product-card">

                <div class="product-image">
    <img src="assets/image/<?php echo htmlspecialchars($product['image']); ?>" alt="">
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

<a href="product-details.php?id=<?php echo $product['product_id']; ?>" class="view-details-btn">
    View Details
</a>
            </div>

        <?php
        }
        ?>

    </div>
</section>
           
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
<section class="contact">
    <h2>Contact Us</h2>

    <div class="contact-info">
        <p><strong>Phone:</strong> 0300-0000000</p>
        <p><strong>Email:</strong> info@maanghafargarments.com</p>
        <p><strong>Address:</strong> Pakistan</p>
    </div>
</section>
<footer class="footer">
    <p>&copy; 2026 Maan Ghafar Garments. All Rights Reserved.</p>
</footer>
</body>
</html>