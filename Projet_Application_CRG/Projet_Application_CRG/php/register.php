<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Afficher les données POST pour débogage
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    // Récupérer et échapper les données du formulaire
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $date_of_birth = $conn->real_escape_string($_POST['date_of_birth']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $experience_volontaire = $conn->real_escape_string($_POST['experience_volontaire']);
    $status_volontaire = ($experience_volontaire == 'yes') ? 'active' : 'potential';
    $localite_id = $conn->real_escape_string($_POST['localite_id']);
    $role = 'volunteer'; // Par défaut, tous les inscrits sont des volontaires

    // Vérifier si l'email existe déjà
    $sql_check_email = "SELECT id FROM users WHERE email = ?";
    $stmt_check_email = $conn->prepare($sql_check_email);
    $stmt_check_email->bind_param("s", $email);
    $stmt_check_email->execute();
    $result_check_email = $stmt_check_email->get_result();

    if ($result_check_email->num_rows > 0) {
        echo "Erreur : Un utilisateur avec cet email existe déjà.";
    } else {
        // Insérer dans la table users
        $sql = "INSERT INTO users (first_name, last_name, phone, date_of_birth, email, password, experience_volontaire, status_volontaire, localite_id, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssss", $first_name, $last_name, $phone, $date_of_birth, $email, $password, $experience_volontaire, $status_volontaire, $localite_id, $role);

        if ($stmt->execute()) {
            echo "Nouvel utilisateur enregistré avec succès.";
            header("Location: ../html/login.html");
            exit();
        } else {
            echo "Erreur lors de l'ajout dans la table users: " . $conn->error;
        }

        $stmt->close();
    }

    $stmt_check_email->close();
    $conn->close();
}
?>
