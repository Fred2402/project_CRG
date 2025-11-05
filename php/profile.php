<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../html/login.html");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

/* Récupérer les infos du user (inclure photo_path, languages, skills) */
$sql = "SELECT first_name, last_name, date_of_birth, phone, email, photo_path, languages, skills
        FROM users
        WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) { die("Erreur préparation SQL: " . $conn->error); }
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res  = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();
$conn->close();

if (!$user) { echo "Utilisateur non trouvé."; exit(); }

/* Flash messages */
$flash = null;
if (!empty($_GET['saved']))  $flash = ['type'=>'success', 'msg' => "Profil mis à jour avec succès."];
if (!empty($_GET['error']))  $flash = ['type'=>'error',   'msg' => "Erreur : ".htmlspecialchars($_GET['error'])];

/* Déterminer la photo à afficher */
$photo = '../images/default_avatar.png'; // fallback : mets une image par défaut dans /images/
if (!empty($user['photo_path'])) {
    // photo_path est stocké en relatif type "uploads/uid_...jpg"
    $photo = '../' . ltrim($user['photo_path'], '/');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Profil Volontaire - Croix-Rouge</title>
    <link rel="stylesheet" href="../css/profile.css">
    <style>
      /* petits styles si besoin */
      .cv-header { display:flex; align-items:center; gap:16px; }
      .avatar { width:96px; height:96px; border-radius:50%; object-fit:cover; border:2px solid #e5e7eb; }
      .flash { padding:10px 12px; border-radius:10px; margin:10px 0; font-size:14px; border:1px solid transparent; }
      .flash.success { background:#ecfdf5;border-color:#a7f3d0;color:#065f46; }
      .flash.error { background:#fee2e2;border-color:#fecaca;color:#991b1b; }
      .hint { color:#6b7280; font-size:12px; }
    </style>
</head>
<body>
    <div class="cv-container">
        <!-- En-tête -->
        <header class="cv-header">
            <img src="<?php echo htmlspecialchars($photo); ?>" alt="Photo de profil" class="avatar">
            <div>
              <h1>Profil de <?php echo htmlspecialchars($user['first_name'] . " " . $user['last_name']); ?></h1>
              <p>Email : <?php echo htmlspecialchars($user['email']); ?></p>
              <p>Téléphone : <?php echo htmlspecialchars($user['phone']); ?></p>
            </div>
        </header>

        <?php if ($flash): ?>
          <div class="flash <?php echo $flash['type']; ?>"><?php echo $flash['msg']; ?></div>
        <?php endif; ?>

        <!-- Formulaire -->
        <form action="save_profile.php" method="POST" class="profile-form" enctype="multipart/form-data">
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
                    <label for="date_of_birth">Date de naissance :</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($user['date_of_birth']); ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Téléphone :</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
            </section>

            <section class="cv-section">
                <h2>Photo de profil</h2>
                <div class="form-group">
                    <label for="photo">Fichier (JPEG/PNG, ≤ 2 Mo) :</label>
                    <input type="file" id="photo" name="photo" accept="image/jpeg,image/png">
                    <div class="hint">Si vous ne choisissez pas de fichier, la photo actuelle est conservée.</div>
                </div>
            </section>

            <section class="cv-section">
                <h2>Compétences</h2>
                <div class="form-group">
                    <label for="skills">Compétences :</label>
                    <textarea id="skills" name="skills" rows="4"><?php echo htmlspecialchars($user['skills'] ?? ''); ?></textarea>
                    <div class="hint">Ex : Premiers secours, Logistique, Coordination…</div>
                </div>
            </section>

            <section class="cv-section">
                <h2>Langues</h2>
                <div class="form-group">
                    <label for="languages">Langues parlées :</label>
                    <textarea id="languages" name="languages" rows="4"><?php echo htmlspecialchars($user['languages'] ?? ''); ?></textarea>
                    <div class="hint">Ex : Français (C2), Anglais (B2), Espagnol (A2)…</div>
                </div>
            </section>

            <div class="manage-profile">
                <button type="submit">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</body>
</html>
