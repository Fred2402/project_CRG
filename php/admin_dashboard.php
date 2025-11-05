<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../html/login.html");
    exit();
}

include 'db.php';

/* ====== 1) Données ====== */

/* Utilisateurs (tri par date de création DESC) */
$sql_users = "SELECT id, first_name, last_name, email, phone, role, created_at 
              FROM users
              ORDER BY created_at DESC";
$result_users = $conn->query($sql_users);

/* Statistiques globales */
function count_or_zero(mysqli $conn, string $sql): int {
    $res = $conn->query($sql);
    if (!$res) return 0;
    $row = $res->fetch_assoc();
    return (int)($row['total'] ?? 0);
}
$total_users      = count_or_zero($conn, "SELECT COUNT(*) as total FROM users");
$total_missions   = count_or_zero($conn, "SELECT COUNT(*) as total FROM missions");
$total_events     = count_or_zero($conn, "SELECT COUNT(*) as total FROM events");
/* trainings -> training (singulier) */
$total_trainings  = count_or_zero($conn, "SELECT COUNT(*) as total FROM training");

/* Notifications (si besoin plus tard) */
$sql_notifications = "SELECT message, date FROM notifications ORDER BY date DESC";
$result_notifications = $conn->query($sql_notifications);

/* 4) Dernières formations (raccourci CRUD) */
$sql_last_trainings = "SELECT t.id, t.training_name, t.niveau, t.date_completed,
                              u.first_name, u.last_name, u.email
                       FROM training t
                       LEFT JOIN users u ON u.id = t.user_id
                       ORDER BY t.id DESC
                       LIMIT 10";
$result_last_trainings = $conn->query($sql_last_trainings);

$conn->close();

/* ====== 3) Flash messages depuis la query string ====== */
$flash = [];
if (!empty($_GET['created']))  $flash[] = ['type'=>'success','msg'=>'Création réussie.'];
if (!empty($_GET['updated']))  $flash[] = ['type'=>'success','msg'=>'Mise à jour effectuée.'];
if (!empty($_GET['deleted']))  $flash[] = ['type'=>'success','msg'=>'Suppression effectuée.'];
if (!empty($_GET['error']))    $flash[] = ['type'=>'error','msg'=>'Erreur : '.htmlspecialchars($_GET['error'])];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Administrateur</title>
    <link rel="stylesheet" href="../css/admin_dashboard.css">
</head><!-- === Toast (popup) === -->
<div id="toast" class="toast" aria-live="polite" aria-atomic="true" style="display:none;">
  <div id="toast-msg">Message</div>
</div>

<script>
(function () {
  function showToast(message, type = 'success', duration = 3000) {
    const toast = document.getElementById('toast');
    const msgEl = document.getElementById('toast-msg');
    if (!toast || !msgEl) return;

    toast.className = 'toast ' + (type === 'error' ? 'toast-error' : 'toast-success');
    msgEl.textContent = message;
    toast.style.display = 'block';
    toast.style.opacity = '1';

    setTimeout(() => {
      toast.style.opacity = '0';
      setTimeout(() => { toast.style.display = 'none'; }, 300);
    }, duration);
  }

  const params = new URLSearchParams(window.location.search);
  if (params.get('notif') === '1') {
    showToast('Notification envoyée ✅', 'success');
    // Optionnel: nettoyer l’URL pour éviter le toast au refresh
    const url = new URL(window.location.href);
    url.searchParams.delete('notif');
    window.history.replaceState({}, '', url.toString());
  }
  if (params.get('error')) {
    showToast('Erreur: ' + params.get('error'), 'error');
    const url = new URL(window.location.href);
    url.searchParams.delete('error');
    window.history.replaceState({}, '', url.toString());
  }
})();
</script>

