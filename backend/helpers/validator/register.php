<?php
/**
 * Valide les données soumises lors de l'inscription d'un utilisateur.
 * Retourne un tableau associatif des erreurs de validation.
 *
 * @param array $data Données reçues depuis le formulaire d'inscription.
 * @return array Tableau des erreurs par champ.
 */
function validateRegister(array $data): array
{
  // Tableau centralisant les erreurs remontées au formulaire.
  $errors = [];

  // Nettoyage des valeurs avant validation pour éviter les espaces parasites.
  $name = trim($data['name'] ?? '');
  $lastname = trim($data['lastname'] ?? '');
  $email = trim($data['email'] ?? '');
  $password = $data['password'] ?? '';
  $passwordConfirm = $data['password_confirm'] ?? '';
  $terms = $data['terms'] ?? false;

  // Validation du nom : champ requis.
  if ($name === '') {
    $errors['name'] = 'Le nom est obligatoire.';
  }

  // Validation du prénom : champ requis.
  if ($lastname === '') {
    $errors['lastname'] = 'Le prénom est obligatoire.';
  }

  // Validation de l'email : obligatoire puis format valide.
  if ($email === '') {
    $errors['email'] = 'L\'email est obligatoire.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'L\'email n\'est pas valide.';
  }

  // Validation du mot de passe : sécurité minimale exigée.
  if ($password === '') {
    $errors['password'] = 'Veuillez renseigner un mot de passe.';
  } elseif (strlen($password) < 8) {
    $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères.';
  } elseif (!preg_match('/[a-z]/', $password)) {
    $errors['password'] = 'Le mot de passe doit contenir au moins une minuscule.';
  } elseif (!preg_match('/[A-Z]/', $password)) {
    $errors['password'] = 'Le mot de passe doit contenir au moins une majuscule.';
  } elseif (!preg_match('/[0-9]/', $password)) {
    $errors['password'] = 'Le mot de passe doit contenir au moins un chiffre.';
  } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
    $errors['password'] = 'Le mot de passe doit contenir au moins un caractère spécial.';
  }

  // Vérification de la confirmation du mot de passe.
  if ($passwordConfirm === '') {
    $errors['password_confirm'] = 'Veuillez confirmer le mot de passe.';
  } elseif ($password !== $passwordConfirm) {
    $errors['password_confirm'] = 'Les mots de passe ne correspondent pas.';
  }

  // Validation des conditions générales : acceptation obligatoire.
  if (!$terms) {
    $errors['terms'] = 'Vous devez accepter les conditions d\'utilisation.';
  }

  // Retourne les erreurs collectées afin d'afficher les messages dans le formulaire.
  return $errors;
}