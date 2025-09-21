<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../config.php';

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['export'])) {
        // Export JSON
        $tables = ['snakes','clutches','feedings','photos','sheds','snake_images','babies','baby_parents','cares','gestations'];
        $data = [];
        foreach ($tables as $t) {
            $data[$t] = $pdo->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
        }
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="export.json"');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    } elseif (isset($_POST['import']) && isset($_FILES['json'])) {
        // Import JSON
        $json = file_get_contents($_FILES['json']['tmp_name']);
        $data = json_decode($json, true);
        if ($data) {
            // Ordre d'importation pour gérer les clés étrangères
            $import_order = ['snakes', 'clutches', 'feedings', 'photos', 'sheds', 'snake_images','babies','baby_parents','cares','gestations'];
            foreach ($import_order as $table) {
                if (isset($data[$table])) {
                    // Désactivation des vérifications de clés étrangères pour l'importation
                    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0;');
                    
                    $pdo->exec("DELETE FROM `$table`");
                    foreach ($data[$table] as $row) {
                        $cols = array_keys($row);
                        $vals = array_values($row);
                        $placeholders = implode(',', array_fill(0, count($vals), '?'));
                        $stmt = $pdo->prepare("INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES ($placeholders)");
                        $stmt->execute($vals);
                    }
                    
                    // Réactivation des vérifications
                    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
                }
            }
            $message = "✅ Importation réussie !";
        } else {
            $message = "❌ Fichier JSON invalide.";
        }
    } elseif (isset($_POST['reset'])) {
        // Reset avec mot de passe
        if ($_POST['password'] !== ADMIN_PASSWORD) {
            $message = "❌ Mot de passe incorrect.";
        } else {
            // Désactivation des vérifications pour la suppression
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0;');
            
            // Ordre de suppression pour gérer les clés étrangères
            $tables = ['clutches','feedings','photos','sheds','snake_images','snakes','babies','baby_parents','cares','gestations'];
            foreach ($tables as $t) {
                $pdo->exec("DELETE FROM `$t`");
            }
            
            // Réactivation des vérifications
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
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
        <p><strong><?= htmlspecialchars($message) ?></strong></p>
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
