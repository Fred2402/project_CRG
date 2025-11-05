<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../html/login.html");
    exit();
}

/* Users pour le select */
$sqlUsers = "SELECT id, first_name, last_name, email
             FROM users
             ORDER BY first_name, last_name";
$resultUsers = $conn->query($sqlUsers);

/* Liste des formations */
$sqlList = "SELECT t.id, t.training_name, t.niveau, t.description, t.date_completed, t.user_id,
                   CONCAT(u.first_name, ' ', u.last_name) AS user_fullname, u.email
            FROM training t
            LEFT JOIN users u ON u.id = t.user_id
            ORDER BY (t.date_completed IS NULL) DESC, t.training_name ASC, t.id DESC";
$resultList = $conn->query($sqlList);

/* Flash messages */
$flash = [];
if (!empty($_GET['created']))   $flash[] = ['type'=>'success','msg'=>'Formation créée.'];
if (!empty($_GET['updated']))   $flash[] = ['type'=>'success','msg'=>'Formation mise à jour.'];
if (!empty($_GET['deleted']))   $flash[] = ['type'=>'success','msg'=>'Formation supprimée.'];
if (!empty($_GET['validated'])) $flash[] = ['type'=>'success','msg'=>'Statut mis à jour.'];
if (!empty($_GET['error']))     $flash[] = ['type'=>'error','msg'=>'Erreur : '.htmlspecialchars($_GET['error'])];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gestion des Formations - Administrateur</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/trainings_admin.css">
</head>
<body>
<div class="container">
  <h1>Gestion des Formations</h1>

  <?php if ($flash): foreach ($flash as $f): ?>
    <div class="alert <?php echo $f['type']==='success' ? 'success' : 'error'; ?>">
      <?php echo $f['msg']; ?>
    </div>
  <?php endforeach; endif; ?>

  <!-- Formulaire d'ajout -->
  <form action="save_training.php" method="POST" style="margin-bottom:16px;">
    <label for="user_id">Utilisateur</label>
    <select id="user_id" name="user_id" required>
      <option value="">-- Sélectionner --</option>
      <?php if ($resultUsers && $resultUsers->num_rows > 0): while($row = $resultUsers->fetch_assoc()): ?>
        <option value="<?php echo (int)$row['id']; ?>">
          <?php echo htmlspecialchars($row['first_name'].' '.$row['last_name'].' ('.$row['email'].')'); ?>
        </option>
      <?php endwhile; endif; ?>
    </select>

    <label for="training_name">Nom de la formation</label>
    <input type="text" id="training_name" name="training_name" required>

    <label for="niveau">Niveau</label>
    <input type="text" id="niveau" name="niveau" required>

    <label for="description">Description (optionnel)</label>
    <textarea id="description" name="description" rows="3"></textarea>

    <label for="status">Statut</label>
    <select id="status" name="status" required>
      <option value="non">Non acquis</option>
      <option value="acquis">Acquis</option>
    </select>

    <label for="date_completed">Date d’acquisition (si Acquis)</label>
    <input type="date" id="date_completed" name="date_completed">

    <div class="form-actions">
      <button type="submit" class="btn">Créer</button>
    </div>
  </form>

  <h2>Liste des formations</h2>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Utilisateur</th>
          <th>Nom</th>
          <th>Niveau</th>
          <th>Description</th>
          <th>Statut</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($resultList && $resultList->num_rows > 0): while($r = $resultList->fetch_assoc()): ?>
        <tr>
          <td>
            <?php echo htmlspecialchars($r['user_fullname'] ?: '—'); ?>
            <small style="color:#6b7280;display:block;"><?php echo htmlspecialchars($r['email'] ?? ''); ?></small>
          </td>
          <td><?php echo htmlspecialchars($r['training_name']); ?></td>
          <td><?php echo htmlspecialchars($r['niveau']); ?></td>
          <td><?php echo htmlspecialchars($r['description'] ?? ''); ?></td>
          <td>
            <?php if (!empty($r['date_completed'])): ?>
              <span class="badge success">Acquis</span>
            <?php else: ?>
              <span class="badge muted">Non acquis</span>
            <?php endif; ?>
          </td>
          <td><?php echo !empty($r['date_completed']) ? htmlspecialchars($r['date_completed']) : '—'; ?></td>
          <td class="actions">
            <a href="edit_training.php?id=<?php echo (int)$r['id']; ?>">Modifier</a>
            <a class="btn danger" href="delete_training.php?id=<?php echo (int)$r['id']; ?>" onclick="return confirm('Supprimer cette formation ?')">Supprimer</a>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="7">Aucune formation.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
