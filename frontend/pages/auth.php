<?php
require_once __DIR__ . '/../../backend/helpers/session.php';

if (isUserLoggedIn()) {
  header('Location: testLogout.php');
  exit;
}

$authContentPath = __DIR__ . '/../pageContent/auth/';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Connexion — Compte</title>

  <!-- Style de la page Auth -->
  <link rel="stylesheet" href="../tempFiles/styles/auth.css">

  <!-- Animations / affichage / changement de formulaire -->
  <script src="../tempFiles/scripts/auth-ui.js" defer></script>
  <!-- AJAX Auth -->
  <script src="../scripts/ajax/auth.js" defer></script>
</head>

<body>

<main class="auth">

  <!-- Boutons Connexion / Inscription -->
  <?php include $authContentPath . 'switchForm.html'; ?>

  <!-- Formulaire connexion -->
  <?php include $authContentPath . 'login.html'; ?>

  <!-- Formulaire inscription -->
  <?php
    include $authContentPath . 'register.html';
	?>

</main>

</body>
</html>