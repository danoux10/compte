<?php

require_once __DIR__ . '/../../backend/config/database.php';

// Garantit l'idempotence du seed en empêchant les doublons logiques.
function ensureUniqueIndexes(PDO $bdd): void
{
  $indexes = [
    [
      'table' => 'iconsCategories',
      'name' => 'idx_icons_categories_name_type',
      'ddl' => 'CREATE UNIQUE INDEX idx_icons_categories_name_type
                ON iconsCategories (name, type)',
    ],
    [
      'table' => 'icons',
      'name' => 'idx_icons_path',
      'ddl' => 'CREATE UNIQUE INDEX idx_icons_path
                ON icons (path)',
    ],
  ];

  $checkIndex = $bdd->prepare(
    'SELECT 1
     FROM information_schema.statistics
     WHERE table_schema = DATABASE()
       AND table_name = :tableName
       AND index_name = :indexName
     LIMIT 1'
  );

  foreach ($indexes as $index) {
    $checkIndex->execute([
      'tableName' => $index['table'],
      'indexName' => $index['name'],
    ]);

    if ($checkIndex->fetchColumn()) {
      continue;
    }

    $bdd->exec($index['ddl']);
  }
}

// Retourne l'id de la catégorie existante, ou la crée si absente.
function ensureCategoryId(PDO $bdd, string $categoryName, string $databaseType): int
{
  $getCategory = $bdd->prepare(
    'SELECT idIconsCategories
     FROM iconsCategories
     WHERE name = :name
       AND type = :type
     LIMIT 1'
  );

  $getCategory->execute([
    'name' => $categoryName,
    'type' => $databaseType,
  ]);

  $iconCategoryId = $getCategory->fetchColumn();

  if ($iconCategoryId) {
    return (int)$iconCategoryId;
  }

  $insertCategory = $bdd->prepare(
    'INSERT INTO iconsCategories (name, type)
     VALUES (:name, :type)'
  );

  $insertCategory->execute([
    'name' => $categoryName,
    'type' => $databaseType,
  ]);

  return (int)$bdd->lastInsertId();
}

// Normalise le nom pour marquer explicitement les icônes par défaut.
function formatDefaultIconName(string $iconName): string
{
  if (stripos($iconName, 'defaultIcon') !== false) {
    return $iconName;
  }

  return $iconName . '-defaultIcon';
}

function insertIconIfMissing(
  PDO $bdd,
  string $iconName,
  string $relativePath,
  int $iconCategoryId,
  array &$stats
): void {
  $checkIcon = $bdd->prepare(
    'SELECT idIcon, name
     FROM icons
     WHERE path = :path
     LIMIT 1'
  );

  $checkIcon->execute(['path' => $relativePath]);

  $existingIcon = $checkIcon->fetch(PDO::FETCH_ASSOC);

  // Si le path existe déjà, on synchronise le nom pour appliquer la convention defaultIcon.
  if ($existingIcon) {
    if (($existingIcon['name'] ?? '') !== $iconName) {
      $updateIconName = $bdd->prepare(
        'UPDATE icons
         SET name = :name
         WHERE idIcon = :idIcon'
      );

      $updateIconName->execute([
        'name' => $iconName,
        'idIcon' => (int)$existingIcon['idIcon'],
      ]);
      echo "Renommée : " . $iconName . PHP_EOL;
    }

    $stats['existing_icons']++;
    echo "Déjà présente : " . $iconName . PHP_EOL;
    return;
  }

  try {
    $insertIcon = $bdd->prepare(
      'INSERT INTO icons (name, path, iconCategory_id, user_id)
       VALUES (:name, :path, :iconCategoryId, NULL)'
    );

    $insertIcon->execute([
      'name' => $iconName,
      'path' => $relativePath,
      'iconCategoryId' => $iconCategoryId,
    ]);

    $stats['created_icons']++;
    echo "Ajoutée : " . $iconName . PHP_EOL;
  } catch (Throwable $e) {
    $stats['existing_icons']++;
    echo "Déjà présente (conflit) : " . $iconName . PHP_EOL;
  }
}

