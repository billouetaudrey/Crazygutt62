<?php
try {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/functions.php';

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ids = $_POST['snake_ids'] ?? [];
        if (!empty($ids)) {
            $pdo->beginTransaction();
            foreach ($ids as $id) {
                $name = trim($_POST['name'][$id] ?? '');
                $sex = $_POST['sex'][$id] ?? '';
                $morph = trim($_POST['morph'][$id] ?? '');
                $birth_year = (int)($_POST['birth_year'][$id] ?? 0);
                $weight = is_numeric($_POST['weight'][$id] ?? '') ? (float)$_POST['weight'][$id] : null;
                $comment = trim($_POST['comment'][$id] ?? '');
                $default_meal_type = $_POST['default_meal_type'][$id] ?? null;
                
                // Récupère la valeur de la case à cocher pour cet ID.
                // Si la case n'est pas cochée, elle ne sera pas dans le tableau POST.
                $ready_to_breed = isset($_POST['ready_to_breed'][$id]) ? 1 : 0; 

                $stmt = $pdo->prepare('UPDATE snakes SET name = ?, sex = ?, morph = ?, birth_year = ?, weight = ?, comment = ?, default_meal_type = ?, ready_to_breed = ? WHERE id = ?');
                $stmt->execute([$name, $sex, $morph, $birth_year, $weight, $comment, $default_meal_type, $ready_to_breed, $id]);
            }
            $pdo->commit();
            header('Location: ' . base_url('index.php'));
            exit;
        }
    }

    $snakeIds = $_GET['snake_ids'] ?? [];
    if (empty($snakeIds)) {
        die('Aucun serpent sélectionné.');
    }

    $in = str_repeat('?,', count($snakeIds) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM snakes WHERE id IN ($in)");
    $stmt->execute($snakeIds);
    $snakes = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pantherophis — Édition en masse</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <div class="brand">🐍 Pantherophis — Édition en masse</div>
        <a class="btn secondary" href="<?= base_url('index.php') ?>">Retour</a>
    </div>

    <div class="card">
        <h2>Modification en masse</h2>
        <form method="post" action="bulk_edit_snakes.php">
            <input type="hidden" name="action" value="update">
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Sexe</th>
                            <th>Phase</th>
                            <th>Année de naissance</th>
                            <th>Poids (g)</th>
                            <th>Type de repas par défaut</th>
                            <th>Commentaire</th>
                            <th>Prêt pour la reproduction</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($snakes as $s): ?>
                        <tr>
                            <input type="hidden" name="snake_ids[]" value="<?= (int)$s['id'] ?>">
                            <td><input type="text" name="name[<?= (int)$s['id'] ?>]" value="<?= h($s['name']) ?>"></td>
                            <td>
                                <select name="sex[<?= (int)$s['id'] ?>]">
                                    <option value="M" <?= ($s['sex'] === 'M') ? 'selected' : '' ?>>Mâle</option>
                                    <option value="F" <?= ($s['sex'] === 'F') ? 'selected' : '' ?>>Femelle</option>
                                    <option value="I" <?= ($s['sex'] === 'I') ? 'selected' : '' ?>>Indéfini</option>
                                </select>
                            </td>
                            <td><input type="text" name="morph[<?= (int)$s['id'] ?>]" value="<?= h($s['morph']) ?>"></td>
                            <td><input type="number" name="birth_year[<?= (int)$s['id'] ?>]" value="<?= h($s['birth_year']) ?>"></td>
                            <td><input type="number" step="0.01" name="weight[<?= (int)$s['id'] ?>]" value="<?= h($s['weight']) ?>"></td>
                            <td>
                                <select name="default_meal_type[<?= (int)$s['id'] ?>]">
                                    <option value="" <?= ($s['default_meal_type'] === null) ? 'selected' : '' ?>>(Aucun)</option>
                                    <option value="rosé" <?= ($s['default_meal_type'] === 'rosé') ? 'selected' : '' ?>>Rosé</option>
                                    <option value="blanchon" <?= ($s['default_meal_type'] === 'blanchon') ? 'selected' : '' ?>>Blanchon</option>
                                    <option value="sauteuse" <?= ($s['default_meal_type'] === 'sauteuse') ? 'selected' : '' ?>>Sauteuse</option>
                                    <option value="adulte" <?= ($s['default_meal_type'] === 'adulte') ? 'selected' : '' ?>>Adulte</option>
                                </select>
                            </td>
                            <td><input type="text" name="comment[<?= (int)$s['id'] ?>]" value="<?= h($s['comment']) ?>"></td>
                            <td>
                                <input type="checkbox" name="ready_to_breed[<?= (int)$s['id'] ?>]" value="1" <?= ($s['ready_to_breed'] == 1) ? 'checked' : '' ?>>
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
