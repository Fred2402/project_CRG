<?php
session_start();
include 'db.php';

// Vérifie si l'utilisateur est connecté
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Vérifier la connexion
    if ($conn->connect_error) {
        die("Erreur de connexion à la base de données : " . $conn->connect_error);
    }

    // Récupérer les informations de l'utilisateur depuis la base de données
    $sql = "SELECT first_name, last_name, email, phone, status_volontaire FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Erreur de préparation de la requête SQL : " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        echo "Utilisateur non trouvé.";
        exit();
    }

    // Récupérer les statistiques
    $sql_missions = "SELECT COUNT(*) as total FROM missions WHERE user_id = ?";
    $stmt_missions = $conn->prepare($sql_missions);
    $stmt_missions->bind_param("i", $user_id);
    $stmt_missions->execute();
    $result_missions = $stmt_missions->get_result();
    $missions = $result_missions->fetch_assoc()['total'];

    $sql_events = "SELECT COUNT(*) as total FROM events WHERE user_id = ?";
    $stmt_events = $conn->prepare($sql_events);
    $stmt_events->bind_param("i", $user_id);
    $stmt_events->execute();
    $result_events = $stmt_events->get_result();
    $events = $result_events->fetch_assoc()['total'];

    $sql_trainings = "SELECT COUNT(*) as total FROM trainings WHERE user_id = ?";
    $stmt_trainings = $conn->prepare($sql_trainings);
    $stmt_trainings->bind_param("i", $user_id);
    $stmt_trainings->execute();
    $result_trainings = $stmt_trainings->get_result();
    $trainings = $result_trainings->fetch_assoc()['total'];

    // Récupérer les notifications
    $sql_notifications = "SELECT message, date FROM notifications WHERE user_id = ? OR user_id IS NULL ORDER BY date DESC";
    $stmt_notifications = $conn->prepare($sql_notifications);
    if ($stmt_notifications === false) {
        die("Erreur de préparation de la requête SQL (notifications) : " . $conn->error);
    }
    $stmt_notifications->bind_param("i", $user_id);
    $stmt_notifications->execute();
    $result_notifications = $stmt_notifications->get_result();

} else {
    // Redirige vers la page de connexion si l'utilisateur n'est pas connecté
    header("Location: ../html/login.html");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Bienvenue</title>
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <div class="container">
        <!-- Section Bienvenue et Profil Utilisateur -->
        <section class="welcome-profile">
            <div class="profile-info">
                <!-- Placeholder for the profile picture, to be added later -->
                <div class="user-details">
                    <h1>Bienvenue, <?php echo htmlspecialchars($user['first_name'] . " " . $user['last_name']); ?> !</h1>
                    <p>Email : <?php echo htmlspecialchars($user['email']); ?></p>
                    <p>Téléphone : <?php echo htmlspecialchars($user['phone']); ?></p>
                    <p>Statut : <?php echo htmlspecialchars($user['status_volontaire']); ?></p>
                    <a href="profile.php" class="btn">Gérer le Profil</a>
                </div>
            </div>
        </section>

        <!-- Section Statistiques -->
        <section class="statistics">
            <h2>Statistiques</h2>
            <div class="stat-grid">
                <div class="stat-item">
                    <h3>Nombre de Missions</h3>
                    <p><?php echo $missions; ?></p>
                </div>
                <div class="stat-item">
                    <h3>Événements à Venir</h3>
                    <p><?php echo $events; ?></p>
                </div>
                <div class="stat-item">
                    <h3>Formations Suivies</h3>
                    <p><?php echo $trainings; ?></p>
                </div>
            </div>
        </section>

        <!-- Section Notifications et Annonces -->
        <section class="notifications">
            <h2>Notifications et Annonces</h2>
            <ul>
                <?php
                if ($result_notifications->num_rows > 0) {
                    while($row = $result_notifications->fetch_assoc()) {
                        echo "<li>" . htmlspecialchars($row['message']) . " - <em>" . $row['date'] . "</em></li>";
                    }
                } else {
                    echo "<li>Aucune notification pour le moment.</li>";
                }
                ?>
            </ul>
        </section>

        <!-- Section Accès Rapide -->
        <section class="quick-access">
            <h2>Accès Rapide</h2>
            <div class="access-grid">
                <a href="../html/events.html" class="access-item">Événements</a>
                <a href="profile.php" class="access-item">Profil</a>
                <a href=user_trainings.php class="access-item">Formations</a>
                <a href="../html/messages.html" class="access-item">Messages</a>
            </div>
        </section>
    </div>
</body>
</html>
