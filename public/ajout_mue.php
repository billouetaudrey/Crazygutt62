<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Récupère l'ID du serpent depuis l'URL si elle est présente
$preselectedSnakeId = isset($_GET['snake_id']) ? (int)$_GET['snake_id'] : null;

// Récupère la liste de tous les serpents
$snakes = $pdo->query("SELECT * FROM snakes ORDER BY name ASC")->fetchAll();

// On va regrouper les serpents en deux catégories
$groupedSnakes = [
    'Bébés' => [],
    'Subadultes/Adultes' => [],
    'Non défini' => []
];

foreach ($snakes as $s) {
    $mealType = strtolower($s['default_meal_type']);
    if ($mealType === 'rosé' || $mealType === 'blanchon') {
        $groupedSnakes['Bébés'][] = $s;
    } elseif ($mealType === 'sauteuse' || $mealType === 'adulte') {
        $groupedSnakes['Subadultes/Adultes'][] = $s;
    } else {
        $groupedSnakes['Non défini'][] = $s;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les IDs des serpents cochés
    $snake_ids = $_POST['snakes'] ?? [];

    // Ajouter l'ID du serpent pré-sélectionné si il a été envoyé via le champ caché
    if (isset($_POST['preselected_snake_id'])) {
        $preselectedId = (int)$_POST['preselected_snake_id'];
        // On s'assure qu'il n'est pas déjà dans le tableau pour éviter les doublons
        if (!in_array($preselectedId, $snake_ids)) {
            $snake_ids[] = $preselectedId;
        }
    }
    
    $date = $_POST['date'] ?? null;
    $complete = isset($_POST['complete']) ? 1 : 0;
    $comment = $_POST['comment'] ?? '';

    if (!empty($snake_ids) && $date) {
        $stmt = $pdo->prepare("INSERT INTO sheds (snake_id, date, complete, comment) VALUES (?, ?, ?, ?)");
        foreach ($snake_ids as $sid) {
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
    <script>
        // Fonction pour tout cocher/décocher
        function toggleAll(source, group) {
            const checkboxes = document.querySelectorAll('input[data-group="' + group + '"]');
            checkboxes.forEach(cb => {
                if (!cb.disabled) {
                    cb.checked = source.checked;
                }
            });
        }
    </script>
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
      <input type="date" name="date" value="<?= date('Y-m-d') ?>" required><br>

      <label><input type="checkbox" name="complete" checked> Mue complète</label><br>

      <label>Commentaire :</label>
      <textarea name="comment"></textarea><br>

      <h3>Sélectionnez les serpents :</h3>
        <?php foreach ($groupedSnakes as $groupName => $snakes): ?>
            <?php if (!empty($snakes)): ?>
                <div style="margin-top:1rem;">
                    <h3>
                        <?= h($groupName) ?>
                        <small style="margin-left: .5rem;">
                            <label><input type="checkbox" onclick="toggleAll(this, '<?= h($groupName) ?>')"> Tout cocher</label>
                        </small>
                    </h3>
                </div>
                <div class="snakes-grid">
                    <?php foreach ($snakes as $s): ?>
                        <label>
                            <?php if ($preselectedSnakeId == (int)$s['id']): ?>
                                <input type="checkbox" name="snakes[]" value="<?= (int)$s['id'] ?>" data-group="<?= h($groupName) ?>" checked disabled>
                                <input type="hidden" name="preselected_snake_id" value="<?= (int)$s['id'] ?>">
                            <?php else: ?>
                                <input type="checkbox" name="snakes[]" value="<?= (int)$s['id'] ?>" data-group="<?= h($groupName) ?>">
                            <?php endif; ?>
                            <?= h($s['name']) ?>
                        </label><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

      <button class="btn" type="submit">Ajouter</button>
    </form>
  </div>
</div>
</body>
</html>
