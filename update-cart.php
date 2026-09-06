<?php

session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| Get Product ID
|--------------------------------------------------------------------------
*/

$product_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

$action = $_GET['action'] ?? '';

$allowed_actions = [
    'increase',
    'decrease'
];

/*
|--------------------------------------------------------------------------
| Validate Request
|--------------------------------------------------------------------------
*/

if (
    !$product_id ||
    $product_id <= 0 ||
    !in_array($action, $allowed_actions, true)
) {
    header("Location: cart.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Check Product Exists In Cart
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['cart'][$product_id])) {
    header("Location: cart.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Current Stock
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT stock_quantity
    FROM products
    WHERE product_id = ?
    LIMIT 1
");

if (!$stmt) {

    error_log(
        "Update cart prepare failed: " .
        $conn->error
    );

    header("Location: cart.php");
    exit;
}

$stmt->bind_param(
    "i",
    $product_id
);

if (!$stmt->execute()) {

    error_log(
        "Update cart execute failed: " .
        $stmt->error
    );

    $stmt->close();

    header("Location: cart.php");
    exit;
}

$result = $stmt->get_result();

$product = $result->fetch_assoc();

$stmt->close();

/*
|--------------------------------------------------------------------------
| Product No Longer Exists
|--------------------------------------------------------------------------
*/

if (!$product) {

    unset(
        $_SESSION['cart'][$product_id]
    );

    header("Location: cart.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Stock
|--------------------------------------------------------------------------
*/

$stock = (int) $product['stock_quantity'];

$current_quantity =
    (int) $_SESSION['cart'][$product_id];

/*
|--------------------------------------------------------------------------
| Clean Invalid Quantity
|--------------------------------------------------------------------------
*/

if ($current_quantity < 1) {
    $current_quantity = 1;
}

/*
|--------------------------------------------------------------------------
| Out Of Stock
|--------------------------------------------------------------------------
*/

if ($stock <= 0) {

    unset(
        $_SESSION['cart'][$product_id]
    );

    header("Location: cart.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Prevent Cart Quantity From Exceeding Stock
|--------------------------------------------------------------------------
*/

if ($current_quantity > $stock) {

    $current_quantity = $stock;

    $_SESSION['cart'][$product_id] =
        $stock;
}

/*
|--------------------------------------------------------------------------
| Increase Quantity
|--------------------------------------------------------------------------
*/

if ($action === 'increase') {

    if ($current_quantity < $stock) {

        $_SESSION['cart'][$product_id] =
            $current_quantity + 1;

    } else {

        $_SESSION['cart'][$product_id] =
            $stock;
    }
}

/*
|--------------------------------------------------------------------------
| Decrease Quantity
|--------------------------------------------------------------------------
*/

if ($action === 'decrease') {

    $new_quantity =
        $current_quantity - 1;

    if ($new_quantity <= 0) {

        unset(
            $_SESSION['cart'][$product_id]
        );

    } else {

        $_SESSION['cart'][$product_id] =
            $new_quantity;
    }
}

/*
|--------------------------------------------------------------------------
| Redirect Back To Cart
|--------------------------------------------------------------------------
*/

header("Location: cart.php");
exit;