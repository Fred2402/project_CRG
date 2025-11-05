<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../html/login.html"); exit(); }

$user_id = (int)$_SESSION['user_id'];
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { header("Location: messages.php"); exit(); }

/* Récupérer pour savoir de quel côté on supprime */
$stmt = $conn->prepare("SELECT sender_id, receiver_id FROM messages WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) { header("Location: messages.php"); exit(); }

if ($row['sender_id'] == $user_id) {
    $st = $conn->prepare("UPDATE messages SET deleted_by_sender = 1 WHERE id = ?");
    $tab = 'sent';
} elseif ($row['receiver_id'] == $user_id) {
    $st = $conn->prepare("UPDATE messages SET deleted_by_receiver = 1 WHERE id = ?");
    $tab = 'inbox';
} else {
    header("Location: messages.php");
    exit();
}
$st->bind_param("i", $id);
$st->execute();
$st->close();
$conn->close();

header("Location: messages.php?deleted=1&tab=".$tab);
exit();
