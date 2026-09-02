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
    <style>
.cart-item {
    margin-bottom: 20px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 10px;
}

.quantity-controls {
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.quantity-controls a {
    display: inline-flex;
    width: 30px;
    height: 30px;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    background: #222;
    color: white;
    border-radius: 5px;
    font-weight: bold;
}

.quantity-controls a:hover {
    opacity: 0.8;
}
.remove-btn {
    display: inline-block;
    margin-top: 10px;
    padding: 8px 15px;
    background: #c0392b;
    color: white;
    text-decoration: none;
    border-radius: 5px;
}

.remove-btn:hover {
    opacity: 0.8;
}
</style>
</head>

<body>

<h1>Shopping Cart</h1>

<?php if (empty($_SESSION['cart'])): ?>

    <p>Your cart is empty.</p>

<?php else: ?>
    

    <?php $grand_total = 0; ?>

    <?php foreach ($_SESSION['cart'] as $product_id => $quantity): ?>


    <?php
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $grand_total += $product['price'] * $quantity;
    ?>

    <?php if ($product): ?>

        <div class="cart-item">

    <img src="assets/image/<?php echo htmlspecialchars($product['image']); ?>"
         alt="<?php echo htmlspecialchars($product['product_name']); ?>"
         width="100">

    <p>
    Product: <?php echo htmlspecialchars($product['product_name']); ?>
    | Price: Rs. <?php echo number_format($product['price']); ?>
    
    | Quantity:
    
    <a href="update-cart.php?id=<?php echo $product_id; ?>&action=decrease">
        −
    </a>

    <?php echo $quantity; ?>

    <a href="update-cart.php?id=<?php echo $product_id; ?>&action=increase">
        +
    </a>

    | Total: Rs. <?php echo number_format($product['price'] * $quantity); ?>
</p>
<a href="remove-from-cart.php?id=<?php echo $product_id; ?>" class="remove-btn">
    Remove
</a>
</div>
    <?php endif; ?>

<?php endforeach; ?>
<h2>
    Grand Total: Rs. <?php echo number_format($grand_total); ?>
</h2>
<a href="remove-from-cart.php?id=<?php echo $product_id; ?>">
    Remove
</a>
<?php endif; ?>

<a href="index.php">← Continue Shopping</a>

</body>
</html>