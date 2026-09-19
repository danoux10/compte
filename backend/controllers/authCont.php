<?php
// Chargement des dépendances globales du contrôleur d'authentification.
require_once '../config/database.php';
require_once '../helpers/request.php';
require_once '../helpers/secureData.php';
require_once '../helpers/response.php';

// Validation des champs du formulaire d'inscription.
require_once '../helpers/Validator/register.php';

// Récupère l'action demandée par la requête HTTP.
$task = getTask();

// Distribution des tâches vers la bonne fonction de traitement.
match ($task) {
  'register' => register(),
  'login' => login(),
  'logout' => logout(),
  default => response([
    'success' => false,
    'message' => 'Tâche inconnue.'
  ], 400)
};

// Enregistre un nouvel utilisateur dans la base de données.
function register()
{
  global $bdd;

  // Récupération et nettoyage des données soumises par le formulaire.
  $name = sanitize($_POST['name'] ?? null);
  $lastname = sanitize($_POST['lastname'] ?? null);
  $email = sanitize($_POST['email'] ?? null);
  // Le mot de passe ne doit pas être nettoyé pour conserver sa valeur brute.
  $password = $_POST['password'] ?? '';
  $password_confirm = $_POST['password_confirm'] ?? '';
  $term = isset($_POST['terms']);

  // Vérification des règles métier et des champs obligatoires.
  $errors = validateRegister([
    'name' => $name,
    'lastname' => $lastname,
    'email' => $email,
    'password' => $password,
    'password_confirm' => $password_confirm,
    'terms' => $term
  ]);

  // Vérifie si l'email est déjà utilisé avant de créer un compte.
  if (!isset($errors['email'])) {
    $checkEmail = $bdd->prepare(
      'SELECT idUser
       FROM users
       WHERE email = :email
       LIMIT 1'
    );
    $checkEmail->execute([
      ':email' => $email
    ]);
    if ($checkEmail->fetch()) {
      $errors['email'] = "L'adresse email \"$email\" est déjà utilisée.";
    }
  }

  // Retourne immédiatement les erreurs de formulaire si elles existent.
  if (!empty($errors)) {
    response([
      'success' => false,
      'errors' => $errors
    ], 400);
  }

  // Hashage du mot de passe avant l'enregistrement sécurisé en base.
  $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

  // Création du nouvel utilisateur dans la table des comptes.
/*
  $newUser = $bdd->prepare(
    'INSERT INTO users (name, lastname, email, password)
     VALUES (:name, :lastname, :email, :password)'
  );
  $newUser->execute([
    ':name' => $name,
    ':lastname' => $lastname,
    ':email' => $email,
    ':password' => $hashedPassword
  ]);
*/
  // Réponse de succès envoyée au client.
  response([
    'success' => true,
    'message' => "Inscription réussie pour \"$email\"."
  ], 201);
}