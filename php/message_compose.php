<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../html/login.html"); exit(); }

$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';

if ($role === 'admin') {
    $res = $conn->query("SELECT id, first_name, last_name, email FROM users ORDER BY first_name, last_name");
} else {
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email FROM users WHERE role = 'admin' ORDER BY first_name, last_name");
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Nouveau message</title>
  <link rel="stylesheet" href="../css/messages.css">
</head>
<body>
  <div class="container">
    <h1>Nouveau message</h1>
    <form action="message_send.php" method="post" class="card">
      <label>Destinataire</label>
      <select name="receiver_id" required>
        <option value="">— Sélectionner —</option>
        <?php if ($res) while($u=$res->fetch_assoc()): ?>
          <option value="<?php echo (int)$u['id']; ?>">
            <?php echo htmlspecialchars($u['first_name'].' '.$u['last_name'].' ('.$u['email'].')'); ?>
          </option>
        <?php endwhile; ?>
      </select>

      <label>Objet</label>
      <input type="text" name="subject" required>

      <label>Message</label>
      <textarea name="body" rows="8" required></textarea>

      <div class="actions">
        <a class="btn" href="messages.php">Annuler</a>
        <button class="btn primary" type="submit">Envoyer</button>
      </div>
    </form>
  </div>
</body>
</html>
