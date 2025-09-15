<?php
try {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/functions.php';

    define('THUMB_DIR', 'uploads/thumbnails/');

    $snakes = [];
    $is_single_snake = false;
    $single_snake_data = null;
    $feedings = [];
    $sheds = [];

    // Get snake IDs from the URL
    $id = $_GET['id'] ?? null;
    $id1 = $_GET['id1'] ?? null;
    $id2 = $_GET['id2'] ?? null;

    if ($id) {
        $is_single_snake = true;
        // Fetch single snake info
        $stmt = $pdo->prepare("SELECT id, name, sex, morph, birth_year, profile_photo_id, comment FROM snakes WHERE id = ?");
        $stmt->execute([$id]);
        $single_snake_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$single_snake_data) {
            die("Erreur : Impossible de trouver le serpent spécifié.");
        }

        // Fetch last 3 feedings
        $feedingsStmt = $pdo->prepare("SELECT date, meal_type, prey_type, count FROM feedings WHERE snake_id = ? ORDER BY date DESC LIMIT 3");
        $feedingsStmt->execute([$id]);
        $feedings = $feedingsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch last 3 sheds
        $shedsStmt = $pdo->prepare("SELECT date, complete, comment FROM sheds WHERE snake_id = ? ORDER BY date DESC LIMIT 3");
        $shedsStmt->execute([$id]);
        $sheds = $shedsStmt->fetchAll(PDO::FETCH_ASSOC);

        $snakes[] = $single_snake_data;

    } elseif ($id1 && $id2) {
        // Fetch two snakes for a couple display
        $is_single_snake = false;
        
        $stmt1 = $pdo->prepare("SELECT id, name, sex, morph, birth_year, profile_photo_id FROM snakes WHERE id = ?");
        $stmt1->execute([$id1]);
        $snake1 = $stmt1->fetch(PDO::FETCH_ASSOC);

        $stmt2 = $pdo->prepare("SELECT id, name, sex, morph, birth_year, profile_photo_id FROM snakes WHERE id = ?");
        $stmt2->execute([$id2]);
        $snake2 = $stmt2->fetch(PDO::FETCH_ASSOC);

        if ($snake1 && $snake2) {
            $snakes = [$snake1, $snake2];
        } else {
            die("Erreur : Impossible de trouver un ou plusieurs des serpents spécifiés.");
        }
    } else {
        die("Erreur : Un ou deux identifiants de serpent sont requis.");
    }

    // Function to get the photo filename
    function get_snake_photo($pdo, $snake_data) {
        $photo_filename = null;
        if ($snake_data['profile_photo_id'] > 0) {
            $stmt = $pdo->prepare("SELECT filename FROM photos WHERE id = ?");
            $stmt->execute([$snake_data['profile_photo_id']]);
            $photo_filename = $stmt->fetchColumn();
        }
        if (!$photo_filename) {
            $stmt = $pdo->prepare("SELECT filename FROM photos WHERE snake_id = ? ORDER BY uploaded_at DESC LIMIT 1");
            $stmt->execute([$snake_data['id']]);
            $photo_filename = $stmt->fetchColumn();
        }
        return $photo_filename;
    }

    foreach ($snakes as &$snake) {
        $snake['photo'] = get_snake_photo($pdo, $snake);
    }

    unset($snake); // Unset the reference.

} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?php if ($is_single_snake): ?>
            Étiquette : <?= h($snakes[0]['name']) ?>
        <?php else: ?>
            Couple : <?= h($snakes[0]['name']) ?> & <?= h($snakes[1]['name']) ?>
        <?php endif; ?>
    </title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/print.css" media="print">
    <style>
        body {
            background-color: var(--background-color);
            color: var(--text-color);
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .print-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1rem;
            max-width: 400px;
            margin: auto;
        }
        .snake-card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            color: white;
            text-align: center;
            width: 100%;
        }
        .snake-card h2 {
            margin: 0 0 0.5rem;
            font-size: 1.5rem;
        }
        .snake-photo {
            width: 100px;
            height: 100px;
            overflow: hidden;
            border-radius: 50%;
            margin: 0 auto 0.5rem;
            border: 2px solid var(--border-color);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .snake-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .no-photo {
            font-size: 2rem;
            color: var(--text-color-light);
        }
        .info-list {
            list-style: none;
            padding: 0;
            margin: 0.5rem 0;
            text-align: left;
            font-size: 0.9rem;
        }
        .info-list li {
            margin-bottom: 0.2rem;
        }
        .info-list strong {
            display: inline-block;
            width: 120px;
        }
        .section-title {
            font-weight: bold;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.2rem;
        }
        .details-list {
            list-style: none;
            padding: 0;
            font-size: 0.8rem;
        }
        .details-list li {
            margin-bottom: 0.2rem;
        }
        
        /* Styles d'impression */
        @media print {
            body {
                background-color: white;
                color: black;
                margin: 0;
            }
            .no-print {
                display: none;
            }
            .print-container {
                padding: 0.5cm;
            }
            .snake-card {
                border-color: black;
                box-shadow: none;
                color: black;
            }
            .snake-card h2, .snake-card p, .snake-card strong {
                color: black !important;
            }
            .snake-photo {
                border-color: black !important;
            }
            .no-photo {
                color: black !important;
            }
            .section-title {
                border-bottom-color: black;
            }
        }
    </style>
</head>
<body>
    <div style="text-align: center; margin: 1rem;" class="no-print">
        <button onclick="window.print()">Imprimer cette étiquette</button>
        <button onclick="window.close()">Fermer</button>
    </div>

    <div class="print-container">
        <?php if ($is_single_snake): ?>
            <div class="snake-card">
                <h2><?= h($snakes[0]['name']) ?></h2>
                <div class="snake-photo">
                    <?php if ($snakes[0]['photo']): ?>
                        <img src="<?= base_url(THUMB_DIR . h($snakes[0]['photo'])) ?>" alt="Photo de <?= h($snakes[0]['name']) ?>">
                    <?php else: ?>
                        <div class="no-photo">📸</div>
                    <?php endif; ?>
                </div>

                <ul class="info-list">
                    <li><strong>Sexe :</strong> <?= sex_badge($snakes[0]['sex']) ?></li>
                    <li><strong>Phase :</strong> <?= h($snakes[0]['morph']) ?></li>
                    <li><strong>Naissance :</strong> <?= h($snakes[0]['birth_year']) ?></li>
                </ul>

                <?php if ($snakes[0]['comment']): ?>
                    <p class="section-title">Commentaire</p>
                    <p style="font-size: 0.8rem; text-align: left; margin: 0;"><?= nl2br(h($snakes[0]['comment'])) ?></p>
                <?php endif; ?>

                <p class="section-title">Derniers repas</p>
                <?php if ($feedings): ?>
                    <ul class="details-list">
                        <?php foreach ($feedings as $f): ?>
                            <li><?= date('d/m/Y', strtotime($f['date'])) ?>: <?= h($f['prey_type']) ?: 'N/A' ?> (x<?= (int)$f['count'] ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="font-size: 0.8rem; margin: 0;">Aucun repas récent.</p>
                <?php endif; ?>

                <p class="section-title">Dernières mues</p>
                <?php if ($sheds): ?>
                    <ul class="details-list">
                        <?php foreach ($sheds as $s): ?>
                            <li><?= date('d/m/Y', strtotime($s['date'])) ?>: <?= ($s['complete'] == 1) ? 'Complète' : 'Incomplète' ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="font-size: 0.8rem; margin: 0;">Aucune mue récente.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($snakes as $snake): ?>
                <div class="snake-card">
                    <h2><?= h($snake['name']) ?></h2>
                    <div class="snake-photo">
                        <?php if ($snake['photo']): ?>
                            <img src="<?= base_url(THUMB_DIR . h($snake['photo'])) ?>" alt="Photo de <?= h($snake['name']) ?>">
                        <?php else: ?>
                            <div class="no-photo">📸</div>
                        <?php endif; ?>
                    </div>
                    <p><strong>Sexe :</strong> <?= sex_badge($snake['sex']) ?></p>
                    <p><strong>Phase :</strong> <?= h($snake['morph']) ?></p>
                    <p><strong>Année de naissance :</strong> <?= h($snake['birth_year']) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
