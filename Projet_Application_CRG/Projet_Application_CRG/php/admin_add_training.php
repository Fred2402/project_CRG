<?php
session_start();
include 'db.php';

// Vérifie si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

// Récupérer tous les utilisateurs pour les assigner aux formations
$sql = "SELECT id, first_name, last_name FROM users";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Formations - Administrateur</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <h1>Gestion des Formations</h1>
    <form action="save_training.php" method="POST">
        <div>
            <label for="user_id">Utilisateur :</label>
            <select id="user_id" name="user_id" required>
                <?php while($row = $result->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>">
                        <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label for="training_name">Nom de la formation :</label>
            <input type="text" id="training_name" name="training_name" required>
        </div>
        <div>
            <label for="niveau">Niveau de formation :</label>
            <input type="text" id="niveau" name="niveau" required>
        </div>
        <div>
            <label for="date_completed">Date de complétion :</label>
            <input type="date" id="date_completed" name="date_completed">
        </div>
        <button type="submit">Ajouter la formation</button>
    </form>
</body>
</html>
