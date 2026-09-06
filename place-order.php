<?php

session_start();

require_once "../config/database.php";


/* =========================
   LOGIN CHECK
========================= */

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'customer'
) {
    header("Location: ../login.php");
    exit;
}


/* =========================
   POST REQUEST CHECK
========================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: checkout.php");
    exit;
}


/* =========================
   CART CHECK
========================= */

if (
    !isset($_SESSION['cart']) ||
    !is_array($_SESSION['cart']) ||
    empty($_SESSION['cart'])
) {
    header("Location: ../cart.php");
    exit;
}


/* =========================
   HELPER
========================= */

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================
   CUSTOMER DETAILS
========================= */

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? '');

$user_id = (int) $_SESSION['user_id'];


/* =========================
   VALIDATION
========================= */

if ($name === '' || $phone === '' || $address === '') {
    die("Please fill all required fields.");
}

if ($payment_method === '') {
    die("Please select a payment method.");
}

if (mb_strlen($name) > 100) {
    die("Name is too long.");
}

if (mb_strlen($phone) > 30) {
    die("Phone number is too long.");
}

if (mb_strlen($address) > 500) {
    die("Shipping address is too long.");
}

$allowed_payment_methods = [
    'Cash on Delivery',
    'JazzCash',
    'EasyPaisa',
    'Bank Transfer'
];

if (!in_array($payment_method, $allowed_payment_methods, true)) {
    die("Invalid payment method selected.");
}


/* =========================
   GET CART PRODUCTS
========================= */

$cart_products = [];
$total_amount = 0;


/* =========================
   START TRANSACTION
========================= */

$conn->begin_transaction();


