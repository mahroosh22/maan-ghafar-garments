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
   SAFE OUTPUT
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   IMAGE SETTINGS
========================================================= */

$allowed_mime_types = [
    "image/jpeg",
    "image/png",
    "image/webp"
];

$allowed_extensions = [
    "jpg",
    "jpeg",
    "png",
    "webp"
];

$max_image_size = 5 * 1024 * 1024;


/* =========================================================
   DELETE PRODUCT
========================================================= */

if (isset($_GET['delete'])) {

    $product_id = filter_var(
        $_GET['delete'],
        FILTER_VALIDATE_INT
    );

    if (!$product_id || $product_id <= 0) {

        $error = "Invalid product.";

    } else {

        $stmt = $conn->prepare("
            SELECT image
            FROM products
            WHERE product_id = ?
            LIMIT 1
        ");

        if (!$stmt) {

            $error = "Unable to find product.";

        } else {

            $stmt->bind_param(
                "i",
                $product_id
            );

            if (!$stmt->execute()) {

                $error = "Unable to find product.";
                $stmt->close();

            } else {

                $result = $stmt->get_result();
                $product = $result->fetch_assoc();

                $stmt->close();

                if (!$product) {

                    $error = "Product not found.";

                } else {

                    $stmt = $conn->prepare("
                        DELETE FROM products
                        WHERE product_id = ?
                    ");

                    if (!$stmt) {

                        $error =
                            "Product could not be deleted.";

                    } else {

                        $stmt->bind_param(
                            "i",
                            $product_id
                        );

                        if ($stmt->execute()) {

                            $stmt->close();

                            if (!empty($product['image'])) {

                                $image_path =
                                    "../uploads/products/" .
                                    basename(
                                        $product['image']
                                    );

                                if (is_file($image_path)) {
                                    @unlink($image_path);
                                }
                            }

                            header(
                                "Location: products.php?deleted=1"
                            );

                            exit;

                        } else {

                            $error =
                                "Product could not be deleted. " .
                                "Please check the product order relationship.";

                            $stmt->close();
                        }
                    }
                }
            }
        }
    }
}


/* =========================================================
   SUCCESS MESSAGE AFTER DELETE
========================================================= */

if (
    isset($_GET['deleted']) &&
    $error === ""
) {

    $message =
        "Product deleted successfully! Old orders are safe.";
}


/* =========================================================
   LOAD CATEGORIES
========================================================= */

$categories = [];

$category_result = $conn->query("
    SELECT
        category_id,
        category_name,
        description
    FROM category
    ORDER BY category_name ASC
");

if ($category_result) {

    while (
        $category =
            $category_result->fetch_assoc()
    ) {

        $categories[] = $category;
    }
}


/* =========================================================
   CATEGORY FILTER
========================================================= */

$selected_category = filter_var(
    $_GET['category'] ?? 0,
    FILTER_VALIDATE_INT
);

if (
    $selected_category === false ||
    $selected_category < 1
) {

    $selected_category = 0;
}


$selected_category_name = "";


/* Get selected category name */

if ($selected_category > 0) {

    $stmt = $conn->prepare("
        SELECT category_name
        FROM category
        WHERE category_id = ?
        LIMIT 1
    ");

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $selected_category
        );

        if ($stmt->execute()) {

            $result =
                $stmt->get_result();

            $row =
                $result->fetch_assoc();

            if ($row) {

                $selected_category_name =
                    $row['category_name'];
            }
        }

        $stmt->close();
    }
}


/* =========================================================
   EDIT PRODUCT
========================================================= */

$edit_product = null;

if (isset($_GET['edit'])) {

    $edit_id = filter_var(
        $_GET['edit'],
        FILTER_VALIDATE_INT
    );

    if (!$edit_id || $edit_id <= 0) {

        $error = "Invalid product.";

    } else {

        $stmt = $conn->prepare("
            SELECT *
            FROM products
            WHERE product_id = ?
            LIMIT 1
        ");

        if (!$stmt) {

            $error =
                "Unable to load product.";

        } else {

            $stmt->bind_param(
                "i",
                $edit_id
            );

            if (!$stmt->execute()) {

                $error =
                    "Unable to load product.";

            } else {

                $result =
                    $stmt->get_result();

                $edit_product =
                    $result->fetch_assoc();

                if (!$edit_product) {

                    $error =
                        "Product not found.";
                }
            }

            $stmt->close();
        }
    }
}


/* =========================================================
   ADD / UPDATE PRODUCT
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action =
        $_POST['action'] ?? 'add';

    if (
        $action !== 'add' &&
        $action !== 'update'
    ) {

        $error = "Invalid request.";

    } else {

        $product_name =
            trim(
                $_POST['product_name'] ?? ''
            );

        $category_id =
            filter_var(
                $_POST['category_id'] ?? 0,
                FILTER_VALIDATE_INT
            );

        $description =
            trim(
                $_POST['description'] ?? ''
            );

        $price_raw =
            trim(
                $_POST['price'] ?? ''
            );

        $stock_raw =
            trim(
                $_POST['stock_quantity'] ?? ''
            );

        $size =
            trim(
                $_POST['size'] ?? ''
            );


        /* =================================================
           VALIDATE BASIC VALUES
        ================================================= */

        $price = is_numeric($price_raw)
            ? (float) $price_raw
            : 0;

        $stock_quantity = filter_var(
            $stock_raw,
            FILTER_VALIDATE_INT
        );

        if ($stock_quantity === false) {
            $stock_quantity = -1;
        }


        if (
            $product_name === "" ||
            mb_strlen($product_name) > 150 ||
            !$category_id ||
            $category_id <= 0 ||
            $description === "" ||
            mb_strlen($description) > 5000 ||
            $price <= 0 ||
            $stock_quantity < 0 ||
            $size === "" ||
            mb_strlen($size) > 100
        ) {

            $error =
                "Please fill all fields correctly.";

        } else {


            /* =================================================
               CHECK CATEGORY EXISTS
            ================================================= */

            $stmt = $conn->prepare("
                SELECT category_id
                FROM category
                WHERE category_id = ?
                LIMIT 1
            ");

            if (!$stmt) {

                $error =
                    "Unable to verify category.";

            } else {

                $stmt->bind_param(
                    "i",
                    $category_id
                );

                if (!$stmt->execute()) {

                    $error =
                        "Unable to verify category.";

                } else {

                    $category_check =
                        $stmt->get_result();

                    $category_exists =
                        $category_check->num_rows === 1;

                    if (!$category_exists) {

                        $error =
                            "Selected category does not exist.";
                    }
                }

                $stmt->close();
            }


            /* =================================================
               UPDATE PRODUCT
            ================================================= */

            if (
                $error === "" &&
                $action === "update"
            ) {

                $product_id =
                    filter_var(
                        $_POST['product_id'] ?? 0,
                        FILTER_VALIDATE_INT
                    );

                if (
                    !$product_id ||
                    $product_id <= 0
                ) {

                    $error =
                        "Invalid product.";

                } else {

                    $stmt = $conn->prepare("
                        SELECT image
                        FROM products
                        WHERE product_id = ?
                        LIMIT 1
                    ");

                    if (!$stmt) {

                        $error =
                            "Unable to load product.";

                    } else {

                        $stmt->bind_param(
                            "i",
                            $product_id
                        );

                        if (!$stmt->execute()) {

                            $error =
                                "Unable to load product.";

                        } else {

                            $result =
                                $stmt->get_result();

                            $old_product =
                                $result->fetch_assoc();

                            if (!$old_product) {

                                $error =
                                    "Product not found.";
                            }
                        }

                        $stmt->close();
                    }


                    if ($error === "") {

                        $image_name =
                            trim(
                                $old_product['image']
                                ?? ''
                            );

                        $new_uploaded_image = false;
                        $new_image_path = "";


                        /* =====================================
                           NEW IMAGE UPLOAD
                        ===================================== */

                        if (
                            isset($_FILES['image']) &&
                            $_FILES['image']['error'] !==
                            UPLOAD_ERR_NO_FILE
                        ) {

                            if (
                                $_FILES['image']['error'] !==
                                UPLOAD_ERR_OK
                            ) {

                                $error =
                                    "There was a problem uploading the image.";

                            } elseif (
                                $_FILES['image']['size'] >
                                $max_image_size
                            ) {

                                $error =
                                    "Image size must be 5 MB or less.";

                            } else {

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

                                    $finfo =
                                        finfo_open(
                                            FILEINFO_MIME_TYPE
                                        );

                                    $mime_type =
                                        $finfo
                                            ? finfo_file(
                                                $finfo,
                                                $tmp_name
                                            )
                                            : false;

                                    if ($finfo) {
                                        finfo_close($finfo);
                                    }


                                    if (
                                        !in_array(
                                            $mime_type,
                                            $allowed_mime_types,
                                            true
                                        )
                                    ) {

                                        $error =
                                            "Invalid image file.";

                                    } elseif (
                                        @getimagesize(
                                            $tmp_name
                                        ) === false
                                    ) {

                                        $error =
                                            "Uploaded file is not a valid image.";

                                    } else {

                                        $upload_dir =
                                            "../uploads/products/";


                                        if (
                                            !is_dir(
                                                $upload_dir
                                            )
                                        ) {

                                            if (
                                                !mkdir(
                                                    $upload_dir,
                                                    0755,
                                                    true
                                                )
                                            ) {

                                                $error =
                                                    "Unable to create image upload directory.";
                                            }
                                        }


                                        if (
                                            $error === ""
                                        ) {

                                            $new_image_name =
                                                time() .
                                                "_" .
                                                bin2hex(
                                                    random_bytes(8)
                                                ) .
                                                "." .
                                                $extension;


                                            $new_image_path =
                                                $upload_dir .
                                                $new_image_name;


                                            if (
                                                move_uploaded_file(
                                                    $tmp_name,
                                                    $new_image_path
                                                )
                                            ) {

                                                $image_name =
                                                    $new_image_name;

                                                $new_uploaded_image =
                                                    true;

                                            } else {

                                                $error =
                                                    "Image upload failed.";
                                            }
                                        }
                                    }
                                }
                            }
                        }


                        /* =====================================
                           UPDATE DATABASE
                        ===================================== */

                        if (
                            $error === ""
                        ) {

                            $stmt = $conn->prepare("
                                UPDATE products
                                SET
                                    product_name = ?,
                                    category_id = ?,
                                    description = ?,
                                    price = ?,
                                    stock_quantity = ?,
                                    size = ?,
                                    image = ?
                                WHERE product_id = ?
                            ");

                            if (!$stmt) {

                                $error =
                                    "Unable to update product.";

                            } else {

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


                                if (
                                    $stmt->execute()
                                ) {

                                    $stmt->close();


                                    if (
                                        $new_uploaded_image &&
                                        !empty(
                                            $old_product['image']
                                        )
                                    ) {

                                        $old_image_path =
                                            "../uploads/products/" .
                                            basename(
                                                $old_product['image']
                                            );

                                        if (
                                            is_file(
                                                $old_image_path
                                            )
                                        ) {

                                            @unlink(
                                                $old_image_path
                                            );
                                        }
                                    }


                                    /*
                                     * Keep category filter
                                     * after updating product.
                                     */

                                    $redirect_url =
                                        "products.php?updated=1";

                                    if (
                                        $selected_category > 0
                                    ) {

                                        $redirect_url .=
                                            "&category=" .
                                            $selected_category;
                                    }

                                    header(
                                        "Location: " .
                                        $redirect_url
                                    );

                                    exit;

                                } else {

                                    if (
                                        $new_uploaded_image &&
                                        is_file(
                                            $new_image_path
                                        )
                                    ) {

                                        @unlink(
                                            $new_image_path
                                        );
                                    }


                                    $error =
                                        "Product could not be updated.";

                                    $stmt->close();
                                }
                            }
                        }
                    }
                }


            /* =================================================
               ADD PRODUCT
            ================================================= */

            } elseif (
                $error === "" &&
                $action === "add"
            ) {

                $image_name = "";
                $image_path = "";


                /* =============================================
                   IMAGE REQUIRED FOR NEW PRODUCT
                ============================================= */

                if (
                    !isset($_FILES['image']) ||
                    $_FILES['image']['error'] ===
                    UPLOAD_ERR_NO_FILE
                ) {

                    $error =
                        "Please select a product image.";

                } elseif (
                    $_FILES['image']['error'] !==
                    UPLOAD_ERR_OK
                ) {

                    $error =
                        "There was a problem uploading the image.";

                } elseif (
                    $_FILES['image']['size'] >
                    $max_image_size
                ) {

                    $error =
                        "Image size must be 5 MB or less.";

                } else {

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

                        $finfo =
                            finfo_open(
                                FILEINFO_MIME_TYPE
                            );

                        $mime_type =
                            $finfo
                                ? finfo_file(
                                    $finfo,
                                    $tmp_name
                                )
                                : false;

                        if ($finfo) {
                            finfo_close($finfo);
                        }


                        if (
                            !in_array(
                                $mime_type,
                                $allowed_mime_types,
                                true
                            )
                        ) {

                            $error =
                                "Invalid image file.";

                        } elseif (
                            @getimagesize(
                                $tmp_name
                            ) === false
                        ) {

                            $error =
                                "Uploaded file is not a valid image.";

                        } else {

                            $upload_dir =
                                "../uploads/products/";


                            if (
                                !is_dir(
                                    $upload_dir
                                )
                            ) {

                                if (
                                    !mkdir(
                                        $upload_dir,
                                        0755,
                                        true
                                    )
                                ) {

                                    $error =
                                        "Unable to create image upload directory.";
                                }
                            }


                            if (
                                $error === ""
                            ) {

                                $image_name =
                                    time() .
                                    "_" .
                                    bin2hex(
                                        random_bytes(8)
                                    ) .
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
                        }
                    }
                }


                /* =============================================
                   INSERT PRODUCT
                ============================================= */

                if (
                    $error === ""
                ) {

                    $stmt = $conn->prepare("
                        INSERT INTO products
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
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");


                    if (!$stmt) {

                        $error =
                            "Unable to add product.";

                    } else {

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


                        if (
                            $stmt->execute()
                        ) {

                            $stmt->close();

                            /*
                             * Keep category filter
                             * after adding product.
                             */

                            $redirect_url =
                                "products.php?added=1";

                            if (
                                $selected_category > 0
                            ) {

                                $redirect_url .=
                                    "&category=" .
                                    $selected_category;
                            }

                            header(
                                "Location: " .
                                $redirect_url
                            );

                            exit;

                        } else {

                            if (
                                $image_name !== "" &&
                                is_file(
                                    $image_path
                                )
                            ) {

                                @unlink(
                                    $image_path
                                );
                            }


                            $error =
                                "Product could not be added.";

                            $stmt->close();
                        }
                    }
                }
            }
        }
    }
}


/* =========================================================
   SUCCESS MESSAGES
========================================================= */

if (
    isset($_GET['added']) &&
    $error === ""
) {

    $message =
        "Product added successfully!";
}


if (
    isset($_GET['updated']) &&
    $error === ""
) {

    $message =
        "Product updated successfully!";
}


/* =========================================================
   UNREAD MESSAGES
========================================================= */

$unread_messages = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM contact_messages
    WHERE status = 'unread'
");

if ($result) {

    $row = $result->fetch_assoc();

    $unread_messages =
        (int)($row['total'] ?? 0);
}


/* =========================================================
   PRODUCTS LIST
========================================================= */

$search =
    trim(
        $_GET['search'] ?? ''
    );

$products = false;


/*
 * Category + Search
 */

if (
    $selected_category > 0 &&
    $search !== ""
) {

    $search_value =
        "%" . $search . "%";


    $stmt = $conn->prepare("
        SELECT
            p.*,
            c.category_name
        FROM products p
        LEFT JOIN category c
            ON p.category_id = c.category_id
        WHERE
            p.category_id = ?
            AND (
                p.product_name LIKE ?
                OR p.description LIKE ?
                OR p.size LIKE ?
                OR c.category_name LIKE ?
            )
        ORDER BY p.product_id DESC
    ");


    if ($stmt) {

        $stmt->bind_param(
            "issss",
            $selected_category,
            $search_value,
            $search_value,
            $search_value,
            $search_value
        );


        if ($stmt->execute()) {

            $products =
                $stmt->get_result();

        } else {

            $error =
                "Unable to load products.";
        }

        $stmt->close();

    } else {

        $error =
            "Unable to search products.";
    }


/*
 * Category only
 */

} elseif (
    $selected_category > 0
) {

    $stmt = $conn->prepare("
        SELECT
            p.*,
            c.category_name
        FROM products p
        LEFT JOIN category c
            ON p.category_id = c.category_id
        WHERE p.category_id = ?
        ORDER BY p.product_id DESC
    ");


    if ($stmt) {

        $stmt->bind_param(
            "i",
            $selected_category
        );


        if ($stmt->execute()) {

            $products =
                $stmt->get_result();

        } else {

            $error =
                "Unable to load category products.";
        }

        $stmt->close();

    } else {

        $error =
            "Unable to load category products.";
    }


/*
 * Search only
 */

} elseif (
    $search !== ""
) {

    $search_value =
        "%" . $search . "%";


    $stmt = $conn->prepare("
        SELECT
            p.*,
            c.category_name
        FROM products p
        LEFT JOIN category c
            ON p.category_id = c.category_id
        WHERE
            p.product_name LIKE ?
            OR p.description LIKE ?
            OR p.size LIKE ?
            OR c.category_name LIKE ?
        ORDER BY p.product_id DESC
    ");


    if ($stmt) {

        $stmt->bind_param(
            "ssss",
            $search_value,
            $search_value,
            $search_value,
            $search_value
        );


        if ($stmt->execute()) {

            $products =
                $stmt->get_result();

        } else {

            $error =
                "Unable to load products.";
        }

        $stmt->close();

    } else {

        $error =
            "Unable to search products.";
    }


/*
 * All products
 */

} else {

    $products =
        $conn->query("
            SELECT
                p.*,
                c.category_name
            FROM products p
            LEFT JOIN category c
                ON p.category_id = c.category_id
            ORDER BY p.product_id DESC
        ");

    if (!$products) {

        $error =
            "Unable to load products.";
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

    <title>
        Products - QAMROSH
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

            z-index: 9999;
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


        .menu {
            position: relative;
            z-index: 10000;
        }


        .menu a {
            display: flex;

            align-items: center;

            justify-content: space-between;

            text-decoration: none;

            color: #d1d5db;

            padding: 14px 16px;

            margin-bottom: 8px;

            border-radius: 8px;

            transition: 0.3s;

            position: relative;

            z-index: 10001;
        }


        .menu a:hover,
        .menu a.active {
            background: #b8860b;

            color: #ffffff;
        }


        .menu-left {
            display: flex;

            align-items: center;
        }


        .menu-badge {
            background: #dc2626;

            color: #ffffff;

            min-width: 22px;

            height: 22px;

            padding: 0 6px;

            border-radius: 20px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 12px;

            font-weight: bold;
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
        .form-group textarea,
        .form-group select {
            width: 100%;

            padding: 13px 14px;

            border: 1px solid #d1d5db;

            border-radius: 9px;

            background: #f9fafb;

            font-size: 15px;

            outline: none;
        }


        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
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


        .category-help {
            margin-top: 7px;

            color: #6b7280;

            font-size: 12px;
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
           PRODUCTS
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


        .category-title {
            margin-top: 6px;

            color: #b8860b;

            font-size: 14px;

            font-weight: bold;
        }


        /* =========================
           CATEGORY FILTER
        ========================= */

        .category-filter {
            display: flex;

            flex-wrap: wrap;

            gap: 8px;

            margin-bottom: 22px;

            padding: 15px;

            background: #f9fafb;

            border-radius: 10px;

            border: 1px solid #e5e7eb;
        }


        .category-filter-btn {
            display: inline-block;

            text-decoration: none;

            padding: 9px 14px;

            border-radius: 7px;

            background: #e5e7eb;

            color: #374151;

            font-size: 13px;

            font-weight: 600;

            transition: 0.3s;
        }


        .category-filter-btn:hover {
            background: #d1d5db;

            color: #111827;
        }


        .category-filter-btn.active {
            background: #b8860b;

            color: #ffffff;
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


        .clear-filter {
            display: inline-block;

            text-decoration: none;

            padding: 9px 14px;

            border-radius: 7px;

            background: #dc2626;

            color: #ffffff;

            font-size: 13px;

            font-weight: 600;
        }


        .clear-filter:hover {
            background: #b91c1c;
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

            object-fit: contain;

            border-radius: 8px;

            border: 1px solid #eeeeee;

            background: #f8f8f8;
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


        /* =========================
           STOCK
        ========================= */

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
           ACTIONS
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


            .category-filter {
                flex-direction: column;

                align-items: stretch;
            }


            .category-filter-btn,
            .clear-filter {
                text-align: center;
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


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

    <div class="logo">

        QAMROSH

    </div>


    <div class="admin-title">

        ADMIN PANEL

    </div>


    <nav class="menu">

        <a href="dashboard.php">

            <span class="menu-left">
                🏠 Dashboard
            </span>

        </a>


        <a
            href="products.php"
            class="active"
        >

            <span class="menu-left">
                📦 Products
            </span>

        </a>


        <a href="categories.php">

            <span class="menu-left">
                📂 Categories
            </span>

        </a>


        <a href="orders.php">

            <span class="menu-left">
                🛒 Orders
            </span>

        </a>


        <a href="customers.php">

            <span class="menu-left">
                👥 Customers
            </span>

        </a>


        <a href="messages.php">

            <span class="menu-left">
                💬 Messages
            </span>


            <?php if ($unread_messages > 0): ?>

                <span class="menu-badge">

                    <?= $unread_messages ?>

                </span>

            <?php endif; ?>

        </a>

    </nav>


    <div class="logout">

        <a href="logout.php">
            Logout
        </a>

    </div>

</aside>


<!-- =========================
     MAIN
========================= -->

<main class="main-content">


    <div class="topbar">

        <h1>
            Products
        </h1>

        <p>
            Manage QAMROSH products
        </p>

    </div>


    <?php if ($message !== ""): ?>

        <div class="success-message">
            <?= e($message) ?>
        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="error-message">
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <!-- =========================
         ADD / EDIT PRODUCT
    ========================= -->

    <div class="form-box">


        <h2 class="form-title">

            <?=
            $edit_product
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
                value="<?=
                $edit_product
                    ? 'update'
                    : 'add';
                ?>"
            >


            <?php if ($edit_product): ?>

                <input
                    type="hidden"
                    name="product_id"
                    value="<?=
                    (int)
                    $edit_product['product_id'];
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
                        value="<?=
                        e(
                            $edit_product[
                                'product_name'
                            ] ?? ''
                        );
                        ?>"
                        placeholder="Enter product name"
                        maxlength="150"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Category
                    </label>

                    <select
                        name="category_id"
                        required
                    >

                        <option value="">
                            Select Category
                        </option>


                        <?php foreach (
                            $categories as $category
                        ): ?>

                            <option
                                value="<?=
                                (int)
                                $category[
                                    'category_id'
                                ];
                                ?>"
                                <?php
                                if (
                                    $edit_product &&
                                    (int)
                                    $edit_product[
                                        'category_id'
                                    ] ===
                                    (int)
                                    $category[
                                        'category_id'
                                    ]
                                ) {
                                    echo "selected";
                                } elseif (
                                    !$edit_product &&
                                    $selected_category > 0 &&
                                    $selected_category ===
                                    (int)
                                    $category[
                                        'category_id'
                                    ]
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >

                                <?=
                                e(
                                    $category[
                                        'category_name'
                                    ]
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>


                    <small class="category-help">

                        Select a category from the database.

                    </small>

                </div>


                <div class="form-group full-width">

                    <label>
                        Description
                    </label>

                    <textarea
                        name="description"
                        maxlength="5000"
                        placeholder="Enter product description"
                        required
                    ><?=
                    e(
                        $edit_product[
                            'description'
                        ] ?? ''
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
                        min="0.01"
                        value="<?=
                        e(
                            $edit_product[
                                'price'
                            ] ?? ''
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
                        value="<?=
                        e(
                            $edit_product[
                                'stock_quantity'
                            ] ?? ''
                        );
                        ?>"
                        placeholder="100"
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
                        value="<?=
                        e(
                            $edit_product[
                                'size'
                            ] ?? ''
                        );
                        ?>"
                        placeholder="S, M, L, XL"
                        maxlength="100"
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
                        <?=
                        $edit_product
                            ? ''
                            : 'required';
                        ?>
                    >


                    <small class="category-help">

                        JPG, JPEG, PNG or WEBP — Maximum 5 MB.

                    </small>


                    <?php if (
                        $edit_product &&
                        !empty(
                            $edit_product['image']
                        )
                    ): ?>

                        <small
                            style="
                                margin-top:8px;
                                color:#6b7280;
                            "
                        >

                            Current image:

                            <?=
                            e(
                                $edit_product[
                                    'image'
                                ]
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

                <?=
                $edit_product
                    ? "Update Product"
                    : "+ Add Product";
                ?>

            </button>


            <?php if ($edit_product): ?>

                <a
                    href="products.php<?=
                    $selected_category > 0
                        ? '?category=' . $selected_category
                        : '';
                    ?>"
                    class="cancel-btn"
                >
                    Cancel Edit
                </a>

            <?php endif; ?>


        </form>

    </div>


    <!-- =========================
         PRODUCTS LIST
    ========================= -->

    <div class="products-box">


        <div class="products-header">

            <div>

                <h2>
                    <?php if ($selected_category > 0): ?>

                        Category Products

                    <?php else: ?>

                        All Products

                    <?php endif; ?>
                </h2>


                <?php if (
                    $selected_category > 0 &&
                    $selected_category_name !== ""
                ): ?>

                    <div class="category-title">

                        Category:
                        <?= e($selected_category_name) ?>

                    </div>

                <?php endif; ?>

            </div>


            <form
                method="GET"
                class="search-form"
            >

                <?php if ($selected_category > 0): ?>

                    <input
                        type="hidden"
                        name="category"
                        value="<?= $selected_category ?>"
                    >

                <?php endif; ?>


                <input
                    type="text"
                    name="search"
                    value="<?= e($search) ?>"
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


        <!-- =========================
             CATEGORY FILTER BUTTONS
        ========================= -->

        <div class="category-filter">

            <a
                href="products.php"
                class="category-filter-btn <?=
                    $selected_category === 0
                        ? 'active'
                        : '';
                ?>"
            >
                All Products
            </a>


            <?php foreach (
                $categories as $category
            ): ?>

                <?php
                    $cat_id =
                        (int)
                        $category['category_id'];

                    $cat_name =
                        trim(
                            $category['category_name']
                            ?? ''
                        );
                ?>


                <a
                    href="products.php?category=<?= $cat_id ?>"
                    class="category-filter-btn <?=
                        $selected_category === $cat_id
                            ? 'active'
                            : '';
                    ?>"
                >

                    <?= e($cat_name) ?>

                </a>

            <?php endforeach; ?>


            <?php if ($selected_category > 0): ?>

                <a
                    href="products.php"
                    class="clear-filter"
                >
                    Clear Filter
                </a>

            <?php endif; ?>

        </div>


        <!-- =========================
             PRODUCTS TABLE
        ========================= -->

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

                                    <?=
                                    (int)
                                    $product[
                                        'product_id'
                                    ];
                                    ?>

                                </td>


                                <td>

                                    <?php

                                    $product_image =
                                        trim(
                                            $product[
                                                'image'
                                            ] ?? ''
                                        );


                                    $image_path =
                                        "../uploads/products/" .
                                        basename(
                                            $product_image
                                        );

                                    ?>


                                    <?php if (
                                        $product_image !== "" &&
                                        is_file(
                                            $image_path
                                        )
                                    ): ?>


                                        <img
                                            src="<?=
                                            e(
                                                $image_path
                                            );
                                            ?>"
                                            class="product-img"
                                            alt="<?=
                                            e(
                                                $product[
                                                    'product_name'
                                                ]
                                            );
                                            ?>"
                                        >


                                    <?php else: ?>


                                        <div class="no-image">
                                            No Image
                                        </div>


                                    <?php endif; ?>


                                </td>


                                <td>

                                    <strong>

                                        <?=
                                        e(
                                            $product[
                                                'product_name'
                                            ]
                                        );
                                        ?>

                                    </strong>

                                </td>


                                <td>

                                    <?=
                                    e(
                                        $product[
                                            'category_name'
                                        ] ??
                                        'Uncategorized'
                                    );
                                    ?>

                                </td>


                                <td>

                                    Rs.

                                    <?=
                                    number_format(
                                        (float)
                                        $product[
                                            'price'
                                        ],
                                        2
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php

                                    $stock =
                                        max(
                                            0,
                                            (int)
                                            $product[
                                                'stock_quantity'
                                            ]
                                        );


                                    if (
                                        $stock <= 0
                                    ) {

                                        $stock_class =
                                            "out-stock";

                                    } elseif (
                                        $stock <= 5
                                    ) {

                                        $stock_class =
                                            "low-stock";

                                    } else {

                                        $stock_class =
                                            "in-stock";
                                    }

                                    ?>


                                    <span
                                        class="stock <?=
                                        e(
                                            $stock_class
                                        );
                                        ?>"
                                    >

                                        <?=
                                        $stock;
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <?=
                                    e(
                                        $product[
                                            'size'
                                        ] ?? ''
                                    );
                                    ?>

                                </td>


                                <td>


                                    <a
                                        href="products.php?edit=<?=
                                        (int)
                                        $product[
                                            'product_id'
                                        ];
                                        ?><?=
                                        $selected_category > 0
                                            ? '&category=' .
                                                $selected_category
                                            : '';
                                        ?>"
                                        class="edit-btn"
                                    >
                                        Edit
                                    </a>


                                    <a
                                        href="products.php?delete=<?=
                                        (int)
                                        $product[
                                            'product_id'
                                        ];
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

                if (
                    $selected_category > 0 &&
                    $selected_category_name !== ""
                ) {

                    if ($search !== "") {

                        echo
                            "No products found in " .
                            e($selected_category_name) .
                            " for your search.";

                    } else {

                        echo
                            "No products found in " .
                            e($selected_category_name) .
                            ".";

                    }

                } elseif ($search !== "") {

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