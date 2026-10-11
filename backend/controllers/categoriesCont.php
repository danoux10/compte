<?php
// chargement des dépendances globales du contrôleur de catégories.
require_once '../config/database.php';
require_once '../helpers/request.php';
require_once '../helpers/secureData.php';
require_once '../helpers/response.php';
require_once '../helpers/session.php';
require_once '../helpers/icons.php';

//Validation des champs du formulaire de création de catégorie.
require_once '../helpers/Validator/icons.php';
require_once '../helpers/Validator/categories.php';

// Récupère l'action demandée par la requête HTTP.
$task = getTask();

// Distribution des tâches vers la bonne fonction de traitement.
match ($task) {
    'add_icon' => addIcon(),
    'add_category' => addCategory(),
    'update_category' => updateCategory(),
    'get_icons' => getIcons(),
    'get_categories' => getCategories(),
    'toggle_category_state' => toggleCategoryState(),
    'delete_category' => deleteCategory(),
    default => response([
      'success' => false,
      'error' => 'Tâche inconnue.'
    ],400)
};

function getConnectedUserId(): int
{
  if (!isUserLoggedIn()) {
    response([
      'success' => false,
      'message' => 'Vous devez être connecté.'
    ], 401);
  }

  $userId = $_SESSION['user']['id'] ?? null;

  if (!$userId) {
    response([
      'success' => false,
      'message' => 'Utilisateur introuvable dans la session.'
    ], 401);
  }

  return (int) $userId;
}

function getIcons(){
  global $bdd;
  $userId = getConnectedUserId();
  $getIcons = $bdd->prepare("SELECT
    i.idIcon,
    i.name,
    TRIM(REPLACE(REPLACE(i.name, '-defaultIcon', ''), '-defautlIcon', '')) AS displayName,
    i.path,
    ic.idIconsCategories,
    ic.name AS iconCatName,
    ic.type AS iconType
  FROM icons i
  INNER JOIN iconscategories ic ON ic.idIconsCategories = i.iconCategory_id
  WHERE i.user_id IS NULL OR i.user_id = :userId
  ORDER BY ic.name, i.name
");
  $getIcons->execute([':userId' => $userId]);
  $icons = $getIcons->fetchAll(PDO::FETCH_ASSOC);

  $groupedIcons = [];
  foreach ($icons as $icon) {
    $categoryId = $icon['idIconsCategories'];
    $groupedIcons[$categoryId][] = $icon;
  }
  response([
    'success' => true,
    'iconsByCategory' => $groupedIcons
  ], 200);
}

function addIcon(){
  global $bdd;

  $userId = getConnectedUserId();
  $name = sanitize($_POST['icon-name'] ?? null);
  $file = $_FILES['icon-file'] ?? null;

  //verification icon
  $errors = validateIcon(['name' => $name], $file);
  if (!empty($errors)) {
    response([
      'success' => false,
      'errors' => $errors
    ], 400);
  }

  if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
    response([
      'success' => false,
      'error' => 'Impossible de récupérer le fichier téléchargé.'
    ], 400);
  }

  $queryIconCat = $bdd->prepare('SELECT idIconsCategories FROM iconscategories WHERE name = :name AND type = :type LIMIT 1');
  $queryIconCat->execute([
    ':name' => 'Mes icons',
    ':type' => 'both'
  ]);

  $iconCategory = $queryIconCat->fetch();

  if(!$iconCategory) {
    response([
      'success' => false,
      'error' => 'La catégorie "Mes icons" est introuvable.'
    ], 500);
  }

  $iconCategory = (int) $iconCategory['idIconsCategories'];

  $svg = file_get_contents($file['tmp_name']);
  try {
    $svg = normalizeSvgColor($svg);
  } catch (Exception $e) {
    response([
      'success' => false,
      'error' => 'Impossible de normaliser le fichier SVG.'
    ], 500);
  }

  $relativeDirectory = 'assets/icons/categories/mes_icons/'.$userId.'/';
  $absoluteDirectory = dirname(__DIR__, 2).'/'.$relativeDirectory;

  if (!is_dir($absoluteDirectory)) {
    if (!mkdir($absoluteDirectory,0755,true) && !is_dir($absoluteDirectory)) {
      response([
        'success' => false,
        'error' => 'Impossible de créer le dossier de stockage des icônes.'
      ], 500);
    }
  }

  do{
    $fileName = bin2hex(random_bytes(16)).'.svg';
    $absolutePath = $absoluteDirectory.$fileName;
  } while (file_exists($absolutePath));

  $databasePath = $relativeDirectory.$fileName;

  $saved = file_put_contents($absolutePath, $svg, LOCK_EX);
  if($saved === false) {
    response([
      'success' => false,
      'error' => 'Impossible d\'enregistrer le fichier SVG.'
    ], 500);
  }

  try {
    $insertIcon = $bdd->prepare('INSERT INTO icons (name, path, iconCategory_id, user_id) VALUES (:name, :path, :idIconCategories, :user_id)');
    $insertIcon->execute([
      ':name' => $name,
      ':path' => $databasePath,
      ':idIconCategories' => $iconCategory,
      ':user_id' => $userId
    ]);
  } catch (Exception $e) {
    response([
      'success' => false,
      'error' => 'Impossible d\'enregistrer l\'icône dans la base de données.'
    ], 500);
  }

  response([
    'success' => true,
    'message' => 'Icône ajoutée avec succès.',
  ], 201);
}

