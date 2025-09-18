<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirection si aucun repas n'est sélectionné
$feedingIds = $_GET['feeding_ids'] ?? $_POST['feeding_ids'] ?? [];
if (empty($feedingIds)) {
    header('Location: ' . base_url());
    exit;
}

// Récupérer le snake_id pour la redirection ultérieure
$snakeId = isset($_GET['snake_id']) ? (int)$_GET['snake_id'] : null;
if (!$snakeId && !empty($feedingIds)) {
    // Si l'ID du serpent n'est pas dans l'URL, le récupérer depuis le premier repas
    $stmt = $pdo->prepare("SELECT snake_id FROM feedings WHERE id = ?");
    $stmt->execute([(int)$feedingIds[0]]);
    $result = $stmt->fetch();
    $snakeId = $result['snake_id'] ?? null;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    foreach ($feedingIds as $id) {
        $date = $_POST['date'][$id] ?? date('Y-m-d');
        $prey_state = $_POST['prey_state'][$id] ?? null;
        $prey_type = $_POST['prey_type'][$id] ?? null;
        $meal_type = $_POST['meal_type'][$id] ?? null;
        $count = (int)($_POST['count'][$id] ?? 1);
        $refused = isset($_POST['refused'][$id]) ? 1 : 0;
        $notes = trim($_POST['notes'][$id] ?? '');

        $stmt = $pdo->prepare("UPDATE feedings SET date = ?, prey_state = ?, prey_type = ?, meal_type = ?, count = ?, refused = ?, notes = ? WHERE id = ?");
        $stmt->execute([$date, $prey_state, $prey_type, $meal_type, $count, $refused, $notes, $id]);
    }
    $pdo->commit();
    header('Location: ' . base_url('snake.php?id=' . (int)$snakeId));
    exit;
}

// Récupération des repas pour l'affichage
$in = str_repeat('?,', count($feedingIds) - 1) . '?';
$stmt = $pdo->prepare("SELECT * FROM feedings WHERE id IN ($in) ORDER BY date DESC");
$stmt->execute($feedingIds);
$feedings = $stmt->fetchAll();

if (empty($feedings)) {
    echo "Aucun repas trouvé.";
    exit;
}

// On récupère le nom du serpent pour le titre
$snakeStmt = $pdo->prepare("SELECT name FROM snakes WHERE id = ?");
$snakeStmt->execute([$snakeId]);
$snake = $snakeStmt->fetch();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Modification en masse des repas de <?= h($snake['name']) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <a href="<?= base_url('snake.php?id=' . (int)$snakeId) ?>" class="btn secondary">← Retour au serpent</a>
        <div class="brand">🐍 Modification en masse des repas</div>
    </div>
    <div class="card">
        <h2>Modifier les repas de <?= h($snake['name']) ?></h2>
        <form method="post">
            <input type="hidden" name="snake_id" value="<?= (int)$snakeId ?>">
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>État de la proie</th>
                            <th>Type de rongeur</th>
                            <th>Taille</th>
                            <th>Nombre</th>
                            <th>Refusé</th>
                            <th>Commentaire</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedings as $f): ?>
                            <tr>
                                <input type="hidden" name="feeding_ids[]" value="<?= (int)$f['id'] ?>">
                                <td><input type="date" name="date[<?= (int)$f['id'] ?>]" value="<?= h($f['date']) ?>" required></td>
                                <td>
                                    <select name="prey_state[<?= (int)$f['id'] ?>]">
                                        <option value="vivant" <?= ($f['prey_state'] === 'vivant') ? 'selected' : '' ?>>Vivant</option>
                                        <option value="mort" <?= ($f['prey_state'] === 'mort') ? 'selected' : '' ?>>Mort</option>
                                        <option value="congelé" <?= ($f['prey_state'] === 'congelé') ? 'selected' : '' ?>>Congelé</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="prey_type[<?= (int)$f['id'] ?>]">
                                        <option value="">(Aucun)</option>
                                        <option value="souris" <?= ($f['prey_type'] === 'souris') ? 'selected' : '' ?>>Souris</option>
                                        <option value="rat" <?= ($f['prey_type'] === 'rat') ? 'selected' : '' ?>>Rat</option>
                                        <option value="mastomys" <?= ($f['prey_type'] === 'mastomys') ? 'selected' : '' ?>>Mastomys</option>
                                        <option value="autres" <?= ($f['prey_type'] === 'autres') ? 'selected' : '' ?>>Autres</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="meal_type[<?= (int)$f['id'] ?>]">
                                        <option value="">(Aucun)</option>
                                        <option value="rosé" <?= ($f['meal_type'] === 'rosé') ? 'selected' : '' ?>>Rosé</option>
                                        <option value="blanchon" <?= ($f['meal_type'] === 'blanchon') ? 'selected' : '' ?>>Blanchon</option>
                                        <option value="sauteuse" <?= ($f['meal_type'] === 'sauteuse') ? 'selected' : '' ?>>Sauteuse</option>
                                        <option value="adulte" <?= ($f['meal_type'] === 'adulte') ? 'selected' : '' ?>>Adulte</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="count[<?= (int)$f['id'] ?>]" value="<?= (int)$f['count'] ?>" min="0">
                                </td>
                                <td>
                                    <input type="checkbox" name="refused[<?= (int)$f['id'] ?>]" <?= $f['refused'] ? 'checked' : '' ?>>
                                </td>
                                <td>
                                    <textarea name="notes[<?= (int)$f['id'] ?>]"><?= h($f['notes']) ?></textarea>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 1rem;">
                <button type="submit" class="btn ok">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
