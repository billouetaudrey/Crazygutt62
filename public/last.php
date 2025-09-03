<?php
require_once __DIR__ . '/../includes/db.php';
// Exemple de connexion à la base de données
// Assurez-vous d'avoir une connexion valide ($pdo)
// ...
try {
    // Récupérer l'ID du serpent (exemple)
    $snake_id = 4; 

    // Préparer et exécuter la requête pour obtenir la dernière date de repas
    $stmt = $pdo->prepare("SELECT date FROM feedings WHERE snake_id = :snake_id ORDER BY date DESC LIMIT 1");
    $stmt->bindValue(':snake_id', $snake_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $last_feeding_date = $stmt->fetchColumn();

    if ($last_feeding_date) {
        $last_feeding_timestamp = strtotime($last_feeding_date);
        $current_timestamp = time(); // Temps actuel en secondes
        $diff_in_seconds = $current_timestamp - $last_feeding_timestamp;
        $diff_in_days = floor($diff_in_seconds / (60 * 60 * 24));

        if ($diff_in_days > 7) {
            echo '<div class="alert danger">Attention : Le dernier repas de ce serpent remonte à plus de 7 jours ! (Dernier repas : ' . date('d/m/Y', $last_feeding_timestamp) . ')</div>';
        }
    }

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
