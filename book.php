<?php
require 'connexion-bdd.php';

if (!isset($_GET['isbn']) || empty($_GET['isbn'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Missing isbn']);
  exit;
}

$isbn = $_GET['isbn'];

$sql = "SELECT
  Livre.isbn,
  Livre.titre,
  Livre.resume,
  Livre.image,
  Livre.annee,
  GROUP_CONCAT(CONCAT_WS(' ', Personne.prenom, Personne.nom) SEPARATOR ' • ') AS auteur,
  Editeur.libelle AS editeur,
  Langue.libelle AS langue,
  Livre.nbpages
FROM Livre
LEFT JOIN Auteur ON Livre.isbn = Auteur.idLivre
LEFT JOIN Personne ON Auteur.idPersonne = Personne.id
LEFT JOIN Editeur ON Livre.editeur = Editeur.id
LEFT JOIN Langue ON Livre.langue = Langue.id
WHERE Livre.isbn = :isbn
GROUP BY Livre.isbn
LIMIT 1";

$stmt = $bdd->prepare($sql);
$stmt->execute([':isbn' => $isbn]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
  http_response_code(404);
  echo json_encode(['error' => 'Not found']);
  exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($book, JSON_UNESCAPED_UNICODE);
