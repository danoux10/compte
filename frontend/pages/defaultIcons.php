<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestion des icônes</title>
  <!-- Styles SCSS de la page (compilés vers defaultIcon.css). -->
  <link rel="stylesheet" href="../tempFiles/styles/defaultIcon.css">
  <!-- UI de la page (chargement, seed et rendu des icônes). -->
  <script src="../tempFiles/scripts/defaultIcon-ui.js" defer></script>
</head>
<body>
  <main class="container">
    <section class="panel">
      <h1>Gestion des icônes</h1>

      <div class="actions">
        <button id="seed-icons-btn" type="button">Ajouter les icônes</button>
        <button id="show-icons-btn" type="button">Voir toutes les icônes</button>
      </div>

      <div id="status-message" aria-live="polite"></div>

      <section id="icon-list"></section>
    </section>
  </main>

</body>
</html>
