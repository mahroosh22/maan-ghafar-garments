<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";


/* =========================
   CSRF TOKEN
========================= */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];


/* =========================
   VARIABLES
========================= */

$error = "";
$success = "";

$upload_dir = "../uploads/categories/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
$max_file_size = 5 * 1024 * 1024;


/* =========================
   ADD CATEGORY
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_category'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {

        $error = "Invalid CSRF token.";

    } else {

        $category_name = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($category_name === '') {

            $error = "Category name is required.";

        } else {

            $image_name = null;

            if (
                isset($_FILES['image']) &&
                $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {

                    $error = "Image upload failed.";

                } elseif ($_FILES['image']['size'] > $max_file_size) {

                    $error = "Image size must be less than 5MB.";

                } else {

                    $extension = strtolower(
                        pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION)
                    );

                    if (!in_array($extension, $allowed_extensions)) {

                        $error = "Only JPG, JPEG, PNG and WEBP images are allowed.";

                    } else {

                        $image_name =
                            time() . "_" .
                            bin2hex(random_bytes(5)) .
                            "." . $extension;

                        $target_file = $upload_dir . $image_name;

                        if (!move_uploaded_file(
                            $_FILES['image']['tmp_name'],
                            $target_file
                        )) {

                            $error = "Failed to upload image.";
                            $image_name = null;
                        }
                    }
                }
            }


            if ($error === "") {

                $stmt = $conn->prepare("
                    INSERT INTO category
                    (category_name, description, image)
                    VALUES (?, ?, ?)
                ");

                $stmt->bind_param(
                    "sss",
                    $category_name,
                    $description,
                    $image_name
                );

                if ($stmt->execute()) {

                    $success = "Category added successfully.";

                } else {

                    if ($image_name && file_exists($upload_dir . $image_name)) {
                        unlink($upload_dir . $image_name);
                    }

                    $error = "Failed to add category.";
                }

                $stmt->close();
            }
        }
    }
}


/* =========================
   UPDATE CATEGORY
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_category'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {

        $error = "Invalid CSRF token.";

    } else {

        $category_id = (int)($_POST['category_id'] ?? 0);
        $category_name = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($category_id <= 0 || $category_name === '') {

            $error = "Invalid category information.";

        } else {

            $old_image = null;

            $stmt = $conn->prepare("
                SELECT image
                FROM category
                WHERE category_id = ?
            ");

            $stmt->bind_param("i", $category_id);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $old_image = $row['image'];
            }

            $stmt->close();

            $new_image = $old_image;


            /* Upload New Image */

            if (
                isset($_FILES['image']) &&
                $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {

                    $error = "Image upload failed.";

                } elseif ($_FILES['image']['size'] > $max_file_size) {

                    $error = "Image size must be less than 5MB.";

                } else {

                    $extension = strtolower(
                        pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION)
                    );

                    if (!in_array($extension, $allowed_extensions)) {

                        $error = "Only JPG, JPEG, PNG and WEBP images are allowed.";

                    } else {

                        $new_image =
                            time() . "_" .
                            bin2hex(random_bytes(5)) .
                            "." . $extension;

                        $target_file = $upload_dir . $new_image;

                        if (!move_uploaded_file(
                            $_FILES['image']['tmp_name'],
                            $target_file
                        )) {

                            $error = "Failed to upload image.";
                            $new_image = $old_image;
                        }
                    }
                }
            }


            /* Update Database */

            if ($error === "") {

                $stmt = $conn->prepare("
                    UPDATE category
                    SET
                        category_name = ?,
                        description = ?,
                        image = ?
                    WHERE category_id = ?
                ");

                $stmt->bind_param(
                    "sssi",
                    $category_name,
                    $description,
                    $new_image,
                    $category_id
                );

                if ($stmt->execute()) {

                    $success = "Category updated successfully.";

                    if (
                        $new_image !== $old_image &&
                        $old_image &&
                        file_exists($upload_dir . basename($old_image))
                    ) {

                        unlink(
                            $upload_dir . basename($old_image)
                        );
                    }

                } else {

                    if (
                        $new_image !== $old_image &&
                        $new_image &&
                        file_exists($upload_dir . basename($new_image))
                    ) {

                        unlink(
                            $upload_dir . basename($new_image)
                        );
                    }

                    $error = "Failed to update category.";
                }

                $stmt->close();
            }
        }
    }
}


