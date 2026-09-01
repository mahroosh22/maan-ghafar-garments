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
<section class="products">
    <h2>Our Products</h2>

    <div class="product-container">

        <div class="product-card">
            <div class="product-image">Product Image</div>
            <h3>Men's Shirt</h3>
            <p>Premium quality shirt</p>
            <span>Rs. 3,500</span>
        </div>

        <div class="product-card">
            <div class="product-image">Product Image</div>
            <h3>Men's Shalwar Kameez</h3>
            <p>Comfortable and stylish</p>
            <span>Rs. 4,500</span>
        </div>

        <div class="product-card">
            <div class="product-image">Product Image</div>
            <h3>Women's Collection</h3>
            <p>Elegant fashion wear</p>
            <span>Rs. 5,000</span>
        </div>

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
</body>
</html>