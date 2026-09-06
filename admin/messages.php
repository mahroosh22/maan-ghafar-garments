<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

$message = "";
$error = "";


/* =========================================
   CSRF TOKEN
========================================= */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];


/* =========================================
   HELPER - SAFE OUTPUT
========================================= */

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================
   POST ACTIONS
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $posted_token = $_POST['csrf_token'] ?? '';

    if (
        empty($posted_token) ||
        !hash_equals($csrf_token, $posted_token)
    ) {
        $error = "Invalid security token. Please try again.";

    } else {

        $action = trim($_POST['action'] ?? '');

        $message_id = filter_var(
            $_POST['message_id'] ?? '',
            FILTER_VALIDATE_INT
        );

        if (
            $message_id === false ||
            $message_id <= 0
        ) {
            $error = "Invalid message.";

        } elseif (
            !in_array(
                $action,
                ['mark_read', 'delete', 'send_reply'],
                true
            )
        ) {
            $error = "Invalid action.";

        }


        /* =========================================
           MARK MESSAGE AS READ
        ========================================= */

        elseif ($action === 'mark_read') {

            $stmt = $conn->prepare("
                UPDATE contact_messages
                SET status = 'read'
                WHERE message_id = ?
            ");

            if (!$stmt) {

                $error = "Database error.";

            } else {

                $stmt->bind_param(
                    "i",
                    $message_id
                );

                if ($stmt->execute()) {

                    $stmt->close();

                    header("Location: messages.php");
                    exit;

                } else {

                    $error = "Unable to mark message as read.";
                    $stmt->close();
                }
            }
        }


        /* =========================================
           DELETE MESSAGE
        ========================================= */

        elseif ($action === 'delete') {

            $stmt = $conn->prepare("
                DELETE FROM contact_messages
                WHERE message_id = ?
            ");

            if (!$stmt) {

                $error = "Database error.";

            } else {

                $stmt->bind_param(
                    "i",
                    $message_id
                );

                if ($stmt->execute()) {

                    $stmt->close();

                    header("Location: messages.php");
                    exit;

                } else {

                    $error = "Unable to delete message.";
                    $stmt->close();
                }
            }
        }


        /* =========================================
           SEND / UPDATE ADMIN REPLY
        ========================================= */

        elseif ($action === 'send_reply') {

            $admin_reply = trim(
                $_POST['admin_reply'] ?? ''
            );


            if ($admin_reply === '') {

                $error = "Please write a reply.";

            } elseif (mb_strlen($admin_reply) > 5000) {

                $error =
                    "Reply is too long. Maximum 5000 characters allowed.";

            } else {

                /*
                    reply_seen = 0
                    means customer has not seen
                    the new admin reply yet.
                */

                $stmt = $conn->prepare("
                    UPDATE contact_messages
                    SET
                        admin_reply = ?,
                        status = 'read',
                        reply_seen = 0
                    WHERE message_id = ?
                ");

                if (!$stmt) {

                    $error = "Database error.";

                } else {

                    $stmt->bind_param(
                        "si",
                        $admin_reply,
                        $message_id
                    );

                    if ($stmt->execute()) {

                        $stmt->close();

                        header(
                            "Location: messages.php?reply=success"
                        );

                        exit;

                    } else {

                        $error =
                            "Unable to send reply.";

                        $stmt->close();
                    }
                }
            }
        }
    }
}


/* =========================================
   REPLY SUCCESS MESSAGE
========================================= */

if (
    isset($_GET['reply']) &&
    $_GET['reply'] === 'success'
) {
    $message = "Reply saved successfully.";
}


/* =========================================
   GET STATISTICS
========================================= */

$total_messages = 0;
$unread_messages = 0;
$read_messages = 0;

$result = $conn->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'unread'), 0) AS unread,
        COALESCE(SUM(status = 'read'), 0) AS read_count
    FROM contact_messages
");

if ($result) {

    $row = $result->fetch_assoc();

    $total_messages =
        (int) ($row['total'] ?? 0);

    $unread_messages =
        (int) ($row['unread'] ?? 0);

    $read_messages =
        (int) ($row['read_count'] ?? 0);
}


/* =========================================
   GET ALL MESSAGES
========================================= */

$messages = [];

