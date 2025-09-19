<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Rcuprer la liste des serpents, classe par leur type de repas par dfaut
$snakesByMealType = $pdo->query("
    SELECT * FROM snakes 
    ORDER BY default_meal_type ASC,
             CAST(REGEXP_SUBSTR(name, '[0-9]+') AS UNSIGNED) ASC,
             name ASC
")->fetchAll();

// On va regrouper les serpents par type de repas
$groupedSnakes = [];
foreach ($snakesByMealType as $s) {
    $mealType = $s['default_meal_type'] ?: 'Non dfini';
    if (!isset($groupedSnakes[$mealType])) {
        $groupedSnakes[$mealType] = [];
    }
    $groupedSnakes[$mealType][] = $s;
}

// Rcuprer l'ID du serpent depuis l'URL si elle est prsente
$preselectedSnakeId = isset($_GET['snake_id']) ? (int)$_GET['snake_id'] : null;
$preselectedSnake = null;
if ($preselectedSnakeId) {
    $stmt = $pdo->prepare("SELECT * FROM snakes WHERE id = ?");
    $stmt->execute([$preselectedSnakeId]);
    $preselectedSnake = $stmt->fetch();
}

$done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rcuprer les IDs des serpents cochs
    $snake_ids = $_POST['snakes'] ?? [];
    
    // Ajouter l'ID du serpent pr-slectionn si il a t envoy via le champ cach
    if (isset($_POST['preselected_snake_id'])) {
        $preselectedId = (int)$_POST['preselected_snake_id'];
        // On s'assure qu'il n'est pas dj dans le tableau pour viter les doublons
        if (!in_array($preselectedId, $snake_ids)) {
            $snake_ids[] = $preselectedId;
        }
    }

    $date = $_POST['date'] ?? date('Y-m-d');
    $count = (int)($_POST['count'] ?? 1);
    $prey_type = in_array($_POST['prey_type'] ?? '', ['vivant','mort','congel']) ? $_POST['prey_type'] : 'mort';
    
    // NOUVEAU : Rcuprer le type et la taille du rongeur sparment
    $rongeur_type = $_POST['rongeur_type'] ?? null;
    $rongeur_size = $_POST['rongeur_size'] ?? null;
    
    // NOUVEAU : Combiner le type et la taille pour le champ meal_type de la base de donnes
    $meal_type = ($rongeur_type && $rongeur_size) ? $rongeur_type . ' ' . $rongeur_size : null;
    
    $refused = isset($_POST['refused']) ? 1 : 0;
    $notes = trim($_POST['notes'] ?? '');

    if ($snake_ids) {
        $stmt = $pdo->prepare("INSERT INTO feedings (snake_id, date, count, prey_type, meal_type, refused, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($snake_ids as $sid) {
            $stmt->execute([(int)$sid, $date, $count, $prey_type, $meal_type, $refused, $notes ?: null]);
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
        // Fonction pour tout cocher/dcocher
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
        <div class="brand">
            <?php if ($preselectedSnakeId): ?>
                <a class="btn secondary" href="snake.php?id=<?= (int)$preselectedSnakeId ?>">← Retour au serpent</a>
            <?php else: ?>
                <a class="btn secondary" href="index.php">← Retour</a>
            <?php endif; ?>
        </div>
        <button class="theme-toggle" onclick="toggleTheme()">☀️/🌙</button>
    </div>

    <div class="card">
        <h2>Ajouter un repas</h2>
        <?php if ($done): ?>
            <div class="helper" style="color:var(--ok)">Repas enregistré ✅</div>
        <?php endif; ?>

        <form method="post">
            <label>Choisir les serpents :</label>

            <?php foreach ($groupedSnakes as $mealType => $snakes): ?>
                <details>
                    <summary style="margin-top:1rem; cursor: pointer;">
                        <h3>
                            <?= h(ucfirst($mealType)) ?>
                            <small style="margin-left: .5rem;">
                                <label><input type="checkbox" onclick="toggleAll(this, '<?= h($mealType) ?>')"> Tout cocher</label>
                            </small>
                        </h3>
                    </summary>
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
                </details>
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
                    <label>Type de rongeur</label>
                    <select name="rongeur_type">
                        <option value="souris">Souris</option>
                        <option value="rat">Rat</option>
                        <option value="mastomys">Mastomys</option>
                    </select>
                </div>
                <div>
                    <label>Taille du rongeur</label>
                    <select name="rongeur_size">
                        <option value="rosé">Rosé</option>
                        <option value="blanchon">Blanchon</option>
                        <option value="sauteuse">Sauteuse</option>
                        <option value="adulte">Adulte</option>
                    </select>
                </div>
                <div>
                    <label><input type="checkbox" name="refused" value="1"> Refusé</label>
                </div>
            </div>

            <div style="margin-top:1rem;">
                <label>Notes</label>
                <textarea name="notes"></textarea>
            </div>

            <div style="margin-top:1rem;">
                <button class="btn ok">Ajouter repas</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
