
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

$message = "";
$error = "";


/* =========================================================
   DELETE PRODUCT
   IMPORTANT:
   order_items.product_id must be NULLABLE
   and FK should use ON DELETE SET NULL.
   This keeps old order history safe.
========================================================= */

if (isset($_GET['delete'])) {

    $product_id = intval($_GET['delete']);

    if ($product_id > 0) {

        /* GET PRODUCT IMAGE */

        $stmt = $conn->prepare(
            "SELECT image FROM products WHERE product_id = ?"
        );

        $stmt->bind_param("i", $product_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        $stmt->close();


        if ($product) {

            /* DELETE PRODUCT */

            $stmt = $conn->prepare(
                "DELETE FROM products WHERE product_id = ?"
            );

            $stmt->bind_param("i", $product_id);

            if ($stmt->execute()) {

                /*
                 * Because order_items.product_id is configured
                 * with ON DELETE SET NULL, old orders remain safe.
                 */

                /* DELETE PRODUCT IMAGE */

                if (!empty($product['image'])) {

                    $image_path =
                        "../uploads/products/" .
                        $product['image'];

                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }

                $stmt->close();

                header(
                    "Location: products.php?deleted=1"
                );

                exit;

            } else {

                $error =
                    "Product could not be deleted. " .
                    "Please make sure the order_items foreign key " .
                    "uses ON DELETE SET NULL.";

                $stmt->close();
            }

        } else {

            $error = "Product not found.";
        }
    }
}


/* =========================================================
   SUCCESS MESSAGE AFTER DELETE
========================================================= */

if (isset($_GET['deleted'])) {

    $message =
        "Product deleted successfully! Old orders are safe.";
}


/* =========================================================
   EDIT PRODUCT
========================================================= */

$edit_product = null;

if (isset($_GET['edit'])) {

    $edit_id = intval($_GET['edit']);

    if ($edit_id > 0) {

        $stmt = $conn->prepare(
            "SELECT *
             FROM products
             WHERE product_id = ?"
        );

        $stmt->bind_param("i", $edit_id);
        $stmt->execute();

        $result = $stmt->get_result();

        $edit_product = $result->fetch_assoc();

        $stmt->close();

        if (!$edit_product) {

            $error =
                "Product not found.";
        }
    }
}


/* =========================================================
   ADD / UPDATE PRODUCT
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action =
        $_POST['action'] ?? 'add';

    $product_name =
        trim($_POST['product_name'] ?? '');

    $category_id =
        intval($_POST['category_id'] ?? 0);

    $description =
        trim($_POST['description'] ?? '');

    $price =
        floatval($_POST['price'] ?? 0);

    $stock_quantity =
        intval($_POST['stock_quantity'] ?? 0);

    $size =
        trim($_POST['size'] ?? '');


    /* VALIDATION */

    if (
        $product_name === "" ||
        $category_id <= 0 ||
        $description === "" ||
        $price <= 0 ||
        $stock_quantity < 0 ||
        $size === ""
    ) {

        $error =
            "Please fill all fields correctly.";

    } else {


        /* =====================================================
           UPDATE PRODUCT
        ===================================================== */

        if ($action === "update") {

            $product_id =
                intval($_POST['product_id'] ?? 0);


            $stmt = $conn->prepare(
                "SELECT image
                 FROM products
                 WHERE product_id = ?"
            );

            $stmt->bind_param(
                "i",
                $product_id
            );

            $stmt->execute();

            $result =
                $stmt->get_result();

            $old_product =
                $result->fetch_assoc();

            $stmt->close();


            if (!$old_product) {

                $error =
                    "Product not found.";

            } else {

                $image_name =
                    $old_product['image'];


                /* NEW IMAGE */

                if (
                    isset($_FILES['image']) &&
                    $_FILES['image']['error'] ===
                    UPLOAD_ERR_OK
                ) {

                    $upload_dir =
                        "../uploads/products/";


                    if (!is_dir($upload_dir)) {

                        mkdir(
                            $upload_dir,
                            0777,
                            true
                        );
                    }


                    $original_name =
                        $_FILES['image']['name'];

                    $tmp_name =
                        $_FILES['image']['tmp_name'];


                    $extension =
                        strtolower(
                            pathinfo(
                                $original_name,
                                PATHINFO_EXTENSION
                            )
                        );


                    $allowed_extensions = [
                        "jpg",
                        "jpeg",
                        "png",
                        "webp"
                    ];


                    if (
                        !in_array(
                            $extension,
                            $allowed_extensions,
                            true
                        )
                    ) {

                        $error =
                            "Only JPG, JPEG, PNG and WEBP images are allowed.";

                    } else {

                        $new_image_name =
                            time() .
                            "_" .
                            uniqid() .
                            "." .
                            $extension;


                        $image_path =
                            $upload_dir .
                            $new_image_name;


                        if (
                            move_uploaded_file(
                                $tmp_name,
                                $image_path
                            )
                        ) {

                            /* DELETE OLD IMAGE */

                            if (!empty($image_name)) {

                                $old_image_path =
                                    $upload_dir .
                                    $image_name;

                                if (
                                    file_exists(
                                        $old_image_path
                                    )
                                ) {

                                    unlink(
                                        $old_image_path
                                    );
                                }
                            }


                            $image_name =
                                $new_image_name;

                        } else {

                            $error =
                                "Image upload failed.";
                        }
                    }
                }


                if ($error === "") {

                    $stmt = $conn->prepare(
                        "UPDATE products
                         SET
                            product_name = ?,
                            category_id = ?,
                            description = ?,
                            price = ?,
                            stock_quantity = ?,
                            size = ?,
                            image = ?
                         WHERE product_id = ?"
                    );


                    $stmt->bind_param(
                        "sisdissi",
                        $product_name,
                        $category_id,
                        $description,
                        $price,
                        $stock_quantity,
                        $size,
                        $image_name,
                        $product_id
                    );


                    if ($stmt->execute()) {

                        $stmt->close();

                        header(
                            "Location: products.php?updated=1"
                        );

                        exit;

                    } else {

                        $error =
                            "Database error: " .
                            $stmt->error;

                        $stmt->close();
                    }
                }
            }


        } else {


            /* =================================================
               ADD PRODUCT
            ================================================= */

            $image_name = "";


            if (
                isset($_FILES['image']) &&
                $_FILES['image']['error'] ===
                UPLOAD_ERR_OK
            ) {

                $upload_dir =
                    "../uploads/products/";


                if (!is_dir($upload_dir)) {

                    mkdir(
                        $upload_dir,
                        0777,
                        true
                    );
                }


                $original_name =
                    $_FILES['image']['name'];

                $tmp_name =
                    $_FILES['image']['tmp_name'];


                $extension =
                    strtolower(
                        pathinfo(
                            $original_name,
                            PATHINFO_EXTENSION
                        )
                    );


                $allowed_extensions = [
                    "jpg",
                    "jpeg",
                    "png",
                    "webp"
                ];


                if (
                    !in_array(
                        $extension,
                        $allowed_extensions,
                        true
                    )
                ) {

                    $error =
                        "Only JPG, JPEG, PNG and WEBP images are allowed.";

                } else {

                    $image_name =
                        time() .
                        "_" .
                        uniqid() .
                        "." .
                        $extension;


                    $image_path =
                        $upload_dir .
                        $image_name;


                    if (
                        !move_uploaded_file(
                            $tmp_name,
                            $image_path
                        )
                    ) {

                        $error =
                            "Image upload failed.";
                    }
                }

            } else {

                $error =
                    "Please select a product image.";
            }


            if ($error === "") {

                $stmt = $conn->prepare(
                    "INSERT INTO products
                    (
                        product_name,
                        category_id,
                        description,
                        price,
                        stock_quantity,
                        size,
                        image,
                        created_at
                    )
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

                    $stmt->close();

                    header(
                        "Location: products.php?added=1"
                    );

                    exit;

                } else {

                    $error =
                        "Database error: " .
                        $stmt->error;

                    $stmt->close();
                }
            }
        }
    }
}


