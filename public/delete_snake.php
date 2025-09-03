<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérifier que la requête est de type POST et qu'un ID de serpent a été fourni.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header('Location: ' . base_url());
    exit;
}

$id = (int)$_POST['id'];

// Définir le chemin de base pour les uploads.
define('UPLOAD_DIR', __DIR__ . '/uploads/');

try {
    // Démarrer une transaction pour s'assurer que toutes les suppressions se font ou aucune.
    $pdo->beginTransaction();

    // 1. Récupérer les noms des fichiers photos associés au serpent.
    $photosStmt = $pdo->prepare("SELECT filename FROM photos WHERE snake_id = ?");
    $photosStmt->execute([$id]);
    $photos = $photosStmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Supprimer les fichiers physiques sur le disque dur.
    foreach ($photos as $filename) {
        $filepath = UPLOAD_DIR . $filename;
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    // 3. Supprimer les enregistrements des photos de la base de données.
    $deletePhotosStmt = $pdo->prepare("DELETE FROM photos WHERE snake_id = ?");
    $deletePhotosStmt->execute([$id]);

    // 4. Supprimer tous les repas du serpent.
    $deleteFeedingsStmt = $pdo->prepare("DELETE FROM feedings WHERE snake_id = ?");
    $deleteFeedingsStmt->execute([$id]);

    // 5. Supprimer toutes les mues du serpent.
    $deleteShedsStmt = $pdo->prepare("DELETE FROM sheds WHERE snake_id = ?");
    $deleteShedsStmt->execute([$id]);

    // 6. Supprimer l'enregistrement du serpent lui-même.
    $deleteSnakeStmt = $pdo->prepare("DELETE FROM snakes WHERE id = ?");
    $deleteSnakeStmt->execute([$id]);

    // Valider la transaction si toutes les requêtes ont réussi.
    $pdo->commit();

    // Rediriger vers la page d'accueil avec un message de succès.
    header('Location: ' . base_url('index.php?success=Serpent_supprimé'));
    exit;

} catch (PDOException $e) {
    // En cas d'erreur, annuler la transaction pour ne rien supprimer.
    $pdo->rollBack();
    // Rediriger vers la page du serpent avec un message d'erreur.
    header('Location: ' . base_url('snake.php?id=' . $id . '&error=Echec_suppression'));
    exit;
}