<body>
    <div class="container">
        <!-- Flash messages -->
        <?php if (!empty($flash)): ?>
            <?php foreach ($flash as $f): ?>
                <div class="alert <?php echo $f['type']==='success' ? 'success' : 'error'; ?>">
                    <?php echo $f['msg']; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Section Bienvenue -->
        <section class="welcome-profile">
            <h1>Bienvenue, Administrateur</h1>
        </section>

        <!-- Section Statistiques Globales -->
        <section class="statistics">
            <h2>Statistiques Globales</h2>
            <div class="stat-grid">
                <div class="stat-item">
                    <h3>Nombre d'Utilisateurs</h3>
                    <p><?php echo (int)$total_users; ?></p>
                </div>
                <div class="stat-item">
                    <h3>Nombre de Missions</h3>
                    <p><?php echo (int)$total_missions; ?></p>
                </div>
                <div class="stat-item">
                    <h3>Nombre d'Événements</h3>
                    <p><?php echo (int)$total_events; ?></p>
                </div>
                <div class="stat-item">
                    <h3>Nombre de Formations</h3>
                    <p><?php echo (int)$total_trainings; ?></p>
                </div>
            </div>
        </section>

        <!-- Section Gestion des Utilisateurs -->
        <section class="user-management">
            <h2>Gestion des Utilisateurs</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Prénom</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Rôle</th>
                    <th>Créé le</th>
                    <th>Actions</th>
                </tr>
                <?php if ($result_users && $result_users->num_rows > 0): ?>
                    <?php while($row = $result_users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo (int)$row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><?php echo htmlspecialchars($row['role']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at'] ?? ''); ?></td>
                        <td>
                            <a href="edit_user.php?id=<?php echo (int)$row['id']; ?>">Modifier</a> | 
                            <a href="delete_user.php?id=<?php echo (int)$row['id']; ?>" onclick="return confirm('Supprimer cet utilisateur ?')">Supprimer</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8">Aucun utilisateur.</td></tr>
                <?php endif; ?>
            </table>
        </section>

        <!-- 4) Raccourci formations (dernieres entrées + actions CRUD) -->
        <section class="user-management" style="margin-top:16px;">
            <h2>Dernières formations</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Volontaire</th>
                            <th>Nom</th>
                            <th>Niveau</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result_last_trainings && $result_last_trainings->num_rows > 0): ?>
                        <?php while($t = $result_last_trainings->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo (int)$t['id']; ?></td>
                            <td>
                                <?php
                                  $full = trim(($t['first_name'] ?? '').' '.($t['last_name'] ?? ''));
                                  echo htmlspecialchars($full !== '' ? $full : '—');
                                ?>
                                <small style="color:#6b7280;display:block;"><?php echo htmlspecialchars($t['email'] ?? ''); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($t['training_name']); ?></td>
                            <td><?php echo htmlspecialchars($t['niveau']); ?></td>
                            <td>
                                <?php if (!empty($t['date_completed'])): ?>
                                    <span class="badge success">Acquis</span>
                                <?php else: ?>
                                    <span class="badge muted">Non acquis</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($t['date_completed'] ?? '—'); ?></td>
                            <td class="actions">
                                <a href="edit_training.php?id=<?php echo (int)$t['id']; ?>">Modifier</a> |
                                <a href="delete_training.php?id=<?php echo (int)$t['id']; ?>" class="btn danger" onclick="return confirm('Supprimer cette formation ?')">Supprimer</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7">Aucune formation récente.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:8px;">
                <a class="btn" href="admin_add_training.php">Gérer toutes les formations</a>
            </div>
        </section>

        <!-- Section Notifications et Annonces -->
        <section class="send-notifications">
            <h2>Envoyer une Notification</h2>
            <form action="send_notification.php" method="POST">
                <div>
                    <label for="localite_id">Sélectionnez la Localité :</label>
                    <select id="localite_id" name="localite_id" required>
                        <option value="1">Owendo</option>
                        <option value="2">Libreville</option>
                        <option value="3">Akanda</option>
                        <option value="all">Tous</option>
                    </select>
                </div>
                <div>
                    <label for="message">Message :</label>
                    <textarea id="message" name="message" rows="4" required></textarea>
                </div>
                <button type="submit">Envoyer la Notification</button>
            </form>
        </section>

        <!-- Section Accès Rapide -->
        <section class="quick-access">
            <h2>Accès Rapide</h2>
            <div class="access-grid">
                <a href="events.php" class="access-item">Événements</a>
                <a href="profile.php" class="access-item">Profil</a>
                <a href="admin_add_training.php" class="access-item">Formations</a>
                <a href="messages.php" class="access-item card link">Messages</a>
                <a href="create_mission.php" class="access-item">Créer une mission</a>
                <a href="assign_mission.php" class="access-item">Assigner mission</a>
                <a href="create_event.php" class="access-item">Créer un événement</a>
                <a href="register_event.php" class="access-item">Inscrire à un événement</a>
            </div>
        </section>
    </div>
</body>
</html>
