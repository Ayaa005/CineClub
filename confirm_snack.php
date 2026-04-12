<?php
require '../config/db.php';session_start();
if(!isset($_SESSION['role'])||$_SESSION['role']!=='organizer'){header("Location: /cineclub/welcome.php");exit;}
$sid=intval($_POST['snack_id']??0);
$pdo->prepare("UPDATE snacks SET status='confirmed' WHERE id=?")->execute([$sid]);
header("Location: /cineclub/snacks.php");exit;
?>
