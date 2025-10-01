<?php
try {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/functions.php';

    // Handle form submission to add feedings
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ids = $_POST['snake_ids'] ?? [];
        
        // Récupération des données du formulaire
        $date = trim($_POST['date'] ?? '');
        $count = (int)($_POST['count'] ?? 1);
        
        $rongeur_type = trim($_POST['rongeur_type'] ?? '');
        $rongeur_size = trim($_POST['rongeur_size'] ?? '');
        $prey_type = trim($_POST['prey_type'] ?? '');
        
        $refused = isset($_POST['refused']) ? 1 : 0;
        $notes = trim($_POST['notes'] ?? '');
        $pending = isset($_POST['pending']) ? 1 : 0;
        
        // **********************************************
        // * CORRECTION MAJEURE : Construction du meal_type *
        // **********************************************
        // meal_type doit être la combinaison du type et de la taille (ex: "souris adulte")
        $meal_type = ($rongeur_type && $rongeur_size) ? $rongeur_type . ' ' . $rongeur_size : null;
        
        // Le champ meal_size dans la DB prendra uniquement la taille (ex: "adulte")
        $meal_size = $rongeur_size; 

        if (empty($ids) || empty($rongeur_type) || empty($rongeur_size) || empty($date)) {
            die('Erreur : Tous les champs requis doivent être remplis.');
        }

        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare('
            INSERT INTO feedings 
            (snake_id, date, meal_type, prey_type, meal_size, count, refused, pending, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        foreach ($ids as $id) {
            $stmt->bindValue(1, (int)$id, PDO::PARAM_INT);      // 1: snake_id
            $stmt->bindValue(2, $date, PDO::PARAM_STR);         // 2: date
            
            // 3: meal_type -> On utilise la combinaison complète
            $stmt->bindValue(3, $meal_type, PDO::PARAM_STR);    
            
            $stmt->bindValue(4, $prey_type, PDO::PARAM_STR);    // 4: prey_type
            
            // 5: meal_size -> On utilise la taille seule
            $stmt->bindValue(5, $meal_size, PDO::PARAM_STR);    
            
            $stmt->bindValue(6, (int)$count, PDO::PARAM_INT);
            $stmt->bindValue(7, (int)$refused, PDO::PARAM_INT);
            $stmt->bindValue(8, (int)$pending, PDO::PARAM_INT);
            $stmt->bindValue(9, $notes !== '' ? $notes : null, PDO::PARAM_STR);
            
            $stmt->execute();
        }
        $pdo->commit();
        header('Location: ' . base_url('index.php'));
        exit;
    }

    // Get snake IDs and pre-selected meal type from URL
    $snakeIds = $_GET['snake_ids'] ?? [];

    if (empty($snakeIds)) {
        die('Aucun serpent sélectionné.');
    }

    // Fetch snake data
    $in = str_repeat('?,', count($snakeIds) - 1) . '?';
    $stmt = $pdo->prepare("SELECT id, name, default_meal_type FROM snakes WHERE id IN ($in) ORDER BY name");
    $stmt->execute($snakeIds);
    $snakes = $stmt->fetchAll();

} catch (PDOException $e) {
    // Si la transaction a échoué, on s'assure qu'elle est rollback
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pantherophis — Ajouter un repas en masse</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/theme.js" defer></script>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="brand">🐍 Pantherophis — Ajouter un repas en masse</div>
        <a class="btn secondary" href="<?= base_url('index.php') ?>">Retour</a>
    </div>

    <div class="card">
        <h2>Ajouter un repas pour <?= count($snakes) ?> serpent(s)</h2>
        <form method="post" action="bulk_feeding.php">
            <?php foreach ($snakes as $s): ?>
                <input type="hidden" name="snake_ids[]" value="<?= (int)$s['id'] ?>">
            <?php endforeach; ?>
            
            <div class="grid">
                <div>
                    <label>Date du repas</label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label>Type de rongeur</label>
                    <select name="rongeur_type" required>
                        <option value="souris">Souris</option>
                        <option value="rat">Rat</option>
                        <option value="mastomys">Mastomys</option>
                    </select>
                </div>
                <div>
                    <label>Taille du rongeur</label>
                    <select name="rongeur_size" required>
                        <option value="rosé">Rosé</option>
                        <option value="blanchon">Blanchon</option>
                        <option value="sauteuse">Sauteuse</option>
                        <option value="adulte">Adulte</option>
                    </select>
                </div>
                <div>
                    <label>État de la proie</label>
                    <select name="prey_type" required>
                        <option value="vivant">Vivant</option>
                        <option value="mort">Mort</option>
                        <option value="congelé" selected>Congelé</option>
                    </select>
                </div>
                <div>
                    <label>Nombre de proies</label>
                    <select name="count" required>
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                    </select>
                </div>
            </div>
            <div style="margin-top: 1rem;">
                <label>
                    <input type="checkbox" name="refused" value="1"> Repas refusé
                </label>
            </div>
            <div style="margin-top: 1rem;">
                <label>
                    <input type="checkbox" name="pending" value="1"> Repas en attente
                </label>
            </div>
            <div style="margin-top: 1rem;">
                <label>Notes (facultatif)</label>
                <textarea name="notes" placeholder="Ex. A mangé facilement, a eu du mal, etc."></textarea>
            </div>
            <div style="margin-top: 1rem;">
                <button type="submit" class="btn ok">Enregistrer le repas</button>
            </div>
        </form>
        
        <div style="margin-top: 2rem;">
            <h3>Serpents concernés :</h3>
            <ul>
                <?php foreach ($snakes as $s): ?>
                    <li><?= h($s['name']) ?> (Repas par défaut : <?= h($s['default_meal_type'] ?: 'Non défini') ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
</body>
</html>
