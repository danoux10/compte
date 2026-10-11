<?php

require_once __DIR__ . '/../../backend/config/database.php';


/*
|--------------------------------------------------------------------------
| Catégories par défaut
|--------------------------------------------------------------------------
|
| idUser = NULL
| state = active
|
| iconName correspond au nom du fichier SVG
| sans "-defaultIcon".
|
| Tu peux modifier cette liste pour changer
| les catégories créées automatiquement.
|
*/

function getDefaultCategoriesData(): array
{
  return [

    /*
    |--------------------------------------------------------------------------
    | DÉPENSES
    |--------------------------------------------------------------------------
    */

    [
      'name' => 'Achats',
      'type' => 'expense',
      'color' => '#7C8CF8',
      'iconName' => 'shopping',
    ],

    [
      'name' => 'Alimentation',
      'type' => 'expense',
      'color' => '#65C466',
      'iconName' => 'alimentation',
    ],

    [
      'name' => 'Animaux',
      'type' => 'expense',
      'color' => '#D49A6A',
      'iconName' => 'animaux',
    ],

    [
      'name' => 'Autres',
      'type' => 'expense',
      'color' => '#8E98A8',
      'iconName' => 'autres',
    ],

    [
      'name' => 'Famille',
      'type' => 'expense',
      'color' => '#E77CAB',
      'iconName' => 'enfants',
    ],

    [
      'name' => 'Finances',
      'type' => 'expense',
      'color' => '#5C9BE6',
      'iconName' => 'banque',
    ],

    [
      'name' => 'Logement',
      'type' => 'expense',
      'color' => '#D38B5D',
      'iconName' => 'logement',
    ],

    [
      'name' => 'Loisirs',
      'type' => 'expense',
      'color' => '#A677E8',
      'iconName' => 'loisirs',
    ],

    [
      'name' => 'Santé',
      'type' => 'expense',
      'color' => '#E86666',
      'iconName' => 'sante',
    ],

    [
      'name' => 'Services',
      'type' => 'expense',
      'color' => '#55BFC2',
      'iconName' => 'internet',
    ],

    [
      'name' => 'Transports',
      'type' => 'expense',
      'color' => '#4A9FE8',
      'iconName' => 'transport',
    ],

    [
      'name' => 'Voyages',
      'type' => 'expense',
      'color' => '#E2B75B',
      'iconName' => 'voyage',
    ],


    /*
    |--------------------------------------------------------------------------
    | REVENUS
    |--------------------------------------------------------------------------
    */

    [
      'name' => 'Aides',
      'type' => 'income',
      'color' => '#65C466',
      'iconName' => 'aide',
    ],

    [
      'name' => 'Autres revenus',
      'type' => 'income',
      'color' => '#8E98A8',
      'iconName' => 'autres-revenus',
    ],

    [
      'name' => 'Finances',
      'type' => 'income',
      'color' => '#5C9BE6',
      'iconName' => 'virement-recu',
    ],

    [
      'name' => 'Travail',
      'type' => 'income',
      'color' => '#65C466',
      'iconName' => 'salaire',
    ],

  ];
}


/*
|--------------------------------------------------------------------------
| Recherche de l'icône par défaut
|--------------------------------------------------------------------------
*/

function findDefaultIconId(
  PDO $bdd,
  string $iconName,
  string $type
): ?int {

  $getIcon = $bdd->prepare(
    'SELECT i.idIcon

     FROM icons i

     INNER JOIN iconsCategories ic
       ON ic.idIconsCategories =
          i.iconCategory_id

     WHERE i.user_id IS NULL

     AND LOWER(
       TRIM(
         REPLACE(
           REPLACE(
             i.name,
             "-defaultIcon",
             ""
           ),
           "-defautlIcon",
           ""
         )
       )
     ) = LOWER(:iconName)

     AND (
       ic.type = :type
       OR ic.type = "both"
     )

     LIMIT 1'
  );

  $getIcon->execute([
    'iconName' => $iconName,
    'type' => $type,
  ]);

  $iconId =
    $getIcon->fetchColumn();


  if (!$iconId) {
    return null;
  }


  return (int) $iconId;
}


/*
|--------------------------------------------------------------------------
| Seed
|--------------------------------------------------------------------------
*/

function seedDefaultCategories(PDO $bdd): array
{
  $categories =
    getDefaultCategoriesData();


  $stats = [
    'created_categories' => 0,
    'existing_categories' => 0,
    'missing_icons' => 0,
  ];


  try {

    $bdd->beginTransaction();


    $checkCategory = $bdd->prepare(
      'SELECT idCategory

       FROM categories

       WHERE idUser IS NULL
       AND type = :type
       AND LOWER(name) = LOWER(:name)

       LIMIT 1'
    );


    $insertCategory = $bdd->prepare(
      'INSERT INTO categories (
        name,
        color,
        icon,
        type,
        idUser,
        state
      )

      VALUES (
        :name,
        :color,
        :icon,
        :type,
        NULL,
        "active"
      )'
    );


    echo PHP_EOL;
    echo "Insertion des catégories par défaut..." .
      PHP_EOL;

    echo "--------------------------------------" .
      PHP_EOL;


    foreach ($categories as $category) {

      /*
       * Vérifie que la catégorie
       * par défaut n'existe pas déjà.
       */
      $checkCategory->execute([
        'name' => $category['name'],
        'type' => $category['type'],
      ]);


      if ($checkCategory->fetchColumn()) {

        $stats['existing_categories']++;

        echo
          "Déjà présente : " .
          $category['name'] .
          ' [' .
          $category['type'] .
          ']' .
          PHP_EOL;

        continue;
      }


      /*
       * Recherche automatiquement
       * l'id de l'icône correspondante.
       */
      $iconId =
        findDefaultIconId(
          $bdd,
          $category['iconName'],
          $category['type']
        );


      if (!$iconId) {

        $stats['missing_icons']++;

        echo
          "Icône introuvable : " .
          $category['iconName'] .
          ' pour ' .
          $category['name'] .
          PHP_EOL;

        continue;
      }


      /*
       * Création de la catégorie.
       */
      $insertCategory->execute([
        'name' =>
          $category['name'],

        'color' =>
          $category['color'],

        'icon' =>
          $iconId,

        'type' =>
          $category['type'],
      ]);


      $stats['created_categories']++;


      echo
        "Ajoutée : " .
        $category['name'] .
        ' [' .
        $category['type'] .
        ']' .
        PHP_EOL;

    }


    $bdd->commit();


    return [
      'success' => true,

      'message' =>
        'Import des catégories terminé avec succès.',

      'stats' => $stats,
    ];


  } catch (Throwable $e) {

    if ($bdd->inTransaction()) {
      $bdd->rollBack();
    }


    return [
      'success' => false,

      'message' =>
        'Erreur pendant l\'import : ' .
        $e->getMessage(),
    ];

  }
}


/*
|--------------------------------------------------------------------------
| Exécution directe CLI
|--------------------------------------------------------------------------
*/

if (
  realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ===
  __FILE__
) {

  $result =
    seedDefaultCategories($bdd);


  if (!$result['success']) {
    exit(1);
  }

}
