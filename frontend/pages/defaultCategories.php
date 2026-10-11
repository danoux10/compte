<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Gestion des catégories par défaut</title>

  <!-- Styles SCSS de la page (compilés vers defaultCategory.css). -->
  <link rel="stylesheet" href="../tempFiles/styles/defaultCategory.css">

  <!-- UI de la page (chargement, seed et rendu des catégories). -->
  <script src="../tempFiles/scripts/defaultCategory-ui.js" defer></script>
</head>

<body>

<main class="container">

  <section class="panel">

    <h1>Gestion des catégories par défaut</h1>

    <div class="actions">

      <button
        id="seed-categories-btn"
        type="button"
      >
        Ajouter les catégories
      </button>

      <button
        id="show-categories-btn"
        type="button"
      >
        Voir toutes les catégories
      </button>

    </div>

    <div
      id="status-message"
      aria-live="polite"
    ></div>

    <section id="category-list"></section>

  </section>

</main>

</body>
</html>
