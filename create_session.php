<?php
require '../config/db.php';session_start();
if(!isset($_SESSION['role'])||$_SESSION['role']!=='organizer'){header("Location: /cineclub/welcome.php");exit;}
$title=htmlspecialchars(trim($_POST['movie_title']??''));
$date=$_POST['session_date']??'';$time=$_POST['session_time']??'20:00';
if(empty($title)||empty($date)){header("Location: /cineclub/participants.php");exit;}
$pdo->prepare("INSERT INTO sessions(movie_title,movie_poster,session_date,session_time,status,created_by)VALUES(?,'uploads/posters/default.jpg',?,?,'upcoming',?)")
    ->execute([$title,$date,$time,$_SESSION['user_id']]);
$sid=$pdo->lastInsertId();
$pdo->prepare("INSERT IGNORE INTO session_participants(session_id,user_id,status)VALUES(?,?,'attending')")->execute([$sid,$_SESSION['user_id']]);
header("Location: /cineclub/participants.php");exit;
?>
