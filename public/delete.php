<?php
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);

    // Fetch photos to delete files
    $stmt = $pdo->prepare('SELECT filename FROM photos WHERE snake_id = ?');
    $stmt->execute([$id]);
    $files = $stmt->fetchAll();

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('DELETE FROM snakes WHERE id = ?');
        $stmt->execute([$id]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo 'Erreur lors de la suppression: ' . htmlspecialchars($e->getMessage());
        exit;
    }

    // Delete files from disk
    foreach ($files as $f) {
        $path = __DIR__ . '/uploads/' . $f['filename'];
        if (is_file($path)) @unlink($path);
    }
}

header('Location: ' . (defined('BASE_URL') ? BASE_URL : '/') . 'index.php');
exit;
