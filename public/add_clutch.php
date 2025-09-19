<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Récupérer tous les accouplements existants
$gestations = [];
try {
    $stmt = $pdo->prepare("
        SELECT g.id, g.pairing_date, sm.name AS male_name, sf.name AS female_name
        FROM gestations g
        LEFT JOIN snakes sm ON g.male_id = sm.id
        LEFT JOIN snakes sf ON g.female_id = sf.id
        ORDER BY g.pairing_date DESC
    ");
    $stmt->execute();
    $gestations = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur de base de données lors de la récupération des accouplements : " . $e->getMessage());
}

// Gérer la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gestation_id = (int)($_POST['gestation_id'] ?? 0);
    $lay_date = $_POST['lay_date'] ?? null;
    $comment = $_POST['comment'] ?? '';
    $egg_count = (int)($_POST['egg_count'] ?? 0);

    // Vérifier si un accouplement a été sélectionné et que la date est valide
    if ($gestation_id && $lay_date) {
        // Récupérer les identifiants du mâle et de la femelle à partir de l'accouplement sélectionné
        $stmt_ids = $pdo->prepare('SELECT male_id, female_id FROM gestations WHERE id = ?');
        $stmt_ids->execute([$gestation_id]);
        $gestation_data = $stmt_ids->fetch();

        if ($gestation_data) {
            $male_id = $gestation_data['male_id'];
            $female_id = $gestation_data['female_id'];

            // Calculer la date d'éclosion estimée (moyenne 58 jours)
            $hatch_date = date('Y-m-d', strtotime($lay_date . ' +58 days'));

            // Préparer la requête d'insertion d'une nouvelle ponte
            $stmt_insert = $pdo->prepare("INSERT INTO clutches (male_id, female_id, lay_date, hatch_date, egg_count, comment, gestation_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_insert->execute([$male_id, $female_id, $lay_date, $hatch_date, $egg_count, $comment, $gestation_id]);

            // Rediriger vers la page d'accueil
            header("Location: index.php");
            exit;
        } else {
            // Gérer le cas où l'accouplement n'est pas trouvé
            echo "Erreur : Accouplement invalide sélectionné.";
        }
    } else {
        echo "Erreur : Veuillez remplir tous les champs requis.";
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
            <div class="form-group">
                <label for="gestation_id">Accouplement :</label>
                <select id="gestation_id" name="gestation_id" required>
                    <option value="">-- Choisir un accouplement --</option>
                    <?php foreach($gestations as $g): ?>
                        <option value="<?= (int)$g['id'] ?>">
                            <?= h($g['female_name']) ?> & <?= h($g['male_name']) ?> (Accouplement du <?= date('d/m/Y', strtotime($g['pairing_date'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="lay_date">Date de ponte :</label>
                <input type="date" id="lay_date" name="lay_date" required>
            </div>

            <div class="form-group">
                <label for="egg_count">Nombre d'œufs :</label>
                <input type="number" id="egg_count" name="egg_count" value="0" min="0">
            </div>

            <div class="form-group">
                <label for="comment">Commentaire :</label>
                <textarea id="comment" name="comment"></textarea>
            </div>

            <button class="btn" type="submit">Ajouter</button>
        </form>
    </div>
</div>
</body>
</html>
