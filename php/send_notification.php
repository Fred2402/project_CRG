<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../html/login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_dashboard.php?error=bad_method");
    exit();
}

$message = trim($_POST['message'] ?? '');
$localite_raw = $_POST['localite_id'] ?? '';

if ($message === '') {
    header("Location: admin_dashboard.php?error=empty_message");
    exit();
}

$stmt = null;

if ($localite_raw === 'all') {
    // Cible: tous les utilisateurs → localite_id = NULL
    $sql = "INSERT INTO notifications (message, localite_id) VALUES (?, NULL)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        header("Location: admin_dashboard.php?error=prepare");
        exit();
    }
    $stmt->bind_param("s", $message);
} else {
    // Cible: une localité précise
    $localite_id = (int)$localite_raw;
    if ($localite_id <= 0) {
        header("Location: admin_dashboard.php?error=bad_localite");
        exit();
    }
    $sql = "INSERT INTO notifications (message, localite_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        header("Location: admin_dashboard.php?error=prepare");
        exit();
    }
    $stmt->bind_param("si", $message, $localite_id);
}

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: admin_dashboard.php?notif=1");
    exit();
} else {
    $err = urlencode($stmt->error);
    $stmt->close();
    $conn->close();
    header("Location: admin_dashboard.php?error=sql&detail=".$err);
    exit();
}
