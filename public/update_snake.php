<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url());
    exit;
}

$id = (int)$_POST['id'];
if ($id <= 0) {
    header('Location: ' . base_url());
    exit;
}

// Récupérer les données du formulaire
$name = trim($_POST['name']);
$sex = $_POST['sex'];
$morph = trim($_POST['morph']);
$birth_year = (int)$_POST['birth_year'];
$weight = $_POST['weight'] !== '' ? (float)$_POST['weight'] : null;
$default_meal_type = $_POST['default_meal_type'] !== '' ? $_POST['default_meal_type'] : null;
$comment = trim($_POST['comment']);

// Préparer la requête SQL pour la mise à jour
// L'ordre des colonnes ici doit correspondre à l'ordre des variables ci-dessous
$stmt = $pdo->prepare("
    UPDATE snakes
    SET name = ?, sex = ?, morph = ?, birth_year = ?, weight = ?, default_meal_type = ?, comment = ?
    WHERE id = ?
");

// Exécuter la requête
// L'ordre des variables DOIT correspondre à l'ordre des ? dans la requête
$stmt->execute([
    $name,
    $sex,
    $morph,
    $birth_year,
    $weight,
    $default_meal_type,
    $comment,
    $id
]);

// Redirection après la mise à jour
header('Location: ' . base_url('snake.php?id=' . $id));
exit;
