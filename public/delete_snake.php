<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérifier que la requête est de type POST et qu'un ID de serpent a été fourni.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header('Location: ' . base_url('index.php')); // Redirection par défaut en cas d'erreur
    exit;
}

$id = (int)$_POST['id'];
// 1. Gérer la redirection (vient de sales.php ou index.php)
$redirect_to = $_POST['redirect_to'] ?? 'index.php'; 

// Définir les chemins de base pour les uploads (ORIGINAL) et les vignettes (THUMBNAILS)
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('THUMB_DIR', __DIR__ . '/../uploads/thumbnails/'); // NOUVEAU: Ajout du chemin des vignettes

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
        $thumbpath = THUMB_DIR . $filename; // NOUVEAU: Chemin de la vignette
        
        // Supprimer le fichier original
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        // Supprimer la vignette
        if (file_exists($thumbpath)) {
            unlink($thumbpath);
        }
    }

    // 3. Supprimer les enregistrements des photos de la base de données.
    $deletePhotosStmt = $pdo->prepare("DELETE FROM photos WHERE snake_id = ?");
    $deletePhotosStmt->execute([$id]);

    // 4. Supprimer les soins (cares) du serpent. (Ajouté car non présent dans votre version mais souvent nécessaire)
    $pdo->prepare('DELETE FROM cares WHERE snake_id = ?')->execute([$id]);

    // 5. Supprimer tous les repas du serpent.
    $deleteFeedingsStmt = $pdo->prepare("DELETE FROM feedings WHERE snake_id = ?");
    $deleteFeedingsStmt->execute([$id]);

    // 6. Supprimer toutes les mues du serpent.
    $deleteShedsStmt = $pdo->prepare("DELETE FROM sheds WHERE snake_id = ?");
    $deleteShedsStmt->execute([$id]);

    // 7. Supprimer l'enregistrement du serpent lui-même.
    $deleteSnakeStmt = $pdo->prepare("DELETE FROM snakes WHERE id = ?");
    $deleteSnakeStmt->execute([$id]);

    // Valider la transaction si toutes les requêtes ont réussi.
    $pdo->commit();

    // Rediriger vers la page demandée (index.php ou sales.php)
    header('Location: ' . base_url($redirect_to . '?deleted=' . $id));
    exit;

} catch (PDOException $e) {
    // En cas d'erreur, annuler la transaction pour ne rien supprimer.
    $pdo->rollBack();
    // Rediriger vers la page des ventes/index avec un message d'erreur.
    header('Location: ' . base_url($redirect_to . '?error=Echec_suppression'));
    exit;
}
