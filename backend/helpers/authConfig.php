<?php

/**
 * Page par défaut après connexion.
 * Modifie uniquement cette valeur plus tard pour changer la page d'arrivée.
 */
const DEFAULT_AUTHENTICATED_PAGE = 'testLogout.php';

function getDefaultAuthenticatedPage(): string
{
  return DEFAULT_AUTHENTICATED_PAGE;
}