function addCategory(){
  global $bdd;
  $userId = getConnectedUserId();
  $name = sanitize($_POST['category-name'] ?? null);
  $typeCategory = sanitize($_POST['category-type'] ?? null);
  $idIcon = sanitize($_POST['category-icon'] ?? null);
  $colorCategory = sanitize($_POST['category-color'] ?? null);

  // verification des champs
  $errors = validateCategory([
    'name' => $name,
    'type' => $typeCategory,
    'icon_id' => $idIcon,
    'color' => $colorCategory
  ]);

  // vérifie qu'une catégorie active avec le même nom/type n'existe pas déjà pour l'utilisateur
  if (!isset($errors['name']) && !isset($errors['type'])) {
    $checkCategory = $bdd->prepare(
      'SELECT idCategory
       FROM categories
       WHERE idUser = :userId
         AND state = :state
         AND type = :type
         AND LOWER(name) = LOWER(:name)
       LIMIT 1'
    );
    $checkCategory->execute([
      ':userId' => $userId,
      ':state' => 'active',
      ':type' => $typeCategory,
      ':name' => $name
    ]);

    if ($checkCategory->fetch()) {
      $errors['name'] = 'Une catégorie avec ce nom existe déjà pour ce type.';
    }
  }

  // vérifie que l'icône existe et qu'elle est accessible à l'utilisateur
  if (!isset($errors['icon_id'])) {
    $checkIcon = $bdd->prepare(
      'SELECT idIcon
       FROM icons
       WHERE idIcon = :idIcon
         AND (user_id IS NULL OR user_id = :userId)
       LIMIT 1'
    );
    $checkIcon->execute([
      ':idIcon' => (int) $idIcon,
      ':userId' => $userId
    ]);

    if (!$checkIcon->fetch()) {
      $errors['icon_id'] = 'L\'icône sélectionnée est introuvable.';
    }
  }

  if (!empty($errors)) {
    response([
      'success' => false,
      'errors' => $errors
    ], 400);
  }

  // insertion de la catégorie dans la base de données
  $insertCategory = $bdd->prepare(
    'INSERT INTO categories (name, type, icon, color, idUser)
     VALUES (:name, :type, :icon, :color, :userId)'
  );

  $insertCategory->execute([
    ':name' => $name,
    ':type' => $typeCategory,
    ':icon' => (int) $idIcon,
    ':color' => $colorCategory,
    ':userId' => $userId
  ]);

  response([
    'success' => true,
    'message' => 'Validation de la catégorie réussie.'
  ], 200);
}

