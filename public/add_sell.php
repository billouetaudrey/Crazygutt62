<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Le formulaire d'index.php n'envoie pas le prix, nous allons créer un formulaire temporaire
// pour gérer la saisie du prix si l'ID est envoyé via POST.

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['snake_id'])) {
    $snake_id = (int)$_POST['snake_id'];
    
    // --- Étape 2: Enregistrement final ---
    if (isset($_POST['final_sell']) && isset($_POST['price'])) {
        $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
        $sell_date = date('Y-m-d'); 
        
        if ($price === false || $price < 0) {
            $price = null; // Si le prix est invalide, on le laisse NULL
        }

        try {
            // Marquer le serpent comme vendu, enregistrer la date et le prix de vente
            $stmt = $pdo->prepare('UPDATE snakes SET sold = TRUE, sell_date = ?, price = ? WHERE id = ?');
            $stmt->execute([$sell_date, $price, $snake_id]);

            // OPTIONNEL : Supprimer tous les repas en attente pour ce serpent
            $stmt_del_pending = $pdo->prepare('DELETE FROM feedings WHERE snake_id = ? AND pending = 1');
            $stmt_del_pending->execute([$snake_id]);

            // Rediriger vers la page des ventes
            header('Location: ' . base_url('sales.php?sold=' . $snake_id));
            exit;

        } catch (PDOException $e) {
            die("Erreur de base de données lors de la vente : " . $e->getMessage());
        }
    } 
    
    // --- Étape 1: Formulaire de saisie du prix ---
    // Si l'ID est présent mais pas le prix (vient d'index.php), on affiche le formulaire.
    $stmt = $pdo->prepare('SELECT name, morph FROM snakes WHERE id = ?');
    $stmt->execute([$snake_id]);
    $snake = $stmt->fetch();

    if ($snake) {
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vendre <?= h($snake['name']) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container" style="max-width: 600px; margin-top: 50px;">
        <div class="card">
            <h2>Vente de <?= h($snake['name']) ?> (<?= h($snake['morph']) ?>)</h2>
            <form method="post" action="add_sell.php">
                <input type="hidden" name="snake_id" value="<?= $snake_id ?>">
                <input type="hidden" name="final_sell" value="1">
                
                <label for="price">Prix de vente (€) *</label>
                <input type="number" step="0.01" id="price" name="price" required placeholder="Ex: 120.00">
                
                <p style="margin-top: 1rem;">Confirmez la vente ? La date enregistrée sera la date du jour.</p>
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn ok">Confirmer la vente</button>
                    <a href="<?= base_url('index.php') ?>" class="btn danger">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php
    } else {
        header('Location: ' . base_url('index.php'));
        exit;
    }
} else {
    // Redirection si l'accès n'est pas via POST ou s'il manque l'ID
    header('Location: ' . base_url('index.php'));
    exit;
}
?>
