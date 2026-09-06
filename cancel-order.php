<?php

session_start();

require_once "config/database.php";


/* =========================
   CUSTOMER LOGIN CHECK
========================= */

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'customer'
) {
    header("Location: login.php");
    exit;
}


$user_id = filter_var(
    $_SESSION['user_id'],
    FILTER_VALIDATE_INT
);

if (!$user_id || $user_id <= 0) {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}


/* =========================
   CSRF TOKEN
========================= */

if (
    !isset($_SESSION['csrf_token']) ||
    !is_string($_SESSION['csrf_token']) ||
    $_SESSION['csrf_token'] === ''
) {
    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));
}


/* =========================
   POST ONLY
========================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: customer/orders.php");
    exit;
}


/* =========================
   VERIFY CSRF
========================= */

$csrf_token =
    $_POST['csrf_token'] ?? '';

if (
    !is_string($csrf_token) ||
    $csrf_token === '' ||
    !hash_equals(
        $_SESSION['csrf_token'],
        $csrf_token
    )
) {

    die("
        <div style='
            font-family:Arial,sans-serif;
            text-align:center;
            padding:80px 20px;
        '>

            <h2 style='margin-bottom:10px;'>
                Invalid request
            </h2>

            <p style='color:#666;'>
                Your request could not be verified.
                Please try again.
            </p>

            <a
                href='customer/orders.php'
                style='
                    display:inline-block;
                    margin-top:15px;
                    padding:12px 22px;
                    background:#111;
                    color:#fff;
                    text-decoration:none;
                    border-radius:8px;
                '
            >
                Back to My Orders
            </a>

        </div>
    ");

}


/* =========================
   GET ORDER ID FROM POST
========================= */

$order_id =
    trim($_POST['order_id'] ?? '');


if ($order_id === '') {

    header("Location: customer/orders.php");
    exit;
}


/*
 * Order IDs are VARCHAR values such as:
 * MG-20260905113025-123
 *
 * Keep them as strings.
 */

if (strlen($order_id) > 50) {

    header("Location: customer/orders.php");
    exit;
}


/* =========================
   GET CUSTOMER ORDER
========================= */

$stmt = $conn->prepare("
    SELECT
        order_id,
        status
    FROM orders
    WHERE order_id = ?
    AND user_id = ?
    LIMIT 1
");


if (!$stmt) {

    error_log(
        "Cancel order prepare failed: " .
        $conn->error
    );

    die("
        <div style='
            font-family:Arial,sans-serif;
            text-align:center;
            padding:80px 20px;
        '>

            <h2>
                Order could not be cancelled
            </h2>

            <p style='color:#666;'>
                Something went wrong. Please try again.
            </p>

            <a
                href='customer/orders.php'
                style='
                    display:inline-block;
                    margin-top:15px;
                    padding:12px 22px;
                    background:#111;
                    color:#fff;
                    text-decoration:none;
                    border-radius:8px;
                '
            >
                Back to My Orders
            </a>

        </div>
    ");
}


$stmt->bind_param(
    "si",
    $order_id,
    $user_id
);


if (!$stmt->execute()) {

    error_log(
        "Cancel order execute failed: " .
        $stmt->error
    );

    $stmt->close();

    die("
        <div style='
            font-family:Arial,sans-serif;
            text-align:center;
            padding:80px 20px;
        '>

            <h2>
                Order could not be cancelled
            </h2>

            <p style='color:#666;'>
                Something went wrong. Please try again.
            </p>

            <a
                href='customer/orders.php'
                style='
                    display:inline-block;
                    margin-top:15px;
                    padding:12px 22px;
                    background:#111;
                    color:#fff;
                    text-decoration:none;
                    border-radius:8px;
                '
            >
                Back to My Orders
            </a>

        </div>
    ");

}


$result = $stmt->get_result();

$order = $result->fetch_assoc();

$stmt->close();


/* =========================
   ORDER NOT FOUND
========================= */

if (!$order) {

    die("
        <div style='
            font-family:Arial,sans-serif;
            text-align:center;
            padding:80px 20px;
        '>

            <h2 style='margin-bottom:10px;'>
                Order not found
            </h2>

            <p style='color:#666;'>
                This order does not exist or does not belong to you.
            </p>

            <a
                href='customer/orders.php'
                style='
                    display:inline-block;
                    margin-top:15px;
                    padding:12px 22px;
                    background:#111;
                    color:#fff;
                    text-decoration:none;
                    border-radius:8px;
                '
            >
                Back to My Orders
            </a>

        </div>
    ");

}


/* =========================
   ONLY PENDING ORDERS
========================= */

$current_status = strtolower(
    trim(
        $order['status'] ?? ''
    )
);


if ($current_status !== 'pending') {

    die("
        <div style='
            font-family:Arial,sans-serif;
            text-align:center;
            padding:80px 20px;
        '>

            <h2 style='margin-bottom:10px;'>
                This order cannot be cancelled
            </h2>

            <p style='color:#666;'>
                Only pending orders can be cancelled.
            </p>

            <a
                href='customer/orders.php'
                style='
                    display:inline-block;
                    margin-top:15px;
                    padding:12px 22px;
                    background:#111;
                    color:#fff;
                    text-decoration:none;
                    border-radius:8px;
                '
            >
                Back to My Orders
            </a>

        </div>
    ");

}


/* =========================
   GET ORDER ITEMS
========================= */

$item_stmt = $conn->prepare("
    SELECT
        product_id,
        quantity
    FROM order_items
    WHERE order_id = ?
");


if (!$item_stmt) {

    error_log(
        "Cancel order items prepare failed: " .
        $conn->error
    );

    die("
        <div style='
            font-family:Arial,sans-serif;
            text-align:center;
            padding:80px 20px;
        '>

            <h2>
                Order could not be cancelled
            </h2>

            <p style='color:#666;'>
                Something went wrong. Please try again.
            </p>

            <a
                href='customer/orders.php'
                style='
                    display:inline-block;
                    margin-top:15px;
                    padding:12px 22px;
                    background:#111;
                    color:#fff;
                    text-decoration:none;
                    border-radius:8px;
                '
            >
                Back to My Orders
            </a>

        </div>
    ");
}


$item_stmt->bind_param(
    "s",
    $order_id
);


if (!$item_stmt->execute()) {

    error_log(
        "Cancel order items execute failed: " .
        $item_stmt->error
    );

    $item_stmt->close();

    die("
        <div style='
            font-family:Arial,sans-serif;
            text-align:center;
            padding:80px 20px;
        '>

            <h2>
                Order could not be cancelled
            </h2>

            <p style='color:#666;'>
                Something went wrong. Please try again.
            </p>

            <a
                href='customer/orders.php'
                style='
                    display:inline-block;
                    margin-top:15px;
                    padding:12px 22px;
                    background:#111;
                    color:#fff;
                    text-decoration:none;
                    border-radius:8px;
                '
            >
                Back to My Orders
            </a>

        </div>
    ");
}


$items_result =
    $item_stmt->get_result();

$order_items = [];


while (
    $item =
    $items_result->fetch_assoc()
) {

    $product_id =
        (int) (
            $item['product_id'] ?? 0
        );

    $quantity =
        (int) (
            $item['quantity'] ?? 0
        );


    if (
        $product_id > 0 &&
        $quantity > 0
    ) {

        $order_items[] = [
            'product_id' => $product_id,
            'quantity'   => $quantity
        ];

    }
}


$item_stmt->close();


/* =========================
   START TRANSACTION
========================= */

$conn->begin_transaction();


try {


    /* =========================
       CANCEL ORDER
    ========================= */

    $update_stmt = $conn->prepare("
        UPDATE orders
        SET status = 'cancelled'
        WHERE order_id = ?
        AND user_id = ?
        AND LOWER(status) = 'pending'
    ");


    if (!$update_stmt) {

        throw new Exception(
            "Order update could not be prepared."
        );
    }


    $update_stmt->bind_param(
        "si",
        $order_id,
        $user_id
    );


    if (!$update_stmt->execute()) {

        $update_stmt->close();

        throw new Exception(
            "Order could not be cancelled."
        );
    }


    /*
     * If affected_rows is not 1,
     * another request may have already
     * changed the order status.
     */

    if ($update_stmt->affected_rows !== 1) {

        $update_stmt->close();

        throw new Exception(
            "Order could not be cancelled."
        );
    }


    $update_stmt->close();


    /* =========================
       RESTORE PRODUCT STOCK
    ========================= */

    foreach ($order_items as $item) {


        $stock_stmt = $conn->prepare("
            UPDATE products
            SET stock_quantity =
                stock_quantity + ?
            WHERE product_id = ?
        ");


        if (!$stock_stmt) {

            throw new Exception(
                "Stock update could not be prepared."
            );
        }


        $stock_stmt->bind_param(
            "ii",
            $item['quantity'],
            $item['product_id']
        );


        if (!$stock_stmt->execute()) {

            $stock_stmt->close();

            throw new Exception(
                "Stock could not be restored."
            );
        }


        if ($stock_stmt->affected_rows !== 1) {

            $stock_stmt->close();

            throw new Exception(
                "Product stock could not be restored."
            );
        }


        $stock_stmt->close();

    }


    /* =========================
       COMMIT
    ========================= */

    $conn->commit();


    /*
     * Refresh CSRF token after
     * successful state-changing action.
     */

    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));


    /* =========================
       RETURN TO ORDERS
    ========================= */

    header("Location: customer/orders.php");
    exit;


} catch (Throwable $e) {


    /* =========================
       ROLLBACK
    ========================= */

    $conn->rollback();


    error_log(
        "Cancel order failed: " .
        $e->getMessage()
    );


    die("
        <div style='
            font-family:Arial,sans-serif;
            text-align:center;
            padding:80px 20px;
        '>

            <h2 style='margin-bottom:10px;'>
                Order could not be cancelled
            </h2>

            <p style='color:#666;'>
                Something went wrong.
                Please try again.
            </p>

            <a
                href='customer/orders.php'
                style='
                    display:inline-block;
                    margin-top:15px;
                    padding:12px 22px;
                    background:#111;
                    color:#fff;
                    text-decoration:none;
                    border-radius:8px;
                '
            >
                Back to My Orders
            </a>

        </div>
    ");

}

?>