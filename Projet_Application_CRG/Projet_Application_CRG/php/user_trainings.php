<?php
session_start();
include 'db.php';

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Récupérer l'ID de l'utilisateur depuis la session
$user_id = $_SESSION['user_id'];

// Préparer et exécuter la requête pour récupérer les formations de l'utilisateur
$sql = "SELECT training_name, niveau, date_completed 
        FROM training 
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Erreur de préparation de la requête SQL : " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$trainings = [];
while ($row = $result->fetch_assoc()) {
    $trainings[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Formations</title>
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <h1>Mes Formations</h1>

    <table border="1" cellpadding="10">
        <thead>
            <tr>
                <th>Nom de la Formation</th>
                <th>Niveau</th>
                <th>Statut</th>
                <th>Date de Complétion</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($trainings)): ?>
                <tr>
                    <td colspan="4">Aucune formation trouvée.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($trainings as $training): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($training['training_name']); ?></td>
                        <td><?php echo htmlspecialchars($training['niveau']); ?></td>
                        <td><?php echo !empty($training['date_completed']) ? 'Acquis' : 'Non Acquis'; ?></td>
                        <td><?php echo !empty($training['date_completed']) ? htmlspecialchars($training['date_completed']) : 'N/A'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
