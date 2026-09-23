<?php
// Chargement des dépendances globales du contrôleur d'authentification.
require_once '../config/database.php';
require_once '../helpers/request.php';
require_once '../helpers/secureData.php';
require_once '../helpers/response.php';
require_once '../helpers/session.php';

// Validation des champs du formulaire d'inscription.
require_once '../helpers/Validator/register.php';
require_once '../helpers/Validator/login.php';

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

  // Réponse de succès envoyée au client.
  response([
    'success' => true,
    'message' => "Inscription réussie pour \"$email\"."
  ], 201);
}

function login(){
  global $bdd;
  // Récupération et nettoyage des données soumises par le formulaire.
  $email = sanitize($_POST['email'] ?? null);
  $password = $_POST['password'] ?? '';
  $remember = isset($_POST['remember']);

  // Vérification des champs obligatoires.
  $errors = validateLogin([
    'email' => $email,
    'password' => $password
  ]);

  // Retourne immédiatement les erreurs de formulaire si elles existent.
  if (!empty($errors)) {
    response([
      'success' => false,
      'errors' => $errors
    ], 400);
  }

  // Vérification des informations de connexion.
  $query = $bdd->prepare('SELECT idUser, password FROM users WHERE email = :email LIMIT 1');
  $query->execute([':email' => $email]);
  $user = $query->fetch();

  if (!$user || !password_verify($password, $user['password'])) {
    response([
      'success' => false,
      'message' => 'Email ou mot de passe incorrect.'
    ], 401);
  }

  // Crée une session pour chaque connexion réussie.
  // "Se souvenir de moi" allonge simplement la durée de session.
  $sessionDuration = $remember ? 2592000 : 3600;
  createSession([
    'id' => $user['idUser'],
    'email' => $email
  ], $sessionDuration);

  // Connexion réussie.
  response([
    'success' => true,
    'message' => 'Connexion réussie.',
    'redirect' => 'testLogout.php',
    'session_created' => true,
    'user' => [
      'id' => $user['idUser'],
      'email' => $email
    ]
  ], 200);

}

/**
 * Déconnecte l'utilisateur en détruisant sa session.
 */
function logout()
{
  // Détruit la session utilisateur.
  destroySession();

  // Réponse de succès.
  response([
    'success' => true,
    'message' => 'Déconnexion réussie.',
    'redirect' => 'auth.php'
  ], 200);
}