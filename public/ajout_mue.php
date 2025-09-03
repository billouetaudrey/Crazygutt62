<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Récupère la liste des serpents
$snakes = $pdo->query("SELECT * FROM snakes ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? null;
    $complete = isset($_POST['complete']) ? 1 : 0;
    $comment = $_POST['comment'] ?? '';

    if (!empty($_POST['snakes']) && $date) {
        $stmt = $pdo->prepare("INSERT INTO sheds (snake_id, date, complete, comment) VALUES (?, ?, ?, ?)");
        foreach ($_POST['snakes'] as $sid) {
            $stmt->execute([$sid, $date, $complete, $comment]);
        }
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
  <title>Ajouter une mue</title>
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
    <h2>Ajouter une mue</h2>
    <form method="post">
      <label>Date :</label>
      <input type="date" name="date" required><br>

      <label><input type="checkbox" name="complete" checked> Mue complète</label><br>

      <label>Commentaire :</label>
      <textarea name="comment"></textarea><br>

      <h3>Sélectionnez les serpents :</h3>
      <?php foreach($snakes as $s): ?>
        <label><input type="checkbox" name="snakes[]" value="<?= $s['id'] ?>"> <?= h($s['name']) ?></label><br>
      <?php endforeach; ?>

      <button class="btn" type="submit">Ajouter</button>
    </form>
  </div>
</div>
</body>
</html>
