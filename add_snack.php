<?php
require '../config/db.php';session_start();
if(!isset($_SESSION['role'])||$_SESSION['role']!=='organizer'){header("Location: /cineclub/welcome.php");exit;}
$sid=intval($_POST['session_id']??0);
$name=htmlspecialchars(trim($_POST['name']??''));
$emoji=htmlspecialchars(trim($_POST['emoji']??''))??'🍿';
if(empty($name)){header("Location: /cineclub/snacks.php");exit;}
$pdo->prepare("INSERT INTO snacks(session_id,name,emoji,status)VALUES(?,?,?,'unassigned')")
    ->execute([$sid,$name,$emoji]);
header("Location: /cineclub/snacks.php");exit;
?>
