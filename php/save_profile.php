<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../html/login.html");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

/* Récupération des champs texte */
$first_name    = trim($_POST['first_name'] ?? '');
$last_name     = trim($_POST['last_name'] ?? '');
$date_of_birth = trim($_POST['date_of_birth'] ?? '');
$phone         = trim($_POST['phone'] ?? '');
$skills        = trim($_POST['skills'] ?? '');
$languages     = trim($_POST['languages'] ?? '');

if ($first_name === '' || $last_name === '') {
    header("Location: profile.php?error=invalid_name");
    exit();
}

/* On commence par mettre à jour les champs texte (sans la photo) */
$sql = "UPDATE users
        SET first_name = ?, last_name = ?, date_of_birth = ?, phone = ?, skills = ?, languages = ?
        WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    header("Location: profile.php?error=prepare");
    exit();
}
$stmt->bind_param("ssssssi", $first_name, $last_name, $date_of_birth, $phone, $skills, $languages, $user_id);
if (!$stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: profile.php?error=sql_update");
    exit();
}
$stmt->close();

/* Gestion upload photo (optionnel) */
if (!empty($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {

    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $conn->close();
        header("Location: profile.php?error=upload_error");
        exit();
    }

    // Limite taille 2 Mo
    if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
        $conn->close();
        header("Location: profile.php?error=file_too_large");
        exit();
    }

    // Vérification type MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['photo']['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($allowed[$mime])) {
        $conn->close();
        header("Location: profile.php?error=bad_type");
        exit();
    }

    // S'assurer que le dossier uploads existe
    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    // Nom de fichier unique
    $ext = $allowed[$mime];
    $filename = "uid{$user_id}_" . date('Ymd_His') . "." . $ext;

    $destAbs = $uploadDir . DIRECTORY_SEPARATOR . $filename;     // chemin serveur
    $destRel = 'uploads/' . $filename;                            // chemin public relatif pour BDD

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $destAbs)) {
        $conn->close();
        header("Location: profile.php?error=move_failed");
        exit();
    }

    // Mettre à jour la BDD (photo_path)
    $sql2 = "UPDATE users SET photo_path = ? WHERE id = ?";
    $stmt2 = $conn->prepare($sql2);
    if ($stmt2) {
        $stmt2->bind_param("si", $destRel, $user_id);
        if (!$stmt2->execute()) {
            // En cas d’échec BDD, on peut garder le fichier, ou le supprimer si tu préfères
            // @unlink($destAbs);
            $stmt2->close();
            $conn->close();
            header("Location: profile.php?error=sql_photo");
            exit();
        }
        $stmt2->close();
    } else {
        // @unlink($destAbs);
        $conn->close();
        header("Location: profile.php?error=prepare_photo");
        exit();
    }
}

$conn->close();
header("Location: profile.php?saved=1");
exit();
