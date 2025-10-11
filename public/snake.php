<?php
session_start(); // Démarre la session au début du fichier
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

// Récupération des repas du serpent, incluant le statut 'pending'
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

// --- NOUVEAU CODE POUR LES SOINS ---
$caresStmt = $pdo->prepare("SELECT * FROM cares WHERE snake_id = ? ORDER BY date DESC");
$caresStmt->execute([$id]);
$cares = $caresStmt->fetchAll();
// ------------------------------------

// Définir le chemin de base pour les uploads et les vignettes
define('UPLOAD_DIR', 'uploads/');
define('THUMB_DIR', 'uploads/thumbnails/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/xrange.js"></script>
<script src="https://code.highcharts.com/modules/series-label.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>

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

        /* Style pour le message de succès */
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <a href="<?= base_url('index.php') ?>" class="btn secondary">← Retour</a>
        <div class="brand">🐍 Détails de <?= h($snake['name']) ?></div>
    <div>
        <a href="print.php?id=<?= (int)$snake['id'] ?>" class="btn primary" target="_blank">Imprimer la fiche</a>

    </div>
        <div class="empty"></div>
    </div>
    
    <?php
    // Affiche le message de succès s'il existe et le supprime de la session
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
        unset($_SESSION['success_message']);
    }
    // Affiche le message d'erreur d'upload s'il existe
    if (isset($_GET['upload_error'])) {
        echo '<div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 1rem; margin-bottom: 1rem; border: 1px solid #f5c6cb; border-radius: 5px;">Erreur d\'upload : ' . h($_GET['upload_error']) . '</div>';
    }
    ?>

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
                        <label>
                            <input type="checkbox" name="ready_to_breed" <?= ($snake['ready_to_breed'] == 1) ? 'checked' : '' ?>>
                            Prêt pour la reproduction
                        </label>
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
            Commentaire : <?= $snake['comment'] ? nl2br(h($snake['comment'])) : 'N/A' ?><br>
            Prêt pour la reproduction : <strong><?= ($snake['ready_to_breed'] == 1) ? 'Oui' : 'Non' ?></strong> </p>
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
    </div>

    <div class="card">
        <h3>Photos du serpent</h3>
        <details>
            <summary>Ajouter une nouvelle photo</summary>
            
            <form id="photo-upload-form" action="upload_photo.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= (int)$snake['id'] ?>">
                
                <label for="photo_input">Choisir une image :</label>
                <input type="file" id="photo_input" accept="image/*" required>
                
                <input type="hidden" name="photo_data" id="photo_data">
                
                <input type="hidden" name="photo_extension" id="photo_extension" value="jpg"> 

                <div style="margin-top: 15px;">
                    <div id="image-container-wrapper" style="max-width: 500px; max-height: 500px; margin-bottom: 10px; display: none; overflow: hidden;">
                        <img id="image-to-crop" style="max-width: 100%; display: block;">
                    </div>
                    
                    <div id="cropper-controls" style="display: none; gap: 10px; margin-bottom: 10px;">
                        <button type="button" class="btn secondary small" id="rotate-left" title="Pivoter à gauche">↺ -45°</button>
                        <button type="button" class="btn secondary small" id="rotate-right" title="Pivoter à droite">↻ +45°</button>
                        <button type="button" class="btn secondary small" id="zoom-in" title="Zoomer">+</button>
                        <button type="button" class="btn secondary small" id="zoom-out" title="Dézoomer">-</button>
                        <button type="button" class="btn secondary small" id="reset-cropper" title="Réinitialiser">Réinitialiser</button>
                    </div>

                </div>

                <button type="submit" class="btn ok" id="upload-btn" disabled>Envoyer la photo</button>
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

    <div class="card">
        <h3>Repas</h3>
        <a class="btn" href="add_feeding.php?snake_id=<?= (int)$snake['id'] ?>">+ Ajouter un repas</a>

<?php
// On ne garde que les repas pris (non refusés et non en attente)
$validFeedings = array_filter($feedings, function($f) {
    return empty($f['refused']) && empty($f['pending']);
});

// On les trie par date croissante
usort($validFeedings, function($a, $b) {
    return strtotime($a['date']) <=> strtotime($b['date']);
});
?>

