<?php
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

require_once "config/database.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Maan Ghafar Garments</title>
</head>

<body>

<h1>Shopping Cart</h1>

<?php if (empty($_SESSION['cart'])): ?>

    <p>Your cart is empty.</p>

<?php else: ?>

    <?php foreach ($_SESSION['cart'] as $product_id => $quantity): ?>

        <p>
            Product ID: <?php echo $product_id; ?>
            | Quantity: <?php echo $quantity; ?>
        </p>

    <?php endforeach; ?>

<?php endif; ?>

<a href="index.php">← Continue Shopping</a>

</body>
</html>