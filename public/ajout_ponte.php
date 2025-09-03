<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Sélectionne les serpents mâles et femelles
$males = $pdo->query("SELECT * FROM snakes WHERE sex='M' ORDER BY name")->fetchAll();
$females = $pdo->query("SELECT * FROM snakes WHERE sex='F' ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $male_id = (int)$_POST['male'];
    $female_id = (int)$_POST['female'];
    $lay_date = $_POST['lay_date'] ?? null;
    $comment = $_POST['comment'] ?? '';
    $egg_count = (int)($_POST['egg_count'] ?? 0);

    if ($male_id && $female_id && $lay_date) {
        // Fix: Add a placeholder for egg_count in the SQL query
        $stmt = $pdo->prepare("INSERT INTO clutches (male_id, female_id, lay_date, egg_count, comment) VALUES (?, ?, ?, ?, ?)");
        
        // Fix: Ensure the number of values in execute() matches the placeholders
        $stmt->execute([$male_id, $female_id, $lay_date, $egg_count, $comment]);
        
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
  <title>Ajouter une ponte</title>
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
    <h2>Ajouter une ponte</h2>
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

      <label>Date de ponte :</label>
      <input type="date" name="lay_date" required><br>

      <label>Nombre d'œufs :</label>
      <input type="number" name="egg_count" value="0" min="0"><br>

      <label>Commentaire :</label>
      <textarea name="comment"></textarea><br>

      <button class="btn" type="submit">Ajouter</button>
    </form>
  </div>
</div>
</body>
</html>
