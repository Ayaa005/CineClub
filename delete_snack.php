<?php
require '../config/db.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'organizer') {
    header("Location: /cineclub/snacks.php"); exit;
}

$snack_id = intval($_POST['snack_id'] ?? 0);
if ($snack_id > 0) {
    $pdo->prepare("DELETE FROM snacks WHERE id=?")->execute([$snack_id]);
}

header("Location: /cineclub/snacks.php"); exit;
?>
