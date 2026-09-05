```php
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

$order_id = intval($_GET['id'] ?? 0);

if ($order_id <= 0) {
    header("Location: orders.php");
    exit;
}

/* =========================
   ORDER + CUSTOMER
========================= */

$order_stmt = $conn->prepare("
    SELECT
        o.order_id,
        o.user_id,
        o.total_amount,
        o.status,
        o.shipping_address,
        o.payment_method,
        o.created_at,
        u.name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.order_id = ?
    LIMIT 1
");

$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();

$order_result = $order_stmt->get_result();
$order = $order_result->fetch_assoc();

$order_stmt->close();

if (!$order) {
    header("Location: orders.php");
    exit;
}


/* =========================
   ORDER ITEMS
========================= */

$item_stmt = $conn->prepare("
    SELECT
        oi.order_item_id,
        oi.product_id,
        oi.quantity,
        oi.price,
        p.product_name,
        p.image
    FROM order_items oi
    LEFT JOIN products p
        ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
    ORDER BY oi.order_item_id ASC
");

$item_stmt->bind_param("i", $order_id);
$item_stmt->execute();

$item_result = $item_stmt->get_result();

$items = [];

while ($row = $item_result->fetch_assoc()) {
    $items[] = $row;
}

$item_stmt->close();


/* =========================
   PAYMENT
========================= */

$payment = null;

$payment_stmt = $conn->prepare("
    SELECT
        payment_method,
        amount,
        transaction_id,
        payment_status,
        paid_at
    FROM payments
    WHERE order_id = ?
    ORDER BY payment_id DESC
    LIMIT 1
");

$payment_stmt->bind_param("i", $order_id);
$payment_stmt->execute();

$payment_result = $payment_stmt->get_result();
$payment = $payment_result->fetch_assoc();

$payment_stmt->close();


/* =========================
   SHIPPING
========================= */

$shipping = null;

$shipping_stmt = $conn->prepare("
    SELECT
        shipping_address,
        city,
        shipping_status,
        shipped_at,
        postal_code,
        country
    FROM shipping
    WHERE order_id = ?
    LIMIT 1
");

$shipping_stmt->bind_param("i", $order_id);
$shipping_stmt->execute();

$shipping_result = $shipping_stmt->get_result();
$shipping = $shipping_result->fetch_assoc();

$shipping_stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order #<?php echo $order['order_id']; ?> - Admin</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            color: #222;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 230px;
            height: 100vh;
            background: #111827;
            padding: 25px 15px;
        }

        .sidebar h2 {
            color: #fff;
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar a {
            display: block;
            color: #d1d5db;
            text-decoration: none;
            padding: 13px 15px;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .sidebar a:hover {
            background: #374151;
            color: #fff;
        }

        .main {
            margin-left: 230px;
            padding: 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 15px;
        }

        .topbar h1 {
            font-size: 28px;
        }

        .back-btn {
            background: #111827;
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 7px;
        }

        .back-btn:hover {
            background: #374151;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }

        .card h2 {
            font-size: 20px;
            margin-bottom: 18px;
            border-bottom: 1px solid #eee;
            padding-bottom: 12px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 9px 0;
            border-bottom: 1px solid #f1f1f1;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .label {
            font-weight: bold;
            color: #555;
        }

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            background: #eef2ff;
            color: #3730a3;
            font-size: 13px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .products-card {
            margin-bottom: 20px;
        }

        .product {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .product:last-child {
            border-bottom: none;
        }

        .product-image {
            width: 75px;
            height: 75px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #ddd;
            background: #f3f4f6;
        }

        .no-image {
            width: 75px;
            height: 75px;
            border-radius: 8px;
            border: 1px solid #ddd;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777;
            font-size: 12px;
            text-align: center;
        }

        .product-info {
            flex: 1;
        }

        .product-info h3 {
            font-size: 16px;
            margin-bottom: 6px;
        }

        .product-info p {
            color: #666;
            font-size: 14px;
            margin: 3px 0;
        }

        .product-total {
            font-weight: bold;
            font-size: 16px;
        }

        .total-box {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 2px solid #111827;
            display: flex;
            justify-content: space-between;
            font-size: 20px;
            font-weight: bold;
        }

        .empty {
            color: #777;
            padding: 15px 0;
        }

        @media (max-width: 900px) {

            .sidebar {
                width: 190px;
            }

            .main {
                margin-left: 190px;
                padding: 20px;
            }

            .grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 650px) {

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 15px;
            }

            .sidebar h2 {
                margin-bottom: 15px;
            }

            .sidebar a {
                display: inline-block;
                margin-right: 5px;
                margin-bottom: 5px;
                padding: 9px 12px;
                font-size: 13px;
            }

            .main {
                margin-left: 0;
                padding: 15px;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .topbar h1 {
                font-size: 23px;
            }

            .product {
                align-items: flex-start;
            }

            .product-image,
            .no-image {
                width: 60px;
                height: 60px;
            }

            .product-total {
                font-size: 14px;
            }

            .info-row {
                flex-direction: column;
                gap: 4px;
            }
        }

    </style>

</head>

<body>


<!-- SIDEBAR -->

<div class="sidebar">

    <h2>Maan Ghafar</h2>

    <a href="dashboard.php">Dashboard</a>

    <a href="products.php">Products</a>

    <a href="orders.php">Orders</a>

    <a href="customers.php">Customers</a>

    <a href="logout.php">Logout</a>

</div>


<!-- MAIN -->

<div class="main">


    <div class="topbar">

        <h1>Order #<?php echo htmlspecialchars($order['order_id']); ?></h1>

        <a href="orders.php" class="back-btn">
            ← Back to Orders
        </a>

    </div>


    <!-- ORDER + CUSTOMER -->

    <div class="grid">


        <!-- ORDER INFO -->

        <div class="card">

            <h2>Order Information</h2>

            <div class="info-row">

                <span class="label">Order ID</span>

                <span>
                    #<?php echo htmlspecialchars($order['order_id']); ?>
                </span>

            </div>

            <div class="info-row">

                <span class="label">Order Date</span>

                <span>
                    <?php echo htmlspecialchars($order['created_at']); ?>
                </span>

            </div>

            <div class="info-row">

                <span class="label">Status</span>

                <span class="status">
                    <?php echo htmlspecialchars($order['status']); ?>
                </span>

            </div>

            <div class="info-row">

                <span class="label">Payment Method</span>

                <span>
                    <?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?>
                </span>

            </div>

            <div class="info-row">

                <span class="label">Total Amount</span>

                <strong>
                    Rs. <?php echo number_format((float)$order['total_amount'], 2); ?>
                </strong>

            </div>

        </div>


        <!-- CUSTOMER INFO -->

        <div class="card">

            <h2>Customer Information</h2>

            <div class="info-row">

                <span class="label">Name</span>

                <span>
                    <?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?>
                </span>

            </div>

            <div class="info-row">

                <span class="label">Email</span>

                <span>
                    <?php echo htmlspecialchars($order['customer_email'] ?? 'N/A'); ?>
                </span>

            </div>

            <div class="info-row">

                <span class="label">Phone</span>

                <span>
                    <?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?>
                </span>

            </div>

            <div class="info-row">

                <span class="label">Shipping Address</span>

                <span>
                    <?php echo nl2br(htmlspecialchars($order['shipping_address'] ?? 'N/A')); ?>
                </span>

            </div>

        </div>

    </div>


    <!-- ORDERED PRODUCTS -->

    <div class="card products-card">

        <h2>Ordered Products</h2>


        <?php if (count($items) > 0): ?>

            <?php foreach ($items as $item): ?>

                <?php

                $is_deleted = empty($item['product_id']) || empty($item['product_name']);

                $product_name = $is_deleted
                    ? 'Deleted Product'
                    : $item['product_name'];

                $subtotal = (float)$item['price'] * (int)$item['quantity'];

                ?>


                <div class="product">


                    <!-- IMAGE -->

                    <?php if (!$is_deleted && !empty($item['image'])): ?>

                        <img
                            src="../uploads/products/<?php echo htmlspecialchars($item['image']); ?>"
                            alt="<?php echo htmlspecialchars($product_name); ?>"
                            class="product-image"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                        >

                        <div class="no-image" style="display:none;">
                            No Image
                        </div>

                    <?php else: ?>

                        <div class="no-image">
                            Deleted
                        </div>

                    <?php endif; ?>


                    <!-- PRODUCT INFO -->

                    <div class="product-info">

                        <h3>
                            <?php echo htmlspecialchars($product_name); ?>
                        </h3>


                        <p>

                            Product ID:

                            <?php if ($is_deleted): ?>

                                <strong>Deleted</strong>

                            <?php else: ?>

                                #<?php echo htmlspecialchars($item['product_id']); ?>

                            <?php endif; ?>

                        </p>


                        <p>
                            Price:
                            Rs. <?php echo number_format((float)$item['price'], 2); ?>
                        </p>


                        <p>
                            Quantity:
                            <?php echo htmlspecialchars($item['quantity']); ?>
                        </p>

                    </div>


                    <!-- SUBTOTAL -->

                    <div class="product-total">

                        Rs. <?php echo number_format($subtotal, 2); ?>

                    </div>


                </div>

            <?php endforeach; ?>


            <!-- TOTAL -->

            <div class="total-box">

                <span>Total</span>

                <span>
                    Rs. <?php echo number_format((float)$order['total_amount'], 2); ?>
                </span>

            </div>


        <?php else: ?>

            <p class="empty">
                No products found for this order.
            </p>

        <?php endif; ?>

    </div>


    <!-- PAYMENT + SHIPPING -->

    <div class="grid">


        <!-- PAYMENT -->

        <div class="card">

            <h2>Payment Details</h2>


            <?php if ($payment): ?>

                <div class="info-row">

                    <span class="label">Payment Method</span>

                    <span>
                        <?php echo htmlspecialchars($payment['payment_method'] ?? 'N/A'); ?>
                    </span>

                </div>


                <div class="info-row">

                    <span class="label">Amount</span>

                    <span>
                        Rs. <?php echo number_format((float)$payment['amount'], 2); ?>
                    </span>

                </div>


                <div class="info-row">

                    <span class="label">Transaction ID</span>

                    <span>
                        <?php echo htmlspecialchars($payment['transaction_id'] ?? 'N/A'); ?>
                    </span>

                </div>


                <div class="info-row">

                    <span class="label">Payment Status</span>

                    <span class="status">
                        <?php echo htmlspecialchars($payment['payment_status'] ?? 'N/A'); ?>
                    </span>

                </div>


                <div class="info-row">

                    <span class="label">Paid At</span>

                    <span>
                        <?php echo htmlspecialchars($payment['paid_at'] ?? 'N/A'); ?>
                    </span>

                </div>


            <?php else: ?>

                <p class="empty">
                    No payment record found.
                </p>

            <?php endif; ?>

        </div>


        <!-- SHIPPING -->

        <div class="card">

            <h2>Shipping Details</h2>


            <?php if ($shipping): ?>

                <div class="info-row">

                    <span class="label">Address</span>

                    <span>
                        <?php echo nl2br(htmlspecialchars($shipping['shipping_address'] ?? 'N/A')); ?>
                    </span>

                </div>


                <div class="info-row">

                    <span class="label">City</span>

                    <span>
                        <?php echo htmlspecialchars($shipping['city'] ?? 'N/A'); ?>
                    </span>

                </div>


                <div class="info-row">

                    <span class="label">Postal Code</span>

                    <span>
                        <?php echo htmlspecialchars($shipping['postal_code'] ?? 'N/A'); ?>
                    </span>

                </div>


                <div class="info-row">

                    <span class="label">Country</span>

                    <span>
                        <?php echo htmlspecialchars($shipping['country'] ?? 'N/A'); ?>
                    </span>

                </div>


                <div class="info-row">

                    <span class="label">Shipping Status</span>

                    <span class="status">
                        <?php echo htmlspecialchars($shipping['shipping_status'] ?? 'N/A'); ?>
                    </span>

                </div>


                <div class="info-row">

                    <span class="label">Shipped At</span>

                    <span>
                        <?php echo htmlspecialchars($shipping['shipped_at'] ?? 'N/A'); ?>
                    </span>

                </div>


            <?php else: ?>

                <p class="empty">
                    No shipping record found.
                </p>

            <?php endif; ?>

        </div>

    </div>


</div>

</body>

</html>
```
