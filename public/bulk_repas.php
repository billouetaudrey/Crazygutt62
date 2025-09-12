<?php
try {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/functions.php';

    // Handle form submission to add feedings
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ids = $_POST['snake_ids'] ?? [];
        $meal_type = trim($_POST['meal_type'] ?? '');
        $prey_type = trim($_POST['prey_type'] ?? ''); // Added prey_type
        $date = trim($_POST['date'] ?? '');
        $count = (int)($_POST['count'] ?? 1); // Added count
        $refused = isset($_POST['refused']) ? 1 : 0;
        $notes = trim($_POST['notes'] ?? '');

        if (empty($ids) || empty($meal_type) || empty($date)) {
            die('Erreur : Tous les champs requis doivent être remplis.');
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO feedings (snake_id, date, meal_type, prey_type, count, refused, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($ids as $id) {
            $stmt->execute([$id, $date, $meal_type, $prey_type, $count, $refused, $notes]);
        }
        $pdo->commit();
        header('Location: ' . base_url('index.php'));
        exit;
    }

    // Get snake IDs and pre-selected meal type from URL
    $snakeIds = $_GET['snake_ids'] ?? [];
    $preselectedMealType = $_GET['meal_type'] ?? '';

    if (empty($snakeIds)) {
        die('Aucun serpent sélectionné.');
    }

    // Fetch snake data
    $in = str_repeat('?,', count($snakeIds) - 1) . '?';
    $stmt = $pdo->prepare("SELECT id, name, default_meal_type FROM snakes WHERE id IN ($in) ORDER BY name");
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
        <h2>Ajouter un repas pour <?= count($snakes) ?> serpent(s)</h2>
        <form method="post" action="bulk_repas.php">
            <?php foreach ($snakes as $s): ?>
                <input type="hidden" name="snake_ids[]" value="<?= (int)$s['id'] ?>">
            <?php endforeach; ?>
            
            <div class="grid">
                <div>
                    <label>Date du repas</label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label>Type de repas</label>
                    <select name="meal_type" required>
                        <option value="">(Sélectionner)</option>
                        <option value="rosé" <?= ($preselectedMealType == 'rosé') ? 'selected' : '' ?>>Rosé</option>
                        <option value="blanchon" <?= ($preselectedMealType == 'blanchon') ? 'selected' : '' ?>>Blanchon</option>
                        <option value="sauteuse" <?= ($preselectedMealType == 'sauteuse') ? 'selected' : '' ?>>Sauteuse</option>
                        <option value="adulte" <?= ($preselectedMealType == 'adulte') ? 'selected' : '' ?>>Adulte</option>
                        <option value="rat">Rat</option>
                    </select>
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
            <h3>Serpents concernés :</h3>
            <ul>
                <?php foreach ($snakes as $s): ?>
                    <li><?= h($s['name']) ?> (Repas par défaut : <?= h($s['default_meal_type'] ?: 'Non défini') ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
</body>
</html>
