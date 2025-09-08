<?php
include 'db.php';

$first_name = 'Admin';
$last_name = 'User';
$phone = '123456789';
$date_of_birth = '1980-01-01';
$email = 'admin@example.com';
$password = password_hash('adminpassword', PASSWORD_DEFAULT);
$experience_volontaire = 'no';
$status_volontaire = 'active';
$localite_id = 1;
$role = 'admin';

$sql = "INSERT INTO users (first_name, last_name, phone, date_of_birth, email, password, experience_volontaire, status_volontaire, localite_id, role)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssssss", $first_name, $last_name, $phone, $date_of_birth, $email, $password, $experience_volontaire, $status_volontaire, $localite_id, $role);

if ($stmt->execute()) {
    echo "Administrateur ajouté avec succès.";
} else {
    echo "Erreur lors de l'ajout de l'administrateur: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
