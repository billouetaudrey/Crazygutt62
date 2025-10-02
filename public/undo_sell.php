<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['snake_id'])) {
    header('Location: ' . base_url('sales.php'));
    exit;
}

$snake_id = (int)$_POST['snake_id'];

try {
    // Rétablir le serpent dans l'inventaire principal
    $stmt = $pdo->prepare('
        UPDATE snakes 
        SET 
            sold = FALSE, 
            sell_date = NULL, 
            price = NULL
        WHERE id = ?
    ');
    $stmt->execute([$snake_id]);

    // Rediriger vers la liste principale
    header('Location: ' . base_url('index.php?unsold=' . $snake_id));
    exit;

} catch (PDOException $e) {
    die("Erreur de base de données lors de l'annulation de la vente : " . $e->getMessage());
}
?>
