<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Maan Ghafar Garments</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<header class="header">
    <div class="logo">Maan Ghafar Garments</div>

    <nav class="navbar">
        <a href="index.php">Home</a>
        <a href="products.php">Products</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
        <a href="my-orders.php">My Orders</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<section class="products">
    <h2>My Orders</h2>

    <?php if ($result->num_rows > 0): ?>

        <div class="product-container">

            <?php while ($order = $result->fetch_assoc()): ?>

                <div class="product-card">

                    <h3>Order #<?php echo $order['order_id']; ?></h3>

                    <p>
                        <strong>Date:</strong>
                        <?php echo $order['created_at']; ?>
                    </p>

                    <p>
                        <strong>Total:</strong>
                        Rs. <?php echo number_format($order['total_amount']); ?>
                    </p>

                    <p>
                        <strong>Payment:</strong>
                        <?php echo htmlspecialchars($order['payment_method']); ?>
                    </p>

                    <p>
                        <strong>Status:</strong>
                        <?php echo htmlspecialchars($order['status']); ?>
                    </p>

                </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <p>No orders found.</p>

    <?php endif; ?>

</section>

</body>
</html>