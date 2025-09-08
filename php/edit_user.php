<?php
session_start();
include 'db.php';

// Vérifie si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

// Récupérer l'ID de l'utilisateur à modifier
$user_id = $_GET['id'];

// Récupérer les informations de l'utilisateur à partir de la base de données
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "Utilisateur non trouvé.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mettre à jour les informations de l'utilisateur
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $role = $conn->real_escape_string($_POST['role']);

    $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, role = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $role, $user_id);

    if ($stmt->execute()) {
        header("Location: admin_dashboard.php?message=modification_reussie");
        exit();
    } else {
        echo "Erreur lors de la mise à jour : " . $stmt->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Modifier Utilisateur</title>
    <link rel="stylesheet" href="../css/admin_dashboard.css">
</head>
<body>
    <h1>Modifier Utilisateur</h1>
    <form action="edit_user.php?id=<?php echo $user['id']; ?>" method="POST">
        <label for="first_name">Prénom :</label>
        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
        
        <label for="last_name">Nom :</label>
        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
        
        <label for="email">Email :</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        
        <label for="phone">Téléphone :</label>
        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
        
        <label for="role">Rôle :</label>
        <select id="role" name="role">
            <option value="user" <?php if ($user['role'] === 'user') echo 'selected'; ?>>Utilisateur</option>
            <option value="admin" <?php if ($user['role'] === 'admin') echo 'selected'; ?>>Administrateur</option>
        </select>
        
        <button type="submit">Modifier</button>
    </form>
</body>
</html>
