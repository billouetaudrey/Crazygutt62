<?php
try {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/functions.php';

    // Définir le chemin de base pour les vignettes
    define('THUMB_DIR', 'uploads/thumbnails/');

    // Get snake IDs from URL parameters
    $id1 = $_GET['id1'] ?? null;
    $id2 = $_GET['id2'] ?? null;

    if (!$id1 || !$id2) {
        die("Erreur : Deux identifiants de serpent sont requis.");
    }

    // Prepare a query to fetch the snake data, including profile_photo_id
    $stmt = $pdo->prepare("SELECT id, name, sex, morph, birth_year, profile_photo_id FROM snakes WHERE id IN (?, ?) ORDER BY name ASC");
    $stmt->execute([$id1, $id2]);
    $snakes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If we don't find two snakes, show an error
    if (count($snakes) !== 2) {
        die("Erreur : Impossible de trouver les deux serpents spécifiés.");
    }

    // Assign the two snakes to variables for easy access
    $snake1 = $snakes[0];
    $snake2 = $snakes[1];

    // Function to get the photo filename for a snake
    function get_snake_photo($pdo, $snake_data) {
        $photo_filename = null;
        if ($snake_data['profile_photo_id'] > 0) {
            $stmt = $pdo->prepare("SELECT filename FROM photos WHERE id = ?");
            $stmt->execute([$snake_data['profile_photo_id']]);
            $photo_filename = $stmt->fetchColumn();
        }
        // Fallback: get the latest photo if no profile photo is set
        if (!$photo_filename) {
            $stmt = $pdo->prepare("SELECT filename FROM photos WHERE snake_id = ? ORDER BY uploaded_at DESC LIMIT 1");
            $stmt->execute([$snake_data['id']]);
            $photo_filename = $stmt->fetchColumn();
        }
        return $photo_filename;
    }

    $snake1['photo'] = get_snake_photo($pdo, $snake1);
    $snake2['photo'] = get_snake_photo($pdo, $snake2);

} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Couple : <?= h($snake1['name']) ?> & <?= h($snake2['name']) ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/print.css" media="print">
    <style>
        .couple-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin: 2rem;
        }
        .couple-card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            color: white; /* Couleur du texte pour l'affichage */
            display: flex;
            flex-direction: column;
            align-items: center; /* Centrer le contenu horizontalement */
            text-align: center; /* Centrer le texte */
        }
        .couple-card h2, .couple-card p, .couple-card strong {
            color: white;
        }
        .couple-card h2 {
            margin-top: 0;
            margin-bottom: 1rem;
        }
        .couple-card .snake-photo {
            width: 150px; /* Taille de la photo */
            height: 150px;
            overflow: hidden;
            border-radius: 50%; /* Rendre la photo ronde */
            margin-bottom: 1rem;
            background-color: var(--background-color); /* Fond pour les photos manquantes */
            display: flex;
            justify-content: center;
            align-items: center;
            border: 2px solid var(--border-color); /* Bordure autour de la photo */
        }
        .couple-card .snake-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* Assurer que l'image couvre le cercle */
        }
        .couple-card .no-photo {
            font-size: 3rem;
            color: var(--text-color-light);
        }

        /* Styles spécifiques pour l'impression, pour s'assurer que la photo est visible */
        @media print {
            .couple-card {
                color: black; /* Texte noir pour l'impression */
                border-color: black !important;
            }
            .couple-card h2, .couple-card p, .couple-card strong {
                color: black !important;
            }
            .couple-card .snake-photo {
                border-color: black !important;
                background-color: white !important;
            }
            .couple-card .no-photo {
                color: black !important;
            }
        }
    </style>
</head>
<body>
    <div style="text-align: center; margin: 1rem;" class="no-print">
        <button onclick="window.print()">Imprimer cette page</button>
        <button onclick="window.close()">Retour à la liste</button>
    </div>

    <div class="couple-container">
        <div class="couple-card">
            <h2><?= h($snake1['name']) ?></h2>
            <div class="snake-photo">
                <?php if ($snake1['photo']): ?>
                    <img src="<?= base_url(THUMB_DIR . h($snake1['photo'])) ?>" alt="Photo de <?= h($snake1['name']) ?>">
                <?php else: ?>
                    <div class="no-photo">📸</div>
                <?php endif; ?>
            </div>
            <p><strong>Sexe :</strong> <?= sex_badge($snake1['sex']) ?></p>
            <p><strong>Phase :</strong> <?= h($snake1['morph']) ?></p>
            <p><strong>Année de naissance :</strong> <?= h($snake1['birth_year']) ?></p>
        </div>
        <div class="couple-card">
            <h2><?= h($snake2['name']) ?></h2>
            <div class="snake-photo">
                <?php if ($snake2['photo']): ?>
                    <img src="<?= base_url(THUMB_DIR . h($snake2['photo'])) ?>" alt="Photo de <?= h($snake2['name']) ?>">
                <?php else: ?>
                    <div class="no-photo">📸</div>
                <?php endif; ?>
            </div>
            <p><strong>Sexe :</strong> <?= sex_badge($snake2['sex']) ?></p>
            <p><strong>Phase :</strong> <?= h($snake2['morph']) ?></p>
            <p><strong>Année de naissance :</strong> <?= h($snake2['birth_year']) ?></p>
        </div>
    </div>
</body>
</html>