$result = $conn->query("
    SELECT
        message_id,
        name,
        email,
        subject,
        message,
        admin_reply,
        reply_seen,
        status,
        created_at
    FROM contact_messages
    ORDER BY
        CASE
            WHEN status = 'unread' THEN 0
            ELSE 1
        END,
        created_at DESC
");

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
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

    <title>Contact Messages - Admin</title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            color: #222;
        }


        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 240px;
            height: 100vh;
            background: #111827;
            color: white;
            padding: 25px 18px;
            overflow-y: auto;
        }


        .sidebar h2 {
            margin: 0 0 35px;
            text-align: center;
            color: #d4af37;
            font-size: 22px;
        }


        .sidebar a {
            display: block;
            color: #ddd;
            text-decoration: none;
            padding: 13px 15px;
            margin-bottom: 8px;
            border-radius: 7px;
            transition: 0.3s;
        }


        .sidebar a:hover,
        .sidebar a.active {
            background: #d4af37;
            color: #111;
        }


        .logout-link {
            margin-top: 30px;
            border-top: 1px solid #333;
            padding-top: 20px !important;
        }


        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 240px;
            padding: 35px;
            min-height: 100vh;
        }


        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            gap: 20px;
        }


        .topbar h1 {
            margin: 0;
            font-size: 30px;
            color: #111827;
        }


        .topbar p {
            margin: 7px 0 0;
            color: #777;
        }


        /* =========================
           ALERTS
        ========================= */

        .alert {
            padding: 13px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
        }


        .alert-success {
            background: #e8f7ee;
            color: #187a3d;
            border: 1px solid #bde5ca;
        }


        .alert-error {
            background: #feecec;
            color: #b42318;
            border: 1px solid #f3c4c4;
        }


        /* =========================
           STATS
        ========================= */

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }


        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.06);
            border-left: 4px solid #d4af37;
        }


        .stat-card h3 {
            margin: 0 0 8px;
            font-size: 15px;
            color: #777;
            font-weight: normal;
        }


        .stat-card .number {
            font-size: 30px;
            font-weight: bold;
            color: #111827;
        }


        /* =========================
           MESSAGES
        ========================= */

        .messages-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }


        .messages-header {
            padding: 22px 25px;
            border-bottom: 1px solid #eee;
        }


        .messages-header h2 {
            margin: 0;
            color: #111827;
            font-size: 21px;
        }


        .message-card {
            padding: 25px;
            border-bottom: 1px solid #eee;
            transition: 0.2s;
        }


        .message-card:last-child {
            border-bottom: none;
        }


        .message-card:hover {
            background: #fafafa;
        }


        .message-card.unread {
            background: #fffdf4;
            border-left: 4px solid #d4af37;
        }


        .message-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }


        .sender-info {
            flex: 1;
        }


        .sender-info h3 {
            margin: 0 0 7px;
            color: #111827;
            font-size: 18px;
        }


        .sender-info .email {
            color: #777;
            font-size: 14px;
        }


        .message-date {
            color: #888;
            font-size: 13px;
            white-space: nowrap;
        }


        .message-subject {
            margin-top: 18px;
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }


        .message-text {
            margin-top: 10px;
            color: #555;
            line-height: 1.7;
            white-space: pre-wrap;
            word-break: break-word;
        }


        /* =========================
           ADMIN REPLY
        ========================= */

        .reply-box {
            margin-top: 22px;
            padding: 18px;
            background: #f8f9fb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
        }


        .reply-box h4 {
            margin: 0 0 10px;
            color: #111827;
            font-size: 15px;
        }


        .reply-box textarea {
            width: 100%;
            min-height: 110px;
            resize: vertical;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            outline: none;
            background: white;
        }


        .reply-box textarea:focus {
            border-color: #d4af37;
            box-shadow:
                0 0 0 3px
                rgba(212, 175, 55, 0.12);
        }


        .btn-reply {
            margin-top: 10px;
            border: none;
            background: #d4af37;
            color: #111;
            cursor: pointer;
        }


        .btn-reply:hover {
            background: #b99524;
            transform: translateY(-1px);
        }


        /* =========================
           EXISTING REPLY
        ========================= */

        .existing-reply {
            margin-top: 20px;
            padding: 16px 18px;
            background: #f1f8f3;
            border-left: 4px solid #187a3d;
            border-radius: 8px;
        }


        .existing-reply-title {
            font-weight: bold;
            color: #187a3d;
            margin-bottom: 8px;
            font-size: 14px;
        }


        .existing-reply-text {
            color: #444;
            line-height: 1.7;
            white-space: pre-wrap;
            word-break: break-word;
            font-size: 14px;
        }


        .message-footer {
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }


        /* =========================
           STATUS
        ========================= */

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }


        .status.unread {
            background: #fff1c7;
            color: #8a6500;
        }


        .status.read {
            background: #e8f7ee;
            color: #187a3d;
        }


        /* =========================
           BUTTONS
        ========================= */

        .btn {
            display: inline-block;
            padding: 8px 13px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
            transition: 0.2s;
        }


        .btn-read {
            background: #111827;
            color: white;
            border: none;
            cursor: pointer;
        }


        .btn-read:hover {
            background: #273449;
        }


        .btn-delete {
            background: #fdecec;
            color: #b42318;
            border: none;
            cursor: pointer;
        }


        .btn-delete:hover {
            background: #f8d5d5;
        }


        /* =========================
           EMPTY
        ========================= */

        .empty {
            padding: 60px 25px;
            text-align: center;
            color: #777;
        }


        .empty-icon {
            font-size: 45px;
            margin-bottom: 15px;
        }


        .empty h3 {
            margin: 0 0 8px;
            color: #333;
        }


        /* =========================
           ACTION FORM
        ========================= */

        .action-form {
            display: inline;
            margin: 0;
            padding: 0;
        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 900px) {

            .sidebar {
                width: 200px;
            }


            .main {
                margin-left: 200px;
                padding: 25px;
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
                padding: 18px;
            }


            .sidebar h2 {
                margin-bottom: 18px;
            }


            .sidebar a {
                display: inline-block;
                margin-right: 5px;
            }


            .main {
                margin-left: 0;
                padding: 20px;
            }


            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }


            .message-top {
                flex-direction: column;
                gap: 8px;
            }


            .message-date {
                white-space: normal;
            }

        }

    </style>

