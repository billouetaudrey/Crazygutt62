<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Récupérer la liste des serpents, classée par leur type de repas par défaut
$snakesByMealType = $pdo->query("SELECT * FROM snakes ORDER BY default_meal_type ASC, name ASC")->fetchAll();

// On va regrouper les serpents par type de repas
$groupedSnakes = [];
foreach ($snakesByMealType as $s) {
    $mealType = $s['default_meal_type'] ?: 'Non défini';
    if (!isset($groupedSnakes[$mealType])) {
        $groupedSnakes[$mealType] = [];
    }
    $groupedSnakes[$mealType][] = $s;
}

// Récupérer l'ID du serpent depuis l'URL si elle est présente
$preselectedSnakeId = isset($_GET['snake_id']) ? (int)$_GET['snake_id'] : null;

$done = false;
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

    $date = $_POST['date'] ?? date('Y-m-d');
    $count = (int)($_POST['count'] ?? 1);
    $prey_type = in_array($_POST['prey_type'] ?? '', ['vivant','mort','congelé']) ? $_POST['prey_type'] : 'mort';
    $refused = isset($_POST['refused']) ? 1 : 0;
    $comment = trim($_POST['comment'] ?? '');

    if ($snake_ids) {
        $stmt = $pdo->prepare("INSERT INTO feedings (snake_id, date, count, prey_type, refused, comment) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($snake_ids as $sid) {
            $stmt->execute([(int)$sid, $date, $count, $prey_type, $refused, $comment ?: null]);
        }
        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter repas</title>
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
        <h2>Ajouter un repas</h2>
        <?php if ($done): ?>
            <div class="helper" style="color:var(--ok)">Repas enregistré ✅</div>
        <?php endif; ?>

        <form method="post">
            <label>Choisir les serpents :</label>

            <?php foreach ($groupedSnakes as $mealType => $snakes): ?>
                <div style="margin-top:1rem;">
                    <h3>
                        <?= h(ucfirst($mealType)) ?>
                        <small style="margin-left: .5rem;">
                            <label><input type="checkbox" onclick="toggleAll(this, '<?= h($mealType) ?>')"> Tout cocher</label>
                        </small>
                    </h3>
                </div>
                <div class="snakes-grid">
                    <?php foreach ($snakes as $s): ?>
                        <label>
                            <?php if ($preselectedSnakeId == (int)$s['id']): ?>
                                <input type="checkbox" name="snakes[]" value="<?= (int)$s['id'] ?>" data-group="<?= h($mealType) ?>" checked disabled>
                                <input type="hidden" name="preselected_snake_id" value="<?= (int)$s['id'] ?>">
                            <?php else: ?>
                                <input type="checkbox" name="snakes[]" value="<?= (int)$s['id'] ?>" data-group="<?= h($mealType) ?>">
                            <?php endif; ?>
                            <?= h($s['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <div class="grid" style="margin-top:1rem;">
                <div>
                    <label>Date</label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label>Nombre de proies</label>
                    <select name="count">
                        <option>1</option><option>2</option><option>3</option>
                    </select>
                </div>
                <div>
                    <label>Type de proie</label>
                    <select name="prey_type">
                        <option value="vivant">Vivant</option>
                        <option value="mort">Mort</option>
                        <option value="congelé">Congelé</option>
                    </select>
                </div>
                <div>
                    <label><input type="checkbox" name="refused" value="1"> Refusé</label>
                </div>
            </div>

            <div style="margin-top:1rem;">
                <label>Commentaire</label>
                <textarea name="comment"></textarea>
            </div>

            <div style="margin-top:1rem;">
                <button class="btn ok">Ajouter repas</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
