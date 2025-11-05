<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../html/login.html");
    exit();
}
include 'db.php';

$flash = ['type'=>null,'msg'=>null];
$localite_id = $_GET['localite_id'] ?? 'all';
$event_id    = (int)($_GET['event_id'] ?? 0);

/* Retrait d'une inscription */
if (isset($_GET['remove_id'])) {
    $rid = (int)$_GET['remove_id'];
    if ($rid > 0) {
        $stmt = $conn->prepare("DELETE FROM event_registrations WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $rid);
            $stmt->execute();
            $stmt->close();
            $flash = ['type'=>'success','msg'=>'Inscription retirée.'];
        } else {
            $flash = ['type'=>'error','msg'=>'Erreur prepare() retrait.'];
        }
    }
}

/* Inscriptions en masse */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $event_id = (int)($_POST['event_id'] ?? 0);
    $selected = $_POST['users'] ?? [];
    $inserted = 0; $skipped = 0;

    if ($event_id > 0 && is_array($selected) && count($selected) > 0) {
        $stmt = $conn->prepare("INSERT IGNORE INTO event_registrations (event_id, user_id) VALUES (?, ?)");
        if ($stmt) {
            foreach ($selected as $uid) {
                $uid = (int)$uid;
                $stmt->bind_param("ii", $event_id, $uid);
                if ($stmt->execute()) {
                    $inserted += ($stmt->affected_rows > 0) ? 1 : 0;
                    $skipped  += ($stmt->affected_rows === 0) ? 1 : 0;
                }
            }
            $stmt->close();
            header("Location: register_event.php?registered=1&added=".$inserted."&skipped=".$skipped."&localite_id=".urlencode($_POST['localite_id'])."&event_id=".$event_id);
            exit();
        } else {
            $flash = ['type'=>'error','msg'=>'Erreur prepare() inscription.'];
        }
    } else {
        $flash = ['type'=>'error','msg'=>'Événement ou utilisateurs invalides.'];
    }
}

/* Localités */
$locRes = $conn->query("SELECT id, name FROM localities ORDER BY name");

/* Événements */
$evRes = $conn->query("SELECT id, title FROM events ORDER BY id DESC");

/* Utilisateurs filtrés */
$users = [];
if ($localite_id === 'all') {
    $resU = $conn->query("SELECT id, first_name, last_name, email FROM users ORDER BY first_name, last_name");
} else {
    $lid = (int)$localite_id;
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email FROM users WHERE localite_id = ? ORDER BY first_name, last_name");
    $stmt->bind_param("i", $lid);
    $stmt->execute();
    $resU = $stmt->get_result();
    $stmt->close();
}
if ($resU) { while($r=$resU->fetch_assoc()) $users[] = $r; }

