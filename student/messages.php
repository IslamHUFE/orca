<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit();
}

if ($_SESSION["role"] !== "student") {
    header("Location: ../index.php");
    exit();
}

require_once "../db.php";

$user_id = $_SESSION["user_id"];
$full_name = $_SESSION["full_name"];

$current_page = "messages.php";

$message = "";
$error = "";

$selected_user_id = intval($_GET["user"] ?? 0);


/*
====================================================
SEND MESSAGE / REPLY
====================================================
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $receiver_id = intval($_POST["receiver_id"] ?? 0);

    $resource_id = !empty($_POST["resource_id"])
        ? intval($_POST["resource_id"])
        : null;

    $exchange_id = !empty($_POST["exchange_id"])
        ? intval($_POST["exchange_id"])
        : null;

    $message_text = trim($_POST["message"] ?? "");


    if ($receiver_id <= 0) {

        $error = "Invalid receiver.";

    } elseif ($receiver_id == $user_id) {

        $error = "You cannot send a message to yourself.";

    } elseif ($message_text === "") {

        $error = "Please enter a message.";

    } else {

        /*
        Check that receiver exists
        */

        $sql = "
            SELECT user_id
            FROM users
            WHERE user_id = ?
            AND role = 'student'
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$receiver_id]);

        $receiver = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$receiver) {

            $error = "Student not found.";

        } else {

            /*
            Insert message
            */

            $sql = "
                INSERT INTO messages
                (
                    sender_id,
                    receiver_id,
                    resource_id,
                    exchange_id,
                    message
                )
                VALUES
                (?, ?, ?, ?, ?)
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $user_id,
                $receiver_id,
                $resource_id,
                $exchange_id,
                $message_text
            ]);


            /*
            Open the conversation after sending
            */

            header(
                "Location: messages.php?user=" . $receiver_id
            );

            exit();
        }
    }
}


/*
====================================================
GET CONVERSATION USERS
====================================================
*/

$sql = "
    SELECT
        u.user_id,
        u.full_name,

        (
            SELECT m2.message
            FROM messages m2
            WHERE
                (
                    m2.sender_id = ?
                    AND m2.receiver_id = u.user_id
                )
                OR
                (
                    m2.sender_id = u.user_id
                    AND m2.receiver_id = ?
                )
            ORDER BY m2.created_at DESC
            LIMIT 1
        ) AS last_message,

        (
            SELECT m3.created_at
            FROM messages m3
            WHERE
                (
                    m3.sender_id = ?
                    AND m3.receiver_id = u.user_id
                )
                OR
                (
                    m3.sender_id = u.user_id
                    AND m3.receiver_id = ?
                )
            ORDER BY m3.created_at DESC
            LIMIT 1
        ) AS last_message_time

    FROM users u

    WHERE u.user_id != ?

    AND EXISTS (

        SELECT 1

        FROM messages m

        WHERE
            (
                m.sender_id = ?
                AND m.receiver_id = u.user_id
            )
            OR
            (
                m.sender_id = u.user_id
                AND m.receiver_id = ?
            )
    )

    ORDER BY last_message_time DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $user_id,
    $user_id,
    $user_id,
    $user_id,
    $user_id,
    $user_id,
    $user_id
]);

$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
====================================================
GET SELECTED STUDENT
====================================================
*/

$selected_user = null;

if ($selected_user_id > 0) {

    $sql = "
        SELECT
            user_id,
            full_name,
            email
        FROM users
        WHERE user_id = ?
        AND role = 'student'
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $selected_user_id
    ]);

    $selected_user = $stmt->fetch(PDO::FETCH_ASSOC);
}


/*
====================================================
GET MESSAGES OF SELECTED CONVERSATION
====================================================
*/

$conversation_messages = [];

