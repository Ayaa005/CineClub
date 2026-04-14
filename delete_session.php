<?php
require '../config/db.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'organizer') {
    header("Location: /cineclub/welcome.php"); exit;
}

$sid = intval($_POST['session_id'] ?? 0);
if ($sid > 0) {
    // Supprimer d'abord tous les enregistrements liés
    $pdo->prepare("DELETE FROM session_participants WHERE session_id=?")->execute([$sid]);
    $pdo->prepare("DELETE FROM snacks WHERE session_id=?")->execute([$sid]);
    $pdo->prepare("DELETE FROM session_ratings WHERE session_id=?")->execute([$sid]);
    $pdo->prepare("DELETE FROM gallery WHERE session_id=?")->execute([$sid]);
    $pdo->prepare("DELETE FROM invitations WHERE session_id=?")->execute([$sid]);
    // Puis supprimer la session
    $pdo->prepare("DELETE FROM sessions WHERE id=?")->execute([$sid]);
}

header("Location: /cineclub/planning.php"); exit;
?>
