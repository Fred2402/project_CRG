<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../html/login.html"); exit(); }

$user_id = (int)$_SESSION['user_id'];
$tab = ($_GET['tab'] ?? 'inbox') === 'sent' ? 'sent' : 'inbox';
$q = trim($_GET['q'] ?? '');

$flash = null;
if (isset($_GET['sent']))   $flash = ['type'=>'success','msg'=>'Message envoyé.'];
if (isset($_GET['deleted']))$flash = ['type'=>'success','msg'=>'Message supprimé.'];

$params = [];
if ($tab === 'inbox') {
    $sql = "SELECT m.id, m.subject, m.body, m.is_read, m.created_at,
                   u.first_name AS from_first, u.last_name AS from_last, u.email AS from_email
            FROM messages m
            JOIN users u ON u.id = m.sender_id
            WHERE m.receiver_id = ? AND m.deleted_by_receiver = 0";
    $params[] = $user_id;
} else {
    $sql = "SELECT m.id, m.subject, m.body, m.is_read, m.created_at,
                   u.first_name AS to_first, u.last_name AS to_last, u.email AS to_email
            FROM messages m
            JOIN users u ON u.id = m.receiver_id
            WHERE m.sender_id = ? AND m.deleted_by_sender = 0";
    $params[] = $user_id;
}

if ($q !== '') {
    $sql .= " AND (m.subject LIKE CONCAT('%', ?, '%') OR m.body LIKE CONCAT('%', ?, '%'))";
    $params[] = $q; $params[] = $q;
}
$sql .= " ORDER BY m.created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) { die("SQL messages: ".$conn->error); }
$types = str_repeat('s', count($params)); // ajuster types
// corriger types (on sait que le 1er est int ; les éventuels q sont string)
if ($tab === 'inbox' || $tab === 'sent') {
    if ($q === '') { $types = 'i'; }
    else { $types = 'iss'; }
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Messagerie</title>
  <link rel="stylesheet" href="../css/messages.css">
</head>
<body>
  <div class="container">
    <h1>Messagerie</h1>

    <div class="toolbar">
      <div class="tabs">
        <a class="tab<?php echo $tab==='inbox'?' active':''; ?>" href="messages.php?tab=inbox">📥 Boîte de réception</a>
        <a class="btn" href="messages_mark_all_read.php" onclick="return confirm('Marquer tous les messages comme lus ?');">Tout marquer comme lu</a>
        <a class="tab<?php echo $tab==='sent'?' active':''; ?>" href="messages.php?tab=sent">📤 Envoyés</a>
      </div>
      <div class="actions">
        <a class="btn primary" href="message_compose.php">✉️ Nouveau message</a>
        <form class="search" method="get">
          <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
          <input type="text" name="q" placeholder="Rechercher..." value="<?php echo htmlspecialchars($q); ?>">
          <button class="btn" type="submit">Rechercher</button>
        </form>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="alert <?php echo $flash['type']; ?>"><?php echo $flash['msg']; ?></div>
    <?php endif; ?>

    <div class="list">
      <?php if ($res && $res->num_rows > 0): ?>
        <?php while ($m = $res->fetch_assoc()): ?>
          <a class="row<?php echo ($tab==='inbox' && !$m['is_read']) ? ' unread':''; ?>" href="message_view.php?id=<?php echo (int)$m['id']; ?>&tab=<?php echo htmlspecialchars($tab); ?>">
            <div class="who">
              <?php if ($tab==='inbox'): ?>
                <div class="name"><?php echo htmlspecialchars(($m['from_first']??'').' '.($m['from_last']??'')); ?></div>
                <div class="email"><?php echo htmlspecialchars($m['from_email'] ?? ''); ?></div>
              <?php else: ?>
                <div class="name"><?php echo htmlspecialchars(($m['to_first']??'').' '.($m['to_last']??'')); ?></div>
                <div class="email"><?php echo htmlspecialchars($m['to_email'] ?? ''); ?></div>
              <?php endif; ?>
            </div>
            <div class="subject">
              <div class="subj"><?php echo htmlspecialchars($m['subject']); ?></div>
              <div class="snippet"><?php echo htmlspecialchars(mb_strimwidth(strip_tags($m['body']), 0, 80, '…')); ?></div>
            </div>
            <div class="date"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($m['created_at']))); ?></div>
          </a>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty">Aucun message.</div>
      <?php endif; ?>
    </div>

 <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
  <?php
    // 1️⃣ Bouton "Retour" : vers la page précédente
    $previous = $_SERVER['HTTP_REFERER'] ?? '#';
    echo '<a class="btn" href="'.htmlspecialchars($previous).'">← Retour</a>';

    // 2️⃣ Bouton "Accueil" : dépend du rôle de la session
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] === 'admin') {
            echo '<a class="btn" href="admin_dashboard.php">🏠 Accueil Admin</a>';
        } else {
            echo '<a class="btn" href="dashboard.php">🏠 Accueil Utilisateur</a>';
        }
    } else {
        // Si jamais la session expire, on redirige vers la page d'accueil publique
        echo '<a class="btn" href="../html/login.html">🏠 Accueil</a>';
    }
  ?>
</div>


  </div>
</body>
</html>