if ($selected_user) {

    $sql = "
        SELECT
            m.message_id,
            m.sender_id,
            m.receiver_id,
            m.resource_id,
            m.exchange_id,
            m.message,
            m.is_read,
            m.created_at,

            sender.full_name AS sender_name,
            receiver.full_name AS receiver_name,

            r.name AS resource_name

        FROM messages m

        INNER JOIN users sender
            ON m.sender_id = sender.user_id

        INNER JOIN users receiver
            ON m.receiver_id = receiver.user_id

        LEFT JOIN resource r
            ON m.resource_id = r.resource_id

        WHERE
            (
                m.sender_id = ?
                AND m.receiver_id = ?
            )
            OR
            (
                m.sender_id = ?
                AND m.receiver_id = ?
            )

        ORDER BY m.created_at ASC
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $user_id,
        $selected_user_id,
        $selected_user_id,
        $user_id
    ]);

    $conversation_messages =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


    /*
    Mark received messages as read
    */

    $sql = "
        UPDATE messages

        SET is_read = 1

        WHERE
            sender_id = ?
            AND receiver_id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $selected_user_id,
        $user_id
    ]);
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

<title>Messages - UniShare</title>


<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;

    font-family: Arial, sans-serif;

    background: #f8faf9;

    color: #111827;
}


/* =========================
   Layout
========================= */

.dashboard-container {
    display: flex;

    min-height: 100vh;
}



/* =========================
   Main
========================= */

.main-content {

    margin-left: 240px;

    width: calc(100% - 240px);

    min-height: 100vh;

    padding: 40px;
}


.messages-container {

    max-width: 1100px;

    margin: auto;
}


/* =========================
   Header
========================= */

.page-header {

    margin-bottom: 25px;
}


.page-header h1 {

    margin: 0 0 8px;

    font-size: 32px;
}


.page-header p {

    margin: 0;

    color: #6b7280;

    font-size: 15px;
}


/* =========================
   Alerts
========================= */

.message-alert {

    padding: 13px 16px;

    border-radius: 8px;

    margin-bottom: 20px;
}


.success {

    background: #eaf6ee;

    color: #16803c;

    border: 1px solid #cdebd8;
}


.error {

    background: #fff1f2;

    color: #be123c;

    border: 1px solid #fecdd3;
}


/* =========================
   Chat Layout
========================= */

.chat-container {

    display: grid;

    grid-template-columns: 300px 1fr;

    height: 650px;

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 15px;

    overflow: hidden;
}


/* =========================
   Conversations
========================= */

.conversations {

    border-right: 1px solid #e5e7eb;

    background: #fafafa;

    overflow-y: auto;
}


.conversations-title {

    padding: 20px;

    font-size: 18px;

    font-weight: 700;

    border-bottom: 1px solid #e5e7eb;
}


.conversation-link {

    display: block;

    text-decoration: none;

    color: #111827;

    padding: 15px;

    border-bottom: 1px solid #eeeeee;

    transition: 0.2s;
}


.conversation-link:hover {

    background: #f0f8f3;
}


.conversation-link.active {

    background: #eaf6ee;
}


.conversation-person {

    display: flex;

    align-items: center;

    gap: 10px;
}


.conversation-avatar {

    width: 42px;

    height: 42px;

    border-radius: 50%;

    background: #16803c;

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: bold;

    flex-shrink: 0;
}


.conversation-info {

    min-width: 0;

    flex: 1;
}


.conversation-name {

    font-weight: 600;

    font-size: 14px;

    margin-bottom: 5px;
}


.last-message {

    color: #6b7280;

    font-size: 12px;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}


/* =========================
   Chat
========================= */

.chat {

    display: flex;

    flex-direction: column;

    min-width: 0;
}


.chat-header {

    padding: 18px 20px;

    border-bottom: 1px solid #e5e7eb;

    display: flex;

    align-items: center;

    gap: 12px;
}


.chat-header-avatar {

    width: 42px;

    height: 42px;

    border-radius: 50%;

    background: #16803c;

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: bold;
}


.chat-header-info strong {

    display: block;

    font-size: 15px;
}


.chat-header-info span {

    color: #6b7280;

    font-size: 12px;
}


