<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_GET['id'])) {
    header('Location: ' . base_url());
    exit;
}

$id = (int)$_GET['id'];

// Fetch feeding record
$feedingStmt = $pdo->prepare("SELECT * FROM feedings WHERE id = ?");
$feedingStmt->execute([$id]);
$feeding = $feedingStmt->fetch();

if (!$feeding) {
    echo "Repas non trouvé.";
    exit;
}

// Fetch snake info
$snakeStmt = $pdo->prepare("SELECT name FROM snakes WHERE id = ?");
$snakeStmt->execute([$feeding['snake_id']]);
$snake = $snakeStmt->fetch();


// *******************************************************************
// * NOUVEAU : Pré-remplissage des champs du formulaire              *
// * On divise les données de la DB pour l'affichage                 *
// *******************************************************************

// Récupérer le type de rongeur (ex: "souris") à partir du 'meal_type' complet
$db_meal_type_parts = explode(' ', $feeding['meal_type'] ?? '');
$db_rongeur_type = $db_meal_type_parts[0] ?? ''; // ex: "souris"
// Récupérer la taille du rongeur (ex: "adulte") à partir de 'meal_size'
$db_rongeur_size = $feeding['meal_size'] ?? ''; // ex: "adulte"
// L'état de la proie est stocké dans 'prey_type'
$db_prey_state = $feeding['prey_type'] ?? ''; // ex: "congelé"


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Récupération des données du formulaire (avec les nouveaux noms)
    $date = $_POST['date'] ?? '';
    $rongeur_type = $_POST['rongeur_type'] ?? '';   // ex: souris
    $rongeur_size = $_POST['rongeur_size'] ?? '';   // ex: adulte
    $prey_state = $_POST['prey_state'] ?? '';       // ex: congelé
    $count = (int)($_POST['count'] ?? 1);
    $refused = isset($_POST['refused']) ? 1 : 0;
    $notes = $_POST['notes'] ?: null;

    // 2. Reconstruction des variables DB selon le modèle
    
    // meal_type (Type + Taille : "souris adulte")
    $db_meal_type_full = ($rongeur_type && $rongeur_size) ? $rongeur_type . ' ' . $rongeur_size : null;
    
    // meal_size (Taille seule : "adulte")
    $db_meal_size = $rongeur_size;
    
    // prey_type (État de la proie : "congelé")
    $db_prey_type_state = $prey_state;


    // 3. Exécution de la requête UPDATE avec les bonnes colonnes
    $stmt = $pdo->prepare("
        UPDATE feedings 
        SET 
            date = ?, 
            meal_type = ?, 
            meal_size = ?, 
            prey_type = ?, 
            count = ?, 
            refused = ?, 
            notes = ? 
        WHERE id = ?
    ");
    
    $stmt->execute([
        $date, 
        $db_meal_type_full, 
        $db_meal_size, 
        $db_prey_type_state, 
        $count, 
        $refused, 
        $notes, 
        $id
    ]);
    
    header('Location: ' . base_url('snake.php?id=' . (int)$feeding['snake_id']));
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Modifier le repas — <?= h($snake['name'] ?? 'Serpent') ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <a href="<?= base_url('snake.php?id=' . (int)$feeding['snake_id']) ?>" class="btn secondary">← Retour au serpent</a>
        <div class="brand">Modifier le repas de <?= h($snake['name'] ?? 'Serpent') ?></div>
        <div class="empty"></div>
    </div>
    <div class="card">
        <form method="post" action="edit_feeding.php?id=<?= (int)$id ?>">
            <div class="grid">
                <div>
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" value="<?= h($feeding['date']) ?>" required>
                </div>
                <div>
                    <label for="rongeur_type">Type de rongeur</label>
                    <select id="rongeur_type" name="rongeur_type" required>
                        <option value="souris" <?= ($db_rongeur_type === 'souris') ? 'selected' : '' ?>>Souris</option>
                        <option value="rat" <?= ($db_rongeur_type === 'rat') ? 'selected' : '' ?>>Rat</option>
                        <option value="mastomys" <?= ($db_rongeur_type === 'mastomys') ? 'selected' : '' ?>>Mastomys</option>
                        <option value="autres" <?= ($db_rongeur_type === 'autres') ? 'selected' : '' ?>>Autres</option>
                    </select>
                </div>
                <div>
                    <label for="rongeur_size">Taille du rongeur</label>
                    <select id="rongeur_size" name="rongeur_size" required>
                        <option value="rosé" <?= ($db_rongeur_size === 'rosé') ? 'selected' : '' ?>>Rosé</option>
                        <option value="blanchon" <?= ($db_rongeur_size === 'blanchon') ? 'selected' : '' ?>>Blanchon</option>
                        <option value="sauteuse" <?= ($db_rongeur_size === 'sauteuse') ? 'selected' : '' ?>>Sauteuse</option>
                        <option value="adulte" <?= ($db_rongeur_size === 'adulte') ? 'selected' : '' ?>>Adulte</option>
                    </select>
                </div>
                <div>
                    <label for="prey_state">État de la proie</label>
                    <select id="prey_state" name="prey_state" required>
                        <option value="vivant" <?= ($db_prey_state === 'vivant') ? 'selected' : '' ?>>Vivant</option>
                        <option value="mort" <?= ($db_prey_state === 'mort') ? 'selected' : '' ?>>Mort</option>
                        <option value="congelé" <?= ($db_prey_state === 'congelé') ? 'selected' : '' ?>>Congelé</option>
                    </select>
                </div>
                <div>
                    <label for="count">Nombre</label>
                    <input type="number" id="count" name="count" value="<?= (int)$feeding['count'] ?>" min="0" required>
                </div>
                <div style="grid-column: 1 / 3;">
                    <label>
                        <input type="checkbox" name="refused" <?= $feeding['refused'] ? 'checked' : '' ?>> Repas refusé
                    </label>
                </div>
                <div style="grid-column: 1 / 3;">
                    <label for="notes">Commentaire</label>
                    <textarea id="notes" name="notes" rows="3"><?= h($feeding['notes']) ?></textarea>
                </div>
            </div>
            <div style="margin-top: 1rem;">
                <button type="submit" class="btn ok">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
