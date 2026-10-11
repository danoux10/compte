<?php
function normalizeSvgColor(string $svg):string{
  $dom = new DOMDocument();
  libxml_use_internal_errors(true);
  $loaded = $dom->loadXML($svg, LIBXML_NONET);
  libxml_clear_errors();
  if (!$loaded) {
    throw new RuntimeException('SVG invalide.');
  }
  foreach ($dom->getElementsByTagName('*') as $element) {
    if ($element->hasAttribute('color')) {
      $element->removeAttribute('color');
    }
    if ($element->hasAttribute('fill')) {
      $fill = strtolower(trim($element->getAttribute('fill')));
      if ($fill !== 'none' && $fill !== 'transparent') {
        $element->setAttribute('fill', 'currentColor');
      }
    }
    if ($element->hasAttribute('stroke')) {
      $stroke = strtolower(trim($element->getAttribute('stroke')));
      if ($stroke !== 'none' && $stroke !== 'transparent') {
        $element->setAttribute('stroke', 'currentColor');
      }
    }
    if ($element->hasAttribute('style')) {
      $style = $element->getAttribute('style');
      $style = preg_replace('/color\s*:\s*[^;]+;?/i', '', $style);
      $style = preg_replace_callback(
        '/(fill|stroke)\s*:\s*([^;]+)/i',
        function (array $matches): string {
          $property = strtolower($matches[1]);
          $value = strtolower(trim($matches[2]));
          if ($value === 'none' || $value === 'transparent') {
            return "$property: $value;";
          }
          return "$property: currentColor;";
        },
        $style
      );
      $element->setAttribute('style', trim($style));
    }
  }
  $root = $dom->documentElement;
  if ($root->hasAttribute('fill')) {
    $fill = strtolower(trim($root->getAttribute('fill')));
  }
  return $dom->saveXML($dom->documentElement);
}