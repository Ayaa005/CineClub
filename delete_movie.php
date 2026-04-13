<?php
require '../config/db.php';session_start();
if(!isset($_SESSION['role'])||$_SESSION['role']!=='organizer'){header("Location: /cineclub/welcome.php");exit;}
$mid=intval($_POST['movie_id']??0);
$pdo->prepare("DELETE FROM votes WHERE movie_id=?")->execute([$mid]);
$pdo->prepare("DELETE FROM movie_suggestions WHERE id=?")->execute([$mid]);
header("Location: /cineclub/voting.php");exit;
?>
