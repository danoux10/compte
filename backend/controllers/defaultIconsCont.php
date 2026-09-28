<?php
require_once '../config/database.php';
require_once '../helpers/request.php';
require_once '../helpers/response.php';
require_once '../../Database/Insert/seedDefaultIcons.php';

// Autorise les appels depuis l'aperçu PhpStorm (localhost:63342).
applyCorsHeaders();

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
// Réponse dédiée au preflight CORS.
if ($requestMethod === 'OPTIONS') {
  http_response_code(204);
  exit;
}

$task = getTask();

match ($task) {
  'seed_default' => seedDefaultAction(),
  'list_all' => listAllIconsAction(),
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

  $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
  if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
  }

  header('Access-Control-Allow-Methods: GET, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type');
}

function seedDefaultAction(): void
{
  global $bdd;

  // Le seed écrit des logs via echo : on les neutralise pour garder une réponse JSON valide.
  ob_start();
  $result = seedDefaultIcons($bdd);
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
    'message' => $result['message'] ?? 'Erreur d\'import.'
  ], 500);
}

function listAllIconsAction(): void
{
  global $bdd;

  $query = $bdd->query(
    'SELECT ic.name AS category_name,
            i.name AS icon_name,
            i.path,
            i.user_id
     FROM icons i
     INNER JOIN iconsCategories ic
       ON ic.idIconsCategories = i.iconCategory_id
     -- "Mes icônes" à la fin + icônes par défaut avant celles utilisateur.
     ORDER BY CASE
                WHEN LOWER(ic.name) IN (\'mes icônes\', \'mes icons\') THEN 1
                ELSE 0
              END,
              ic.type,
              ic.name,
              CASE WHEN i.user_id IS NULL THEN 0 ELSE 1 END,
              i.name'
  );

  $rows = $query->fetchAll();
  $grouped = [];

  foreach ($rows as $row) {
    $category = $row['category_name'];

    if (!isset($grouped[$category])) {
      $grouped[$category] = [];
    }

    $grouped[$category][] = [
      'name' => $row['icon_name'],
      'path' => $row['path'],
    ];
  }

  response([
    'success' => true,
    'count' => count($rows),
    'categories' => $grouped
  ], 200);
}
