<?php
session_start();

require_once "config/database.php";

if (empty($_SESSION['cart'])) {
    die("Your cart is empty.");
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');

if ($name === '' || $phone === '' || $address === '') {
    die("Please fill all fields.");
}

/* Calculate total */
$total_amount = 0;

foreach ($_SESSION['cart'] as $product_id => $quantity) {

    $stmt = $conn->prepare("SELECT price FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if ($product) {
        $total_amount += $product['price'] * $quantity;
    }
}

/* Save order */
$user_id = 1;
$status = "Pending";
$payment_method = $_POST['payment_method'] ?? '';

if ($payment_method === '') {
    die("Please select a payment method.");
}

$stmt = $conn->prepare("
    INSERT INTO orders
    (user_id, customer_name, phone, total_amount, status, shipping_address, payment_method)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "issdsss",
    $user_id,
    $name,
    $phone,
    $total_amount,
    $status,
    $address,
    $payment_method
);

if ($stmt->execute()) {
    $order_id = $conn->insert_id;
foreach ($_SESSION['cart'] as $product_id => $quantity) {

    $stmt = $conn->prepare("SELECT price FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if ($product) {

        $price = $product['price'];

        $item_stmt = $conn->prepare("
            INSERT INTO order_items
            (order_id, product_id, quantity, price)
            VALUES (?, ?, ?, ?)
        ");

        $item_stmt->bind_param(
            "iiid",
            $order_id,
            $product_id,
            $quantity,
            $price
        );

        $item_stmt->execute();
    }
}
echo '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Successful - Maan Ghafar Garments</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
    padding: 20px;
}
.order-success {
    max-width: 600px;
    margin: 60px auto;
    padding: 30px;
    background: white;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
    .success-icon {
    width: 70px;
    height: 70px;
    margin: 0 auto 20px;
    background: #27ae60;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    font-weight: bold;
}
</style>
</head>
<body>
<div class="order-success">';
echo "<div class='success-icon'>✓</div>";
    echo "<h1>🎉 Order Placed Successfully!</h1>";
echo "<p>Thank you, " . htmlspecialchars($name) . "!</p>";
echo "<p>Your Order ID is: <strong>#" . $order_id . "</strong></p>";
echo "<p>Total Amount: <strong>Rs. " . number_format($total_amount) . "</strong></p>";
echo "<p>Payment Method: <strong>" . htmlspecialchars($payment_method) . "</strong></p>";
echo "<p>Your order has been received successfully.</p>";
echo '<a href="index.php">Continue Shopping</a>';
echo '</div>
</body>
</html>';
    unset($_SESSION['cart']);
} else {
    echo "Order could not be placed.";
}
?>