<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

$total_customers = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customers - Maan Ghafar Garments</title>

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

        /* Sidebar */
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
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 40px;
            line-height: 1.4;
        }

        .admin-title {
            text-align: center;
            color: #ffffff;
            font-size: 14px;
            margin-bottom: 30px;
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
            bottom: 25px;
            left: 18px;
            right: 18px;
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

        /* Main Content */
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

        /* Customer Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
            border-left: 4px solid #b8860b;
        }

        .stat-card h3 {
            font-size: 15px;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 30px;
            font-weight: bold;
            color: #111827;
        }

        /* Customers Box */
        .customers-box {
            background: #ffffff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .customers-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .customers-header h2 {
            color: #111827;
            font-size: 21px;
        }

        .empty-message {
            text-align: center;
            padding: 55px 20px;
            color: #6b7280;
        }

        .empty-icon {
            font-size: 45px;
            margin-bottom: 15px;
        }

        .empty-message h3 {
            color: #111827;
            margin-bottom: 8px;
        }

        .empty-message p {
            line-height: 1.6;
        }

        /* Mobile */
        @media (max-width: 900px) {
            .sidebar {
                width: 210px;
            }

            .main-content {
                margin-left: 210px;
            }

            .stats {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 650px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 20px;
            }

            .logo {
                margin-bottom: 15px;
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

            .topbar h1 {
                font-size: 23px;
            }

            .customers-box {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
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

            <a href="products.php">
                📦 Products
            </a>

            <a href="orders.php">
                🛒 Orders
            </a>

            <a href="customers.php" class="active">
                👥 Customers
            </a>

        </nav>

        <div class="logout">
            <a href="logout.php">
                Logout
            </a>
        </div>

    </aside>


    <!-- Main Content -->
    <main class="main-content">

        <div class="topbar">

            <h1>Customers</h1>

            <p>
                Manage and monitor your customers from here.
            </p>

        </div>


        <!-- Statistics -->
        <div class="stats">

            <div class="stat-card">

                <h3>Total Customers</h3>

                <div class="stat-number">
                    <?php echo $total_customers; ?>
                </div>

            </div>


            <div class="stat-card">

                <h3>Active Customers</h3>

                <div class="stat-number">
                    0
                </div>

            </div>


            <div class="stat-card">

                <h3>New Customers</h3>

                <div class="stat-number">
                    0
                </div>

            </div>

        </div>


        <!-- Customers List -->
        <div class="customers-box">

            <div class="customers-header">

                <h2>Customer List</h2>

            </div>


            <div class="empty-message">

                <div class="empty-icon">
                    👥
                </div>

                <h3>No Customers Yet</h3>

                <p>
                    Customer information will appear here
                    once customers register on the website.
                </p>

            </div>

        </div>

    </main>

</body>
</html>