<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../html/login.html");
    exit();
}

include 'db.php';

// Récupérer les informations des utilisateurs
$sql_users = "SELECT * FROM users";
$result_users = $conn->query($sql_users);

// Récupérer les statistiques globales
$sql_total_users = "SELECT COUNT(*) as total FROM users";
$result_total_users = $conn->query($sql_total_users);
$total_users = $result_total_users->fetch_assoc()['total'];

$sql_total_missions = "SELECT COUNT(*) as total FROM missions";
$result_total_missions = $conn->query($sql_total_missions);
$total_missions = $result_total_missions->fetch_assoc()['total'];

$sql_total_events = "SELECT COUNT(*) as total FROM events";
$result_total_events = $conn->query($sql_total_events);
$total_events = $result_total_events->fetch_assoc()['total'];

$sql_total_trainings = "SELECT COUNT(*) as total FROM trainings";
$result_total_trainings = $conn->query($sql_total_trainings);
$total_trainings = $result_total_trainings->fetch_assoc()['total'];

// Récupérer les notifications
$sql_notifications = "SELECT message, date FROM notifications ORDER BY date DESC";
$result_notifications = $conn->query($sql_notifications);

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Administrateur</title>
    <link rel="stylesheet" href="../css/admin_dashboard.css">
</head>
<body>
    <div class="container">
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
                    <p><?php echo $total_users; ?></p>
                </div>
                <div class="stat-item">
                    <h3>Nombre de Missions</h3>
                    <p><?php echo $total_missions; ?></p>
                </div>
                <div class="stat-item">
                    <h3>Nombre d'Événements</h3>
                    <p><?php echo $total_events; ?></p>
                </div>
                <div class="stat-item">
                    <h3>Nombre de Formations</h3>
                    <p><?php echo $total_trainings; ?></p>
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
                    <th>Actions</th>
                </tr>
                <?php while($row = $result_users->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['first_name']; ?></td>
                    <td><?php echo $row['last_name']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['phone']; ?></td>
                    <td><?php echo $row['role']; ?></td>
                    <td>
                        <a href="edit_user.php?id=<?php echo $row['id']; ?>">Modifier</a> | 
                        <a href="delete_user.php?id=<?php echo $row['id']; ?>">Supprimer</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
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
                <a href="../html/events.html" class="access-item">Événements</a>
                <a href=profile.php class="access-item">Profil</a>
                <a href=admin_add_training.php class="access-item">Formations</a>
                <a href="../html/messages.html" class="access-item">Messages</a>
            </div>
        </section>
        
    </div>
</body>
</html>