function seedDefaultIcons(PDO $bdd): array
{
  $projectRoot = dirname(__DIR__, 2);
  $iconsRoot = $projectRoot . '/assets/icons/categories';

  $types = [
    'depenses' => 'expense',
    'revenus'  => 'income',
  ];

  $categoryNames = [
    'achats'       => 'Achats',
    'alimentation' => 'Alimentation',
    'animaux'      => 'Animaux',
    'autres'       => 'Autres',
    'famille'      => 'Famille',
    'finances'     => 'Finances',
    'logement'     => 'Logement',
    'loisirs'      => 'Loisirs',
    'sante'        => 'Santé',
    'services'     => 'Services',
    'transports'   => 'Transports',
    'voyages'      => 'Voyages',
    'aides'        => 'Aides',
    'travail'      => 'Travail',
  ];

  $stats = [
    'created_categories' => 0,
    'existing_categories' => 0,
    'created_icons' => 0,
    'existing_icons' => 0,
  ];

  if (!is_dir($iconsRoot)) {
    return [
      'success' => false,
      'message' => 'Le dossier des icônes n\'existe pas : ' . $iconsRoot,
    ];
  }

  try {
    ensureUniqueIndexes($bdd);

    // Transaction unique: catégories + icônes, pour éviter un état partiellement seedé.
    $bdd->beginTransaction();

    echo PHP_EOL;
    echo "Création des catégories d'icônes..." . PHP_EOL;
    echo "------------------------------------" . PHP_EOL;

    foreach ($types as $folderType => $databaseType) {
      $typePath = $iconsRoot . '/' . $folderType;

      if (!is_dir($typePath)) {
        echo "Dossier ignoré : " . $typePath . PHP_EOL;
        continue;
      }

      $categoryFolders = array_filter(glob($typePath . '/*'), 'is_dir');

      foreach ($categoryFolders as $categoryFolder) {
        $folderName = basename($categoryFolder);
        $categoryName = $categoryNames[$folderName] ?? ucfirst($folderName);

        $checkCategory = $bdd->prepare(
          'SELECT idIconsCategories
           FROM iconsCategories
           WHERE name = :name
             AND type = :type
           LIMIT 1'
        );

        $checkCategory->execute([
          'name' => $categoryName,
          'type' => $databaseType,
        ]);

        $categoryId = $checkCategory->fetchColumn();

        if ($categoryId) {
          $stats['existing_categories']++;
          echo "Déjà présente : " . $categoryName . ' [' . $databaseType . ']' . PHP_EOL;
          continue;
        }

        try {
          $insertCategory = $bdd->prepare(
            'INSERT INTO iconsCategories (name, type)
             VALUES (:name, :type)'
          );

          $insertCategory->execute([
            'name' => $categoryName,
            'type' => $databaseType,
          ]);

          $stats['created_categories']++;
          echo "Créée : " . $categoryName . ' [' . $databaseType . ']' . PHP_EOL;
        } catch (Throwable $e) {
          $stats['existing_categories']++;
          echo "Déjà présente (conflit) : " . $categoryName . ' [' . $databaseType . ']' . PHP_EOL;
        }
      }
    }

    echo PHP_EOL;
    echo "Insertion des icônes..." . PHP_EOL;
    echo "-------------------------" . PHP_EOL;

    foreach ($types as $folderType => $databaseType) {
      $typePath = $iconsRoot . '/' . $folderType;

      if (!is_dir($typePath)) {
        continue;
      }

      $categoryFolders = array_filter(glob($typePath . '/*'), 'is_dir');

      foreach ($categoryFolders as $categoryFolder) {
        $folderName = basename($categoryFolder);
        $categoryName = $categoryNames[$folderName] ?? ucfirst($folderName);

        $getCategory = $bdd->prepare(
          'SELECT idIconsCategories
           FROM iconsCategories
           WHERE name = :name
             AND type = :type
           LIMIT 1'
        );

        $getCategory->execute([
          'name' => $categoryName,
          'type' => $databaseType,
        ]);

        $iconCategoryId = $getCategory->fetchColumn();

        if (!$iconCategoryId) {
          throw new RuntimeException('Catégorie introuvable : ' . $categoryName . ' / ' . $databaseType);
        }

        $svgFiles = glob($categoryFolder . '/*.svg');

        foreach ($svgFiles as $svgFile) {
          $iconName = formatDefaultIconName(pathinfo($svgFile, PATHINFO_FILENAME));
          $relativePath = 'assets/icons/categories/' . $folderType . '/' . $folderName . '/' . basename($svgFile);
          insertIconIfMissing($bdd, $iconName, $relativePath, (int)$iconCategoryId, $stats);
        }
      }
    }

    // Les icônes perso sont volontairement traitées après les icônes par défaut.
    $myIconsRoot = $iconsRoot . '/mes_icons';
    if (is_dir($myIconsRoot)) {
      echo PHP_EOL;
      echo "Insertion de mes icônes (en dernier)..." . PHP_EOL;
      echo "----------------------------------------" . PHP_EOL;

      foreach ($types as $folderType => $databaseType) {
        $typePath = $myIconsRoot . '/' . $folderType;

        if (!is_dir($typePath)) {
          continue;
        }

        $directSvgFiles = glob($typePath . '/*.svg');
        if ($directSvgFiles !== false && count($directSvgFiles) > 0) {
          $personalCategoryId = ensureCategoryId($bdd, 'Mes icônes', $databaseType);
          foreach ($directSvgFiles as $svgFile) {
            $iconName = pathinfo($svgFile, PATHINFO_FILENAME);
            $relativePath = 'assets/icons/categories/mes_icons/' . $folderType . '/' . basename($svgFile);
            insertIconIfMissing($bdd, $iconName, $relativePath, $personalCategoryId, $stats);
          }
        }

        $categoryFolders = array_filter(glob($typePath . '/*'), 'is_dir');

        foreach ($categoryFolders as $categoryFolder) {
          $folderName = basename($categoryFolder);
          $categoryName = $categoryNames[$folderName] ?? ucfirst($folderName);
          $personalCategoryId = ensureCategoryId($bdd, $categoryName, $databaseType);
          $svgFiles = glob($categoryFolder . '/*.svg');

          foreach ($svgFiles as $svgFile) {
            $iconName = pathinfo($svgFile, PATHINFO_FILENAME);
            $relativePath = 'assets/icons/categories/mes_icons/' . $folderType . '/' . $folderName . '/' . basename($svgFile);
            insertIconIfMissing($bdd, $iconName, $relativePath, $personalCategoryId, $stats);
          }
        }
      }
    }

    $bdd->commit();

    echo PHP_EOL;
    echo "====================================" . PHP_EOL;
    echo "Import terminé avec succès." . PHP_EOL;
    echo "====================================" . PHP_EOL;

    return [
      'success' => true,
      'message' => 'Import terminé avec succès.',
      'stats' => $stats,
    ];
  } catch (Throwable $e) {
    if ($bdd->inTransaction()) {
      $bdd->rollBack();
    }

    echo PHP_EOL;
    echo "Erreur pendant l'import :" . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;

    return [
      'success' => false,
      'message' => 'Erreur pendant l\'import : ' . $e->getMessage(),
    ];
  }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
  $result = seedDefaultIcons($bdd);

  if (!$result['success']) {
    exit(1);
  }
}
