<?php
try {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/functions.php';

    // Récupérer l'ID du serpent
    $snake_id = (int)($_GET['id'] ?? 0);
    if ($snake_id === 0) {
        header('Location: ' . base_url('sales.php'));
        exit;
    }

    // Récupérer les données du serpent
    $stmt = $pdo->prepare('SELECT * FROM snakes WHERE id = ?');
    $stmt->execute([$snake_id]);
    $snake = $stmt->fetch();

    if (!$snake) {
        die("Serpent non trouvé.");
    }

    $errors = [];
    $success = false;

    // Traitement du formulaire de mise à jour
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action_type'])) {
        $name = trim($_POST['name'] ?? $snake['name']);
        $morph = trim($_POST['morph'] ?? $snake['morph']);
        $sex = $_POST['sex'] ?? $snake['sex'];
        $birth_year = (int)($_POST['birth_year'] ?? $snake['birth_year']);
        $price = filter_var($_POST['price'] ?? $snake['price'], FILTER_VALIDATE_FLOAT);
        $sell_date = trim($_POST['sell_date'] ?? $snake['sell_date']);
        $comment = trim($_POST['comment'] ?? $snake['comment']);
        $sold = isset($_POST['sold']); // Checkbox pour marquer comme vendu/non vendu

        if ($name === '') $errors[] = 'Le nom est requis.';
        if (!is_valid_year($birth_year)) $errors[] = 'Année de naissance invalide.';
        if ($price === false || $price < 0) $price = null;
        if ($sell_date === '') $sell_date = null;

        if (!$errors) {
            $update_stmt = $pdo->prepare('
                UPDATE snakes 
                SET 
                    name = ?, 
                    morph = ?, 
                    sex = ?, 
                    birth_year = ?, 
                    price = ?, 
                    sell_date = ?, 
                    comment = ?, 
                    sold = ?
                WHERE id = ?
            ');
            $update_stmt->execute([
                $name, 
                $morph, 
                $sex, 
                $birth_year, 
                $price, 
                $sell_date, 
                $comment ?: null,
                $sold ? 1 : 0,
                $snake_id
            ]);
            
            // Recharger les données après la mise à jour
            $stmt->execute([$snake_id]);
            $snake = $stmt->fetch();
            $success = true;
            
            // Si le serpent n'est plus vendu (décoché), rediriger vers l'index
            if (!$sold) {
                header('Location: ' . base_url('index.php?unsold=' . $snake_id));
                exit;
            }
        }
    }

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Éditer Serpent Vendu : <?= h($snake['name']) ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/theme.js" defer></script>
</head>
<body>
<div class="container" style="max-width: 800px;">
    <div class="header">
        <a href="<?= base_url('sales.php') ?>" class="btn secondary">← Retour aux ventes</a>
        <div class="brand">Éditer : <?= h($snake['name']) ?></div>
    </div>
    
    <?php if ($success): ?>
        <div class="card alert ok">
            Serpent vendu mis à jour avec succès !
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="card alert danger">
            <?php foreach ($errors as $e): ?>
                <div>• <?= h($e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Informations de Base</h2>
        <form method="post" action="edit_sold_snake.php?id=<?= $snake_id ?>">
            <div class="grid">
                <div>
                    <label>Nom *</label>
                    <input type="text" name="name" value="<?= h($snake['name']) ?>" required>
                </div>
                <div>
                    <label>Phase (morph)</label>
                    <input type="text" name="morph" value="<?= h($snake['morph']) ?>">
                </div>
                <div>
                    <label>Sexe *</label>
                    <select name="sex">
                        <option value="M" <?= $snake['sex'] === 'M' ? 'selected' : '' ?>>Mâle</option>
                        <option value="F" <?= $snake['sex'] === 'F' ? 'selected' : '' ?>>Femelle</option>
                        <option value="I" <?= $snake['sex'] === 'I' ? 'selected' : '' ?>>Indéfini</option>
                    </select>
                </div>
                <div>
                    <label>Année de naissance *</label>
                    <input type="number" name="birth_year" min="1900" max="<?= (int)date('Y') ?>" value="<?= (int)$snake['birth_year'] ?>" required>
                </div>
            </div>
            
            <div style="grid-column: 1 / 3; margin-top: 1rem;">
                <label>Commentaire</label>
                <textarea name="comment" rows="3"><?= h($snake['comment'] ?? '') ?></textarea>
            </div>
            
            <h2 style="margin-top: 2rem;">Statut de Vente</h2>
            
            <div class="grid">
                <div>
                    <label>Prix de vente (€)</label>
                    <input type="number" step="0.01" name="price" value="<?= $snake['price'] ?? '' ?>" placeholder="120.00">
                </div>
                <div>
                    <label>Date de vente</label>
                    <input type="date" name="sell_date" value="<?= $snake['sell_date'] ?? '' ?>">
                </div>
            </div>
            
            <div style="margin-top: 1.5rem;">
                <label>
                    <input type="checkbox" name="sold" <?= $snake['sold'] ? 'checked' : '' ?>>
                    Serpent Vendu (Décochez pour le remettre dans l'inventaire principal)
                </label>
            </div>

            <div style="margin-top:.8rem;">
                <button type="submit" class="btn ok">Enregistrer les modifications</button>
                <a href="<?= base_url('sales.php') ?>" class="btn secondary">Annuler l'édition</a
            </div>
        </form>

        <div style="margin-top:.8rem;">
            <form method="post" action="undo_sell.php" onsubmit="return confirm('Êtes-vous sûr de vouloir ANNULER cette vente et remettre <?= h($snake['name']) ?> dans l\'inventaire principal ?');"> 
                <input type="hidden" name="snake_id" value="<?= $snake_id ?>"> 
                <button class="btn warning" type="submit">Annuler la Vente (Rétablir)</button> 
            </form>

            <form method="post" action="delete_snake.php" onsubmit="return confirm('Êtes-vous sûr de vouloir SUPPRIMER DÉFINITIVEMENT <?= h($snake['name']) ?> et toutes ses données ?');"> 
                <input type="hidden" name="id" value="<?= $snake_id ?>"> 
                <input type="hidden" name="redirect_to" value="sales.php">
                <button class="btn danger" type="submit">Supprimer Définitivement</button> 
            </form>
        </div>
    </div>
</div>
</body>
</html>
