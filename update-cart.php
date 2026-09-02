<?php
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {

    if ($action === 'increase') {
        $_SESSION['cart'][$product_id]++;
    }

    if ($action === 'decrease') {
        $_SESSION['cart'][$product_id]--;

        if ($_SESSION['cart'][$product_id] <= 0) {
            unset($_SESSION['cart'][$product_id]);
        }
    }
}

header("Location: cart.php");
exit;
?>