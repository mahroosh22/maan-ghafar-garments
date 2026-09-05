
<?php
session_start();

require_once "config/database.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {

        $error = "Please fill all fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 6) {

        $error = "Password must be at least 6 characters.";

    } else {

        /* =========================
           CHECK EMAIL
        ========================= */

        $stmt = $conn->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $error = "Email already registered.";

            $stmt->close();

        } else {

            $stmt->close();

            /* =========================
               HASH PASSWORD
            ========================= */

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            /* =========================
               CREATE CUSTOMER ACCOUNT
            ========================= */

            $stmt = $conn->prepare("
                INSERT INTO users
                (name, email, password, role)
                VALUES (?, ?, ?, 'customer')
            ");

            $stmt->bind_param(
                "sss",
                $name,
                $email,
                $hashed_password
            );


            if ($stmt->execute()) {

                $success = "Registration successful! You can now login.";

            } else {

                $error = "Registration failed. Please try again.";

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
            <?php echo htmlspecialchars($error); ?>
        </p>

    <?php endif; ?>


    <?php if ($success !== ''): ?>

        <p class="success">
            <?php echo htmlspecialchars($success); ?>
        </p>

    <?php endif; ?>


    <form method="POST">

        <label>
            Name
        </label>

        <input
            type="text"
            name="name"
            required
        >


        <label>
            Email
        </label>

        <input
            type="email"
            name="email"
            required
        >


        <label>
            Password
        </label>

        <input
            type="password"
            name="password"
            minlength="6"
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