</head>


<body>


<!-- =========================================
     SIDEBAR
========================================= -->

<aside class="sidebar">

    <h2>Maan Ghafar</h2>


    <a href="dashboard.php">
        📊 Dashboard
    </a>


    <a href="products.php">
        🛍️ Products
    </a>


    <a href="orders.php">
        📦 Orders
    </a>


    <a href="customers.php">
        👥 Customers
    </a>


    <a href="messages.php" class="active">
        💬 Messages
    </a>


    <a href="logout.php" class="logout-link">
        🚪 Logout
    </a>

</aside>



<!-- =========================================
     MAIN CONTENT
========================================= -->

<main class="main">


    <div class="topbar">

        <div>

            <h1>
                Contact Messages
            </h1>

            <p>
                Manage messages received from your customers.
            </p>

        </div>

    </div>



    <?php if ($message !== ""): ?>

        <div class="alert alert-success">

            <?= e($message) ?>

        </div>

    <?php endif; ?>



    <?php if ($error !== ""): ?>

        <div class="alert alert-error">

            <?= e($error) ?>

        </div>

    <?php endif; ?>



    <!-- =====================================
         STATISTICS
    ====================================== -->

    <div class="stats">


        <div class="stat-card">

            <h3>
                Total Messages
            </h3>

            <div class="number">
                <?= $total_messages ?>
            </div>

        </div>



        <div class="stat-card">

            <h3>
                Unread Messages
            </h3>

            <div class="number">
                <?= $unread_messages ?>
            </div>

        </div>



        <div class="stat-card">

            <h3>
                Read Messages
            </h3>

            <div class="number">
                <?= $read_messages ?>
            </div>

        </div>


    </div>



    <!-- =====================================
         MESSAGES
    ====================================== -->

    <div class="messages-container">


        <div class="messages-header">

            <h2>
                Customer Messages
            </h2>

        </div>



        <?php if (empty($messages)): ?>


            <div class="empty">

                <div class="empty-icon">
                    💬
                </div>


                <h3>
                    No Messages Yet
                </h3>


                <p>
                    Customer messages will appear here
                    when someone contacts you.
                </p>

            </div>


        <?php else: ?>


            <?php foreach ($messages as $msg): ?>

                <?php

                $status = strtolower(
                    trim(
                        (string) ($msg['status'] ?? 'read')
                    )
                );

                if (
                    !in_array(
                        $status,
                        ['unread', 'read'],
                        true
                    )
                ) {
                    $status = 'read';
                }

                $message_name = trim(
                    (string) ($msg['name'] ?? '')
                );

                if ($message_name === '') {
                    $message_name = 'Customer';
                }

                ?>


                <div
                    class="message-card
                    <?= $status === 'unread'
                        ? 'unread'
                        : ''
                    ?>"
                >


                    <div class="message-top">


                        <div class="sender-info">


                            <h3>

                                <?= e($message_name) ?>


                                <?php if (
                                    $status === 'unread'
                                ): ?>

                                    <span
                                        style="
                                            font-size:11px;
                                            background:#d4af37;
                                            color:#111;
                                            padding:4px 7px;
                                            border-radius:10px;
                                            margin-left:6px;
                                        "
                                    >
                                        NEW
                                    </span>

                                <?php endif; ?>


                            </h3>


                            <div class="email">

                                📧

                                <?= e(
                                    $msg['email'] ?? ''
                                ) ?>

                            </div>


                        </div>


                        <div class="message-date">

                            <?= e(
                                date(
                                    "d M Y, h:i A",
                                    strtotime(
                                        $msg['created_at']
                                    )
                                )
                            ) ?>

                        </div>


                    </div>



                    <div class="message-subject">

                        Subject:

                        <?= e(
                            $msg['subject'] ?? ''
                        ) ?>

                    </div>



                    <div class="message-text">

                        <?= e(
                            $msg['message'] ?? ''
                        ) ?>

                    </div>



                    <!-- EXISTING ADMIN REPLY -->

                    <?php if (
                        !empty($msg['admin_reply'])
                    ): ?>

                        <div class="existing-reply">


                            <div class="existing-reply-title">

                                💬 Admin Reply

                            </div>


                            <div class="existing-reply-text">

                                <?= e(
                                    $msg['admin_reply']
                                ) ?>

                            </div>


                        </div>

                    <?php endif; ?>



                    <!-- REPLY FORM -->

                    <div class="reply-box">


                        <h4>

                            <?= !empty(
                                $msg['admin_reply']
                            )
                                ? '✏️ Update Reply'
                                : '💬 Reply to Customer'
                            ?>

                        </h4>


                        <form
                            method="POST"
                            action="messages.php"
                        >


                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e($csrf_token) ?>"
                            >


                            <input
                                type="hidden"
                                name="action"
                                value="send_reply"
                            >


                            <input
                                type="hidden"
                                name="message_id"
                                value="<?= (int)
                                    $msg['message_id'] ?>"
                            >


                            <textarea
                                name="admin_reply"
                                placeholder="Write your reply to the customer..."
                                maxlength="5000"
                                required
                            ><?= e(
                                $msg['admin_reply'] ?? ''
                            ) ?></textarea>


                            <button
                                type="submit"
                                class="btn btn-reply"
                            >

                                💬

                                <?= !empty(
                                    $msg['admin_reply']
                                )
                                    ? 'Update Reply'
                                    : 'Send Reply'
                                ?>

                            </button>


                        </form>


                    </div>



                    <!-- FOOTER -->

                    <div class="message-footer">


                        <span
                            class="status <?= e($status) ?>"
                        >

                            <?= ucfirst(e($status)) ?>

                        </span>



                        <?php if (
                            $status === 'unread'
                        ): ?>


                            <form
                                method="POST"
                                action="messages.php"
                                class="action-form"
                                onsubmit="
                                    return confirm(
                                        'Mark this message as read?'
                                    );
                                "
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e($csrf_token) ?>"
                                >


                                <input
                                    type="hidden"
                                    name="action"
                                    value="mark_read"
                                >


                                <input
                                    type="hidden"
                                    name="message_id"
                                    value="<?= (int)
                                        $msg['message_id'] ?>"
                                >


                                <button
                                    type="submit"
                                    class="btn btn-read"
                                >

                                    ✓ Mark as Read

                                </button>

                            </form>


                        <?php endif; ?>



                        <form
                            method="POST"
                            action="messages.php"
                            class="action-form"
                            onsubmit="
                                return confirm(
                                    'Are you sure you want to permanently delete this message?'
                                );
                            "
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e($csrf_token) ?>"
                            >


                            <input
                                type="hidden"
                                name="action"
                                value="delete"
                            >


                            <input
                                type="hidden"
                                name="message_id"
                                value="<?= (int)
                                    $msg['message_id'] ?>"
                            >


                            <button
                                type="submit"
                                class="btn btn-delete"
                            >

                                🗑 Delete

                            </button>

                        </form>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php endif; ?>


    </div>


</main>


</body>

</html>