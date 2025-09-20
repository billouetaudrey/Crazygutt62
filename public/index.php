<?php
try {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/functions.php';

    // Handle create snake form submission first
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
        $name = trim($_POST['name'] ?? '');
        $sex = $_POST['sex'] ?? 'M';
        $morph = trim($_POST['morph'] ?? '');
        $birth_year = (int)($_POST['birth_year'] ?? 0);
        $weight = $_POST['weight'] !== '' ? (float)$_POST['weight'] : null;
        $comment = trim($_POST['comment'] ?? '');
        $default_meal_type = $_POST['default_meal_type'] ?? null;

        if ($name === '') $errors[] = 'Le nom est requis.';
        if (!is_valid_year($birth_year)) $errors[] = 'Année de naissance invalide.';

        if (!$errors) {
            $stmt = $pdo->prepare('INSERT INTO snakes (name, sex, morph, birth_year, weight, comment, default_meal_type) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $sex, $morph, $birth_year, $weight, $comment ?: null, $default_meal_type]);
            header('Location: ' . base_url('index.php'));
            exit;
        }
    }

    // Fetch all snakes, including the profile_photo_id
    $snakes = $pdo->query('
        SELECT
            s.*,
            COUNT(f.id) AS meal_count
        FROM
            snakes s
        LEFT JOIN
            feedings f ON s.id = f.snake_id AND f.refused = 0
        GROUP BY
            s.id
        ORDER BY
            s.name * 1 ASC
    ')->fetchAll();

    // Comptage par tranche d'âge
    $now = new DateTime();
    $baby = $sub = $adult = 0;
    foreach ($snakes as $s) {
        if (!$s['birth_year'] || $s['birth_year'] == '0000') continue;
        $age = (int)$now->format('Y') - (int)$s['birth_year'];
        if ($age < 1) {
            $baby++;
        } elseif ($age < 2) {
            $sub++;
        } else {
            $adult++;
        }
    }

    // Comptage par type de repas basé uniquement sur la table 'snakes'
    $mealCountsStmt = $pdo->prepare("
        SELECT default_meal_type, COUNT(*) AS count
        FROM snakes
        WHERE default_meal_type IS NOT NULL AND default_meal_type != ''
        GROUP BY default_meal_type
    ");
    $mealCountsStmt->execute();
    $mealCounts = $mealCountsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $mealTypes = ['rosé', 'blanchon', 'sauteuse', 'adulte'];

    // Fetch all clutches (pontes)
    $clutchesStmt = $pdo->prepare("
        SELECT c.*, sm.name AS male_name, sf.name AS female_name
        FROM clutches c
        LEFT JOIN snakes sm ON c.male_id = sm.id
        LEFT JOIN snakes sf ON c.female_id = sf.id
        ORDER BY c.lay_date DESC
    ");
    $clutchesStmt->execute();
    $clutches = $clutchesStmt->fetchAll();

    // Fetch all gestations (accouplements)
    $gestationsStmt = $pdo->prepare("
        SELECT g.*, sm.name AS male_name, sf.name AS female_name
        FROM gestations g
        LEFT JOIN snakes sm ON g.male_id = sm.id
        LEFT JOIN snakes sf ON g.female_id = sf.id
        ORDER BY g.pairing_date DESC
    ");
    $gestationsStmt->execute();
    $gestations = $gestationsStmt->fetchAll();
    
    // NOUVEAU: Récupérer les serpents avec des repas en attente
    $pending_snakes = $pdo->query("
        SELECT s.name
        FROM snakes s
        JOIN feedings f ON s.id = f.snake_id
        WHERE f.pending = 1 AND f.refused = 0
        GROUP BY s.id
    ")->fetchAll(PDO::FETCH_COLUMN);

    // Sépare les serpents par catégorie d'âge et récupère la dernière photo
    // + Ajout des drapeaux d'alerte pour les repas
    $now = (int)(new DateTime())->format('Y');
    $babies = [];
    $subadults = [];
    $adults = [];

    $alert_hungry_baby = false;
    $alert_hungry_subadults = [];
    $alert_hungry_adults = [];

    // Récupère les serpents avec leur photo de profil ou la dernière photo
    $snakesWithPhotos = [];
    foreach ($snakes as $s) {
        $photo = null;
        if ($s['profile_photo_id'] > 0) {
            $stmt = $pdo->prepare("SELECT filename FROM photos WHERE id = ?");
            $stmt->execute([$s['profile_photo_id']]);
            $photo = $stmt->fetchColumn();
        }
        if (!$photo) {
            $stmt = $pdo->prepare("SELECT filename FROM photos WHERE snake_id = ? ORDER BY uploaded_at DESC LIMIT 1");
            $stmt->execute([$s['id']]);
            $photo = $stmt->fetchColumn();
        }
        $s['photo'] = $photo;
        $snakesWithPhotos[] = $s;

        // Vérification du dernier repas et de l'âge pour définir les alertes
        $q = $pdo->prepare("SELECT MAX(date) AS last_date FROM feedings WHERE snake_id=? AND refused=0");
        $q->execute([$s['id']]);
        $lastMeal = $q->fetch();
        $days_since_meal = null;
        if ($lastMeal['last_date']) {
            $days_since_meal = (new DateTime($lastMeal['last_date']))->diff(new DateTime())->days;
        }

        if (!$s['birth_year'] || $s['birth_year'] == '0000') continue;
        $age = $now - (int)$s['birth_year'];

        $needs_feeding_alert = $days_since_meal !== null && $days_since_meal > 7;

        if ($age < 1) {
            $babies[] = $s;
            if ($needs_feeding_alert) $alert_hungry_baby = true;
        } elseif ($age >= 1 && $age < 2) {
            $subadults[] = $s;
            if ($needs_feeding_alert) $alert_hungry_subadults[] = $s['name'];
        } else {
            $adults[] = $s;
            if ($needs_feeding_alert) $alert_hungry_adults[] = $s['name'];
        }
    }
    
    // Définir le chemin de base pour les vignettes
    define('THUMB_DIR', 'uploads/thumbnails/');

    // Fonction pour générer les fiches de serpent avec cases à cocher 
    function render_snake_cards($list, $pdo) { 
        if (!$list) { 
            return '<div class="helper">Aucun serpent dans cette catégorie.</div>'; 
        } 
        ob_start(); 
        ?> 
        <div class="snake-grid"> 
            <?php foreach ($list as $s):  ?> 
                <?php
                $q = $pdo->prepare("SELECT MAX(date) AS last_date FROM feedings WHERE snake_id=? AND refused=0");
                $q->execute([$s['id']]);
                $lastMeal = $q->fetch();
                $days_since_meal = null;
                if ($lastMeal['last_date']) {
                    $days_since_meal = (new DateTime($lastMeal['last_date']))->diff(new DateTime())->days;
                }
                $needs_feeding_alert = $days_since_meal !== null && $days_since_meal > 7;
                ?>
                <div class="snake-card"> 
                    <input type="checkbox" name="snake_ids[]" value="<?= (int)$s['id'] ?>" style="position: absolute; top: 10px; left: 10px; z-index: 10;"> 
                    <a href="<?= base_url('snake.php?id=' . (int)$s['id']) ?>"> 
                        <?php if ($needs_feeding_alert): ?>
                            <div class="card-badge warning">+7 jours</div>
                        <?php endif; ?>
                        <div class="snake-photo"> 
                            <?php if ($s['photo']): ?> 
                                <img src="<?= base_url(THUMB_DIR . h($s['photo'])) ?>" alt="Photo de <?= h($s['name']) ?>"> 
                            <?php else: ?> 
                                <div class="no-photo">📸</div> 
                            <?php endif; ?> 
                        </div> 
                        <div class="snake-info"> 
                            <h4 class="snake-name"><?= h($s['name']) ?></h4> 
                            <span class="snake-sex"><?= sex_badge($s['sex']) ?></span> 
                            <p class="snake-morph"><?= h($s['morph']) ?></p> 
                            <p class="snake-age"><?= compute_age_from_year((int)$s['birth_year']) ?> ans</p> 
                        </div> 
                    </a> 
                </div> 
            <?php endforeach; ?> 
        </div> 
        <?php 
        return ob_get_clean(); 
    } 
    
    // Petite fonction pour générer le tableau des bébés 
    function render_snake_table($list, $pdo) { 
        if (!$list) { 
            return '<div class="helper">Aucun serpent dans cette catégorie.</div>'; 
        } 
        ob_start(); 
        ?> 
        <div style="overflow:auto;"> 
            <table> 
                <thead> 
                    <tr> 
                        <th><input type="checkbox" class="select-all"></th> 
                        <th>Nom</th> 
                        <th>Sexe</th> 
                        <th>Phase</th> 
                        <th>Âge</th> 
                        <th>Dernier repas</th> 
                        <th>Jours écoulés</th> 
                        <th>Repas pris</th> 
                        <th>Actions</th> 
                    </tr> 
                </thead> 
                <tbody> 
                <?php foreach ($list as $s): ?> 
                    <?php 
                    $last_date = '-'; 
                    $days_since_meal = '-'; 
                    $alert_icon = '<span style="color:red;font-size:1rem;">⚠️</span>'; 

                    $q = $pdo->prepare("SELECT MAX(date) AS last_date FROM feedings WHERE snake_id=? AND refused=0"); 
                    $q->execute([$s['id']]); 
                    $lastMeal = $q->fetch(); 

                    if ($lastMeal['last_date']) { 
                        $last_date = $lastMeal['last_date']; 
                        $days_since_meal = (new DateTime($last_date))->diff(new DateTime())->days; 
                        if ($days_since_meal <= 7) { 
                            $alert_icon = ''; 
                        } 
                    } 
                    ?> 
                    <tr> 
                        <td><input type="checkbox" name="snake_ids[]" value="<?= (int)$s['id'] ?>"></td> 
                        <td><?= h($s['name']) ?></td> 
                        <td><?= sex_badge($s['sex']) ?></td> 
                        <td><?= h($s['morph']) ?></td> 
                        <td><?= compute_age_from_year((int)$s['birth_year']) ?> ans</td> 
                        <td> 
                            <?php if ($last_date && $last_date !== '-'): ?> 
                                <?= date('d/m/Y', strtotime($last_date)) ?> 
                            <?php else: ?> 
                                <?= $last_date ?> 
                            <?php endif; ?> 
                        </td> 
                        <td> 
                            <?= $days_since_meal ?> 
                            <?= $alert_icon ?> 
                        </td> 
                        <td><?= (int)$s['meal_count'] ?></td> 
                        <td style="display:flex;gap:.4rem;"> 
                            <a class="btn" href="<?= base_url('snake.php?id=' . (int)$s['id']) ?>">Ouvrir</a> 
                            <a class="btn secondary" href="<?= base_url('edit_snake.php?id=' . (int)$s['id']) ?>">Éditer</a> 
                            <form method="post" action="delete.php" onsubmit="return confirm('Supprimer définitivement ce serpent ?');"> 
                                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>"> 
                                <button class="btn danger" type="submit">🗑</button> 
                            </form> 
                        </td> 
                    </tr> 
                <?php endforeach; ?> 
                </tbody> 
            </table> 
        </div> 
        <?php 
        return ob_get_clean(); 
    } 

} catch (PDOException $e) { 
    // Displays a database connection error 
    die("Erreur de connexion à la base de données : " . $e->getMessage()); 
} 
?> 
<!DOCTYPE html> 
<html lang="fr"> 
<head> 
    <meta charset="utf-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1"> 
    <title>Pantherophis — Suivi</title> 
    <link rel="stylesheet" href="assets/style.css"> 
    <script src="assets/theme.js" defer></script> 
</head> 
<body> 
<div class="container"> 
    <div class="header"> 
        <div class="brand">🐍 Pantherophis — Suivi</div> 
        <button class="theme-toggle" onclick="toggleTheme()" title="Basculer thème">🌙/☀️</button> 
        <div style="margin-top:1rem; text-align:right;"> 
            <a class="btn secondary" href="gestion_donnees.php">⚙️ Gestion des données</a>
            <a class="btn secondary" href="https://billouetaudrey.ovh/gestion_naissances/">⚙️ Gestion des ventes/dépenses</a> 
            <a class="btn secondary" href="stats.php">📊 Statistiques</a>          
            <a class="btn secondary" href="https://www.morphmarket.com/c/reptiles/colubrids/corn-snakes/genetic-calculator/" target="_blank">🧬 Génétique</a>
        </div> 
    </div> 
    
    <div class="card">
        <details>
            <summary>
                <h2>⚠️ Alertes</h2>
            </summary>
            <?php if (!empty($pending_snakes)): ?>
                <div class="card alert warning" style="margin-bottom: 1rem;">
                    ⏳ **Repas en attente :** Ces serpents n'ont pas encore eu leur repas :
                    <ul>
                        <?php foreach ($pending_snakes as $snake_name): ?>
                            <li><?= h($snake_name) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if ($alert_hungry_baby): ?>
                <div class="card alert warning" style="margin-bottom: 1rem;">
                    ⚠️ Attention : au moins un bébé n'a pas mangé depuis plus de 7 jours !
                </div>
            <?php endif; ?>
            <?php if (!empty($alert_hungry_subadults)): ?>
                <div class="card alert warning" style="margin-bottom: 1rem;">
                    ⚠️ Attention, ces sub-adultes n'ont pas mangé depuis plus de 7 jours :
                    <ul>
                        <?php foreach ($alert_hungry_subadults as $snake_name): ?>
                            <li><?= h($snake_name) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if (!empty($alert_hungry_adults)): ?>
                <div class="card alert warning">
                    ⚠️ Attention, ces adultes n'ont pas mangé depuis plus de 7 jours :
                    <ul>
                        <?php foreach ($alert_hungry_adults as $snake_name): ?>
                            <li><?= h($snake_name) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if (!$alert_hungry_baby && empty($alert_hungry_subadults) && empty($alert_hungry_adults) && empty($pending_snakes)): ?>
                <div class="helper">🎉 Aucune alerte en cours. Tous les serpents ont été nourris récemment.</div>
            <?php endif; ?>
        </details>
    </div>

    <div class="card"> 
        <details> 
            <summary> 
                <h2>Ajouter un serpent</h2> 
            </summary> 
            <?php if ($errors): ?> 
                <div class="card" style="background:transparent;border-color:var(--danger);"> 
                    <?php foreach ($errors as $e): ?> 
                        <div>• <?= h($e) ?></div> 
                    <?php endforeach; ?> 
                </div> 
            <?php endif; ?> 
            <form method="post" action="index.php"> 
                <input type="hidden" name="action" value="create"> 
                <div class="grid"> 
                    <div> 
                        <label>Nom *</label> 
                        <input type="text" name="name" required> 
                    </div> 
                    <div> 
                        <label>Sexe *</label> 
                        <select name="sex"> 
                            <option value="M">Mâle</option> 
                            <option value="F">Femelle</option> 
                            <option value="I">Indéfini</option> 
                        </select> 
                    </div> 
                    <div> 
                        <label>Phase (morph)</label> 
                        <input type="text" name="morph" placeholder="Ex. Anery, Amel, etc."> 
                    </div> 
                    <div> 
                        <label>Année de naissance *</label> 
                        <input type="number" name="birth_year" min="1900" max="<?= (int)date('Y') ?>" required> 
                    </div> 
                    <div> 
                        <label>Poids (g, facultatif)</label> 
                        <input type="number" step="0.01" name="weight" placeholder="Ex. 120"> 
                    </div> 
                    <div> 
                        <label>Type de repas par défaut</label> 
                        <select name="default_meal_type"> 
                            <option value="">(Aucun)</option> 
                            <option value="rosé">Rosé</option> 
                            <option value="blanchon">Blanchon</option> 
                            <option value="sauteuse">Sauteuse</option> 
                            <option value="adulte">Adulte</option> 
                        </select> 
                    </div> 
                    <div style="grid-column: 1 / 3;"> 
                        <label>Commentaire</label> 
                        <input type="text" name="comment" placeholder="Notes libres"> 
                    </div> 
                </div> 
                <div style="margin-top:.8rem;"> 
                    <button type="submit" class="btn ok">Ajouter</button> 
                </div> 
            </form> 
        </details> 
    </div> 

    <div class="card" style="text-align:center;"> 
        <h2>Répartition par âge</h2> 
        <div style="display:flex; justify-content:space-around; margin-top:1rem;"> 
            <div> 
                <strong>🐍 Bébés (< 1 an)</strong><br> 
                <?= $baby ?> 
            </div> 
            <div> 
                <strong>🟠 Sub-adultes (1–2 ans)</strong><br> 
                <?= $sub ?> 
            </div> 
            <div> 
                <strong>🟢 Adultes (> 2 ans)</strong><br> 
                <?= $adult ?> 
            </div> 
        </div> 
    </div> 
    
    <div class="card" style="text-align:center;"> 
        <h2>Répartition par type de repas</h2> 
        <div style="display:flex; justify-content:space-around; margin-top:1rem;"> 
            <?php foreach ($mealTypes as $type): ?> 
            <div> 
                <strong><?= ucwords($type) ?></strong><br> 
                <?= $mealCounts[$type] ?? 0 ?> 
            </div> 
            <?php endforeach; ?> 
        </div> 
    </div> 

    <div class="card"> 
        <h2>Mes serpents</h2> 
        <?php if (empty($snakes)): ?> 
            <div class="helper">Aucun serpent pour l'instant.</div> 
        <?php else: ?> 
            <details> 
                <summary><h3>🐍 Bébés (< 1 an) (<?= count($babies) ?>)</h3></summary> 
                <form method="get" action="bulk_edit_snakes.php"> 
                    <?= render_snake_table($babies, $pdo) ?> 
                    <div style="margin-top:1rem;"> 
                        <button type="submit" class="btn secondary">Éditer les bébés</button> 
                        <button type="submit" formaction="bulk_feeding.php?meal_type=rosé" class="btn secondary">Ajouter un repas</button>
                    </div> 
                </form> 
            </details> 
            <details style="margin-top:1rem;"> 
                <summary><h3>🟠 Sub-adultes (1–2 ans) (<?= count($subadults) ?>)</h3></summary> 
                <form method="get" action="bulk_edit_snakes.php"> 
                    <label style="margin-bottom: 1rem; font-weight: bold;"> 
                        <input type="checkbox" class="select-all" id="select-all-subadults"> 
                        Sélectionner tout 
                    </label> 
                    <?= render_snake_cards($subadults, $pdo) ?> 
                    <div style="margin-top:1rem;"> 
                        <button type="submit" class="btn secondary">Éditer les sub-adultes</button> 
                        <button type="submit" formaction="bulk_feeding.php?meal_type=sauteuse" class="btn secondary">Ajouter un repas</button>
                    </div> 
                </form> 
            </details> 
            <details style="margin-top:1rem;"> 
                <summary><h3>🟢 Adultes (> 2 ans) (<?= count($adults) ?>)</h3></summary> 
                <div style="margin-bottom: 1rem;">
                    <button type="button" class="btn primary" onclick="printCouple()">
                        🖨️ Créer une étiquette de couple
                    </button>
                </div>
                <form method="get" action="bulk_edit_snakes.php"> 
                    <label style="margin-bottom: 1rem; font-weight: bold;"> 
                        <input type="checkbox" class="select-all" id="select-all-adults"> 
                        Sélectionner tout 
                    </label> 
                    <?= render_snake_cards($adults, $pdo) ?> 
                    <div style="margin-top:1rem;"> 
                        <button type="submit" class="btn secondary">Éditer les adultes</button> 
                        <button type="submit" formaction="bulk_feeding.php?meal_type=adulte" class="btn secondary">Ajouter un repas</button>
                    </div> 
                </form> 
            </details> 
        <?php endif; ?> 
    </div> 

    <div class="card">
        <h3>Accouplements</h3>
        <a class="btn" href="add_gestation.php">+ Ajouter accouplement</a>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Date accouplement</th>
                        <th>Père</th>
                        <th>Mère</th>
                        <th>Ponte min. (35J)</th>
                        <th>Ponte max. (43J)</th>
                        <th>Jours restants</th>
                        <th>Commentaire</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($gestations as $g):
                    $today = new DateTime();
                    $ponte_min_date = new DateTime($g['pairing_date']);
                    $ponte_min_date->modify('+35 days');
                    $ponte_max_date = new DateTime($g['pairing_date']);
                    $ponte_max_date->modify('+43 days');
                    
                    $status = '';
                    if ($today > $ponte_max_date) {
                        $status = 'Terminé';
                    } elseif ($today >= $ponte_min_date && $today <= $ponte_max_date) {
                        $status = 'En cours';
                    } else {
                        $remaining_min = $today->diff($ponte_min_date)->days;
                        $remaining_max = $today->diff($ponte_max_date)->days;
                        $status = "J-$remaining_min à J-$remaining_max";
                    }
                ?>
<tr>
    <td><?= date('d/m/Y', strtotime($g['pairing_date'])) ?></td>
    <td><a href="snake.php?id=<?= (int)$g['male_id'] ?>"><?= h($g['male_name']) ?></a></td>
    <td><a href="snake.php?id=<?= (int)$g['female_id'] ?>"><?= h($g['female_name']) ?></a></td>
    <td><?= $ponte_min_date->format('d/m/Y') ?></td>
    <td><?= $ponte_max_date->format('d/m/Y') ?></td>
    <td><?= $status ?></td>
    <td><?= h($g['comment']) ?></td>
    <td>
        <form method="post" action="delete_gestation.php" onsubmit="return confirm('Supprimer cet accouplement ?')">
            <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
            <input type="hidden" name="redirect_to" value="index.php">
            <button class="btn danger" type="submit">🗑</button>
        </form>
    </td>
</tr>

                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card"> 
        <h3>Pontes</h3> 
        <a class="btn" href="add_clutch.php">+ Ajouter ponte</a> 
    <div style="overflow-x: auto;">
        <table> 
            <thead> 
                <tr> 
                    <th>Date ponte</th> 
                    <th>Père</th> 
                    <th>Mère</th> 
                    <th>Nb œufs</th> 
                    <th>Éclosion min. (55J)</th>
                    <th>Éclosion max. (61J)</th>
                    <th>Jours restant</th> 
                    <th>Commentaire</th> 
                    <th>Action</th> 
                </tr> 
            </thead> 
            <tbody> 
            <?php foreach ($clutches as $c): 
                $today = new DateTime();
                $hatch_date_min = new DateTime($c['lay_date']);
                $hatch_date_min->modify('+55 days');
                $hatch_date_max = new DateTime($c['lay_date']);
                $hatch_date_max->modify('+61 days');
                
                $hatch_status = ''; 
                if ($today > $hatch_date_max) { 
                    $hatch_status = 'Éclos'; 
                } elseif ($today >= $hatch_date_min && $today <= $hatch_date_max) {
                    $hatch_status = 'En cours';
                } else {
                    $remaining_min = $today->diff($hatch_date_min)->days;
                    $remaining_max = $today->diff($hatch_date_max)->days;
                    $hatch_status = "J-$remaining_min à J-$remaining_max";
                }
            ?> 
<tr> 
    <td><?= date('d/m/Y', strtotime($c['lay_date'])) ?></td> 
    <td><a href="snake.php?id=<?= (int)$c['male_id'] ?>"><?= h($c['male_name']) ?></a></td> 
    <td><a href="snake.php?id=<?= (int)$c['female_id'] ?>"><?= h($c['female_name']) ?></a></td> 
    <td><?= (int)$c['egg_count'] ?></td> 
    <td><?= $hatch_date_min->format('d/m/Y') ?></td>
    <td><?= $hatch_date_max->format('d/m/Y') ?></td>
    <td><?= $hatch_status ?></td> 
    <td><?= h($c['comment']) ?></td>
    <td>
        <form method="post" action="delete_clutch.php" onsubmit="return confirm('Supprimer cette ponte ?')"> 
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>"> 
            <input type="hidden" name="redirect_to" value="index.php"> 
            <button class="btn danger" type="submit">🗑</button> 
        </form> 
    </td> 
</tr>

            <?php endforeach; ?> 
            </tbody> 
        </table> 
    </div> 

    <div align="center" class="card"> 
        <div style="overflow:auto;"> 
            <a class="btn" href="add_feeding.php">+ Ajouter un repas</a> 
            <a class="btn" href="add_shed.php">+ Ajouter une mue</a> 
        </div> 
    </div> 
</div> 

<script> 
    document.addEventListener('DOMContentLoaded', () => { 
        // Gère la sélection de toutes les checkboxes dans une table (pour les bébés) 
        const selectAllTables = document.querySelectorAll('table .select-all'); 
        selectAllTables.forEach(checkbox => { 
            checkbox.addEventListener('change', (e) => { 
                const table = e.target.closest('table'); 
                const checkboxes = table.querySelectorAll('tbody input[type="checkbox"]'); 
                checkboxes.forEach(cb => { 
                    cb.checked = e.target.checked; 
                }); 
            }); 
        }); 

        // Gère la sélection de toutes les checkboxes dans une grille de cartes (pour sub-adultes et adultes) 
        const selectAllGrids = document.querySelectorAll('form .select-all'); 
        selectAllGrids.forEach(checkbox => { 
            checkbox.addEventListener('change', (e) => { 
                const form = e.target.closest('form'); 
                const checkboxes = form.querySelectorAll('.snake-grid input[type="checkbox"][name="snake_ids[]"]'); 
                checkboxes.forEach(cb => { 
                    cb.checked = e.target.checked; 
                }); 
            }); 
        }); 

        // Gère le clic sur la carte pour basculer la checkbox 
        const snakeCards = document.querySelectorAll('.snake-card'); 
        snakeCards.forEach(card => { 
            card.addEventListener('click', (e) => { 
                // Empêche le basculement si le clic est sur un lien ou la checkbox elle-même 
                if (e.target.closest('a') || e.target.type === 'checkbox') { 
                    return; 
                } 
                const checkbox = card.querySelector('input[type="checkbox"]'); 
                if (checkbox) { 
                    checkbox.checked = !checkbox.checked; 
                } 
            }); 
        }); 

        // Fonction pour gérer l'impression d'un couple
        function printCouple() {
            const checkedSnakes = document.querySelectorAll('input[name="snake_ids[]"]:checked');

            if (checkedSnakes.length !== 2) {
                alert("Veuillez sélectionner exactement deux serpents pour créer une étiquette de couple.");
                return;
            }

            const id1 = checkedSnakes[0].value;
            const id2 = checkedSnakes[1].value;

            window.open(`print.php?id1=${id1}&id2=${id2}`, '_blank');
        }
    });
</script>
</body> 
</html>
