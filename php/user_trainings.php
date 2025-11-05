<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../html/login.html");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$sql = "SELECT training_name, niveau, description, date_completed
        FROM training
        WHERE user_id = ?
        ORDER BY (date_completed IS NULL) ASC, date_completed DESC, training_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($r = $result->fetch_assoc()) $rows[] = $r;

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Mes formations</title>
  <link rel="stylesheet" href="../css/user_trainings.css">
</head>
<body>
<div class="container">
  <h1>Mes formations</h1>

  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Nom</th><th>Niveau</th><th>Description</th><th>Statut</th><th>Date</th></tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="5">Aucune formation.</td></tr>
      <?php else: foreach ($rows as $t): ?>
        <tr>
          <td><?php echo htmlspecialchars($t['training_name']); ?></td>
          <td><?php echo htmlspecialchars($t['niveau']); ?></td>
          <td><?php echo htmlspecialchars($t['description'] ?? ''); ?></td>
          <td>
            <?php if (!empty($t['date_completed'])): ?>
              <span class="badge success">Acquis</span>
            <?php else: ?>
              <span class="badge muted">Non acquis</span>
            <?php endif; ?>
          </td>
          <td><?php echo !empty($t['date_completed']) ? htmlspecialchars($t['date_completed']) : '—'; ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
