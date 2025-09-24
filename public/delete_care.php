<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php'; // Add this line

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['snake_id'])) {
    $id = (int)$_POST['id'];
    $snake_id = (int)$_POST['snake_id'];

    // Récupère les infos du soin pour le message
    $stmt = $pdo->prepare("SELECT date, care_type FROM cares WHERE id = ?");
    $stmt->execute([$id]);
    $care = $stmt->fetch();

    $stmt = $pdo->prepare("DELETE FROM cares WHERE id = ?");
    $stmt->execute([$id]);

    if ($care) {
        $_SESSION['success_message'] = "Le soin de type **" . h($care['care_type']) . "** du " . date('d/m/Y', strtotime($care['date'])) . " a été supprimé avec succès.";
    } else {
        $_SESSION['success_message'] = "Le soin a été supprimé avec succès.";
    }

    header("Location: snake.php?id=" . $snake_id);
    exit;
}
header("Location: index.php");
exit;