/* =========================================================
   SUCCESS MESSAGES
========================================================= */

if (isset($_GET['added'])) {

    $message =
        "Product added successfully!";
}


if (isset($_GET['updated'])) {

    $message =
        "Product updated successfully!";
}


/* =========================================================
   PRODUCTS LIST
========================================================= */

$search =
    trim($_GET['search'] ?? '');


if ($search !== "") {

    $search_value =
        "%" . $search . "%";


    $stmt = $conn->prepare(
        "SELECT *
         FROM products
         WHERE
            product_name LIKE ?
            OR description LIKE ?
            OR size LIKE ?
         ORDER BY product_id DESC"
    );


    $stmt->bind_param(
        "sss",
        $search_value,
        $search_value,
        $search_value
    );


    $stmt->execute();

    $products =
        $stmt->get_result();

} else {

    $products =
        $conn->query(
            "SELECT *
             FROM products
             ORDER BY product_id DESC"
        );
}

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
        Products - Maan Ghafar Garments
    </title>


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


        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;

            width: 250px;
            height: 100vh;

            background: #111827;

            padding: 25px 18px;

            z-index: 999;
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


        /* =========================
           MAIN
        ========================= */

        .main-content {
            margin-left: 250px;

            padding: 30px;
        }


        .topbar {
            background: #ffffff;

            padding: 22px 25px;

            border-radius: 12px;

            margin-bottom: 25px;

            box-shadow:
                0 3px 12px
                rgba(0, 0, 0, 0.06);
        }


        .topbar h1 {
            font-size: 28px;

            color: #111827;

            margin-bottom: 6px;
        }


        .topbar p {
            color: #6b7280;
        }


        /* =========================
           MESSAGES
        ========================= */

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


        /* =========================
           FORM
        ========================= */

        .form-box {
            background: #ffffff;

            padding: 30px;

            border-radius: 14px;

            box-shadow:
                0 3px 12px
                rgba(0, 0, 0, 0.06);

            margin-bottom: 30px;
        }


        .form-title {
            font-size: 21px;

            margin-bottom: 22px;

            color: #111827;
        }


        .form-grid {
            display: grid;

            grid-template-columns:
                1fr 1fr;

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
        }


        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #b8860b;

            background: #ffffff;
        }


        .form-group textarea {
            min-height: 110px;

            resize: vertical;
        }


        .form-group input[type="file"] {
            background: #ffffff;
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
        }


        .submit-btn:hover {
            background: #96700a;
        }


        .cancel-btn {
            display: block;

            text-align: center;

            text-decoration: none;

            margin-top: 10px;

            padding: 13px;

            border-radius: 9px;

            background: #6b7280;

            color: #ffffff;

            font-weight: bold;
        }


        /* =========================
           PRODUCTS SECTION
        ========================= */

        .products-box {
            background: #ffffff;

            padding: 25px;

            border-radius: 14px;

            box-shadow:
                0 3px 12px
                rgba(0, 0, 0, 0.06);
        }


        .products-header {
            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 20px;
        }


        .products-header h2 {
            color: #111827;
        }


        .search-form {
            display: flex;

            gap: 8px;
        }


        .search-form input {
            padding: 10px 12px;

            border: 1px solid #d1d5db;

            border-radius: 7px;

            outline: none;
        }


        .search-btn {
            border: none;

            background: #111827;

            color: #ffffff;

            padding: 10px 15px;

            border-radius: 7px;

            cursor: pointer;
        }


        /* =========================
           TABLE
        ========================= */

        .table-wrapper {
            width: 100%;

            overflow-x: auto;
        }


        table {
            width: 100%;

            min-width: 1000px;

            border-collapse: collapse;
        }


        th,
        td {
            padding: 13px 14px;

            text-align: left;

            border-bottom:
                1px solid #eeeeee;
        }


        th {
            background: #f5f5f5;

            color: #374151;

            font-size: 14px;
        }


        td {
            font-size: 14px;

            color: #4b5563;

            vertical-align: middle;
        }


        tr:hover {
            background: #fafafa;
        }


        .product-img {
            width: 65px;

            height: 65px;

            object-fit: cover;

            border-radius: 8px;

            border: 1px solid #eeeeee;
        }


        .no-image {
            width: 65px;

            height: 65px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #eeeeee;

            border-radius: 8px;

            font-size: 11px;

            color: #777;
        }


        .stock {
            font-weight: bold;
        }


        .out-stock {
            color: #dc2626;
        }


        .low-stock {
            color: #d97706;
        }


        .in-stock {
            color: #16a34a;
        }


        /* =========================
           ACTION BUTTONS
        ========================= */

        .edit-btn,
        .delete-btn {
            display: inline-block;

            text-decoration: none;

            padding: 7px 11px;

            border-radius: 6px;

            font-size: 12px;

            font-weight: bold;

            margin-right: 4px;
        }


        .edit-btn {
            background: #2563eb;

            color: #ffffff;
        }


        .edit-btn:hover {
            background: #1d4ed8;
        }


        .delete-btn {
            background: #dc2626;

            color: #ffffff;
        }


        .delete-btn:hover {
            background: #b91c1c;
        }


        .empty-message {
            text-align: center;

            padding: 40px 20px;

            color: #6b7280;
        }


        /* =========================
           MOBILE
        ========================= */

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


            .products-header {
                flex-direction: column;

                align-items: stretch;
            }


            .search-form {
                width: 100%;
            }


            .search-form input {
                flex: 1;
            }
        }


        @media (max-width: 500px) {

            .main-content {
                padding: 15px;
            }


            .form-box,
            .products-box {
                padding: 20px;
            }


            .topbar h1 {
                font-size: 23px;
            }
        }

    </style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

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


