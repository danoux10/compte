<?php
require_once __DIR__ . '/../../backend/helpers/session.php';

if (!isUserLoggedIn()) {
  header('Location: auth.php');
  exit;
}

$authContentPath = __DIR__ . '/../pageContent/auth/';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Categories - compte</title>

  <!-- Style de la page Auth -->
  <link rel="stylesheet" href="../tempFiles/styles/">

  <!-- AJAX Auth -->
  <script src="../scripts/ajax/" defer></script>

  <!-- Script pour la gestion de l'UI -->
  <script src="../tempFiles/scripts/" defer></script>
</head>

<body>

<main class="connectedPage">

</main>

</body>
</html>