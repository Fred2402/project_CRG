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

// Préparer et exécuter la requête pour récupérer les informations de l'utilisateur
$sql = "SELECT first_name, last_name, date_of_birth, phone, email FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Erreur de préparation de la requête SQL : " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Vérifie si l'utilisateur existe
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "Utilisateur non trouvé.";
    exit();
}

// Fermer la connexion
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Volontaire - Croix-Rouge</title>
    <link rel="stylesheet" href="../css/profile.css">
</head>
<body>
    <div class="cv-container">
        <!-- En-tête du CV -->
        <header class="cv-header">
            <img src="../images/croix_rouge_logo.jpg" alt="Logo Croix-Rouge" class="cv-logo">
            <h1>Profil de <?php echo htmlspecialchars($user['first_name'] . " " . $user['last_name']); ?></h1>
            <p>Email : <?php echo htmlspecialchars($user['email']); ?></p>
            <p>Téléphone : <?php echo htmlspecialchars($user['phone']); ?></p>
        </header>

        <!-- Formulaire de remplissage du profil -->
        <form action="save_profile.php" method="POST" class="profile-form">
            <!-- Section Informations Personnelles -->
            <section class="cv-section">
                <h2>Informations Personnelles</h2>
                <div class="form-group">
                    <label for="first_name">Prénom :</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Nom :</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="date_of_birth">Date de Naissance :</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($user['date_of_birth']); ?>" required>
                </div>
            </section>

            <!-- Section Expérience de Volontariat -->
            <section class="cv-section">
                <h2>Expérience de Volontariat</h2>
                <div class="form-group">
                    <label for="volunteer_experience">Décrivez votre expérience :</label>
                    <textarea id="volunteer_experience" name="volunteer_experience" rows="4"></textarea>
                </div>
            </section>

            <!-- Section Compétences -->
            <section class="cv-section">
                <h2>Compétences</h2>
                <div class="form-group">
                    <label for="skills">Compétences :</label>
                    <textarea id="skills" name="skills" rows="4"></textarea>
                </div>
            </section>

            <!-- Section Langues -->
            <section class="cv-section">
                <h2>Langues</h2>
                <div class="form-group">
                    <label for="languages">Langues parlées :</label>
                    <textarea id="languages" name="languages" rows="4"></textarea>
                </div>
            </section>

            <!-- Section Autres Informations -->
            <section class="cv-section">
                <h2>Autres Informations</h2>
                <div class="form-group">
                    <label for="additional_info">Informations supplémentaires :</label>
                    <textarea id="additional_info" name="additional_info" rows="4"></textarea>
                </div>
            </section>

            <div class="manage-profile">
                <button type="submit">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</body>
</html>