function updateCategory() {
  global $bdd;
  $userId = getConnectedUserId();

  $idCategory = (int) sanitize($_POST['category-id'] ?? null);
  $name = sanitize($_POST['category-name'] ?? null);
  $typeCategory = sanitize($_POST['category-type'] ?? $_POST['update-category-type'] ?? null);
  $idIcon = sanitize($_POST['category-icon'] ?? null);
  $colorCategory = sanitize($_POST['category-color'] ?? null);
  $stateCategory = sanitize($_POST['category-state'] ?? null);

  if ($idCategory <= 0) {
    response([
      'success' => false,
      'error' => 'Identifiant de catégorie invalide.'
    ], 400);
  }

  // Une catégorie par défaut (idUser = NULL) ne peut pas être modifiée par l'utilisateur.
  $findCategory = $bdd->prepare(
    'SELECT idCategory
     FROM categories
     WHERE idCategory = :idCategory
       AND idUser = :userId
     LIMIT 1'
  );
  $findCategory->execute([
    ':idCategory' => $idCategory,
    ':userId' => $userId
  ]);

  if (!$findCategory->fetch(PDO::FETCH_ASSOC)) {
    response([
      'success' => false,
      'error' => 'Catégorie introuvable.'
    ], 404);
  }

  $errors = validateCategory([
    'name' => $name,
    'type' => $typeCategory,
    'icon_id' => $idIcon,
    'color' => $colorCategory
  ]);

  if ($stateCategory !== 'active' && $stateCategory !== 'inactive') {
    $errors['state'] = 'L\'état de la catégorie est invalide.';
  }

  if (!isset($errors['name']) && !isset($errors['type']) && $stateCategory === 'active') {
    $checkCategory = $bdd->prepare(
      'SELECT idCategory
       FROM categories
       WHERE idUser = :userId
         AND state = :state
         AND type = :type
         AND LOWER(name) = LOWER(:name)
         AND idCategory != :idCategory
       LIMIT 1'
    );
    $checkCategory->execute([
      ':userId' => $userId,
      ':state' => 'active',
      ':type' => $typeCategory,
      ':name' => $name,
      ':idCategory' => $idCategory
    ]);

    if ($checkCategory->fetch()) {
      $errors['name'] = 'Une catégorie avec ce nom existe déjà pour ce type.';
    }
  }

  if (!isset($errors['icon_id'])) {
    $checkIcon = $bdd->prepare(
      'SELECT idIcon
       FROM icons
       WHERE idIcon = :idIcon
         AND (user_id IS NULL OR user_id = :userId)
       LIMIT 1'
    );
    $checkIcon->execute([
      ':idIcon' => (int) $idIcon,
      ':userId' => $userId
    ]);

    if (!$checkIcon->fetch()) {
      $errors['icon_id'] = 'L\'icône sélectionnée est introuvable.';
    }
  }

  if (!empty($errors)) {
    response([
      'success' => false,
      'errors' => $errors
    ], 400);
  }

  $updateCategory = $bdd->prepare(
    'UPDATE categories
     SET name = :name,
         type = :type,
         icon = :icon,
         color = :color,
         state = :state
     WHERE idCategory = :idCategory
       AND idUser = :userId'
  );
  $updateCategory->execute([
    ':name' => $name,
    ':type' => $typeCategory,
    ':icon' => (int) $idIcon,
    ':color' => $colorCategory,
    ':state' => $stateCategory,
    ':idCategory' => $idCategory,
    ':userId' => $userId
  ]);

  response([
    'success' => true,
    'message' => 'Catégorie mise à jour avec succès.'
  ], 200);
}

function getCategories() {
  global $bdd;
  $userId = getConnectedUserId();

  /*
   * On récupère :
   * - les catégories par défaut (idUser IS NULL)
   * - les catégories personnelles de l'utilisateur
   *
   * Pour une catégorie par défaut, categoryUserSettings permet
   * d'avoir un état différent pour chaque utilisateur.
   */
  $getCategories = $bdd->prepare(
    'SELECT
       c.idCategory,
       c.name,
       c.type,
       c.icon,
       c.color,
       c.idUser,
       i.path AS iconPath,

       CASE
         WHEN c.idUser IS NULL THEN COALESCE(cus.state, c.state)
         ELSE c.state
       END AS state,

       CASE
         WHEN c.idUser IS NULL THEN 1
         ELSE 0
       END AS isDefault

     FROM categories c

     LEFT JOIN icons i
       ON c.icon = i.idIcon

     LEFT JOIN categoryUserSettings cus
       ON cus.idCategory = c.idCategory
       AND cus.idUser = :settingsUserId

     WHERE c.idUser IS NULL
        OR c.idUser = :ownerUserId

     ORDER BY
       c.type ASC,
       c.name ASC'
  );

  $getCategories->execute([
    ':settingsUserId' => $userId,
    ':ownerUserId' => $userId
  ]);

  $categories = $getCategories->fetchAll(PDO::FETCH_ASSOC);
  $activeCategories = [];
  $inactiveCategories = [];

  foreach ($categories as $category) {
    if (($category['state'] ?? 'active') === 'inactive') {
      $inactiveCategories[] = $category;
      continue;
    }

    $activeCategories[] = $category;
  }

  response([
    'success' => true,
    'categories' => $categories,
    'activeCategories' => $activeCategories,
    'inactiveCategories' => $inactiveCategories
  ], 200);
}

