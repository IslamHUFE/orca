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

$receiver_id = intval($_GET["user_id"] ?? 0);
$exchange_id = intval($_GET["exchange_id"] ?? 0);
$resource_id = intval($_GET["resource_id"] ?? 0);

$error = "";
$success = "";


/*
=========================================
CHECK RECEIVER
=========================================
*/

$sql = "
    SELECT
        user_id,
        full_name
    FROM users
    WHERE user_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$receiver_id]);

$receiver = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$receiver) {
    die("Student not found.");
}


/*
=========================================
SEND MESSAGE
=========================================
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $message_text = trim(
        $_POST["message"] ?? ""
    );


    if ($message_text === "") {

        $error = "Please enter a message.";

    } else {

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
            $resource_id > 0 ? $resource_id : null,
            $exchange_id > 0 ? $exchange_id : null,
            $message_text
        ]);


        header(
            "Location: conversation.php?user_id="
            . $receiver_id
            . "&exchange_id="
            . $exchange_id
            . "&resource_id="
            . $resource_id
        );

        exit();
    }
}


/*
=========================================
MARK RECEIVED MESSAGES AS READ
=========================================
*/

$sql = "
    UPDATE messages
    SET is_read = 1
    WHERE sender_id = ?
    AND receiver_id = ?
    AND exchange_id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $receiver_id,
    $user_id,
    $exchange_id
]);


/*
=========================================
GET CONVERSATION
=========================================
*/

$sql = "
    SELECT
        m.message_id,
        m.sender_id,
        m.receiver_id,
        m.message,
        m.created_at,

        sender.full_name AS sender_name

    FROM messages m

    INNER JOIN users sender
        ON m.sender_id = sender.user_id

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

    AND m.exchange_id = ?

    ORDER BY m.created_at ASC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $user_id,
    $receiver_id,
    $receiver_id,
    $user_id,
    $exchange_id
]);

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Conversation - UniShare</title>

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

.container {
    max-width: 850px;
    margin: 40px auto;
    padding: 0 20px;
}

.header {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
}

.header h1 {
    margin: 0 0 5px;
    font-size: 24px;
}

.header p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

.chat {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 15px;
    padding: 20px;
    min-height: 450px;
    max-height: 550px;
    overflow-y: auto;
}

.message {
    display: flex;
    margin-bottom: 15px;
}

.message.mine {
    justify-content: flex-end;
}

.bubble {
    max-width: 70%;
    padding: 12px 15px;
    border-radius: 12px;
    background: #f3f4f6;
}

.mine .bubble {
    background: #16803c;
    color: white;
}

.sender {
    font-size: 11px;
    font-weight: bold;
    margin-bottom: 5px;
}

.text {
    font-size: 14px;
    line-height: 1.5;
}

.date {
    font-size: 10px;
    margin-top: 5px;
    opacity: 0.7;
}

.form {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 15px;
    padding: 18px;
    margin-top: 20px;
    display: flex;
    gap: 10px;
}

.form textarea {
    flex: 1;
    resize: none;
    height: 50px;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-family: Arial;
}

.form button {
    border: none;
    background: #16803c;
    color: white;
    padding: 0 22px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
}

.form button:hover {
    background: #126b32;
}

.back {
    display: inline-block;
    margin-bottom: 15px;
    color: #16803c;
    text-decoration: none;
    font-size: 14px;
}

.error {
    background: #fff1f2;
    color: #be123c;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.empty {
    text-align: center;
    padding: 100px 20px;
    color: #6b7280;
}

</style>

</head>

<body>


<div class="container">


<a
    href="exchanges.php"
    class="back"
>
    ← Back to Exchanges
</a>


<div class="header">

    <h1>
        💬 <?= htmlspecialchars($receiver["full_name"]) ?>
    </h1>

    <p>
        Arrange the exchange and pickup details here.
    </p>

</div>


<?php if ($error !== ""): ?>

    <div class="error">
        <?= htmlspecialchars($error) ?>
    </div>

<?php endif; ?>


<div class="chat">


<?php if (empty($messages)): ?>

    <div class="empty">

        💬

        <br><br>

        No messages yet.

        <br>

        Start the conversation.

    </div>


<?php else: ?>


    <?php foreach ($messages as $msg): ?>


        <?php

        $mine =
            $msg["sender_id"] == $user_id;

        ?>


        <div class="message <?= $mine ? "mine" : "" ?>">


            <div class="bubble">


                <div class="sender">

                    <?= htmlspecialchars(
                        $mine
                        ? "You"
                        : $msg["sender_name"]
                    ) ?>

                </div>


                <div class="text">

                    <?= nl2br(
                        htmlspecialchars(
                            $msg["message"]
                        )
                    ) ?>

                </div>


                <div class="date">

                    <?= htmlspecialchars(
                        $msg["created_at"]
                    ) ?>

                </div>


            </div>


        </div>


    <?php endforeach; ?>


<?php endif; ?>


</div>


<form
    method="POST"
    class="form"
>

    <textarea
        name="message"
        placeholder="Write a message..."
        required
    ></textarea>


    <button type="submit">

        Send

    </button>

</form>


</div>


</body>

</html>