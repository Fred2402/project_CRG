<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../html/login.html");
    exit();
}
include 'db.php';

$training_id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['id'] ?? 0);
if ($training_id <= 0) {
    header("Location: admin_add_training.php?error=missing_id");
    exit();
}

$sqlLoad = "SELECT t.id, t.user_id, t.training_name, t.niveau, t.description, t.date_completed,
                   u.first_name, u.last_name, u.email
            FROM training t
            LEFT JOIN users u ON u.id = t.user_id
            WHERE t.id = ?";
$stmt = $conn->prepare($sqlLoad);
if (!$stmt) die("Erreur SQL (load): ".$conn->error);
$stmt->bind_param("i", $training_id);
$stmt->execute();
$training = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$training) {
    header("Location: admin_add_training.php?error=not_found");
    exit();
}

$errors = [];
$notice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $training_name = trim($_POST['training_name'] ?? '');
    $niveau        = trim($_POST['niveau'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $status        = ($_POST['status'] ?? 'non') === 'acquis' ? 'acquis' : 'non';
    $date_input    = trim($_POST['date_completed'] ?? '');

    if ($training_name === '') $errors[] = "Le nom est requis.";
    if ($niveau === '')        $errors[] = "Le niveau est requis.";

    if (!$errors) {
        if ($status === 'acquis') {
            if ($date_input === '') {
                $sqlU = "UPDATE training SET training_name=?, niveau=?, description=?, date_completed=CURDATE() WHERE id=?";
                $stU  = $conn->prepare($sqlU);
                $stU->bind_param("sssi", $training_name, $niveau, $description, $training_id);
            } else {
                $sqlU = "UPDATE training SET training_name=?, niveau=?, description=?, date_completed=? WHERE id=?";
                $stU  = $conn->prepare($sqlU);
                $stU->bind_param("ssssi", $training_name, $niveau, $description, $date_input, $training_id);
            }
        } else {
            $sqlU = "UPDATE training SET training_name=?, niveau=?, description=?, date_completed=NULL WHERE id=?";
            $stU  = $conn->prepare($sqlU);
            $stU->bind_param("sssi", $training_name, $niveau, $description, $training_id);
        }

        if ($stU && $stU->execute()) {
            $notice = "Formation mise à jour.";
        } else {
            $errors[] = "Erreur SQL: ".$conn->error;
        }
        if ($stU) $stU->close();

        // Recharger
        $stmt = $conn->prepare($sqlLoad);
        $stmt->bind_param("i", $training_id);
        $stmt->execute();
        $training = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$conn->close();

$isAcquired = !empty($training['date_completed']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Modifier formation #<?php echo (int)$training['id']; ?></title>
  <link rel="stylesheet" href="../css/trainings_admin.css">
</head>
<body>
<div class="container">
  <h1>Modifier la formation #<?php echo (int)$training['id']; ?></h1>
  <p><strong>Volontaire :</strong>
    <?php echo htmlspecialchars(($training['first_name'] ?? '').' '.($training['last_name'] ?? '')); ?>
    (<?php echo htmlspecialchars($training['email'] ?? ''); ?>)
  </p>

  <?php if (!empty($notice)): ?>
    <div class="alert success"><?php echo htmlspecialchars($notice); ?></div>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
    <div class="alert error"><?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?></div>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="id" value="<?php echo (int)$training['id']; ?>">

    <label>Nom</label>
    <input type="text" name="training_name" value="<?php echo htmlspecialchars($training['training_name']); ?>" required>

    <label>Niveau</label>
    <input type="text" name="niveau" value="<?php echo htmlspecialchars($training['niveau']); ?>" required>

    <label>Description</label>
    <textarea name="description" rows="3"><?php echo htmlspecialchars($training['description'] ?? ''); ?></textarea>

    <label>Statut</label>
    <select name="status" required>
      <option value="non"    <?php echo !$isAcquired ? 'selected' : ''; ?>>Non acquis</option>
      <option value="acquis" <?php echo  $isAcquired ? 'selected' : ''; ?>>Acquis</option>
    </select>

    <label>Date d’acquisition (si Acquis)</label>
    <input type="date" name="date_completed" value="<?php echo htmlspecialchars($training['date_completed'] ?? ''); ?>">

    <div class="actions" style="margin-top:12px">
      <button type="submit" class="btn">Enregistrer</button>
      <a class="btn secondary" href="admin_add_training.php">← Retour</a>
      <a class="btn danger" href="delete_training.php?id=<?php echo (int)$training['id']; ?>" onclick="return confirm('Supprimer cette formation ?')">Supprimer</a>
    </div>
  </form>

  <div class="card" style="margin-top:16px;">
    <p><strong>Statut actuel :</strong>
      <?php if ($isAcquired): ?>
        <span class="badge success">Acquis</span> le <?php echo htmlspecialchars($training['date_completed']); ?>
      <?php else: ?>
        <span class="badge muted">Non acquis</span>
      <?php endif; ?>
    </p>
  </div>
</div>
</body>
</html>
