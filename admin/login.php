<?php

session_start();

require_once "../config/database.php";

$error = "";


/* =========================
   ADMIN LOGIN
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';


    /* =========================
       VALIDATION
    ========================= */

    if ($email === '' || $password === '') {

        $error = "Please enter your email and password.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {


        /* =========================
           FIND ADMIN
        ========================= */

        $stmt = $conn->prepare("
            SELECT
                id,
                name,
                email,
                password,
                role
            FROM users
            WHERE email = ?
            AND role = 'admin'
            LIMIT 1
        ");

        if (!$stmt) {

            $error = "Something went wrong. Please try again.";

        } else {

            $stmt->bind_param("s", $email);

            if (!$stmt->execute()) {

                $error = "Something went wrong. Please try again.";

            } else {

                $result = $stmt->get_result();


                if ($result->num_rows === 1) {

                    $admin = $result->fetch_assoc();


                    /* =========================
                       PASSWORD CHECK
                    ========================= */

                    $hashed_password = hash(
                        'sha256',
                        $password
                    );


                    if (
                        hash_equals(
                            (string) $admin['password'],
                            $hashed_password
                        )
                    ) {


                        /* =========================
                           REGENERATE SESSION
                        ========================= */

                        session_regenerate_id(true);


                        $_SESSION['admin_id'] =
                            (int) $admin['id'];

                        $_SESSION['admin_name'] =
                            $admin['name'];

                        $_SESSION['admin_email'] =
                            $admin['email'];

                        $_SESSION['admin_role'] =
                            'admin';


                        header(
                            "Location: dashboard.php"
                        );

                        exit;

                    } else {

                        $error =
                            "Invalid email or password.";
                    }

                } else {

                    $error =
                        "Invalid email or password.";
                }
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Admin Login - Maan Ghafar Garments
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(
                135deg,
                #111827,
                #1f2937
            );
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-section {
            width: 100%;
            padding: 20px;
        }

        .login-box {
            width: 100%;
            max-width: 430px;
            margin: auto;
            background: #ffffff;
            padding: 45px 38px;
            border-radius: 20px;
            box-shadow:
                0 20px 50px
                rgba(0, 0, 0, 0.30);
        }

        .brand-name {
            text-align: center;
            color: #b8860b;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 12px;
        }

        .login-box h2 {
            text-align: center;
            color: #111827;
            font-size: 30px;
            margin-bottom: 8px;
        }

        .login-subtitle {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .input-group {
            margin-bottom: 18px;
        }

        .input-group label {
            display: block;
            color: #374151;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .login-box input {
            width: 100%;
            padding: 14px 15px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 15px;
            outline: none;
            background: #f9fafb;
            transition: 0.3s;
        }

        .login-box input:focus {
            border-color: #b8860b;
            background: #ffffff;
            box-shadow:
                0 0 0 3px
                rgba(184, 134, 11, 0.12);
        }

        .login-box button {
            width: 100%;
            padding: 15px;
            margin-top: 8px;
            border: none;
            border-radius: 10px;
            background: #b8860b;
            color: #ffffff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .login-box button:hover {
            background: #96700a;
            transform: translateY(-2px);
        }

        .error-message {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px;
            border-radius: 9px;
            text-align: center;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .login-footer {
            text-align: center;
            margin-top: 25px;
            color: #9ca3af;
            font-size: 12px;
        }

        @media (max-width: 480px) {

            .login-section {
                padding: 15px;
            }

            .login-box {
                padding: 35px 22px;
                border-radius: 16px;
            }

            .login-box h2 {
                font-size: 26px;
            }

            .brand-name {
                font-size: 13px;
            }

        }

    </style>

</head>


<body>


<section class="login-section">


    <div class="login-box">


        <div class="brand-name">
            MAAN GHAFAR GARMENTS
        </div>


        <h2>
            Admin Login
        </h2>


        <p class="login-subtitle">
            Login to access your admin panel
        </p>


        <?php if ($error !== ""): ?>

            <div class="error-message">

                <?php
                echo htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action=""
            autocomplete="off"
        >


            <div class="input-group">

                <label for="email">
                    Admin Email
                </label>


                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    autocomplete="username"
                    value="<?php
                        echo htmlspecialchars(
                            $email ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        );
                    ?>"
                    required
                >

            </div>


            <div class="input-group">

                <label for="password">
                    Password
                </label>


                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >

            </div>


            <button type="submit">
                Login to Admin Panel
            </button>


        </form>


        <div class="login-footer">
            © <?php echo date("Y"); ?>
            Maan Ghafar Garments
        </div>


    </div>


</section>


</body>

</html>