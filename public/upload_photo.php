<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$uploadError = null;

// Vérifie l'existence du serpent
$stmt = $pdo->prepare("SELECT id FROM snakes WHERE id = ?");
$stmt->execute([$id]);
if (!$stmt->fetchColumn()) {
    http_response_code(404);
    die("Serpent introuvable.");
}

// ----------------------------------------------------
// --- NOUVELLE LOGIQUE POUR GÉRER L'UPLOAD BASE64 ---
// ----------------------------------------------------

$sourceFile = null;
$mime = null;
$extension = null;
$originalFileName = null;

if (isset($_POST['photo_data']) && !empty($_POST['photo_data'])) {
    // Cas Base64 (via Cropper.js)
    $data = $_POST['photo_data'];
    $ext = isset($_POST['photo_extension']) ? strtolower($_POST['photo_extension']) : 'png';

    // Nettoyage de l'entête Data URI
    if (preg_match('/^data:([^;]+);base64,(.*)$/', $data, $matches)) {
        $mime = $matches[1];
        $base64_string = $matches[2];
    } else {
        // Supposons que c'est une simple chaîne Base64 sans Data URI (moins fiable)
        $mime = 'image/jpeg'; // Valeur par défaut si l'entête est manquant
        $base64_string = $data;
    }

    $sourceData = base64_decode($base64_string);
    $sourceSize = strlen($sourceData);

    // Vérifie taille (10 Mo max)
    $maxSize = 10 * 1024 * 1024;
    if ($sourceSize > $maxSize) {
        $uploadError = "Fichier trop volumineux (>10 Mo).";
    }

    // Définit le fichier source temporaire
    if (!$uploadError) {
        $tempFile = tempnam(sys_get_temp_dir(), 'cropper_');
        if ($tempFile) {
            file_put_contents($tempFile, $sourceData);
            $sourceFile = $tempFile;

            // Vérifie le type réel via MIME
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($sourceFile);
            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            ];
            if (!isset($allowed[$mime])) {
                $uploadError = "Format non autorisé après traitement. MIME: " . $mime;
                unlink($tempFile); // Supprime le fichier temp
                $sourceFile = null;
            } else {
                // L'extension pour la suite sera celle détectée ou celle de l'input si elle était valide
                $extension = $allowed[$mime];
            }
        } else {
            $uploadError = "Erreur lors de la création du fichier temporaire.";
        }
    }

} elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    // Cas d'upload standard via $_FILES (pour la compatibilité)
    $sourceFile = $_FILES['photo']['tmp_name'];
    $sourceSize = $_FILES['photo']['size'];
    $originalFileName = $_FILES['photo']['name'];

    // Vérifie taille (10 Mo max)
    $maxSize = 10 * 1024 * 1024;
    if ($sourceSize > $maxSize) {
        $uploadError = "Fichier trop volumineux (>10 Mo).";
    }

    // Vérifie le type réel via MIME
    if (!$uploadError) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($sourceFile);
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];
        if (!isset($allowed[$mime])) {
            $uploadError = "Format non autorisé.";
        } else {
            $extension = $allowed[$mime];
        }
    }

} else {
    // Upload invalide ou aucun fichier/donnée
    $err = $_FILES['photo']['error'] ?? 'no_file';
    $uploadError = "Upload invalide (code $err).";
}


// Redirection en cas d'erreur
if ($uploadError) {
    header("Location: " . base_url('snake.php?id=' . $id) . "&upload_error=" . urlencode($uploadError));
    exit;
}

// À partir d'ici, $sourceFile contient le chemin du fichier (temp ou uploadé)
// et $mime contient le type MIME réel.

// Prépare les dossiers
$uploadDir = __DIR__ . '/uploads/';
$thumbDir = __DIR__ . '/uploads/thumbnails/';

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    $uploadError = "Impossible de créer le dossier uploads.";
}
if (!$uploadError && !is_dir($thumbDir) && !mkdir($thumbDir, 0755, true)) {
    $uploadError = "Impossible de créer le dossier thumbnails.";
}

if ($uploadError) {
    // Si c'était un fichier temp Base64, le supprimer avant de rediriger
    if (isset($tempFile) && file_exists($tempFile)) {
        unlink($tempFile);
    }
    header("Location: " . base_url('snake.php?id=' . $id) . "&upload_error=" . urlencode($uploadError));
    exit;
}

// Nom unique pour les deux fichiers
$filename = bin2hex(random_bytes(12)) . '.' . $extension;
$originalDest = $uploadDir . $filename;
$thumbDest = $thumbDir . $filename;

// Déplacement de l'original (copie si c'est un fichier temp)
if (isset($tempFile)) {
    // Si c'est un fichier temp Base64, on le déplace/copie (dépend de la fonction)
    if (!copy($sourceFile, $originalDest)) {
        $uploadError = "Erreur lors de la copie du fichier original Base64.";
    }
    unlink($sourceFile); // Supprime le fichier temporaire
} else {
    // Si c'est un upload standard, on le déplace
    if (!move_uploaded_file($sourceFile, $originalDest)) {
        $uploadError = "Erreur lors du déplacement du fichier uploadé.";
    }
}

if ($uploadError) {
    header("Location: " . base_url('snake.php?id=' . $id) . "&upload_error=" . urlencode($uploadError));
    exit;
}

// Redimensionnement et sauvegarde de la vignette (Thumb)
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

    // Sauvegarde de la vignette (Thumb)
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

// Ajout d'un message de succès
session_start();
$_SESSION['success_message'] = "Photo uploadée et redimensionnée avec succès !";

header("Location: " . base_url('snake.php?id=' . $id));
exit;
