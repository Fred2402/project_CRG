<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../html/login.html");
    exit();
}
include 'db.php';

$flash = null;

/* Création mission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date   = trim($_POST['end_date'] ?? '');

    if ($title === '') {
        $flash = ['type'=>'error','msg'=>'Le titre est requis.'];
    } else {
        if ($start_date === '') $start_date = null;
        if ($end_date === '')   $end_date = null;

        $stmt = $conn->prepare("INSERT INTO missions (title, description, start_date, end_date) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $title, $description, $start_date, $end_date);
            if ($stmt->execute()) {
                $flash = ['type'=>'success','msg'=>'Mission créée.'];
            } else {
                $flash = ['type'=>'error','msg'=>'Erreur SQL à la création.'];
            }
            $stmt->close();
        } else {
            $flash = ['type'=>'error','msg'=>'Erreur prepare() à la création.'];
        }
    }
}

/* Liste missions */
$res = $conn->query("SELECT id, title, start_date, end_date FROM missions ORDER BY id DESC");
$conn->close();

/* Flash depuis query */
if (isset($_GET['deleted'])) $flash = ['type'=>'success','msg'=>'Mission supprimée.'];
if (isset($_GET['updated'])) $flash = ['type'=>'success','msg'=>'Mission mise à jour.'];
if (isset($_GET['error']))   $flash = ['type'=>'error','msg'=>'Erreur: '.htmlspecialchars($_GET['error'])];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Créer une mission</title>
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
    <h1>Créer une mission</h1>

    <?php if ($flash): ?>
      <div class="alert <?php echo $flash['type']==='success'?'success':'error'; ?>"><?php echo $flash['msg']; ?></div>
    <?php endif; ?>

    <form method="post">
      <label>Titre</label>
      <input type="text" name="title" required>
      <label>Description</label>
      <textarea name="description" rows="4"></textarea>
      <div class="actions">
        <div>
          <label>Début</label>
          <input type="date" name="start_date">
        </div>
        <div>
          <label>Fin</label>
          <input type="date" name="end_date">
        </div>
      </div>
      <div class="actions">
        <button class="btn" type="submit">Créer</button>
        <a class="btn" href="admin_dashboard.php">← Retour admin</a>
      </div>
    </form>

    <h2 style="margin-top:16px;">Missions existantes</h2>
    <table>
      <thead><tr><th>#</th><th>Titre</th><th>Dates</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if ($res && $res->num_rows>0): while($m=$res->fetch_assoc()): ?>
          <tr>
            <td><?php echo (int)$m['id']; ?></td>
            <td><?php echo htmlspecialchars($m['title']); ?></td>
            <td>
              <?php
                $sd = $m['start_date'] ? date('d/m/Y', strtotime($m['start_date'])) : '—';
                $ed = $m['end_date'] ? date('d/m/Y', strtotime($m['end_date'])) : '—';
                echo $sd.' → '.$ed;
              ?>
            </td>
            <td class="actions">
              <a class="btn" href="edit_mission.php?id=<?php echo (int)$m['id']; ?>">Modifier</a>
              <a class="btn danger" href="delete_mission.php?id=<?php echo (int)$m['id']; ?>" onclick="return confirm('Supprimer cette mission ?')">Supprimer</a>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="4">Aucune mission.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
