<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../html/login.html");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$flash = ['type'=>null,'msg'=>null];

/* Traiter les actions (inscription / désinscription) */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $event_id = (int)($_POST['event_id'] ?? 0);

    if ($event_id > 0) {
        if ($action === 'register') {
            // Inscrire: unique (event_id, user_id) existe en BDD
            $stmt = $conn->prepare("INSERT IGNORE INTO event_registrations (event_id, user_id) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param("ii", $event_id, $user_id);
                if ($stmt->execute()) {
                    $flash = ['type'=>'success','msg'=>'Inscription enregistrée.'];
                } else {
                    $flash = ['type'=>'error','msg'=>'Erreur SQL lors de l’inscription.'];
                }
                $stmt->close();
            } else {
                $flash = ['type'=>'error','msg'=>'Erreur prepare() lors de l’inscription.'];
            }
        } elseif ($action === 'unregister') {
            $stmt = $conn->prepare("DELETE FROM event_registrations WHERE event_id = ? AND user_id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $event_id, $user_id);
                if ($stmt->execute()) {
                    $flash = ['type'=>'success','msg'=>'Inscription annulée.'];
                } else {
                    $flash = ['type'=>'error','msg'=>'Erreur SQL lors de l’annulation.'];
                }
                $stmt->close();
            } else {
                $flash = ['type'=>'error','msg'=>'Erreur prepare() lors de l’annulation.'];
            }
        }
    } else {
        $flash = ['type'=>'error','msg'=>'Événement invalide.'];
    }
}

/* Récupérer les inscriptions de l’utilisateur */
$myRegs = [];
$sqlMy = "SELECT event_id FROM event_registrations WHERE user_id = ?";
$stmt = $conn->prepare($sqlMy);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) { $myRegs[(int)$r['event_id']] = true; }
    $stmt->close();
}

/* Récupérer la liste des événements (prochains d’abord, puis passés) */
$sqlEvents = "
    SELECT id, title, description, event_date, created_at
    FROM events
    ORDER BY
        (event_date IS NULL) ASC,      -- on montre d’abord ceux qui ont une date
        event_date ASC,                -- prochains → plus proches
        id DESC
";
$result_events = $conn->query($sqlEvents);

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Événements</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/events_missions.css">
  <style>
    /* Petits styles si besoin (ou mets-les dans dashboard.css) */
    .events-container { display: grid; gap: 12px; }
    .event-card { background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px;box-shadow:0 6px 16px rgba(0,0,0,0.05); }
    .event-header { display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px; }
    .event-title { font-weight:600; }
    .event-meta { font-size:12px;color:#6b7280; }
    .event-actions { margin-top:10px; display:flex; gap:8px; }
    .btn.sm { padding:6px 10px; border-radius:8px; border:1px solid #e5e7eb; background:#fff; text-decoration:none; }
    .btn.sm:hover { background:#f9fafb; }
    .badge-date { display:inline-block;padding:2px 8px;border-radius:999px;background:#eef2ff;border:1px solid #c7d2fe;color:#3730a3;font-size:12px; }
    .alert { padding:10px 12px;border-radius:10px;margin-bottom:12px;font-size:14px; }
    .alert.success { background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46; }
    .alert.error   { background:#fee2e2;border:1px solid #fecaca;color:#991b1b; }
    .empty { padding:16px;text-align:center;color:#6b7280;border:1px dashed #e5e7eb;border-radius:12px;background:#fff; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Événements</h1>

    <?php if ($flash['type']): ?>
      <div class="alert <?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($flash['msg']); ?>
      </div>
    <?php endif; ?>

    <div class="events-container">
      <?php if ($result_events && $result_events->num_rows > 0): ?>
        <?php while($ev = $result_events->fetch_assoc()): 
              $eid   = (int)$ev['id'];
              $title = $ev['title'] ?? '';
              $desc  = $ev['description'] ?? '';
              $date  = $ev['event_date']; // peut être NULL
              $isRegistered = isset($myRegs[$eid]);
        ?>
          <article class="event-card">
            <div class="event-header">
              <div class="event-title"><?php echo htmlspecialchars($title); ?></div>
              <div class="event-meta">
                <?php if (!empty($date)): ?>
                  <span class="badge-date"><?php echo htmlspecialchars(date('d/m/Y', strtotime($date))); ?></span>
                <?php else: ?>
                  <span class="badge-date" style="background:#f3f4f6;border-color:#e5e7eb;color:#111827;">Date à confirmer</span>
                <?php endif; ?>
              </div>
            </div>
            <div class="event-desc"><?php echo nl2br(htmlspecialchars($desc)); ?></div>

            <div class="event-actions">
              <?php if ($isRegistered): ?>
                <form method="post" onsubmit="return confirm('Annuler votre inscription ?');">
                  <input type="hidden" name="event_id" value="<?php echo $eid; ?>">
                  <input type="hidden" name="action" value="unregister">
                  <button type="submit" class="btn sm">Se désinscrire</button>
                </form>
              <?php else: ?>
                <form method="post">
                  <input type="hidden" name="event_id" value="<?php echo $eid; ?>">
                  <input type="hidden" name="action" value="register">
                  <button type="submit" class="btn sm">S’inscrire</button>
                </form>
              <?php endif; ?>
            </div>
          </article>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty">Aucun événement pour le moment.</div>
      <?php endif; ?>
    </div>

    <div>
<button type="button" class="btn sm" onclick="window.history.back();">← Retour</button>
    </div>
  </div>
</body>
</html>
