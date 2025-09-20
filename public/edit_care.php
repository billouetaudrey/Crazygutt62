<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirection si l'ID n'est pas fourni
if (!isset($_GET['id'])) {
    header('Location: ' . base_url());
    exit;
}

$id = (int)$_GET['id'];

// Récupération du soin existant
$careStmt = $pdo->prepare("SELECT * FROM cares WHERE id = ?");
$careStmt->execute([$id]);
$care = $careStmt->fetch();

if (!$care) {
    echo "Soin non trouvé.";
    exit;
}

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $care_type = $_POST['care_type'] ?? null;
    $date = $_POST['date'] ?? null;
    $comment = $_POST['comment'] ?? '';

    // Vérification des données
    if ($care_type && $date) {
        $updateStmt = $pdo->prepare("UPDATE cares SET care_type = ?, date = ?, comment = ? WHERE id = ?");
        $updateStmt->execute([$care_type, $date, $comment, $id]);
        
        // Redirection vers la page du serpent après la mise à jour
        header("Location: snake.php?id=" . $care['snake_id']);
        exit;
    }
}

// Récupération des informations du serpent pour l'affichage
$snakeStmt = $pdo->prepare("SELECT name FROM snakes WHERE id = ?");
$snakeStmt->execute([$care['snake_id']]);
$snake = $snakeStmt->fetch();
$snakeName = $snake['name'] ?? 'Serpent inconnu';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un soin</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/theme.js" defer></script>
</head>
<body>
<div class="container">
    <div class="header">
        <a href="snake.php?id=<?= (int)$care['snake_id'] ?>" class="btn secondary">← Retour</a>
        <div class="brand">✨ Modifier un soin pour <?= h($snakeName) ?></div>
    </div>
    <div class="card">
        <form method="post">
            <input type="hidden" name="id" value="<?= (int)$care['id'] ?>">
            <label for="care_type">Type de soin :</label>
            <select id="care_type" name="care_type" required>
                <option value="Médicament" <?= ($care['care_type'] === 'Médicament') ? 'selected' : '' ?>>Médicament</option>
                <option value="Blessure" <?= ($care['care_type'] === 'Blessure') ? 'selected' : '' ?>>Blessure</option>
                <option value="Parasite" <?= ($care['care_type'] === 'Parasite') ? 'selected' : '' ?>>Parasite</option>
                <option value="Vétérinaire" <?= ($care['care_type'] === 'Vétérinaire') ? 'selected' : '' ?>>Vétérinaire</option>
                <option value="Autre" <?= ($care['care_type'] === 'Autre') ? 'selected' : '' ?>>Autre</option>
            </select>

            <label for="date">Date du soin :</label>
            <input type="date" id="date" name="date" value="<?= h($care['date']) ?>" required>

            <label for="comment">Commentaire :</label>
            <textarea id="comment" name="comment" rows="4" placeholder="Détail du soin"><?= h($care['comment']) ?></textarea>
            
            <div style="margin-top:1rem;">
                <button type="submit" class="btn ok">Enregistrer les modifications</button>
                <a href="snake.php?id=<?= (int)$care['snake_id'] ?>" class="btn secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
