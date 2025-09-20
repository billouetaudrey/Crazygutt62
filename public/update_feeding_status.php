<?php
try {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/functions.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feeding_ids'])) {
        $feeding_ids = $_POST['feeding_ids'];
        $ids_placeholder = implode(',', array_fill(0, count($feeding_ids), '?'));

        // Mettre à jour le statut 'pending' de 1 à 0
        $stmt = $pdo->prepare("UPDATE feedings SET pending = 0 WHERE id IN ($ids_placeholder)");
        $stmt->execute($feeding_ids);
    }
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

header('Location: ' . base_url('index.php'));
exit;
?>
