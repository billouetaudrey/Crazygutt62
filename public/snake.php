<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirection si l'ID n'est pas fourni
if (!isset($_GET['id'])) {
    header('Location: ' . base_url());
    exit;
}

$id = (int)$_GET['id'];

// Récupération des informations du serpent
$snakeStmt = $pdo->prepare("SELECT * FROM snakes WHERE id = ?");
$snakeStmt->execute([$id]);
$snake = $snakeStmt->fetch();

if (!$snake) {
    echo "Serpent non trouvé.";
    exit;
}

// Récupération des repas du serpent avec le type de repas et le type de proie
$feedingsStmt = $pdo->prepare("
    SELECT f.*
    FROM feedings f
    WHERE f.snake_id = ?
    ORDER BY f.date DESC
");
$feedingsStmt->execute([$id]);
$feedings = $feedingsStmt->fetchAll();

// Récupération du nombre de repas non refusés
$mealCountStmt = $pdo->prepare("SELECT COUNT(*) FROM feedings WHERE snake_id = ? AND refused = 0");
$mealCountStmt->execute([$id]);
$mealCount = $mealCountStmt->fetchColumn();

// Récupération des mues du serpent
$shedsStmt = $pdo->prepare("SELECT * FROM sheds WHERE snake_id = ? ORDER BY date DESC");
$shedsStmt->execute([$id]);
$sheds = $shedsStmt->fetchAll();

// Récupération des photos du serpent
$photosStmt = $pdo->prepare("SELECT * FROM photos WHERE snake_id = ? ORDER BY uploaded_at DESC");
$photosStmt->execute([$id]);
$photos = $photosStmt->fetchAll();

// Récupération de l'ID de la photo de profil actuelle
$profilePhotoIdStmt = $pdo->prepare("SELECT profile_photo_id FROM snakes WHERE id = ?");
$profilePhotoIdStmt->execute([$id]);
$profilePhotoId = $profilePhotoIdStmt->fetchColumn();

// Définir le chemin de base pour les uploads et les vignettes
define('UPLOAD_DIR', 'uploads/');
define('THUMB_DIR', 'uploads/thumbnails/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <script src="assets/theme.js" defer></script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($snake['name']) ?> — Suivi</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .image-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .image-item {
            position: relative;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 5px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .image-item img {
            max-width: 150px;
            height: auto;
            display: block;
        }
        .image-item .actions {
            display: flex;
            gap: 5px;
            margin-top: 5px;
        }
        .image-item .delete-btn {
            background-color: rgba(255, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 3px;
            padding: 3px 6px;
            cursor: pointer;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <a href="<?= base_url('index.php') ?>" class="btn secondary">← Retour</a>
        <div class="brand">🐍 Détails de <?= h($snake['name']) ?></div>
        <div class="empty"></div>
    </div>
    <div class="card">
        <h2>Informations du serpent</h2>
        <details>
            <summary>
                Modifier les informations
            </summary>
            <form action="update_snake.php" method="post">
                <input type="hidden" name="id" value="<?= (int)$snake['id'] ?>">
                <div class="grid">
    <div>
        <label>Nom</label>
        <input type="text" name="name" value="<?= h($snake['name']) ?>" required>
    </div>
                    <div>
                        <label>Sexe</label>
                        <select name="sex" required>
                            <option value="M" <?= ($snake['sex'] === 'M') ? 'selected' : '' ?>>Mâle</option>
                            <option value="F" <?= ($snake['sex'] === 'F') ? 'selected' : '' ?>>Femelle</option>
                            <option value="I" <?= ($snake['sex'] === 'I') ? 'selected' : '' ?>>Indéfini</option>
                        </select>
                    </div>
                    <div>
                        <label>Phase (morph)</label>
                        <input type="text" name="morph" value="<?= h($snake['morph']) ?>">
                    </div>
                    <div>
                        <label>Année de naissance</label>
                        <input type="number" name="birth_year" value="<?= (int)$snake['birth_year'] ?>" required>
                    </div>
                    <div>
                        <label>Poids (g)</label>
                        <input type="number" step="0.01" name="weight" value="<?= ($snake['weight'] !== null) ? h($snake['weight']) : '' ?>">
                    </div>
                    <div>
                        <label>Type de repas par défaut</label>
                        <select name="default_meal_type">
                            <option value="" <?= ($snake['default_meal_type'] === null) ? 'selected' : '' ?>>(Aucun)</option>
                            <option value="rosé" <?= ($snake['default_meal_type'] === 'rosé') ? 'selected' : '' ?>>Rosé</option>
                            <option value="blanchon" <?= ($snake['default_meal_type'] === 'blanchon') ? 'selected' : '' ?>>Blanchon</option>
                            <option value="sauteuse" <?= ($snake['default_meal_type'] === 'sauteuse') ? 'selected' : '' ?>>Sauteuse</option>
                            <option value="adulte" <?= ($snake['default_meal_type'] === 'adulte') ? 'selected' : '' ?>>Adulte</option>
                        </select>
                    </div>
                    <div style="grid-column: 1 / 3;">
                        <label>Commentaire</label>
                        <textarea name="comment" rows="3"><?= h($snake['comment']) ?></textarea>
                    </div>
                </div>
                <div style="margin-top:.8rem;">
                    <button type="submit" class="btn ok">Enregistrer les modifications</button>
                </div>

            </form>
        </details>
        
        <p>
            Nom : <strong><?= h($snake['name']) ?></strong><br>
            Sexe : <?= sex_badge($snake['sex']) ?><br>
            Phase : <?= h($snake['morph']) ?: 'N/A' ?><br>
            Année de naissance : <?= h($snake['birth_year']) ?: 'N/A' ?><br>
            Poids : <?= ($snake['weight'] !== null) ? h($snake['weight']) . ' g' : 'N/A' ?><br>
            Type de repas par défaut : <?= h($snake['default_meal_type']) ?: 'N/A' ?><br>
            Commentaire : <?= $snake['comment'] ? nl2br(h($snake['comment'])) : 'N/A' ?>
<div class="card">
    <h3>Supprimer ce serpent</h3>
    <p>
        Attention : Cette action est irréversible et supprimera toutes les informations associées à ce serpent (repas, mues, photos).
    </p>
    <form method="post" action="delete_snake.php" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement ce serpent et toutes ses données ?')">
        <input type="hidden" name="id" value="<?= (int)$snake['id'] ?>">
        <button type="submit" class="btn danger full-width">Supprimer le serpent</button>
    </form>
</div>        
</p>
    </div>

    ---

    <div class="card">
        <h3>Photos du serpent</h3>
        <details>
            <summary>Ajouter une nouvelle photo</summary>
            <form action="upload_photo.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= (int)$snake['id'] ?>">
                <label for="photo">Choisir une image :</label>
                <input type="file" name="photo" id="photo" accept="image/*" required>
                <br><br>
                <button type="submit" class="btn ok">Envoyer la photo</button>
            </form>
        </details>

        <?php if ($photos): ?>
            <div class="image-gallery">
                <?php foreach ($photos as $photo): ?>
                    <div class="image-item">
                        <a href="<?= base_url(UPLOAD_DIR . h($photo['filename'])) ?>" target="_blank">
                            <img src="<?= base_url(THUMB_DIR . h($photo['filename'])) ?>" alt="Photo de <?= h($snake['name']) ?>">
                        </a>
                        <div class="actions">
                            <?php if ($photo['id'] != $profilePhotoId): ?>
                                <form method="post" action="set_profile_photo.php">
                                    <input type="hidden" name="photo_id" value="<?= (int)$photo['id'] ?>">
                                    <input type="hidden" name="snake_id" value="<?= (int)$snake['id'] ?>">
                                    <button type="submit" class="btn primary">Définir comme profil</button>
                                </form>
                            <?php else: ?>
                                <span class="btn ok">Photo de profil actuelle</span>
                            <?php endif; ?>
                            <form method="post" action="delete_photo.php" onsubmit="return confirm('Supprimer cette photo ?')">
                                <input type="hidden" name="photo_id" value="<?= (int)$photo['id'] ?>">
                                <input type="hidden" name="snake_id" value="<?= (int)$snake['id'] ?>">
                                <button type="submit" class="delete-btn">X</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="helper">Aucune photo enregistrée pour ce serpent.</div>
        <?php endif; ?>
    </div>

    ---

    <div class="card">
        <h3>Repas</h3>
        <a class="btn" href="ajout_repas.php?snake_id=<?= (int)$snake['id'] ?>">+ Ajouter un repas</a>
        <p>Nombre de repas pris : <strong><?= (int)$mealCount ?></strong></p>

        <?php if ($feedings): ?>
            <div style="overflow:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type de repas</th>
                            <th>Type de proie</th>
                            <th>Nombre</th>
                            <th>Refusé</th>
                            <th>Commentaire</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedings as $f): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($f['date'])) ?></td>
                                <td><?= h($f['meal_type']) ?: 'N/A' ?></td>
                                <td><?= h($f['prey_type']) ?: 'N/A' ?></td>
                                <td><?= (int)$f['count'] ?></td>
                                <td><?= $f['refused'] ? 'Oui' : 'Non' ?></td>
                                <td><?= h($f['notes']) ?: 'N/A' ?></td>
                                <td style="display:flex;gap:.4rem;">
                                    <form method="post" action="delete_feeding.php" onsubmit="return confirm('Supprimer ce repas ?')">
                                        <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                                        <input type="hidden" name="snake_id" value="<?= (int)$snake['id'] ?>">
                                        <button class="btn danger" type="submit">🗑</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="helper">Aucun repas enregistré pour ce serpent.</div>
        <?php endif; ?>
    </div>

    ---

    <div class="card">
        <h3>Mues</h3>
        <a class="btn" href="ajout_mue.php?snake_id=<?= (int)$snake['id'] ?>">+ Ajouter une mue</a>
        
        <?php if ($sheds): ?>
            <div style="overflow:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Qualité</th>
                            <th>Commentaire</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sheds as $s): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($s['date'])) ?></td>
                                <td><?= h($s['quality']) ?: 'N/A' ?></td>
                                <td><?= h($s['comment']) ?: 'N/A' ?></td>
                                <td style="display:flex;gap:.4rem;">
                                    <form method="post" action="delete_shed.php" onsubmit="return confirm('Supprimer cette mue ?')">
                                        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                        <input type="hidden" name="snake_id" value="<?= (int)$snake['id'] ?>">
                                        <button class="btn danger" type="submit">🗑</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="helper">Aucune mue enregistrée pour ce serpent.</div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
