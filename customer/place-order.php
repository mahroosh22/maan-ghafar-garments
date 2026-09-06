<?php

session_start();

require_once "../config/database.php";


/* =========================================================
   CUSTOMER LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'customer'
) {
    header("Location: ../login.php");
    exit;
}


$user_id = filter_var(
    $_SESSION['user_id'],
    FILTER_VALIDATE_INT
);


if (!$user_id || $user_id <= 0) {

    session_unset();
    session_destroy();

    header("Location: ../login.php");
    exit;
}


/* =========================================================
   ONLY POST REQUEST ALLOWED
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: checkout.php");
    exit;
}


/* =========================================================
   CSRF VERIFICATION
========================================================= */

$csrf_token = $_POST['csrf_token'] ?? '';

if (
    !isset($_SESSION['csrf_token']) ||
    !is_string($_SESSION['csrf_token']) ||
    $_SESSION['csrf_token'] === '' ||
    !is_string($csrf_token) ||
    $csrf_token === '' ||
    !hash_equals($_SESSION['csrf_token'], $csrf_token)
) {
    die("Invalid or expired request. Please go back to checkout and try again.");
}


/* =========================================================
   CART CHECK
========================================================= */

if (
    empty($_SESSION['cart']) ||
    !is_array($_SESSION['cart'])
) {
    header("Location: ../cart.php");
    exit;
}


/* =========================================================
   GET FORM DATA
========================================================= */

$name = trim(
    $_POST['name'] ?? ''
);

$phone = trim(
    $_POST['phone'] ?? ''
);

$address = trim(
    $_POST['address'] ?? ''
);

$city = trim(
    $_POST['city'] ?? ''
);

$postal_code = trim(
    $_POST['postal_code'] ?? ''
);

$payment_method = trim(
    $_POST['payment_method'] ?? ''
);


/* =========================================================
   VALID PAYMENT METHODS
========================================================= */

$allowed_payment_methods = [
    "Cash on Delivery",
    "JazzCash",
    "EasyPaisa",
    "Bank Transfer"
];


/* =========================================================
   VALIDATION
========================================================= */

if ($name === '') {
    die("Please enter your full name.");
}


if (mb_strlen($name) > 100) {
    die("Your name is too long.");
}


if ($phone === '') {
    die("Please enter your phone number.");
}


if (mb_strlen($phone) > 30) {
    die("Your phone number is too long.");
}


if ($address === '') {
    die("Please enter your delivery address.");
}


if (mb_strlen($address) > 1000) {
    die("Your delivery address is too long.");
}


if ($city === '') {
    die("Please enter your city.");
}


if (mb_strlen($city) > 100) {
    die("Your city name is too long.");
}


if ($postal_code === '') {
    die("Please enter your postal code.");
}


if (mb_strlen($postal_code) > 20) {
    die("Your postal code is too long.");
}


if (
    !in_array(
        $payment_method,
        $allowed_payment_methods,
        true
    )
) {
    die("Please select a valid payment method.");
}


/* =========================================================
   START DATABASE TRANSACTION
========================================================= */

$conn->begin_transaction();


