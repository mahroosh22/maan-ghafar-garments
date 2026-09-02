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
$payment_method = "Cash on Delivery";

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

    echo "<h1>Order Placed Successfully!</h1>";
    echo "<p>Order ID: " . $order_id . "</p>";
    echo "<p>Thank you, " . htmlspecialchars($name) . "!</p>";
    echo "<p>Total Amount: Rs. " . number_format($total_amount) . "</p>";

    unset($_SESSION['cart']);
} else {
    echo "Order could not be placed.";
}
?>