<?php if (!empty($validFeedings)): ?>
    <h4>📅 Frise chronologique des repas pris</h4>
    <div class="timeline-scroll">
        <div class="timeline">
            <?php
            $previousDate = null;
            foreach ($validFeedings as $f):
                $dateLabel = date('d/m/Y', strtotime($f['date']));
                $tooltip = htmlspecialchars("{$f['meal_type']} ({$f['count']})");

                // Icône selon le type de repas
                $icon = "🐭";
                if (stripos($f['meal_type'], 'rat') !== false) $icon = "🐀";
                if (stripos($f['meal_type'], 'poussin') !== false) $icon = "🐥";
                if (stripos($f['meal_type'], 'adulte') !== false) $icon = "🥩";

                // Calcul de l'écart en jours avec le repas précédent
                $alertIcon = "";
                if ($previousDate) {
                    $daysDiff = (strtotime($f['date']) - strtotime($previousDate)) / 86400;
                    if ($daysDiff > 10) {
                        $alertIcon = "❗"; // écart supérieur à 10 jours
                        $tooltip .= " — ⚠️ Écart de " . round($daysDiff) . " jours";
                    }
                }
                $previousDate = $f['date'];
            ?>
                <div class="timeline-event" title="<?= $tooltip ?>">
                    <div class="timeline-icon"><?= $icon ?></div>
                    <?php if ($alertIcon): ?>
                        <div class="timeline-alert" title="Écart de plus de 10 jours"><?= $alertIcon ?></div>
                    <?php endif; ?>
                    <div class="timeline-label"><?= $dateLabel ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <div class="helper">Aucun repas pris enregistré pour ce serpent.</div>
