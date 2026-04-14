<?php
require '../config/db.php';
session_start();

// Tout le monde connecté peut supprimer
if (!isset($_SESSION['user_id'])) {
    header("Location: /cineclub/gallery.php"); exit;
}

$photo_id = intval($_POST['photo_id'] ?? 0);
if ($photo_id > 0) {
    // Récupérer le chemin pour supprimer le fichier
    $s = $pdo->prepare("SELECT image_path FROM gallery WHERE id=?");
    $s->execute([$photo_id]);
    $photo = $s->fetch();
    if ($photo) {
        // Supprimer le fichier physique
        $file = '../' . $photo['image_path'];
        if (file_exists($file)) {
            @unlink($file);
        }
        // Supprimer de la BDD
        $pdo->prepare("DELETE FROM gallery WHERE id=?")->execute([$photo_id]);
    }
}

header("Location: /cineclub/gallery.php"); exit;
?>
