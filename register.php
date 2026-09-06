<?php

session_start();

require_once "config/database.php";

$error = "";
$success = "";


/* =========================================
   CSRF TOKEN
========================================= */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];


/* =========================================
   REGISTER
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $posted_token = $_POST['csrf_token'] ?? '';

    if (
        empty($posted_token) ||
        !hash_equals($csrf_token, $posted_token)
    ) {

        $error = "Invalid security token. Please try again.";

    } else {

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';


        /* =========================================
           VALIDATION
        ========================================= */

        if (
            $name === '' ||
            $email === '' ||
            $password === ''
        ) {

            $error = "Please fill all fields.";

        } elseif (mb_strlen($name) > 100) {

            $error = "Name is too long.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = "Please enter a valid email address.";

        } elseif (strlen($email) > 150) {

            $error = "Email address is too long.";

        } elseif (strlen($password) < 6) {

            $error = "Password must be at least 6 characters.";

        } else {


            /* =========================================
               CHECK EMAIL
            ========================================= */

            $stmt = $conn->prepare("
                SELECT id
                FROM users
                WHERE LOWER(TRIM(email)) = LOWER(TRIM(?))
                LIMIT 1
            ");


            if (!$stmt) {

                $error = "Unable to process registration.";

            } else {

                $stmt->bind_param(
                    "s",
                    $email
                );


                if (!$stmt->execute()) {

                    $error =
                        "Unable to process registration.";

                    $stmt->close();

                } else {

                    $result = $stmt->get_result();


                    if ($result->num_rows > 0) {

                        $error =
                            "Email already registered.";

                        $stmt->close();

                    } else {

                        $stmt->close();


                        /* =========================================
                           HASH PASSWORD
                        ========================================= */

                        $hashed_password = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );


                        if ($hashed_password === false) {

                            $error =
                                "Unable to create account.";

                        } else {


                            /* =========================================
                               CREATE CUSTOMER ACCOUNT
                            ========================================= */

                            $stmt = $conn->prepare("
                                INSERT INTO users
                                (
                                    name,
                                    email,
                                    password,
                                    role
                                )
                                VALUES
                                (?, ?, ?, 'customer')
                            ");


                            if (!$stmt) {

                                $error =
                                    "Unable to create account.";

                            } else {

                                $stmt->bind_param(
                                    "sss",
                                    $name,
                                    $email,
                                    $hashed_password
                                );


                                if ($stmt->execute()) {

                                    $success =
                                        "Registration successful! You can now login.";

                                } else {

                                    /*
                                        Handle duplicate email
                                        safely in case another
                                        registration happens at
                                        the same time.
                                    */

                                    if (
                                        $conn->errno === 1062
                                    ) {

                                        $error =
                                            "Email already registered.";

                                    } else {

                                        $error =
                                            "Registration failed. Please try again.";
                                    }
                                }


                                $stmt->close();
                            }
                        }
                    }
                }
            }
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
        Register - Maan Ghafar Garments
    </title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .register-box {
            max-width: 400px;
            margin: 60px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 25px;
        }

        label {
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #111;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #333;
        }

        .error {
            color: #c0392b;
            text-align: center;
            margin-bottom: 15px;
        }

        .success {
            color: #27ae60;
            text-align: center;
            margin-bottom: 15px;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: #111;
            font-weight: bold;
            text-decoration: none;
        }

    </style>

</head>

<body>

<div class="register-box">

    <h1>
        Create Account
    </h1>


    <?php if ($error !== ''): ?>

        <p class="error">

            <?php
            echo htmlspecialchars(
                $error,
                ENT_QUOTES,
                'UTF-8'
            );
            ?>

        </p>

    <?php endif; ?>


    <?php if ($success !== ''): ?>

        <p class="success">

            <?php
            echo htmlspecialchars(
                $success,
                ENT_QUOTES,
                'UTF-8'
            );
            ?>

        </p>

    <?php endif; ?>


    <form
        method="POST"
        autocomplete="off"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?php
                echo htmlspecialchars(
                    $csrf_token,
                    ENT_QUOTES,
                    'UTF-8'
                );
            ?>"
        >


        <label for="register_name">
            Name
        </label>

        <input
            type="text"
            name="name"
            id="register_name"
            maxlength="100"
            autocomplete="name"
            required
        >


        <label for="register_email">
            Email
        </label>

        <input
            type="email"
            name="email"
            id="register_email"
            maxlength="150"
            autocomplete="email"
            required
        >


        <label for="register_password">
            Password
        </label>

        <input
            type="password"
            name="password"
            id="register_password"
            minlength="6"
            autocomplete="new-password"
            required
        >


        <button type="submit">
            Register
        </button>

    </form>


    <div class="login-link">

        Already have an account?

        <a href="login.php">
            Login
        </a>

    </div>

</div>

</body>

</html>