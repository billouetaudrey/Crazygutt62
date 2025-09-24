<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php'; // Add this line

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['snake_id'])) {
    $id = (int)$_POST['id'];
    $snake_id = (int)$_POST['snake_id'];

    // Récupère les infos du repas pour le message
    $stmt = $pdo->prepare("SELECT date, meal_type FROM feedings WHERE id = ?");
    $stmt->execute([$id]);
    $feeding = $stmt->fetch();

    $stmt = $pdo->prepare("DELETE FROM feedings WHERE id = ?");
    $stmt->execute([$id]);

    if ($feeding) {
        $_SESSION['success_message'] = "Le repas de type **" . h($feeding['meal_type']) . "** du " . date('d/m/Y', strtotime($feeding['date'])) . " a été supprimé avec succès.";
    } else {
        $_SESSION['success_message'] = "Le repas a été supprimé avec succès.";
    }

    header("Location: snake.php?id=" . $snake_id);
    exit;
}
header("Location: index.php");
exit;
