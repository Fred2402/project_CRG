<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../html/login.html");
    exit();
}
include 'db.php';

$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM training WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: admin_add_training.php?deleted=1");
            exit();
        } else {
            $error = "Erreur SQL: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "ID invalide.";
    }
} else {
    if ($id <= 0) {
        header("Location: admin_add_training.php?error=missing_id");
        exit();
    }
    $stmt = $conn->prepare("SELECT training_name, niveau FROM training WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if (!$row) {
        header("Location: admin_add_training.php?error=not_found");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Supprimer formation</title>
  <link rel="stylesheet" href="../css/trainings_admin.css">
</head>
<body>
  <h1>Supprimer la formation</h1>
  <?php if (!empty($error)): ?>
    <div style="color:#b91c1c;background:#fee2e2;padding:8px;border-radius:8px;margin-bottom:12px;">
      <?php echo htmlspecialchars($error); ?>
    </div>
  <?php endif; ?>
  <form method="post">
    <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
    <p>Confirmer la suppression de <strong><?php echo htmlspecialchars($row['training_name'] ?? ''); ?></strong> (niveau <?php echo htmlspecialchars($row['niveau'] ?? ''); ?>) ?</p>
    <div style="margin-top:12px">
      <a href="admin_add_training.php">Annuler</a>
      <button type="submit">Supprimer</button>
    </div>
  </form>
</body>
</html>
