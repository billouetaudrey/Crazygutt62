<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../config.php';

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['export'])) {
        // Export JSON libre
        $tables = ['snakes','photos','feedings','sheds','clutches'];
        $data = [];
        foreach ($tables as $t) {
            $data[$t] = $pdo->query("SELECT * FROM $t")->fetchAll(PDO::FETCH_ASSOC);
        }
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="export.json"');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    elseif (isset($_POST['import']) && isset($_FILES['json'])) {
        // Import JSON libre
        $json = file_get_contents($_FILES['json']['tmp_name']);
        $data = json_decode($json, true);
        if ($data) {
            foreach ($data as $table => $rows) {
                $pdo->exec("DELETE FROM $table");
                foreach ($rows as $row) {
                    $cols = array_keys($row);
                    $vals = array_values($row);
                    $placeholders = implode(',', array_fill(0,count($vals),'?'));
                    $pdo->prepare("INSERT INTO $table (".implode(',',$cols).") VALUES ($placeholders)")
                        ->execute($vals);
                }
            }
            $message = "✅ Import réussi !";
        } else {
            $message = "❌ Fichier JSON invalide.";
        }
    }

    elseif (isset($_POST['reset'])) {
        // Reset protégé par mot de passe
if ($_POST['password'] !== ADMIN_PASSWORD) {
            $message = "❌ Mot de passe incorrect.";
        } else {
            $tables = ['clutches','sheds','feedings','photos','snakes'];
            foreach ($tables as $t) { $pdo->exec("DELETE FROM $t"); }
            $message = "✅ Toutes les données ont été supprimées.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestion des données</title>
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
    <h2>Gestion des données</h2>

    <?php if($message): ?>
        <p><strong><?= h($message) ?></strong></p>
    <?php endif; ?>

    <form method="post">
      <button class="btn" name="export">Exporter (JSON)</button>
    </form>

    <form method="post" enctype="multipart/form-data">
      <input type="file" name="json" accept="application/json" required>
      <button class="btn" name="import">Importer (JSON)</button>
    </form>

    <form method="post" onsubmit="return confirm('Vraiment tout effacer ?')">
      <label>Mot de passe admin :</label>
      <input type="password" name="password" required>
      <button class="btn danger" name="reset">Tout effacer</button>
    </form>
  </div>
</div>
</body>
</html>
