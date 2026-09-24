<?php

session_start();

require_once "config/database.php";

$error = "";


/* =========================================
   CSRF TOKEN
========================================= */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];


/* =========================================
   LOGIN
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $posted_token = $_POST['csrf_token'] ?? '';

    if (
        empty($posted_token) ||
        !hash_equals($csrf_token, $posted_token)
    ) {

        $error = "Invalid security token. Please try again.";

    } else {

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';


        /* =========================================
           VALIDATION
        ========================================= */

        if ($email === '' || $password === '') {

            $error = "Please fill all fields.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = "Please enter a valid email address.";

        } else {

            /* =========================================
               GET CUSTOMER
            ========================================= */

            $stmt = $conn->prepare("
                SELECT
                    id,
                    name,
                    email,
                    password,
                    role
                FROM users
                WHERE email = ?
                LIMIT 1
            ");


            if (!$stmt) {

                $error = "Unable to process login.";

            } else {

                $stmt->bind_param(
                    "s",
                    $email
                );

                if (!$stmt->execute()) {

                    $error = "Unable to process login.";

                    $stmt->close();

                } else {

                    $result = $stmt->get_result();

                    $user = $result->fetch_assoc();

                    $stmt->close();


                    /* =========================================
                       VERIFY CUSTOMER
                    ========================================= */

                    if (
                        $user &&
                        ($user['role'] ?? '') === 'customer' &&
                        password_verify(
                            $password,
                            $user['password']
                        )
                    ) {

                        /*
                            Regenerate session ID after
                            successful authentication.
                        */

                        session_regenerate_id(true);


                        $_SESSION['user_id'] =
                            (int) $user['id'];

                        $_SESSION['user_name'] =
                            $user['name'];

                        $_SESSION['user_email'] =
                            $user['email'];

                        $_SESSION['user_role'] =
                            $user['role'];


                        /*
                            Generate a fresh CSRF token
                            after login.
                        */

                        $_SESSION['csrf_token'] =
                            bin2hex(random_bytes(32));


                        header("Location: index.php");
                        exit;

                    } else {

                        /*
                            Generic error prevents revealing
                            whether an email exists.
                        */

                        $error =
                            "Invalid email or password.";
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

    <title>Login - QAMROSH</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .login-box {
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

        .register-link {
            text-align: center;
            margin-top: 20px;
        }

        .register-link a {
            color: #111;
            font-weight: bold;
            text-decoration: none;
        }

    </style>

</head>

<body>

<div class="login-box">

    <h1>Customer Login</h1>


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


    <form
        method="POST"
        autocomplete="off"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars(
                $csrf_token,
                ENT_QUOTES,
                'UTF-8'
            ); ?>"
        >


        <label for="login_email">
            Email
        </label>

        <input
            type="email"
            name="email"
            id="login_email"
            autocomplete="email"
            maxlength="150"
            required
        >


        <label for="login_password">
            Password
        </label>

        <input
            type="password"
            name="password"
            id="login_password"
            autocomplete="current-password"
            required
        >


        <button type="submit">
            Login
        </button>

    </form>


    <div class="register-link">

        Don't have an account?

        <a href="register.php">
            Create Account
        </a>

    </div>

</div>

</body>

</html>
