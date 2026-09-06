<?php

session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

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

if (!$product_id || $product_id <= 0) {
    http_response_code(400);
    die("Invalid Product ID.");
}

/*
|--------------------------------------------------------------------------
| Check Product and Stock
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        product_name,
        stock_quantity
    FROM products
    WHERE product_id = ?
    LIMIT 1
");

if (!$stmt) {
    error_log(
        "Add to cart query prepare failed: " .
        $conn->error
    );

    http_response_code(500);
    die("Unable to process your request.");
}

$stmt->bind_param(
    "i",
    $product_id
);

if (!$stmt->execute()) {

    error_log(
        "Add to cart query execute failed: " .
        $stmt->error
    );

    $stmt->close();

    http_response_code(500);
    die("Unable to process your request.");
}

$result = $stmt->get_result();

$product = $result->fetch_assoc();

$stmt->close();

/*
|--------------------------------------------------------------------------
| Product Not Found
|--------------------------------------------------------------------------
*/

if (!$product) {
    http_response_code(404);
    die("Product not found.");
}

$product_name = $product['product_name'];
$stock = (int) $product['stock_quantity'];

/*
|--------------------------------------------------------------------------
| Out of Stock
|--------------------------------------------------------------------------
*/

if ($stock <= 0) {

    die(
        "Sorry, " .
        e($product_name) .
        " is out of stock."
    );
}

/*
|--------------------------------------------------------------------------
| Current Cart Quantity
|--------------------------------------------------------------------------
*/

$current_quantity = isset(
    $_SESSION['cart'][$product_id]
)
    ? (int) $_SESSION['cart'][$product_id]
    : 0;

/*
|--------------------------------------------------------------------------
| Prevent Invalid Cart Quantity
|--------------------------------------------------------------------------
*/

if ($current_quantity < 0) {
    $current_quantity = 0;
}

/*
|--------------------------------------------------------------------------
| Prevent Adding More Than Available Stock
|--------------------------------------------------------------------------
*/

if ($current_quantity >= $stock) {

    die(
        "Sorry, only " .
        $stock .
        " item(s) of " .
        e($product_name) .
        " are available."
    );
}

/*
|--------------------------------------------------------------------------
| Add Product To Cart
|--------------------------------------------------------------------------
*/

$_SESSION['cart'][$product_id] =
    $current_quantity + 1;

/*
|--------------------------------------------------------------------------
| Redirect To Cart
|--------------------------------------------------------------------------
*/

header("Location: cart.php");
exit;