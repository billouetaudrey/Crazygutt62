<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_GET['id'])) {
    header('Location: ' . base_url());
    exit;
}

$id = (int)$_GET['id'];

// Fetch feeding record
$feedingStmt = $pdo->prepare("SELECT * FROM feedings WHERE id = ?");
$feedingStmt->execute([$id]);
$feeding = $feedingStmt->fetch();

if (!$feeding) {
    echo "Repas non trouvé.";
    exit;
}

// Fetch snake info
$snakeStmt = $pdo->prepare("SELECT name FROM snakes WHERE id = ?");
$snakeStmt->execute([$feeding['snake_id']]);
$snake = $snakeStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $meal_type = $_POST['meal_type'] ?: null; // Taille du rongeur (rosé, blanchon...)
    $prey_type = $_POST['prey_type'] ?: null; // Type de rongeur (souris, rats...)
    $prey_state = $_POST['prey_state'] ?: null; // État de la proie (vivant, mort...)
    $count = (int)$_POST['count'];
    $refused = isset($_POST['refused']) ? 1 : 0;
    $notes = $_POST['notes'] ?: null;

    $stmt = $pdo->prepare("UPDATE feedings SET date = ?, meal_type = ?, prey_type = ?, prey_state = ?, count = ?, refused = ?, notes = ? WHERE id = ?");
    $stmt->execute([$date, $meal_type, $prey_type, $prey_state, $count, $refused, $notes, $id]);
    
    header('Location: ' . base_url('snake.php?id=' . (int)$feeding['snake_id']));
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Modifier le repas — <?= h($snake['name']) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <a href="<?= base_url('snake.php?id=' . (int)$feeding['snake_id']) ?>" class="btn secondary">← Retour au serpent</a>
        <div class="brand">Modifier le repas de <?= h($snake['name']) ?></div>
        <div class="empty"></div>
    </div>
    <div class="card">
        <form method="post" action="edit_feeding.php?id=<?= (int)$id ?>">
            <div class="grid">
                <div>
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" value="<?= h($feeding['date']) ?>" required>
                </div>
                <div>
                    <label for="prey_state">Type de proie</label>
                    <select id="prey_state" name="prey_state">
                        <option value="vivant" <?= ($feeding['prey_state'] === 'vivant') ? 'selected' : '' ?>>Vivant</option>
                        <option value="mort" <?= ($feeding['prey_state'] === 'mort') ? 'selected' : '' ?>>Mort</option>
                        <option value="congelé" <?= ($feeding['prey_state'] === 'congelé') ? 'selected' : '' ?>>Congelé</option>
                    </select>
                </div>
                <div>
                    <label for="prey_type">Type de rongeur</label>
                    <select id="prey_type" name="prey_type">
                        <option value="">(Aucun)</option>
                        <option value="souris" <?= ($feeding['prey_type'] === 'souris') ? 'selected' : '' ?>>Souris</option>
                        <option value="rats" <?= ($feeding['prey_type'] === 'rats') ? 'selected' : '' ?>>Rats</option>
                        <option value="mastomys" <?= ($feeding['prey_type'] === 'mastomys') ? 'selected' : '' ?>>Mastomys</option>
                        <option value="autres" <?= ($feeding['prey_type'] === 'autres') ? 'selected' : '' ?>>Autres</option>
                    </select>
                </div>
                <div>
                    <label for="meal_type">Taille du rongeur</label>
                    <select id="meal_type" name="meal_type">
                        <option value="">(Aucun)</option>
                        <option value="rosé" <?= ($feeding['meal_type'] === 'rosé') ? 'selected' : '' ?>>Rosé</option>
                        <option value="blanchon" <?= ($feeding['meal_type'] === 'blanchon') ? 'selected' : '' ?>>Blanchon</option>
                        <option value="sauteuse" <?= ($feeding['meal_type'] === 'sauteuse') ? 'selected' : '' ?>>Sauteuse</option>
                        <option value="adulte" <?= ($feeding['meal_type'] === 'adulte') ? 'selected' : '' ?>>Adulte</option>
                    </select>
                </div>
                <div>
                    <label for="count">Nombre</label>
                    <input type="number" id="count" name="count" value="<?= (int)$feeding['count'] ?>" min="0" required>
                </div>
                <div style="grid-column: 1 / 3;">
                    <label>
                        <input type="checkbox" name="refused" <?= $feeding['refused'] ? 'checked' : '' ?>> Repas refusé
                    </label>
                </div>
                <div style="grid-column: 1 / 3;">
                    <label for="notes">Commentaire</label>
                    <textarea id="notes" name="notes" rows="3"><?= h($feeding['notes']) ?></textarea>
                </div>
            </div>
            <div style="margin-top: 1rem;">
                <button type="submit" class="btn ok">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
