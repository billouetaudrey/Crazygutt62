<?php
try {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/functions.php';

    // --- Récupération de toutes les données nécessaires en une seule requête pour l'efficacité ---
    $allSnakesStatsStmt = $pdo->query("
        SELECT 
            s.id,
            s.name,
            s.birth_year,
            COUNT(f.id) AS total_repas,
            SUM(CASE WHEN f.refused = 1 THEN 1 ELSE 0 END) AS repas_refuses,
            SUM(CASE WHEN f.prey_type = 'mort' AND f.refused = 0 THEN 1 ELSE 0 END) AS repas_mort,
            SUM(CASE WHEN f.prey_type = 'vivant' AND f.refused = 0 THEN 1 ELSE 0 END) AS repas_vivant,
            SUM(CASE WHEN f.prey_type = 'congelé' AND f.refused = 0 THEN 1 ELSE 0 END) AS repas_congele,
            (SELECT COUNT(*) FROM sheds sh WHERE sh.snake_id = s.id) AS mues
        FROM 
            snakes s
        LEFT JOIN 
            feedings f ON s.id = f.snake_id
        GROUP BY 
            s.id
        ORDER BY 
            s.name
    ");
    $allSnakesStats = $allSnakesStatsStmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Tri des serpents par catégorie d'âge ---
    $now = (int)(new DateTime())->format('Y');
    $babies = [];
    $subadults = [];
    $adults = [];

    foreach ($allSnakesStats as $s) {
        if (!$s['birth_year'] || $s['birth_year'] == '0000') {
            continue;
        }
        $age = $now - (int)$s['birth_year'];

        if ($age < 1) {
            $babies[] = $s;
        } elseif ($age >= 1 && $age < 2) {
            $subadults[] = $s;
        } else {
            $adults[] = $s;
        }
    }

    // --- Calcul des totaux globaux pour le graphique en camembert ---
    $repasTotauxParTypeStmt = $pdo->query("
        SELECT 
            SUM(CASE WHEN prey_type = 'mort' AND refused = 0 THEN 1 ELSE 0 END) AS total_mort,
            SUM(CASE WHEN prey_type = 'vivant' AND refused = 0 THEN 1 ELSE 0 END) AS total_vivant,
            SUM(CASE WHEN prey_type = 'congelé' AND refused = 0 THEN 1 ELSE 0 END) AS total_congele,
            SUM(CASE WHEN refused = 1 THEN 1 ELSE 0 END) AS total_refuse
        FROM 
            feedings
    ");
    $repasTotauxParType = $repasTotauxParTypeStmt->fetch(PDO::FETCH_ASSOC);

    // --- Calcul des totaux par catégorie d'âge pour les graphiques à barres ---
    $repasTotauxBabies = ['vivant' => 0, 'mort' => 0, 'congele' => 0, 'refuse' => 0];
    foreach ($babies as $s) {
        $repasTotauxBabies['vivant'] += (int)$s['repas_vivant'];
        $repasTotauxBabies['mort'] += (int)$s['repas_mort'];
        $repasTotauxBabies['congele'] += (int)$s['repas_congele'];
        $repasTotauxBabies['refuse'] += (int)$s['repas_refuses'];
    }

    $repasTotauxSubadults = ['vivant' => 0, 'mort' => 0, 'congele' => 0, 'refuse' => 0];
    foreach ($subadults as $s) {
        $repasTotauxSubadults['vivant'] += (int)$s['repas_vivant'];
        $repasTotauxSubadults['mort'] += (int)$s['repas_mort'];
        $repasTotauxSubadults['congele'] += (int)$s['repas_congele'];
        $repasTotauxSubadults['refuse'] += (int)$s['repas_refuses'];
    }

    $repasTotauxAdults = ['vivant' => 0, 'mort' => 0, 'congele' => 0, 'refuse' => 0];
    foreach ($adults as $s) {
        $repasTotauxAdults['vivant'] += (int)$s['repas_vivant'];
        $repasTotauxAdults['mort'] += (int)$s['repas_mort'];
        $repasTotauxAdults['congele'] += (int)$s['repas_congele'];
        $repasTotauxAdults['refuse'] += (int)$s['repas_refuses'];
    }

} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Statistiques — Pantherophis</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/theme.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Styles de base */
        .stats-table th, .stats-table td {
            text-align: left;
            padding: 8px;
        }
        .stats-table thead th {
            font-weight: bold;
            border-bottom: 2px solid var(--border-color);
        }

        /* Styles pour les couleurs des cellules */
        .repas-pris { background-color: rgba(75, 192, 192, 0.2); }
        .repas-refuses { background-color: rgba(255, 99, 132, 0.2); }
        .repas-vivants { background-color: rgba(75, 192, 192, 0.1); }
        .repas-morts { background-color: rgba(255, 159, 64, 0.1); }
        .repas-congeles { background-color: rgba(54, 162, 235, 0.1); }
        .nombre-mues { background-color: rgba(255, 205, 86, 0.2); }

        /* Styles pour l'accordéon */
        .collapsible-container h2,
        .collapsible-container h3 {
            cursor: pointer;
            user-select: none;
            display: flex;
            align-items: center;
        }
        .collapsible-container h2:hover,
        .collapsible-container h3:hover {
            color: var(--primary-color);
        }
        .toggle-icon {
            font-size: 1.2rem;
            margin-right: 0.5rem;
            transition: transform 0.3s ease;
        }
        .toggle-icon.active {
            transform: rotate(90deg);
        }
        .collapsible-content {
            display: block; /* Affiche le contenu par défaut */
            overflow: hidden;
            transition: max-height 0.3s ease-out, opacity 0.3s ease-out;
        }
        .collapsible-content.collapsed {
            display: none; /* Cache le contenu */
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <a href="<?= base_url('index.php') ?>" class="btn secondary">← Retour</a>
        <div class="brand">📊 Statistiques de l'élevage</div>
        <div class="empty"></div>
    </div>

    <div class="card">
        <div class="collapsible-container">
            <h2><span class="toggle-icon active">►</span> Statistiques globales</h2>
            <div class="collapsible-content">
                <div class="grid">
                    <div>
                        <strong>Total des repas donnés :</strong>
                        <?php
                        $totalRepasDonnesStmt = $pdo->query("SELECT COUNT(*) FROM feedings WHERE refused = 0");
                        echo $totalRepasDonnesStmt->fetchColumn();
                        ?>
                    </div>
                    <div>
                        <strong>Total des mues :</strong>
                        <?php
                        $totalMuesStmt = $pdo->query("SELECT COUNT(*) FROM sheds");
                        echo $totalMuesStmt->fetchColumn();
                        ?>
                    </div>
                    <div>
                        <strong>Total des refus :</strong>
                        <?php
                        $totalRefusStmt = $pdo->query("SELECT COUNT(*) FROM feedings WHERE refused = 1");
                        echo $totalRefusStmt->fetchColumn();
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <hr>
    
    <div class="card">
        <div class="collapsible-container">
            <h2><span class="toggle-icon active">►</span> Répartition des repas (total)</h2>
            <div class="collapsible-content">
                <div style="max-width: 500px; margin: auto;">
                    <canvas id="repasChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <hr>
    
    <div class="card">
        <div class="collapsible-container">
            <h2><span class="toggle-icon">►</span> Répartition des repas par catégorie</h2>
            <div class="collapsible-content collapsed">
                <?php if (!empty($babies)): ?>
                    <h3>🐍 Bébés (< 1 an)</h3>
                    <div style="max-width: 600px; margin: auto;">
                        <canvas id="babiesChart"></canvas>
                    </div>
                <?php endif; ?>

                <?php if (!empty($subadults)): ?>
                    <h3 style="margin-top: 2rem;">🟠 Sub-adultes (1–2 ans)</h3>
                    <div style="max-width: 600px; margin: auto;">
                        <canvas id="subadultsChart"></canvas>
                    </div>
                <?php endif; ?>

                <?php if (!empty($adults)): ?>
                    <h3 style="margin-top: 2rem;">🟢 Adultes (> 2 ans)</h3>
                    <div style="max-width: 600px; margin: auto;">
                        <canvas id="adultsChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <hr>

    <div class="card">
        <div class="collapsible-container">
            <h2><span class="toggle-icon">►</span> Statistiques par serpent</h2>
            <div class="collapsible-content collapsed">
                <?php if (!empty($babies)): ?>
                    <h3 style="margin-top: 2rem;">🐍 Bébés (< 1 an) (<?= count($babies) ?>)</h3>
                    <div style="overflow: auto;">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Nom du serpent</th>
                                    <th>Repas pris</th>
                                    <th>Refusés</th>
                                    <th>Vivants</th>
                                    <th>Morts</th>
                                    <th>Congelés</th>
                                    <th>Mues</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($babies as $s): ?>
                                    <tr>
                                        <td><?= h($s['name']) ?></td>
                                        <td class="repas-pris"><?= (int)$s['total_repas'] ?></td>
                                        <td class="repas-refuses"><?= (int)$s['repas_refuses'] ?></td>
                                        <td class="repas-vivants"><?= (int)$s['repas_vivant'] ?></td>
                                        <td class="repas-morts"><?= (int)$s['repas_mort'] ?></td>
                                        <td class="repas-congeles"><?= (int)$s['repas_congele'] ?></td>
                                        <td class="nombre-mues"><?= (int)$s['mues'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if (!empty($subadults)): ?>
                    <h3 style="margin-top: 2rem;">🟠 Sub-adultes (1–2 ans) (<?= count($subadults) ?>)</h3>
                    <div style="overflow: auto;">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Nom du serpent</th>
                                    <th>Repas pris</th>
                                    <th>Refusés</th>
                                    <th>Vivants</th>
                                    <th>Morts</th>
                                    <th>Congelés</th>
                                    <th>Mues</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subadults as $s): ?>
                                    <tr>
                                        <td><?= h($s['name']) ?></td>
                                        <td class="repas-pris"><?= (int)$s['total_repas'] ?></td>
                                        <td class="repas-refuses"><?= (int)$s['repas_refuses'] ?></td>
                                        <td class="repas-vivants"><?= (int)$s['repas_vivant'] ?></td>
                                        <td class="repas-morts"><?= (int)$s['repas_mort'] ?></td>
                                        <td class="repas-congeles"><?= (int)$s['repas_congele'] ?></td>
                                        <td class="nombre-mues"><?= (int)$s['mues'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if (!empty($adults)): ?>
                    <h3 style="margin-top: 2rem;">🟢 Adultes (> 2 ans) (<?= count($adults) ?>)</h3>
                    <div style="overflow: auto;">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Nom du serpent</th>
                                    <th>Repas pris</th>
                                    <th>Refusés</th>
                                    <th>Vivants</th>
                                    <th>Morts</th>
                                    <th>Congelés</th>
                                    <th>Mues</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($adults as $s): ?>
                                    <tr>
                                        <td><?= h($s['name']) ?></td>
                                        <td class="repas-pris"><?= (int)$s['total_repas'] ?></td>
                                        <td class="repas-refuses"><?= (int)$s['repas_refuses'] ?></td>
                                        <td class="repas-vivants"><?= (int)$s['repas_vivant'] ?></td>
                                        <td class="repas-morts"><?= (int)$s['repas_mort'] ?></td>
                                        <td class="repas-congeles"><?= (int)$s['repas_congele'] ?></td>
                                        <td class="nombre-mues"><?= (int)$s['mues'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du pliage/dépliage des sections
    const collapsibles = document.querySelectorAll('.collapsible-container h2, .collapsible-container h3');
    collapsibles.forEach(header => {
        header.addEventListener('click', () => {
            const content = header.nextElementSibling;
            const icon = header.querySelector('.toggle-icon');

            if (content.classList.contains('collapsed')) {
                content.classList.remove('collapsed');
                icon.classList.add('active');
            } else {
                content.classList.add('collapsed');
                icon.classList.remove('active');
            }
        });
    });

    // Graphique en camembert global
    const stats = {
        vivant: <?= (int)$repasTotauxParType['total_vivant'] ?>,
        mort: <?= (int)$repasTotauxParType['total_mort'] ?>,
        congele: <?= (int)$repasTotauxParType['total_congele'] ?>,
        refuse: <?= (int)$repasTotauxParType['total_refuse'] ?>
    };

    const ctx = document.getElementById('repasChart').getContext('2d');
    const repasChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Vivant', 'Mort', 'Congelé', 'Refusé'],
            datasets: [{
                label: 'Nombre de repas',
                data: [stats.vivant, stats.mort, stats.congele, stats.refuse],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(255, 159, 64, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 99, 132, 0.8)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Répartition des repas par type et refus'
                }
            }
        }
    });

    // Fonction pour créer un graphique à barres réutilisable
    function createBarChart(elementId, title, data) {
        const element = document.getElementById(elementId);
        if (!element) {
            return;
        }
        const ctx = element.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Vivant', 'Mort', 'Congelé', 'Refusé'],
                datasets: [{
                    label: 'Nombre de repas',
                    data: [data.vivant, data.mort, data.congele, data.refuse],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 99, 132, 0.8)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: title
                    }
                }
            }
        });
    }

    // Données pour chaque graphique à barres
    const babiesStats = {
        vivant: <?= (int)$repasTotauxBabies['vivant'] ?>,
        mort: <?= (int)$repasTotauxBabies['mort'] ?>,
        congele: <?= (int)$repasTotauxBabies['congele'] ?>,
        refuse: <?= (int)$repasTotauxBabies['refuse'] ?>
    };

    const subadultsStats = {
        vivant: <?= (int)$repasTotauxSubadults['vivant'] ?>,
        mort: <?= (int)$repasTotauxSubadults['mort'] ?>,
        congele: <?= (int)$repasTotauxSubadults['congele'] ?>,
        refuse: <?= (int)$repasTotauxSubadults['refuse'] ?>
    };

    const adultsStats = {
        vivant: <?= (int)$repasTotauxAdults['vivant'] ?>,
        mort: <?= (int)$repasTotauxAdults['mort'] ?>,
        congele: <?= (int)$repasTotauxAdults['congele'] ?>,
        refuse: <?= (int)$repasTotauxAdults['refuse'] ?>
    };

    // Création des graphiques à barres
    createBarChart('babiesChart', 'Répartition des repas pour les bébés', babiesStats);
    createBarChart('subadultsChart', 'Répartition des repas pour les sub-adultes', subadultsStats);
    createBarChart('adultsChart', 'Répartition des repas pour les adultes', adultsStats);
});
</script>
</body>
</html>
