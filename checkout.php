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
    <title>Checkout - Maan Ghafar Garments</title>
</head>

<body>

<h1>Checkout</h1>

<p>Checkout page ready.</p>
<form action="place-order.php" method="POST">

    <label>Full Name</label><br>
    <input type="text" name="name" required><br><br>

    <label>Phone Number</label><br>
    <input type="text" name="phone" required><br><br>

    <label>Address</label><br>
    <textarea name="address" rows="4" required></textarea><br><br>

    <button type="submit">Place Order</button>

</form>

</body>
</html>