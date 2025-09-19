<?php
try {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/functions.php';

    // Regrouper les serpents sélectionnés par leur type de repas par défaut
    $snakeIds = $_GET['snake_ids'] ?? [];
    if (empty($snakeIds)) {
        die('Aucun serpent sélectionné.');
    }

    $in = str_repeat('?,', count($snakeIds) - 1) . '?';
    $stmt = $pdo->prepare("SELECT id, name, default_meal_type FROM snakes WHERE id IN ($in) ORDER BY default_meal_type, name");
    $stmt->execute($snakeIds);
    $selectedSnakes = $stmt->fetchAll();

    $groupedSnakes = [];
    foreach ($selectedSnakes as $s) {
        $mealType = $s['default_meal_type'] ?: 'Non défini';
        if (!isset($groupedSnakes[$mealType])) {
            $groupedSnakes[$mealType] = [];
        }
        $groupedSnakes[$mealType][] = $s;
    }

    // Gérer la soumission du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $date = trim($_POST['date'] ?? '');
        $prey_type = trim($_POST['prey_type'] ?? '');
        $count = (int)($_POST['count'] ?? 1);
        $refused = isset($_POST['refused']) ? 1 : 0;
        $notes = trim($_POST['notes'] ?? '');

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO feedings (snake_id, date, meal_type, prey_type, count, refused, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');

        foreach ($_POST['groups'] as $meal_type_group => $group_data) {
            $meal_type = $group_data['rongeur_type'] . ' ' . $group_data['rongeur_size'];
            $snake_ids = $group_data['snake_ids'] ?? [];

            foreach ($snake_ids as $id) {
                $stmt->execute([(int)$id, $date, $meal_type, $prey_type, $count, $refused, $notes]);
            }
        }

        $pdo->commit();
        header('Location: ' . base_url('index.php'));
        exit;
    }

} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pantherophis — Ajouter un repas en masse</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/theme.js" defer></script>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="brand">🐍 Pantherophis — Ajouter un repas en masse</div>
        <a class="btn secondary" href="<?= base_url('index.php') ?>">Retour</a>
    </div>

    <div class="card">
        <h2>Ajouter un repas pour <?= count($selectedSnakes) ?> serpent(s)</h2>
        
        <form method="post" action="bulk_repas.php">
            
            <?php foreach ($groupedSnakes as $mealType => $snakes): ?>
                <div class="group-repas" style="border: 1px solid #ccc; padding: 1rem; margin-top: 1rem;">
                    <h3>Groupe : <?= h(ucfirst($mealType)) ?></h3>
                    
                    <p>Serpents concernés :</p>
                    <ul>
                        <?php foreach ($snakes as $s): ?>
                            <li><?= h($s['name']) ?></li>
                            <input type="hidden" name="groups[<?= h($mealType) ?>][snake_ids][]" value="<?= (int)$s['id'] ?>">
                        <?php endforeach; ?>
                    </ul>

                    <div class="grid">
                        <div>
                            <label>Type de rongeur</label>
                            <select name="groups[<?= h($mealType) ?>][rongeur_type]" required>
                                <option value="souris" <?= (strpos($mealType, 'souris') !== false) ? 'selected' : '' ?>>Souris</option>
                                <option value="rat" <?= (strpos($mealType, 'rat') !== false) ? 'selected' : '' ?>>Rat</option>
                                <option value="mastomys" <?= (strpos($mealType, 'mastomys') !== false) ? 'selected' : '' ?>>Mastomys</option>
                            </select>
                        </div>
                        <div>
                            <label>Taille du rongeur</label>
                            <select name="groups[<?= h($mealType) ?>][rongeur_size]" required>
                                <option value="rosé" <?= (strpos($mealType, 'rosé') !== false) ? 'selected' : '' ?>>Rosé</option>
                                <option value="blanchon" <?= (strpos($mealType, 'blanchon') !== false) ? 'selected' : '' ?>>Blanchon</option>
                                <option value="sauteuse" <?= (strpos($mealType, 'sauteuse') !== false) ? 'selected' : '' ?>>Sauteuse</option>
                                <option value="adulte" <?= (strpos($mealType, 'adulte') !== false) ? 'selected' : '' ?>>Adulte</option>
                            </select>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <hr style="margin-top: 2rem;">

            <h3>Détails du repas (communs à tous les serpents)</h3>
            <div class="grid">
                <div>
                    <label>Date du repas</label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label>Type de proie</label>
                    <select name="prey_type" required>
                        <option value="vivant">Vivant</option>
                        <option value="mort" selected>Mort</option>
                        <option value="congelé">Congelé</option>
                    </select>
                </div>
                <div>
                    <label>Nombre de proies</label>
                    <select name="count" required>
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                    </select>
                </div>
            </div>
            
            <div style="margin-top: 1rem;">
                <label>
                    <input type="checkbox" name="refused" value="1"> Repas refusé
                </label>
            </div>
            <div style="margin-top: 1rem;">
                <label>Notes (facultatif)</label>
                <textarea name="notes" placeholder="Ex. A mangé facilement, a eu du mal, etc."></textarea>
            </div>
            
            <div style="margin-top: 1rem;">
                <button type="submit" class="btn ok">Enregistrer le repas</button>
            </div>
        </form>
        
        <div style="margin-top: 2rem;">
            <h3>Récapitulatif de la sélection :</h3>
            <ul>
                <?php foreach ($selectedSnakes as $s): ?>
                    <li><?= h($s['name']) ?> (Repas par défaut : <?= h($s['default_meal_type'] ?: 'Non défini') ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
</body>
</html>
