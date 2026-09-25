<?php
require_once __DIR__ . '/../../backend/helpers/session.php';
require_once __DIR__ . '/../../backend/helpers/authConfig.php';

if (isUserLoggedIn()) {
  header('Location: ' . getDefaultAuthenticatedPage());
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
  <!-- La page de redirection post-connexion se configure dans backend/helpers/authConfig.php -->
  <script>
    window.defaultAuthenticatedPage = "<?= htmlspecialchars(getDefaultAuthenticatedPage(), ENT_QUOTES, 'UTF-8'); ?>";
  </script>
  <!-- AJAX Auth -->
  <script src="../scripts/ajax/auth.js" defer></script>
</head>

<body>

<main class="auth">

  <!-- TEMP TEST ADMIN START (à supprimer après tests) -->
  <section id="quick-admin-actions">
    <button type="button" id="create-admin-btn">Créer utilisateur admin</button>
    <button type="button" id="login-admin-btn">Connexion admin (session persistante)</button>
    <div id="quick-admin-response" class="hidden"></div>
  </section>
  <!-- TEMP TEST ADMIN END -->

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