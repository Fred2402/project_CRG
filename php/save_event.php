<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../html/login.html");
    exit();
}
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');

    if ($title === '') {
        header("Location: create_event.php?error=title_required");
        exit();
    }

    if ($event_date === '') {
        $stmt = $conn->prepare("INSERT INTO events (title, description, event_date) VALUES (?, ?, NULL)");
        $stmt->bind_param("ss", $title, $description);
    } else {
        $stmt = $conn->prepare("INSERT INTO events (title, description, event_date) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $description, $event_date);
    }

    if ($stmt && $stmt->execute()) {
        header("Location: create_event.php?created=1");
        exit();
    } else {
        header("Location: create_event.php?error=sql");
        exit();
    }
}
