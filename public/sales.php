<?php
try {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/functions.php';

    // Récupérer tous les serpents vendus, triés par date de vente
    $soldSnakesStmt = $pdo->query('
        SELECT 
            s.id, 
            s.name, 
            s.morph, 
            s.sex, 
            s.sell_date, 
            s.price,
            s.birth_year
        FROM snakes s
        WHERE s.sold = TRUE
        ORDER BY s.sell_date DESC
    ');
    $soldSnakes = $soldSnakesStmt->fetchAll();

    // Calculer le total par année
    $annualTotals = [];
    $grandTotal = 0;
    
    foreach ($soldSnakes as $snake) {
        if ($snake['price'] !== null) {
            $year = date('Y', strtotime($snake['sell_date']));
            $price = (float)$snake['price'];
            
            if (!isset($annualTotals[$year])) {
                $annualTotals[$year] = 0;
            }
            $annualTotals[$year] += $price;
            $grandTotal += $price;
        }
    }
    
    // Trier les totaux annuels par année décroissante
    krsort($annualTotals);

} catch (PDOException $e) { 
    die("Erreur de connexion à la base de données : " . $e->getMessage()); 
} 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pantherophis — Serpents Vendus</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/theme.js" defer></script>
</head>
<body>
<div class="container">
    <div class="header">
        <a href="<?= base_url('index.php') ?>" class="btn secondary">← Retour à l'accueil</a>
        <div class="brand">💰 Serpents Vendus</div>
    </div>

    <div class="card">
        <h2>Totaux des Ventes par Année</h2>
        <div style="display:flex; justify-content:space-around; gap: 1rem; flex-wrap: wrap;">
            <?php foreach ($annualTotals as $year => $total): ?>
                <div style="text-align:center; padding: 0.5rem 1rem; border: 1px solid var(--border-color); border-radius: 8px;">
                    <strong><?= h($year) ?></strong><br>
                    <span style="font-size: 1.2rem; font-weight: bold; color: var(--ok);"><?= number_format($total, 2, ',', ' ') ?> €</span>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top: 1rem; text-align: center; border-top: 1px dashed var(--border-color); padding-top: 1rem;">
            <h3>Total Général : <span style="color: var(--ok);"><?= number_format($grandTotal, 2, ',', ' ') ?> €</span></h3>
        </div>
    </div>

    <div class="card" style="margin-top:1rem;">
        <h2>Liste Détaillée (<?= count($soldSnakes) ?> ventes)</h2>
        <?php if (empty($soldSnakes)): ?>
            <div class="helper">Aucun serpent n'a été marqué comme vendu.</div>
        <?php else: ?>
        <div style="overflow:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Phase</th>
                        <th>Sexe</th>
                        <th>Âge (à la vente)</th>
                        <th>Date de vente</th>
                        <th>Prix (€)</th>
                        <th>Vue</th> <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($soldSnakes as $s): ?>
                    <?php 
                        $age_at_sale = 'N/A';
                        if ($s['birth_year'] && $s['birth_year'] !== '0000' && $s['sell_date']) {
                            $year_at_sale = date('Y', strtotime($s['sell_date']));
                            $age_at_sale_years = (int)$year_at_sale - (int)$s['birth_year'];
                            $age_at_sale = $age_at_sale_years . ' an(s)';
                        }
                    ?>
                    <tr>
                        <td><?= h($s['name']) ?></td>
                        <td><?= h($s['morph']) ?></td>
                        <td><?= sex_badge($s['sex']) ?></td>
                        <td><?= $age_at_sale ?></td>
                        <td><?= $s['sell_date'] ? date('d/m/Y', strtotime($s['sell_date'])) : '-' ?></td>
                        <td style="font-weight: bold;">
                            <?= $s['price'] !== null ? number_format((float)$s['price'], 2, ',', ' ') . ' €' : '?' ?>
                        </td>
                        <td>
                            <a class="btn small" href="snake.php?id=<?= (int)$s['id'] ?>">Voir</a>
                        </td>
                        <td style="display:flex; gap: 0.5rem;">
                            <a class="btn secondary small" href="edit_sold_snake.php?id=<?= (int)$s['id'] ?>">Éditer</a>
                            <form method="post" action="undo_sell.php" onsubmit="return confirm('Annuler la vente de <?= h($s['name']) ?> ? Il sera remis dans votre inventaire.');"> 
                                <input type="hidden" name="snake_id" value="<?= (int)$s['id'] ?>"> 
                                <button class="btn warning small" type="submit">Annuler</button> 
                            </form>
                            <form method="post" action="delete_snake.php" onsubmit="return confirm('SUPPRIMER DÉFINITIVEMENT <?= h($s['name']) ?> et toutes ses données ?');"> 
                                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>"> 
                                <input type="hidden" name="redirect_to" value="sales.php">
                                <button class="btn danger small" type="submit">🗑</button> 
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
