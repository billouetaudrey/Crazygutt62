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

// Prépare le dossier pour les images originales
$uploadDir = __DIR__ . '/uploads/';
// Prépare le dossier pour les vignettes
$thumbDir = __DIR__ . '/uploads/thumbnails/';

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    header("Location: " . base_url('snake.php?id=' . $id) . "&upload_error=" . urlencode("Impossible de créer le dossier uploads."));
    exit;
}
if (!is_dir($thumbDir) && !mkdir($thumbDir, 0755, true)) {
    header("Location: " . base_url('snake.php?id=' . $id) . "&upload_error=" . urlencode("Impossible de créer le dossier thumbnails."));
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

// Nom unique pour les deux fichiers
$filename = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
$originalDest = $uploadDir . $filename;
$thumbDest = $thumbDir . $filename;

// Déplacement de l'original
if (!move_uploaded_file($_FILES['photo']['tmp_name'], $originalDest)) {
    header("Location: " . base_url('snake.php?id=' . $id) . "&upload_error=" . urlencode("Erreur lors du déplacement du fichier."));
    exit;
}

// Redimensionnement et sauvegarde de la vignette
$maxWidth = 300;
$maxHeight = 300;

list($width, $height) = getimagesize($originalDest);
$ratio = $width / $height;

if ($maxWidth / $maxHeight > $ratio) {
    $newWidth = $maxHeight * $ratio;
    $newHeight = $maxHeight;
} else {
    $newWidth = $maxWidth;
    $newHeight = $maxWidth / $ratio;
}

$newImage = imagecreatetruecolor((int)$newWidth, (int)$newHeight);

switch ($mime) {
    case 'image/jpeg':
        $source = imagecreatefromjpeg($originalDest);
        break;
    case 'image/png':
        $source = imagecreatefrompng($originalDest);
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        break;
    case 'image/gif':
        $source = imagecreatefromgif($originalDest);
        break;
    case 'image/webp':
        $source = imagecreatefromwebp($originalDest);
        break;
    default:
        $source = null;
}

if ($source) {
    imagecopyresampled($newImage, $source, 0, 0, 0, 0, (int)$newWidth, (int)$newHeight, $width, $height);

    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($newImage, $thumbDest, 80);
            break;
        case 'image/png':
            imagepng($newImage, $thumbDest, 9);
            break;
        case 'image/gif':
            imagegif($newImage, $thumbDest);
            break;
        case 'image/webp':
            imagewebp($newImage, $thumbDest);
            break;
    }
    imagedestroy($newImage);
    imagedestroy($source);
}

// Enregistre en base
$stmt = $pdo->prepare("INSERT INTO photos (snake_id, filename) VALUES (?, ?)");
$stmt->execute([$id, $filename]);

header("Location: " . base_url('snake.php?id=' . $id));
exit;
