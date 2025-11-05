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
    $event_date = trim($_POST['event_date'] ?? '');

    if ($id <= 0) $errors[] = "ID invalide.";
    if ($title === '') $errors[] = "Le titre est requis.";

    if (!$errors) {
        if ($event_date === '') $event_date = null;

        $stmt = $conn->prepare("UPDATE events SET title=?, description=?, event_date=? WHERE id=?");
        $stmt->bind_param("sssi", $title, $description, $event_date, $id);
        if ($stmt->execute()) {
            header("Location: create_event.php?updated=1");
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
if ($load_id <= 0) { header("Location: create_event.php?error=missing_id"); exit(); }

$stmt = $conn->prepare("SELECT id, title, description, event_date FROM events WHERE id=?");
$stmt->bind_param("i", $load_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) { header("Location: create_event.php?error=not_found"); exit(); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Modifier événement</title>
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
    <h1>Modifier l’événement</h1>
    <?php if ($errors): ?>
      <div class="alert error"><?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?></div>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
      <label>Titre</label>
      <input type="text" name="title" value="<?php echo htmlspecialchars($row['title']); ?>" required>
      <label>Description</label>
      <textarea name="description" rows="4"><?php echo htmlspecialchars($row['description']); ?></textarea>
      <label>Date (optionnel)</label>
      <input type="date" name="event_date" value="<?php echo htmlspecialchars($row['event_date'] ?? ''); ?>">
      <div class="form-actions">
        <a class="btn" href="create_event.php">Annuler</a>
        <button class="btn" type="submit">Enregistrer</button>
      </div>
    </form>
  </div>
</body>
</html>
