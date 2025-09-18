<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_GET['id'])) {
    header('Location: ' . base_url());
    exit;
}

$id = (int)$_GET['id'];

// Fetch shed record
$shedStmt = $pdo->prepare("SELECT * FROM sheds WHERE id = ?");
$shedStmt->execute([$id]);
$shed = $shedStmt->fetch();

if (!$shed) {
    echo "Mue non trouvée.";
    exit;
}

// Fetch snake info
$snakeStmt = $pdo->prepare("SELECT name FROM snakes WHERE id = ?");
$snakeStmt->execute([$shed['snake_id']]);
$snake = $snakeStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $complete = isset($_POST['complete']) ? 1 : 0;
    $comment = $_POST['comment'] ?: null;

    $stmt = $pdo->prepare("UPDATE sheds SET date = ?, complete = ?, comment = ? WHERE id = ?");
    $stmt->execute([$date, $complete, $comment, $id]);

    header('Location: ' . base_url('snake.php?id=' . (int)$shed['snake_id']));
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Modifier la mue — <?= h($snake['name']) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <a href="<?= base_url('snake.php?id=' . (int)$shed['snake_id']) ?>" class="btn secondary">← Retour au serpent</a>
        <div class="brand">Modifier la mue de <?= h($snake['name']) ?></div>
        <div class="empty"></div>
    </div>
    <div class="card">
        <form method="post" action="edit_shed.php?id=<?= (int)$id ?>">
            <div class="grid">
                <div>
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" value="<?= h($shed['date']) ?>" required>
                </div>
                <div>
                    <label>
                        <input type="checkbox" name="complete" <?= $shed['complete'] ? 'checked' : '' ?>> Mue complète
                    </label>
                </div>
                <div style="grid-column: 1 / 3;">
                    <label for="comment">Commentaire</label>
                    <textarea id="comment" name="comment" rows="3"><?= h($shed['comment']) ?></textarea>
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
