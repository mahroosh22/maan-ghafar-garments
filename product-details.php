<?php

require_once "config/database.php";

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    die("Invalid Product ID");
}

$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    die("Product Not Found");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?> - Maan Ghafar Garments</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <section class="product-details">

        <div class="product-details-image">
            <img src="assets/image/<?php echo htmlspecialchars($product['image']); ?>" alt="">
        </div>

        <div class="product-details-info">

            <h1><?php echo htmlspecialchars($product['product_name']); ?></h1>

            <p>
                <?php echo htmlspecialchars($product['description']); ?>
            </p>

            <h2>
                Rs. <?php echo number_format($product['price']); ?>
            </h2>

            <p>
                Size: <?php echo htmlspecialchars($product['size']); ?>
            </p>

            <p>
                Stock: <?php echo htmlspecialchars($product['stock_quantity']); ?>
            </p>

            <a href="index.php">← Back to Products</a>

        </div>

    </section>

</body>
</html>