try {


    /* =====================================================
       UPDATE CUSTOMER PHONE
    ===================================================== */

    $stmt = $conn->prepare("
        UPDATE users
        SET phone = ?
        WHERE id = ?
        AND role = 'customer'
    ");


    if (!$stmt) {

        throw new Exception(
            "Unable to update customer information."
        );
    }


    $stmt->bind_param(
        "si",
        $phone,
        $user_id
    );


    if (!$stmt->execute()) {

        $stmt->close();

        throw new Exception(
            "Unable to update customer information."
        );
    }


    $stmt->close();


    /* =====================================================
       VALIDATE CART & CALCULATE TOTAL
    ===================================================== */

    $order_items = [];

    $grand_total = 0;


    foreach (
        $_SESSION['cart'] as $product_id => $quantity
    ) {


        $product_id = (int) $product_id;

        $quantity = (int) $quantity;


        if (
            $product_id <= 0 ||
            $quantity <= 0
        ) {
            continue;
        }


        /* =================================================
           LOCK PRODUCT ROW
        ================================================= */

        $stmt = $conn->prepare("
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


        if (!$stmt) {

            throw new Exception(
                "Unable to check product information."
            );
        }


        $stmt->bind_param(
            "i",
            $product_id
        );


        if (!$stmt->execute()) {

            $stmt->close();

            throw new Exception(
                "Unable to check product information."
            );
        }


        $result =
            $stmt->get_result();


        $product =
            $result->fetch_assoc();


        $stmt->close();


        if (!$product) {

            throw new Exception(
                "One of the products in your cart is no longer available."
            );
        }


        /* =================================================
           STOCK CHECK
        ================================================= */

        $stock =
            (int) $product['stock_quantity'];


        if ($stock <= 0) {

            throw new Exception(
                $product['product_name'] .
                " is currently out of stock."
            );
        }


        if ($quantity > $stock) {

            throw new Exception(
                "Only " .
                $stock .
                " item(s) of " .
                $product['product_name'] .
                " are available."
            );
        }


        /* =================================================
           CALCULATE SUBTOTAL
        ================================================= */

        $price =
            (float) $product['price'];


        $subtotal =
            $price * $quantity;


        $grand_total +=
            $subtotal;


        $order_items[] = [
            'product_id' => $product_id,
            'quantity'   => $quantity,
            'price'      => $price
        ];

    }


    /* =====================================================
       VALID CART CHECK
    ===================================================== */

    if (empty($order_items)) {

        throw new Exception(
            "Your cart is empty."
        );
    }


    if ($grand_total <= 0) {

        throw new Exception(
            "Invalid order total."
        );
    }


    /* =====================================================
       GENERATE UNIQUE ORDER ID
    ===================================================== */

    $order_id = "";


    for (
        $attempt = 0;
        $attempt < 5;
        $attempt++
    ) {


        $candidate =
            "MG-" .
            date("YmdHis") .
            "-" .
            random_int(
                100,
                999999
            );


        $check_stmt = $conn->prepare("
            SELECT order_id
            FROM orders
            WHERE order_id = ?
            LIMIT 1
        ");


        if (!$check_stmt) {

            throw new Exception(
                "Unable to verify order number."
            );
        }


        $check_stmt->bind_param(
            "s",
            $candidate
        );


        if (!$check_stmt->execute()) {

            $check_stmt->close();

            throw new Exception(
                "Unable to verify order number."
            );
        }


        $check_result =
            $check_stmt->get_result();


        $exists =
            $check_result->num_rows > 0;


        $check_stmt->close();


        if (!$exists) {

            $order_id =
                $candidate;

            break;
        }

    }


    if ($order_id === '') {

        throw new Exception(
            "Unable to generate a unique order number."
        );
    }


    /* =====================================================
       CREATE ORDER
    ===================================================== */

    $status = "pending";


    $stmt = $conn->prepare("
        INSERT INTO orders
        (
            order_id,
            user_id,
            customer_name,
            phone,
            total_amount,
            status,
            shipping_address,
            payment_method,
            created_at
        )
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");


    if (!$stmt) {

        throw new Exception(
            "Unable to create order."
        );
    }


    $stmt->bind_param(
        "sissdsss",
        $order_id,
        $user_id,
        $name,
        $phone,
        $grand_total,
        $status,
        $address,
        $payment_method
    );


    if (!$stmt->execute()) {

        $stmt->close();

        throw new Exception(
            "Order could not be created."
        );
    }


    $stmt->close();


    /* =====================================================
       INSERT ORDER ITEMS
    ===================================================== */

    $item_stmt = $conn->prepare("
        INSERT INTO order_items
        (
            order_id,
            product_id,
            quantity,
            price
        )
        VALUES
        (?, ?, ?, ?)
    ");


    if (!$item_stmt) {

        throw new Exception(
            "Unable to create order items."
        );
    }


    foreach (
        $order_items as $item
    ) {


        $product_id =
            $item['product_id'];

        $quantity =
            $item['quantity'];

        $price =
            $item['price'];


        $item_stmt->bind_param(
            "siid",
            $order_id,
            $product_id,
            $quantity,
            $price
        );


        if (!$item_stmt->execute()) {

            $item_stmt->close();

            throw new Exception(
                "Unable to save order items."
            );
        }

    }


    $item_stmt->close();


    /* =====================================================
       UPDATE PRODUCT STOCK
    ===================================================== */

    $stock_stmt = $conn->prepare("
        UPDATE products
        SET stock_quantity =
            stock_quantity - ?
        WHERE product_id = ?
        AND stock_quantity >= ?
    ");


    if (!$stock_stmt) {

        throw new Exception(
            "Unable to update product stock."
        );
    }


    foreach (
        $order_items as $item
    ) {


        $quantity =
            $item['quantity'];

        $product_id =
            $item['product_id'];


        $stock_stmt->bind_param(
            "iii",
            $quantity,
            $product_id,
            $quantity
        );


        if (!$stock_stmt->execute()) {

            $stock_stmt->close();

            throw new Exception(
                "Unable to update product stock."
            );
        }


        if ($stock_stmt->affected_rows !== 1) {

            $stock_stmt->close();

            throw new Exception(
                "Product stock changed. Please try again."
            );
        }

    }


    $stock_stmt->close();


    /* =====================================================
       CREATE PAYMENT RECORD
    ===================================================== */

    $payment_status = "pending";


    $payment_stmt = $conn->prepare("
        INSERT INTO payments
        (
            order_id,
            payment_method,
            amount,
            payment_status,
            transaction_id,
            paid_at
        )
        VALUES
        (?, ?, ?, ?, NULL, NULL)
    ");


    if (!$payment_stmt) {

        throw new Exception(
            "Unable to create payment record."
        );
    }


    $payment_stmt->bind_param(
        "ssds",
        $order_id,
        $payment_method,
        $grand_total,
        $payment_status
    );


    if (!$payment_stmt->execute()) {

        $payment_stmt->close();

        throw new Exception(
            "Unable to save payment information."
        );
    }


    $payment_stmt->close();


    /* =====================================================
       CREATE SHIPPING RECORD
    ===================================================== */

    $shipping_status = "pending";


    $shipping_stmt = $conn->prepare("
        INSERT INTO shipping
        (
            order_id,
            shipping_address,
            city,
            postal_code,
            country,
            shipping_status,
            shipped_at
        )
        VALUES
        (?, ?, ?, ?, 'Pakistan', ?, NULL)
    ");


    if (!$shipping_stmt) {

        throw new Exception(
            "Unable to create shipping record."
        );
    }


    $shipping_stmt->bind_param(
        "sssss",
        $order_id,
        $address,
        $city,
        $postal_code,
        $shipping_status
    );


    if (!$shipping_stmt->execute()) {

        $shipping_stmt->close();

        throw new Exception(
            "Unable to save shipping information."
        );
    }


    $shipping_stmt->close();


    /* =====================================================
       COMMIT EVERYTHING
    ===================================================== */

    $conn->commit();


    /* =====================================================
       CLEAR CART
    ===================================================== */

    unset(
        $_SESSION['cart']
    );


    /* =====================================================
       REFRESH CSRF TOKEN
    ===================================================== */

    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));


    /* =====================================================
       SAVE LAST ORDER ID
    ===================================================== */

    $_SESSION['last_order_id'] =
        $order_id;


    /* =====================================================
       REDIRECT TO ORDER DETAILS
    ===================================================== */

    header(
        "Location: order_view.php?id=" .
        urlencode($order_id)
    );

    exit;


} catch (Throwable $e) {


    /* =====================================================
       ROLLBACK
    ===================================================== */

    $conn->rollback();


    error_log(
        "Place order failed: " .
        $e->getMessage()
    );


    ?>

    <!DOCTYPE html>

    <html lang="en">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>
            Order Error - Maan Ghafar Garments
        </title>


        <style>

            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                background: #f8f8f8;
                font-family: Arial, sans-serif;
            }


            .error-box {
                width: 90%;
                max-width: 550px;
                background: #fff;
                padding: 40px;
                border-radius: 15px;
                text-align: center;
                box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            }


            .error-icon {
                font-size: 50px;
                margin-bottom: 15px;
            }


            h1 {
                color: #111;
                margin-bottom: 12px;
            }


            p {
                color: #666;
                line-height: 1.6;
            }


            .back-btn {
                display: inline-block;
                margin-top: 20px;
                padding: 13px 25px;
                background: #111;
                color: #fff;
                text-decoration: none;
                border-radius: 8px;
                font-weight: bold;
            }


            .back-btn:hover {
                background: #d4af37;
                color: #111;
            }

        </style>

    </head>


    <body>


        <div class="error-box">


            <div class="error-icon">
                ⚠️
            </div>


            <h1>
                Order Could Not Be Placed
            </h1>


            <p>

                <?php

                echo htmlspecialchars(
                    $e->getMessage(),
                    ENT_QUOTES,
                    'UTF-8'
                );

                ?>

            </p>


            <a
                href="checkout.php"
                class="back-btn"
            >
                ← Back to Checkout
            </a>


        </div>


    </body>

    </html>

    <?php

    exit;

}

?>