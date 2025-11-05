<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../html/login.html"); exit(); }

$sender_id   = (int)$_SESSION['user_id'];
$receiver_id = (int)($_POST['receiver_id'] ?? 0);
$subject     = trim($_POST['subject'] ?? '');
$body        = trim($_POST['body'] ?? '');

if ($receiver_id <= 0 || $subject === '' || $body === '') {
    header("Location: messages.php?error=missing");
    exit();
}

/* sécurité basique: existance du destinataire */
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("i", $receiver_id);
$stmt->execute();
$ok = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ok) {
    header("Location: messages.php?error=unknown_user");
    exit();
}

$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, body) VALUES (?, ?, ?, ?)");
if (!$stmt) { die("SQL send: ".$conn->error); }
$stmt->bind_param("iiss", $sender_id, $receiver_id, $subject, $body);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: messages.php?sent=1&tab=sent");
exit();