/* =========================
   Chat Messages
========================= */

.chat-messages {

    flex: 1;

    padding: 25px;

    overflow-y: auto;

    background: #f8faf9;

    display: flex;

    flex-direction: column;

    gap: 12px;
}


.chat-message {

    max-width: 70%;

    display: flex;

    flex-direction: column;
}


.chat-message.mine {

    align-self: flex-end;

    align-items: flex-end;
}


.chat-message.theirs {

    align-self: flex-start;

    align-items: flex-start;
}


.bubble {

    padding: 11px 15px;

    border-radius: 14px;

    font-size: 14px;

    line-height: 1.5;

    word-break: break-word;
}


.mine .bubble {

    background: #16803c;

    color: white;

    border-bottom-right-radius: 4px;
}


.theirs .bubble {

    background: white;

    color: #374151;

    border: 1px solid #e5e7eb;

    border-bottom-left-radius: 4px;
}


.chat-time {

    color: #9ca3af;

    font-size: 10px;

    margin-top: 4px;
}


.chat-resource {

    margin-top: 5px;

    padding: 5px 9px;

    background: #eaf6ee;

    color: #16803c;

    border-radius: 10px;

    font-size: 10px;
}


/* =========================
   Send Box
========================= */

.send-box {

    border-top: 1px solid #e5e7eb;

    padding: 15px;

    background: white;
}


.send-form {

    display: flex;

    gap: 10px;
}


.send-form textarea {

    flex: 1;

    resize: none;

    height: 45px;

    border: 1px solid #d1d5db;

    border-radius: 9px;

    padding: 12px;

    font-family: Arial, sans-serif;

    font-size: 14px;

    outline: none;
}


.send-form textarea:focus {

    border-color: #16803c;
}


.send-btn {

    border: none;

    border-radius: 9px;

    background: #16803c;

    color: white;

    padding: 0 20px;

    font-weight: 600;

    cursor: pointer;
}


.send-btn:hover {

    background: #126b32;
}


/* =========================
   Empty
========================= */

.empty-chat {

    height: 100%;

    display: flex;

    flex-direction: column;

    justify-content: center;

    align-items: center;

    text-align: center;

    color: #6b7280;

    padding: 30px;
}


.empty-chat-icon {

    font-size: 60px;

    margin-bottom: 15px;
}


.empty-chat h3 {

    color: #374151;

    margin-bottom: 8px;
}


.no-conversations {

    padding: 40px 20px;

    text-align: center;

    color: #6b7280;

    font-size: 13px;
}


/* =========================
   Responsive
========================= */

@media (max-width: 850px) {

    .chat-container {

        grid-template-columns: 230px 1fr;
    }

}


@media (max-width: 650px) {

    .sidebar {

        position: relative;

        width: 100%;

        min-height: auto;
    }

    .dashboard-container {

        flex-direction: column;
    }

    .main-content {

        margin-left: 0;

        width: 100%;

        padding: 20px;
    }

    .chat-container {

        grid-template-columns: 1fr;

        height: auto;

        min-height: 600px;
    }

    .conversations {

        display: none;
    }

}

</style>

</head>


<body>


<div class="dashboard-container">


<!-- =========================
     SIDEBAR
========================= -->

<?php include "includes/sidebar.php"; ?>


<!-- =========================
     MAIN
========================= -->

<main class="main-content">


