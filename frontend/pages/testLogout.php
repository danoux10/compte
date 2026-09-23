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

	<title>Connexion — Compte</title>

	<!-- Style de la page Auth -->
	<link rel="stylesheet" href="../tempFiles/styles/testConnected.css">

	<!-- AJAX Auth -->
	<script src="../scripts/ajax/auth.js" defer></script>

</head>

<body>

<main class="connectedPage">
	<h1>Bienvenue !</h1>
	<?php
		include $authContentPath . 'logout.html';
	?>
</main>

</body>
</html>