<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../html/login.html"); exit(); }
include 'db.php';

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND deleted_by_receiver = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: messages.php?tab=inbox");
exit();
