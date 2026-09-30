<?php
// chargement des dépendances globales du contrôleur de catégories.
require_once '../config/database.php';
require_once '../helpers/request.php';
require_once '../helpers/secureData.php';
require_once '../helpers/response.php';

//Validation des champs du formulaire de création de catégorie.
require_once '../helpers/Validator/icons.php';
require_once '../helpers/Validator/categories.php';

// Récupère l'action demandée par la requête HTTP.
$task = getTask();

// Distribution des tâches vers la bonne fonction de traitement.
match ($task) {
    'add_icon' => addIcon(),
    'add_category' => addCategory(),
    default => response([
      'success' => false,
      'error' => 'Tâche inconnue.'
    ],400)
};

function addIcon(){
  global $bdd;
  $name = sanitize($_POST['name'] ?? null);
  $file = $_FILES['file'] ?? null;

  //verification icon

}

function addCategory(){
  global $bdd;

}