
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

/* =========================
   CUSTOMER STATISTICS
========================= */

$total_customers = 0;
$active_customers = 0;
$new_customers = 0;

/* Total Customers */
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'customer'
");

if ($result) {
    $row = $result->fetch_assoc();
    $total_customers = $row['total'];
}

/* Active Customers
   Assuming customers with role = customer
   are active users.
*/
$active_customers = $total_customers;

/* New Customers
   Customers registered in the last 30 days.
*/
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'customer'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");

if ($result) {
    $row = $result->fetch_assoc();
    $new_customers = $row['total'];
}


/* =========================
   CUSTOMER LIST
========================= */

$customers_result = $conn->query("
    SELECT id, name, email, phone, created_at
    FROM users
    WHERE role = 'customer'
    ORDER BY created_at DESC
");

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

        .menu {
            position: relative;
            z-index: 10000;
        }

        .menu a {
            display: block;
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


        /* =========================
           MAIN CONTENT
        ========================= */

        .main-content {
            margin-left: 250px;
            padding: 30px;
            position: relative;
            z-index: 1;
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


        /* =========================
           CUSTOMER STATS
        ========================= */

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


        /* =========================
           CUSTOMERS BOX
        ========================= */

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


        /* =========================
           CUSTOMER TABLE
        ========================= */

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .customers-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        .customers-table th {
            background: #f9fafb;
            color: #374151;
            text-align: left;
            padding: 14px;
            font-size: 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        .customers-table td {
            padding: 14px;
            border-bottom: 1px solid #e5e7eb;
            color: #4b5563;
            font-size: 14px;
        }

        .customers-table tr:hover {
            background: #fafafa;
        }

        .customer-name {
            font-weight: 600;
            color: #111827;
        }

        .customer-id {
            color: #b8860b;
            font-weight: bold;
        }

.view-btn,
.delete-btn {
    border: none;
    padding: 7px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    margin-right: 5px;
}

.view-btn {
    background: #b8860b;
    color: #ffffff;
}

.delete-btn {
    background: #dc2626;
    color: #ffffff;
}

.view-btn:hover {
    background: #967000;
}

.delete-btn:hover {
    background: #b91c1c;
}
        /* =========================
           EMPTY MESSAGE
        ========================= */

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


        /* =========================
           MOBILE
        ========================= */

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


    <!-- =========================
         SIDEBAR
    ========================= -->

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


    <!-- =========================
         MAIN CONTENT
    ========================= -->

    <main class="main-content">


        <!-- TOP BAR -->

        <div class="topbar">

            <h1>
                Customers
            </h1>

            <p>
                Manage and monitor your customers from here.
            </p>

        </div>


        <!-- =========================
             STATISTICS
        ========================= -->

        <div class="stats">


            <!-- TOTAL CUSTOMERS -->

            <div class="stat-card">

                <h3>
                    Total Customers
                </h3>

                <div class="stat-number">

                    <?php echo $total_customers; ?>

                </div>

            </div>


            <!-- ACTIVE CUSTOMERS -->

            <div class="stat-card">

                <h3>
                    Active Customers
                </h3>

                <div class="stat-number">

                    <?php echo $active_customers; ?>

                </div>

            </div>


            <!-- NEW CUSTOMERS -->

            <div class="stat-card">

                <h3>
                    New Customers
                </h3>

                <div class="stat-number">

                    <?php echo $new_customers; ?>

                </div>

            </div>


        </div>


        <!-- =========================
             CUSTOMER LIST
        ========================= -->

        <div class="customers-box">


            <div class="customers-header">

                <h2>
                    Customer List
                </h2>

            </div>


            <?php if ($customers_result && $customers_result->num_rows > 0): ?>


                <div class="table-wrapper">

                    <table class="customers-table">


                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Name
                                </th>

                                <th>
                                    Email
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Registered
                                </th>
<th>Action</th>
                            </tr>

                        </thead>


                        <tbody>


                            <?php while ($customer = $customers_result->fetch_assoc()): ?>

                                <tr>

                                    <td>

                                        <span class="customer-id">

                                            #<?php echo htmlspecialchars($customer['id']); ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span class="customer-name">

                                            <?php echo htmlspecialchars($customer['name']); ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php echo htmlspecialchars($customer['email']); ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo !empty($customer['phone'])
                                            ? htmlspecialchars($customer['phone'])
                                            : 'Not provided';

                                        ?>

                                    </td>


                                    <td>

                                        <?php echo htmlspecialchars($customer['created_at']); ?>

                                    </td>

<td>
    <a href="customer_view.php?id=<?php echo $customer['id']; ?>" class="view-btn">
    View
</a>

    <button type="button" class="delete-btn">
        Delete
    </button>
</td>
                                </tr>

                            <?php endwhile; ?>


                        </tbody>


                    </table>

                </div>


            <?php else: ?>


                <div class="empty-message">

                    <div class="empty-icon">
                        👥
                    </div>

                    <h3>
                        No Customers Yet
                    </h3>

                    <p>
                        Customer information will appear here
                        once customers register on the website.
                    </p>

                </div>


            <?php endif; ?>


        </div>


    </main>


</body>

</html>