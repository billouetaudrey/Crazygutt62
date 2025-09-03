<?php
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $snake_id = (int)$_POST['snake_id'];

    $stmt = $pdo->prepare("DELETE FROM sheds WHERE id=?");
    $stmt->execute([$id]);

    header("Location: snake.php?id=" . $snake_id);
    exit;
}
