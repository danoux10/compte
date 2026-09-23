<?php

$host = 'localhost';
$dbname = 'compte';
$username = 'root';
$password = '';

try {
  $bdd = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8",
    $username,
    $password,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
  );
} catch (Exception $e) {
  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([
    'success' => false,
    'message' => 'Erreur de connexion à la base de données.'
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

?>