<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_name = trim($_POST['product_name'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $size = trim($_POST['size'] ?? '');

    if (
        $product_name === "" ||
        $category_id <= 0 ||
        $description === "" ||
        $price <= 0 ||
        $stock_quantity < 0 ||
        $size === ""
    ) {
        $error = "Please fill all fields correctly.";
    } else {

        $image_name = "";

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

            $upload_dir = "../uploads/products/";

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $original_name = $_FILES['image']['name'];
            $tmp_name = $_FILES['image']['tmp_name'];

            $extension = strtolower(
                pathinfo($original_name, PATHINFO_EXTENSION)
            );

            $allowed_extensions = ["jpg", "jpeg", "png", "webp"];

            if (!in_array($extension, $allowed_extensions)) {

                $error = "Only JPG, JPEG, PNG and WEBP images are allowed.";

            } else {

                $image_name = time() . "_" . uniqid() . "." . $extension;

                $image_path = $upload_dir . $image_name;

                if (!move_uploaded_file($tmp_name, $image_path)) {
                    $error = "Image upload failed.";
                }
            }

        } else {
            $error = "Please select a product image.";
        }


        if ($error === "") {

            $stmt = $conn->prepare(
                "INSERT INTO products
                (product_name, category_id, description, price, stock_quantity, size, image, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );

            $stmt->bind_param(
                "sisdiss",
                $product_name,
                $category_id,
                $description,
                $price,
                $stock_quantity,
                $size,
                $image_name
            );

            if ($stmt->execute()) {

                $message = "Product added successfully!";

            } else {

                $error = "Database error: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Product - Maan Ghafar Garments</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6f8;
            color: #1f2937;
        }

        /* SIDEBAR */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #111827;
            padding: 25px 18px;
        }

        .logo {
            text-align: center;
            color: #b8860b;
            font-size: 19px;
            font-weight: bold;
            letter-spacing: 1px;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .admin-title {
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
            letter-spacing: 1px;
            margin-bottom: 35px;
        }

        .menu a {
            display: block;
            text-decoration: none;
            color: #d1d5db;
            padding: 14px 16px;
            margin-bottom: 8px;
            border-radius: 8px;
            transition: 0.3s;
        }

        .menu a:hover,
        .menu a.active {
            background: #b8860b;
            color: #ffffff;
        }

        .logout {
            position: absolute;
            left: 18px;
            right: 18px;
            bottom: 25px;
        }

        .logout a {
            display: block;
            text-align: center;
            text-decoration: none;
            color: #ffffff;
            background: #dc2626;
            padding: 12px;
            border-radius: 8px;
        }

        .logout a:hover {
            background: #b91c1c;
        }


        /* MAIN CONTENT */

        .main-content {
            margin-left: 250px;
            padding: 30px;
        }

        .topbar {
            background: #ffffff;
            padding: 22px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .topbar h1 {
            font-size: 28px;
            color: #111827;
            margin-bottom: 6px;
        }

        .topbar p {
            color: #6b7280;
        }


        /* FORM */

        .form-box {
            max-width: 850px;
            background: #ffffff;
            padding: 30px;
            border-radius: 14px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-size: 14px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            background: #f9fafb;
            font-size: 15px;
            outline: none;
            transition: 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #b8860b;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(184, 134, 11, 0.10);
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group input[type="file"] {
            background: #ffffff;
            padding: 11px;
        }

        .submit-btn {
            margin-top: 25px;
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 9px;
            background: #b8860b;
            color: #ffffff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .submit-btn:hover {
            background: #96700a;
            transform: translateY(-1px);
        }


        /* MESSAGES */

        .success-message {
            background: #dcfce7;
            color: #166534;
            padding: 13px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .error-message {
            background: #fee2e2;
            color: #b91c1c;
            padding: 13px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-weight: 600;
        }


        /* MOBILE */

        @media (max-width: 800px) {

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 20px;
            }

            .logo {
                margin-bottom: 10px;
            }

            .admin-title {
                margin-bottom: 15px;
            }

            .menu {
                display: flex;
                gap: 8px;
                overflow-x: auto;
            }

            .menu a {
                white-space: nowrap;
            }

            .logout {
                position: relative;
                left: auto;
                right: auto;
                bottom: auto;
                margin-top: 15px;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: auto;
            }
        }

        @media (max-width: 500px) {

            .main-content {
                padding: 15px;
            }

            .form-box {
                padding: 20px;
            }

            .topbar h1 {
                font-size: 23px;
            }
        }

    </style>

</head>

<body>


    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="logo">
            MAAN GHAFAR<br>
            GARMENTS
        </div>

        <div class="admin-title">
            ADMIN PANEL
        </div>

        <nav class="menu">

            <a href="dashboard.php">
                🏠 Dashboard
            </a>

            <a href="products.php" class="active">
                📦 Products
            </a>

            <a href="orders.php">
                🛒 Orders
            </a>

            <a href="customers.php">
                👥 Customers
            </a>

        </nav>

        <div class="logout">
            <a href="logout.php">
                Logout
            </a>
        </div>

    </aside>


    <!-- MAIN CONTENT -->

    <main class="main-content">

        <div class="topbar">

            <h1>Add Product</h1>

            <p>
                Add a new product to Maan Ghafar Garments
            </p>

        </div>


        <div class="form-box">

            <?php if ($message !== ""): ?>

                <div class="success-message">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php endif; ?>


            <?php if ($error !== ""): ?>

                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>


            <form method="POST" enctype="multipart/form-data">

                <div class="form-grid">


                    <div class="form-group">

                        <label for="product_name">
                            Product Name
                        </label>

                        <input
                            type="text"
                            id="product_name"
                            name="product_name"
                            placeholder="Enter product name"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="category_id">
                            Category ID
                        </label>

                        <input
                            type="number"
                            id="category_id"
                            name="category_id"
                            placeholder="Enter category ID"
                            required
                        >

                    </div>


                    <div class="form-group full-width">

                        <label for="description">
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            placeholder="Enter product description"
                            required
                        ></textarea>

                    </div>


                    <div class="form-group">

                        <label for="price">
                            Price (Rs.)
                        </label>

                        <input
                            type="number"
                            id="price"
                            name="price"
                            step="0.01"
                            min="0"
                            placeholder="3500"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="stock_quantity">
                            Stock Quantity
                        </label>

                        <input
                            type="number"
                            id="stock_quantity"
                            name="stock_quantity"
                            min="0"
                            placeholder="10"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="size">
                            Size
                        </label>

                        <input
                            type="text"
                            id="size"
                            name="size"
                            placeholder="S, M, L, XL"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="image">
                            Product Image
                        </label>

                        <input
                            type="file"
                            id="image"
                            name="image"
                            accept="image/jpeg,image/png,image/webp"
                            required
                        >

                    </div>

                </div>


                <button type="submit" class="submit-btn">
                    + Add Product
                </button>

            </form>

        </div>

    </main>

</body>

</html>