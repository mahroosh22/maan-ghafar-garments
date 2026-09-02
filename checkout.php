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
    <style>
body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
    padding: 30px;
}

form {
    max-width: 500px;
    margin: 30px auto;
    padding: 25px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

label {
    font-weight: bold;
}

input,
textarea {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-sizing: border-box;
}

button {
    width: 100%;
    padding: 12px;
    background: #222;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

button:hover {
    opacity: 0.85;
}
</style>
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