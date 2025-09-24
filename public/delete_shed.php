<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php'; // Add this line

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['snake_id'])) {
    $id = (int)$_POST['id'];
    $snake_id = (int)$_POST['snake_id'];

    // Récupère la date de la mue pour le message de succès
    $stmt = $pdo->prepare("SELECT date FROM sheds WHERE id = ?");
    $stmt->execute([$id]);
    $shed = $stmt->fetch();

    $stmt = $pdo->prepare("DELETE FROM sheds WHERE id = ?");
    $stmt->execute([$id]);

    if ($shed) {
        $_SESSION['success_message'] = "La mue du " . date('d/m/Y', strtotime($shed['date'])) . " a été supprimée avec succès.";
    } else {
        $_SESSION['success_message'] = "La mue a été supprimée avec succès.";
    }

    header("Location: snake.php?id=" . $snake_id);
    exit;
}
header("Location: index.php");
exit;