<div class="messages-container">


    <div class="page-header">

        <h1>
            Messages
        </h1>

        <p>
            Communicate with other students about resources and exchanges.
        </p>

    </div>


    <?php if ($message !== ""): ?>

        <div class="message-alert success">

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="message-alert error">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <div class="chat-container">


        <!-- ==========================================
             CONVERSATIONS
        =========================================== -->

        <div class="conversations">


            <div class="conversations-title">

                Conversations

            </div>


            <?php if (empty($conversations)): ?>


                <div class="no-conversations">

                    💬

                    <br><br>

                    No conversations yet.

                </div>


            <?php else: ?>


                <?php foreach ($conversations as $conversation): ?>


                    <?php

                    $conversation_initial =
                        strtoupper(
                            substr(
                                $conversation["full_name"],
                                0,
                                1
                            )
                        );

                    ?>


                    <a
                        href="messages.php?user=<?= $conversation["user_id"] ?>"
                        class="conversation-link
                        <?= $selected_user_id == $conversation["user_id"] ? 'active' : '' ?>"
                    >


                        <div class="conversation-person">


                            <div class="conversation-avatar">

                                <?= htmlspecialchars(
                                    $conversation_initial
                                ) ?>

                            </div>


                            <div class="conversation-info">


                                <div class="conversation-name">

                                    <?= htmlspecialchars(
                                        $conversation["full_name"]
                                    ) ?>

                                </div>


                                <div class="last-message">

                                    <?= htmlspecialchars(
                                        $conversation["last_message"] ?? ""
                                    ) ?>

                                </div>


                            </div>


                        </div>


                    </a>


                <?php endforeach; ?>


            <?php endif; ?>


        </div>



        <!-- ==========================================
             CHAT
        =========================================== -->

        <div class="chat">


            <?php if (!$selected_user): ?>


                <div class="empty-chat">

                    <div class="empty-chat-icon">
                        💬
                    </div>

                    <h3>
                        Select a conversation
                    </h3>

                    <p>
                        Choose a student from the conversations
                        list to start chatting.
                    </p>

                </div>


            <?php else: ?>


                <!-- CHAT HEADER -->


                <?php

                $selected_initial =
                    strtoupper(
                        substr(
                            $selected_user["full_name"],
                            0,
                            1
                        )
                    );

                ?>


                <div class="chat-header">


                    <div class="chat-header-avatar">

                        <?= htmlspecialchars(
                            $selected_initial
                        ) ?>

                    </div>


                    <div class="chat-header-info">

                        <strong>

                            <?= htmlspecialchars(
                                $selected_user["full_name"]
                            ) ?>

                        </strong>

                        <span>
                            Student
                        </span>

                    </div>


                </div>



                <!-- CHAT MESSAGES -->


                <div class="chat-messages">


                    <?php if (empty($conversation_messages)): ?>


                        <div class="empty-chat">

                            <div class="empty-chat-icon">
                                👋
                            </div>

                            <h3>
                                Start the conversation
                            </h3>

                            <p>
                                Send your first message.
                            </p>

                        </div>


                    <?php else: ?>


                        <?php foreach ($conversation_messages as $msg): ?>


                            <?php

                            $is_mine =
                                $msg["sender_id"] == $user_id;

                            ?>


                            <div
                                class="chat-message
                                <?= $is_mine ? 'mine' : 'theirs' ?>"
                            >


                                <div class="bubble">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $msg["message"]
                                        )
                                    ) ?>

                                </div>


                                <?php if (!empty($msg["resource_name"])): ?>

                                    <div class="chat-resource">

                                        📦

                                        <?= htmlspecialchars(
                                            $msg["resource_name"]
                                        ) ?>

                                    </div>

                                <?php endif; ?>


                                <div class="chat-time">

                                    <?= htmlspecialchars(
                                        $msg["created_at"]
                                    ) ?>

                                </div>


                            </div>


                        <?php endforeach; ?>


                    <?php endif; ?>


                </div>



                <!-- SEND MESSAGE -->


                <div class="send-box">


                    <form
                        method="POST"
                        class="send-form"
                    >


                        <input
                            type="hidden"
                            name="receiver_id"
                            value="<?= $selected_user["user_id"] ?>"
                        >


                        <textarea
                            name="message"
                            placeholder="Type your message..."
                            required
                        ></textarea>


                        <button
                            type="submit"
                            class="send-btn"
                        >
                            Send
                        </button>


                    </form>


                </div>


            <?php endif; ?>


        </div>


    </div>


</div>


</main>


</div>


</body>

</html>