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

    <?php
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    ?>

    <?php if ($product): ?>

        <div class="cart-item">

    <img src="assets/image/<?php echo htmlspecialchars($product['image']); ?>"
         alt="<?php echo htmlspecialchars($product['product_name']); ?>"
         width="100">

    <p>
        Product: <?php echo htmlspecialchars($product['product_name']); ?>
        | Price: Rs. <?php echo number_format($product['price']); ?>
        | Quantity: <?php echo $quantity; ?>
    </p>

</div>
    <?php endif; ?>

<?php endforeach; ?>
<?php endif; ?>

<a href="index.php">← Continue Shopping</a>

</body>
</html>