try {

    /* =========================
       CHECK USER
    ========================= */

    $user_stmt = $conn->prepare("
        SELECT id, name, email
        FROM users
        WHERE id = ?
        AND role = 'customer'
        LIMIT 1
    ");

    if (!$user_stmt) {
        throw new Exception("User query failed.");
    }

    $user_stmt->bind_param(
        "i",
        $user_id
    );

    if (!$user_stmt->execute()) {
        $user_stmt->close();
        throw new Exception("User query failed.");
    }

    $user_result = $user_stmt->get_result();
    $user = $user_result->fetch_assoc();

    $user_stmt->close();

    if (!$user) {
        throw new Exception("Customer account not found.");
    }


    /* =========================
       UPDATE CUSTOMER PHONE
    ========================= */

    $phone_stmt = $conn->prepare("
        UPDATE users
        SET phone = ?
        WHERE id = ?
        AND role = 'customer'
    ");

    if (!$phone_stmt) {
        throw new Exception("Customer information could not be updated.");
    }

    $phone_stmt->bind_param(
        "si",
        $phone,
        $user_id
    );

    if (!$phone_stmt->execute()) {
        $phone_stmt->close();
        throw new Exception("Customer information could not be updated.");
    }

    $phone_stmt->close();


    /* =========================
       CHECK PRODUCTS + STOCK
       WITH ROW LOCK
    ========================= */

    foreach ($_SESSION['cart'] as $product_id => $quantity) {

        $product_id = (int) $product_id;
        $quantity = (int) $quantity;

        if ($product_id <= 0 || $quantity <= 0) {
            throw new Exception("Invalid product or quantity.");
        }


        $product_stmt = $conn->prepare("
            SELECT
                product_id,
                product_name,
                price,
                stock_quantity
            FROM products
            WHERE product_id = ?
            LIMIT 1
            FOR UPDATE
        ");

        if (!$product_stmt) {
            throw new Exception("Product query failed.");
        }

        $product_stmt->bind_param(
            "i",
            $product_id
        );

        if (!$product_stmt->execute()) {
            $product_stmt->close();
            throw new Exception("Product query failed.");
        }

        $product_result = $product_stmt->get_result();
        $product = $product_result->fetch_assoc();

        $product_stmt->close();


        /* Product deleted/not found */

        if (!$product) {
            throw new Exception(
                "Sorry, one of the products in your cart is no longer available."
            );
        }


        $stock = (int) $product['stock_quantity'];


        /* Out of stock */

        if ($stock <= 0) {
            throw new Exception(
                "Sorry, " .
                $product['product_name'] .
                " is out of stock."
            );
        }


        /* Quantity greater than stock */

        if ($quantity > $stock) {
            throw new Exception(
                "Sorry, only " .
                $stock .
                " item(s) of " .
                $product['product_name'] .
                " are available."
            );
        }


        $price = (float) $product['price'];

        $total_amount += $price * $quantity;


        $cart_products[] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'price' => $price,
            'stock' => $stock
        ];
    }


    /* =========================
       GENERATE CUSTOM ORDER ID
    ========================= */

    $order_id =
        'MG-' .
        date('YmdHis') .
        '-' .
        random_int(100, 999);


    /* =========================
       MAKE SURE ORDER ID IS UNIQUE
    ========================= */

    $check_order_stmt = $conn->prepare("
        SELECT order_id
        FROM orders
        WHERE order_id = ?
        LIMIT 1
    ");

    if (!$check_order_stmt) {
        throw new Exception("Order verification failed.");
    }

    $check_order_stmt->bind_param(
        "s",
        $order_id
    );

    if (!$check_order_stmt->execute()) {
        $check_order_stmt->close();
        throw new Exception("Order verification failed.");
    }

    $check_order_result = $check_order_stmt->get_result();

    $check_order_stmt->close();

    if ($check_order_result->num_rows > 0) {
        $order_id =
            'MG-' .
            date('YmdHis') .
            '-' .
            random_int(1000, 9999);
    }


    /* =========================
       INSERT ORDER
    ========================= */

    $status = "pending";

    $order_stmt = $conn->prepare("
        INSERT INTO orders
        (
            order_id,
            user_id,
            customer_name,
            phone,
            total_amount,
            status,
            shipping_address,
            payment_method
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$order_stmt) {
        throw new Exception("Order could not be created.");
    }

    $order_stmt->bind_param(
        "sissdsss",
        $order_id,
        $user_id,
        $name,
        $phone,
        $total_amount,
        $status,
        $address,
        $payment_method
    );

    if (!$order_stmt->execute()) {

        error_log(
            "Order insert failed: " .
            $order_stmt->error
        );

        $order_stmt->close();

        throw new Exception(
            "Order could not be created."
        );
    }

    $order_stmt->close();


    /* =========================
       INSERT ORDER ITEMS
    ========================= */

    foreach ($cart_products as $item) {

        $product_id = (int) $item['product_id'];
        $quantity = (int) $item['quantity'];
        $price = (float) $item['price'];


        $item_stmt = $conn->prepare("
            INSERT INTO order_items
            (
                order_id,
                product_id,
                quantity,
                price
            )
            VALUES (?, ?, ?, ?)
        ");

        if (!$item_stmt) {
            throw new Exception(
                "Order items could not be saved."
            );
        }

        $item_stmt->bind_param(
            "siid",
            $order_id,
            $product_id,
            $quantity,
            $price
        );

        if (!$item_stmt->execute()) {

            error_log(
                "Order item insert failed: " .
                $item_stmt->error
            );

            $item_stmt->close();

            throw new Exception(
                "Order items could not be saved."
            );
        }

        $item_stmt->close();


        /* =========================
           REDUCE STOCK
        ========================= */

        $stock_stmt = $conn->prepare("
            UPDATE products
            SET stock_quantity = stock_quantity - ?
            WHERE product_id = ?
            AND stock_quantity >= ?
        ");

        if (!$stock_stmt) {
            throw new Exception(
                "Stock could not be updated."
            );
        }

        $stock_stmt->bind_param(
            "iii",
            $quantity,
            $product_id,
            $quantity
        );

        if (!$stock_stmt->execute()) {

            error_log(
                "Stock update failed: " .
                $stock_stmt->error
            );

            $stock_stmt->close();

            throw new Exception(
                "Stock could not be updated."
            );
        }

        if ($stock_stmt->affected_rows !== 1) {

            $stock_stmt->close();

            throw new Exception(
                "Stock changed while placing your order. Please try again."
            );
        }

        $stock_stmt->close();
    }


    /* =========================
       COMMIT TRANSACTION
    ========================= */

    $conn->commit();


    /* =========================
       CLEAR CART
    ========================= */

    unset($_SESSION['cart']);


    /* =========================
       SAVE LAST ORDER ID
    ========================= */

    $_SESSION['last_order_id'] = $order_id;


    /* =========================
       REDIRECT TO ORDER DETAILS
    ========================= */

    header(
        "Location: order_view.php?id=" .
        urlencode($order_id)
    );

    exit;


} catch (Throwable $e) {

    /* =========================
       ROLLBACK
    ========================= */

    $conn->rollback();

    error_log(
        "Place order error: " .
        $e->getMessage()
    );

    die(
        "Order could not be placed. Please try again."
    );
}

?>