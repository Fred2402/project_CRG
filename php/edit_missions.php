<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../html/login.html");
    exit();
}
include 'db.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');

    if ($id <= 0) $errors[] = "ID invalide.";
    if ($title === '') $errors[] = "Le titre est requis.";

    if (!$errors) {
        if ($start_date === '') $start_date = null;
        if ($end_date === '')   $end_date = null;

        $stmt = $conn->prepare("UPDATE missions SET title=?, description=?, start_date=?, end_date=? WHERE id=?");
        $stmt->bind_param("ssssi", $title, $description, $start_date, $end_date, $id);
        if ($stmt->execute()) {
            header("Location: create_mission.php?updated=1");
            exit();
        } else {
            $errors[] = "Erreur SQL: " . $stmt->error;
        }
        $stmt->close();
    }
    $load_id = $id;
} else {
    $load_id = (int)($_GET['id'] ?? 0);
}
if ($load_id <= 0) { header("Location: create_mission.php?error=missing_id"); exit(); }

$stmt = $conn->prepare("SELECT id, title, description, start_date, end_date FROM missions WHERE id=?");
$stmt->bind_param("i", $load_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) { header("Location: create_mission.php?error=not_found"); exit(); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Modifier mission</title>
  <link rel="stylesheet" href="../css/register_event.css">
  <style>
    .alert.error{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:10px 12px;border-radius:10px;margin:10px 0}
    .form-actions{display:flex;gap:8px;margin-top:10px}
    .btn{padding:6px 10px;border-radius:8px;border:1px solid #e5e7eb;background:#fff;cursor:pointer;text-decoration:none}
    .btn:hover{background:#f9fafb}
  </style>
</head>
<body>
  <div class="container">
    <h1>Modifier la mission</h1>
    <?php if ($errors): ?>
      <div class="alert error"><?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?></div>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
      <label>Titre</label>
      <input type="text" name="title" value="<?php echo htmlspecialchars($row['title']); ?>" required>
      <label>Description</label>
      <textarea name="description" rows="4"><?php echo htmlspecialchars($row['description']); ?></textarea>
      <label>Début (optionnel)</label>
      <input type="date" name="start_date" value="<?php echo htmlspecialchars($row['start_date'] ?? ''); ?>">
      <label>Fin (optionnel)</label>
      <input type="date" name="end_date" value="<?php echo htmlspecialchars($row['end_date'] ?? ''); ?>">
      <div class="form-actions">
        <a class="btn" href="create_mission.php">Annuler</a>
        <button class="btn" type="submit">Enregistrer</button>
      </div>
    </form>
  </div>
</body>
</html>
