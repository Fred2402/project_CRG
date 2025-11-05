<?php
// php/messages_unread_count.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit();
}

include 'db.php';

$user_id = (int)$_SESSION['user_id'];

$sql = "SELECT COUNT(*) AS c
        FROM messages
        WHERE receiver_id = ?
          AND is_read = 0
          AND deleted_by_receiver = 0";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'prepare']);
    exit();
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

$count = (int)($res['c'] ?? 0);
echo json_encode(['count' => $count]);