/* Inscriptions existantes pour l'événement choisi */
$current = [];
if ($event_id > 0) {
    $stmt = $conn->prepare("SELECT er.id, er.user_id, u.first_name, u.last_name, u.email
                            FROM event_registrations er
                            JOIN users u ON u.id = er.user_id
                            WHERE er.event_id = ?
                            ORDER BY u.first_name, u.last_name");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $resC = $stmt->get_result();
    while ($r = $resC->fetch_assoc()) $current[] = $r;
    $stmt->close();
}
$conn->close();

/* Flash via query */
if (isset($_GET['registered'])) {
    $added = (int)($_GET['added'] ?? 0);
    $skipped = (int)($_GET['skipped'] ?? 0);
    $flash = ['type'=>'success','msg'=>"Inscriptions: +$added, doublons ignorés: $skipped."];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Inscrire à un événement</title>
  <link rel="stylesheet" href="../css/register_event.css">
  <style>
    .alert{padding:10px 12px;border-radius:10px;margin:10px 0;font-size:14px;border:1px solid transparent;}
    .alert.success{background:#ecfdf5;border-color:#a7f3d0;color:#065f46;}
    .alert.error{background:#fee2e2;border-color:#fecaca;color:#991b1b;}
    .grid{display:grid;gap:12px;grid-template-columns:1fr;}
    @media(min-width:900px){.grid{grid-template-columns: 1fr 1fr;}}
    table{width:100%;border-collapse:collapse}
    th,td{border:1px solid #e5e7eb;padding:8px;text-align:left}
    .actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
    .btn{padding:6px 10px;border-radius:8px;border:1px solid #e5e7eb;background:#fff;cursor:pointer;text-decoration:none}
    .btn:hover{background:#f9fafb}
    .btn.danger{background:#fff1f2;border-color:#fecaca;color:#991b1b}
    .btn.danger:hover{background:#fee2e2}
    .muted{color:#6b7280;font-size:12px}
  </style>
</head>
<body>
  <div class="container">
    <h1>Inscrire à un événement</h1>

    <?php if ($flash['type']): ?>
      <div class="alert <?php echo $flash['type']==='success'?'success':'error'; ?>"><?php echo $flash['msg']; ?></div>
    <?php endif; ?>

    <form method="get" class="actions">
      <label>Localité
        <select name="localite_id">
          <option value="all"<?php echo ($localite_id==='all'?' selected':''); ?>>Tous</option>
          <?php if ($locRes) { while($l=$locRes->fetch_assoc()): ?>
            <option value="<?php echo (int)$l['id']; ?>"<?php echo ($localite_id==$l['id']?' selected':''); ?>>
              <?php echo htmlspecialchars($l['name']); ?>
            </option>
          <?php endwhile; } ?>
        </select>
      </label>

      <label>Événement
        <select name="event_id" required>
          <option value="0">— Choisir —</option>
          <?php if ($evRes) { while($e=$evRes->fetch_assoc()): ?>
            <option value="<?php echo (int)$e['id']; ?>"<?php echo ($event_id==$e['id']?' selected':''); ?>>
              #<?php echo (int)$e['id']; ?> — <?php echo htmlspecialchars($e['title']); ?>
            </option>
          <?php endwhile; } ?>
        </select>
      </label>

      <button class="btn" type="submit">Filtrer</button>
    </form>

    <div class="grid" style="margin-top:10px;">
      <div>
        <h2>Candidats (<?php echo $localite_id==='all'?'Toutes localités': 'Localité '.$localite_id; ?>)</h2>
        <form method="post">
          <input type="hidden" name="localite_id" value="<?php echo htmlspecialchars($localite_id); ?>">
          <input type="hidden" name="event_id" value="<?php echo (int)$event_id; ?>">

          <?php if ($event_id <= 0): ?>
            <p class="muted">Choisissez d’abord un événement.</p>
          <?php else: ?>
            <table>
              <thead><tr><th></th><th>Nom</th><th>Email</th></tr></thead>
              <tbody>
                <?php if (count($users) > 0): foreach($users as $u): ?>
                  <tr>
                    <td><input type="checkbox" name="users[]" value="<?php echo (int)$u['id']; ?>"></td>
                    <td><?php echo htmlspecialchars($u['first_name'].' '.$u['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                  </tr>
                <?php endforeach; else: ?>
                  <tr><td colspan="3">Aucun utilisateur.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
            <div class="actions">
              <button class="btn" type="submit" name="register" value="1">Inscrire</button>
            </div>
          <?php endif; ?>
        </form>
      </div>

      <div>
        <h2>Déjà inscrits à l’événement</h2>
        <table>
          <thead><tr><th>Nom</th><th>Email</th><th>Action</th></tr></thead>
          <tbody>
            <?php if (count($current) > 0): foreach($current as $r): ?>
              <tr>
                <td><?php echo htmlspecialchars($r['first_name'].' '.$r['last_name']); ?></td>
                <td><?php echo htmlspecialchars($r['email']); ?></td>
                <td>
                  <a class="btn danger" href="register_event.php?remove_id=<?php echo (int)$r['id']; ?>&localite_id=<?php echo urlencode($localite_id); ?>&event_id=<?php echo (int)$event_id; ?>" onclick="return confirm('Retirer cette inscription ?')">Retirer</a>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="3">Personne pour le moment.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="actions" style="margin-top:12px;">
      <a class="btn" href="admin_dashboard.php">← Retour admin</a>
    </div>
  </div>
</body>
</html>
