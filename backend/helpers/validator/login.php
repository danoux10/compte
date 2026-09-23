<?php
/**
 * Valide les données soumises lors de la connexion d'un utilisateur.
 * Retourne un tableau associatif des erreurs de validation.
 *
 * @param array $data Données reçues depuis le formulaire de connexion.
 * @return array Tableau des erreurs par champ.
 */
function validateLogin(array $data): array
{
  // Tableau centralisant les erreurs remontées au formulaire.
  $errors = [];

  // Nettoyage des valeurs avant validation pour éviter les espaces parasites.
  $email = trim($data['email'] ?? '');
  $password = $data['password'] ?? '';

  // Validation de l'email : champ requis et format valide.
  if ($email === '') {
    $errors['email'] = 'L\'email est obligatoire.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'L\'email n\'est pas valide.';
  }

  // Validation du mot de passe : champ requis.
  if ($password === '') {
    $errors['password'] = 'Veuillez renseigner votre mot de passe.';
  }

  // Retourne les erreurs collectées afin d'afficher les messages dans le formulaire.
  return $errors;
}
