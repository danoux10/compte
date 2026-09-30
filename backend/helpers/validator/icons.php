<?php

function validateIcon(array $data, ?array $file): array
{
  $errors = [];

  //vérification name
  $name = trim($data['name'] ?? '');
  if ($name === '') {
    $errors['name'] = 'Le nom de l\'icône est obligatoire.';
  } elseif (strlen($name) > 100) {
    $errors['name'] = 'Le nom de l\'icône ne doit pas dépasser 100 caractères.';
  }

  //vérification file
  // Vérifie si le fichier est présent
  if ($file === null || !isset($file['error'])) {
    $errors['file'] = 'Le fichier de l\'icône est obligatoire.';
    return $errors;
  }
  // erreur lors de l'upload
  if ($file['error'] !== UPLOAD_ERR_OK) {
    $errors['file'] = 'Une erreur est survenue lors de l\'upload du fichier.';
    return $errors;
  }

  $tmpName = $file['tmp_name'];
  if ($tmpName === '') {
    $errors['icon'] = 'Imposible de lire le fichier svg.';
    return $errors;
  }

  $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  if ($extension !== 'svg') {
    $errors['file'] = 'Le fichier de l\'icône doit être au format SVG.';
    return $errors;
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);


  $mime = $finfo->file($tmpName);

  $allowedMimeTypes = [
    'image/svg+xml',
    'application/xml',
    'text/xml',
    'text/plain',
  ];


  if (!in_array($mime, $allowedMimeTypes,true)) {
    $errors['icon'] =
      'Le fichier envoyé n\'est pas un SVG valide.';
    return $errors;
  }

  $svg = file_get_contents($tmpName);
  if ($svg === false || trim($svg) === '') {
    $errors['icon'] = 'Le fichier SVG est vide ou illisible.';
    return $errors;
  }

  $svgError = validateSvgContent($svg);
  if ($svgError !== null) {
    $errors['icon'] = $svgError;
  }

  return $errors;
}

function validateSvgContent(string $svg): ?string{
  if(stripos($svg,'<!DOCTYPE') !== false || stripos($svg,'<!ENTITY') !== false){
    return  'Le SVG contient des éléments non autorisés.';
  }

  $dom = new DOMDocument();
  libxml_use_internal_errors(true);
  $loaded = $dom->loadXML($svg, LIBXML_NONET);
  libxml_clear_errors();
  if (!$loaded) {
    return 'Le fichier SVG n\'est pas valide.';
  }

  if(strtolower($dom->documentElement?->localName??'') !== 'svg'){
    return 'le fichier envoyé n\'est pas un SVG.';
  }

  $securityErrors = validateSvgSecurity($dom);
  if($securityErrors !== null){
    return $securityErrors;
  }

  if(!isMonochromeSvg($dom)){
    return 'Le SVG doit être monochrome (une seule couleur).';
  }
  return null;
}

function validateSvgSecurity(DOMDocument $dom): ?string{
  $forbiddenTags = [
    'script',
    'foreignObject',
    'iframe',
    'object',
    'embed',
    'image',
    'audio',
    'video',
  ];

  foreach ($forbiddenTags as $tag) {
    if($dom ->getElementsByTagName($tag)->length>0){
      return "Le SVG contient un élément interdit";
    }
  }

  foreach ($dom ->getElementsByTagName('*') as $element){
    foreach( iterator_to_array($element->attributes) as $attribute){
      $name = strtolower($attribute->name);
      $value = strtolower(trim($attribute->value));

      if (str_starts_with($name,'on')){
        return "Le SVG contient un attribut interdit";
      }

      if (str_starts_with($value,'javascript:')){
        return "Le SVG contient une valeur interdite";
      }

      if($name === 'href' || $name === 'xlink:href'){
        if(!str_starts_with($value,'#')){
          return "Les ressources externes ne sont pas autorisées";
        }
      }
    }
    return null;
  }
}

function isMonochromeSvg(DOMDocument $dom): bool{
 $colors = [];
  foreach ($dom ->getElementsByTagName('*') as $element){
    if($element ->hasAttribute('fill')){
      addSvgColor($colors,$element->getAttribute('fill'));
    }
    if($element ->hasAttribute('stroke')){
      addSvgColor($colors,$element->getAttribute('stroke'));
    }
    if($element ->hasAttribute('style')){
      $style = $element->getAttribute('style');
      preg_match_all('/(?:fill|stroke)\s*:\s*([^;]+)/i', $style, $matches);
      foreach ($matches[1] ?? [] as $color) {
        addSvgColor($colors, $color);
      }
    }
  }
  $colors = array_unique($colors);
  return count($colors) <= 1;

  function addSvgColor(array &$colors, string $color): void{
    $color = strtolower(trim($color));
    $ignoredColors = [
      '',
      'none',
      'transparent',
      'currentColor',
      'inherit',];
    if(in_array($color,$ignoredColors,true)){
      return;
    }
    $colors[] = $color;
  }
}