/* =========================
   DELETE CATEGORY
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_category'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {

        $error = "Invalid CSRF token.";

    } else {

        $category_id = (int)($_POST['category_id'] ?? 0);

        if ($category_id <= 0) {

            $error = "Invalid category.";

        } else {

            $stmt = $conn->prepare("
                SELECT COUNT(*) AS total
                FROM products
                WHERE category_id = ?
            ");

            $stmt->bind_param("i", $category_id);
            $stmt->execute();

            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            $product_count = (int)$row['total'];

            $stmt->close();


            if ($product_count > 0) {

                $error =
                    "This category cannot be deleted because products are assigned to it.";

            } else {

                $stmt = $conn->prepare("
                    SELECT image
                    FROM category
                    WHERE category_id = ?
                ");

                $stmt->bind_param("i", $category_id);
                $stmt->execute();

                $result = $stmt->get_result();

                $image = null;

                if ($row = $result->fetch_assoc()) {
                    $image = $row['image'];
                }

                $stmt->close();


                $stmt = $conn->prepare("
                    DELETE FROM category
                    WHERE category_id = ?
                ");

                $stmt->bind_param("i", $category_id);

                if ($stmt->execute()) {

                    $success = "Category deleted successfully.";

                    if (
                        $image &&
                        file_exists($upload_dir . basename($image))
                    ) {

                        unlink(
                            $upload_dir . basename($image)
                        );
                    }

                } else {

                    $error = "Failed to delete category.";
                }

                $stmt->close();
            }
        }
    }
}


/* =========================
   FETCH CATEGORIES
========================= */

$categories = [];

$result = $conn->query("
    SELECT
        c.category_id,
        c.category_name,
        c.description,
        c.image,
        COUNT(p.product_id) AS product_count
    FROM category c
    LEFT JOIN products p
        ON p.category_id = c.category_id
    GROUP BY
        c.category_id,
        c.category_name,
        c.description,
        c.image
    ORDER BY c.category_id DESC
");

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
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

    <title>Manage Categories - Admin</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #333;
        }

        .container {
            width: 95%;
            max-width: 1200px;
            margin: 40px auto;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 15px;
            flex-wrap: wrap;
        }

        h1 {
            margin: 0;
            color: #222;
        }

        .btn {
            border: none;
            padding: 9px 14px;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            display: inline-block;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0069d9;
        }

        .btn-products {
            background: #28a745;
            color: white;
        }

        .btn-products:hover {
            background: #218838;
        }

        .btn-edit {
            background: #ffc107;
            color: #222;
        }

        .btn-edit:hover {
            background: #e0a800;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .card h2 {
            margin-top: 0;
        }

        form input,
        form textarea {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        textarea {
            resize: vertical;
            min-height: 90px;
        }

        /* =========================
           CATEGORY ROWS
        ========================== */

        .category-grid {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .category-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            min-height: 150px;
        }

        .category-image {
            width: 180px;
            height: 150px;
            flex-shrink: 0;
            background: #f2f2f2;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .category-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .no-image {
            color: #999;
            font-size: 14px;
        }

        .category-content {
            padding: 20px;
            flex: 1;
        }

        .category-content h3 {
            margin: 0 0 8px;
            font-size: 20px;
        }

        .category-content p {
            margin: 0 0 15px;
            color: #666;
            font-size: 14px;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .actions form {
            margin: 0;
        }


        /* =========================
           EDIT MODAL
        ========================== */

        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.55);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-box {
            background: white;
            width: 100%;
            max-width: 550px;
            border-radius: 12px;
            padding: 25px;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-box h2 {
            margin-top: 0;
            margin-bottom: 20px;
        }

        .close-modal {
            position: absolute;
            right: 18px;
            top: 12px;
            font-size: 28px;
            cursor: pointer;
            color: #777;
        }

        .close-modal:hover {
            color: #222;
        }

        .current-image {
            width: 140px;
            height: 100px;
            background: #f2f2f2;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .current-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .modal-actions button {
            flex: 1;
        }


        /* =========================
           MOBILE
        ========================== */

        @media (max-width: 600px) {

            .container {
                width: 92%;
                margin: 25px auto;
            }

            .category-card {
                display: block;
            }

            .category-image {
                width: 100%;
                height: 200px;
            }

            .category-content {
                padding: 16px;
            }

            .actions {
                flex-direction: column;
            }

            .actions .btn,
            .actions form,
            .actions form .btn {
                width: 100%;
                text-align: center;
            }

            .modal-box {
                padding: 20px;
            }

        }

    </style>

</head>

<body>

<div class="container">


    <!-- =========================
         TOP BAR
    ========================== -->

    <div class="top-bar">

        <h1>Manage Categories</h1>

        <a
            href="dashboard.php"
            class="btn btn-primary"
        >
            Back to Dashboard
        </a>

    </div>


    <!-- =========================
         ALERTS
    ========================== -->

    <?php if ($success !== ""): ?>

        <div class="alert success">
            <?= htmlspecialchars($success) ?>
        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="alert error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <!-- =========================
         ADD CATEGORY
    ========================== -->

    <div class="card">

        <h2>Add New Category</h2>

        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrf_token) ?>"
            >

            <label>Category Name</label>

            <input
                type="text"
                name="category_name"
                placeholder="Enter category name"
                required
            >


            <label>Description</label>

            <textarea
                name="description"
                placeholder="Enter category description"
            ></textarea>


            <label>Category Image</label>

            <input
                type="file"
                name="image"
                accept=".jpg,.jpeg,.png,.webp"
            >


            <button
                type="submit"
                name="add_category"
                class="btn btn-primary"
            >
                Add Category
            </button>

        </form>

    </div>


    <!-- =========================
         CATEGORY LIST
    ========================== -->

    <div class="category-grid">

        <?php if (empty($categories)): ?>

            <div class="card">
                <p>No categories found.</p>
            </div>

        <?php else: ?>


            <?php foreach ($categories as $category): ?>

                <?php

                $category_id =
                    (int)$category['category_id'];

                $category_name =
                    $category['category_name'];

                $description =
                    $category['description'];

                $image =
                    $category['image'];

                $product_count =
                    (int)$category['product_count'];

                ?>


                <div class="category-card">


                    <!-- CATEGORY IMAGE -->

                    <div class="category-image">

                        <?php if ($image): ?>

                            <img
                                src="../uploads/categories/<?= htmlspecialchars(basename($image)) ?>"
                                alt="<?= htmlspecialchars($category_name) ?>"
                            >

                        <?php else: ?>

                            <div class="no-image">
                                No Image
                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- CATEGORY CONTENT -->

                    <div class="category-content">

                        <h3>
                            <?= htmlspecialchars($category_name) ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars(
                                $description ?: 'No description available.'
                            ) ?>
                        </p>


                        <div class="actions">


                            <!-- VIEW PRODUCTS -->

                            <a
                                href="products.php?category=<?= $category_id ?>"
                                class="btn btn-products"
                            >
                                View Products
                            </a>


                            <!-- EDIT -->

                            <button
                                type="button"
                                class="btn btn-edit"
                                onclick='openEditModal(
                                    <?= json_encode($category_id) ?>,
                                    <?= json_encode($category_name) ?>,
                                    <?= json_encode($description) ?>,
                                    <?= json_encode($image) ?>
                                )'
                            >
                                Edit
                            </button>


                            <!-- DELETE -->

                            <?php if ($product_count === 0): ?>

                                <form
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this category?');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars($csrf_token) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="category_id"
                                        value="<?= $category_id ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="delete_category"
                                        class="btn btn-danger"
                                    >
                                        Delete
                                    </button>

                                </form>

                            <?php endif; ?>


                        </div>

                    </div>

                </div>

            <?php endforeach; ?>


        <?php endif; ?>

    </div>

