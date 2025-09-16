<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Sélectionnez les mâles et les femelles de plus de 2 ans
$current_year = (int)date('Y');
$breeding_age_year = $current_year - 2;

$males = $pdo->prepare("SELECT * FROM snakes WHERE sex='M' AND birth_year <= ? ORDER BY name");
$males->execute([$breeding_age_year]);
$males = $males->fetchAll();

$females = $pdo->prepare("SELECT * FROM snakes WHERE sex='F' AND birth_year <= ? ORDER BY name");
$females->execute([$breeding_age_year]);
$females = $females->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $male_id = (int)($_POST['male'] ?? 0);
    $female_id = (int)($_POST['female'] ?? 0);
    $pairing_date = $_POST['pairing_date'] ?? null;
    $comment = $_POST['comment'] ?? '';

    if ($male_id && $female_id && $pairing_date) {
        // Calcule la date de ponte estimée (date d'accouplement + 35 jours)
        $gestation_date = date('Y-m-d', strtotime($pairing_date . ' +35 days'));

        // Prépare la requête SQL pour insérer un nouvel accouplement
        $stmt = $pdo->prepare("INSERT INTO gestations (male_id, female_id, pairing_date, gestation_date, comment) VALUES (?, ?, ?, ?, ?)");
        
        // Exécute la requête avec les données soumises
        $stmt->execute([$male_id, $female_id, $pairing_date, $gestation_date, $comment]);
        
        // Redirige vers la page principale après l'insertion
        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un accouplement</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/theme.js" defer></script>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="brand"><a class="btn secondary" href="index.php">← Retour</a></div>
        <button class="theme-toggle" onclick="toggleTheme()">🌙/☀️</button>
    </div>

    <div class="card">
        <h2>Ajouter un accouplement</h2>
        <form method="post">
            <label>Mâle :</label>
            <select name="male" required>
                <option value="">-- Choisir --</option>
                <?php foreach($males as $m): ?>
                    <option value="<?= $m['id'] ?>"><?= h($m['name']) ?></option>
                <?php endforeach; ?>
            </select><br>

            <label>Femelle :</label>
            <select name="female" required>
                <option value="">-- Choisir --</option>
                <?php foreach($females as $f): ?>
                    <option value="<?= $f['id'] ?>"><?= h($f['name']) ?></option>
                <?php endforeach; ?>
            </select><br>

            <label>Date d'accouplement :</label>
            <input type="date" name="pairing_date" required><br>

            <label>Commentaire :</label>
            <textarea name="comment"></textarea><br>

            <button class="btn" type="submit">Ajouter</button>
        </form>
    </div>
</div>
</body>
</html>
