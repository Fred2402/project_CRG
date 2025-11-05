<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../html/login.html");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$flash = ['type'=>null,'msg'=>null];

/* Actions: s'inscrire / se retirer d'une mission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $mission_id = (int)($_POST['mission_id'] ?? 0);

    if ($mission_id > 0) {
        if ($action === 'assign') {
            // Unicité (mission_id,user_id) assurée par UNIQUE en BDD
            $stmt = $conn->prepare("INSERT IGNORE INTO mission_assignments (mission_id, user_id) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param("ii", $mission_id, $user_id);
                if ($stmt->execute()) {
                    $flash = ['type'=>'success','msg'=>'Inscription à la mission enregistrée.'];
                } else {
                    $flash = ['type'=>'error','msg'=>"Erreur SQL lors de l'inscription."];
                }
                $stmt->close();
            } else {
                $flash = ['type'=>'error','msg'=>"Erreur prepare() lors de l'inscription."];
            }
        } elseif ($action === 'unassign') {
            $stmt = $conn->prepare("DELETE FROM mission_assignments WHERE mission_id = ? AND user_id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $mission_id, $user_id);
                if ($stmt->execute()) {
                    $flash = ['type'=>'success','msg'=>'Vous vous êtes retiré de la mission.'];
                } else {
                    $flash = ['type'=>'error','msg'=>'Erreur SQL lors du retrait.'];
                }
                $stmt->close();
            } else {
                $flash = ['type'=>'error','msg'=>'Erreur prepare() lors du retrait.'];
            }
        }
    } else {
        $flash = ['type'=>'error','msg'=>'Mission invalide.'];
    }
}

/* Mes missions (pour savoir si je suis inscrit) */
$myAssign = [];
$sqlMy = "SELECT mission_id FROM mission_assignments WHERE user_id = ?";
$stmt = $conn->prepare($sqlMy);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) { $myAssign[(int)$r['mission_id']] = true; }
    $stmt->close();
}

/* Liste des missions (celles avec dates d'abord, les plus proches en premier) */
$sqlMissions = "
    SELECT id, title, description, start_date, end_date, created_at
    FROM missions
    ORDER BY
        (start_date IS NULL) ASC,
        start_date ASC,
        id DESC
";
$result_missions = $conn->query($sqlMissions);

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Missions</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/events_missions.css">
</head>
<body>
  <div class="container">
    <h1>Missions</h1>

    <?php if ($flash['type']): ?>
      <div class="alert <?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($flash['msg']); ?>
      </div>
    <?php endif; ?>

    <div class="cards-container">
      <?php if ($result_missions && $result_missions->num_rows > 0): ?>
        <?php while ($m = $result_missions->fetch_assoc()):
              $mid   = (int)$m['id'];
              $title = $m['title'] ?? '';
              $desc  = $m['description'] ?? '';
              $sd    = $m['start_date'];
              $ed    = $m['end_date'];
              $isAssigned = isset($myAssign[$mid]);
        ?>
          <article class="item-card">
            <div class="item-header">
              <div class="item-title"><?php echo htmlspecialchars($title); ?></div>
              <div class="item-meta">
                <?php if (!empty($sd)): ?>
                  <span class="badge-date"><?php echo htmlspecialchars(date('d/m/Y', strtotime($sd))); ?></span>
                  <?php if (!empty($ed)): ?>
                    <span class="badge-date sep">→</span>
                    <span class="badge-date"><?php echo htmlspecialchars(date('d/m/Y', strtotime($ed))); ?></span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="badge-date muted">Dates à confirmer</span>
                <?php endif; ?>
              </div>
            </div>

            <div class="item-desc"><?php echo nl2br(htmlspecialchars($desc)); ?></div>

            <div class="item-actions">
              <?php if ($isAssigned): ?>
                <form method="post" onsubmit="return confirm('Se retirer de cette mission ?');">
                  <input type="hidden" name="mission_id" value="<?php echo $mid; ?>">
                  <input type="hidden" name="action" value="unassign">
                  <button type="submit" class="btn sm">Se retirer</button>
                </form>
              <?php else: ?>
                <form method="post">
                  <input type="hidden" name="mission_id" value="<?php echo $mid; ?>">
                  <input type="hidden" name="action" value="assign">
                  <button type="submit" class="btn sm">S’inscrire</button>
                </form>
              <?php endif; ?>
            </div>
          </article>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty">Aucune mission disponible pour le moment.</div>
      <?php endif; ?>
    </div>

    <div style="margin-top:16px;">
      <a href="dashboard.php" class="btn sm">← Retour au tableau de bord</a>
    </div>
  </div>
</body>
</html>