</div>


<!-- =========================
     EDIT CATEGORY MODAL
========================= -->

<div
    id="editModal"
    class="modal"
    onclick="closeEditModal(event)"
>

    <div
        class="modal-box"
        onclick="event.stopPropagation()"
    >

        <span
            class="close-modal"
            onclick="closeEditModal()"
        >
            &times;
        </span>


        <h2>Edit Category</h2>


        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrf_token) ?>"
            >

            <input
                type="hidden"
                name="category_id"
                id="edit_category_id"
            >


            <label>Category Name</label>

            <input
                type="text"
                name="category_name"
                id="edit_category_name"
                required
            >


            <label>Description</label>

            <textarea
                name="description"
                id="edit_description"
            ></textarea>


            <label>Current Image</label>

            <div
                id="currentImageBox"
                class="current-image"
            >
                <span class="no-image">
                    No Image
                </span>
            </div>


            <label>Change Image</label>

            <input
                type="file"
                name="image"
                accept=".jpg,.jpeg,.png,.webp"
            >


            <div class="modal-actions">

                <button
                    type="submit"
                    name="update_category"
                    class="btn btn-primary"
                >
                    Update Category
                </button>

                <button
                    type="button"
                    class="btn btn-danger"
                    onclick="closeEditModal()"
                >
                    Cancel
                </button>

            </div>

        </form>

    </div>

</div>


<script>

    /* =========================
       OPEN EDIT MODAL
    ========================== */

    function openEditModal(
        id,
        name,
        description,
        image
    ) {

        document.getElementById("edit_category_id").value = id;

        document.getElementById("edit_category_name").value = name;

        document.getElementById("edit_description").value =
            description || "";


        const imageBox =
            document.getElementById("currentImageBox");


        if (image) {

            imageBox.innerHTML =
                '<img src="../uploads/categories/' +
                encodeURIComponent(
                    image.split('/').pop()
                ) +
                '" alt="Current Image">';

        } else {

            imageBox.innerHTML =
                '<span class="no-image">No Image</span>';
        }


        document.getElementById("editModal").style.display =
            "flex";
    }


    /* =========================
       CLOSE EDIT MODAL
    ========================== */

    function closeEditModal(event) {

        if (
            event &&
            event.target !==
            document.getElementById("editModal")
        ) {
            return;
        }

        document.getElementById("editModal").style.display =
            "none";
    }


    /* =========================
       ESC KEY
    ========================== */

    document.addEventListener(
        "keydown",
        function(event) {

            if (event.key === "Escape") {
                closeEditModal();
            }

        }
    );

</script>

</body>

</html>