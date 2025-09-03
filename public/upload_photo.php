<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

// Vérifie l'existence du serpent
$stmt = $pdo->prepare("SELECT id FROM snakes WHERE id = ?");
$stmt->execute([$id]);
if (!$stmt->fetchColumn()) {
    http_response_code(404);
    die("Serpent introuvable.");
}

// Vérifie fichier
if (empty($_FILES['photo']['name']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $err = $_FILES['photo']['error'] ?? 'no_file';
    header("Location: " . base_url('snake.php?id=' . $id) . "&upload_error=" . urlencode("Upload invalide (code $err)."));
    exit;
}

// Prépare dossier
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    header("Location: " . base_url('snake.php?id=' . $id) . "&upload_error=" . urlencode("Impossible de créer le dossier uploads."));
    exit;
}

// Vérifie taille (10 Mo max)
$maxSize = 10 * 1024 * 1024;
if ($_FILES['photo']['size'] > $maxSize) {
    header("Location: " . base_url('snake.php?id=' . $id) . "&upload_error=" . urlencode("Fichier trop volumineux (>10 Mo)."));
    exit;
}

// Vérifie le type réel via MIME
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($_FILES['photo']['tmp_name']);
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
if (!isset($allowed[$mime])) {
    header("Location: " . base_url('snake.php?id=' . $id) . "&upload_error=" . urlencode("Format non autorisé."));
    exit;
}

// Nom unique
$filename = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
$dest = $uploadDir . $filename;

// Déplacement
if (!move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
    header("Location: " . base_url('snake.php?id=' . $id) . "&upload_error=" . urlencode("Erreur lors du déplacement du fichier."));
    exit;
}

// Enregistre en base
$stmt = $pdo->prepare("INSERT INTO photos (snake_id, filename) VALUES (?, ?)");
$stmt->execute([$id, $filename]);

header("Location: " . base_url('snake.php?id=' . $id));
exit;
