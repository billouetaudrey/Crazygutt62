<?php
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];

    // Check for a specific redirection URL
    $redirect_url = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : null;

    $stmt = $pdo->prepare("DELETE FROM clutches WHERE id=?");
    $stmt->execute([$id]);

    // Redirect to the specified URL or to the snake's page as a fallback
    if ($redirect_url) {
        header("Location: " . $redirect_url);
    } elseif (isset($_POST['snake_id'])) {
        header("Location: snake.php?id=" . (int)$_POST['snake_id']);
    } else {
        // Fallback to the main index page if no specific redirect is provided
        header("Location: index.php");
    }
    exit;
}
?>
