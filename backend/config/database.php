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
  die('Erreur : ' . $e->getMessage());
}

?>