<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<main class="main-content">


    <div class="topbar">

        <h1>
            Products
        </h1>


        <p>
            Manage Maan Ghafar Garments products
        </p>

    </div>


    <?php if ($message !== ""): ?>

        <div class="success-message">

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="error-message">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <!-- =================================================
         ADD / EDIT FORM
    ================================================= -->

    <div class="form-box">


        <h2 class="form-title">

            <?php
            echo $edit_product
                ? "Edit Product"
                : "Add New Product";
            ?>

        </h2>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <input
                type="hidden"
                name="action"
                value="<?php
                echo $edit_product
                    ? 'update'
                    : 'add';
                ?>"
            >


            <?php if ($edit_product): ?>

                <input
                    type="hidden"
                    name="product_id"
                    value="<?php
                    echo $edit_product['product_id'];
                    ?>"
                >

            <?php endif; ?>


            <div class="form-grid">


                <div class="form-group">

                    <label>
                        Product Name
                    </label>

                    <input
                        type="text"
                        name="product_name"
                        value="<?php
                        echo htmlspecialchars(
                            $edit_product['product_name']
                            ?? ''
                        );
                        ?>"
                        placeholder="Enter product name"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Category ID
                    </label>

                    <input
                        type="number"
                        name="category_id"
                        value="<?php
                        echo htmlspecialchars(
                            $edit_product['category_id']
                            ?? ''
                        );
                        ?>"
                        placeholder="Enter category ID"
                        required
                    >

                </div>


                <div class="form-group full-width">

                    <label>
                        Description
                    </label>

                    <textarea
                        name="description"
                        placeholder="Enter product description"
                        required
                    ><?php
                    echo htmlspecialchars(
                        $edit_product['description']
                        ?? ''
                    );
                    ?></textarea>

                </div>


                <div class="form-group">

                    <label>
                        Price (Rs.)
                    </label>

                    <input
                        type="number"
                        name="price"
                        step="0.01"
                        min="0"
                        value="<?php
                        echo htmlspecialchars(
                            $edit_product['price']
                            ?? ''
                        );
                        ?>"
                        placeholder="3500"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Stock Quantity
                    </label>

                    <input
                        type="number"
                        name="stock_quantity"
                        min="0"
                        value="<?php
                        echo htmlspecialchars(
                            $edit_product['stock_quantity']
                            ?? ''
                        );
                        ?>"
                        placeholder="10"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Size
                    </label>

                    <input
                        type="text"
                        name="size"
                        value="<?php
                        echo htmlspecialchars(
                            $edit_product['size']
                            ?? ''
                        );
                        ?>"
                        placeholder="S, M, L, XL"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Product Image
                    </label>

                    <input
                        type="file"
                        name="image"
                        accept="image/jpeg,image/png,image/webp"
                        <?php
                        echo $edit_product
                            ? ''
                            : 'required';
                        ?>
                    >


                    <?php if (
                        $edit_product &&
                        !empty($edit_product['image'])
                    ): ?>

                        <small
                            style="
                                margin-top:8px;
                                color:#6b7280;
                            "
                        >

                            Current image:
                            <?php
                            echo htmlspecialchars(
                                $edit_product['image']
                            );
                            ?>

                        </small>

                    <?php endif; ?>

                </div>


            </div>


            <button
                type="submit"
                class="submit-btn"
            >

                <?php
                echo $edit_product
                    ? "Update Product"
                    : "+ Add Product";
                ?>

            </button>


            <?php if ($edit_product): ?>

                <a
                    href="products.php"
                    class="cancel-btn"
                >
                    Cancel Edit
                </a>

            <?php endif; ?>


        </form>

    </div>


    <!-- =================================================
         PRODUCTS LIST
    ================================================= -->

    <div class="products-box">


        <div class="products-header">


            <h2>
                All Products
            </h2>


            <form
                method="GET"
                class="search-form"
            >

                <input
                    type="text"
                    name="search"
                    value="<?php
                    echo htmlspecialchars($search);
                    ?>"
                    placeholder="Search product..."
                >


                <button
                    type="submit"
                    class="search-btn"
                >
                    Search
                </button>

            </form>

        </div>


        <?php if (
            $products &&
            $products->num_rows > 0
        ): ?>


            <div class="table-wrapper">


                <table>


                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Image</th>

                            <th>Product</th>

                            <th>Category</th>

                            <th>Price</th>

                            <th>Stock</th>

                            <th>Size</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php
                        while (
                            $product =
                            $products->fetch_assoc()
                        ):
                        ?>


                            <tr>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $product['product_id']
                                    );
                                    ?>

                                </td>


                                <td>


                                    <?php

                                    $image_path =
                                        "../uploads/products/" .
                                        $product['image'];

                                    if (
                                        !empty(
                                            $product['image']
                                        ) &&
                                        file_exists(
                                            $image_path
                                        )
                                    ):

                                    ?>


                                        <img
                                            src="<?php
                                            echo htmlspecialchars(
                                                $image_path
                                            );
                                            ?>"
                                            class="product-img"
                                            alt="Product"
                                        >


                                    <?php else: ?>


                                        <div class="no-image">
                                            No Image
                                        </div>


                                    <?php endif; ?>


                                </td>


                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $product['product_name']
                                        );
                                        ?>

                                    </strong>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $product['category_id']
                                    );
                                    ?>

                                </td>


                                <td>

                                    Rs.

                                    <?php
                                    echo number_format(
                                        (float)$product['price'],
                                        2
                                    );
                                    ?>

                                </td>


                                <td>


                                    <?php

                                    $stock =
                                        intval(
                                            $product[
                                                'stock_quantity'
                                            ]
                                        );


                                    if ($stock <= 0) {

                                        $stock_class =
                                            "out-stock";

                                    } elseif ($stock <= 5) {

                                        $stock_class =
                                            "low-stock";

                                    } else {

                                        $stock_class =
                                            "in-stock";
                                    }

                                    ?>


                                    <span
                                        class="stock <?php
                                        echo $stock_class;
                                        ?>"
                                    >

                                        <?php
                                        echo $stock;
                                        ?>

                                    </span>


                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $product['size']
                                    );
                                    ?>

                                </td>


                                <td>


                                    <a
                                        href="products.php?edit=<?php
                                        echo $product['product_id'];
                                        ?>"
                                        class="edit-btn"
                                    >
                                        Edit
                                    </a>


                                    <a
                                        href="products.php?delete=<?php
                                        echo $product['product_id'];
                                        ?>"
                                        class="delete-btn"
                                        onclick="
                                            return confirm(
                                                'Are you sure you want to delete this product? Old orders will remain safe. Continue?'
                                            );
                                        "
                                    >
                                        Delete
                                    </a>


                                </td>


                            </tr>


                        <?php endwhile; ?>


                    </tbody>


                </table>


            </div>


        <?php else: ?>


            <div class="empty-message">

                <?php

                if ($search !== "") {

                    echo
                        "No products found for your search.";

                } else {

                    echo
                        "No products found.";
                }

                ?>

            </div>


        <?php endif; ?>


    </div>


</main>


</body>

</html>