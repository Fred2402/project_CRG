<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../html/login.html");
    exit();
}
include 'db.php';

$flash = null;

/* Création événement */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');

    if ($title === '') {
        $flash = ['type'=>'error','msg'=>'Le titre est requis.'];
    } else {
        if ($event_date === '') $event_date = null;

        $stmt = $conn->prepare("INSERT INTO events (title, description, event_date) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $title, $description, $event_date);
            if ($stmt->execute()) {
                $flash = ['type'=>'success','msg'=>'Événement créé.'];
            } else {
                $flash = ['type'=>'error','msg'=>'Erreur SQL à la création.'];
            }
            $stmt->close();
        } else {
            $flash = ['type'=>'error','msg'=>'Erreur prepare() à la création.'];
        }
    }
}

/* Liste événements */
$res = $conn->query("SELECT id, title, event_date FROM events ORDER BY id DESC");
$conn->close();

/* Flash via query */
if (isset($_GET['deleted'])) $flash = ['type'=>'success','msg'=>'Événement supprimé.'];
if (isset($_GET['updated'])) $flash = ['type'=>'success','msg'=>'Événement mis à jour.'];
if (isset($_GET['error']))   $flash = ['type'=>'error','msg'=>'Erreur: '.htmlspecialchars($_GET['error'])];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Créer un événement</title>
  <link rel="stylesheet" href="../css/register_event.css">
  <style>
    .alert{padding:10px 12px;border-radius:10px;margin:10px 0;font-size:14px;border:1px solid transparent;}
    .alert.success{background:#ecfdf5;border-color:#a7f3d0;color:#065f46;}
    .alert.error{background:#fee2e2;border-color:#fecaca;color:#991b1b;}
    table{width:100%;border-collapse:collapse}
    th,td{border:1px solid #e5e7eb;padding:8px;text-align:left}
    .actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
    .btn{padding:6px 10px;border-radius:8px;border:1px solid #e5e7eb;background:#fff;cursor:pointer;text-decoration:none}
    .btn:hover{background:#f9fafb}
    .btn.danger{background:#fff1f2;border-color:#fecaca;color:#991b1b}
    .btn.danger:hover{background:#fee2e2}
  </style>
</head>
<body>
  <div class="container">
    <h1>Créer un événement</h1>

    <?php if ($flash): ?>
      <div class="alert <?php echo $flash['type']==='success'?'success':'error'; ?>"><?php echo $flash['msg']; ?></div>
    <?php endif; ?>

    <form method="post">
      <label>Titre</label>
      <input type="text" name="title" required>
      <label>Description</label>
      <textarea name="description" rows="4"></textarea>
      <label>Date (optionnel)</label>
      <input type="date" name="event_date">
      <div class="actions">
        <button class="btn" type="submit">Créer</button>
        <a class="btn" href="admin_dashboard.php">← Retour admin</a>
      </div>
    </form>

    <h2 style="margin-top:16px;">Événements existants</h2>
    <table>
      <thead><tr><th>#</th><th>Titre</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if ($res && $res->num_rows>0): while($e=$res->fetch_assoc()): ?>
          <tr>
            <td><?php echo (int)$e['id']; ?></td>
            <td><?php echo htmlspecialchars($e['title']); ?></td>
            <td><?php echo $e['event_date'] ? htmlspecialchars(date('d/m/Y', strtotime($e['event_date']))) : '—'; ?></td>
            <td class="actions">
              <a class="btn" href="edit_event.php?id=<?php echo (int)$e['id']; ?>">Modifier</a>
              <a class="btn danger" href="delete_event.php?id=<?php echo (int)$e['id']; ?>" onclick="return confirm('Supprimer cet événement ?')">Supprimer</a>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="4">Aucun événement.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
