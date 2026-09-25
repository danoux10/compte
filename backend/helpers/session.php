<?php
/**
 * Crée une session pour un utilisateur connecté.
 * Définit les données de l'utilisateur en session et configure le temps d'expiration.
 *
 * @param array $userData Données de l'utilisateur (idUser, email, etc.)
 * @param int|null $sessionDuration Durée de la session en secondes (null = session sans expiration)
 * @return void
 */
function createSession(array $userData, ?int $sessionDuration = 3600): void
{
  $isSecureConnection = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
  // TEMP TEST ADMIN START (à supprimer après tests)
  $cookieLifetime = $sessionDuration ?? 315360000; // 10 ans
  // TEMP TEST ADMIN END

  if (session_status() === PHP_SESSION_NONE) {
    // Configure le cookie de session avant le démarrage de session.
    session_set_cookie_params([
      'lifetime' => $cookieLifetime,
      'httponly' => true,
      'secure' => $isSecureConnection,
      'samesite' => 'Lax'
    ]);

    // Démarre la session si elle n'est pas déjà active.
    session_start();
  }

  // Stocke les données de l'utilisateur dans la session.
  $_SESSION['user'] = $userData;

  // Définit le temps de création de la session.
  $_SESSION['created_at'] = time();

  // TEMP TEST ADMIN START (à supprimer après tests)
  // Définit le temps d'expiration de la session si demandé.
  if ($sessionDuration !== null) {
    $_SESSION['expires_at'] = time() + $sessionDuration;
  } else {
    unset($_SESSION['expires_at']);
  }
  // TEMP TEST ADMIN END

}

/**
 * Vérifie si l'utilisateur a une session active et valide.
 * Détruit la session si elle a expiré.
 *
 * @return bool True si l'utilisateur est connecté, false sinon.
 */
function isUserLoggedIn(): bool
{
  // Démarre la session si elle n'est pas déjà active.
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }

  // Vérifie la présence de données utilisateur en session.
  if (!isset($_SESSION['user'])) {
    return false;
  }

  // Vérifie si la session a expiré.
  if (isset($_SESSION['expires_at']) && time() > $_SESSION['expires_at']) {
    // Détruit la session expirée.
    destroySession();
    return false;
  }

  return true;
}

/**
 * Détruit la session utilisateur.
 *
 * @return void
 */
function destroySession(): void
{
  // Démarre la session si elle n'est pas déjà active.
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }

  // Détruit toutes les variables de session.
  $_SESSION = [];

  // Supprime le cookie de session.
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
      session_name(),
      '',
      time() - 42000,
      $params['path'],
      $params['domain'],
      $params['secure'],
      $params['httponly']
    );
  }

  // Termine la session.
  session_destroy();
}
