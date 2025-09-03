<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Méthode non autorisée.');
}

$snake_id = isset($_POST['snake_id']) ? (int)$_POST['snake_id'] : 0;
// CHANGEMENT : on récupère un seul ID, pas un tableau
$photo_id = isset($_POST['photo_id']) ? (int)$_POST['photo_id'] : 0;

// On vérifie que les deux IDs sont bien présents
if (!$snake_id || !$photo_id) {
    header("Location: " . base_url('snake.php?id=' . $snake_id) . "&photo_error=" . urlencode("ID de photo ou de serpent manquant."));
    exit;
}

// Récupère le nom du fichier pour pouvoir le supprimer du serveur
$stmt = $pdo->prepare("SELECT filename FROM photos WHERE id = ? AND snake_id = ?");
$stmt->execute([$photo_id, $snake_id]);
$photo = $stmt->fetch();

if ($photo) {
    // Supprime le fichier physique s'il existe
    $filePath = __DIR__ . '/uploads/' . $photo['filename'];
    if (file_exists($filePath)) {
        @unlink($filePath);
    }

    // Supprime l'entrée de la base de données
    $deleteStmt = $pdo->prepare("DELETE FROM photos WHERE id = ?");
    $deleteStmt->execute([$photo_id]);
}

header("Location: " . base_url('snake.php?id=' . $snake_id));
exit;