function toggleCategoryState() {
  global $bdd;
  $userId = getConnectedUserId();
  $idCategory = (int) sanitize($_POST['category-id'] ?? null);

  if ($idCategory <= 0) {
    response([
      'success' => false,
      'error' => 'Identifiant de catégorie invalide.'
    ], 400);
  }

  /*
   * On accepte :
   * - une catégorie par défaut
   * - une catégorie appartenant à l'utilisateur
   *
   * Pour une catégorie par défaut, on récupère son état effectif
   * pour l'utilisateur connecté.
   */
  $findCategory = $bdd->prepare(
    'SELECT
       c.idCategory,
       c.idUser,

       CASE
         WHEN c.idUser IS NULL THEN COALESCE(cus.state, c.state)
         ELSE c.state
       END AS state

     FROM categories c

     LEFT JOIN categoryUserSettings cus
       ON cus.idCategory = c.idCategory
       AND cus.idUser = :settingsUserId

     WHERE c.idCategory = :idCategory
       AND (c.idUser IS NULL OR c.idUser = :ownerUserId)

     LIMIT 1'
  );

  $findCategory->execute([
    ':settingsUserId' => $userId,
    ':idCategory' => $idCategory,
    ':ownerUserId' => $userId
  ]);

  $category = $findCategory->fetch(PDO::FETCH_ASSOC);

  if (!$category) {
    response([
      'success' => false,
      'error' => 'Catégorie introuvable.'
    ], 404);
  }

  $currentState = $category['state'] ?? 'active';
  $newState = $currentState === 'inactive' ? 'active' : 'inactive';

  /*
   * Catégorie par défaut :
   * on ne modifie jamais categories.state.
   * On stocke uniquement la préférence de l'utilisateur.
   */
  if ($category['idUser'] === null) {
    $updateState = $bdd->prepare(
      'INSERT INTO categoryUserSettings (
         idUser,
         idCategory,
         state
       )
       VALUES (
         :idUser,
         :idCategory,
         :state
       )
       ON DUPLICATE KEY UPDATE
         state = :updateState'
    );

    $updateState->execute([
      ':idUser' => $userId,
      ':idCategory' => $idCategory,
      ':state' => $newState,
      ':updateState' => $newState
    ]);
  } else {
    /*
     * Catégorie personnelle :
     * l'état reste stocké directement dans categories.state.
     */
    $updateState = $bdd->prepare(
      'UPDATE categories
       SET state = :state
       WHERE idCategory = :idCategory
         AND idUser = :idUser'
    );

    $updateState->execute([
      ':state' => $newState,
      ':idCategory' => $idCategory,
      ':idUser' => $userId
    ]);
  }

  response([
    'success' => true,
    'state' => $newState,
    'message' => $newState === 'active'
      ? 'La catégorie est maintenant active.'
      : 'La catégorie est maintenant inactive.'
  ], 200);
}

function deleteCategory() {
  global $bdd;
  $userId = getConnectedUserId();
  $idCategory = (int) sanitize($_POST['category-id'] ?? null);

  if ($idCategory <= 0) {
    response([
      'success' => false,
      'error' => 'Identifiant de catégorie invalide.'
    ], 400);
  }

  // Une catégorie par défaut (idUser = NULL) ne peut pas être supprimée par l'utilisateur.
  $findCategory = $bdd->prepare(
    'SELECT idCategory
     FROM categories
     WHERE idCategory = :idCategory
       AND idUser = :userId
     LIMIT 1'
  );
  $findCategory->execute([
    ':idCategory' => $idCategory,
    ':userId' => $userId
  ]);

  if (!$findCategory->fetch(PDO::FETCH_ASSOC)) {
    response([
      'success' => false,
      'error' => 'Catégorie introuvable.'
    ], 404);
  }

  $categoryUsageCount = getCategoryUsageCount($idCategory);
  if ($categoryUsageCount > 0) {
    response([
      'success' => false,
      'error' => 'La catégorie ne peux pas être supprimer.'
    ], 409);
  }

  $deleteCategory = $bdd->prepare(
    'DELETE FROM categories
     WHERE idCategory = :idCategory
       AND idUser = :userId'
  );
  $deleteCategory->execute([
    ':idCategory' => $idCategory,
    ':userId' => $userId
  ]);

  response([
    'success' => true,
    'message' => 'Catégorie supprimée.'
  ], 200);
}

function getCategoryUsageCount(int $idCategory): int
{
  global $bdd;

  $getReferences = $bdd->prepare(
    'SELECT TABLE_NAME, COLUMN_NAME
     FROM information_schema.KEY_COLUMN_USAGE
     WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
       AND REFERENCED_TABLE_NAME = :referencedTableName
       AND REFERENCED_COLUMN_NAME = :referencedColumnName'
  );
  $getReferences->execute([
    ':referencedTableName' => 'categories',
    ':referencedColumnName' => 'idCategory'
  ]);

  $references = $getReferences->fetchAll(PDO::FETCH_ASSOC);
  $usageCount = 0;

  foreach ($references as $reference) {
    $tableName = str_replace('`', '``', $reference['TABLE_NAME']);
    $columnName = str_replace('`', '``', $reference['COLUMN_NAME']);

    $getUsage = $bdd->prepare(
      "SELECT COUNT(*) FROM `{$tableName}` WHERE `{$columnName}` = :idCategory"
    );
    $getUsage->execute([
      ':idCategory' => $idCategory
    ]);

    $usageCount += (int) $getUsage->fetchColumn();
  }

  return $usageCount;
}
