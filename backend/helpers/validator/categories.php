<?php
function validateCategory(array $data): array
{
  $errors = [];

  // Nettoyage des valeurs avant validation pour éviter les espaces parasites.
  $name = trim($data['name'] ?? '');
  $type = trim($data['type'] ?? '');
  $iconId = trim($data['icon_id'] ?? '');
  $color = trim($data['color'] ?? '');

  // Validation du nom : champ requis + longueur max.
  if ($name === '') {
    $errors['name'] = 'Le nom de la catégorie est obligatoire.';
  } elseif (mb_strlen($name) > 255) {
    $errors['name'] = 'Le nom de la catégorie ne doit pas dépasser 255 caractères.';
  }

  // Validation du type : income ou expense uniquement.
  if ($type === '') {
    $errors['type'] = 'Le type de la catégorie est obligatoire.';
  } elseif (!in_array($type, ['income', 'expense'], true)) {
    $errors['type'] = 'Le type de la catégorie est invalide.';
  }

  // Validation de l'ID de l'icône : champ requis et doit être un entier positif.
  if ($iconId === '') {
    $errors['icon_id'] = 'L\'icône est obligatoire.';
  } elseif (!ctype_digit($iconId) || (int) $iconId <= 0) {
    $errors['icon_id'] = 'L\'icône sélectionnée est invalide.';
  }

  // Validation de la couleur : format hexadécimal #RRGGBB.
  if ($color === '') {
    $errors['color'] = 'La couleur de la catégorie est obligatoire.';
  } elseif (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
    $errors['color'] = 'La couleur doit être au format hexadécimal (#RRGGBB).';
  }

  return $errors;
}