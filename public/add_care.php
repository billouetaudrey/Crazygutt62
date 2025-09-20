<?php
try {
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
        if (!$s['birth_year'] || $s['birth_year'] == '0000') {
            $groupedSnakes['Non défini'][] = $s;
            continue;
        }

        $now = (int)(new DateTime())->format('Y');
        $age = $now - (int)$s['birth_year'];

        if ($age < 1) {
            $groupedSnakes['Bébés'][] = $s;
        } elseif ($age < 2) {
            $groupedSnakes['Subadultes/Adultes'][] = $s;
        } else {
            $groupedSnakes['Subadultes/Adultes'][] = $s;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $snake_ids = $_POST['snakes'] ?? [];
        $date = $_POST['date'] ?? null;
        $care_type = $_POST['care_type'] ?? null;
        $comment = $_POST['comment'] ?? '';
        
        // Ajoute l'ID pré-sélectionné même s'il est désactivé
        if (isset($_POST['preselected_snake_id']) && !in_array($_POST['preselected_snake_id'], $snake_ids)) {
            $snake_ids[] = (int)$_POST['preselected_snake_id'];
        }

        if (!empty($snake_ids) && $date && $care_type) {
            $stmt = $pdo->prepare("INSERT INTO cares (snake_id, date, care_type, comment) VALUES (?, ?, ?, ?)");
            foreach ($snake_ids as $sid) {
                $stmt->execute([$sid, $date, $care_type, $comment]);
            }
            header("Location: index.php");
            exit;
        }
    }
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un soin</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/theme.js" defer></script>
    <script>
        function toggleAll(source, group) {
            const checkboxes = document.querySelectorAll(`input[name="snakes[]"][data-group="${group}"]`);
            checkboxes.forEach(cb => {
                cb.checked = source.checked;
            });
        }
    </script>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="brand">✨ Ajouter un soin</div>
        <button class="theme-toggle" onclick="toggleTheme()" title="Basculer thème">🌙/☀️</button>
    </div>

    <div class="card">
        <form method="post">
            <input type="hidden" name="preselected_snake_id" value="<?= h($preselectedSnakeId) ?>">

            <label for="care_type">Type de soin :</label>
            <select id="care_type" name="care_type" required>
                <option value="">Sélectionner un type</option>
                <option value="Médicament">Médicament</option>
                <option value="Blessure">Blessure</option>
                <option value="Parasite">Parasite</option>
                <option value="Vétérinaire">Vétérinaire</option>
                <option value="Autre">Autre</option>
            </select>

            <label for="date">Date du soin :</label>
            <input type="date" id="date" name="date" value="<?= date('Y-m-d') ?>" required>

            <label for="comment">Commentaire :</label>
            <textarea id="comment" name="comment" rows="4" placeholder="Détail du soin (Ex: appliqué Bétadine, pris rendez-vous chez le véto, etc.)."></textarea>

            <hr>
            <h3>Sélectionner les serpents concernés :</h3>
            <?php foreach ($groupedSnakes as $groupName => $list): ?>
                <?php if (!empty($list)): ?>
                    <h4>
                        <input type="checkbox" onclick="toggleAll(this, '<?= h($groupName) ?>')">
                        <?= h($groupName) ?> (<?= count($list) ?>)
                    </h4>
                    <div style="display:flex; flex-wrap:wrap; gap:10px;">
                        <?php foreach ($list as $snake): ?>
                            <?php 
                                $checked = '';
                                $disabled = '';
                                if ($preselectedSnakeId && $snake['id'] == $preselectedSnakeId) {
                                    $checked = 'checked';
                                    $disabled = 'disabled';
                                }
                            ?>
                            <label style="border:1px solid var(--border-color); padding:5px 10px; border-radius:5px; display:inline-flex; align-items:center; cursor:pointer;">
                                <input type="checkbox" name="snakes[]" value="<?= (int)$snake['id'] ?>" <?= $checked ?> <?= $disabled ?> data-group="<?= h($groupName) ?>">
                                <?= h($snake['name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <div style="margin-top:1rem;">
                <button type="submit" class="btn ok">Enregistrer</button>
                <a href="index.php" class="btn secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
