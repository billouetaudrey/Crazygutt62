<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirection si l'ID n'est pas fourni
if (!isset($_GET['id'])) {
    header('Location: ' . base_url());
    exit;
}

$id = (int)$_GET['id'];

// Récupération des informations du serpent
$snakeStmt = $pdo->prepare("SELECT * FROM snakes WHERE id = ?");
$snakeStmt->execute([$id]);
$snake = $snakeStmt->fetch();

if (!$snake) {
    echo "Serpent non trouvé.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Modifier <?= h($snake['name']) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <a href="<?= base_url('snake.php?id=' . (int)$snake['id']) ?>" class="btn secondary">← Annuler</a>
        <div class="brand">✏️ Modifier <?= h($snake['name']) ?></div>
        <div class="empty"></div>
    </div>
    
    <div class="card">
        <h2>Modifier les informations</h2>
        <form action="update_snake.php" method="post">
            <input type="hidden" name="id" value="<?= (int)$snake['id'] ?>">
            <div class="grid">
        <div>
            <label>Nom</label>
            <input type="text" name="name" value="<?= h($snake['name']) ?>" required>
        </div>
                <div>
                    <label>Sexe</label>
                    <select name="sex" required>
                        <option value="M" <?= ($snake['sex'] === 'M') ? 'selected' : '' ?>>Mâle</option>
                        <option value="F" <?= ($snake['sex'] === 'F') ? 'selected' : '' ?>>Femelle</option>
                        <option value="I" <?= ($snake['sex'] === 'I') ? 'selected' : '' ?>>Indéfini</option>
                    </select>
                </div>
                <div>
                    <label>Phase (morph)</label>
                    <input type="text" name="morph" value="<?= h($snake['morph']) ?>">
</div>
<div>
    <label>Type de repas par défaut</label>
    <select name="default_meal_type">
        <option value="">(Aucun)</option>
        <option value="rosé" <?= ($snake['default_meal_type'] === 'rosé') ? 'selected' : '' ?>>Rosé</option>
        <option value="blanchon" <?= ($snake['default_meal_type'] === 'blanchon') ? 'selected' : '' ?>>Blanchon</option>
        <option value="sauteuse" <?= ($snake['default_meal_type'] === 'sauteuse') ? 'selected' : '' ?>>Sauteuse</option>
        <option value="adulte" <?= ($snake['default_meal_type'] === 'adulte') ? 'selected' : '' ?>>Adulte</option>
    </select>
</div>                
</div>
                <div>
                    <label>Année de naissance *</label>
                    <input type="number" name="birth_year" min="1900" max="<?= (int)date('Y') ?>" value="<?= (int)$snake['birth_year'] ?>" required>
                </div>
                <div>
                    <label>Poids (g, facultatif)</label>
                    <input type="number" step="0.01" name="weight" value="<?= ($snake['weight'] !== null) ? h($snake['weight']) : '' ?>" placeholder="Ex. 120">
                </div>
                <div>
                    <label>Commentaire</label>
                    <textarea name="comment" rows="4"><?= h($snake['comment'] ?? '') ?></textarea>
                </div>
            </div>
            <div style="margin-top:.8rem;">
                <button type="submit" class="btn ok">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
