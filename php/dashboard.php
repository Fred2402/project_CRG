<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../html/login.html");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

/* 1) Infos utilisateur (+ localite_id pour filtrer les notifications + photo_path) */
$sqlUser = "SELECT first_name, last_name, email, phone, status_volontaire, localite_id, photo_path
            FROM users WHERE id = ?";
$stmt = $conn->prepare($sqlUser);
if (!$stmt) die("SQL user: " . $conn->error);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) { echo "Utilisateur non trouvé."; exit(); }

/* Photo de profil (fallback) */
$photo = '../images/default_avatar.png';
if (!empty($user['photo_path'])) {
    $photo = '../' . ltrim($user['photo_path'], '/');
}

/* 2) Mes formations (training.user_id) */
$sqlTrainings = "SELECT COUNT(*) AS total FROM training WHERE user_id = ?";
$stmt = $conn->prepare($sqlTrainings);
if (!$stmt) die("SQL trainings: " . $conn->error);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_trainings = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

/* 3) Missions assignées (mission_assignments.user_id) */
$sqlMissions = "SELECT COUNT(*) AS total FROM mission_assignments WHERE user_id = ?";
$stmt = $conn->prepare($sqlMissions);
if (!$stmt) die("SQL missions: " . $conn->error);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_missions = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

/* 4) Événements inscrits (event_registrations.user_id) */
$sqlEvents = "SELECT COUNT(*) AS total FROM event_registrations WHERE user_id = ?";
$stmt = $conn->prepare($sqlEvents);
if (!$stmt) die("SQL events: " . $conn->error);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_events = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

/* 5) Notifications : par localité OU globales (NULL) + nom localité */
$sqlNotifs = "SELECT n.message, n.date, n.localite_id, l.name AS localite_name
              FROM notifications n
              LEFT JOIN localities l ON l.id = n.localite_id
              WHERE (n.localite_id = ? OR n.localite_id IS NULL)
              ORDER BY n.date DESC
              LIMIT 10";
