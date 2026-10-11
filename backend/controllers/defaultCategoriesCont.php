<?php

require_once '../config/database.php';
require_once '../helpers/request.php';
require_once '../helpers/response.php';
require_once '../../Database/Insert/seedDefaultCategories.php';

// Autorise les appels depuis l'aperçu PhpStorm (localhost:63342).
applyCorsHeaders();

$requestMethod =
  $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Réponse dédiée au preflight CORS.
if ($requestMethod === 'OPTIONS') {
  http_response_code(204);
  exit;
}

$task = getTask();

match ($task) {
  'seed_default' => seedDefaultAction(),
  'list_all' => listAllDefaultCategoriesAction(),

  default => response([
    'success' => false,
    'message' => 'Tâche inconnue.'
  ], 400),
};


function applyCorsHeaders(): void
{
  $allowedOrigins = [
    'http://localhost:63342',
    'http://127.0.0.1:63342',
  ];

  $origin =
    $_SERVER['HTTP_ORIGIN'] ?? '';

  if (in_array($origin, $allowedOrigins, true)) {
    header(
      'Access-Control-Allow-Origin: ' .
      $origin
    );

    header('Vary: Origin');
  }

  header(
    'Access-Control-Allow-Methods: GET, OPTIONS'
  );

  header(
    'Access-Control-Allow-Headers: Content-Type'
  );
}


function seedDefaultAction(): void
{
  global $bdd;

  /*
   * Le seed écrit des logs via echo.
   * On les neutralise pour conserver
   * une réponse JSON valide.
   */
  ob_start();

  $result =
    seedDefaultCategories($bdd);

  ob_end_clean();


  if ($result['success']) {

    response([
      'success' => true,
      'message' => $result['message'],
      'stats' => $result['stats'] ?? []
    ], 200);

  }


  response([
    'success' => false,
    'message' =>
      $result['message'] ??
      'Erreur pendant l\'import.'
  ], 500);
}


function listAllDefaultCategoriesAction(): void
{
  global $bdd;

  $query = $bdd->query(
    'SELECT
      c.idCategory,
      c.name,
      c.color,
      c.type,
      c.state,

      i.name AS icon_name,
      i.path AS icon_path

     FROM categories c

     INNER JOIN icons i
       ON i.idIcon = c.icon

     WHERE c.idUser IS NULL

     ORDER BY
       c.type,
       c.name'
  );

  $rows =
    $query->fetchAll(PDO::FETCH_ASSOC);

  $categories = [];


  foreach ($rows as $row) {

    $categories[] = [
      'idCategory' =>
        (int) $row['idCategory'],

      'name' =>
        $row['name'],

      'color' =>
        $row['color'],

      'type' =>
        $row['type'],

      'state' =>
        $row['state'],

      'iconName' =>
        str_replace(
          '-defaultIcon',
          '',
          $row['icon_name']
        ),

      'iconPath' =>
        $row['icon_path'],
    ];

  }


  response([
    'success' => true,
    'count' => count($categories),
    'categories' => $categories
  ], 200);
}
