<?php
require_once __DIR__ . '/../../backend/helpers/session.php';

if (!isUserLoggedIn()) {
  header('Location: auth.php');
  exit;
}

$authContentPath = __DIR__ . '/../pageContent/categories/';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Categories - compte</title>

  <!-- Style de la page Auth -->
  <link rel="stylesheet" href="../tempFiles/styles/categories.css">

  <!-- AJAX Auth -->
  <script src="../scripts/ajax/categories.js" defer></script>

  <!-- Script pour la gestion de l'UI -->
  <script src="../tempFiles/scripts/categories-ui.js" defer></script>

	<script src="https://unpkg.com/react@18/umd/react.development.js" crossorigin></script>
	<script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js" crossorigin></script>
	<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
	<script src="../scripts/react/categories/icons-library-skeleton.jsx" type="text/babel"></script>
	<script src="../scripts/react/categories/categories-list-skeleton.jsx" type="text/babel"></script>
</head>

<body>

<main class="connectedPage">
  <?php include $authContentPath . 'iconsForm.html'; ?>
  <?php include $authContentPath . 'categoriesForm.html'; ?>
  <?php include $authContentPath . 'viewCat.html'; ?>
  <?php include $authContentPath . 'updateCatForm.html'; ?>
</main>

</body>
</html>