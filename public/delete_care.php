<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérifie que la requête est de type POST et que les IDs sont présents
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['snake_id'])) {
    
    // Récupère et valide les IDs
    $careId = (int)$_POST['id'];
    $snakeId = (int)$_POST['snake_id'];

    // Prépare et exécute la requête de suppression
    $stmt = $pdo->prepare("DELETE FROM cares WHERE id = ? AND snake_id = ?");
    $stmt->execute([$careId, $snakeId]);

    // Redirige vers la page du serpent après la suppression
    header("Location: snake.php?id=" . $snakeId);
    exit;
} else {
    // Si la requête n'est pas valide, redirige vers la page d'accueil
    header("Location: index.php");
    exit;
}
