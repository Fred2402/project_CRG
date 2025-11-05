<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../html/login.html"); exit(); }

$user_id = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);
$tab = ($_GET['tab'] ?? 'inbox') === 'sent' ? 'sent' : 'inbox';
if ($id <= 0) { header("Location: messages.php?tab=".$tab); exit(); }

$sql = "SELECT m.*, 
               su.first_name AS s_first, su.last_name AS s_last, su.email AS s_email,
               ru.first_name AS r_first, ru.last_name AS r_last, ru.email AS r_email
        FROM messages m
        JOIN users su ON su.id = m.sender_id
        JOIN users ru ON ru.id = m.receiver_id
        WHERE m.id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) die("SQL view: ".$conn->error);
$stmt->bind_param("i", $id);
$stmt->execute();
$msg = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$msg) { header("Location: messages.php?tab=".$tab); exit(); }

/* Autorisation : visible si je suis expéditeur ou destinataire (et pas soft-deleted de mon côté) */
if ($msg['sender_id'] != $user_id && $msg['receiver_id'] != $user_id) {
    header("Location: messages.php?tab=".$tab);
    exit();
}
if ($msg['receiver_id'] == $user_id && $msg['deleted_by_receiver']) { header("Location: messages.php?tab=inbox"); exit(); }
if ($msg['sender_id']   == $user_id && $msg['deleted_by_sender'])   { header("Location: messages.php?tab=sent");  exit(); }

/* Marquer comme lu si je suis destinataire */
if ($msg['receiver_id'] == $user_id && !$msg['is_read']) {
    $st = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
    if ($st) { $st->bind_param("i", $id); $st->execute(); $st->close(); }
}

$conn->close();
$isMine = ($msg['sender_id'] == $user_id);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Message</title>
  <link rel="stylesheet" href="../css/messages.css">
</head>
<body>
  <div class="container">
    <div class="toolbar">
      <a class="btn" href="messages.php?tab=<?php echo htmlspecialchars($tab); ?>">← Retour</a>
      <div class="spacer"></div>
      <form action="message_delete.php" method="post" onsubmit="return confirm('Supprimer ce message ?');">
        <input type="hidden" name="id" value="<?php echo (int)$msg['id']; ?>">
        <button class="btn danger" type="submit">Supprimer</button>
      </form>
    </div>

    <div class="message card">
      <div class="head">
        <div class="subject"><?php echo htmlspecialchars($msg['subject']); ?></div>
        <div class="date"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($msg['created_at']))); ?></div>
      </div>
      <div class="meta">
        <div><strong>De :</strong> <?php echo htmlspecialchars($msg['s_first'].' '.$msg['s_last'].' <'.$msg['s_email'].'>'); ?></div>
        <div><strong>À :</strong> <?php echo htmlspecialchars($msg['r_first'].' '.$msg['r_last'].' <'.$msg['r_email'].'>'); ?></div>
      </div>
      <div class="body"><?php echo nl2br(htmlspecialchars($msg['body'])); ?></div>
    </div>

    <?php if ($msg['receiver_id'] == $user_id): ?>
      <div class="reply card">
        <form action="message_send.php" method="post">
          <input type="hidden" name="receiver_id" value="<?php echo (int)$msg['sender_id']; ?>">
          <label>Objet</label>
          <input type="text" name="subject" value="<?php echo htmlspecialchars('Re: '.$msg['subject']); ?>" required>
          <label>Message</label>
          <textarea name="body" rows="5" required></textarea>
          <div class="actions">
            <button class="btn primary" type="submit">Envoyer</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
