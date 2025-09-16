<?php

// Inclure les fichiers de connexion et de fonctions
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérifier que la requête est de type POST et qu'un ID est fourni
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    try {
        // Préparer et exécuter la requête de suppression
        $stmt = $pdo->prepare('DELETE FROM gestations WHERE id = ?');
        $stmt->execute([$id]);

        // Rediriger vers la page d'origine
        $redirect_to = $_POST['redirect_to'] ?? 'index.php';
        header('Location: ' . base_url($redirect_to) . '?status=gestation_deleted');
        exit;

    } catch (PDOException $e) {
        // En cas d'erreur de base de données, afficher un message d'erreur
        die("Erreur de base de données : " . $e->getMessage());
    }
} else {
    // Si l'ID est manquant ou si la méthode n'est pas POST, rediriger avec un message d'erreur
    header('Location: ' . base_url('index.php') . '?error=missing_id');
    exit;
}