$stmt = $conn->prepare($sqlNotifs);
if (!$stmt) die("SQL notifications: " . $conn->error);
$stmt->bind_param("i", $user['localite_id']);
$stmt->execute();
$result_notifications = $stmt->get_result();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Tableau de Bord</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
      /* Mini styles complémentaires */
      .topbar-messages{ position:relative; display:inline-flex; align-items:center; gap:8px; margin:6px 0 10px; font-size:18px; }
      .msg-link{ position:relative; display:inline-flex; align-items:center; gap:6px; text-decoration:none; color:#111827; font-weight:600; }
      .msg-link:hover{ opacity:.9; }
      .msg-badge{ position:absolute; top:-6px; right:-10px; min-width:20px; height:20px; padding:0 6px; display:inline-flex; align-items:center; justify-content:center; background:#d2232a; color:#fff; border-radius:999px; font-size:12px; line-height:1; border:2px solid #fff; }

      .profile-info{ display:flex; align-items:center; gap:12px; }
      .avatar{ width:64px; height:64px; border-radius:50%; object-fit:cover; border:2px solid #e5e7eb; }
      .section-header{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:8px; }
      .notif-list{ list-style:none; padding:0; margin:0; display:grid; gap:10px; }
      .notif.card{ padding:12px 14px; border-radius:12px; background:#fff; border:1px solid #e5e7eb; box-shadow:0 8px 20px rgba(0,0,0,0.06); }
      .notif-header{ display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; }
      .notif-date{ font-size:12px; color:#6b7280; }
      .notif-message{ font-size:14px; line-height:1.5; color:#1f2937; white-space:pre-wrap; }
      .chip{ display:inline-block; padding:2px 8px; border-radius:999px; font-size:12px; border:1px solid #e5e7eb; background:#f3f4f6; color:#111827; }
      .chip-all{ background:#eef2ff; border-color:#c7d2fe; color:#3730a3; }
      .btn.link-btn{ display:inline-flex; align-items:center; gap:6px; padding:8px 12px; border-radius:10px; text-decoration:none; border:1px solid #e5e7eb; background:#ffffff; color:#111827; font-weight:500; }
      .btn.link-btn:hover{ background:#f9fafb; }
    </style>
</head>
<body>
    <div class="container">

        <!-- Cloche messages (badge non lus) -->
        <div class="topbar-messages">
          <a href="messages.php?tab=inbox" class="msg-link" title="Boîte de réception">
            🔔
            <span id="msg-badge" class="msg-badge" hidden>0</span>
          </a>
        </div>

        <!-- Profil -->
        <section class="welcome-profile card">
            <div class="profile-info">
                <img src="<?php echo htmlspecialchars($photo); ?>" alt="Photo" class="avatar">
                <div class="user-details">
                    <h1>Bienvenue, <?php echo htmlspecialchars($user['first_name'].' '.$user['last_name']); ?> !</h1>
                    <p><strong>Email :</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                    <p><strong>Statut :</strong> <?php echo htmlspecialchars($user['status_volontaire']); ?></p>
                    <a href="profile.php" class="btn primary">Gérer le profil</a>
                </div>
            </div>
        </section>

        <!-- Statistiques -->
        <section class="statistics">
            <h2>Statistiques</h2>
            <div class="stat-grid">
                <div class="stat-item card">
                    <h3>Missions assignées</h3>
                    <p class="stat-number"><?php echo (int)$my_missions; ?></p>
                </div>
                <div class="stat-item card">
                    <h3>Événements inscrits</h3>
                    <p class="stat-number"><?php echo (int)$my_events; ?></p>
                </div>
                <div class="stat-item card">
                    <h3>Formations suivies</h3>
                    <p class="stat-number"><?php echo (int)$my_trainings; ?></p>
                </div>
            </div>
        </section>

        <!-- Notifications -->
        <section class="notifications">
            <div class="section-header">
                <h2>Notifications</h2>
                <a href="events.php" class="btn link-btn">Consulter les événements</a>
            </div>
            <ul class="notif-list">
                <?php if ($result_notifications && $result_notifications->num_rows > 0): ?>
                    <?php while($row = $result_notifications->fetch_assoc()): ?>
                        <li class="notif card">
                            <div class="notif-header">
                                <?php if ($row['localite_id'] === null): ?>
                                  <span class="chip chip-all">Tous</span>
                                <?php else: ?>
                                  <span class="chip"><?php echo htmlspecialchars($row['localite_name'] ?? 'Localité'); ?></span>
                                <?php endif; ?>
                                <time class="notif-date"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($row['date']))); ?></time>
                            </div>
                            <div class="notif-message"><?php echo nl2br(htmlspecialchars($row['message'])); ?></div>
                        </li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li class="notif card"><div class="notif-message">Aucune notification pour le moment.</div></li>
                <?php endif; ?>
            </ul>
        </section>

        <!-- Accès rapide -->
        <section class="quick-access">
            <h2>Accès rapide</h2>
            <div class="access-grid">
                <a href="events.php" class="access-item card link">Événements</a>
                <a href="missions.php" class="access-item card link">Missions</a>
                <a href="user_trainings.php" class="access-item card link">Formations</a>
                <a href="profile.php" class="access-item card link">Profil</a>
                <a href="messages.php" class="access-item card link">Messages</a>
            </div>
        </section>
    </div>

    <!-- Polling AJAX du badge messages non lus -->
    <script>
    (function(){
      const BADGE_ID = 'msg-badge';
      const ENDPOINT = 'messages_unread_count.php'; // même dossier (php/)
      const INTERVAL_MS = 30000; // 30s

      const badge = document.getElementById(BADGE_ID);
      if (!badge) return;

      async function refreshBadge(){
        try {
          const res = await fetch(ENDPOINT, { credentials: 'same-origin', cache: 'no-store' });
          if (!res.ok) throw new Error('HTTP '+res.status);
          const data = await res.json();

          const count = (data && typeof data.count === 'number') ? data.count : 0;
          if (count > 0) {
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.hidden = false;
            document.title = '('+badge.textContent+') ' + document.title.replace(/^\(\d+\+\)\s|\(\d+\)\s/, '');
          } else {
            badge.hidden = true;
            document.title = document.title.replace(/^\(\d+\+\)\s|\(\d+\)\s/, '');
          }
        } catch (e) {
          /* silencieux */
        }
      }

      refreshBadge();
      const timer = setInterval(refreshBadge, INTERVAL_MS);
      window.addEventListener('beforeunload', () => clearInterval(timer));
    })();
    </script>
</body>
</html>