<?php endif; ?>
<p>Nombre de repas pris : <strong><?= (int)$mealCount ?></strong></p>

        <?php if ($feedings): ?>
            <div style="overflow:auto;">
                <table>
                    <thead>
                        <tr>
                            <th><a class="btn primary small" href="bulk_edit_feeding.php?snake_id=<?= (int)$snake['id'] ?>">Modifier la sélection</a></th>
                            <th>Date</th>
                            <th>Type de proie</th>
                            <th>Taille</th>
                            <th>État de la proie</th>
                            <th>Nombre</th>
                            <th>Refusé</th>
                            <th>En attente</th>
                            <th>Commentaire</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedings as $f): ?>
                            <?php
                            // ************************************************************
                            // * CORRECTION APPLIQUÉE ICI : Extraction du type de rongeur *
                            // ************************************************************
                            // La colonne 'meal_type' contient "souris adulte", on extrait "souris".
                            $full_meal_type = $f['meal_type'] ?: 'N/A';
                            $meal_type_parts = explode(' ', $full_meal_type);
                            $rongeur_type = $meal_type_parts[0] ?? $full_meal_type;
                            // ************************************************************
                            ?>
                            <tr>
                                <td><input type="checkbox" name="feeding_ids[]" value="<?= (int)$f['id'] ?>" form="bulk-edit-form"></td>
                                <td><?= date('d/m/Y', strtotime($f['date'])) ?></td>
                                <td><?= h($rongeur_type) ?></td> <td><?= h($f['meal_size']) ?: 'N/A' ?></td> <td><?= h($f['prey_type']) ?: 'N/A' ?></td>
                                <td><?= (int)$f['count'] ?></td>
                                <td><?= $f['refused'] ? 'Oui' : 'Non' ?></td>
                                <td><?= $f['pending'] ? 'Oui' : 'Non' ?></td>
                                <td><?= h($f['notes']) ?: 'N/A' ?></td>
                                <td style="display:flex;gap:.4rem;">
                                    <a class="btn secondary" href="edit_feeding.php?id=<?= (int)$f['id'] ?>">Éditer</a>
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
                <form id="bulk-edit-form" action="bulk_edit_feeding.php" method="get" style="display:none;">
                    <input type="hidden" name="snake_id" value="<?= (int)$snake['id'] ?>">
                </form>
            </div>
        <?php else: ?>
            <div class="helper">Aucun repas enregistré pour ce serpent.</div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Mues</h3>
        <a class="btn" href="add_shed.php?snake_id=<?= (int)$snake['id'] ?>">+ Ajouter une mue</a>
        
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
                                <td><?= ($s['complete'] == 1) ? 'Complète' : 'Incomplète' ?></td>
                                <td><?= h($s['comment']) ?: 'N/A' ?></td>
                                <td style="display:flex;gap:.4rem;">
                                    <a class="btn secondary" href="edit_shed.php?id=<?= (int)$s['id'] ?>">Éditer</a>
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

    <div class="card">
        <h3>Soins</h3>
        <a class="btn" href="add_care.php?snake_id=<?= (int)$snake['id'] ?>">+ Ajouter un soin</a>
        
        <?php if ($cares): ?>
            <div style="overflow:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type de soin</th>
                            <th>Commentaire</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cares as $c): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($c['date'])) ?></td>
                                <td><?= h($c['care_type']) ?></td>
                                <td><?= h($c['comment']) ?: 'N/A' ?></td>
                                <td style="display:flex;gap:.4rem;">
                                    <a class="btn secondary" href="edit_care.php?id=<?= (int)$c['id'] ?>">Éditer</a>
                                    <form method="post" action="delete_care.php" onsubmit="return confirm('Supprimer ce soin ?')">
                                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
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
            <div class="helper">Aucun soin enregistré pour ce serpent.</div>
        <?php endif; ?>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const fileInput = document.getElementById('photo_input');
        const image = document.getElementById('image-to-crop');
        const uploadBtn = document.getElementById('upload-btn');
        const photoDataInput = document.getElementById('photo_data');
        const photoExtInput = document.getElementById('photo_extension');
        const cropperControls = document.getElementById('cropper-controls');
        const imageContainerWrapper = document.getElementById('image-container-wrapper');
        const form = document.getElementById('photo-upload-form');
        
        let cropper;

        fileInput.addEventListener('change', (e) => {
            const files = e.target.files;
            if (files && files.length > 0) {
                const file = files[0];
                
                // Extraction de l'extension
                const ext = file.name.split('.').pop().toLowerCase();
                // On va forcer l'export en JPG pour la soumission par défaut
                photoExtInput.value = 'jpg'; 

                const reader = new FileReader();
                reader.onload = (event) => {
                    image.src = event.target.result;
                    imageContainerWrapper.style.display = 'block';
                    cropperControls.style.display = 'flex';
                    uploadBtn.disabled = false;

                    // Détruire l'instance précédente si elle existe
                    if (cropper) {
                        cropper.destroy();
                    }
                    
                    // Initialisation de Cropper.js
                    cropper = new Cropper(image, {
                        aspectRatio: NaN, // Pas de ratio fixe, l'utilisateur peut choisir
                        viewMode: 1, // Restreint le recadrage au conteneur
                        autoCropArea: 0.9, // 90% de la zone d'image est recadrée par défaut
                        responsive: true,
                        background: true
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        // Gestion des boutons de Cropper avec vérification de l'existence de 'cropper'
        document.getElementById('rotate-left').addEventListener('click', () => { 
            if (cropper) cropper.rotate(-45);
        });
        document.getElementById('rotate-right').addEventListener('click', () => { 
            if (cropper) cropper.rotate(45);
        });
        document.getElementById('zoom-in').addEventListener('click', () => { 
            if (cropper) cropper.zoom(0.1);
        });
        document.getElementById('zoom-out').addEventListener('click', () => { 
            if (cropper) cropper.zoom(-0.1);
        });
        document.getElementById('reset-cropper').addEventListener('click', () => { 
            if (cropper) cropper.reset();
        });


        // Soumission du formulaire
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            if (!cropper) {
                alert('Veuillez sélectionner une image d\'abord.');
                return;
            }

            // Récupère les données de l'image recadrée/pivotée au format Base64
            // On force l'export en JPEG pour un meilleur rapport qualité/taille. 
            const mimeType = 'image/jpeg';
            const quality = 0.8;
            photoExtInput.value = 'jpg'; // On s'assure que le backend utilise JPG

            const canvas = cropper.getCroppedCanvas({
                maxWidth: 1200, // Limite la taille de l'image finale
                maxHeight: 1200,
            });

            // Conversion du canvas en Base64 et suppression de l'entête "data:..."
            const croppedImageBase64 = canvas.toDataURL(mimeType, quality).split(',')[1];
            
            // Mise à jour du champ masqué et soumission du formulaire
            photoDataInput.value = croppedImageBase64;
            
            // Désactivation du bouton pour éviter les doubles clics
            uploadBtn.disabled = true; 
            
            form.submit();
        });
    });
</script>

</body>
</html>
