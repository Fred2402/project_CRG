<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../html/login.html");
    exit();
}

$user_id       = (int)($_POST['user_id'] ?? 0);
$training_name = trim($_POST['training_name'] ?? '');
$niveau        = trim($_POST['niveau'] ?? '');
$description   = trim($_POST['description'] ?? '');
$status        = ($_POST['status'] ?? 'non') === 'acquis' ? 'acquis' : 'non';
$date_input    = trim($_POST['date_completed'] ?? ''); // facultatif si acquis

if ($user_id <= 0 || $training_name === '' || $niveau === '') {
    header("Location: admin_add_training.php?error=invalid_input");
    exit();
}

/* Règle: 
   - status=acquis  => date_completed = (date_input || CURDATE())
   - status=non     => date_completed = NULL
*/
if ($status === 'acquis') {
    if ($date_input === '') {
        $sql = "INSERT INTO training (user_id, training_name, niveau, description, date_completed)
                VALUES (?, ?, ?, ?, CURDATE())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $user_id, $training_name, $niveau, $description);
    } else {
        $sql = "INSERT INTO training (user_id, training_name, niveau, description, date_completed)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $user_id, $training_name, $niveau, $description, $date_input);
    }
} else {
    $sql = "INSERT INTO training (user_id, training_name, niveau, description, date_completed)
            VALUES (?, ?, ?, ?, NULL)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $user_id, $training_name, $niveau, $description);
}

if (!$stmt) {
    header("Location: admin_add_training.php?error=prepare");
    exit();
}

if ($stmt->execute()) {
    header("Location: admin_add_training.php?created=1");
    exit();
} else {
    header("Location: admin_add_training.php?error=sql");
    exit();
}
